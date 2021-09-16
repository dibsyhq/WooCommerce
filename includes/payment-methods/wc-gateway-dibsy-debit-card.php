<?php


class WC_Dibsy_Debit_Gateway extends WC_Payment_Gateway
{


    /**
     * API access secret key
     *
     * @var string
     */
    public $secret_key;

    /**
     * Api access publishable key
     *
     * @var string
     */
    public $public_key;

    /**
     * Is test mode active?
     *
     * @var bool
     */
    public $testmode;


    /**
     * Class constructor,
     */
    public function __construct()
    {

        $this->id = 'dibsy_debit'; // payment gateway plugin ID
        $this->has_fields = true; // in case you need a custom credit card form
        $this->method_title = 'Dibsy - NAPS';
        $this->method_description = sprintf('All other general Dibsy settings can be adjusted <a href="%s">here</a>.', admin_url('admin.php?page=wc-settings&tab=checkout&section=dibsy'));


        // gateways can support subscriptions, refunds, saved payment methods,
        $this->supports = array(
            'products',
            'refunds',
            "pre-orders"
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        $dibsy_settings  = get_option('woocommerce_dibsy_settings');

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = (!empty($dibsy_settings['testmode']) && 'yes' === $dibsy_settings['testmode']) ? true : false;
        $this->public_key = !empty($dibsy_settings['public_key']) ? $dibsy_settings['public_key'] : '';
        $this->secret_key = !empty($dibsy_settings['secret_key']) ? $dibsy_settings['secret_key'] : '';

        // set the secret key so we can use it in controller
        WC_Dibsy_API::set_secret_key($this->secret_key);


        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));


