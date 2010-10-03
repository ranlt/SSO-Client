<?php

class Cwcl_Services_Model_Field_Integer_Test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->field = new Cwcl_Services_Model_Field_Integer('int', array());
	}
	
	public function testParseValue()
	{
		$this->field->setValue('3Joe');
		$this->assertEquals(3, $this->field->getValue());
	}
}