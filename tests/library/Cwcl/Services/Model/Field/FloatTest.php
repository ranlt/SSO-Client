<?php

class Cwcl_Services_Model_Field_Float_Test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->field = new Cwcl_Services_Model_Field_Float('float', array());
	}
	
	public function testParseValue()
	{
		$this->field->setValue("4.05Bob");
		$this->assertEquals(4.05, $this->field->getValue());
	}
}