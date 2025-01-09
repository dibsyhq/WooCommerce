<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Dibsy_Apple_Pay_Controller
 */
class WC_Dibsy_Apple_Pay_Controller {
            
    /**
     * Base
     *
     * @var string
     */
    public $base = 'https://api.dibsy.one/v2/';
        
    /**
     * Session
     *
     * @var string
     */
    public $session = 'wallets/applepay/session';
        
    /**
     * Method __construct
     *
     * @return void
     */
    public function __construct() {

        $settings = get_option( 'woocommerce_dibsy-v2_settings', array() );

        if ( ! empty( $settings ) && isset( $settings['enabled'] ) && 'yes' == $settings['enabled'] ) {
            
            add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'express_checkout_button_html' ), 1 );
            add_action( 'woocommerce_pay_order_before_payment', array( $this, 'express_checkout_button_html' ), 1 );

            add_action( 'wp_ajax_initialize_apple_pay', array( $this, 'initialize' ) );
            add_action( 'wp_ajax_nopriv_initialize_apple_pay', array( $this, 'initialize' ) );

            add_action( 'wp_ajax_merchant_session_apple_pay', array( $this, 'merchant_session' ) );
            add_action( 'wp_ajax_nopriv_merchant_session_apple_pay', array( $this, 'merchant_session' ) );

            add_action( 'wp_ajax_process_payment', array( $this, 'process_payment' ) );
            add_action( 'wp_ajax_nopriv_process_payment', array( $this, 'process_payment' ) );

