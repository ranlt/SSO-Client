<?php

class Cwcl_Services_Model_Field_Enum_Test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->field = new Cwcl_Services_Model_Field_Enum('enum', array());
	}
	
	public function testValidateNotInEnum()
	{
		$vals = array(
			'Bob',
			'Joe'
		);
		
		$this->field->setEnumeration($vals);
		$this->field->__init();
		$this->field->setValue('James');
		
		$this->assertTrue(count($this->field->getErrors()) > 0);
	}
	
	public function testValidateInEnum()
	{
		$vals = array(
			'Bob',
			'Joe'
		);
		
		$this->field->setEnumeration($vals);
		$this->field->__init();
		$this->field->setValue('Bob');
		
		$this->assertTrue(count($this->field->getErrors()) == 0);
	}
}