<?php

class Cwcl_Services_Client_Abstract_Test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->_subject = $this->getMockForAbstractClass('Cwcl_Services_Client_Abstract', array(), '', FALSE);
	}
	
	public function testSetUri()
	{
		$uri = 'bob';
		$this->_subject->setUri($uri);
		
		$this->assertEquals($uri, $this->_subject->getUri());
	}
	
	public function testSetLogWriter()
	{
		$logWriter = $this->getMock('Zend_Log_Writer_Stream', array(), array('php://output'));
		$logWriter->expects($this->once())->method('write');
		$this->_subject->setLogWriter($logWriter);
		
		$log = $this->_subject->getLogWriter();
		$log->info('some message');
	}
	
	public function testSetCache()
	{
		$cache = $this->getMock('Zend_Cache');
		$this->_subject->setCache($cache);
		
		$this->assertEquals($cache, $this->_subject->getCache());
	}
	
	public function testRemoveCache()
	{
		$cache = $this->getMock('Zend_Cache');
		$this->_subject->setCache($cache);
		$this->_subject->removeCache();
		
		$this->assertNull($this->_subject->getCache()); 
	}
	
	public function testSetGateway()
	{
		$gateway = $this->getMockforAbstractClass('Cwcl_Services_Gateway_Abstract');
		$this->_subject->setGateway($gateway);
		
		$this->assertEquals($gateway, $this->_subject->getGateway());
	}
	
	public function testGetClientName()
	{
		$subject = $this->getMockForAbstractClass('Cwcl_Services_Client_Abstract', array(), 'Cwcl_Services_Client_AbstractMock', FALSE);
		$this->assertEquals('Cwcl_Services_Client_AbstractMock', $subject->getClientName());
	}
	
	public function testRun()
	{
		// variables
		$path = 'www.foobar.com/foo';
		
		// mock request
		$request = $this->getMock('Cwcl_Services_Request', array('setPath'));
		$request->expects($this->once())->method('setPath')->with($path);
		
		// mock response
		$response = $this->getMock('Cwcl_Services_Response', array('getHttpcode'), array(
			array (
				'httpCode' => '200'
			)
		));
		$response->expects($this->once())->method('getHttpcode')->will($this->returnValue('200'));
		
		// mock gateway
		$gateway = $this->getMock('Cwcl_Services_Gateway_Curl', array('isExecuted', 'exec', 'getResponse'));
		$gateway->expects($this->once())->method('isExecuted')->will($this->returnValue(false));
		$gateway->expects($this->once())->method('exec')->will($this->returnValue($gateway));
		$gateway->expects($this->once())->method('getResponse')->will($this->returnValue($response));
		
		$this->_subject->setUri($path);
		$this->_subject->setGateway($gateway);
		
		$res = $this->_subject->run($request);
		$this->assertEquals($response, $res);
	}
	
	public function testRunGatewayPreviouslyExecuted()
	{
		// variables
		$path = 'www.foobar.com/foo';
		
		// mock request
		$request = $this->getMock('Cwcl_Services_Request', array('setPath'));
		$request->expects($this->once())->method('setPath')->with($path);
		
		// mock response
		$response = $this->getMock('Cwcl_Services_Response', array('getHttpcode'), array(
			array (
				'httpCode' => '200'
			)
		));
		$response->expects($this->once())->method('getHttpcode')->will($this->returnValue('200'));
		
		// mock gateway
		$gateway = $this->getMock('Cwcl_Services_Gateway_Curl', array('isExecuted', 'exec', 'getResponse', 'reset'));
		$gateway->expects($this->once())->method('isExecuted')->will($this->returnValue(true));
		$gateway->expects($this->once())->method('reset');
		$gateway->expects($this->once())->method('exec')->will($this->returnValue($gateway));
		$gateway->expects($this->once())->method('getResponse')->will($this->returnValue($response));
		
		$this->_subject->setUri($path);
		$this->_subject->setGateway($gateway);
		
		$res = $this->_subject->run($request);
		$this->assertEquals($response, $res);
	}
	
	public function testRunNoGateway()
	{
		// variables
		$path = 'www.foobar.com/foo';
		
		// mock request
		$request = $this->getMock('Cwcl_Services_Request', array('setPath'));
		
		try {
			$res = $this->_subject->run($request);
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testRunBadResponse()
	{
		// variables
		$path = 'www.foobar.com/foo';
		$content = json_encode(array(
			'errorMessage' => 'Something went wrong',
			'errorCode' => '21'
		));
		
		// mock request
		$request = $this->getMock('Cwcl_Services_Request', array('setPath'));
		$request->expects($this->once())->method('setPath')->with($path);
		
		// mock response
		$response = $this->getMock('Cwcl_Services_Response', array('getHttpcode', 'getContent'), array(
			array (
				'httpCode' => '404'
			)
		));
		$response->expects($this->once())->method('getHttpcode')->will($this->returnValue('404'));
		$response->expects($this->once())->method('getContent')->will($this->returnValue($content));
		
		// mock gateway
		$gateway = $this->getMock('Cwcl_Services_Gateway_Curl', array('isExecuted', 'exec', 'getResponse'));
		$gateway->expects($this->once())->method('isExecuted')->will($this->returnValue(false));
		$gateway->expects($this->once())->method('exec')->will($this->returnValue($gateway));
		$gateway->expects($this->once())->method('getResponse')->will($this->returnValue($response));
		
		$this->_subject->setUri($path);
		$this->_subject->setGateway($gateway);
		
		try {
			$res = $this->_subject->run($request);
		} catch (Cwcl_Exception_Service_ErrorResponse $e) { return; }
		
		$this->fail('Service error response exception expected');
	}
	
	public function testRunNonJsonErrorResponse()
	{
		// variables
		$path = 'www.foobar.com/foo';
		$content = '<?xml version="1.0" encoding="UTF-8"?><response>Not JSON</response>';
		
		// mock request
		$request = $this->getMock('Cwcl_Services_Request', array('setPath'));
		$request->expects($this->once())->method('setPath')->with($path);
		
		// mock response
		$response = $this->getMock('Cwcl_Services_Response', array('getHttpcode', 'getContent'), array(
			array (
				'httpCode' => '404'
			)
		));
		$response->expects($this->exactly(2))->method('getHttpcode')->will($this->returnValue('404'));
		$response->expects($this->exactly(2))->method('getContent')->will($this->returnValue($content));
		
		// mock gateway
		$gateway = $this->getMock('Cwcl_Services_Gateway_Curl', array('isExecuted', 'exec', 'getResponse'));
		$gateway->expects($this->once())->method('isExecuted')->will($this->returnValue(false));
		$gateway->expects($this->once())->method('exec')->will($this->returnValue($gateway));
		$gateway->expects($this->once())->method('getResponse')->will($this->returnValue($response));
		
		$this->_subject->setUri($path);
		$this->_subject->setGateway($gateway);
		
		try {
			$res = $this->_subject->run($request);
		} catch (Cwcl_Services_Client_Exception $e) { return; }
		
		$this->fail('Service client exception expected');
	}
	
	public function testRunGatewayError()
	{
		// variables
		$path = 'www.foobar.com/foo';
		
		// mock request
		$request = $this->getMock('Cwcl_Services_Request', array('setPath'));
		$request->expects($this->once())->method('setPath')->with($path);
		
		// mock gateway
		$gateway = $this->getMock('Cwcl_Services_Gateway_Curl', array('isExecuted', 'exec', 'getResponse'));
		$gateway->expects($this->once())->method('isExecuted')->will($this->returnValue(false));
		$gateway->expects($this->once())->method('exec')->will($this->returnValue($gateway));
		$gateway->expects($this->once())->method('getResponse')->will($this->throwException(new Cwcl_Services_Gateway_Exception));
		
		$this->_subject->setUri($path);
		$this->_subject->setGateway($gateway);
		
		try {
			$this->_subject->run($request);
		} catch (Cwcl_Services_Client_Exception $e) { return; }
		
		$this->fail('Cwcl Services Client Exception expected');
	}
}