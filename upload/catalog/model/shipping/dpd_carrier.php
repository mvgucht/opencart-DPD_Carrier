<?php
class ModelShippingDpdCarrier extends Model {
	function getQuote($address) {
		$this->language->load('shipping/dpd_carrier');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
		
		if (!$this->config->get('flat_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$cost = 0;
			$weight = $this->cart->getWeight();
			$sub_total = $this->cart->getSubTotal();
			
			$geo_zone_ids = array(0);
			foreach($query->rows as $zone_to_geo_zone) {
				$geo_zone_ids[] = $zone_to_geo_zone['geo_zone_id'];
			}

			$quote_data = array();
			
			foreach($this->config->get('dpd_carrier_service') as $service) {
				if($service['status'] == '1') {
					$cost = false;
					foreach($service['rows'] as $quote) {
						if( in_array( $quote['geo_zone_id'], $geo_zone_ids)
							&& $quote['from'] < $sub_total
							&& ( !$cost
								|| $quote['cost'] < $cost)){
							$cost = $quote['cost'];
						}
					}
					
					/* Not needed, quote does it itself.
					if ( $this->currency->getCode() != $this->config->get('config_currency')
						&& $cost != 0) {
						var_dump('calculate!');
						$cost = $this->currency->convert($cost, $this->config->get('config_currency'), $this->currency->getCode());
					}
					*/
					
					$code = strtolower(str_replace(' ', '_', $service['name']));
					$quote_data[$code] = array(
						'code'         => 'dpd_carrier.' . $code,
						'title'        => '<img onload="dpdCarrierLoad(event);" src="catalog/view/theme/default/image/DPD/' . $code . '.jpg" />',
						'cost'         => $cost,
						'tax_class_id' => $this->config->get('dpd_carrier_tax_class_id'),
						'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('dpd_carrier_tax_class_id'), $this->config->get('config_tax'))),
					);
				}
			}

			$method_data = array(
				'code'       => 'dpd_carrier',
				'title'      => 'DPD', //$this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('dpd_carrier_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
	}
}
?>