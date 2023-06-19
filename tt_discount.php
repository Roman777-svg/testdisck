<?php
class ModelExtensionTotalTtDiscount extends Model {
	public function getTotal($total) {
		$this->load->language('extension/total/tt_discount');
		
		if($this->customer->getTelephone()) {
			$telephone = $this->prepareTelephone($this->customer->getTelephone());
		} elseif(isset($this->session->data['guest']['telephone']) && !empty($this->session->data['guest']['telephone'])) {
			$telephone = $this->prepareTelephone($this->session->data['guest']['telephone']);
		} else {
			$telephone = '';
		}
		
		$status = true;
		
		$discount = 0;
		$percent = 0;
	
		if($telephone) {
			$status_confirm = implode(',', $this->config->get('config_complete_status'));
			
			$query = $this->db->query("SELECT SUM(total) AS total FROM `" . DB_PREFIX . "order` WHERE `telephone` LIKE '%" . $this->db->escape(trim($telephone)) . "%' AND order_status_id IN(" . $this->db->escape($status_confirm) . ")");
		
			$total_summ = $query->row['total'];
			
			$discounts_val = $this->config->get('total_tt_discount_discounts');
			
			$total_for_discount = 0;
			
			$this->load->model('catalog/product');
			
			foreach($this->cart->getProducts() as $product) {
				$product_info = $this->model_catalog_product->getProduct($product['product_id']);
				
				if(!$product_info['special']) {
					$total_for_discount += $product_info['price'] * $product['quantity'];
				}
			}
			
			if(!$this->config->get('total_tt_discount_special')) {
				$total_for_discount = $total['total'];
			}
	
			foreach($discounts_val as $value) {
				if($total_summ > $value['min'] && $total_summ < $value['max']) {
					$discount = $this->prepareDiscount($value['type'], $value['discount'], $total_for_discount);

					if($value['type'] == 'percent') {
						$title_value = sprintf($this->language->get('text_tt_discount_total'), $value['discount']);
					} else {
						$title_value = sprintf($this->language->get('text_tt_discount_total_fix'), $this->currency->format($value['discount'], $this->session->data['currency']));
					}
					
					break;
				}
			}
			
			if(!$discount) {
				$status = false;
			}
		} else {
			$status = false;
		}
		
		if($status) {
			$total['totals'][] = array(
				'code'       => 'tt_discount',
				'title'      => $title_value,
				'value'      => -$discount, 
				'sort_order' => $this->config->get('total_tt_discount_sort_order')
			);

			$total['total'] -= $discount;
		}
	}
	
	private function prepareTelephone($telephone) {
		$parts = explode(',', $this->config->get('total_tt_discount_replace'));
		
		foreach($parts as $part) {
			$telephone = str_replace($part, '', $telephone);
		}
		
		return $telephone;
	}
	
	private function prepareDiscount($type, $value, $total) {
		if($type == 'percent') {
			$value = $total * ($value / 100);
		}
		
		return $value;
	}
}
