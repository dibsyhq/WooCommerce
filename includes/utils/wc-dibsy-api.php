<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * WC_Dibsy_API class.
 *
 * Communicates with Dibsy API.
 */
class WC_Dibsy_API
{

	/**
	 * Dibsy API Endpoint
	 */
	const ENDPOINT           = 'https://api.dibsy.one/v2/';
	const DIBSY_API_VERSION = '2022-07-10';

	/**
	 * Secret API Key.
	 *
	 * @var string
	 */
	private static $secret_key = '';

	/**
	 * Set secret API Key.
	 *
	 * @param string $key
	 */
	public static function set_secret_key($secret_key)
	{
		self::$secret_key = $secret_key;
	}

	/**
	 * Get secret key.
	 *
	 * @return string
	 */
	public static function get_secret_key()
	{
		if (!self::$secret_key) {
			$options = get_option('woocommerce_dibsy-v2_settings');

			if (isset($options['secret_key'])) {
				self::set_secret_key($options['secret_key']);
			}
		}
		return self::$secret_key;
	}

	/**
	 * Generates the user agent we use to pass to API request so
	 * Dibsy can identify our application.
	 *
	 * @since 4.0.0
	 * @version 1.0.0
	 */
	public static function get_user_agent()
	{
		$app_info = [
			'name'       => 'Dibsy Payments',
			'version'    => WC_DIBSY_VERSION,
			'url'    => "https://dibsy.one"
		];

		return [
			'lang'         => 'php',
			'lang_version' => phpversion(),
			'publisher'    => 'dibsy',
			'uname'        => php_uname(),
			'application'  => $app_info,
		];
	}

	/**
	 * Generates the headers to pass to API request.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public static function get_headers()
	{
		$user_agent = self::get_user_agent();
		$app_info   = $user_agent['application'];

		$headers = apply_filters(
			'woocommerce_dibsy_request_headers',
			[
				'Authorization'  => 'Bearer ' . self::get_secret_key(),
				'Content-Type' => 'application/json',
				'Accept' => '*/*',
				'Dibsy-Version' => self::DIBSY_API_VERSION,
			]
		);

		// These headers should not be overridden for this gateway.
		$headers['User-Agent']                 = $app_info['name'] . '/' . $app_info['version'] . ' (' . $app_info['url'] . ')';
		$headers['X-Dibsy-Client-User-Agent'] = wp_json_encode($user_agent);

		return $headers;
	}

	/**
	 * Send the request to Dibsy's API
	 *
	 * @since 3.1.0
	 * @version 4.0.6
	 * @param array  $request
	 * @param string $api
	 * @param string $method
	 * @param bool   $with_headers To get the response with headers.
	 * @return stdClass|array
	 * @throws WC_Dibsy_Exception
	 */
	public static function request($request, $api = 'payments', $method = 'POST', $with_headers = false)
	{
		WC_Dibsy_Logger::log("{$api} request: " . print_r($request, true));

		$headers = self::get_headers();

		$req_body = [
			'method'  => $method,
			'headers' => $headers,
			'timeout' => 70,
		];

		if ($method == "POST") {
			$req_body = [
				'method'  => $method,
				'headers' => $headers,
				'body'    => json_encode($request),
				'timeout' => 70,
			];
		}

		$response = wp_remote_post(self::ENDPOINT . $api, $req_body);

		if (is_wp_error($response) || empty($response['body'])) {
			WC_Dibsy_Logger::log(
				'Error Response: ' . print_r($response, true) . PHP_EOL . PHP_EOL . 'Failed request: ' . print_r(
					[
						'api'             => $api,
						'request'         => $request,
					],
					true
				)
			);

			throw new WC_Dibsy_Exception(print_r($response, true), 'There was a problem connecting to the Dibsy API endpoint.');
		}

		if ($with_headers) {
			return [
				'headers' => wp_remote_retrieve_headers($response),
				'body'    => json_decode($response['body']),
			];
		}

		return json_decode($response['body']);
	}


	/**
	 * Retrieve API endpoint.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 * @param string $api
	 */
	public static function retrieve($api)
	{
		WC_Dibsy_Logger::log("{$api}");

		$response = wp_safe_remote_get(
			self::ENDPOINT . $api,
			[
				'method'  => 'GET',
				'headers' => self::get_headers(),
				'timeout' => 70,
			]
		);

		if (is_wp_error($response) || empty($response['body'])) {
			WC_Dibsy_Logger::log('Error Response: ' . print_r($response, true));
			return new WP_Error('dibsy_error', __('There was a problem connecting to the Dibsy API endpoint.', 'woocommerce-gateway-Dibsy'));
		}

		return json_decode($response['body']);
	}
}
