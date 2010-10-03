<?php

class Cwcl_Services_Model_Iterator_Test extends PHPUnit_Framework_TestCase
{
	
	function setUp()
	{
		$this->model = $this->getMock('Cwcl_Services_Model_Abstract');
		$this->iterator = new Cwcl_Services_Model_Iterator(array(), $this->model);
	}
	
	public function testAccessorsPostConstruction()
	{
		// iterator is constructed in set up
		$this->assertEquals(0, $this->iterator->count());
		$this->assertEquals(array(), $this->iterator->getRecords());
		$this->assertSame($this->model, $this->iterator->getModel());
	}
	
	public function testCount()
	{
		$records = array('one', 'two', 'three');
		$this->iterator->setRecords($records);
		$this->assertEquals(3, $this->iterator->count());
	}
	
	public function testGetRecords()
	{
		$records = array(
			array(
				'id' => 1,
				'name' => 'Bob'
			),
			array(
				'id' => 2,
				'name' => 'Joe'
			)
		);
		
		$data = array(
			
		);
		
		$mockRequest = $this->getMock('Cwcl_Services_Request');
		
		$this->model->expects($this->exactly(2))->method('getRequest')->will($this->returnValue($mockRequest));
		$this->model->expects($this->exactly(2))->method('loadIteratorResult')->will($this->returnValue($this->model));
		
		$this->iterator->setRecords($records);
		$res = $this->iterator->getRecords();
	}
}
