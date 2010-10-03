<?php

require_once 'TestHelper.php';
require_once('PHPUnit/Framework.php');

class Test_Request extends PHPUnit_Framework_TestCase {
	
	public $fixtures = array(
		'baseUrl' => 'http://example.com',
		'params' => array('var1' => 'val1', 'var2' => 'val2'),
		'allowedErrors' => array(400, 418),
		'fullRequest' => array(
			'postParams' => array('username' => 'admin@myorg'),
			'getParams' => array('limit' => 5, 'offset' => 10),
			'urlParams' => array('user', 'test'),
			'cookies' => array('token' => 'something'),
			'method' => Sso_Request::POST
		),
		'fullRequestString' => 'POST /user/test?limit=5&offset=10 -d username=admin%40myorg -b token=something'
	);

	public function setup()
	{
	}
	
	public function tearDown()
	{
	}
	
	public function testSetPath()
	{
		$request = new Sso_Request();
		$request->setPath('/path');
		
		$this->assertEquals('/path', $request->getPath());
	}
	
	public function testSetPathTrailingSlash()
	{
		$request = new Sso_Request();
		$request->setPath('/path/');
		
		$this->assertEquals('/path', $request->getPath());
	}
	
	public function testZendUriHttp()
	{
		// sanity checking, was getting // at beginning of path
		$uri = Zend_Uri_Http::fromString('http://example.com/');
		$uri->setPath('/token');
		
		$this->assertEquals('http://example.com/token', $uri->getUri());
	}
	
	public function testGetUrlBasic1()
	{
		$request = new Sso_Request(array('path' => '/token'));
		
		$this->assertEquals('http://example.com/token', $request->getUrl('http://example.com/'));
	}
	
	public function testGetUrlBasic2()
	{
		$request = new Sso_Request(array('path' => '/token/something'));
		
		$this->assertEquals('http://example.com/token/something', $request->getUrl('http://example.com/'));
	}
	
	public function testGetUrlBasicTrailingSlashInPath()
	{
		$request = new Sso_Request(array('path' => '/token/'));
		
		$this->assertEquals('http://example.com/token', $request->getUrl('http://example.com/'));
	}
	
	public function testGetUrlWithGetParams1()
	{
		$request = new Sso_Request(array('path' => '/user', 'getParams' => array('limit' => 10)));
		
		$this->assertEquals('http://example.com/user?limit=10', $request->getUrl('http://example.com/'));
	}
	
	public function testGetUrlWithGetParams2()
	{
		$request = new Sso_Request(array('path' => '/user', 'getParams' => array('limit' => 10, 'offset' => 0)));
		
		$this->assertEquals('http://example.com/user?limit=10&offset=0', $request->getUrl('http://example.com/'));
	}
	
	public function testSetPostParamsDirect()
	{
		$request = new Sso_Request();
		$request->setPostParams($this->fixtures['params']);
		$params = $request->getPostParams();
		
		$this->assertType('array', $params);
		$this->assertEquals(count($this->fixtures['params']), count($params));
		$this->assertEquals($this->fixtures['params']['var1'], $params['var1']);
	}

	public function testSetPostParamsDirectConstructor()
	{
		$request = new Sso_Request(array('postParams' => $this->fixtures['params']));
		$params = $request->getPostParams();
		
		$this->assertType('array', $params);
		$this->assertEquals(count($this->fixtures['params']), count($params));
		$this->assertEquals($this->fixtures['params']['var1'], $params['var1']);
	}
	
	public function testGetPostParam()
	{
		$request = new Sso_Request();
		$request->addPostParam('var1', $this->fixtures['params']['var1']);
				
		$this->assertEquals($this->fixtures['params']['var1'], $request->getPostParam('var1'));
	}
	
	public function testGetPostParamEmpty()
	{
		$request = new Sso_Request();
		
		$this->assertEquals(null, $request->getPostParam('var1'));
	}
	
	public function testAddPostParam()
	{
		$request = new Sso_Request();
		$request->addPostParam('var1', $this->fixtures['params']['var1']);
		
		$this->assertEquals($this->fixtures['params']['var1'], $request->getPostParam('var1'));

		$request->addPostParam('var2', $this->fixtures['params']['var2']);
		
		$this->assertEquals($this->fixtures['params']['var1'], $request->getPostParam('var1'));
		$this->assertEquals($this->fixtures['params']['var2'], $request->getPostParam('var2'));
	}
	
	public function testHasPostParams()
	{
		$request = new Sso_Request();
		$this->assertFalse($request->hasPostParams());
		
		$request->addPostParam('var1', $this->fixtures['params']['var1']);
		
		$this->assertTrue($request->hasPostParams());
	}

	public function testRemovePostParam()
	{
		$request = new Sso_Request();
		$request->addPostParam('var1', $this->fixtures['params']['var1']);
		$request->removePostParam('var1');
		
		$this->assertEquals(null, $request->getPostParam('var1'));
	}

	public function testSetGetParamsDirect()
	{
		$request = new Sso_Request();
		$request->setGetParams($this->fixtures['params']);
		$params = $request->getGetParams();
		
		$this->assertType('array', $params);
		$this->assertEquals(count($this->fixtures['params']), count($params));
		$this->assertEquals($this->fixtures['params']['var1'], $params['var1']);
	}

	public function testSetGetParamsDirectConstructor()
	{
		$request = new Sso_Request(array('getParams' => $this->fixtures['params']));
		$params = $request->getGetParams();
		
		$this->assertType('array', $params);
		$this->assertEquals(count($this->fixtures['params']), count($params));
		$this->assertEquals($this->fixtures['params']['var1'], $params['var1']);
	}
	
