<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides static methods as helpers.
 *
 * @since 4.0.0
 */
class WC_Dibsy_Helper {

	/**
	 * Gets the supported card brands, taking the store's base country and currency into account.
	 * For more information, please see: https://dibsy.com/docs/payments/cards/supported-card-brands.
	 *
	 * @since 4.9.0
	 * @version 4.9.0
	 * @return array
	 */
	public static function get_supported_card_brands() {

		$supported_card_brands = [ 'visa', 'mastercard','amex' ];

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
	public static function get_settings( $method = null, $setting = null ) {
		$all_settings = null === $method ? get_option( 'woocommerce_dibsy_settings', [] ) : get_option( 'woocommerce_dibsy_' . $method . '_settings', [] );

		if ( null === $setting ) {
			return $all_settings;
		}

		return isset( $all_settings[ $setting ] ) ? $all_settings[ $setting ] : '';
	}

	/**
	 * Checks if WC version is less than passed in version.
	 *
	 * @since 4.1.11
	 * @param string $version Version to check against.
	 * @return bool
	 */
	public static function is_wc_lt( $version ) {
		return version_compare( WC_VERSION, $version, '<' );
	}



	/**
	 * Checks if this page is a cart or checkout page.
	 *
	 * @since 5.2.3
	 * @return boolean
	 */
	public static function has_cart_or_checkout_on_current_page() {
		return is_cart() || is_checkout();
	}
}
