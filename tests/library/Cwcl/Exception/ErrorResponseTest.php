<?php

/**
* 
*/
class Cwcl_Exception_ErrorResponse_Test extends PHPUnit_Framework_TestCase
{
	
	function testDefaultMessage()
	{
		$errorResp = new Cwcl_Exception_Service_ErrorResponse(null);
		
		$this->assertEquals('Fault in backend system.', $errorResp->getMessage());
	}
}
