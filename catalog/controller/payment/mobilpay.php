<?php
namespace Opencart\Catalog\Controller\Extension\Mobilpay\Payment;

/**
 * Class Mobilpay
 *
 * @package Opencart\Catalog\Controller\Extension\Mobilpay\Payment
 */
class Mobilpay extends \Opencart\System\Engine\Controller {
    /**
     * Order Status Constants in OC
     */
    const OC_ORDER_STATUS_PENDING = 1;
    const OC_ORDER_STATUS_PROCESSING = 2;
    const OC_ORDER_STATUS_COMPLETE = 5;
    const OC_ORDER_STATUS_CANCELLED = 7;
    const OC_ORDER_STATUS_DENIED = 8;
    const OC_ORDER_STATUS_FAILED = 10;
    const OC_ORDER_STATUS_REFUNDED = 11;
    const OC_ORDER_STATUS_PROCESSED = 15;


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

			$this->model_checkout_order->addHistory($this->session->data['order_id'], self::OC_ORDER_STATUS_PENDING , $comment, false);

			// $json['redirect'] = $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true);
            /**
             * Make request to pay and return payment URL
             */

			// Create an instance of the PAY controller
			$payController = new \Opencart\Catalog\Controller\Extension\Mobilpay\Payment\Pay($this->registry);

			// Call the index method of the Pay controller
			$paymentResult = $payController->index();


			$json['paymentResult'] = $paymentResult;
            $json['redirect_external'] = true;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
     * Call Back is the IPN
	 * http://localhost/open_v4.0.2/index.php?route=extension/mobilpay/payment/mobilpay.callback&language=en-gb
     */
    public function callback() {
       /**
         * get defined keys
         */
        
        $ntpIpn = new \Opencart\Catalog\Controller\Extension\Mobilpay\Payment\Lib\IPN($this->registry);

        $ntpIpn->activeKey         = $this->config->get('payment_mobilpay_signature'); // activeKey or posSignature
        $ntpIpn->posSignatureSet[] = $this->config->get('payment_mobilpay_signature'); // The active key should be in posSignatureSet as well
        $ntpIpn->posSignatureSet[] = 'AAAA-BBBB-CCCC-DDDD-EEEE'; 
        $ntpIpn->posSignatureSet[] = 'DDDD-AAAA-BBBB-CCCC-EEEE'; 
        $ntpIpn->posSignatureSet[] = 'EEEE-DDDD-AAAA-BBBB-CCCC';
        $ntpIpn->hashMethod        = 'SHA512';
        $ntpIpn->alg               = 'RS512';
        
        $ntpIpn->publicKeyStr = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAy6pUDAFLVul4y499gz1P\ngGSvTSc82U3/ih3e5FDUs/F0Jvfzc4cew8TrBDrw7Y+AYZS37D2i+Xi5nYpzQpu7\nryS4W+qvgAA1SEjiU1Sk2a4+A1HeH+vfZo0gDrIYTh2NSAQnDSDxk5T475ukSSwX\nL9tYwO6CpdAv3BtpMT5YhyS3ipgPEnGIQKXjh8GMgLSmRFbgoCTRWlCvu7XOg94N\nfS8l4it2qrEldU8VEdfPDfFLlxl3lUoLEmCncCjmF1wRVtk4cNu+WtWQ4mBgxpt0\ntX2aJkqp4PV3o5kI4bqHq/MS7HVJ7yxtj/p8kawlVYipGsQj3ypgltQ3bnYV/LRq\n8QIDAQAB\n-----END PUBLIC KEY-----\n";
        $ipnResponse = $ntpIpn->verifyIPN();
        
        /**
         * Add Order History & change status
         */
        $ocOrderID = $this->getRealOrderID($ipnResponse['rawData']['orderID']); 
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($ocOrderID);
        
        // Add payment result to history
        $this->model_checkout_order->addHistory($ocOrderID, $order_info['order_status_id'], $ipnResponse['errorMessage']);     
        
        // Get Payment Status
        $this->statusPayment($ipnResponse['rawData']['orderID'], $ipnResponse['rawData']['ntpID']);

        /**
         * IPN Output
         */
        // echo json_encode($ipnResponse);
        echo json_encode([
            "errorType" => $ipnResponse['errorType'],
            "errorCode" => $ipnResponse['errorCode'],
            "errorMessage" => $ipnResponse['errorMessage']
        ]);
        die();
    }


    /**
     * statusPayment is to get current status of payment 
	 * http://localhost/open_v4.0.2/index.php?route=extension/mobilpay/payment/mobilpay.statusPayment
     */
    public function statusPayment($orderID, $ntpID) {
        /**
         * Load necessary OC lib
         */  
        $this->load->language('extension/mobilpay/payment/mobilpay');
        $this->load->model('checkout/order');

        /**
         * Defined Status payment
         */        
        $ntpStatus = new \Opencart\Catalog\Controller\Extension\Mobilpay\Payment\Lib\Status($this->registry);
        $ntpStatus->posSignature = $this->config->get('payment_mobilpay_signature');
                
        $isTestMod = $this->config->get('payment_mobilpay_test'); 
        if($isTestMod) {
            $ntpStatus->isLive = false;
            $ntpStatus->apiKey = $this->config->get('payment_mobilpay_sandbox_apikey');
        } else {
            $ntpStatus->isLive = true;
            $ntpStatus->apiKey = $this->config->get('payment_mobilpay_live_apikey');
        }
        
        $ntpStatus->ntpID = $ntpID;
        $ntpStatus->orderID = $orderID;

        /**
         * Set payment status parameteres
         */
        $orderStatusJson = $ntpStatus->setStatus();

		/** Get Order Status */
		$statusRespunse = $ntpStatus->getStatus($orderStatusJson);

        $statusRespunseObj = json_decode($statusRespunse);
         
        switch ($statusRespunseObj->data->payment->status) {
            case 3:
            case 5:
                // change order status and add history
                $comment = $this->language->get('text_payment_paid') . "\n";
                
                $this->model_checkout_order->addHistory($this->getRealOrderID($orderID), $this->config->get('payment_mobilpay_order_status_id'), $comment);  
                break;
        } 
    }

    public function getRealOrderID($ntpOrderID) {
        $expStr = explode("_", $ntpOrderID);
        $ocOrderID = $expStr[0]; 
        return $ocOrderID;
    }

}