<?php
namespace Opencart\Catalog\Controller\Extension\Mobilpay\Payment;

class Pay extends \Opencart\System\Engine\Controller {
    public function index() {
        /**
         * Steps 1
         * Create an instance of the PAY controller
         * - Make Request
         * - Send Request 
         * - return Payment URL
         */
        
        
        // Load Language
        $this->load->language('extension/mobilpay/payment/mobilpay');

        // Load the Cart model
        $this->load->model('checkout/cart');
        
        $payRequest = new \Opencart\Catalog\Controller\Extension\Mobilpay\Payment\Lib\Request($this->registry);
        $payRequest->posSignature = $this->config->get('payment_mobilpay_signature');

        $isTestMod = $this->config->get('payment_mobilpay_test'); 
        if($isTestMod) {
            $payRequest->apiKey = $this->config->get('payment_mobilpay_sandbox_apikey');
        } else {
            $payRequest->apiKey = $this->config->get('payment_mobilpay_live_apikey');
        }
        
        /**
         * Prepare json for start action
         */

        // /** - 3DS section  */
        // $threeDSecusreData =  array(); 

        /** - Order section  */
        /**
         * Order Full Information
         */
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $orderData = new \StdClass();

        /**
         * Set a custom Order description
         */
        $customPaymentDescription = 'Plata pentru comanda cu ID: '.$order_info['order_id'].' | '.$order_info['invoice_prefix'].' | '.$order_info['payment_method']['code'].' | '.$order_info['shipping_firstname'] .' '.$order_info['shipping_lastname'];

        $orderData->description             = $customPaymentDescription;
        $orderData->orderID                 = $order_info['order_id'].'_'.$this->randomUniqueIdentifier();
        $orderData->amount                  = $order_info['total'];
        $orderData->currency                = $order_info['currency_code'];
        

        $orderData->billing                 = new \StdClass();
        $orderData->billing->email          = $order_info['email'];
        $orderData->billing->phone          = $order_info['telephone'];
        $orderData->billing->firstName      = $order_info['payment_firstname'] ?? $order_info['firstname'];
        $orderData->billing->lastName       = $order_info['payment_lastname'] ?? $order_info['lastname'];
        $orderData->billing->city           = $order_info['payment_city'] ?? $order_info['shipping_city'];
        $orderData->billing->country        = 642;
        $orderData->billing->state          = $order_info['payment_zone'] ?? $order_info['shipping_zone'];
        $orderData->billing->postalCode     = $order_info['payment_postcode'] ?? $order_info['shipping_postcode'];

        $paymentCountryName  = $order_info['payment_country'] ?? $order_info['shipping_country'];
        $paymentFullAddress  = $order_info['payment_address_1'] ?? $order_info['shipping_address_1'];
        $paymentFullAddress .= $order_info['payment_address_2'] ?? $order_info['shipping_address_2'];
        $billingFullStr = $paymentCountryName 
         .' , '.$orderData->billing->city
         .' , '.$orderData->billing->state
         .' , '.$paymentFullAddress
         .' , '.$orderData->billing->postalCode;
        $orderData->billing->details        = !empty($order_info['comment']) ?  $order_info['comment'] . " | ". $billingFullStr : $billingFullStr;

        
        $orderData->shipping                = new \StdClass();
        $orderData->shipping->email         = $order_info['email'];
        $orderData->shipping->phone         = $order_info['telephone'];
        $orderData->shipping->firstName     = $order_info['shipping_firstname'] ?? $order_info['firstname'];
        $orderData->shipping->lastName      = $order_info['shipping_lastname'] ?? $order_info['lastname'];
        $orderData->shipping->city          = $order_info['shipping_city'] ?? $order_info['payment_city'];
        $orderData->shipping->country       = 642 ;
        $orderData->shipping->state         = $order_info['shipping_zone'] ?? $order_info['payment_zone'];
        $orderData->shipping->postalCode    = $order_info['shipping_postcode'] ?? $order_info['payment_postcode'];

        $shippingCountryName  = $order_info['shipping_country'] ?? $order_info['payment_country'];
        $shippingFullAddress  = $order_info['shipping_address_1'] ?? $order_info['payment_address_1'];
        $shippingFullAddress .= $order_info['shipping_address_2'] ?? $order_info['payment_address_2'];
        $shippingFullStr = $shippingCountryName 
         .' , '.$orderData->shipping->city
         .' , '.$orderData->shipping->state
         .' , '.$shippingFullAddress
         .' , '.$orderData->shipping->postalCode;
        $orderData->shipping->details        = !empty($order_info['comment']) ?  $order_info['comment'] . " | ". $shippingFullStr : $shippingFullStr;


         // Get all products in the cart
         $products = $this->model_checkout_cart->getProducts();

         $orderData->products                = $this->getCartSummary($products); // It's JSON

         /**	Add Api & CRM version to request*/
        $orderData->data				 	= new \StdClass();
        $orderData->data->plugin_version 	= "1.0.0";
        $orderData->data->api 		        = "2.0";
        $orderData->data->platform 		    = "Opencart";
        $orderData->data->platform_version 	= $this->getOpenCartVersion();


        /** - Config section  */
        $configData = [
            'emailTemplate' => "",
            'notifyUrl'     => $this->url->link('extension/mobilpay/payment/mobilpay.callback'),
            'redirectUrl'   => $this->url->link('extension/mobilpay/payment/pay.redirect'),
            'cancelUrl'   => $this->url->link('extension/mobilpay/payment/pay.cancel', 'id='.$orderData->orderID, true),
            'language'      => "RO"
            ];

        /**
         * Assign values and generate Json
         */
        $payRequest->jsonRequest = $payRequest->setRequest($configData, $orderData);

        /**
         * Send Json to Start action 
         */
        $startResult = $payRequest->startPayment();

        ////////////////
        // /**
        //  * Result of start action is in jason format
        //  * get PaymentURL & do redirect
        //  */
        $responseArr = [];
        $resultObj = json_decode($startResult);
        
        switch($resultObj->status) {
            case 0:
                if(($resultObj->code == 401) && ($resultObj->data->code == 401)) {
                    $errorMsg = $this->language->get('error_redirect_code_401');
                } elseif (($resultObj->code == 400) && ($resultObj->data->code == 99)) {
                    $errorMsg = $this->language->get('error_redirect_code_99');
                }
                $errorMsg  .= $this->language->get('error_redirect');

                $responseArr['status'] = $resultObj->status; 
                $responseArr['code'] = $resultObj->data->code; 
                $responseArr['msg'] = $errorMsg;
                $responseArr['url'] = '';
            break;
            case 1:
            if ($resultObj->code == 200 &&  !is_null($resultObj->data->payment->paymentURL)) {
                $errorMsg  = $this->language->get('message_redirect');
                $responseArr['status'] = 1; 
                $responseArr['code'] = $resultObj->data->error->code; 
                $responseArr['msg'] = $errorMsg;
                $responseArr['url'] = $resultObj->data->payment->paymentURL;                
            } else {
                $responseArr['status'] = 0; 
                $responseArr['code'] = ''; 
                $responseArr['msg'] = $resultObj->message;
                $responseArr['url'] = '';
            }
            break;
            default:
            $errorMsg  = $this->language->get('error_redirect');
            $errorMsg  .= $this->language->get('error_redirect_problem_unknown');

            $responseArr['status'] = 0; 
            $responseArr['code'] = ''; 
            $responseArr['msg'] = $errorMsg;
            $responseArr['url'] = '';
            break;
        }
        
        return $responseArr;
    }

   

