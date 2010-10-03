<?php

/**
 * SSO_Exception
 *
 * Exception wrapper for SSO service
 *
 * @package    Sso
 */
class Sso_Exception extends Zend_Exception
{
	private $_data = array();

	/**
	 * Set extra debug data on the exception, to be displayed on error page.
	 */
	public function setDebugDataValue($key, $value)
	{
		$this->_data[$key] = $value;
		return $this;
	}

	/**
	 * Get the debugging data value for a specific key.
	 */
	public function getDebugDataValue($key)
	{
		return $this->_data[$key];
	}

	/**
	 * Get all (extra) debugging data for this exception.
	 */
	public function getDebugData()
	{
		return $this->_data;
	}
}
