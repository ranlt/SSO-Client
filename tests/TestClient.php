<?php 

class TestClient extends Sso_Client
{
	// no error
	const SIMULATED_ERROR_NONE					= 0;
	// sso service host unreachable
	const SIMULATED_ERROR_HOST_UNREACHABLE		= 1;
	// uncaught error
	const SIMULATED_ERROR_UNKNOWN_ERROR			= 2;
	// invalid json, or non-json returned from service
	const SIMULATED_ERROR_INVALID_DATA			= 3;
	// malformed request, normally means missing params
	const SIMULATED_ERROR_BAD_REQUEST			= 4;
	// not logged in
	const SIMULATED_ERROR_UNAUTHORISED			= 5;
	// entity not found
	const SIMULATED_ERROR_NOT_FOUND				= 6;
	// method (eg PUT) not supported on this entity
	const SIMULATED_ERROR_METHOD_NOT_ALLOWED	= 7;
	// entity already exisst, cannot create new
	const SIMULATED_ERROR_ALREADY_EXISTS		= 8;
	// invalid request id
	const SIMULATED_ERROR_INVALID_REQUEST_ID	= 9;
	// no client url specified
	const SIMULATED_ERROR_NO_URL_SPECIFIED		= 10;
	// invalid client config specified
	const SIMULATED_ERROR_INVALID_CLIENT_CONFIG	= 11;
	// unknown http method
	const SIMULATED_ERROR_UNKNOWN_HTTP_METHOD	= 12;
	
	protected $simulatedError = 0;
	
	public function setSimulatedError($error)
	{
		$this->simulatedError = $error;
	}
	
	protected function processBody($body, $request)
	{
		if ($this->simulatedError == self::SIMULATED_ERROR_INVALID_DATA) {
			throw new Sso_Client_Exception('Invalid data returned from request');
		}
		
		return parent::processBody($body, $request);
	}
	
	public function getResponse($id = 'single') {
		switch ($this->simulatedError) {
			case self::SIMULATED_ERROR_BAD_REQUEST:
				throw new Sso_Client_Exception_BadRequest('Bad request', 400);
			case self::SIMULATED_ERROR_UNAUTHORISED:
				throw new Sso_Client_Exception_Unauthorised('Unauthorised', 401);
			case self::SIMULATED_ERROR_NOT_FOUND:
				throw new Sso_Client_Exception_NotFound('Entity not found', 404);
			case self::SIMULATED_ERROR_METHOD_NOT_ALLOWED:
				throw new Sso_Client_Exception_MethodNotAllowed('Method not allowed', 405);
			case self::SIMULATED_ERROR_ALREADY_EXISTS:
				throw new Sso_Client_Exception_AlreadyExists('Entity already exists', 409);
			case self::SIMULATED_ERROR_UNKNOWN_ERROR:
				throw new Sso_Client_Exception('Unknown error', 500);
			case self::SIMULATED_ERROR_INVALID_REQUEST_ID:
				throw new Sso_Client_Exception('Invalid Sso_Client response: id=wrong');
			case self::SIMULATED_ERROR_HOST_UNREACHABLE:
				throw new Sso_Client_Exception_HostUnreachable('Host unreachable');
		}
		
		return parent::getResponse($id);
	}
	
	public static function factory($config = array(), $client = 'TestClient') {
		return parent::factory($config, $client);
	}
	
	public static function & getInstance($config = array(), $client = 'TestClient') {
		if ($this->simulatedError == self::SIMULATED_ERROR_NO_URL_SPECIFIED) {
			throw new Sso_Client_Exception('No URL supplied in the Sso_Client configuration!');
		}
		
		return parent::getInstance($config, $client);
	}
	
	protected static function _sanitizeConfiguration($config) {
		if ($this->simulatedError == self::SIMULATED_ERROR_INVALID_CLIENT_CONFIG) {
			throw new Sso_Client_Exception('$config argument was not recognised. Must be string, array or Zend_Config!');
		}
		
		return parent::_sanitizeConfiguration($config);
	}
	
	protected function _buildCurl($request) {
		if ($this->simulatedError == self::SIMULATED_ERROR_UNKNOWN_HTTP_METHOD) {
			throw new Sso_Client_Exception('Unknown method');
		}
		
		return parent::_buildCurl($request);
	}
}