    /**
     * Cancel Payment
     * Must redirect to the Cart page
     */
    public function cancel() {
        
        if (!empty($_GET)) {
            if (isset($_GET['id'])) {
                // Get order info
                $ntpExtention = new \Opencart\Catalog\Controller\Extension\Mobilpay\Payment\Mobilpay($this->registry);
                $ocOrderID = $ntpExtention->getRealOrderID($_GET['id']); 
                echo "<pre>";
                // var_dump($this->registry);
                var_dump( $ocOrderID);
                echo "</pre>";
            } else {
                echo "Order ID is missing.";
            }
        } else {
            echo "GET request is empty.";
        }

        
		// $this->failur();
		// $this->success();
        
	}

    /**
     * Failur Page
     */
    public function failur() {
        // Load Language
        $this->load->language('extension/mobilpay/payment/mobilpay');

		$this->document->setTitle($this->language->get('ntp_failure_heading_title'));
        $data['ntp_return_heading_title'] = $this->language->get('ntp_failure_heading_title');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('ntp_failure_text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('ntp_failure_text_basket'),
			'href' => $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('ntp_failure_text_checkout'),
			'href' => $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('ntp_failure_text_failure'),
			'href' => $this->url->link('checkout/failure', 'language=' . $this->config->get('config_language'))
		];

		$data['text_message'] = sprintf($this->language->get('ntp_failure_text_message'), $this->url->link('information/contact', 'language=' . $this->config->get('config_language')));

		$data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('extension/mobilpay/payment/returnPage', $data));
    }

    /**
     * Success Page
     */
    public function success() {
        // Load Language
        $this->load->language('extension/mobilpay/payment/mobilpay');

		if (isset($this->session->data['order_id'])) {
			$this->cart->clear();

			unset($this->session->data['order_id']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['comment']);
			unset($this->session->data['agree']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
		}

		$this->document->setTitle($this->language->get('ntp_success_heading_title'));

        $data['ntp_return_heading_title'] = $this->language->get('ntp_success_heading_title');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('ntp_success_text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('ntp_success_text_basket'),
			'href' => $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('ntp_success_text_checkout'),
			'href' => $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('ntp_success_text_success'),
			'href' => $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'))
		];

		if ($this->customer->isLogged()) {
			$data['text_message'] = sprintf($this->language->get('ntp_success_text_customer'), $this->url->link('account/account', 'language=' . $this->config->get('config_language') .  '&customer_token=' . $this->session->data['customer_token']), $this->url->link('account/order', 'language=' . $this->config->get('config_language') .  '&customer_token=' . $this->session->data['customer_token']), $this->url->link('account/download', 'language=' . $this->config->get('config_language') .  '&customer_token=' . $this->session->data['customer_token']), $this->url->link('information/contact', 'language=' . $this->config->get('config_language')));
		} else {
			$data['text_message'] = sprintf($this->language->get('ntp_success_text_guest'), $this->url->link('information/contact', 'language=' . $this->config->get('config_language')));
		}

		$data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/mobilpay/payment/returnPage', $data));
        
	}

    /**
     * Test
     * Will be delete
     */
    public function test() {
        $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD COLUMN ntpID VARCHAR(50) DEFAULT NULL COMMENT 'NETOPIA Payments ID';");
	}

    /**
     * Generate Random unique number
     */
    public function randomUniqueIdentifier() {
        $microtime = microtime();
        list($usec, $sec) = explode(" ", $microtime);
        $seed = (int)($sec * 1000000 + $usec);
        srand($seed);
        $randomUniqueIdentifier = md5(uniqid(rand(), true));
        return $randomUniqueIdentifier;
    }

    /**
     * 
     */
    public function getCartSummary($products) {
        $cartArr = $products;
        $i = 0;	
        $cartSummary = array();	
        foreach ($cartArr as $key => $value ) {
            $cartSummary[$i]['name']                 =  $value['name'].' '. $value['model'];
            $cartSummary[$i]['code']                 =  $value['product_id'];
            $cartSummary[$i]['price']                =  floatval($value['price']);
            $cartSummary[$i]['quantity']             =  $value['quantity'];
            $cartSummary[$i]['short_description']    =  $value['image'] ??  'no descriptio, no image';
            $i++;
           }
        return $cartSummary;
    }


    /**
     * Look's Like geting Version is not implimented in Opencart 
     */
    public function getOpenCartVersion() {
        return "OpenCart version not found! - Static text";
    }


}