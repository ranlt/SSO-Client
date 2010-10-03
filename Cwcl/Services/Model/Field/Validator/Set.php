<?php
/**
 * Provides validation for the Enum field type
 *
 * @package Cwcl Services Model Field Validator
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Validator_Set extends Zend_Validate_InArray
{
	/**
	 * Validates the value supplied
	 *
	 * @param string $value 
	 * @return boolean
	 * @author Sam de Freyssinet
	 */
	public function isValid($value)
	{
		$this->_setValue($value);
		$set = explode(',', $value);
		
		foreach ($set as $enum) {
			$isValid = parent::isValid($enum);
			
			if (!$isValid) {
				break;
			}
		}
	
		return $isValid;
	}
}