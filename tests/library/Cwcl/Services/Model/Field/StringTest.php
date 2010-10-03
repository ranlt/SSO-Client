<?php

class Cwcl_Services_Model_Field_String_Test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->field = new Cwcl_Services_Model_Field_String('string', array());
	}
	
	public function testParseValueNull()
	{
		$this->field->setValue(null);
		$this->assertNull($this->field->getValue());
	}
	
	public function testParseValueArray()
	{
		$value = array('something');
		$this->field->setValue($value);
		$this->assertEquals('Array', $this->field->getValue());
	}
}