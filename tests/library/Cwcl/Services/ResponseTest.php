<?php

class Cwcl_Services_Response_Test extends PHPUnit_Framework_TestCase
{
	
	public function testConstructorsAndAccessors()
	{
		$headers = array(
			'Cache-Control' => 'must-revalidate',
			'Content-Type'  => 'text/html;charset=utf-8'
		);
		$request = new Cwcl_Services_Request;
		$options = array(
			'httpCode' => '200',
			'headers'  => $headers,
			'request'  => $request,
			'content'  => 'stuff'
		); 
		
		$response = new Cwcl_Services_Response($options);
		
		$this->assertEquals($headers, $response->getHeaders());
		$this->assertEquals($request, $response->getRequest());
		$this->assertEquals(200, $response->getHttpCode());
		$this->assertEquals('stuff', $response->getContent());
		
		$this->assertEquals('must-revalidate', $response->getHeader('Cache-Control'));
		$this->assertEquals('text/html;charset=utf-8', $response->getHeader('Content-Type'));
	}
	
	public function testSerialize()
	{
		$headers = array(
			'Cache-Control' => 'must-revalidate',
			'Content-Type'  => 'text/html;charset=utf-8'
		);
		$request = new Cwcl_Services_Request;
		$options = array(
			'httpCode' => '200',
			'headers'  => $headers,
			'request'  => $request,
			'content'  => 'stuff'
		);
		
		$response = new Cwcl_Services_Response($options);
		$serial = $response->serialize();
		$newResponse = new Cwcl_Services_Response();
		$newResponse->unserialize($serial);
		
		$this->assertEquals($response, $newResponse);
	}
}