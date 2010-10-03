<?php
/**
 * Filter class to format to unsigned values
 *
 * @package Cwcl Services Client
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Filter_Zerofill implements Zend_Filter_Interface
{
	/**
	 * Controls the amount of padding returned
	 *
	 * @var string
	 */
	protected $_padding = 10;

	/**
	 * Constructor allows the setting of padding value
	 *
	 * @param integer $padding 
	 * @access public
	 */
	public function __construct($padding = NULL)
	{
		if (NULL !== $padding and 0 <= $padding) {
			$this->_padding = (int) $padding;
		}
	}

	/**
	 * Returns the value as unsigned
	 *
	 * @param integer $value 
	 * @return string
	 * @access public
	 */
	public function filter($value)
	{
		return sprintf('%0' . $this->_padding . 's', $value);
	}
}