<?php
namespace Opencart\Catalog\Model\Extension\Mobilpay\Payment;

/**
 * Class Mobilpay
 *
 * @package Opencart\Catalog\Model\Extension\Mobilpay\Payment
 */
class Mobilpay extends \Opencart\System\Engine\Model
{

    /**
     * @param array $address
     *
     * @return array
     */
    public function getMethods(array $address = []): array
    {
        $this->load->language('extension/mobilpay/payment/mobilpay');

		
		/**
		 * Check arguments
		 * Note : Currently we do not check the amount ZERO
		 */
        if ($this->cart->hasSubscription()) {
            $status = false;
        } elseif (!$this->config->get('config_checkout_payment_address')) {
            $status = true;
        } elseif (!$this->config->get('payment_mobilpay_geo_zone_id')) {
            $status = true;
        } else {
            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_mobilpay_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

            if ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        }

        $method_data = [];

        if ($status) {
            $option_data['mobilpay'] = [
            'code' => 'mobilpay.mobilpay',
            'name' => $this->language->get('text_option_card')
            ];

            $method_data = [
            'code'       => 'mobilpay',
            'name'       => $this->language->get('heading_title'),
            'option'     => $option_data,
            'sort_order' => $this->config->get('payment_mobilpay_sort_order')
            ];
        }

            return $method_data;
    }
}