<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provides static methods as helpers.
 *
 * @since 4.0.0
 */
class WC_Dibsy_Helper
{

    /**
     * Gets the supported card brands, taking the store's base country and currency into account.
     * For more information, please see: https://dibsy.com/docs/payments/cards/supported-card-brands.
     *
     * @since 4.9.0
     * @version 4.9.0
     * @return array
     */
    public static function get_supported_card_brands()
    {

        $supported_card_brands = ['visa', 'mastercard', 'amex'];

        return $supported_card_brands;
    }


    /**
     * Gets the supported card brands, taking the store's base country and currency into account.
     * For more information, please see: https://dibsy.com/docs/payments/debitcard/naps.
     *
     * @since 4.9.0
     * @version 4.9.0
     * @return array
     */
    public static function get_naps_card_brand()
    {

        $supported_card_brands = ['naps'];

        return $supported_card_brands;
    }

    /**
     * Gets all the saved setting options from a specific method.
     * If specific setting is passed, only return that.
     *
     * @since 4.0.0
     * @version 4.0.0
     * @param string $method The payment method to get the settings from.
     * @param string $setting The name of the setting to get.
     */
    public static function get_settings($method = null, $setting = null)
    {
        $all_settings = null === $method ? get_option('woocommerce_dibsy-v2_settings', []) : get_option('woocommerce_dibsy_' . $method . '_settings', []);

        if (null === $setting) {
            return $all_settings;
        }

        return isset($all_settings[$setting]) ? $all_settings[$setting] : '';
    }

    /**
     * Checks if WC version is less than passed in version.
     *
     * @since 4.1.11
     * @param string $version Version to check against.
     * @return bool
     */
    public static function is_wc_lt($version)
    {
        return version_compare(WC_VERSION, $version, '<');
    }



    /**
     * Checks if this page is a cart or checkout page.
     *
     * @since 5.2.3
     * @return boolean
     */
    public static function has_cart_or_checkout_on_current_page()
    {
        return is_cart() || is_checkout();
    }


    /**
     * Create the payment.
     *
     * @param object $order
     * @param string $method
     * @return mixed
     */
    public static function create_payment($order)
    {

        // check if we need to create customer
        $customer = null;
        if (!empty($order->get_billing_phone())) {
            $customer = WC_Dibsy_Helper::create_customer($order);
        }

        // get the description
        $products = WC()->cart->cart_contents;
        $description = '[WP] ';
        foreach ($products as $product) {
            $description .=  $product['data']->get_title() . " ";
        }

        if (empty($description)) {
            $description = '[WP Order] ' . $order->get_id();
        }

        $post_data               = [];
        $post_data['amount']     = [
            "value" => strval($order->get_total()),
            "currency" => strval($order->get_currency())
        ];
        //$post_data['method']     = ["creditcard", "applepay", "naps"];
        $post_data['description']     = $description;
        $post_data['redirectUrl'] = WC_Dibsy_Helper::getRedirectUrl($order);
        $post_data['webhookUrl'] = get_site_url() . "/?wc-api=wc_dibsy";
        $post_data['metadata']   = [
            "order_id" => $order->id
        ];

        if (!empty($customer->id)) {
            $post_data['customer_id'] = $customer->id;
        }

        WC_Dibsy_Logger::log("Info: Begin creating dibsy transaction");

        return WC_Dibsy_API::request(apply_filters("wc_dibsy_transaction", $post_data, $order));
    }



    /**
     * Create the customer.
     *
     * @param object $order
     * @return mixed
     */
    public static function create_customer($order)
    {
        $post_data  =  WC_Dibsy_Helper::getCustomerDetails($order);
        WC_Dibsy_Logger::log("Info: Begin creating customer");
        return WC_Dibsy_API::request(apply_filters("wc_dibsy_customer", $post_data, $order), 'customers');
    }




    /**
     * Get payment.
     *
     * @param string $paymentId
     * @return mixed
     */
    public static function get_payment($paymentId)
    {
        WC_Dibsy_Logger::log("Info: getting payment with ID : $paymentId");
        return WC_Dibsy_API::request(null, "payments/$paymentId", "GET");
    }

    /**
     * Builds the reditrectUrl from checkout received url.
     *
     * @param object $order
     */
    public static function getRedirectUrl($order = null)
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
     * Get customer details.
     *
     * @param object $order
     * @return object $details
     */
    public static function getCustomerDetails($order)
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

        return  apply_filters('wc_dibsy_customer_details', $details, $order);
    }
}
