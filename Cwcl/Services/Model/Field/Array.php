<?php
/**
 * Boolean Field
 *
 * @package Cwcl Services Model Field
 * @author Andrew Lord <andrew.lord@cw.com>
 */
class Cwcl_Services_Model_Field_Array extends Cwcl_Services_Model_Field_Abstract
{
	/**
	 * The value type
	 *
	 * @var string
	 */
	protected $_type = 'array';

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
		if(is_array($value))
		{
			return $value;
		}

		if (NULL === $value or 'NULL' === strtoupper($value)) {
			return NULL;
		}

		return (array) $value;
	}
}
