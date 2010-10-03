<?php

class Cwcl_Services_Request implements Serializable
{
	/**
	 * HTTP request methods
	 */
	const GET     = 'GET';
	const POST    = 'POST';
	const PUT     = 'PUT';
	const DELETE  = 'DELETE';

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
	 * Request method, one of GET, POST, PUT or DELETE
	 *
	 * @var string
	 */
	protected $_method = self::GET;

	/**
	 * Whether request should return single
	 * Cwcl_Services_Model_Abstract or
	 * Cwcl_Services_Model_Iterator
	 *
	 * @var boolean
	 */
	protected $_singleRecord = TRUE;

	/**
	 * Create new one of these bad bois!
	 *
	 * @param array $data
	 */
	public function __construct(array $data = NULL)
	{
		if (NULL !== $data) {
			$this->setValues($data);
		}
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
	public function serialize(array $toSerialize = array())
	{
		$toSerialize += array(
			'_path'         => $this->_path,
			'_postParams'   => $this->_postParams,
			'_getParams'    => $this->_getParams,
			'_urlParams'    => $this->_urlParams,
			'_cookies'      => $this->_cookies,
			'_method'   => $this->_method,
		);

		return serialize($toSerialize);
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
	 * @return Cwcl_Services_Request
	 */
	public function setPath($path)
	{
		$this->_path = rtrim($path, '/');

		return $this;
	}

	/**
	 * Get the full URL for the request
	 *
	 * @param string $baseUrl
	 * @return string
	 */
	public function getUrl()
	{
		$uri = Zend_Uri_Http::fromString($this->getPath());
		// remove any copies of base url from our path
		// also make sure there is a single leading /
		$path = '/'.ltrim(str_replace((string)$uri, '', rtrim($this->getPath(), '/')), '/');
		foreach ($this->getUrlParams() as $value) {
			$path .= rtrim('/' . urlencode($value), '/');
		}
		$path = preg_replace('@^(/+)(.*)$@', '/\\2', $path);
        
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
		return (bool) $this->_postParams;
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
	 * @return string|void
	 */
	public function getPostParam($key)
	{
		return (isset($this->_postParams[$key])) ? $this->_postParams[$key] : NULL;
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
		if (isset($this->_postParams[$key])) {
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
	 * @return string|void
	 */
	public function getCookie($key)
	{
		return (isset($this->_cookies[$key])) ? $this->_cookies[$key] : NULL;
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
		if (isset($this->_cookies[$key])) {
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
		return (bool) $this->_getParams;
	}

	/**
	 * Set a series of parameters to use in a GET request
	 *
	 * @param array $params
	 * @return self
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
	 * @return self
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
		if (isset($this->_getParams[$key])) {
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
		return (isset($this->_getParams[$key])) ? $this->_getParams[$key] : NULL;
	}

	/**
	 * Whether the request has url parameters
	 *
	 * @return boolean
	 */
	public function hasUrlParams()
	{
		return (bool) $this->_urlParams;
	}

	/**
	 * Set the URL parameters
	 *
	 * @param array $params of the form $param1, $param2, ...
	 * @return self
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
	 * @return self
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
	 * @return self
	 * @throws Cwcl_Services_Request_Exception
	 */
	public function setMethod($method)
	{
		$method = strtoupper($method);

		if ( ! in_array($method, self::$_allowedMethods)) {
			throw new Cwcl_Services_Request_Exception(__METHOD__.' requires a valid HTTP method. Supplied : '.$method);
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
	 * Control the single record value
	 *
	 * @param boolean $value 
	 * @return self
	 * @access public
	 */
	public function setSingleRecord($value)
	{
		$this->_singleRecord = (bool) $value;
		return $this;
	}

	/**
	 * Return the single record value
	 *
	 * @return boolean
	 * @access public
	 */
	public function getSingleRecord()
	{
		return $this->_singleRecord;
	}

	/**
	 * String interpretation of the request
	 *
	 * @return string
	 */
	public function __toString()
	{
		$path = rtrim($this->_path, '/');

		if ($this->hasUrlParams()) {
			$urlParams = $this->getUrlParams();
			foreach ($urlParams as $value) {
				$path .= rtrim('/' . urlencode($value), '/');
			}
		}

		if ($this->hasGetParams()) {
			$getParams = $this->getGetParams();
			foreach ($getParams as $key => $value) {
				$_params[] = $key . '=' . urlencode($value);
			}
			$path .= '?'.implode('&', $_params);
		}

		if ($this->hasPostParams()) {
			$postParams = $this->getPostParams();
			foreach ($postParams as $key => $value) {
				if (is_array($value) && count ($value) < 2) {
					$path .= ' -d ' . $key . '=' . urlencode($value[0]);
				} else {
					$path .= ' -d ' . $key . '=' . urlencode($value);
				}
			}
		}

		if ($this->hasCookies()) {
			$cookies = $this->getCookies();
			foreach ($cookies as $key => $value) {
				$path .= ' -b '.$key.'='.urlencode($value);
			}
		}

		return $this->_method . ' ' . $path;
	}
}
