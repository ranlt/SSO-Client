<?php
/**
 * Boolean Field
 *
 * @package Cwcl Services Model Field
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_StdClass extends Cwcl_Services_Model_Field_Abstract
{
	/**
	 * The value type
	 *
	 * @var string
	 */
	protected $_type = 'object';

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
		if(is_object($value))
		{
			return $value;
		}

		if (NULL === $value or 'NULL' === strtoupper($value)) {
			return NULL;
		}

		return (object) $value;
	}
}