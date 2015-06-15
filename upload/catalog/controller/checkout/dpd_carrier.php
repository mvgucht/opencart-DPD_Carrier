<?php 
class ControllerCheckoutDpdCarrier extends Controller { 
	public function index() {
		if(!(isset($_SERVER['HTTP_REFERER'])
			&& parse_url($_SERVER['HTTP_REFERER'])['host'] == parse_url($this->config->get('config_url'))['host']))
		{
			$this->redirect($this->url->link('common/home'));
			die;
		}
		
		$this->log = new Log('dpd.log');
		
		if(isset($_POST['confirmShop']))
			$this->confirmParcelShop();

		if(isset($_POST['long']) && isset($_POST['lat']))
			$this->returnParcelShops();
		
		$this->redirect($this->url->link('common/home'));
		die;
	}
	
	private function confirmParcelShop() {
		$this->load->model('account/address');
		
		$shops = unserialize($this->session->data['shops']);
		$id_parcelshop = $_POST['confirmShop'];
		
		$delivery_address = $this->getDeliveryAddress();		

		if (isset($shops[$id_parcelshop]))
		{
			$parcelshop = $shops[$id_parcelshop];

			if($delivery_address['iso_code_2'] == $parcelshop->isoAlpha2)
			{
				$this->session->data['guest']['shipping'] = array(
					'firstname'      => 'Pickup by DPD'
					,'lastname'      => $delivery_address['firstname'] . ' ' . $delivery_address['lastname']
					,'company'       => $parcelshop->company
					,'tax_id'        => isset($delivery_address['tax_id']) ? $delivery_address['tax_id'] : '' // TODO: see if there is a store tax id.
					,'address_1'     => $parcelshop->street . ' ' . $parcelshop->houseNo
					,'address_2'     => $parcelshop->parcelShopId
					,'postcode'      => $parcelshop->zipCode
					,'city'          => $parcelshop->city
					,'zone_id'       => $delivery_address['zone_id']
					,'zone'          => $delivery_address['zone']
					,'zone_code'     => $delivery_address['zone_code']
					,'country_id'    => $delivery_address['country_id']
					,'country'       => $delivery_address['country']
					,'iso_code_2'    => $delivery_address['iso_code_2']
					,'iso_code_3'    => $delivery_address['iso_code_3']
					,'address_format'	=> $delivery_address['address_format']
				);
				
				$return = array();
				$return['result'] = '<p>' . 'You have chosen' . ': <strong>' . $parcelshop->company . '</strong>';
				$return['result'] .= '<br>' . 'Located at' . ': ' . $parcelshop->street . ' ' . $parcelshop->houseNo . ', ' . $parcelshop->zipCode . ' ' . $parcelshop->city . '</p>';
				$return['result'] .= '<a href="#" onclick="javascript:dpdLocator.showLocator();return false;">' . 'Click here to alter your choice' .'</a>';
			
			} else {
				$this->log->write('Customer, ' . $this->customer->getFirstName() . ' ' . $this->customer->getLastName() . ' (' . $this->customer->getId() . '), tried to hack the country restrictions for Pickup Delivery.');
				$return = array(
					'hasErrors' => true
					,'errors' => 'Somehow the shop is not in the same country as your delivery address. As this is not allowed this warning has been logged. Please select a shop in the same country as your pre selected delivery address.'
				);
			}
		} else {
			$this->log->write('Customer, ' . $this->customer->getFirstName() . ' ' . $this->customer->getLastName() . ' (' . $this->customer->getId() . '), tried to hack  the ParcelShop locator by sending an unknown shop id (' . $id_parcelshop . ')');
			$return = array(
				'hasErrors' => true
				,'errors' => 'Somehow the shop that you selected was not in the list of proposed shops. As this is not allowed this warning has been logged. Please select a shop from the initial suggestions.'
			);
		}
		
		die(json_encode($return));
	}
		
	private function returnParcelShops() {
		$this->load->library('DPD/dpdlogin');
		$this->load->library('DPD/dpdparcelshopfinder');
		
		$this->delisID = $this->config->get('dpd_carrier_delis_id');
		$this->delisPw = $this->config->get('dpd_carrier_delis_password');
		
		$this->url = $this->config->get('dpd_carrier_delis_server') == 1 ? 'https://public-ws.dpd.com/services/' : 'https://public-ws-stage.dpd.com/services/';
		
		$this->timeLogging = $this->config->get('dpd_carrier_time_logging') == 1;
		
		$login;
		if(!($login = unserialize($this->cache->get('dpd_carrier_login')))
			|| !($login->url == $this->url)
			|| !($login->delisId == $this->delisId))
		{
			try
			{
				$login = new DpdLogin($this->delisID, $this->delisPw, $this->url, $this->timeLogging);
			}
			catch (Exception $e)
			{
				$this->log->write('Something went wrong logging in to the DPD Web Services (' . $e->getMessage() . ')');
				die;
			}
		}
		
		$long = isset($_POST['long']) ? $_POST['long'] : die;
		$lat = isset($_POST['lat']) ? $_POST['lat'] : die;
		
		try
		{
			$parcelshopfinder = new DpdParcelShopFinder($login);
			$parcelshopfinder->search(array('long' => $long, 'lat' => $lat));
		}
		catch (Exception $e)
		{
			$this->log->write('Something went wrong looking for ParcelShops (' . $e->getMessage() . ')');
			die;
		}
			
		if($parcelshopfinder->login->refreshed)
		{
			$this->log->write('DPD Login Refreshed');
			$parcelshopfinder->login->refreshed = false;
			$this->cache->set('dpd_carrier_login', serialize($login));
		}
		
		$delivery_address = $this->getDeliveryAddress();
		
		if(isset($parcelshopfinder->results)){
			foreach($parcelshopfinder->results as $key => $parcelshop){
				if($delivery_address['iso_code_2'] != $parcelshop->isoAlpha2){
					unset($parcelshopfinder->results[$key]);
				}
			}
		}
		
		$this->session->data['shops'] = serialize($parcelshopfinder->results);
		
		echo json_encode($parcelshopfinder->results);
		die;
	}
	
	private function getDeliveryAddress() {
		if(isset($this->session->data['shipping_address_id'])) {
			$this->load->model('account/address');
			$delivery_address_id = $this->session->data['shipping_address_id'];
			return $this->model_account_address->getAddress($delivery_address_id);
		} else {
			return $this->session->data['guest']['shipping'];
		}
	}
	
}
?>