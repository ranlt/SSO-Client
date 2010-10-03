<?php

class Cwcl_Services_Model_Field_Abstract_Test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->field = $this->getMockForAbstractClass('Cwcl_Services_Model_Field_Abstract', array('id', array()));
	}
	
	/**
	 * NotEmpty will be set as the default validator
	 */
	public function testValidateNullValueDefaultValidator()
	{
		$this->assertFalse($this->field->validate());
	}
	
	public function testSetAllowNull()
	{
		$this->field->setAllowNull(true);
		$this->assertTrue($this->field->validate());
	}
	
	public function testSetValidator()
	{
		$this->field->setValidator('alnum');
		$this->field->setValue('bre@k');
		$this->assertTrue(count($this->field->getErrors()) > 0);
	}
	
	public function testSetId()
	{
		$this->field->setId('bob');
		$this->assertEquals('bob', $this->field->getId());
	}
	
	public function testValidateNotNullDefaultValidator()
	{
		$this->field->expects($this->once())->method('parseValue')->will($this->returnValue('richard'));
		$this->field->setValue('richard');
		$res = $this->field->validate();
		$this->assertTrue($res);
	}
	
	public function testDefaultValue()
	{
		$this->field->setDefaultValue('bob');
		$this->assertEquals('bob', $this->field->getValue());
		
		$this->field->expects($this->once())->method('parseValue')->will($this->returnValue('joe'));
		$this->field->setValue('joe');
		$this->assertEquals('joe', $this->field->getValue());
	}
	
	public function testSetGetDescription()
	{
		$desc = 'This is a field';
		$this->field->setDescription($desc);
		$this->assertEquals($desc, $this->field->getDescription());
	}
	
	public function testSetUnsignedOption()
	{
		$options = array(
			'unsigned'
		);
		
		$this->field->setOptions($options);
		$this->field->expects($this->once())->method('parseValue')->will($this->returnValue(-3));
		$this->field->setValue(-3);
		
		$this->assertEquals(3, $this->field->getValue());
	}
	
	public function testSetZerofillOption()
	{
		$options = array(
			'zerofill'
		);
		
		$this->field->setLength(4);
		$this->field->setOptions($options);
		$this->field->expects($this->once())->method('parseValue')->will($this->returnValue(3));
		$this->field->setValue(3);
		
		$this->assertEquals(0003, $this->field->getValue());
	}
	
	public function testGetMessagesNoValidation()
	{
		$this->field->setAllowNull(true);
		$this->assertEquals(array(), $this->field->getMessages());
	}
	
	public function testGetMessages()
	{
		// default validator and error message
		$messages = array(
			'isEmpty' => "Value is required and can't be empty"
		);
		
		$this->assertEquals($messages, $this->field->getMessages());
	}
	
	public function testGetErrors()
	{
		// default notNull validation will have been run after creation
		$this->assertEquals(array("isEmpty"), $this->field->getErrors());
	}
	
	public function testSerialization()
	{
		$serial = $this->field->serialize();
		$field = $this->getMockForAbstractClass('Cwcl_Services_Model_Field_Abstract', array('id', array()));
		$field->unserialize($serial);
		
		$this->assertTrue($this->field == $field); 
	}
	
	public function testToString()
	{
		$this->field->expects($this->once())->method('parseValue')->will($this->returnValue('bob'));
		$this->field->setValue('bob');
		$res = (string) $this->field;
		$this->assertEquals('bob', $res);
	}
}