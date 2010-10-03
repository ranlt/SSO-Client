<?php


class Cwcl_Services_Request_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var Cwcl_Services_Request
     */
    private $_request;
	
	public function setUp()
	{
		$this->_request = new Cwcl_Services_Request;
	}
	
	public function testSetValues()
	{
		$urlParams = array('param1', 'param2');
		$postParams = array('key' => 'val');
		$cookies = array('cookie');
		$getParams = array('key' => 'val');
		$values = array(
			'path' 		 => 'path',
			'urlParams'  => $urlParams,
			'method' 	 => 'GET',
			'postParams' => $postParams,
			'cookies' 	 => $cookies,
			'getParams'  => $getParams,
			'singleRecord' => true
			
		);
		
		$this->_request->setValues($values);
		
		$this->assertEquals($this->_request->getPath(), 'path');
		$this->assertEquals($this->_request->getUrlParams(), $urlParams);
		$this->assertEquals($this->_request->getMethod(), 'GET');
		$this->assertEquals($this->_request->getPostParams(), $postParams);
		$this->assertEquals($this->_request->getCookies(), $cookies);
		$this->assertEquals($this->_request->getGetParams(), $getParams);
		$this->assertTrue($this->_request->getSingleRecord());
	}
	
	public function testGetUrl()
	{
		$path = 'http://www.foobar.com';
		$params = array('foo', 'bar');
		
		$this->_request->setPath($path);
		$this->_request->setUrlParams($params);
		
		$this->assertEquals('http://www.foobar.com/foo/bar', $this->_request->getUrl());
	}

    public function testGetUrlSlash()
    {
        $path = 'http://www.foobar.com//';
		$params = array('foo', 'bar');

		$this->_request->setPath($path);
		$this->_request->setUrlParams($params);

		$this->assertEquals('http://www.foobar.com/foo/bar', $this->_request->getUrl());
    }
	
	public function testHasPostParams()
	{
		$postParams = array('key' => 'val');
		
		$this->assertFalse($this->_request->hasPostParams());
		
		$this->_request->setPostParams($postParams);
		$this->assertTrue($this->_request->hasPostParams());
	}
	
	public function testGetPostParamVoid()
	{
		$this->assertNull($this->_request->getPostParam('foo'));
	}
	
	public function testGetPostParam()
	{
		$this->_request->addPostParam('foo', 'bar');
		$this->assertEquals('bar', $this->_request->getPostParam('foo'));
	}
	
	public function testRemovePostParam()
	{
		$this->_request->addPostParam('foo', 'bar');
		$this->_request->removePostParam('foo');
		
		$this->assertNull($this->_request->getPostParam('foo'));
	}
	
	public function testHasCookies()
	{
		$cookies = array('butter scotch', 'chocolate chip');
		
		$this->assertFalse($this->_request->hasCookies());
		
		$this->_request->setCookies($cookies);
		$this->assertTrue($this->_request->hasCookies());
	}
	
	public function testGetCookieVoid()
	{
		$this->assertNull($this->_request->getCookie('chocolate chip'));
	}
	
	public function testGetCookie()
	{
		$this->_request->setCookie('short', 'bread');
		$this->assertEquals('bread', $this->_request->getCookie('short'));
	}
	
	public function testRemoveCookie()
	{
		$this->_request->setCookie('chocolate', 'chip');
		$this->_request->removeCookie('chocolate');
		
		$this->assertNull($this->_request->getCookie('chocolate'));
	}
	
	public function testHasGetParams()
	{
		$params = array('foo' => 'bar');
		
		$this->assertFalse($this->_request->hasGetParams());
		
		$this->_request->setGetParams($params);
		$this->assertTrue($this->_request->hasGetParams());
	}
	
	public function testGetGetParamVoid()
	{
		$this->assertNull($this->_request->getGetParam('foo'));
	}
	
	public function testGetGetParam()
	{
		$this->_request->addGetParam('foo', 'bar');
		$this->assertEquals('bar', $this->_request->getGetParam('foo'));
	}
	
	public function testRemoveGetParam()
	{
		$this->_request->addGetParam('foo', 'bar');
		$this->_request->removeGetParam('foo');
		
		$this->assertNull($this->_request->getGetParam('foo'));
	}
	
	public function testHasUrlParams()
	{
		$params = array('foo', 'bar');
		
		$this->assertFalse($this->_request->hasUrlParams());
		
		$this->_request->setUrlParams($params);
		$this->assertTrue($this->_request->hasUrlParams());
	}
	
	public function testAddUrlParam()
	{
		$this->_request->addUrlParam('foo');
		$this->assertEquals('foo', array_pop($this->_request->getUrlParams()));
	}
	
	public function testSetMethodInvalid()
	{
		try {
			$this->_request->setMethod('create');
		} catch(Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testToString()
	{
		$urlParams = array('param1', 'param2');
		$postParams = array('key' => 'val');
		$cookies = array('cookie');
		$getParams = array('key' => 'val');
		$values = array(
			'path' 		 => 'www.foobar.com',
			'urlParams'  => $urlParams,
			'method' 	 => 'GET',
			'postParams' => $postParams,
			'cookies' 	 => $cookies,
			'getParams'  => $getParams,
			'singleRecord' => true
		);
		
		$this->_request->setValues($values);
		
		// method path/params?getParams -d postParams -b cookies
		$this->assertEquals('GET www.foobar.com/param1/param2?key=val -d key=val -b 0=cookie', (string)$this->_request);
	}
	
	public function testSerialize()
	{
		$urlParams = array('param1', 'param2');
		$postParams = array('key' => 'val');
		$cookies = array('cookie');
		$getParams = array('key' => 'val');
		$values = array(
			'path' 		 => 'www.foobar.com',
			'urlParams'  => $urlParams,
			'method' 	 => 'GET',
			'postParams' => $postParams,
			'cookies' 	 => $cookies,
			'getParams'  => $getParams,
			'singleRecord' => true
		);
		
		$this->_request->setValues($values);
		$res = $this->_request->serialize();
		$this->_request->unserialize($res);
		
		$newRequest = new Cwcl_Services_Request($values);
		
		$this->assertEquals($newRequest, $this->_request);
	}
}
