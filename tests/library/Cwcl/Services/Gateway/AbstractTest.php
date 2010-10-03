<?php

/**
* 
*/
class Cwcl_Gateway_Abstract_Test extends PHPUnit_Framework_TestCase
{
	
	function setUp()
	{
		$this->gateway = $this->getMockForAbstractClass('Cwcl_Services_Gateway_Abstract');
	}
	
	public function testRequestSetterGetter()
	{
		$request = $this->getMock('Cwcl_Services_Request');
		$this->gateway->setRequest($request);
		$this->assertSame($request, $this->gateway->getRequest());
	}
	
	public function testGetResponseNotExecuted()
	{
		$this->assertNull($this->gateway->getResponse());
	}
	
	public function testSerialize()
	{
		$request = new Cwcl_Services_Request;
		
		$this->gateway->setRequest($request);
		$serial = $this->gateway->serialize();
		
		$gateway = $this->getMockForAbstractClass('Cwcl_Services_Gateway_Abstract');
		$gateway->unserialize($serial);
		
		$this->assertTrue($this->gateway == $gateway);
	}
}
