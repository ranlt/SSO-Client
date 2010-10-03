<?php

class Cwcl_Services_Model_Field_StdClass_Test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->field = new Cwcl_Services_Model_Field_StdClass('stdClass', array());
	}
	
	public function testParseValueObject()
	{
		$obj = new StdClass;
		$this->field->setValue($obj);
		$this->assertSame($obj, $this->field->getValue());
	}
	
	public function testParseValueNull()
	{
		$this->assertNull($this->field->getValue());
	}
	
	public function testParseValueNonObject()
	{
		$value = 'Joe';
		$test = new StdClass;
		$test->scalar = 'Joe';
		
		$this->field->setValue($value);
		$this->assertEquals($test, $this->field->getValue());
	}
}