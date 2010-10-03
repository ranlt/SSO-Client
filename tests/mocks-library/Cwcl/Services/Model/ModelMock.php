<?php

/**
* Just a test implementation of Cwcl_Services_Model_Abstract to test some of the concrete stuff
*/
class Cwcl_Services_Model_Mock extends Cwcl_Services_Model_Abstract
{
	
	protected $_mapping = array(
		'uid' => 'id',
		'name' => 'fullname',
		'same' => 'same'
	);
}
