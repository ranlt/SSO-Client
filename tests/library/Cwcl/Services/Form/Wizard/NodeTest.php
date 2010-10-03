<?php

class Cwcl_Services_Form_Wizard_Node_Test extends PHPUnit_Framework_TestCase
{
	
	function setUp()
	{
		$this->node = $this->getMockForAbstractClass('Cwcl_Services_Form_Wizard_Node', array(), '', false);
	}
	
	public function testNameSetterGetter()
	{
		$this->node->setName('bob');
		$this->assertEquals('bob', $this->node->getName());
	}
	
	public function testTitleGetterSetter()
	{
		$this->node->setTitle("bob's page");
		$this->assertEquals("bob's page", $this->node->getTitle());
	}
	
	public function testDescriptionSetterGetter()
	{
		$this->node->setDescription('A node belonging to bob');
		$this->assertEquals('A node belonging to bob', $this->node->getDescription());
	}
	
	public function testViewSetterGetter()
	{
		$view = $this->getMock('Zend_View');
		$this->node->setView($view);
		$this->assertSame($view, $this->node->getView());
	}
	
	public function testSerialization()
	{
		$this->node->setName('bob');
		$this->node->setTitle("bob's page");
		$this->node->setDescription('A node belonging to bob');
		
		$serial = $this->node->serialize();
		$newNode = $this->getMockForAbstractClass('Cwcl_Services_Form_Wizard_Node', array(), '', false);
		$newNode->unserialize($serial);
		
		$this->assertTrue($this->node == $newNode);
	}
}
