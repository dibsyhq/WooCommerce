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
	public function create_payment(WC_Dibsy_Payment $payment_dto)
	{
		
		if (
			empty($payment_dto->amount->value)
			|| empty($payment_dto->redirectUrl)
		) {
			die;
		}


		try {
			$init_payment = WC_Dibsy_API::request($payment_dto);
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





}

new WC_Dibsy_Payments_Controller();
