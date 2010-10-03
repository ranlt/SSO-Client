<?php
/**
 * Provides the file field with the filter required to send files
 * using a Cwcl_Services_Model_Field_File definition. This filter
 * provides the file path with an @ prefix.
 *
 * @package  Cwcl
 * @author   Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Filter_Filetransfer implements Zend_Filter_Interface
{
	/**
	 * Returns the value with an @ sign prefix. This should
	 * only be used as a post filter.
	 *
	 * @param   string   $value 
	 * @return  string
	 * @access public
	 */
	public function filter($value)
	{
		return ($value[0] === '@') ? $value : '@'.$value;
	}
}