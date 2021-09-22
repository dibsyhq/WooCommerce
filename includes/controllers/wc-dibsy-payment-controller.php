<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * WC_Dibsy_Payments_Controller class.
 *
 * Handles in-checkout AJAX calls, related to Payments.
 */
class WC_Dibsy_Payments_Controller
{
	/**
	 * Holds an instance of the gateway class.
	 *
	 * @since 4.2.0
	 * @var WC_Dibsy_Gateway
	 */
	protected $gateway;

	/**
	 * Class constructor, adds the necessary hooks.
	 *
	 * @since 4.2.0
	 */
	public function __construct()
	{
		add_action('wc_ajax_wc_dibsy_verify_payment', [$this, 'verify_payment']);
		add_action('wc_ajax_wc_dibsy_create_payment', [$this, 'create_payment']);
		add_action('wc_ajax_wc_dibsy_create_order', [$this, 'create_order']);
		add_action('wc_ajax_wc_dibsy_update_order', [$this, 'update_order']);
	}

	/**
	 * Returns an instantiated gateway.
	 *
	 * @since 4.2.0
	 * @return WC_Dibsy_Gateway
	 */
	protected function get_gateway()
	{
		if (!isset($this->gateway)) {
			$class_name = 'WC_Dibsy_Gateway';
			$this->gateway = $class_name;
		}

		return $this->gateway;
	}


	/**
	 * Handles successful PaymentIntent authentications.
	 *
	 * @since 4.2.0
	 */
	public function verify_payment()
	{
	}

	/**
	 * Handles exceptions during intent verification.
	 *
	 * @since 4.2.0
	 * @param WC_Dibsy_Exception $e           The exception that was thrown.
	 * @param string              $redirect_url An URL to use if a redirect is needed.
	 */
	protected function handle_error($e, $redirect_url)
	{
		// Log the exception before redirecting.
		$message = sprintf('Payment verification exception: %s', $e->getLocalizedMessage());
		WC_Dibsy_Logger::log($message);

		// `is_ajax` is only used for PI error reporting, a response is not expected.
		if (isset($_GET['is_ajax'])) {
			exit;
		}

		wp_safe_redirect($redirect_url);
		exit;
	}

	/**
	 * Creates a Payment through AJAX.
	 */
	public function create_payment()
	{
		$data = [
			"lang" => sanitize_text_field($_POST["lang"]),
			"amount" => sanitize_text_field($_POST["amount"]),
			"redirectUrl" => sanitize_url($_POST["redirectUrl"]),
			"customer" => [
				"phone" => sanitize_text_field($_POST["customer"]["phone"]),
				"name" => sanitize_text_field($_POST["customer"]["name"]),
				"email" => sanitize_email($_POST["customer"]["email"]),
			]
		];

		if (
			empty($data['amount'])
			|| empty($data['redirectUrl'])
		) {
			die;
		}


		try {
			$init_payment = WC_Dibsy_API::request($data);
			if (!empty($init_payment->error)) {
				$error_response_message = print_r($init_payment, true);
				WC_Dibsy_Logger::log('Failed init a payment');
				WC_Dibsy_Logger::log("Response: $error_response_message");
				throw new WC_Dibsy_Exception('Their was an error while initiaizing the transaction, refrech the page and continue');
			} else {
				$response = [
					'status' => 'success',
					'transaction' => $init_payment
				];
			}
		} catch (WC_Dibsy_Exception $e) {
			$response = [
				'status' => 'error',
				'error'  => [
					'type'    => 'create_payment_error',
					'message' => $e->getMessage(),
				],
			];
		}

		echo wp_json_encode($response);
		exit;
	}

	/**
	 * create an order through ajax
	 */

