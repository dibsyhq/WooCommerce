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
        
        $this->id = 'dibsy-v2'; // payment gateway plugin ID
        $this->has_fields = false; // in case you need a custom credit card form
        $this->method_title = 'Dibsy';
        $this->method_description = 'Dibsy is a payment service provider (PSP) that allows e-commerce businesses to accept payments online. Accept payments through credit cards, debit cards and wallets in WooCommerce today. '; // will be displayed on the options page

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
        //$this->public_key = $this->get_option('public_key');
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


    public function validate_fields(){
        return true;     
    }

    public function payment_scripts()
    {
        wp_enqueue_style("dibsy_styles", plugins_url('assets/css/dibsy_styles.css', WC_DIBSY_MAIN_FILE), [], WC_DIBSY_VERSION); 
    }



    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'label' => 'Enable Dibsy Checkout',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'Pay with credit cards, Qatari debit cards and Apple Pay',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'This controls the description which the user sees during checkout.',
                'default' => 'You will be redirected to an external checkout page and redirected back on completion.',
                'desc_tip' => true,
            ),
            'testmode' => array(
                'title' => 'Test mode',
                'label' => 'Enable Test Mode',
                'type' => 'checkbox',
                'description' => 'Use Test API keys from your dashboard to emulate the checkout experience.',
                'default' => 'yes',
                'desc_tip' => true,
            ),
            /* 'public_key' => array(
                'title' => 'Public Key',
                'type' => 'text',
                'description' => 'Get your API keys from your dibsy account.',
                'default'     => '',
                'desc_tip'    => true,
            ), */
            'secret_key' => array(
                'title' => 'Secret Key',
                'type' => 'password',
                'description' => 'You can find your secret keys on your Dibsy dashboard under Settings > API Keys.',
                'default'     => '',
                'desc_tip'    => true,
            ),
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
            echo "<div id='testModeNotice'>TEST MODE ENABLED</div>";
        }

        global $wp;
        $description = $this->get_description();

        // If paying from order, we need to get total from order not cart.
        if (isset($_GET['pay_for_order']) && !empty($_GET['key'])) {
            $order = wc_get_order(wc_clean($wp->query_vars['order-pay']));
        }


        echo '<div
			id="dibsy-naps-payment-data">';

        if ($description) {
            echo apply_filters('wc_dibsy_description', wpautop(wp_kses_post($description)), $this->id);
        }

        echo '</div>';

       
    }       


      /*
     * We're processing the payments here
     */
    public function process_payment($order_id)
    {
       
        try {
            $order = wc_get_order($order_id);

            // create payment 
            $response = WC_Dibsy_Helper::create_payment($order);

            if ($response->status == 400 || $response->status == 500) {
                $order->add_order_note($response->details);

                throw new WC_Dibsy_Exception(print_r($response, true), $response->details);
            }

            $order->update_meta_data('dibsy_transaction_id', $response->id);
            $order->save();

            WC_Dibsy_Logger::log('Info: Redirecting to hosted checkout dibsy...'.  print_r($response, true));     

            return [
                'result'   => 'success',
                'redirect' => esc_url_raw($response->_links->checkout->href),
            ];
        } catch (WC_Dibsy_Exception $e) {
            wc_add_notice( "There was an error while trying to redirect to hosted checkout dibsy", 'error' );
            WC_Dibsy_Logger::log( 'Error: ' . $e->getMessage() );
            return ;
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
                'naps'       => '<img src="' . WC_DIBSY_PLUGIN_URL . '/assets/images/naps.svg" class="dibsy-naps-icon dibsy-icon" alt="Naps" />',
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
        //$dibsy_params['public_key']               = $this->public_key;
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
