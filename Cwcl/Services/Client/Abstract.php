<?php
/**
 * Abstract Services Client for communicating with
 * external services.
 * 
 * [requires] php curl
 *
 * @package Services
 * @abstract
 */
abstract class Cwcl_Services_Client_Abstract
{
	/**
	 * Store of instances
	 *
	 * @var array
	 */
	static protected $_instances = array();

	/**
	 * Generate a new instance of the Cwcl_Services_Client
	 *
	 * @param string $type 
	 * @param array $dependencies [Optional]
	 * @return Cwcl_Services_Client_Abstracts
	 * @access public
	 * @static
	 */
	static public function getInstance($type, array $dependencies = array())
	{
		$dependenciesId = self::generateDependencyId($dependencies);
		if ( ! isset(self::$_instances[$type][$dependenciesId])) {
			self::$_instances[$type][$dependenciesId] = new $type($dependencies);
		}

		return self::$_instances[$type][$dependenciesId];
	}

	/**
	 * Generates a dependency Id without serialisation
	 *
	 * @param array $dependencies 
	 * @return string
	 * @access protected
	 * @static
	 */
	static protected function generateDependencyId(array $dependencies)
	{
		$result = array();

		foreach ($dependencies as $key => $value) {
			if ( ! is_scalar($value)) {
				if (is_object($value)) {
					$value = spl_object_hash($value);
				}
				elseif (is_resource($value)) {
					$value = get_resource_type($value);
				}
				if (is_array($value)) {
					$value = implode(',', $value);
				}
			}
			$result[] = $value;
		}

		return sha1(implode('|', $result));
	}

	/**
	 * Loggers available for logging events
	 *
	 * @var Zend_Log
	 */
	protected $_logWriters;

	/**
	 * Cache available for caching requests
	 *
	 * @var Zend_Cache
	 */
	protected $_cache;

	/**
	 * The base resource identifier for this client
	 *
	 * @var string
	 */
	protected $_uri;

	/**
	 * The gateway the client will use
	 *
	 * @var Cwcl_Services_Gateway_Abstract
	 */
	protected $_gateway;

	/**
	 * Constructs the services client, not available publically
	 *
	 * @param array $dependencies 
	 * @access protected
	 * @throws Cwcl_Services_Client_Exception
	 */
	protected function __construct(array $dependencies)
	{
		foreach ($dependencies as $key => $value) {
			$method = 'set'.ucfirst($key);
			if ( ! method_exists($this, $method)) {
				throw new Cwcl_Services_Client_Exception(__METHOD__.' could not invoke method : '.$method);
			}

			$this->$method($value);
		}
	}

	/**
	 * Set the URI for this client
	 *
	 * @param string $uri 
	 * @return self
	 * @access public
	 */
	public function setUri($uri)
	{
		$this->_uri = (string) $uri;
		return $this;
	}

	/**
	 * Get the URI for this client
	 *
	 * @return string
	 * @access public
	 */
	public function getUri()
	{
		return $this->_uri;
	}

	/**
	 * Add a new log writer to the client
	 *
	 * @param Zend_Log_Writer_Abstract $logWriter 
	 * @return self
	 * @access public
	 */
	public function setLogWriter(Zend_Log_Writer_Abstract $logWriter)
	{
		if ( ! $this->_logWriters instanceof Zend_Log) {
			$this->_logWriters = new Zend_Log($logWriter);
		}
		else {
			$this->_logWriters->addWriter($logWriter);
		}
		return $this;
	}

	/**
	 * Return the log writer client
	 *
	 * @return Zend_Log
	 * @access public
	 */
	public function getLogWriter()
	{
		return $this->_logWriters;
	}

	/**
	 * Set a Zend_Cache backend object to the client
	 *
	 * @param Zend_Cache $cache 
	 * @return self
	 * @access public
	 */
	public function setCache(Zend_Cache $cache)
	{
		$this->_cache = $cache;
		return $this;
	}

	/**
	 * Get the current Zend_Cache object
	 *
	 * @return Zend_Cache|void
	 * @access public
	 */
	public function getCache()
	{
		if (!property_exists($this, '_cache')) {
			return null;
		}
		
		return $this->_cache;
	}

	/**
	 * Remove the Zend_Cache class
	 *
	 * @return self
	 * @access public
	 */
	public function removeCache()
	{
		unset($this->_cache);
		return $this;
	}

	/**
	 * Set a new gateway to the client
	 *
	 * @param Cwcl_Services_Gateway_Abstract $gateway 
	 * @return self
	 * @access public
	 */
	public function setGateway(Cwcl_Services_Gateway_Abstract $gateway)
	{
		$this->_gateway = $gateway;
		return $this;
	}

	/**
	 * Return the current gateway
	 *
	 * @return Cwcl_Services_Gateway_Abstract|void
	 * @access public
	 */
	public function getGateway()
	{
		return $this->_gateway;
	}

	/**
	 * Returns the client name
	 *
	 * @return string
	 * @access public
	 */
	public function getClientName()
	{
		return get_class($this);
	}

	/**
	 * Run a Cwcl_Services_Request using the Gateway
	 *
	 * @param Cwcl_Services_Request $request 
	 * @return Cwcl_Services_Response
	 * @access public
	 * @throws Cwcl_Services_Client_Exception
	 */
	public function run(Cwcl_Services_Request $request)
	{
		$gateway = $this->getGateway();
		
		if (NULL === $gateway or ! $gateway instanceof Cwcl_Services_Gateway_Abstract) {
			throw new Cwcl_Services_Client_Exception(__METHOD__.' client has no gateway available!');
		}

		// If the gateway has already been executed, reset it
		if (TRUE === $gateway->isExecuted()) {
			$gateway->reset();
		}

		// Add the client URI to the request
		$request->setPath($this->getUri());

		// Set the request to the gateway
		$gateway->setRequest($request);

		// Execute request
		try {
			$response = $gateway->exec()->getResponse();

			// If there was an HTTP problem throw an exception
			if ( ! in_array($response->getHttpcode(), array(200, 201, 202))) {
				if ($data = json_decode($response->getContent())) {
					$type = (isset($data->errorType)) ? $data->errorType : 'Cwcl_Exception_Service_ErrorResponse';
					$message = (isset($data->errorMessage)) ?  $data->errorMessage : NULL;
					$code = (isset($data->errorCode)) ? preg_replace('/[^\d]/', '', $data->errorCode) : NULL;
					throw new $type($message, $code);
				} else {
					throw new Cwcl_Services_Client_Exception($response->getHttpcode(), $response->getContent());
				}
			}
		}
		catch (Cwcl_Services_Gateway_Exception $e)
		{
			throw new Cwcl_Services_Client_Exception(__METHOD__.' Gateway failed to execute request with message : '.$e->getMessage());
		}

		// Return the response
		return $response;
	}

	/**
	 * Get the current service description
	 *
	 * @param string $api
	 * @return array
	 * @access public
	 * @abstract
	 */
	abstract public function getServiceDescription($api);
}
