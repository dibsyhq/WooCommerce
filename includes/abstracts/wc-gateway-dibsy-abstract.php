<?php


class WC_Dibsy_Gateway_Abstract extends WC_Payment_Gateway
{


    /**
     * API access secret key
     *
     * @var string
     */
    public $secret_key;

    /**
     * Is test mode active?
     *
     * @var bool
     */
    public $testmode;

        /**
	 * Holds the method.
	 *
	 * @var String
	 */
	public $method;





    /**
     * Check if this gateway is enabled and available in the user's country.
     */
    public function is_valid_for_use()
    {
        if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_dibsy_supported_currencies', array('QAR')))) {

            $this->msg = sprintf("Dibsy does not support your store currency. Kindly set your store currency to QAR from");

            return false;
        }

        return true;
    }



 

    /*
      * Fields validation,
     */
    public function validate_fields()
    {
        return true;
    }

    

    /*
     * We're processing the refunds here
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {

        // Do your refund here. Refund $amount for the order with ID $order_id
        $order = wc_get_order($order_id);

        if (!$order) {
            return false;
        }

        $request = [];
        $payment_id      = $order->get_transaction_id();
        $order_currency = $order->get_currency();

        if (!$payment_id) {
            $errors = new WP_Error();
            $errors->add("transaction_id_empty", "The transaction ID is empty, please request a manually refund.");
            return $errors;
        }

        $request['amount'] = $order->get_total();
        if (!is_null($amount)) {
            $request['amount'] = $amount;
        }

        if ($reason) {
            // Trim the refund reason to a max of 100 characters.
            if (strlen($reason) > 100) {
                $reason = function_exists('mb_substr') ? mb_substr($reason, 0, 80) : substr($reason, 0, 80);
                // Add some explainer text indicating where to find the full refund reason.
                // $reason = $reason . '... [See WooCommerce order page for full text.]';
            }

            $request['description'] =  $reason;
        }
        try {
            $refund = WC_Dibsy_API::request($request, "payments/$payment_id/refunds");
            if (!empty($refund->error)) {
                $error_response_message = print_r($refund, true);
                WC_Dibsy_Logger::log('Failed to request the refund');
                WC_Dibsy_Logger::log("Response: $error_response_message");
                throw new WC_Dibsy_Exception("Their was an error while requesting refund for the transaction ID $payment_id");
            } else {
                $order->add_order_note("Refunded $amount $order_currency \nRefund ID: {$refund->id} \nReason: $reason");
                return true;
            }
        } catch (WC_Dibsy_Exception $e) {
            $errors = new WP_Error();
            $errors->add("refund_error", $e->getMessage());
            return $errors;
        }
    }



    protected function getLocalLanguage()
    {
        return substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    }

  
    /**
     * Builds the return URL from redirects.
     *
     * @since 4.0.0
     * @version 4.0.0
     */
    public function get_redirect_url()
    {
        return wp_sanitize_redirect(esc_url_raw($this->get_return_url()));
    }


    /**
     * Admin Panel Options.
     */
    public function admin_options()
    {

?>

        <h2><?php echo 'Dibsy Settings' ?>
            <?php
            if (function_exists('wc_back_link')) {
                wc_back_link('Return to payments', admin_url('admin.php?page=wc-settings&tab=checkout'));
            }
            ?>
        </h2>

        <?php

        if ($this->is_valid_for_use()) {

            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        } else {
        ?>
            <div class="inline error">
                <p><strong>Dibsy Payment Gateway Disabled: </strong> Dibsy does not support your store currency. Kindly set your store currency to QAR from <a href=<?=esc_html(admin_url('admin.php?page=wc-settings&tab=general')) ?>>here</a></p>
            </div>

<?php
        }
    }
}


?>