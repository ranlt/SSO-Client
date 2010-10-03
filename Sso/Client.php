<?php
if (!class_exists('Sso_Client', false)) {

class Sso_Client extends Sso_Client_Abstract
{
	/**
	 * The Sso token
	 *
	 * @var string
	 */
	protected $_token;

	/**
	 * Process the resultant body text
	 *
	 * @param string $body
	 * @return string|StdObject|array
	 */
	protected function processBody($body, $request)
	{
            
		$this->_result = json_decode($body, true);
		//var_dump($body);
		if (!is_array($this->_result) || !isset($this->_result['contentType'])) {
			$e = new Sso_Client_Exception('Invalid data returned from request');
			$e->setDebugDataValue('response body', $body);
			$e->setDebugDataValue('url', $this->getUrl());
			$e->setDebugDataValue('request', $request);
			throw $e;
		}
		// can't be sure of the returned data format, ie for a delete it will be simple array(true);
		if (isset($this->_result['data'])) {
			$data = $this->_result['data'];
		} else {
			$data = $this->_result;
		}

		if (!array_key_exists(0, $data) && !empty($data)) {
			// for multi-row results, the json format is already an array
			$data = array($data);
		}

		$this->_result['data'] = $data;

		// how does the client code want the data back?
		switch ($request->getResultType()) {
			case Sso_Request::RESULT_STRING: {
				return $body;
			}
			case Sso_Request::RESULT_OBJECT: {
				// check for ORM mapping
				return new Sso_Model_Iterator($this->_result['contentType'], $data);
			}
			case Sso_Request::RESULT_ASSOC:
			default: {
				return $this->_result;
			}
		}
	}

	/**
	 * Overloads the buildCurl method to add
	 * the token to the request object
	 *
	 * @param Sso_Request $request
	 * @return Sso_Client
	 */
	protected function _buildCurl($request)
	{
		// If there is a token available
		if (($token = $this->getToken()) !== NULL) {
			// Set it to the request
			$request->setToken($token);
		}

		// Run the parent
		return parent::_buildCurl($request);
	}

	/**
	 * Returns the cache id for the token
	 *
	 * @return string
	 */
	protected function _getTokenCacheId()
	{
		return $this->_config->cacheIdPrefix . $this->getToken();
	}

	/**
	 * Authenticate, returning a token
	 *
	 * @param  string $username
	 * @param  string $password
	 *
	 * @return Model_Token
	 * @throws Sso_Client_Exception_Unauthorised
	 */
	public function authenticate($username, $password)
	{
		// throws Sso_Client_Exception_Unauthorised on auth failure
		$request = new Sso_Request(array(
			'path'          => $this->_config->url . 'token',
			'method'        => Sso_Request::POST,
			'postParams'    => array('username' => $username, 'password' => $password),
			'resultType'    => Sso_Request::RESULT_ASSOC,
			'allowedErrors' => array(400, 401, 404),
		));
		$response = $this->run($request)->getResponse();
		$this->setToken($response['data'][0]['token']);

		$token = new Sso_Model_Token();

		$token->setHash($response['data'][0]['token']);
		$token->setUsername($response['data'][0]['username']);
		$token->setAccessTime($response['data'][0]['accessTime']);

		// cache the token
		$this->_cacheWrite($this->_getTokenCacheId(), $response['data'][0]);

		return $token;
	}

	/**
	 * Delete a token (log out a user)
	 *
	 * @param  string|Model_Token $token
	 * @return Sso_Client
	 */
	public function deleteToken($token = NULL)
	{
		if ($token !== NULL) {
			$this->setToken($token);
		}

		$this->run(Sso_Request::factory(array(
			'urlParams' => array('token', $this->getToken()),
			'method' => Sso_Request::DELETE,
			'resultType' => Sso_Request::RESULT_ASSOC
		)));

		// clear the token
		$this->_cacheRemove($this->_getTokenCacheId());

		return $this;
	}

	/**
	 * Check existing token for validity
	 * - if no longer logged in, will thrown Sso_Exception_Unauthorised
	 *
	 * @param string $token
	 * @return Sso_Client
	 */
	public function checkToken($token = NULL)
	{
		if ($token !== NULL) {
			$this->setToken($token);
		}

		if ( ! $token = $this->_cacheLoad($this->_getTokenCacheId())) {
			// will throw unauthorised if no longer logged in
			$response = $this->run(Sso_Request::factory(array(
				'urlParams' => array('token', $this->getToken()),
				'method' => Sso_Request::GET,
				'resultType' => Sso_Request::RESULT_ASSOC
			)))->getResponse();

			return $response['data'][0]['token'];
		}

		return $token['token'];
	}
	
	/**
	 * Get the data associated with a token
	 *
	 * @param string $token
	 * @return array
	 */
	public function getTokenData($token = NULL) 
	{
		if ($token !== NULL) {
			$this->setToken($token);
		}

		if ( ! $token = $this->_cacheLoad($this->_getTokenCacheId())) {
			// will throw unauthorised if no longer logged in
			$response = $this->run(Sso_Request::factory(array(
				'urlParams' => array('token', $this->getToken()),
				'method' => Sso_Request::GET,
				'resultType' => Sso_Request::RESULT_ASSOC
			)))->getResponse();

			return $response['data'][0];
		}

		return $token;
	}

	/**
	 * Set the token
	 *
	 * @param string|Model_Token $token
	 * @return self
	 */
	public function setToken($token)
	{
		if ($token instanceof Model_Token) {
			$token = $token->getHash();
		}

		$this->_token = $token;
		return $this;
	}

	/**
	 * Get the token
	 *
	 * @return string
	 */
	public function getToken()
	{
		return $this->_token;
	}

	/**
	 * Remove the current token
	 *
	 * @return self
	 */
	public function removeToken()
	{
		return $this->_token = NULL;
	}

	/**
	 * Get a response related to a request
	 *
	 * @param string $id
	 *
	 * @return mixed
	 */
	public function getResponse($id = 'single') {
		if (array_key_exists($id, $this->_responses)) {
			$response = $this->_responses[$id];

			if (!($response instanceof Sso_Error)) {
				return $response;
			}

			$message = $response->getMessage();
			$code = $response->getCode();
			$httpCode = substr($code, 3, 3);

			switch ($code) {
				case 'SSO400':
					throw new Sso_Client_Exception_BadRequest($message, $httpCode);
				case 'SSO401':
					throw new Sso_Client_Exception_Unauthorised($message, $httpCode);
				case 'SSO404':
					throw new Sso_Client_Exception_NotFound($message, $httpCode);
				case 'SSO405':
					throw new Sso_Client_Exception_MethodNotAllowed($message, $httpCode);
				case 'SSO409':
					throw new Sso_Client_Exception_AlreadyExists($message, $httpCode);
				default: {
					throw new Sso_Client_Exception($message, $httpCode);
				}
			}
		}

		throw new Sso_Client_Exception('Invalid Sso_Client response: id=' . $id);
	}
}

}