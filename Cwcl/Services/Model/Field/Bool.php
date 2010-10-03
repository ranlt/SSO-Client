<?php
/**
 * Boolean Field
 *
 * @package Cwcl Services Model Field
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Bool extends Cwcl_Services_Model_Field_Abstract
{
	/**
	 * The value type
	 *
	 * @var string
	 */
	protected $_type = 'bool';

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
		if (NULL === $value or $value === 'NULL') {
			return NULL;
		}

		if (is_string($value)) {
			$value = strtolower($value);
			if (in_array($value, array('t', 'true', 'y', 'yes', '1'))) {
				return TRUE;
			}
			elseif (in_array($value, array('f', 'false', 'n', 'no', '0'))) {
				return FALSE;
			}
			elseif (in_array($value, array('', 'null'))) {
				return NULL;
			}
		}

		return (bool) $value;
	}
}