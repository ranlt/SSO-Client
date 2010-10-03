<?php

/**
 * Sso error class
 *
 */
class Sso_Error
{
	/**
	 * Error message text
	 *
	 * @var string
	 */
	protected $_message;

	/**
	 * Error status
	 *
	 * @var string
	 */
	protected $_code;
	
	/**
	 * Create new error
	 *
	 * @param array $data
	 */
	public function __construct($data)
	{
		if (!is_array($data) || !array_key_exists('errorMessage', $data) 
				|| !array_key_exists('errorCode', $data)) {
			// malformed error data, return empty error information
			// - could throw exception here, but the situation can be handled
			$this->_message = '';
			$this->_code = '';
			
			return;
		}
		
		if (is_array($data['errorMessage'])) {
			$this->_message = join(', ', $data['errorMessage']['messages']);
		} else {
			$this->_message = $data['errorMessage'];
		}
		
		$this->_code = $data['errorCode'];
	}
	
	/**
	 * Return true is we have a valid error message - oh the irony
	 *
	 * @return bool
	 */
	public function isValid()
	{
		return !empty($this->_message);
	}
	
	/**
	 * Get the error message
	 *
	 * @return string
	 */
	public function getMessage()
	{
		return $this->_message;
	}
	
	/**
	 * Get the status
	 *
	 * @return string
	 */
	public function getCode()
	{
		return $this->_code;
	}

	/**
	 * Convenience method to retrieve as a string
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getCode() . ' ' . $this->getMessage();
	}
	
	/**
	 * Convenience method to retrieve as an array
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array('message' => $this->getMessage(), 'code' => $this->getCode());
	}
}