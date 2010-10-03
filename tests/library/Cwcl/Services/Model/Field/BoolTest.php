<?php

/**
* 
*/
class Cwcl_Services_Model_Field_Bool_Test extends PHPUnit_Framework_TestCase
{
	
	function setUp()
	{
		$this->field = new Cwcl_Services_Model_Field_Bool('bool', array());
	}
	
	public function testParseValueNullValue()
	{
		$this->field->setValue(null);
		$this->assertNull($this->field->getValue());
	}
	
	public function testParseValuesFalsyValues()
	{
		$falsyValues = array(
			'f',
			'false',
			'n',
			'no',
			'0'
		);
		
		foreach ($falsyValues as $val) {
			$this->field->setValue($val);
			$this->assertFalse($this->field->getValue());
		}
	}
	
	public function testParseValuesTruthyValues()
	{
		$truthyValues = array(
			't',
			'true',
			'y',
			'yes',
			1
		);
		
		foreach($truthyValues as $val) {
			$this->field->setValue($val);
			$this->assertTrue($this->field->getValue());
		}
	}
	
	public function testParseValuesNoValue()
	{
		$this->field->setValue('');
		$this->assertNull($this->field->getValue());
	}
}
