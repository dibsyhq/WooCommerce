<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentResult;
use Automattic\WooCommerce\Blocks\Payments\PaymentContext;

/**
 * Dibsy_Payment_Gateway_Handler
 * 
 * @extends AbstractPaymentMethodType
 */
final class Dibsy_Payment_Gateway_Handler extends AbstractPaymentMethodType {
        
    /**
     * Name
     *
     * @var string
     */
    protected $name = '';
    
    /**
     * Settings
     *
     * @var array
     */
    protected $settings = array();
        
    /**
     * Method __construct
     *
     * @param string $payment_method_name
     *
     * @return void
     */
    public function __construct( $payment_method_name ) {
		$this->name = $payment_method_name;
	}
        
    /**
     * Method initialize
     *
     * @return void
     */
    public function initialize() {
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();

		$this->settings                = get_option( 'woocommerce_'.$this->name.'_settings', array(
			'title'			=> isset( $payment_gateways[$this->name]->title ) ? $payment_gateways[$this->name]->title : __( 'Pay with Click to Pay, Cards or Wallets', 'woocommerce-gateway-dibsy' ),
			'description'	=> isset( $payment_gateways[$this->name]->description ) ? $payment_gateways[$this->name]->description : __( 'You will be redirected to an external checkout page and redirected back on completion.', 'woocommerce-gateway-dibsy' )
		) );

		if ( ! isset( $this->settings['title'] ) ) {
			$this->settings['title'] = __( 'Pay with Click to Pay, Cards or Wallets', 'woocommerce-gateway-dibsy' );
		}

		if ( isset( $this->settings['secret_key'] ) ) {
			unset( $this->settings['secret_key'] );
		}

	}
    
    /**
     * Method is_active
     *
     * @return bool
     */
    public function is_active() {
		$payment_gateways_class = WC()->payment_gateways();
		$payment_gateways       = $payment_gateways_class->payment_gateways();

		return $payment_gateways[$this->name]->is_available();
	}
    
    /**
     * Method get_payment_method_script_handles
     *
     * @return array
     */
    public function get_payment_method_script_handles() {

      if ( ! $this->is_active() ) {
        return array();
      }

		$asset_path   = plugin_dir_path( WC_DIBSY_MAIN_FILE ) . '/build/index.asset.php';
		$version      = WC_DIBSY_VERSION;
		$dependencies = array();

		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$version      = is_array( $asset ) && isset( $asset['version'] ) ? $asset['version'] : $version;
			$dependencies = is_array( $asset ) && isset( $asset['dependencies'] ) ? $asset['dependencies'] : $dependencies;
		}

		wp_register_script(
			'dibsy-checkout',
			plugin_dir_url( WC_DIBSY_MAIN_FILE ) . '/build/index.js',
			$dependencies,
			$version,
			true
		);

		return array( 'dibsy-checkout' );
	}
    
    /**
     * Method get_payment_method_data
     *
     * @return array
     */
    public function get_payment_method_data() {
		return $this->settings;
	}
}