        // Check if the gateway can be used.
        if (!$this->is_valid_for_use() || $this->testmode) {
            $this->enabled = false;
        }
    }


    /**
     * Check if this gateway is enabled and available in the user's country.
     */
    public function is_valid_for_use()
    {
        if (!in_array(get_woocommerce_currency(), apply_filters('woocommerce_dibsy_supported_currencies', array('QAR')))) {

            $this->msg = sprintf('Dibsy does not support your store currency. Kindly set your store currency to QAR from <a href="%s">here</a>', admin_url('admin.php?page=wc-settings&tab=general'));

            return false;
        }

        return true;
    }

  
    public function init_form_fields()
    {

        $this->form_fields = array(

            'dibsy_general_settings' => array(
                'type' => 'title',
                'description' => sprintf('All other general Dibsy settings can be adjusted <a href="%s">here</a>.', admin_url('admin.php?page=wc-settings&tab=checkout&section=dibsy')),
            ),
            'activation' => array(
                'type' => 'title',
                'description' => "Must be activated from your Dibsy Dashboard Settings <a href='https://dashboard.dibsy.one/settings/payment-methods' target='_blank'>here</a>",
            ),
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable Dibsy - NAPS',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'NAPS',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'You will be redirected to Bancontact.',
                'desc_tip' => true,
            )
        );
    }

    /**
     * You will need it if you want your custom credit card form, Step 4 is about it
     */
    public function payment_fields()
    {

        global $wp;
        $total       = WC()->cart->total;
        $description = $this->get_description();

        // If paying from order, we need to get total from order not cart.
        if (isset($_GET['pay_for_order']) && !empty($_GET['key'])) {
            $order = wc_get_order(wc_clean($wp->query_vars['order-pay']));
            $total = $order->get_total();
        }


        echo '<div
			id="dibsy-naps-payment-data">';

        if ($description) {
            echo apply_filters('wc_dibsy_description', wpautop(wp_kses_post($description)), $this->id);
        }

        echo '</div>';
    }


    /*
      * Fields validation,
     */
    public function validate_fields()
    {
        return true;
    }

    /*
     * We're processing the payments here
     */
    public function process_payment($order_id)
    {
       
        try {
            $order = wc_get_order($order_id);

            // create payment 
            $response = $this->create_payment($order);

            if (!empty($response->error)) {
                $order->add_order_note($response->error->message);

                throw new WC_Dibsy_Exception(print_r($response, true), $response->error->message);
            }

            $order->update_meta_data('dibsy_transaction_id', $response->id);
            $order->save();

            WC_Dibsy_Logger::log('Info: Redirecting to NAPS...');

            return [
                'result'   => 'success',
                'redirect' => esc_url_raw($response->_links->checkout->href),
            ];
        } catch (WC_Dibsy_Exception $e) {
            wc_add_notice( "There was an error while trying to redirect to NAPS", 'error' );
            WC_Dibsy_Logger::log( 'Error: ' . $e->getMessage() );
            return ;
        }
    }

    /**
     * Builds the reditrectUrl from checkout received url.
     *
     * @param object $order
     */
    public function getRedirectUrl($order = null)
    {
        if (is_object($order)) {
            $order_id = $order->get_id();
            $orderReceivedUrl = $order->get_checkout_order_received_url();

            $args = [
                'utm_nooverride' => '1',
                'order_id'       => $order_id,
            ];

            return wp_sanitize_redirect(esc_url_raw(add_query_arg($args, $orderReceivedUrl)));
        }

        return "";
    }


    /**
     * Creates the payment for debit card for charge.
     *
     * @param object $order
     * @return mixed
     */
    public function create_payment($order)
    {
        $currency                = $order->get_currency();
        $post_data               = [];
        $post_data['amount']     = $order->get_total();
        $post_data['currency']   = strtolower($currency);
        $post_data['method']       = 'debitcard';
        $post_data['customer']      = $this->getCustomerDetails($order);
        $post_data['redirectUrl']   = $this->getRedirectUrl($order);


        WC_Dibsy_Logger::log('Info: Begin creating naps transaction');

        return WC_Dibsy_API::request(apply_filters('wc_dibsy_naps_transaction', $post_data, $order));
    }


    /**
     * Get customer details.
     *
     * @param object $order
     * @return object $details
     */
    public function getCustomerDetails($order)
    {
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name  = $order->get_billing_last_name();

        $details = [];

        $name  = $billing_first_name . ' ' . $billing_last_name;
        $email = $order->get_billing_email();
        $phone = $order->get_billing_phone();

        if (!empty($phone)) {
            $details['phone'] = $phone;
        }

        if (!empty($name)) {
            $details['name'] = $name;
        }

        if (!empty($email)) {
            $details['email'] = $email;
        }

        return (object) apply_filters('wc_dibsy_customer_details', $details, $order);
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

    /**
     * Get_icon function.
     *
     * @since 1.0.0
     * @version 4.9.0
     * @return string
     */
    public function get_icon()
    {
        $icons                 = $this->payment_icons();
        $supported_card_brands = WC_Dibsy_Helper::get_naps_card_brand();

        $icons_str = '';

        foreach ($supported_card_brands as $brand) {
            $icons_str .= isset($icons[$brand]) ? $icons[$brand] : '';
        }

        return apply_filters('woocommerce_gateway_icon', $icons_str, $this->id);
    }


    private function getLocalLanguage()
    {
        return substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    }

    /**
     * All payment icons that work with Dibsy. Some icons references
     * WC core icons.
     *
     * @since 4.0.0
     * @since 4.1.0 Changed to using img with svg (colored) instead of fonts.
     * @return array
     */
    private function payment_icons()
    {
        return apply_filters(
            'wc_dibsy_payment_icons',
            [
                'naps'       => '<img src="' . WC_DIBSY_PLUGIN_URL . '/assets/images/naps.svg" class="dibsy-naps-icon dibsy-icon" alt="naps" />',
               ]
        );
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
     * Returns the JavaScript configuration object used on the product, cart, and checkout pages.
     *
     * @return array  The configuration object to be loaded to JS.
     */
    public function javascript_params()
    {

        // get the description
        $products = WC()->cart->cart_contents;
        $description = '';
        foreach ($products as $product) {
            $description .=  $product['data']->get_title() . " ";
        }

        $dibsy_params = [];
        $dibsy_params['public_key']               = $this->public_key;
        $dibsy_params['lang']                      = $this->getLocalLanguage();
        $dibsy_params['amount']                    = $this->get_order_total();
        $dibsy_params['description']               = trim($description);
        $dibsy_params['redirectUrl']               = $this->get_redirect_url();
        $dibsy_params['ajaxurl']                   = WC_AJAX::get_endpoint('%%endpoint%%');
        $dibsy_params['dibsy_nonce']               = wp_create_nonce('_wc_dibsy_nonce');
        $dibsy_params['is_change_payment_page']    = isset($_GET['change_payment_method']) ? 'yes' : 'no'; // wpcs: csrf ok.
        $dibsy_params['user_id']                   = get_current_user_id();

        return $dibsy_params;
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
                <p><strong><?php echo 'Dibsy Payment Gateway Disabled'; ?></strong>: <?php echo esc_html($this->msg); ?></p>
            </div>

<?php
        }
    }
}


?>