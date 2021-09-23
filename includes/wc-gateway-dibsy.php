<?php


class WC_Dibsy_Gateway extends WC_Dibsy_Gateway_Abstract
{

    /**
     * is the credit form inline 
     *
     * @var string
     */
    public $inline_form;

    /**
     * Is the logging enabled 
     *
     * @var string
     */
    public $logging;


    /**
     * Class constructor,
     */
    public function __construct()
    {

        $this->id = 'dibsy'; // payment gateway plugin ID
        $this->has_fields = true; // in case you need a custom credit card form
        $this->method_title = 'Dibsy';
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
        $this->inline_form       = 'yes' === $this->get_option('inline_form');
        $this->secret_key = $this->get_option('secret_key');
        $this->logging = $this->get_option('logging');

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
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable Dibsy - Credit card',
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
                'type' => 'text',
                'description' => 'Get your API keys from your dibsy account.',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'secret_key' => array(
                'title' => 'Secret Key',
                'type' => 'password',
                'description' => 'Get your API keys from your dibsy account.',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'inline_form'                      => [
                'title'       => 'Inline Credit Card Form',
                'type'        => 'checkbox',
                'description' => 'Choose the style you want to show for your credit card form. When unchecked, the credit card form will display separate credit card number field, expiry date field and cvc field.',
                'default'     => 'no',
                'desc_tip'    => true,
            ],
            'logging'                             => [
                'title'       => 'Logging',
                'label'       => 'Log debug messages',
                'type'        => 'checkbox',
                'description' => 'Save debug messages to the WooCommerce System Status log.',
                'default'     => 'no',
                'desc_tip'    => true,
            ],
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


        $inlineClass = $this->inline_form ? "inline-form" : '';

        // I will echo() the form, but you can close PHP tags and print it directly in HTML
        echo '<div id="checkout-loader-wrapper">
                <div class="checkout-loader"></div>
            </div>
            <fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="' . $inlineClass . ' wc-credit-card-form wc-payment-form" >';

        // Add this action hook if you want your custom payment gateway to support it
        do_action('woocommerce_credit_card_form_start', $this->id);

        if ($this->inline_form) {
            echo '
            <div id="dibsy-card-form" class="inline">
                    <div class="field">
                        <div class="dibsy-input" id="card-number"></div>
                    </div>
                    <div class="field">
                        <div class="dibsy-input" id="expiry-date"></div>
                    </div>
                    <div class="field">
                        <div class="dibsy-input" id="card-code"></div> 
                    </div>
            </div>
            <div class="dibsy-input-error" id="card-number-error"></div>
            <div class="dibsy-input-error" id="expiry-date-error"></div>
            <div class="dibsy-input-error" id="card-code-error"></div>';
        } else {
            echo '
            <div id="dibsy-card-form">
                <div class="dibsy-col-2">
                    <div class="dibsy-input" id="card-number"></div>
                    <div class="dibsy-input-error" id="card-number-error"></div>
                </div>
               <div class="expiry-ccv">
                    <div class="dibsy-col-1">
                        <div class="dibsy-input" id="expiry-date"></div>
                        <div class="dibsy-input-error" id="expiry-date-error"></div>
                    </div>
                    <div class="dibsy-col-1">
                        <div class="dibsy-input" id="card-code"></div>
                        <div class="dibsy-input-error" id="card-code-error"></div>
                    </div>
               </div>
            </div>';
        }



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
        $dibsy_params['inline_form']               = $this->inline_form;
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
}
