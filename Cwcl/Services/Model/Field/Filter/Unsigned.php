<?php
/**
 * Filter class to format unsigned values
 *
 * @package Cwcl Services Client
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Filter_Unsigned implements Zend_Filter_Interface
{
	/**
	 * Returns the value as unsigned
	 *
	 * @param integer|float $value 
	 * @return integer|float
	 * @access public
	 */
	public function filter($value)
	{
		return abs($value);
	}
}