	public function testGetGetParam()
	{
		$request = new Sso_Request();
		$request->addGetParam('var1', $this->fixtures['params']['var1']);
				
		$this->assertEquals($this->fixtures['params']['var1'], $request->getGetParam('var1'));
	}
	
	public function testGetGetParamEmpty()
	{
		$request = new Sso_Request();
		
		$this->assertEquals(null, $request->getGetParam('var1'));
	}
	
	public function testAddGetParam()
	{
		$request = new Sso_Request();
		$request->addGetParam('var1', $this->fixtures['params']['var1']);
		
		$this->assertEquals($this->fixtures['params']['var1'], $request->getGetParam('var1'));

		$request->addGetParam('var2', $this->fixtures['params']['var2']);
		
		$this->assertEquals($this->fixtures['params']['var1'], $request->getGetParam('var1'));
		$this->assertEquals($this->fixtures['params']['var2'], $request->getGetParam('var2'));
	}

	public function testRemoveGetParam()
	{
		$request = new Sso_Request();
		$request->addGetParam('var1', $this->fixtures['params']['var1']);
		$request->removeGetParam('var1');
		
		$this->assertEquals(null, $request->getGetParam('var1'));
	}
	
	public function testHasGetParams()
	{
		$request = new Sso_Request();
		$this->assertFalse($request->hasGetParams());
		
		$request->addGetParam('var1', $this->fixtures['params']['var1']);
		
		$this->assertTrue($request->hasGetParams());
	}
	
	public function testSetUrlParamsDirect()
	{
		$request = new Sso_Request();
		$request->setUrlParams(array_values($this->fixtures['params']));
		$params = $request->getUrlParams();
		
		$this->assertType('array', $params);
		$this->assertEquals(count($this->fixtures['params']), count($params));
		$this->assertEquals($this->fixtures['params']['var1'], $params[0]);
	}

	public function testSetUrlParamsDirectConstructor()
	{
		$request = new Sso_Request(array('urlParams' => array_values($this->fixtures['params'])));
		$params = $request->getUrlParams();
		
		$this->assertType('array', $params);
		$this->assertEquals(count($this->fixtures['params']), count($params));
		$this->assertEquals($this->fixtures['params']['var1'], $params[0]);
	}
	
	public function testAddUrlParam()
	{
		$request = new Sso_Request();
		$request->addUrlParam($this->fixtures['params']['var1']);
		
		$params = $request->getUrlParams();
		$this->assertEquals($this->fixtures['params']['var1'], $params[0]);
	}
	
	public function testSetCookiesConstructor()
	{
		$request = new Sso_Request(array('cookies' => $this->fixtures['params']));
		
		$cookies = $request->getCookies();
		$this->assertType('array', $cookies);
		$this->assertEquals(count($this->fixtures['params']), count($cookies));
		$this->assertEquals($this->fixtures['params']['var1'], $cookies['var1']);
	}
	
	public function testSetCookiesDirect()
	{
		$request = new Sso_Request();
		$request->setCookies($this->fixtures['params']);
		
		$cookies = $request->getCookies();
		$this->assertEquals($this->fixtures['params']['var1'], $cookies['var1']);
	}
	
	public function testSetCookie()
	{
		$request = new Sso_Request();
		$request->setCookie('var1', $this->fixtures['params']['var1']);
		
		$this->assertEquals($this->fixtures['params']['var1'], $request->getCookie('var1'));

		$request->setCookie('var2', $this->fixtures['params']['var2']);
		
		$this->assertEquals($this->fixtures['params']['var1'], $request->getCookie('var1'));
		$this->assertEquals($this->fixtures['params']['var2'], $request->getCookie('var2'));
	}
	
	public function testRemoveCookie()
	{
		$request = new Sso_Request();
		$request->setCookies($this->fixtures['params']);
		$request->removeCookie('var1');
		$cookies = $request->getCookies();
		$this->assertFalse(isset($cookies['var1']));
	}

	public function testHasCookies()
	{
		$request = new Sso_Request();
		
		$this->assertFalse($request->hasCookies());
		
		$request->setCookies($this->fixtures['params']);
		
		$this->assertTrue($request->hasCookies());
	}
	
	public function testSetAllowedErrorsConstructor()
	{
		$request = new Sso_Request(array('allowedErrors' => $this->fixtures['allowedErrors']));
		
		$errors = $request->getAllowedErrors();
		$this->assertType('array', $errors);
		
		$this->assertEquals(count($this->fixtures['allowedErrors']), count($errors));
		$this->assertEquals($this->fixtures['allowedErrors'][0], $errors[0]);
	}
	
	public function testSetAllowedErrorsDirect()
	{
		$request = new Sso_Request();
		$request->setAllowedErrors($this->fixtures['allowedErrors']);
		
		$errors = $request->getAllowedErrors();
		$this->assertType('array', $errors);
		$this->assertEquals($this->fixtures['allowedErrors'][0], $errors[0]);
	}
	
	public function testToString()
	{
		$request = new Sso_Request($this->fixtures['fullRequest']);
		
		$this->assertEquals($this->fixtures['fullRequestString'], (string)$request);
	}
	
	public function testSetTokenStringConstruct()
	{
		$request = new Sso_Request(array('token' => 'something'));
		
		$token = $request->getToken();
		
		$this->assertEquals('something', $token);
	}
}

