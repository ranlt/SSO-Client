<?php
/**
 * Cwcl Services Gateway with Curl as the driver
 *
 * @package Cwcl Services 
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Gateway_Curl extends Cwcl_Services_Gateway_Abstract
{
	/**
	 * Curl resource
	 *
	 * @var Resource
	 */
	protected $_curl;

	/**
	 * Headers
	 *
	 * @var array
	 */
	protected $_headers = array();
	
	/**
	 * Multipart encoded (true) or urlencoded string (false)
	 *
	 * @var boolean
	 */
	protected $_multipart = false;

	/**
	 * Construct this gateway and perform preflight checks
	 *
	 * @param Cwcl_Services_Request $request [Optional]
	 * @access public
	 * @throws Cwcl_Services_Gateway_Exception
	 */
	public function __construct(Cwcl_Services_Request $request = NULL)
	{
		// Check for curl
		if ( ! extension_loaded('curl')) {
			throw new Cwcl_Services_Gateway_Exception(__CLASS__.' requires the Curl extension!');
		}

		// Run the parent constructor
		parent::__construct($request);
	}

	/**
	 * Prevent this object from being cloned
	 *
	 * @return void
	 * @access public
	 * @throws Cwcl_Services_Gateway_Exception
	 */
	public function __clone()
	{
		throw new Cwcl_Services_Gateway_Exception(__CLASS__.' cannot be cloned!');
	}


	/**
	 * Initialises a new curl connection.
	 *
	 * @return void
	 * @access public
	 */
	public function __initCurl()
	{
		$this->_curl = curl_init();
	}

	/**
	 * Closes the curl connection. Should be
	 * called by object destruction and
	 * serialisation
	 *
	 * @return void
	 * @access public
	 */
	public function __deinitCurl()
	{
		$this->_headers = array();

		if (is_resource($this->_curl)) {
			curl_close($this->_curl);
		}
	}

	/**
	 * Execute a request and return the
	 * resulting object
	 *
	 * @param Cwcl_Services_Request $request 
	 * @access public
	 * @return self
	 */
	public function exec()
	{
		// If this has been executed
		if ($this->isExecuted()) {
			return $this;
		}

		// Try to build Curl
		if ( ! $this->setupCurl()) {
			return $this;
		}

		// Execute the request
		if (FALSE === ($content = curl_exec($this->_curl))) {
			$err = curl_error($this->_curl);
			throw new Cwcl_Services_Gateway_Exception(__METHOD__.' failed to execute properly' . $err);
		}

		// Set the execution setting to true
		$this->_executed = TRUE;

		// Get the curl info
		$curlinfo = curl_getinfo($this->_curl);
		// Create a response object
		$this->_response = new Cwcl_Services_Response(array(
			'httpCode' => $curlinfo['http_code'],
			'headers'  => $this->_headers,
			'request'  => $this->_request,
			'content'  => $content
		));

		$this->__deinitCurl();

		return $this;
	}

	/**
	 * Serialises the object when serialize() is
	 * invoked
	 * 
	 * [SPL Serializable]
	 *
	 * @param array $toSerialize 
	 * @return void
	 * @author Sam de Freyssinet
	 */
	public function serialize(array $toSerialize = array())
	{
		// Close curl
		$this->__deinitCurl();

		// Add the headers property to the serialisation array
		$toSerialize += array('_headers' => $this->_headers);

		// Serialize this object
		return parent::serialize($toSerialize);
	}

	/**
	 * Called when unserialize() is invoked on
	 * a serialised curl object
	 *
	 * @param string $serialized 
	 * @return void
	 * @access public
	 */
	public function unserialize($serialized)
	{
		// Unserialise the this model
		parent::unserialize($serialized);

		// Re-initialise the Curl object
		$this->__initCurl();
	}

	/**
	 * Reset the gateway for another
	 * request
	 *
	 * @return void
	 * @access public
	 */
	public function reset()
	{
		$this->__deinitCurl();
		$this->__initCurl();
		$this->_request = NULL;
		$this->_response = NULL;
		$this->_headers = array();
		$this->_executed = FALSE;
	}

	/**
	 * Setup the Curl Resource ready for
	 * execution
	 *
	 * @return boolean
	 * @access protected
	 * @throws Cwcl_Services_Gateway_Exception
	 */
	protected function setupCurl()
	{
		if (NULL === $this->_request) {
			return FALSE;
		}

		$this->__initCurl();

		// Create a curl opts array
		$curlOptions = array(
			CURLOPT_URL => $this->_request->getUrl(),
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_HEADER => FALSE,
			CURLOPT_HEADERFUNCTION => array($this, 'parseHeader'),
		);

		// Get the POST params
		$postParams = $this->_request->getPostParams();

		// create payload data structure and serialise for transfer
		if ( ! is_array($postParams) or ! $postParams instanceof stdClass) {
			$postParams = array('payload' => serialize($postParams));
		}

		// Tidy up handling of files
		$postParams = $this->_tidyPostParams($postParams);

		$dump = '';

		switch ($this->_request->getMethod()) {
			case Cwcl_Services_Request::GET: {
				break;
			}
			case Cwcl_Services_Request::POST: {
				$curlOptions[CURLOPT_POST] = TRUE;
				$curlOptions[CURLOPT_POSTFIELDS] = $postParams;
				break;
			}
			case Cwcl_Services_Request::PUT: {

				$postParams = is_array($postParams) ? http_build_query($postParams) : $postParams;
				$curlOptions[CURLOPT_HTTPHEADER] = array('Content-Length: '.strlen($postParams));
				$curlOptions[CURLOPT_CUSTOMREQUEST] = Cwcl_Services_Request::PUT;
				$curlOptions[CURLOPT_POSTFIELDS] = $postParams;
				break;
			}
			case Cwcl_Services_Request::DELETE: {
				$postParams = is_array($postParams) ? http_build_query($postParams) : $postParams;
				$curlOptions[CURLOPT_HTTPHEADER] = array('Content-Length: '.strlen($postParams));
				$curlOptions[CURLOPT_CUSTOMREQUEST] = Cwcl_Services_Request::DELETE;
				break;
			}
			default: {
				throw new Cwcl_Services_Gateway_Exception(__METHOD__.' Unknown HTTP method: ' . $method);
			}
		}

		// If there have been no cookies set, get out of here
		if ( ! $cookies = $this->_request->getCookies()) {
			curl_setopt_array($this->_curl, $curlOptions);
			return TRUE;
		}

		// Use HTTP fast method if exists
		if (function_exists('http_build_cookie')) {
			$cookieString = http_build_cookie($cookies);
		}
		else {
			// Create a jar
			$jar = array();

			// Process the set cookies
			foreach ($cookies as $key => $value) {
				$jar[] = $key.'='.$value;
			}

			// Format the cookie string
			$cookieString = implode(' ;', $jar);
		}

		// Set the cookie to the CURL request
		$curlOptions[CURLOPT_COOKIE] = $cookieString;
		curl_setopt_array($this->_curl, $curlOptions);

		return TRUE;
	}

	/**
	 * Parses the header line by line
	 *
	 * @param Resource $ch 
	 * @param string $header 
	 * @return int
	 * @access protected
	 */
	protected function parseHeader($ch, $header)
	{
		$result = array();

		if (preg_match_all('/(\w[^\s:]*):[ ]*([^\r\n]*(?:\r\n[ \t][^\r\n]*)*)/', $header, $matches))
		{
			foreach ($matches[0] as $key => $value)
				$result[$matches[1][$key]] = $matches[2][$key];
		}

		if ($result)
			$this->_headers += $result;

		return strlen($header);
	}
	
	/**
	 * Set multipart to true/false
	 *
	 * @param boolean $value true to submit multipart encoded POST data,
	 *        false/default so submit URL encoded string data
	 * 
	 * @return Cwcl_Services_Gateway_Curl
	 * @author Johanna Cherry <johanna@ibuildings.com>
	 **/
	public function setMultipart($value)
	{
		$this->_multipart = (bool) $value;
		return $this;
	}
	
	/**
	 * Get multipart value
	 *
	 * @return boolean
	 * @author Johanna Cherry <johanna@ibuildings.com>
	 **/
	public function getMultipart()
	{
		return (bool)$this->_multipart;
	}

	/**
	 * Looks for any file references for CURL and removes them from the
	 * payload and adds them as a separate parameter
	 *
	 * @param array $postParams 
	 * @return void
	 */
	protected function _tidyPostParams(array $postParams)
	{
		$serialised = FALSE;

		if ( ! isset($postParams['payload'])) {
			return $postParams;
		}

		if (is_string($postParams['payload'])) {
			$serialised = TRUE;
			$postParams['payload'] = unserialize($postParams['payload']);
		}

		foreach ($postParams['payload'] as $key => $value) {
			
			if ( ! preg_match('/@+(\/.*)/', $value)) {
				continue;
			}
			else {
				// Create the revised payload and tidy up
				$postParams[$key] = $value;
				unset($postParams['payload'][$key]);
			}
		}

		if ($serialised) {
			$postParams['payload'] = serialize($postParams['payload']);
		}

		return $postParams;
	}
}
