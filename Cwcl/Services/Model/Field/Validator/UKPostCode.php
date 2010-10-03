<?php
/**
 * Provides validation for the Postcode field type
 *
 * @package Cwcl Services Model Field Validator
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Validator_UKPostCode extends Zend_Validate_Abstract
{
	// This is simple
	protected $_postcodeRegex = '/[A-Za-z]{1,2}[0-9A-Za-z]{1,2}[ ]?[0-9]{0,1}[A-Za-z]{2}/';

	/**
	 * Validates the value supplied
	 *
	 * @param string $value 
	 * @return boolean
	 * @author Sam de Freyssinet
	 */
	public function isValid($value)
	{
		return preg_match($this->_postcodeRegex, $value);
	}
}