<?php
class ControllerShippingDpdCarrier extends Controller {
	private $error = array();

	// Configuration screen
	public function index() {
		$this->language->load('shipping/dpd_carrier');

		$this->document->setTitle($this->language->get('DPD Carrier'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
		
			$active = 0;
			foreach ($this->request->post['dpd_carrier_service'] as $service) {
				if ( $service['status'] == 1 )
					$active = 1;
			}
			$this->request->post['dpd_carrier_status'] = $active;
			
			$this->model_setting_setting->editSetting('dpd_carrier', $this->request->post);		

			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['entry_status'] = $this->language->get('Status:');
		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');

		$this->data['delis_id'] = $this->language->get('DelisID:');
		$this->data['delis_password'] = $this->language->get('Password:');
		
		$this->data['delis_server'] = $this->language->get('Server:');
		$this->data['delis_server_live'] = $this->language->get('Live');
		$this->data['delis_server_stage'] = $this->language->get('Stage');
		
		$this->data['locator_location'] = $this->language->get('Locator:');
		$this->data['locator_location_before'] = $this->language->get('Before');
		$this->data['locator_location_after'] = $this->language->get('After');
		
		$this->data['time_logging'] = $this->language->get('Time Logging');
		$this->data['time_logging_on'] = $this->language->get('On');
		$this->data['time_logging_off'] = $this->language->get('Off');
		
		$this->data['service_title_service'] = $this->language->get('Service');
		$this->data['service_title_status'] = $this->language->get('Status');
		$this->data['service_title_zone'] = $this->language->get('Zone');
		$this->data['service_title_from'] = $this->language->get('From') . " (" . $this->getCurrencySymbol() . ")";
		$this->data['service_title_cost'] = $this->language->get('Cost') . " (" . $this->getCurrencySymbol() . ")";
		
		$this->data['service_home'] = $this->language->get('Home');
		$this->data['service_predict'] = $this->language->get('Home With Predict');
		$this->data['service_pickup'] = $this->language->get('Pickup');
		
		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		if (isset($this->error['delis_id'])) {
			$this->data['delis_id_error'] = $this->error['delis_id'];
		} else {
			$this->data['delis_id_error'] = '';
		}
		
		if (isset($this->error['delis_password'])) {
			$this->data['delis_password_error'] = $this->error['delis_password'];
		} else {
			$this->data['delis_password_error'] = '';
		}
		
		if (isset($this->error['delis_server'])) {
			$this->data['delis_server_error'] = $this->error['delis_server'];
		} else {
			$this->data['delis_server_error'] = '';
		}

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_shipping'),
			'href'      => $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('DPD Carrier'),
			'href'      => $this->url->link('shipping/dpd_carrier', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['action'] = $this->url->link('shipping/dpd_carrier', 'token=' . $this->session->data['token'], 'SSL');

		$this->data['cancel'] = $this->url->link('extension/shipping', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['dpd_carrier_delis_id'])) {
			$this->data['dpd_carrier_delis_id'] = $this->request->post['dpd_carrier_delis_id'];
		} else {
			$this->data['dpd_carrier_delis_id'] = $this->config->get('dpd_carrier_delis_id');
		}	
		
		if (isset($this->request->post['dpd_carrier_delis_password'])) {
			$this->data['dpd_carrier_delis_password'] = $this->request->post['dpd_carrier_delis_password'];
		} else {
			$this->data['dpd_carrier_delis_password'] = $this->config->get('dpd_carrier_delis_password');
		}	
		
		if (isset($this->request->post['dpd_carrier_delis_server'])) {
			$this->data['dpd_carrier_delis_server'] = $this->request->post['dpd_carrier_delis_server'];
		} else {
			$this->data['dpd_carrier_delis_server'] = $this->config->get('dpd_carrier_delis_server');
		}
		
		if (isset($this->request->post['dpd_carrier_locator_location'])) {
			$this->data['dpd_carrier_locator_location'] = $this->request->post['dpd_carrier_locator_location'];
		} else {
			$this->data['dpd_carrier_locator_location'] = $this->config->get('dpd_carrier_locator_location');
		}
		
		if (isset($this->request->post['dpd_carrier_time_logging'])) {
			$this->data['dpd_carrier_time_logging'] = $this->request->post['dpd_carrier_time_logging'];
		} else {
			$this->data['dpd_carrier_time_logging'] = $this->config->get('dpd_carrier_time_logging');
		}
		
		$this->data['services'] = array();

		if (isset($this->request->post['dpd_carrier_service'])) {
			$this->data['services'] = $this->request->post['dpd_carrier_service'];
		} elseif ($this->config->get('dpd_carrier_service')) { 
			$this->data['services'] = $this->config->get('dpd_carrier_service');
		} else {
			$this->load->library('DPD/dpdshippingmethods');
			$dpdshippingmethods = new DpdShippingMethods();

			foreach($dpdshippingmethods->methods as $key => $method) {
				$this->data['services'][$key]['name']  = $method->name;
				$this->data['services'][$key]['status']  = 0;
				$this->data['services'][$key]['rows'][0]['geo_zone_id']  = 0;
				$this->data['services'][$key]['rows'][0]['from']  = 0;
				$this->data['services'][$key]['rows'][0]['cost']  = 0;
				$this->data['services'][$key]['id']  = $method->shorthand;
			}
		}
		
		$this->load->model('localisation/geo_zone');
		
		$this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		$this->load->model('design/layout');

		$this->data['layouts'] = $this->model_design_layout->getLayouts();

		$this->template = 'shipping/dpd_carrier.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	// Configuration validation
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'shipping/dpd_carrier')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['dpd_carrier_delis_id']) {
			$this->error['delis_id'] = $this->language->get('Please enter your Delis ID (provide by DPD)');
		}
		
		if (!$this->request->post['dpd_carrier_delis_password']) {
			$this->error['delis_password'] = $this->language->get('Please enter your Delis Password (provide by DPD)');
		}
		
		if (!($this->request->post['dpd_carrier_delis_server'] == 0 
			|| $this->request->post['dpd_carrier_delis_server'] == 1)) 
		{
			$this->error['delis_server'] = $this->language->get('Please select either live or stage server');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}

	private function getCurrencySymbol(){
		$left = $this->currency->getSymbolLeft($this->session->data['currency']);
		$right = $this->currency->getSymbolRight($this->session->data['currency']);
		
		if($left != '')
			return $left;
		else
			return $right;
	}
}
?>
