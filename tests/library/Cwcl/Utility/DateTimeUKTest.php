<?php

class Cwcl_Utility_DateTimeUK_Test extends PHPUnit_Framework_TestCase
{
	
	public function testukstrtotime()
	{
		$datetime = new Cwcl_Utility_DateTimeUK;
		$time = strtotime('08/05/2010');
		
		$this->assertEquals($time, $datetime->ukstrtotime('05/08/2010'));
	}
	
	public function testDefaultFormat()
	{
		$datetime = new Cwcl_Utility_DateTimeUK('05/08/2010', new DateTimeZone('Australia/Adelaide'));
		$res = $datetime->format();
		
		$this->assertEquals('05/08/2010 00:00', $res);
	}
}
