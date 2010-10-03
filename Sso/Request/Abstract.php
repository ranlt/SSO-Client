<?php
/**
 * Abstract request class that formats
 * requests that are passed through the
 * Sso_Client
 *
 * @package Sso
 * @abstract
 */
abstract class Sso_Request_Abstract
{
	/**
	 * HTTP request methods
	 */
	const GET     = 'GET';
	const POST    = 'POST';
	const PUT     = 'PUT';
	const DELETE  = 'DELETE';

	/**
	 * Result types
	 */
	const RESULT_STRING = 1;
	const RESULT_OBJECT = 2;
	const RESULT_ASSOC  = 3;

	/**
	 * Allowed HTTP methods
	 */
	static protected $_allowedMethods = array('GET', 'PUT', 'POST', 'DELETE');

	/**
	 * Base path for the url
	 *
	 * @var string
	 */
	protected $_path = '/';

	/**
	 * Params for POSTing
	 *
	 * @var array of the form $key => $value, ...
	 */
	protected $_postParams = array();

	/**
	 * Params for GETing
	 *
	 * @var array of the form $key => $value
	 */
	protected $_getParams = array();

	/**
	 * Params to be set as path fragments in the url
	 *
	 * @var array of the form $part1, $part2, ...
	 */
	protected $_urlParams = array();

	/**
	 * Cookies to be sent as part of the request
	 *
	 * @var associative array
	 */
	protected $_cookies = array();
	
	/**
	 * Encoding multipart
	 * @var string
	 */
	protected $_isEnctypeMultipart;

	/**
	 * Request method, one of GET, POST, PUT or DELETE
	 *
	 * @var string
	 */
	protected $_method = self::GET;

	/**
	 * Allowed error responses for the request
	 *
	 * @var array of integers indicating the http respose code
	 */
	protected $_allowedErrors = array();

	/**
	 * Whether to parse the resultant JSON
	 *
	 * @var int
	 */
	protected $_resultType = self::RESULT_OBJECT;

	/**
	 * Create new one of these bad bois!
	 *
	 * @param array $data
	 */
	public function __construct($data = null)
	{
		if (null !== $data) {
			$this->setValues($data);
		}
	}

	/**
	 * Serialisation magic method
	 *
	 * @return array
	 * @access public
	 */
	public function __sleep()
	{
		return array('_path', '_postParams', '_getParams', '_urlParams', '_cookies', '_method', '_allowedErrors', '_resultType');
	}

	/**
	 * Factory to create new requests, makes it more fluent
	 *
	 * @param array $data
	 * @return Sso_Request
	 */
	public static function factory($data = null)
	{
		return new Sso_Request($data);
	}

	/**
	 * Set all values passed in
	 *
	 * @param array $data
	 */
	public function setValues($data)
	{
		foreach ($data as $key => $value) {
			$method = 'set' . ucfirst($key);

			if (method_exists($this, $method)) {
				$this->$method($value);
			}
		}
	}

	/**
	 * Get the currently configured path
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * Set the path for the url
	 *
	 * @param string $path
	 * @return Sso_Request
	 */
	public function setPath($path)
	{
		$this->_path = rtrim($path, '/');

		return $this;
	}
	
	/**
	 * Flag for POST data -d or -F
	 * @return boolean
	 */
	public function getIsEnctypeMultipart()
	{
		return $this->_isEnctypeMultipart;
	}

	/**
	 * 
	 *
	 * @param string $path
	 * @return Sso_Request
	 */
	public function setIsEnctypeMultipart($param)
	{
		$this->_isEnctypeMultipart = $param;

		return $this;
	}	

	/**
	 * Get the full URL for the request
	 *
	 * @param string $baseUrl
	 * @return string
	 */
	public function getUrl($baseUrl)
	{
		$uri = Zend_Uri_Http::fromString($baseUrl);
		// remove any copies of base url from our path
		// also make sure there is a single leading /
		$path = '/'.ltrim(str_replace((string)$uri, '', rtrim($this->getPath(), '/')), '/');
		foreach ($this->getUrlParams() as $value) {
			$path .= rtrim('/' . urlencode($value), '/');
		}

		$uri->setPath($path);
		$uri->setQuery($this->_getParams);

		return $uri->getUri();
	}

	/**
	 * Returns true if the request has parameters to POST
	 *
	 * @return boolean
	 */
	public function hasPostParams()
	{
		return count($this->_postParams) > 0;
	}

	/**
	 * Set the POST parameters
	 *
	 * @param array $params of the form $key => $value, ...
	 * @return Sso_Request
	 */
	public function setPostParams($params)
	{
		$this->_postParams = $params;

		return $this;
	}

	/**
	 * Get the currently configured POST parameters
	 *
	 * @return array
	 */
	public function getPostParams()
	{
		return $this->_postParams;
	}

	/**
	 * Get a single POST parameter, or null if it does not exist
	 *
	 * @param string $key
	 * @return string
	 */
	public function getPostParam($key)
	{
		if (array_key_exists($key, $this->_postParams)) {
			return $this->_postParams[$key];
		}

		return null;
	}

	/**
	 * Add a single POST parameter, will overwrite an existing parameter of the
	 * same name.
	 *
	 * @param string $key
	 * @param string $value
	 * @return Sso_Request
	 */
	public function addPostParam($key, $value)
	{
		$this->_postParams[$key] = $value;

		return $this;
	}

	/**
	 * Remove a specific POST parameter
	 *
	 * @param string $key
	 */
	public function removePostParam($key)
	{
		if (array_key_exists($key, $this->_postParams)) {
			unset($this->_postParams[$key]);
		}
	}

	/**
	 * Test to see if there are any cookies set
	 *
	 * @return boolean
	 */
	public function hasCookies()
	{
		// Empty array will return false
		return (bool) $this->_cookies;
	}

