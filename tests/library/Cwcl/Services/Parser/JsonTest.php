<?php

/**
 * These tests are actually testing the JSON Payload due to the way the payload is instantiated in the Parser
 * @todo Create a setPayloadClass or equivalent method, so that we can set it to a mock
 */ 
class Cwcl_Services_Parser_Json_Test extends PHPUnit_Framework_TestCase
{
	
	function setUp()
	{
		$this->parser = new Cwcl_Services_Parser_Json;
	}
	
	public function testParseBadRequestMethod()
	{
		$data = '{
			"contentType": "json",
			"payload": "stuff",
			"metadata": "stuff about aforementioned stuff"
		}';
		
		// mock response
		$mockResponse = $this->getMock('Cwcl_Services_Response');
		$mockResponse->expects($this->any())->method('getContent')->will($this->returnValue($data));
		
		// mock request
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getMethod')->will($this->returnValue('CREATE'));
		$mockResponse->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));
		
		// mock model
		$mockModel = $this->getMock('Cwcl_Services_Model_Abstract');
		
		$this->parser->setInput($mockResponse);
		
		try {
			$this->parser->parse($mockModel);
		} catch (Cwcl_Services_Parser_Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testParseNoPayload()
	{
		$data = '{
			"contentType": "json",
			"metadata": "stuff about aforementioned stuff"
		}';
		
		// mock response
		$mockResponse = $this->getMock('Cwcl_Services_Response');
		$mockResponse->expects($this->any())->method('getContent')->will($this->returnValue($data));
		$mockResponse->expects($this->never())->method('getRequest');
		
		// mock model
		$mockModel = $this->getMock('Cwcl_Services_Model_Abstract');
		
		$this->parser->setInput($mockResponse);
		
		try {
			$this->parser->parse($mockModel);
		} catch (Cwcl_Services_Parser_Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testParseGetRequestSingleRecord()
	{
		$data = '{
			"contentType": "json",
			"payload": ["stuff"],
			"metadata": "stuff about aforementioned stuff"
		}';

		// mock response
		$mockResponse = $this->getMock('Cwcl_Services_Response');
		$mockResponse->expects($this->any())->method('getContent')->will($this->returnValue($data));

		// mock request
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getMethod')->will($this->returnValue('GET'));
		$mockRequest->expects($this->once())->method('getSingleRecord')->will($this->returnValue(true));
		$mockResponse->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		// mock model
		$mockModel = $this->getMock('Cwcl_Services_Model_Abstract');
		$mockModel->expects($this->once())->method('setValues')->with(array('stuff'));

		$this->parser->setInput($mockResponse);
		$this->parser->parse($mockModel);
	}
	
	public function testParserGetRequestMultipleRecords()
	{
		$data = '{
			"contentType": "json",
			"payload": ["stuff"],
			"metadata": {
				"desc" : "stuff about stuff"
			}
		}';

		// mock response
		$mockResponse = $this->getMock('Cwcl_Services_Response');
		$mockResponse->expects($this->any())->method('getContent')->will($this->returnValue($data));

		// mock request
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getMethod')->will($this->returnValue('GET'));
		$mockRequest->expects($this->once())->method('getSingleRecord')->will($this->returnValue(false));
		$mockResponse->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		// mock model
		$mockModel = $this->getMock('Cwcl_Services_Model_Abstract');
		
		$this->parser->setInput($mockResponse);
		$res = $this->parser->parse($mockModel);
		
		$this->assertTrue($res instanceof Cwcl_Services_Model_Iterator);
		$this->assertEquals('stuff about stuff', $res->getMetadata('desc'));
	}
	
	public function testParseDeleteRequest()
	{
		$data = '{
			"contentType": "json",
			"payload": ["stuff"],
			"metadata": "stuff about aforementioned stuff"
		}';

		// mock response
		$mockResponse = $this->getMock('Cwcl_Services_Response');
		$mockResponse->expects($this->any())->method('getContent')->will($this->returnValue($data));

		// mock request
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getMethod')->will($this->returnValue('DELETE'));
		$mockResponse->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		// mock model
		$mockModel = $this->getMock('Cwcl_Services_Model_Abstract');
		$mockModel->expects($this->once())->method('reset');

		$this->parser->setInput($mockResponse);
		$res = $this->parser->parse($mockModel);
		
		$this->assertFalse($res);
	}
	
	public function testParsePostRequest()
	{
		$data = '{
			"contentType": "json",
			"payload": [["stuff"]],
			"metadata": "stuff about aforementioned stuff"
		}';

		// mock response
		$mockResponse = $this->getMock('Cwcl_Services_Response');
		$mockResponse->expects($this->any())->method('getContent')->will($this->returnValue($data));

		// mock request
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		$mockRequest->expects($this->any())->method('getMethod')->will($this->returnValue('POST'));
        $mockRequest->expects($this->once())->method('getSingleRecord')->will($this->returnValue(true));
		$mockResponse->expects($this->any())->method('getRequest')->will($this->returnValue($mockRequest));

		// mock model
		$mockModel = $this->getMock('Cwcl_Services_Model_Abstract');
		$mockModel->expects($this->once())->method('setValues')->with(array(array('stuff')));

		$this->parser->setInput($mockResponse);
		$res = $this->parser->parse($mockModel);
		
		$this->assertTrue($res);
	}
	
	public function testParseInvalidDataType()
	{
		$data = '<?xml version="1.0" encoding="UTF-8"?><data>Invalid</data>';
		
		// mock response
		$mockResponse = $this->getMock('Cwcl_Services_Response');
		$mockResponse->expects($this->any())->method('getContent')->will($this->returnValue($data));
		$mockResponse->expects($this->never())->method('getRequest');
		
		// mock model
		$mockModel = $this->getMock('Cwcl_Services_Model_Abstract');
		
		$this->parser->setInput($mockResponse);
		
		try {
			$this->parser->parse($mockModel);
		} catch (Cwcl_Services_Parser_Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
}
