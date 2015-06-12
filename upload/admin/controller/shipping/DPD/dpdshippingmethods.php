<?php

class DpdShippingMethods
{
	public $default;
	public $methods;

	public function __construct()	
	{
		$this->default = new stdClass();
		$this->default->max_width = 175;			// Real max length
		$this->default->max_circum = 125; 		// (max-height + max-depth) * 2 < 125
		$this->default->max_weight = 31.5;
		$this->default->zones = array('Europe');
		$this->default->weight_ranges = array(0,3,31.5);
		
		$this->methods = array();
		
		$this->methods[0] = new stdClass();
		$this->methods[0]->name = 'Home';
		$this->methods[0]->shorthand = 'home';
		$this->methods[0]->type = 'B2B';
		$this->methods[0]->description = 'Get your parcel delivered at your place';
		
		$this->methods[1] = new stdClass();
		$this->methods[1]->name = 'Home With Predict';
		$this->methods[1]->shorthand = 'predict';
		$this->methods[1]->type = 'B2C';
		$this->methods[1]->description = 'Get your parcel delivered at your place (with notification of delivery)';
		
		$this->methods[2] = new stdClass();
		$this->methods[2]->name = 'Pickup';
		$this->methods[2]->shorthand = 'pickup';
		$this->methods[2]->type = 'PSD';
		$this->methods[2]->description = 'Get your parcel delivered at a Pickup point and collect it at your convenience.';
		$this->methods[2]->max_width = 100;
		$this->methods[2]->max_circum = 200;		
		$this->methods[2]->max_weight = 20;		
		$this->methods[2]->weight_ranges = array(0,3,10,20);
	}
}