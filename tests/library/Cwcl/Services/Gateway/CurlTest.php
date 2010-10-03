<?php

if (!defined('TESTS_CWCL_SERVICE_CLIENT_BASE')) {
	define('TESTS_CWCL_SERVICE_CLIENT_BASE',  'file://' . realpath(dirname(__FILE__)) . '/_fixtures/' );
}

class Cwcl_Serviecs_Gateway_Curl_Test extends PHPUnit_Framework_TestCase
{
	
	function setUp()
	{
		if (!extension_loaded('curl')) {
            $this->markTestSkipped('cURL is not installed, marking all Service Client Curl tests skipped.');
        }

		$this->baseUri = TESTS_CWCL_SERVICE_CLIENT_BASE;
		$this->gateway = new Cwcl_Services_Gateway_Curl;
	}
	
	public function testClone()
	{
		try {
			clone $this->gateway;
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	/**
	 * Given that the gateway has been given a request
	 * And the gateway has not been executed
	 * The gateway should return a response object
	 */
	public function testExec()
	{
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getUrl')->will($this->returnValue($this->baseUri . '200'));
		$mockRequest->expects($this->once())->method('getPostParams')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
		
		$response = new Cwcl_Services_Response(array(
			'httpCode' => 0,
			'headers' => array(),
			'request' => $mockRequest,
			'content' => 'Sha woddy woddy'
		));
		
		$this->gateway->setRequest($mockRequest);
		$res = $this->gateway->exec();
		
		$this->assertEquals($response, $res->getResponse());
	}
	
	/**
	 * Given that a gateway has previously been executed
	 * The gateway should unset its request and response properties
	 */
	public function testReset()
	{
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getUrl')->will($this->returnValue($this->baseUri . '200'));
		$mockRequest->expects($this->once())->method('getPostParams')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
		
		$response = new Cwcl_Services_Response(array(
			'httpCode' => 0,
			'headers' => array(),
			'request' => $mockRequest,
			'content' => 'Sha woddy woddy'
		));
		
		$this->gateway->setRequest($mockRequest);
		$this->gateway->exec();
		$this->gateway->reset();
		
		$this->assertNull($this->gateway->getResponse());
		$this->assertNull($this->gateway->getRequest());
	}
	
	/**
	 * Given the curl gateway has previoulsy been executed
	 * And the gateway has not been reset
	 * A second call to exec will not be run
	 * And the cached response from the first execution will be returned from getResponse() method
	 */
	public function testExecAlreadyExecuted()
	{
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getUrl')->will($this->returnValue($this->baseUri . '200'));
		$mockRequest->expects($this->once())->method('getPostParams')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));

		$response = new Cwcl_Services_Response(array(
			'httpCode' => 0,
			'headers' => array(),
			'request' => $mockRequest,
			'content' => 'Sha woddy woddy'
		));

		$this->gateway->setRequest($mockRequest);
		
		// run exec twice. The second time should return the exact same response object
		$res = $this->gateway->exec()->getResponse();
		$res2 = $this->gateway->exec()->getResponse();
		
		$this->assertSame($res, $res2);
	}
	
	public function testExecNoRequest()
	{
		$this->gateway->exec();
		
		$this->assertNull($this->gateway->getResponse());
	}
	
	public function testExecWithCookies()
	{
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getUrl')->will($this->returnValue($this->baseUri . '200'));
		$mockRequest->expects($this->once())->method('getPostParams')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('GET'));
		$mockRequest->expects($this->once())->method('getCookies')->will($this->returnValue(array('cookie' => 'chocolate-chip')));
		
		$response = new Cwcl_Services_Response(array(
			'httpCode' => 0,
			'headers' => array(),
			'request' => $mockRequest,
			'content' => 'Sha woddy woddy'
		));
		
		$this->gateway->setRequest($mockRequest);
		$res = $this->gateway->exec();
		
		$this->assertEquals($response, $res->getResponse());
	}
	
	public function testExecPostRequest()
	{
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getUrl')->will($this->returnValue($this->baseUri . '200'));
		$mockRequest->expects($this->once())->method('getPostParams')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('POST'));
		
		$response = new Cwcl_Services_Response(array(
			'httpCode' => 0,
			'headers' => array(),
			'request' => $mockRequest,
			'content' => 'Sha woddy woddy'
		));
		
		$this->gateway->setRequest($mockRequest);
		$res = $this->gateway->exec();
		
		$this->assertEquals($response, $res->getResponse());
	}
	
	public function testExecPutRequest()
	{
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getUrl')->will($this->returnValue($this->baseUri . '200'));
		$mockRequest->expects($this->once())->method('getPostParams')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('PUT'));
		
		$response = new Cwcl_Services_Response(array(
			'httpCode' => 0,
			'headers' => array(),
			'request' => $mockRequest,
			'content' => 'Sha woddy woddy'
		));
		
		$this->gateway->setRequest($mockRequest);
		$res = $this->gateway->exec();
		
		$this->assertEquals($response, $res->getResponse());
	}
	
	public function testExecDeleteRequest()
	{
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getUrl')->will($this->returnValue($this->baseUri . '200'));
		$mockRequest->expects($this->once())->method('getPostParams')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('DELETE'));
		
		$response = new Cwcl_Services_Response(array(
			'httpCode' => 0,
			'headers' => array(),
			'request' => $mockRequest,
			'content' => 'Sha woddy woddy'
		));
		
		$this->gateway->setRequest($mockRequest);
		$res = $this->gateway->exec();
		
		$this->assertEquals($response, $res->getResponse());
	}
	
	public function testExecInvalidRequestMethod()
	{
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getUrl')->will($this->returnValue($this->baseUri . '200'));
		$mockRequest->expects($this->once())->method('getPostParams')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('getMethod')->will($this->returnValue('CREATE'));
		
		$this->gateway->setRequest($mockRequest);
		
		try {
			$res = $this->gateway->exec();
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testSerialize()
	{
		
	}
	
	public function testParseHeaders()
	{
		
	}
}
