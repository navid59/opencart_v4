<?php
namespace Opencart\Admin\Controller\Extension\Mobilpay\Payment;
/**
 * Class Mobilpay
 *
 * @package Opencart\Admin\Controller\Extension\Mobilpay\Payment
 */
class Mobilpay extends \Opencart\System\Engine\Controller {
	private $error = [];

    /**
     * @return void
     */
    public function index(): void {
        $this->load->language('extension/mobilpay/payment/mobilpay');
		
		// Assign Custom js
		$this->document->addScript('../extension/mobilpay/admin/view/js/payment/custom_admin_script.js');

		//Build Custome Admin Header
        $this->document->setTitle($this->language->get('heading_title'));
		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment')
		];

   		$data['breadcrumbs'][] = [
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/payment/mobilpay', 'user_token=' . $this->session->data['user_token'], true),
		];

		$data['save'] = $this->url->link('extension/mobilpay/payment/mobilpay.save', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');

		$data['payment_mobilpay_signature'] = $this->config->get('payment_mobilpay_signature');
		$data['payment_mobilpay_test'] = $this->config->get('payment_mobilpay_test');
		$data['payment_mobilpay_live_apikey'] = $this->config->get('payment_mobilpay_live_apikey');
		$data['payment_mobilpay_sandbox_apikey'] = $this->config->get('payment_mobilpay_sandbox_apikey');

		$data['payment_mobilpay_order_status_id'] = $this->config->get('payment_mobilpay_order_status_id');
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();


		$data['payment_mobilpay_geo_zone_id'] = $this->config->get('payment_mobilpay_geo_zone_id');
		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['payment_mobilpay_status'] = $this->config->get('payment_mobilpay_status');
		$data['payment_mobilpay_sort_order'] = $this->config->get('payment_mobilpay_sort_order');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/mobilpay/payment/mobilpay', $data));
    }


	/**
	 * @return void
	 */
	public function save(): void {

		$this->load->language('extension/mobilpay/payment/mobilpay');

		$json = [];

		/** 
		 * Navid Note : No Permition !!!!!!!!!!!!!!
		 */
		// if (!$this->user->hasPermission('modify', 'extension/payment/mobilpay')) {
		// 	$json['error']['warning'] = $this->language->get('error_permission');
		// }
		
		if (!$this->request->post['payment_mobilpay_signature']) {
			$json['error']['signature'] = $this->language->get('error_signature');
		}

		if (!$this->request->post['payment_mobilpay_live_apikey']) {
			$json['error']['live_apikey'] = $this->language->get('error_live_apikey');
		}

		if (!$this->request->post['payment_mobilpay_sandbox_apikey']) {
			$json['error']['sandbox_apikey'] = $this->language->get('error_sandbox_apikey');
		}

		if (!$json) {
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('payment_mobilpay', $this->request->post);
			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
     * Add ntpID field to Order table
     */
    public function install() {
        $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD COLUMN ntpID VARCHAR(50) DEFAULT NULL COMMENT 'NETOPIA Payments ID';");
	}
}