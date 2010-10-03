<?php
/**
 * String Field
 *
 * @package Cwcl Services Model Field
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_String extends Cwcl_Services_Model_Field_Abstract
{
	/**
	 * The value type
	 *
	 * @var string
	 */
	protected $_type = 'string';

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
		return (NULL === $value) ? NULL : (string) $value;
	}
}