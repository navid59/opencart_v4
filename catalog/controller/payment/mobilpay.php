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

        

		// $data['payable'] = $this->config->get('payment_cheque_payable');
		// $data['address'] = nl2br($this->config->get('config_address'));

		// $data['language'] = $this->config->get('config_language');

		return $this->load->view('extension/mobilpay/payment/mobilpay', $data);

    }

    /**
	 * @return void
	 */
	public function confirm(): void {

        // $this->load->language('extension/mobilpay/payment/mobilpay');

		$json = [];

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
    }
}