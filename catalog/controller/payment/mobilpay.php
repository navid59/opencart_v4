<?php
namespace Opencart\Catalog\Controller\Extension\Mobilpay\Payment;

/**
 * Class Mobilpay
 *
 * @package Opencart\Catalog\Controller\Extension\Mobilpay\Payment
 */
class Mobilpay extends \Opencart\System\Engine\Controller {
/**
	 * @return string
	 */
	public function index(): string {
        $this->load->language('extension/mobilpay/payment/mobilpay');

        $data = [];
        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['ntp_signature'] = $this->config->get('payment_mobilpay_signature');
		$data['ntp_live_apikey'] = nl2br($this->config->get('payment_mobilpay_live_apikey'));
		$data['ntp_sandbox_apikey'] = nl2br($this->config->get('payment_mobilpay_sandbox_apikey'));
		$data['language'] = $this->config->get('config_language');
        

		return $this->load->view('extension/mobilpay/payment/mobilpay', $data);

    }


    /**
     * Confirm Order & reqister Order
     * Prepare the data for pay
     * Redirect to NETOPIA Payments page
	 * @return void
	 */
	public function confirm(): void {
		$this->load->language('extension/mobilpay/payment/mobilpay');

		$json = [];

		if (!isset($this->session->data['order_id'])) {
			$json['error'] = $this->language->get('error_order');
		}

		if (!isset($this->session->data['payment_method']) || $this->session->data['payment_method']['code'] != 'mobilpay.mobilpay') {
			$json['error'] = $this->language->get('error_payment_method');
		}

		if (!$json) {
			$comment = $this->language->get('text_payment') . "\n";

			$this->load->model('checkout/order');

			$this->model_checkout_order->addHistory($this->session->data['order_id'], $this->config->get('payment_mobilpay_order_status_id'), $comment, false);

			// $json['redirect'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);
            /**
             * Make request to pay and return payment URL
             */

			// Create an instance of the new controller
			$myController = new \Opencart\Catalog\Controller\Extension\Mobilpay\Payment\Pay($this->registry);

			// Call the index method of the new controller
			$paymentURL = $myController->index();

			$json['redirect'] = $paymentURL; //'https://www.example.com/success-page';
            $json['redirect_external'] = true;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}