	/**
	 * Get the contents of the cookie jar
	 *
	 * @return array
	 */
	public function getCookies()
	{
		return $this->_cookies;
	}

	/**
	 * Sets/replaces all the cookies in this
	 * request
	 *
	 * @param array $cookies 
	 * @return self
	 */
	public function setCookies($cookies)
	{
		$this->_cookies = $cookies;
		return $this;
	}

	/**
	 * Return the value of a specific cookie
	 * in the jar
	 *
	 * @param string $key 
	 * @return mixed
	 */
	public function getCookie($key)
	{
		return (array_key_exists($key, $this->_cookies)) ? $this->_cookies[$key] : NULL;
	}

	/**
	 * Set a value to a specific cookie in
	 * the jar
	 *
	 * @param string $key 
	 * @param string $value 
	 * @return self
	 */
	public function setCookie($key, $value)
	{
		$this->_cookies[$key] = $value;
		return $this;
	}

	/**
	 * Remove a key from the jar
	 *
	 * @param string $key 
	 * @return void
	 */
	public function removeCookie($key)
	{
		if (array_key_exists($key, $this->_cookies)) {
			unset($this->_cookies[$key]);
		}
	}

	/**
	 * Returns true if the request has GET parameters
	 *
	 * @return boolean
	 */
	public function hasGetParams()
	{
		return count($this->_getParams) > 0;
	}

	/**
	 * Set a series of parameters to use in a GET request
	 *
	 * @param array $params
	 * @return Sso_Request
	 */
	public function setGetParams($params)
	{
		$this->_getParams = $params;

		return $this;
	}

	/**
	 * Get all GET parameters
	 *
	 * @return array of the form $key => $value, ...
	 */
	public function getGetParams()
	{
		return $this->_getParams;
	}

	/**
	 * Add a single GET parameter
	 *
	 * @param string $key
	 * @param string $value
	 * @return unknown
	 */
	public function addGetParam($key, $value)
	{
		$this->_getParams[$key] = $value;

		return $this;
	}

	/**
	 * Remove a single GET parameter by name
	 *
	 * @param string $key
	 */
	public function removeGetParam($key)
	{
		if (array_key_exists($key, $this->_getParams)) {
			unset($this->_getParams[$key]);
		}
	}

	/**
	 * Get a single GET parameter by name
	 *
	 * @param string $key
	 * @return string value of key or null if not set
	 */
	public function getGetParam($key)
	{
		if (array_key_exists($key, $this->_getParams)) {
			return $this->_getParams[$key];
		}

		return null;
	}

	/**
	 * Whether the request has url parameters
	 *
	 * @return boolean
	 */
	public function hasUrlParams()
	{
		return count($this->_urlParams) > 0;
	}

	/**
	 * Set the URL parameters
	 *
	 * @param array $params of the form $param1, $param2, ...
	 * @return Sso_Request
	 */
	public function setUrlParams($params)
	{
		$this->_urlParams = $params;

		return $this;
	}

	/**
	 * Get the current URL parameters
	 *
	 * @return array
	 */
	public function getUrlParams()
	{
		return $this->_urlParams;
	}

	/**
	 * Add a single URL parameters
	 *
	 * @param string $value
	 * @return Sso_Request
	 */
	public function addUrlParam($value)
	{
		$this->_urlParams[] = $value;

		return $this;
	}

	/**
	 * Set the request method, one of GET, PUT, POST or DELETE
	 *
	 * @param string $method
	 * @return Sso_Request
	 * @throws Sso_Exception
	 */
	public function setMethod($method)
	{
		$method = strtoupper($method);

		if ( ! in_array($method, Sso_Request_Abstract::$_allowedMethods)) {
			throw new Sso_Exception(__METHOD__.' requires a valid HTTP method. Supplied : '.$method);
		}

		$this->_method = $method;

		return $this;
	}

	/**
	 * Get the request method
	 *
	 * @return string
	 */
	public function getMethod()
	{
		return $this->_method;
	}

	/**
	 * Set the allowed errors for the request
	 *
	 * @param array $errors
	 * @return Sso_Request
	 */
	public function setAllowedErrors($errors)
	{
		$this->_allowedErrors = $errors;

		return $this;
	}

	/**
	 * Get the allowed errors for the request
	 *
	 * @return array
	 */
	public function getAllowedErrors()
	{
		return $this->_allowedErrors;
	}

	/**
	 * Set the result type, supported types are associative arry, string and
	 * object
	 *
	 * @param int $resultType
	 * @return Sso_Request
	 */
	public function setResultType($resultType)
	{
		$this->_resultType = $resultType;

		return $this;
	}

	/**
	 * Get the request's result type
	 *
	 * @return unknown
	 */
	public function getResultType()
	{
		return $this->_resultType;
	}

	/**
	 * String interpretation of the request
	 *
	 * @return unknown
	 */
	public function __toString()
	{
		$path = rtrim($this->_path, '/');

		if ($this->hasUrlParams()) {
			foreach ($this->getUrlParams() as $value) {
				$path .= rtrim('/' . urlencode($value), '/');
			}
		}

		if ($this->hasGetParams()) {
			foreach ($this->getGetParams() as $key => $value) {
				$_params[] = $key . '=' . urlencode($value);
			}
			$path .= '?'.implode('&', $_params);
		}

		if ($this->hasPostParams()) {
			foreach ($this->getPostParams() as $key => $value) {
				$path .= ' -d ' . $key . '=' . urlencode($value);
			}
		}

		if ($this->hasCookies()) {
			foreach ($this->getCookies() as $key => $value) {
				$path .= ' -b '.$key.'='.urlencode($value);
			}
		}

		return $this->_method . ' ' . $path;
	}
}