<?php
/**
 * Abstract Sso_Client class
 *
 * @package Sso
 * @abstract
 */
abstract class Sso_Client_Abstract {

	/**
	 * Default cache timeout
	 *
	 */
	const CACHE_TIMEOUT = 300; // 300 seconds = 5 mins

	/**
	 * Default cache prefix
	 *
	 */
	const CACHE_ID_PREFIX = 'SSO__Token__';

	/**
	 * Current instance
	 *
	 * @var array(Sso_Client)
	 */
	protected static $_instances = array();

	/**
	 * Cache
	 *
	 * @var Zend_Cache
	 */
	protected $_cache;

	/**
	 * Configuration for this library
	 * default contains :
	 * - url
	 * - cacheIdPrefix
	 * - cacheTimeout
	 *
	 * @var Zend_Config
	 */
	protected $_config;

	/**
	 * Handle to curl multi
	 *
	 * @var resource
	 */
	protected $_curlMultiHandle = null;

	/**
	 * Current requests to be processed
	 *
	 * @var array of Sso_Request, indexed by string id
	 */
	protected $_requests = array();

	/**
	 * Most recent responses
	 *
	 * @var array, indexed by string id
	 */
	protected $_responses = array();

	/**
	 * Latest result
	 *
	 * @var array
	 */
	protected $_result;

	/**
	 * Logger
	 *
	 * @var Zend_Log
	 */
	protected $_logger = null;

	/**
	 * List of errors we might expect from the service
	 *
	 * @var array
	 */
	protected $_knownErrors = array(400, 401, 404, 405, 409);

	/**
	 * Client factory
	 *
	 * @param string|array|Zend_Config $config [Optional]
	 * @param string $client [Optional]
	 *
	 * @return Sso_Client
	 * @static
	 */
	public static function factory($config = array(), $client = 'Sso_Client') {
		return self::getInstance($config, $client);
	}

	/**
	 * Return the current instance
	 *
	 * @param string|array|Zend_Config $config [Optional]
	 * @param string $client [Optional]
	 * @return Sso_Client
	 * @static
	 */
	public static function getInstance($config = array(), $client = 'Sso_Client') {
		// If there is no client available for the current URL, set one up
		if (!isset(self::$_instances[$client])) {
			// Sanitize the supplied configutation into Zend_Config object
			$config = self::_sanitizeConfiguration($config);

			// Sanity check the configuration
			if (!$config->get('url', false)) {
				throw new Sso_Client_Exception('No URL supplied in the Sso_Client configuration!');
			}

			self::$_instances[$client] = new $client($config);
		}
		return self::$_instances[$client];
	}

	/**
	 * Destroy an instance of the client
	 *
	 * @param $client
	 */
	public static function destroyInstance($client = 'Sso_Client')
	{
		unset(self::$_instances[$client]);
	}

	/**
	 * Gets the instance name based on the URL set in config
	 *
	 * @param string|array|Zend_Config $config
	 * @return Zend_Config
	 * @throws Sso_Client_Exception
	 * @static
	 */
	protected static function _sanitizeConfiguration($config) {
		// If $config is already a Zend_Config object
		if ($config instanceof Zend_Config) {
			return $config;
		}
		// Arrays are easy
		if (is_array($config)) {
			return new Zend_Config($config);
		}
		// Strings must be a URL only
		if (is_string($config)) {
			return new Zend_Config(array('url' => $config));
		}
		throw new Sso_Client_Exception('$config argument was not recognised. Must be string, array or Zend_Config!');
	}

	/**
	 * Internal constructor
	 *
	 * @param Zend_Config $config
	 */
	protected function __construct(Zend_Config $config) {
		// Create a default configuration (to merge with)
		$default_config = new Zend_Config(array(
			'cacheIdPrefix' => self::CACHE_ID_PREFIX,
			'cacheTimeout'  => self::CACHE_TIMEOUT
		), true);

		// Merge the two configurations
		$default_config->merge($config);

		// Apply the default configuration to this class
		$this->_config = $default_config;

		if (Zend_Registry::isRegistered('log')) $this->setLogger(Zend_Registry::get('log'));
	}

