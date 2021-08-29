<?php


class WC_Dibsy_Gateway extends WC_Payment_Gateway
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

        $this->id = 'dibsy'; // payment gateway plugin ID
        $this->has_fields = true; // in case you need a custom credit card form
        $this->method_title = 'Dibsy Gateway';
        $this->method_description = 'Want to grow your business? Get paid on your website, or send easy to setup payment links through WhatsApp, Instagram DMs, SMS, and more.'; // will be displayed on the options page

        // gateways can support subscriptions, refunds, saved payment methods,
        $this->supports = array(
            'products',
            'refunds',
        );


        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->public_key = $this->get_option('public_key');
        $this->secret_key = $this->get_option('secret_key');

        // set the secret key so we can use it in controller
        WC_Dibsy_API::set_secret_key($this->secret_key);


        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // We need custom JavaScript to obtain a token
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        // Check if the gateway can be used.
        if (!$this->is_valid_for_use()) {
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

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable Dibsy Gateway',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'Credit Card (Dibsy)',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'Want to grow your business? Get paid on your website, or send easy to setup payment links through WhatsApp, Instagram DMs, SMS, and more.',
                'desc_tip' => true,
            ),
            'testmode' => array(
                'title' => 'Test mode',
                'label' => 'Enable Test Mode',
                'type' => 'checkbox',
                'description' => 'Place the payment gateway in test mode using test API keys.',
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'public_key' => array(
                'title' => 'Public Key',
                'type' => 'password',
            ),
            'secret_key' => array(
                'title' => 'Secret Key',
                'type' => 'password'
            )
        );
    }

    /**
     * You will need it if you want your custom credit card form, Step 4 is about it
     */
    public function payment_fields()
    {

        // ok, let's display some description before the payment form
        if ($this->testmode) {
            echo "<div id='testModeNotice'>TEST MODE ENABLED. In test mode, you can use the card number 4242 4242 4242 4242 with any future expiration date</div>";
        }



        // I will echo() the form, but you can close PHP tags and print it directly in HTML
        echo '<div id="checkout-loader-wrapper">
                <div class="checkout-loader"></div>
            </div>
            <fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" >';

        // Add this action hook if you want your custom payment gateway to support it
        do_action('woocommerce_credit_card_form_start', $this->id);


        echo '
            
            <div id="dibsy-card-form">
                <div class="col-2">
                    <div class="dibsy-input" id="card-number"></div>
                    <div class="dibsy-input-error" id="card-number-error"></div>
                </div>

               <div class="expiry-ccv">
                    <div class="col-1">
                        <div class="dibsy-input" id="expiry-date"></div>
                        <div class="dibsy-input-error" id="expiry-date-error"></div>
                    </div>

                    <div class="col-1">
                        <div class="dibsy-input" id="card-code"></div>
                        <div class="dibsy-input-error" id="card-code-error"></div>
                    </div>
               </div>

               
            </div>     
      ';

        do_action('woocommerce_credit_card_form_end', $this->id);

        echo '<div class="clear"></div></fieldset>';
    }

    public function payment_scripts()
    {

        // if our payment gateway is disabled, we do not have to enqueue JS too
        if ('no' === $this->enabled) {
            return;
        }

        // no reason to enqueue JavaScript if API keys are not set
        if (empty($this->secret_key) || empty($this->public_key)) {
            return;
        }

        // do not work with card detailes without SSL unless your website is in a test mode
        //! we will activate this when online
        // if (!$this->testmode && !is_ssl()) {
        //     return;
        // }

        // inject dibsy css and script

        wp_enqueue_style("dibsy_library_style", "https://cdn.dibsy.one/css/dibsy-1.0.0.css", [], WC_DIBSY_VERSION);
        wp_enqueue_script("dibsy_library_script", "https://cdn.dibsy.one/js/dibsy-1.0.0.js", [], WC_DIBSY_VERSION);

        // and this is our custom JS in your plugin directory 
        wp_register_script('woocommerce_dibsy_errors', plugins_url('assets/js/errors.js', WC_DIBSY_MAIN_FILE), [], WC_DIBSY_VERSION);
        wp_register_script('woocommerce_dibsy', plugins_url('assets/js/dibsy.js', WC_DIBSY_MAIN_FILE), ['jquery', 'dibsy_library_script', 'woocommerce_dibsy_errors'], WC_DIBSY_VERSION);

        // pass params to our scripts
        wp_localize_script('woocommerce_dibsy', 'dibsy_params', $this->javascript_params());

        wp_enqueue_script('woocommerce_dibsy');

        // inject custom style too
        wp_enqueue_style("dibsy_styles", plugins_url('assets/css/dibsy_styles.css', WC_DIBSY_MAIN_FILE), [], WC_DIBSY_VERSION);
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
        $supported_card_brands = WC_Dibsy_Helper::get_supported_card_brands();

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
                'visa'       => '<img src="' . WC_DIBSY_PLUGIN_URL . '/assets/images/visa.svg" class="dibsy-visa-icon dibsy-icon" alt="Visa" />',
                'amex'       => '<img src="' . WC_DIBSY_PLUGIN_URL . '/assets/images/amex.svg" class="dibsy-amex-icon dibsy-icon" alt="American Express" />',
                'mastercard' => '<img src="' . WC_DIBSY_PLUGIN_URL . '/assets/images/mc.svg" class="dibsy-mastercard-icon dibsy-icon" alt="Mastercard" />',
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