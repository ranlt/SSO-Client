<?php
/**
 * Cwcl Services response object. Created by the
 * gateway when a request is executed.
 *
 * @package Cwcl Services
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Response implements Serializable
{
	/**
	 * HTTP Status messages
	 *
	 * @var array
	 */
	static public $messages = array(
		// Informational 1xx
		100 => 'Continue',
		101 => 'Switching Protocols',

		// Success 2xx
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',

		// Redirection 3xx
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found', // 1.1
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		// 306 is deprecated but reserved
		307 => 'Temporary Redirect',

		// Client Error 4xx
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',

		// Server Error 5xx
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		509 => 'Bandwidth Limit Exceeded'
	);

	/**
	 * Returned HTTP code from the request
	 *
	 * @var integer
	 */
	protected $_httpCode;

	/**
	 * Headers returned from the request
	 *
	 * @var array
	 */
	protected $_headers;

	/**
	 * The full request object that spawned
	 * this response
	 *
	 * @var Cwcl_Services_Request_Abstract
	 */
	protected $_request;

	/**
	 * The response content returned by the
	 * request;
	 *
	 * @var string
	 */
	protected $_content;

	/**
	 * Standard constructor
	 *
	 * @param array $options 
	 * @access public
	 */
	public function __construct(array $options = array())
	{
		foreach ($options as $key => $value) {
			$key = '_'.$key;

			if (property_exists($this, $key)) {
				$this->$key = ($key === '_httpCode') ? intval($value) : $value;
			}
		}
	}

	/**
	 * Return the current http code
	 *
	 * @return integer
	 * @access public
	 */
	public function getHttpCode()
	{
		return $this->_httpCode;
	}

	/**
	 * Get a unique header based on the name
	 *
	 * @param string $name 
	 * @return string|void
	 * @access public
	 */
	public function getHeader($name)
	{
		return isset($this->_headers[$name]) ? $this->_headers[$name] : NULL;
	}

	/**
	 * Get all the headers as an array
	 *
	 * @return array
	 * @access public
	 */
	public function getHeaders()
	{
		return $this->_headers;
	}

	/**
	 * Get the originating request that
	 * created this response
	 *
	 * @return Cwcl_Services_Request_Abstract
	 * @access public
	 */
	public function getRequest()
	{
		return $this->_request;
	}

	/**
	 * Get the body of the content returned
	 * from the request
	 *
	 * @return string
	 * @access public
	 */
	public function getContent()
	{
		return $this->_content;
	}

	/**
	 * Serialises the object for persistent
	 * using the SPL interface
	 * 
	 * [SPL Serializable]
	 *
	 * @param array $toSerialized [Optional]
	 * @return string
	 * @access public
	 */
	public function serialize(array $toSerialized = array())
	{
		$toSerialized += array(
			'_httpCode'    => $this->_httpCode,
			'_headers'     => $this->_headers,
			'_request'     => $this->_request,
			'_content'     => $this->_content,
		);

		return serialize($toSerialized);
	}

	/**
	 * Unserialises the object from persistent
	 * storage
	 * 
	 * [SPL Serializable]
	 *
	 * @param string $serialized 
	 * @return void
	 * @access public
	 */
	public function unserialize($serialized)
	{
		$unserialized = unserialize($serialized);

		foreach ($unserialized as $key => $value) {
			$this->$key = $value;
		}
	}
}