<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_Dibsy_Webhook_Handler.
 *
 * Handles webhooks from Stripe on sources that are not immediately chargeable.
 *
 * @since 4.0.0
 */
class WC_Dibsy_Webhook_Handler extends WC_Dibsy_Gateway_Abstract
{
    /**
     * Delay of retries.
     *
     * @var int
     */
    public $retry_interval;

    /**
     * Constructor.
     *
     * @since 4.0.0
     * @version 5.0.0
     */
    public function __construct()
    {
        $this->retry_interval = 2;
        add_action('woocommerce_api_wc_dibsy', [$this, 'check_for_webhook']);
        $dibsy_settings  = get_option('woocommerce_dibsy-v2_settings');
        $this->secret_key = !empty($dibsy_settings['secret_key']) ? $dibsy_settings['secret_key'] : '';
        WC_Dibsy_Webhook_State::get_monitoring_began_at();
    }

    /**
     * Check incoming requests for Dibsy Webhook data and process them.
     *
     * @since 4.0.0
     * @version 5.0.0
     */
    public function check_for_webhook()
    {
        if (
            !isset($_SERVER['REQUEST_METHOD'])
            || ('POST' !== $_SERVER['REQUEST_METHOD'])
            || !isset($_GET['wc-api'])
            || ('wc_dibsy' !== $_GET['wc-api'])
        ) {
            return;
        }

        $request_body    = file_get_contents('php://input');
        $request_headers = array_change_key_case($this->get_request_headers(), CASE_UPPER);

        // Validate it to make sure it is legit.
        $validation_result = $this->validateRequest($request_headers, $request_body);
        if (WC_Dibsy_Webhook_State::VALIDATION_SUCCEEDED === $validation_result) {
            $this->process_webhook_payment($request_body);

            $notification = json_decode($request_body);
            WC_Dibsy_Webhook_State::set_last_webhook_success_at($notification->createdAt);

            status_header(200);
            exit;
        } else {
            WC_Dibsy_Logger::log('Incoming webhook failed validation: ' . print_r($request_body, true));
            WC_Dibsy_Webhook_State::set_last_webhook_failure_at(time());
            WC_Dibsy_Webhook_State::set_last_error_reason($validation_result);

            status_header(400);
            exit;
        }
    }

    /**
     * Verify the incoming webhook notification to make sure it is legit.
     *
     * @since 4.0.0
     * @version 5.0.0
     * @param array $headers The request headers from Dibsy.
     * @param array $body    The request body from Dibsy.
     * @return string The validation result (e.g. self::VALIDATION_SUCCEEDED )
     */
    public function validateRequest($headers, $body)
    {
        if (empty($headers)) {
            WC_Dibsy_Logger::log('Webhook - Missing headers');
            return WC_Dibsy_Webhook_State::VALIDATION_FAILED_EMPTY_HEADERS;
        }
        if (empty($body)) {
            WC_Dibsy_Logger::log('Webhook - Missing body');
            return WC_Dibsy_Webhook_State::VALIDATION_FAILED_EMPTY_BODY;
        }

        /*             // Check for signature.
        if (empty($headers['X-DIBSY-SIGNATURE'])) {
            WC_Dibsy_Logger::log('Webhook - Missing signature');
            return WC_Dibsy_Webhook_State::VALIDATION_FAILED_SIGNATURE_INVALID;
        }

        // create the uri
        $body_decoded = json_decode($body);
        $url_parsed = parse_url($body_decoded->webhookUrl);
        $uri = isset($url_parsed["path"]) ? $url_parsed["path"]."?".$url_parsed["query"] : "/?".$url_parsed["query"];


        if (!$this->verfiySignature($headers['X-DIBSY-SIGNATURE'], $this->secret_key, "POST", $uri, $headers['X-DIBSY-TIMESTAMP'], $body)) {
           return WC_Dibsy_Webhook_State::VALIDATION_FAILED_SIGNATURE_MISMATCH;
        } */


        return WC_Dibsy_Webhook_State::VALIDATION_SUCCEEDED;
    }


    /**
     * Gets the incoming request headers. Some servers are not using
     * Apache and "getallheaders()" will not work so we may need to
     * build our own headers.
     *
     * @since 4.0.0
     * @version 4.0.0
     */
    public function get_request_headers()
    {
        if (!function_exists('getallheaders')) {
            $headers = [];

            foreach ($_SERVER as $name => $value) {
                if ('HTTP_' === substr($name, 0, 5)) {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }

            return $headers;
        } else {
            return getallheaders();
        }
    }


    /**
     * Process webhook charge succeeded. This is used for payment methods
     * that takes time to clear which is asynchronous. e.g. SEPA, SOFORT.
     *
     * @since 4.0.0
     * @version 4.0.0
     * @param string $body
     */
    public function process_webhook_payment($body)
    {
        $body_decoded = json_decode($body);
        $transaction_id = $body_decoded->id;

        if (!$transaction_id) {
            return;
        } else {
            // send api req to check payment in dibsy
            $response = WC_Dibsy_Helper::get_payment($transaction_id);

            if ($response->status == 404 || $response->status == 500) {
                WC_Dibsy_Logger::log("Info: (Webhook) - could not found the payment or server error payment id: {$$response->id}, details: {$response->details}");
                throw new WC_Dibsy_Exception(print_r($response, true), $response->details);
            } else {
                WC_Dibsy_Logger::log("Info: (Webhook) - Begin processing payment $transaction_id for the amount of {$response->amount->value} {$response->amount->currency}");

                $order_id = $response->metadata->order_id;
                $order = new WC_Order($order_id);


                if (!$order) {
                    WC_Dibsy_Logger::log('Info: (Webhook) - Could not find order with ID: ' . $order_id);
                    return;
                }

                if ($order->has_status(['processing', 'completed'])) {
                    return;
                }


                if (strtolower($response->status) === "succeeded") {
                    $order->update_status('processing');
                    $order->payment_complete($transaction_id);
                    $order->set_transaction_id($transaction_id);
                    $order->reduce_order_stock();
                    $order->add_order_note(sprintf("Dibsy transaction complete \nTransaction ID: %s ", $transaction_id));
                    WC_Dibsy_Logger::log('Info: (Webhook) - Transaction complete with status succeeded, Transaction ID : ' . $transaction_id);
                } else {
                    $order->update_status('failed');
                    $order->add_order_note('Dibsy was unable to process the transaction');
                    WC_Dibsy_Logger::log('Info: (Webhook) - Dibsy transaction failed : ' . $transaction_id);
                }

                $order->save();
            }
        }
    }
}

new WC_Dibsy_Webhook_Handler();