	public function create_order()
	{
		if (isset($_POST['fields']) && !empty($_POST['fields'])) {
			$order    = new WC_Order();
			$cart     = WC()->cart;
			$checkout = WC()->checkout;
			$data     = [];

			// Loop through posted data array transmitted via jQuery
			foreach ($_POST['fields'] as $values) {
				// Set each key / value pairs in an array
				$data[sanitize_text_field(($values['name']))] = sanitize_text_field($values['value']);
			}

			$cart_hash = md5(json_encode(wc_clean($cart->get_cart_for_session())) . $cart->total);
			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

			// validate order fields first
			$errors = $this->validateOrderFields($data);
			if (count($errors) > 0) {
				echo wp_json_encode(["errors" => $errors]);
			} else {
				// Loop through the data array
				foreach ($data as $key => $value) {
					// Use WC_Order setter methods if they exist
					if (is_callable(array($order, "set_{$key}"))) {
						$order->{"set_{$key}"}($value);
						// Store custom fields prefixed with wither shipping_ or billing_
					} elseif ((0 === stripos($key, 'billing_') || 0 === stripos($key, 'shipping_'))
						&& !in_array($key, array('shipping_method', 'shipping_total', 'shipping_tax'))
					) {
						$order->update_meta_data('_' . $key, $value);
					}
				}

				$user_id = sanitize_text_field($_POST['user_id']);

				$order->set_created_via('checkout');
				$order->set_cart_hash($cart_hash);
				$order->set_customer_id(apply_filters('woocommerce_checkout_customer_id', !isset($user_id) ? $user_id : ''));
				$order->set_currency(get_woocommerce_currency());
				$order->set_prices_include_tax('yes' === get_option('woocommerce_prices_include_tax'));
				$order->set_customer_ip_address(WC_Geolocation::get_ip_address());
				$order->set_customer_user_agent(wc_get_user_agent());
				$order->set_customer_note(isset($data['order_comments']) ? $data['order_comments'] : '');
				$order->set_payment_method(isset($available_gateways[$data['payment_method']]) ? $available_gateways[$data['payment_method']]  : $data['payment_method']);
				$order->set_shipping_total($cart->get_shipping_total());
				$order->set_discount_total($cart->get_discount_total());
				$order->set_discount_tax($cart->get_discount_tax());
				$order->set_cart_tax($cart->get_cart_contents_tax() + $cart->get_fee_tax());
				$order->set_shipping_tax($cart->get_shipping_tax());
				$order->set_total($cart->get_total('edit'));

				$checkout->create_order_line_items($order, $cart);
				$checkout->create_order_fee_lines($order, $cart);
				$checkout->create_order_shipping_lines($order, WC()->session->get('chosen_shipping_methods'), WC()->shipping->get_packages());
				$checkout->create_order_tax_lines($order, $cart);
				$checkout->create_order_coupon_lines($order, $cart);

				/**
				 * Action hook to adjust order before save.
				 * @since 3.0.0
				 */
				do_action('woocommerce_checkout_create_order', $order, $data);

				// Save the order.
				$order_id = $order->save();

				do_action('woocommerce_checkout_update_order_meta', $order_id, $data);

				echo wp_json_encode(["order_id" => $order_id, "amount" => $order->get_total()]);
			}
		}
		die();
	}

	/**
	 * update an order through ajax
	 */
	public function update_order()
	{

		global $woocommerce;
		$transaction_id = sanitize_text_field($_POST['transaction_id']);
		$order_id = sanitize_text_field($_POST['order_id']);
		if (!empty($order_id) && !empty($transaction_id)) {

			$order = wc_get_order($order_id);
			$order->update_status('processing');
			// we received the payment
			$order->payment_complete($transaction_id);
			$order->set_transaction_id($transaction_id);
			$order->reduce_order_stock();
			// some notes to customer/private
			$order->add_order_note("Dibsy transaction complete \nTransaction ID: $transaction_id");
			$order->save();

			// Empty cart
			$woocommerce->cart->empty_cart();
			echo wp_json_encode([
				"order" => [
					"order_key" => $order->get_order_key(),
					"order_id" => $order->get_order_number()
				]
			]);
		} else {
			die();
		}
	}


	/**
	 * validate cart fields to create order
	 */
	public function validateOrderFields($data)
	{
		$errors = [];
		if (empty($data["billing_first_name"])) {
			$errors[] = "Billing First Name is a required field.";
		}

		if (empty($data["billing_last_name"])) {
			$errors[] = "Billing Last Name is a required field.";
		}

		if (empty($data["billing_address_1"]) || strlen($data["billing_address_1"]) < 6) {
			$errors[] = "Billing Street Address is a required field.";
		}


		if (empty($data["billing_state"])) {
			$errors[] = "Billing State/Country is a required field.";
		}



		if (!empty($data["billing_phone"]) && !preg_match("/^[0-9]{8,12}\z/", $data["billing_phone"])) {
			$errors[] = "Billing Phone Number is invalid.";
		} else
		if (empty($data["billing_phone"])) {
			$errors[] = "Billing Phone Number is a required field.";
		}


		if (!empty($data["billing_email"]) && !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $data["billing_email"])) {
			$errors[] = "Billing Email Address is invalid.";
		} else
		if (empty($data["billing_email"])) {
			$errors[] = "Billing Email Address is a required field.";
		}

		return $errors;
	}
}

new WC_Dibsy_Payments_Controller();
