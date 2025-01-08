<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class WC_Dibsy_NAPS_Gateway extends WC_Dibsy_Gateway_Abstract
{



 
    /**
     * Class constructor,
     */
    public function __construct()
    {

        $this->method="naps";
        $this->id = 'dibsy_naps';
        $this->has_fields = true; 
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

        $dibsy_settings  = get_option('woocommerce_dibsy-v2_settings');

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = (!empty($dibsy_settings['testmode']) && 'yes' === $dibsy_settings['testmode']) ? true : false;
        //$this->public_key = !empty($dibsy_settings['public_key']) ? $dibsy_settings['public_key'] : '';
        $this->secret_key = !empty($dibsy_settings['secret_key']) ? $dibsy_settings['secret_key'] : '';
        $this->logging = $this->get_option('logging');
        

        // set the secret key so we can use it in controller
        WC_Dibsy_API::set_secret_key($this->secret_key);


        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));


        // Check if the gateway can be used.
        if (!$this->is_valid_for_use() || $this->testmode) {
            $this->enabled = false;
        }
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
                'default' => 'You will be redirected to NAPS.',
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
        $description = $this->get_description();

        // If paying from order, we need to get total from order not cart.
        if (isset($_GET['pay_for_order']) && !empty(wc_clean($_GET['key']))) {
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
            $response = WC_Dibsy_Helper::create_payment($order,$this->method);

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




  
}


?>