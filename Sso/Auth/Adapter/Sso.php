<?php

/**
 * @category   Sso
 * @package    Sso_Auth
 * @subpackage Sso_Auth_Adapter
 */
class Sso_Auth_Adapter_Sso implements Zend_Auth_Adapter_Interface
{
    /**
     * $_identity - Identity value
     *
     * @var string
     */
    protected $_identity = null;

    /**
     * $_credential - Credential values
     *
     * @var string
     */
    protected $_credential = null;

    /**
     * $_result - Results of authentication
     *
     * @var array
     */
    protected $_result = null;

    /**
     * $_logger - Zend_log object for logging
     * 
     * @var Zend_Log
     */
    protected $_logger = null;

    /**
     * __construct() - Sets configuration options
     *
     * @return void
     */
    public function __construct()
    {}

    /**
     * setLogger - Sets the loger to be used
     * @param  Zend_Log $logger
     * @return Sso_Auth_Adapter_Sso
     */
    public function setLogger(Zend_Log $logger)
    {
    	$this->_logger = $logger;
    	return $this;
    }

    /**
     * setIdentity() - set the value to be used as the identity
     *
     * @param  string $value
     * @return Sso_Auth_Adapter_Sso
     */
    public function setIdentity($value)
    {
        $this->_identity = $value;
        return $this;
    }

    /**
     * setCredential() - set the credential value to be used
     *
     * @param  string $credential
     * @return Cw_Auth_Adapter_Sso Provides a fluent interface
     */
    public function setCredential($credential)
    {
        $this->_credential = $credential;
        return $this;
    }
    
    /**
     * Get the user details from the auth result
     *
     * @return StdObject
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * Log an auth message
     * 
     * @param  $message
     * @return Sso_Auth_Adapter_Sso
     */
    protected function _log($message, $level = 'notice')
    {
    	if ($this->_logger instanceof Zend_Log) {
    		$this->_logger->$level($message);
    	}
        return $this;
    }
    
    /**
     * authenticate() - defined by Zend_Auth_Adapter_Interface.  This method is called to
     * attempt an authenication.  Previous to this call, this adapter would have already
     * been configured with all necessary information to successfully connect to a database
     * table and attempt to find a record matching the provided identity.
     *
     * @throws Zend_Auth_Adapter_Exception if answering the authentication query is impossible
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
		if (false !== ($response = $this->_authenticateSetup())) {
			return $response;
		}

		$client = Sso_Client::getInstance();
		
		try {
	        $result = $client->authenticate($this->_identity, $this->_credential);
		} catch (Sso_Client_Exception_HostUnreachable $e) {
			$this->_log('Authentication failed: username=' . $this->_identity . ', exception=' . get_class($e) . ', message=' . $e->getMessage(), 'crit');
			return new Zend_Auth_Result(
				Zend_Auth_Result::FAILURE_UNCATEGORIZED,
				$this->_identity,
				array($e->getMessage())
			);
		} catch (Sso_Client_Exception $e) {
			$this->_log('Authentication failed: username=' . $this->_identity . ', exception=' . get_class($e) . ' (' . $e->getCode() . '), message=' . $e->getMessage());
			switch($e->getCode()) {
				case 401:
					$resultCode = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
					break;
				case 404:
					$resultCode = Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND;
					break;
				default:
					$resultCode = Zend_Auth_Result::FAILURE;
			}
			return new Zend_Auth_Result(
				$resultCode,
				$this->_identity,
				array($e->getMessage())
			);
		}
        // client authenticate throws an exception, will always get here with
        // a valid response
       	// put it in a nice format - saving objects directly was causing them
       	// to magically be unserialised as incomplete obejcts
		$this->_result = new stdClass();
		$this->_result->accessTime = $result->getAccessTime();
		$this->_result->hash = $result->getHash();
		$this->_result->username = $this->_identity;			
		
       	// log it
        $this->_log('Authentication successful: username=' . $this->_identity);

        return new Zend_Auth_Result(
			Zend_Auth_Result::SUCCESS,
			$this->_result,
			array('Authentication successful.')
		);
    }
    
    /**
     * Log out of our current session
     *
     */
    public function logout()
    {
        Sso_Client::getInstance()->deleteToken();
        Zend_Auth::getInstance()->clearIdentity();
    }

    /**
     * _authenticateSetup() - This method abstracts the steps involved with making sure
     * that this adapter was indeed setup properly with all required pieces of information.
     *
     * @return boolean|Zend_Auth_Result
     */
    protected function _authenticateSetup()
    {
        $exception = null;

        if ($this->_identity == '') {
            $exception = 'A value for the identity was not provided prior to authentication with Zend_Auth_Adapter_DbTable.';
        } elseif ($this->_credential === null) {
            $exception = 'A credential value was not provided prior to authentication with Zend_Auth_Adapter_DbTable.';
        } else {
			return false;
		}

        return new Zend_Auth_Result(
            Zend_Auth_Result::FAILURE,
            $this->_identity,
            array($exception)
        );
    }

}
