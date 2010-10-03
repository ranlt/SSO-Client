<?php

require_once 'TestHelper.php';
require_once('PHPUnit/Framework.php');

class Test_Client extends PHPUnit_Framework_TestCase {
	public $client;
	public $config;
	
	public $fixtures = array(
		'stringUri' => 'http://example.com/',
		'options' => array(
			'resultType' => Sso_Request::RESULT_OBJECT,
			'classBase' => 'Sso_Test_'
		),
		'credentials' => array('username' => 'admin@cw', 'password' => 'password'),
		'badCredentials' => array('username' => 'admin@cw', 'password' => 'fail')
		);
	
	public function login($username, $password = null)
	{
		if (is_array($username)) {
			$password = $username['password'];
			$username = $username['username'];
		}

		return $this->client->authenticate($username, $password);
	}
	
	public function logout()
	{
		return $this->client->deleteToken();
	}

	public function setup()
	{
		$this->config = Zend_Registry::get('ssoConfig');
		$this->fixtures['objectUri'] = Zend_Uri_Http::factory($this->config->url);
		$this->fixtures['options']['uri'] = $this->config->url;
		$this->client = Sso_Client::factory(array('url' => $this->config->url));
	}
	
	public function tearDown()
	{
		// logout
		$session = Zend_Registry::get('session');
		unset($session->cookies);
		
		Sso_Client::destroyInstance();
	}

	public function testGetMessageNoRequest()
	{
		$this->assertFalse($this->client->getMessage());
	}

	public function testGetCodeNoRequest()
	{
		$this->assertFalse($this->client->getStatus());
	}
	
	public function testSetUrl() 
	{
		$result = $this->client->setUrl($this->fixtures['stringUri']);
		$uri = $this->client->getUrl();
		$this->assertType('Sso_Client', $result);
		$this->assertEquals($this->fixtures['stringUri'], $uri);
	}
	
	public function testSetToken() 
	{
		$this->client->setToken('test');
		$this->assertEquals('test', $this->client->getToken());
	}

	public function testPostRequest()
	{
		$this->login($this->fixtures['credentials']);

		$result = $this->client->run(new Sso_Request(array(
			'path' => '/organisation',
			'postParams' => array('name' => 'testorg', 'description' => 'testorg'),
			'method' => Sso_Request::POST,
			'resultType' => Sso_Request::RESULT_ASSOC
		)))->getResponse();
		
		$this->logout();
		
		$this->assertType('array', $result);
		$this->assertEquals('testorg', $result['data'][0]['name']);
	}
	
	public function testPutRequest()
	{
		$this->login($this->fixtures['credentials']);
		
		$result = $this->client->run(new Sso_Request(array(
			'urlParams' => array('organisation', 'testorg'),
			'postParams' => array('name' => 'testorg', 'description' => 'changed'),
			'method' => Sso_Request::PUT,
			'resultType' => Sso_Request::RESULT_ASSOC
		)))->getResponse();
		
		$this->logout();
		
		$this->assertType('array', $result);
		$this->assertEquals('changed', $result['data'][0]['description']);
	}
	
	public function testDeleteRequest()
	{
		$this->login($this->fixtures['credentials']);
		$result = $this->client->run(new Sso_Request(array(
			'urlParams' => array('organisation', 'testorg'),
			'method' => Sso_Request::DELETE,
			'resultType' => Sso_Request::RESULT_ASSOC
		)))->getResponse();
		
		$this->logout();

		$this->assertType('array', $result);
		$this->assertEquals('true', $result['data'][0]);
	}
	
	
	public function testAuthenticate() 
	{
		$result = $this->login($this->fixtures['credentials']);
		$this->logout();
		$this->assertType('Sso_Model_Token', $result);
		$this->assertEquals('admin@cw', $result->getUsername());
	}
	
