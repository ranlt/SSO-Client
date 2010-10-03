<?php
/**
 * Float Field
 *
 * @package Cwcl Services Model Field
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Float extends Cwcl_Services_Model_Field_Abstract
{
	/**
	 * The value type
	 *
	 * @var string
	 */
	protected $_type = 'float';

	/**
	 * Parses a value. Takes the input value
	 * and converts it to the Field type
	 *
	 * @param mixed $value 
	 * @return mixed
	 * @access protected
	 */
	protected function parseValue($value)
	{
		return floatval($value);
	}
}