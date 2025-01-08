<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Blocks_Compatibility
 */
class Blocks_Compatibility {

    /**
     * Method __construct
     *
     * @return void
     */
    public function __construct() {
        add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'init' ) );
    }
    
    /**
     * Method init
     *
     * @param \PaymentMethodRegistry $payment_method_registry
     *
     * @return void
     */
    public function init( $payment_method_registry ) {
        
        require_once( plugin_dir_path( __FILE__ ) . 'dibsy-checkout-block.php' );

        $payment_method_registry->register( new Dibsy_Payment_Gateway_Handler( 'dibsy-v2' ) );

    }
}

( new Blocks_Compatibility() );