	public function testAuthenticateFail() 
	{
		$exception = null;
		try {
			$this->login($this->fixtures['badCredentials']);
		} catch (Exception $e) {
			$exception = $e;
		}
		$this->assertType('Sso_Client_Exception_Unauthorised', $exception);
		$this->assertEquals('Incorrect username/password combination', $exception->getMessage());
	}
	
	public function testCheckToken()
	{
		$token = $this->login($this->fixtures['credentials']);
		$result = $this->client->checkToken($token->getHash());
		$this->assertEquals($token->getHash(), $result);
		$this->logout();
	}
	
	public function testDeleteToken()
	{
		$token = $this->login($this->fixtures['credentials']);
		$this->logout();
		
		$exception = null;
		try {
			$this->client->checkToken($token->getHash());
		} catch (Exception $e) {
			$exception = $e;
		}
		
		$this->assertType('Sso_Client_Exception_Unauthorised', $exception);
	}

	public function testListUsers()
	{
		$this->login($this->fixtures['credentials']);

		$result = $this->client->run(new Sso_Request(array(
			'path' => '/user',
			'resultType' => Sso_Request::RESULT_ASSOC
		)))->getResponse();
		
		$this->logout();
		
		$this->assertTrue(count($result['data']) > 0);
		$this->assertArrayHasKey('username', $result['data'][0]);
	}
	
	public function testListOrganisations()
	{
		$this->login($this->fixtures['credentials']);

		$result = $this->client->run(new Sso_Request(array(
			'path' => '/organisation',
			'resultType' => Sso_Request::RESULT_ASSOC
		)))->getResponse();
		
		$this->logout();

		$this->assertTrue(count($result['data']) > 0);
		$this->assertArrayHasKey('name', $result['data'][0]);
	}
	
	public function testCurlSingle()
	{
		$this->login($this->fixtures['credentials']);

		$request = new Sso_Request(array(
			'path' => '/organisation',
			'resultType' => Sso_Request::RESULT_ASSOC
		));
		
		$this->client->storeRequest($request, 'test');
		$result = $this->client->run()->getResponse('test');
		
		$this->logout();

		$this->assertTrue(count($result['data']) > 0);
		$this->assertArrayHasKey('name', $result['data'][0]);
	}
	
	public function testCurlSingleStringResult()
	{
		$this->login($this->fixtures['credentials']);

		$request = new Sso_Request(array(
			'path' => '/user/admin@cw',
			'resultType' => Sso_Request::RESULT_STRING
		));
		$this->client->storeRequest($request, 'test');
		$result = json_decode($this->client->run()->getResponse('test'), TRUE);

		$this->assertTrue(count($result['data']) > 0);
		$this->assertArrayHasKey('username', $result['data']);
		$this->assertEquals('admin@cw', $result['data']['username']);
	}
	
	public function testCurlMulti()
	{
		$this->login($this->fixtures['credentials']);

		$request = new Sso_Request(array(
			'path' => '/user/admin@cw',
			'resultType' => Sso_Request::RESULT_ASSOC
		));
		$this->client->storeRequest($request, 'testgetuser');
		$request = new Sso_Request(array(
			'path' => '/organisation/CW',
			'resultType' => Sso_Request::RESULT_ASSOC
		));
		$this->client->storeRequest($request, 'testgetorg');
		
		$this->client->run();
		
		$r1 = $this->client->getResponse('testgetuser');
		$r2 = $this->client->getResponse('testgetorg');
				
		$this->assertTrue(count($r1['data']) > 0);
		$this->assertTrue(count($r2['data']) > 0);
		$this->assertArrayHasKey('username', $r1['data'][0]);
		$this->assertArrayHasKey('name', $r2['data'][0]);
		$this->assertEquals('admin@cw', $r1['data'][0]['username']);
		$this->assertEquals('CW', $r2['data'][0]['name']);
	}
	/*
	 * @todo cached tokens
	 * @todo check other errors, 400, 404, 405, 409, more? 
	 */
}

