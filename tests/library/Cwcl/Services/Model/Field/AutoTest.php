<?php

class Cwcl_Services_Model_Field_Auto_Test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->field = new Cwcl_Services_Model_Field_Auto('auto', array());
	}
	
	public function testValidate()
	{
		$this->assertTrue($this->field->validate());
	}
	
	/**
	 * parseValue is protected, so we use setValue as proxy method
	 */
	public function testParseValue()
	{
		$this->field->setValue('bob');
		$this->assertNull($this->field->getValue());
	}
}