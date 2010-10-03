<?php

// because I can't get Zend Autoloader to work...
require_once('Cwcl/Services/Model/Field/StringMock.php');
require_once('Cwcl/Services/Model/Field/IntegerMock.php');

class Cwcl_Services_Model_Field_Container_Test extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->fields = array(
			'name' => array('type' => 'Cwcl_Services_Model_Field_StringMock'),
			'age' => array('type' => 'Cwcl_Services_Model_Field_IntegerMock'),
			'email' => array('type' => 'Cwcl_Services_Model_Field_StringMock')
		);
		
		try {
			$this->container = new Cwcl_Services_Model_Field_Container($this->fields);
		} catch (Exception $e) { die($e->getMessage()); }
	}
	
	public function testTest()
	{
		$this->assertTrue(true);
	}
	
	// test factory method (should be same as constructor)
	public function testFactoryMethod()
	{
		$container = Cwcl_Services_Model_Field_Container::factory($this->fields);
		$this->assertEquals($this->container, $container);
	}
	
	public function testCount()
	{
		$this->assertEquals(3, $this->container->count());
	}
	
	public function testKey()
	{
		$this->assertEquals('name', $this->container->key());
	}
	
	public function testNext()
	{
		$test = new Cwcl_Services_Model_Field_IntegerMock('age', array());
		
		$res = $this->container->next();
		
		$this->assertEquals('age', $this->container->key());
		$this->assertEquals($test, $res);
	}
	
	public function testCurrent()
	{
		$test = new Cwcl_Services_Model_Field_StringMock('name', array());
		
		$this->assertEquals($test, $this->container->current());
	}
	
	public function testValid()
	{
		$this->assertTrue($this->container->valid());
		
		$this->container->next();
		$this->container->next();
		$this->container->next();
	
		$this->assertFalse($this->container->valid());
	}
	
	public function testRewind()
	{
		$this->container->next();
		$this->container->rewind();
		
		$this->assertEquals('name', $this->container->key());
	}
	
	public function testOffsetGetNonExistentKey()
	{
		$this->assertNull($this->container->offsetGet('password'));
	}
	
	public function testOffsetGetFieldObject()
	{
		$this->assertEquals('James', $this->container->offsetGet('name'));
	}
	
	public function testOffsetSetNonExistentKey()
	{
		try {
			$this->container->offsetSet('password', 'somevalue');
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testOffsetSetFieldObjectInterface()
	{
		$this->container->offsetSet('age', array(19));
		$this->assertEquals(19, $this->container->offsetGet('age'));
	}
	
	// this will fail because we can't make dynamic mocks
	// public function testOffsetUnset()
	// {
	// 	$this->container->offsetSet('email', array('james@ibuildings.com'));
	// 	$this->container->offsetUnset('email');
	// 	
	// 	$this->assertNull($this->container->offsetGet('email'));
	// }
	
	public function testOffsetExistsNonExistentKey()
	{
		$this->assertFalse($this->container->offsetExists('password'));
	}
	
	public function testOffsetExistsExistentKey()
	{
		$this->assertTrue($this->container->offsetExists('name'));
	}
	
	public function testIsValidAllFieldsValid()
	{
		$this->assertTrue($this->container->isValid());
	}
}