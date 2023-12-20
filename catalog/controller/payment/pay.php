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
        // print_r($payRequest);
        // echo "<hr>";
        $payRequest->posSignature = $this->config->get('payment_mobilpay_signature');

        $isTestMod = $this->config->get('payment_mobilpay_test'); 
        if($isTestMod) {
            $payRequest->apiKey = nl2br($this->config->get('payment_mobilpay_sandbox_apikey'));
        } else {
            $payRequest->apiKey = nl2br($this->config->get('payment_mobilpay_live_apikey'));
        }
        
        /**
         * Prepare json for start action
         */

        /** - Config section  */
        $configData = [
            'emailTemplate' => "",
            'notifyUrl'     => $this->url->link('extension/mobilpay/payment/mobilpay.callback'),
            'redirectUrl'   => $this->url->link('extension/mobilpay/payment/pay.redirect'),
            'cancelUrl'   => $this->url->link('extension/mobilpay/payment/pay.cancel'),
            'language'      => "RO"
            ];

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
        ////////////////

       
        
        //$json = $responseArr;
        //return $json;

        return $responseArr;

        // echo "<hr>";
        // print_r($startResult);
        // echo "<hr>";
        // // print_r($orderData);

        // echo 
       
        // die();

        // return "http://mobilpay.ro";
    }

   

    /**
     * Cancel Payment
     * Must redirect to the Cart page
     */
    public function cancel() {
		echo "This is cancel Payment";
	}

    /**
     * Redirect
     * Must redirect to success page / Error Page
     */
    public function redirect() {
		echo "This is Success / Error";
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