            add_action( 'woocommerce_before_thankyou', array( $this, 'display_payment_error' ) );

        }
    }
    
    /**
     * Method express_checkout_button_html
     *
     * @return void
     */
    public function express_checkout_button_html() {
        ?>
            <style>
                apple-pay-button {
                --apple-pay-button-width: 140px;
                --apple-pay-button-height: 48px;
                --apple-pay-button-border-radius: 5px;
                --apple-pay-button-padding: 5px 0px;
                }
            </style>
            <div class="apple-pay-container">
                <apple-pay-button buttonstyle="black" type="plain" locale="en" style="width: 100%;"></apple-pay-button>
                <p id="wc-disby-express-checkout-button-separator" style="margin-top:1.5em;text-align:center;display:none;">&mdash; <?php esc_html_e( 'OR', 'woocommerce-gateway-dibsy' ); ?> &mdash;</p>
            </div>
        <?php
    }
        
    /**
     * Method initialize
     *
     * @return void
     */
    public function initialize() {
        $order_id = filter_input( 
            INPUT_POST, 'order_id', FILTER_SANITIZE_SPECIAL_CHARS );

		if ( ! empty( $order_id ) ) {
			$order = wc_get_order( $order_id );
			$total = $order->get_total();
		} else {
			$total = WC()->cart->get_total( 'float' );
		}
		
        wp_send_json(
            array(
                'countryCode'           => 'QA',
                'currencyCode'          => get_woocommerce_currency(),
                'merchantCapabilities'  => array( 'supports3DS' ),
                'supportedNetworks'     => array( 'visa', 'masterCard' ),
                "requiredShippingContactFields" => [
                    "postalAddress",
                    "name",
                    "phone",
                    "email"
                ],
                'total'                 => array(
                    'label' => sprintf('%s Order Payment', get_bloginfo( 'name' )),
                    'type'  => 'final',
                    'amount'=> $total,
                ),
            )
        );
    }
    
    /**
     * Method merchant_session
     *
     * @return void
     */
    public function merchant_session() {
        check_ajax_referer( 'dibsy-applepay-payment', 'security' );

        $request_body = array(
            'validationUrl' => 'https://apple-pay-gateway.apple.com/paymentservices/paymentSession',
            'domain'        => parse_url( home_url(), PHP_URL_HOST ),
        );

        $response = WC_Dibsy_API::request( 
            $request_body,
            $this->session,

        );
        
        wp_send_json_success($response);
    }
    
    /**
     * Method process_payment
     *
     * @return void
     */
    public function process_payment() {
        check_ajax_referer( 'dibsy-applepay-payment', 'security' );
        
        $post = $_POST;
        
        if ( empty( $post['post_data'] ) ) {
            wp_send_json_error(
                array(
                    'error' => esc_html__( 'Invalid Payment. Shipping contact missing from ApplePay.', 'woocommerce-gateway-dibsy' )
                )
            );
        }

        $post_data          = json_decode( stripcslashes( $post['post_data'] ), true );

        if ( empty( $post_data['token'] ) ) {
            wp_send_json_error(
                array(
                    'error' => esc_html__( 'Invalid Payment. Payment token is missing', 'woocommerce-gateway-dibsy' )
                )
            );
        }

        if ( isset( $post['order_id'] ) && ! empty( $post['order_id'] ) ) {
            $order              = wc_get_order( $post['order_id'] );
        } else {
            $order              = $this->create_order( $post_data );
        }
        
        if ( $order instanceof WC_Order ) {
            
            $payment_response   = $this->post_payment( $post_data['token'], $order->get_id() );
        
            WC()->cart->empty_cart();

            if ( isset( $payment_response['status'] ) && 'succeeded' == $payment_response['status'] ) {
                $transaction_id = isset( $payment_response['id'] ) ? $payment_response['id'] : '';
                
                $order->set_transaction_id( $transaction_id );
                $order->add_order_note('Set charge_id: ' . $transaction_id, false);

                $order->update_status('processing', esc_html__( 'Changed order status from Pending Payment to Processing.', 'woocommerce-gateway-dibsy' ));
                $order->maybe_set_date_paid();

            } else {
                
                $order->update_status('failed', esc_html__( 'Changed order status from Pending Payment to Failed.', 'woocommerce-gateway-dibsy' ));

                if ( isset( $payment_response['details']->failureMessage ) ) {
                    $order->add_order_note( $payment_response['details']->failureMessage, true);
                    $order->update_meta_data( 'dibsy_payment_error', $payment_response['details']->failureMessage );
                } else {
                    $order->add_order_note( esc_html__( 'Payment failed. Please try again or contact support.', 'woocommerce-gateway-dibsy' ), true);
                }
                
            }

            $order->save();
        
            wp_send_json_success(
                array(
                    'return_url'    => $order->get_checkout_order_received_url()
                )
            );
        }

        wp_send_json_error(
            array(
                'error' => $order
            )
        );
    }
    
    /**
     * Method post_payment
     *
     * @param string $token
     * @param int $order_id
     *
     * @return array
     */
    public function post_payment( $token, $order_id ) {

        $request_body = array(
            'method'            => 'applepay',
            'amount'            => array(
                'value'             => WC()->cart->get_total( 'float' ),
                'currency'          => get_woocommerce_currency(),
            ),
            'description'       => sprintf( '%s Order #%s', get_bloginfo('name'), $order_id ),
            'metadata'          => array(
                'orderId'           => $order_id,
            ),
            'redirectUrl'       => 'https://example.com/',
            'applePayToken'     => $token
        );
    
        return (array) WC_Dibsy_API::request( 
            $request_body,
        );
    }
    
    /**
     * Method create_order
     *
     * @param array $data
     *
     * @return \WC_Order|string
     */
    public function create_order( $data ) {
        $cart = WC()->cart;
        if ( ! $cart->is_empty() ) {
            try {

                $order = wc_create_order();
                foreach ( $cart->get_cart() as $cart_item ) {
                    $product    = $cart_item['data'];
                    $quantity   = $cart_item['quantity'];
    
                    $order->add_product( $product, $quantity );
                }

                if ( ! empty( $data ) ) {
                    $order->set_address([
                        'first_name' => isset( $data['shippingName'] ) ? $data['shippingName'] : '',
                        'last_name'  => '',
                        'address_1'  => isset( $data['shippingAddress'] ) ? $data['shippingAddress'] : '',
                        'city'       => isset( $data['shippingCity'] ) ? $data['shippingCity'] : '',
                        'state'      => isset( $data['shippingState'] ) ? $data['shippingState'] : '',
                        'postcode'   => isset( $data['shippingPostalCode'] ) ? $data['shippingPostalCode'] : '',
                        'country'    => isset( $data['shippingCountry'] ) ? $data['shippingCountry'] : '',
                    ], 'shipping');

                    $order->set_address([
                        'first_name' => isset( $data['payerName'] )     ? $data['payerName'] : '',
                        'email'      => isset( $data['payerEmail'] )    ? $data['payerEmail'] : '',
                        'phone'      => isset( $data['payerPhone'] )    ? $data['payerPhone'] : '',
                        'address_1'  => isset( $data['shippingAddress'] ) ? $data['shippingAddress'] : '',
                        'city'       => isset( $data['shippingCity'] )  ? $data['shippingCity'] : '',
                        'state'      => isset( $data['shippingState'] ) ? $data['shippingState'] : '',
                        'postcode'   => isset( $data['shippingPostalCode'] ) ? $data['shippingPostalCode'] : '',
                        'country'    => isset( $data['shippingCountry'] ) ? $data['shippingCountry'] : '',
                    ], 'billing');
                }

                $order->set_payment_method('dibsy-apple_pay');

                $order->add_order_note(esc_html__( 'Order status is set to Pending Payment.', 'woocommerce-gateway-dibsy' ), false);

                if ( isset( $data['user_id'] ) && ! empty( $data['user_id'] ) ) {                     
                    $order->set_customer_id( $data['user_id'] );
                }

                $chosen_shipping_methods    = WC()->session->get('chosen_shipping_methods');
                $shipping_method_key        = ! empty( $chosen_shipping_methods ) ? $chosen_shipping_methods[0] : null;

                if ( $shipping_method_key ) {
                    list( $shipping_method_id, $instance_id ) = explode( ':', $shipping_method_key );
                    
                    $shipping_total = WC()->cart->get_shipping_total();

                    if ( ! empty( $shipping_method_id ) ) {

                        $shipping_item = new WC_Order_Item_Shipping();
                        $shipping_item->set_method_id($shipping_method_id);
                        $shipping_item->set_instance_id($instance_id);
                        $shipping_item->set_total($shipping_total);
                    
                        $shipping_methods = WC()->shipping()->get_shipping_methods();
                        if (isset($shipping_methods[$shipping_method_id])) {
                            $method_title = $shipping_methods[$shipping_method_id]->get_method_title();
                            $shipping_item->set_method_title($method_title);
                        } else {
                            $shipping_item->set_method_title(__('Unknown Shipping Method', 'woocommerce-gateway-dibsy'));
                        }

                        $order->add_item($shipping_item);
                    }
                }

                $order->calculate_totals();

                $order->save();

                wc_reduce_stock_levels($order->get_id());
                return $order;

            } catch( Exception $e ) {
                return $e->getMessage();
            }

        }
    }
    
    /**
     * Method display_payment_error on order thankyou page.
     *
     * @param int $order_id
     *
     * @return void
     */
    public function display_payment_error( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( $order->has_status( 'failed' ) ) : ?>
            <p><?php esc_html_e( $order->get_meta( 'dibsy_payment_error' ) ); ?></p>
        <?php endif;
    }

}

( new WC_Dibsy_Apple_Pay_Controller() );
