<?php

class Cwcl_Gearman_Factory_Default_Test extends PHPUnit_Framework_TestCase
{
	public function testGetNonExistentClass()
	{
		try {
			Cwcl_Gearman_Client_Factory_Default::get('nothing');
		} catch (Cwcl_Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testGetNewObject()
	{
		$res = Cwcl_Gearman_Client_Factory_Default::get('AddMACRequest');
		
		$this->assertTrue($res instanceof Cwcl_Gearman_Client_AddMACRequest);
	}
	
	public function testGetExistingObject()
	{
		$first = Cwcl_Gearman_Client_Factory_Default::get('AddMACRequest');
		$second = Cwcl_Gearman_Client_Factory_Default::get('AddMACRequest');
		
		$this->assertSame($first, $second);
	}
}