<?php
/**
 * New custom client exception with built in handling and
 * retrieval of HTTP status codes.
 *
 * @category Cwcl
 * @package Services Client
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Client_Exception extends Zend_Exception {

	/**
	 * HTTP Status code
	 *
	 * @var integer
	 */
	protected $httpStatus;

	/**
	 * Constructor for this exception, note the HTTP status
	 * must be supplied if nothing else
	 *
	 * @param int $httpStatus 
	 * @param string $message 
	 * @param mixed $code 
	 * @param Exception $previous 
	 * @access public
	 */
	public function __construct($httpStatus, $message = NULL, $code = 0)
	{
		parent::__construct($message, $code);

		// Ensure HTTP status is an integer
		$httpStatus = intval($httpStatus);

		if (in_array($httpStatus, Cwcl_Services_Response::$messages)) {
			$this->httpStatus = $httpStatus;
		}
		// Catch all case, set to 500 internal server error
		// (hey, if the status isn't recognised, then you've messed up and 500 is valid!)
		else {
			$this->httpStatus = 500;
		}
	}

	/**
	 * Returns the http status of this exception
	 *
	 * @return integer
	 * @access public
	 */
	public function getHttpStatus()
	{
		return $this->httpStatus;
	}

	/**
	 * Returns the http status message
	 *
	 * @return string
	 * @access public
	 */
	public function getHttpStatusMessage()
	{
		return Cwcl_Services_Response::$messages[$this->httpStatus];
	}

	/**
	 * Overload the __toString method to
	 * output a more useful error message
	 *
	 * @return string
	 * @access public
	 */
	public function __toString()
	{
		// Add the standard W3C status message if there is no custom message
		if (NULL === $this->message) {
			$this->message = Cwcl_Services_Response::$messages[$this->httpStatus];
		}

		// Return the string
		return __CLASS__.' ['.$this->httpStatus.'] '.$this->message;
	}
}