	/**
	 * Set the cache to use
	 *
	 * @param Zend_Cache $cache
	 * @return Sso_Client_Abstract
	 */
	public function setCache(Zend_Cache_Core $cache) {
		$this->_cache = $cache;

		return $this;
	}

	/**
	 * Set the logger to use
	 *
	 * @param Zend_Log $logger
	 * @return Sso_Client_Abstract
	 */
	public function setLogger(Zend_Log $logger)
	{
		$this->_logger = $logger;

		return $this;
	}

	/**
	 * Initialise the curl handle
	 *
	 * @param string $url
	 *
	 * @return resource(curl)
	 */
	protected function _initCurl($url) {
		$ch = curl_init($url);
		if (PHP_SAPI !== 'cli') {
			$timeout = (int) ini_get('max_execution_time');
			$timeout -= 5;
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		}
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER,         false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_FORBID_REUSE,   true);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT,  true);
		return $ch;
	}

	/**
	 * Convert a request into a curl resource
	 *
	 * @param Sso_Request $request
	 *
	 * @return resource(curl)
	 */
	protected function _buildCurl($request) {
		$ch = $this->_initCurl($request->getUrl($this->getUrl()));

		$method = $request->getMethod();

		switch ($method) {
			case Sso_Request::GET: {
				break;
			}
			case Sso_Request::POST: {
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getPostParams());
				break;
			}
			case Sso_Request::PUT: {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request->getPostParams()));
				break;
			}
			case Sso_Request::DELETE: {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
			}
			default: {
				throw new Sso_Client_Exception('Unknown method: ' . $method);
			}
		}

		// Process cookies if any have been set
		if (($cookies = $request->getCookies())) {
			// Create a jar
			$jar = array();

			// Process the set cookies
			foreach ($cookies as $key => $value) {
				$jar[] = $key.'='.$value;
			}

			// Format the cookie string
			$cookieString = implode(' ;', $jar);

			// Set the cookie to the CURL request
			curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
		}

		return $ch;
	}

	/**
	 * Write an entry to the log file
	 *
	 * @param string $level
	 * @param string $message
	 */
	protected function _log($level, $message) {
		if (null !== $this->_logger) {
			if (strpos($message, 'password=')) {
				// ** out the password
				$message = preg_replace("/password=[^ ]*/", 'password=*******', $message);
			}

			$this->_logger->$level('['.$this->getUrl().'] '.$message);
		}
	}

	/**
	 * Process the resultant body text
	 *
	 * @param string $body
	 * @return string|StdObject|array
	 * @abstract
	 */
	abstract protected function processBody($body, $request);

	/**
	 * Process an SSO Response
	 *
	 * @return string|Sso_Error|array
	 */
	protected function processResponse($response, $info, $request, $error = null) {
		if ($info['http_code'] == 0) {
			// means host cannot be contacted
			throw new Sso_Client_Exception_HostUnreachable('Cannot reach : ' . $info['url'] . ', check your configuration. Exact error was: ' . $error);
		} elseif ($info['http_code'] != 200) {
			// some sort of error, need to check whether its something we expect
			if (!in_array($info['http_code'], $this->_knownErrors)) {
				// nope, no real graceful way of failing
				throw new Sso_Client_Exception('Unknown error from request: '
					.$info['http_code'], $info['http_code']);
			}

			// known error, return a nice wrapper
			return new Sso_Error(json_decode($response, true));
		}

		return $this->processBody($response, $request, $info);
	}

	/**
	* Execute a request
	*
	* @param Sso_Request $request [optional]
	* @return Sso_Client
	*/
	public function run(Sso_Request_Abstract $request = null) {
		// clear out any existing responses
		$this->_responses = array();
		try {
			if (null !== $request) {
				$this->_runSingle(array('single' => $request));
			} elseif (count($this->_requests) > 1) {
				$this->_runMulti($this->_requests);
			} elseif (count($this->_requests) == 1) {
				$this->_runSingle($this->_requests);
			}
		} catch (Exception $e) {
			// problem with one or more requests
			$this->_requests = array();
			throw $e;
		}

		// clear out requests
		$this->_requests = array();

		return $this;
	}

	/**
	 * Run a single request
	 *
	 * @param array $requests
	 *
	 * @return Sso_Client
	 */
	protected function _runSingle($requests) {
		if (($id = key($requests))) {
			$time_start = microtime(true);
			$ch = $this->_buildCurl($requests[$id]);
			$this->_responses[$id] = $this->processResponse(curl_exec($ch), curl_getinfo($ch), $requests[$id], curl_error($ch));
			curl_close($ch);
			$time_end = microtime(true);
			$time = round($time_end - $time_start,4);
			$this->_log('debug', "{$requests[$id]} (Single)({$time}s)");
			return $this;
		}

		throw new Exception('No requests specified');
	}

	/**
	 * Run multiple requests
	 *
	 * @param array $requests
	 *
	 * @return Sso_Client
	 */
	protected function _runMulti($requests) {
		$this->_curlMultiHandle = curl_multi_init();

		$handles = array();
		$this->_log('debug', 'Beginning multi-request');
		foreach ($this->_requests as $id => $request) {
			$ch = $this->_buildCurl($request);
			$handles[$id] = $ch;
			curl_multi_add_handle($this->_curlMultiHandle, $ch);
			$this->_log('debug', ' - Request: ' . $request);
		}

		$running = 0;
		do {
			curl_multi_exec($this->_curlMultiHandle, $running);
		} while ($running > 0);
		$this->_log('debug', 'Successfullly completed multi-request');

		foreach ($handles as $id => $ch) {
			$this->_responses[$id] = $this->processResponse(curl_multi_getcontent($ch), curl_getinfo($ch), $requests[$id]);
			curl_close($ch);
			curl_multi_remove_handle($this->_curlMultiHandle, $ch);
		}

		curl_multi_close($this->_curlMultiHandle);

		return $this;
	}

	/**
	 * Get the current URL
	 *
	 * @return string
	 */
	public function getUrl() {
		return $this->_config->url;
	}

	/**
	 * Set the current URL
	 *
	 * @param string $url
	 * @return Sso_Client
	 */
	public function setUrl($url) {
		$this->_config->url = $url;

		return $this;
	}

	/**
	 * Store a request for later execution
	 *
	 * @param Sso_Request $request
	 * @param string $id
	 * @return Sso_Client
	 */
	public function storeRequest($request, $id) {
		$this->_requests[$id] = $request;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getRequests() {
		return $this->_requests;
	}

	/**
	 * Get a response related to a request
	 *
	 * @param string $id
	 *
	 * @return mixed
	 */
	abstract public function getResponse($id = 'single');

	/**
	 * Get the http message from the specified request
	 *
	 * @param string $id
	 * @return string
	 */
	public function getMessage($id = 'single') {
		try {
			return $this->getResponse($id)->getMessage();
		} catch (Exception $e) {
			return FALSE;
		}
	}

	/**
	 * Get the http code for the specified request
	 *
	 * @param string $id
	 * @return string
	 */
	public function getStatus($id = 'single') {
		try {
			return $this->getResponse($id)->getStatus();
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Write some data to our cache, if present
	 *
	 * @param string $id
	 * @param mixed $data
	 * @param int $ttl
	 */
	protected function _cacheWrite($id, $data, $ttl = null) {
		if ($this->_cache instanceof Zend_Cache_Core) {
			if (null === $ttl) {
				$ttl = $this->_config->cacheTimeout;
			}

			$this->_cache->save($data, $id, array(), $ttl);
		}
	}

	/**
	 * Read some data from our cache, if present
	 *
	 * @param string $id
	 * @param mixed  $default [Optional] default response if cache not loaded or found
	 *
	 * @return mixed
	 */
	protected function _cacheLoad($id, $default = false) {
		if (($this->_cache instanceof Zend_Cache_Core) && ($result = $this->_cache->load($id))) {
			return $result;
		}
		return $default;
	}

	/**
	 * Remove some data from our cache
	 *
	 * @param string $id
	 */
	protected function _cacheRemove($id) {
		if ($this->_cache instanceof Zend_Cache_Core) {
			$this->_cache->remove($id);
		}
	}
}
