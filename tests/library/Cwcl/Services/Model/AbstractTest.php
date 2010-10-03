<?php

// include a mock implementation
require_once('Cwcl/Services/Model/ModelMock.php');

class Cwcl_Services_Model_Abstract_Test extends PHPUnit_Framework_TestCase
{
	
	function setUp()
	{
		$this->model = $this->getMockForAbstractClass('Cwcl_Services_Model_Abstract');
	}
	
	public function testClientGetterSetter()
	{
		$mockClient = $this->getMockForAbstractClass('Cwcl_Services_Client_Abstract', array(), '', false);
		$this->model->setClient($mockClient);
		
		$this->assertSame($mockClient, $this->model->getClient());
	}
	
	public function testDefaultPrimaryKey()
	{
		$this->assertEquals('id', $this->model->primaryKey());
	}
	
	public function testParserGetterSetter()
	{
		$mockParser = $this->getMockForAbstractClass('Cwcl_Services_Parser_Abstract');
		$this->model->setParser($mockParser);
		
		$this->assertSame($mockParser, $this->model->getParser());
	}
	
	public function testDispatchLockGetterSetter()
	{
		$this->model->setDispatchLock(true);
		$this->assertTrue($this->model->getDispatchLock());
	}
	
	public function testFind()
	{
		// params
		$id = 1;
		$serviceName = str_replace('mock_cwcl_services_model_', '', strtolower(get_class($this->model)));
		
		// model should pass equivalent request to client
		$mockRequest = new Cwcl_Services_Request(array(
			'urlParams' => array($serviceName),
			'getParams' => array('id' => $id)
		));
		
		// response from parser
		$model = $this->getMockForAbstractClass('Cwcl_Services_Model_Abstract');
		
		// mock response to be returned by client
		$mockResponse = $this->getMock('Cwcl_Services_Response');
		
		// mock the client
		$mockClient = $this->getMock('Cwcl_Services_Client_Abstract', array('run', 'getServiceDescription'), array(), '', false);
		$mockClient->expects($this->once())->method('run')->with($this->equalTo($mockRequest))->will($this->returnValue($mockResponse));
		// $mockClient->expects($this->once())->method('run')->will($this->returnValue($mockResponse));
		$this->model->setClient($mockClient);
		
		// mock the parser
		$mockParser = $this->getMock('Cwcl_Services_Parser_Abstract', array('setInput', 'parse'));
		$mockParser->expects($this->once())->method('setInput')->with($mockResponse)->will($this->returnValue($mockParser));
		$mockParser->expects($this->once())->method('parse')->will($this->returnValue($model));
		$this->model->setParser($mockParser);
		
		$this->model->find($id);
		
		$this->assertTrue($this->model->isLoaded());
		$this->assertTrue($this->model->isSaved());
		$this->assertFalse($this->model->isChanged());
		$this->assertNull($this->model->getRequest());
	}
	
	public function testFindLockDispatched()
	{
		// params
		$id = 1;
		$serviceName = str_replace('mock_cwcl_services_model_', '', strtolower(get_class($this->model)));
		
		// model should pass equivalent request to client
		$mockRequest = new Cwcl_Services_Request(array(
			'urlParams' => array($serviceName),
			'getParams' => array('id' => $id)
		));
		
		// mock the client
		$mockClient = $this->getMock('Cwcl_Services_Client_Abstract', array('run', 'getServiceDescription'), array(), '', false);
		// check that client's run method is never called
		$mockClient->expects($this->never())->method('run');
		$this->model->setClient($mockClient);
		
		// mock the parser
		$mockParser = $this->getMock('Cwcl_Services_Parser_Abstract', array('setInput', 'parse'));
		$mockParser->expects($this->never())->method('setInput');
		$mockParser->expects($this->never())->method('parse');
		$this->model->setParser($mockParser);
		
		$this->model->setDispatchLock(true);
		$this->model->find($id);
		
		$this->assertFalse($this->model->isLoaded());
		$this->assertFalse($this->model->isSaved());
		$this->assertEquals($mockRequest, $this->model->getRequest());
	}
	
	public function testFindLockNoClient()
	{
		// params
		$id = 1;
		
		// mock the parser
		$mockParser = $this->getMock('Cwcl_Services_Parser_Abstract', array('setInput', 'parse'));
		$mockParser->expects($this->never())->method('setInput');
		$mockParser->expects($this->never())->method('parse');
		$this->model->setParser($mockParser);
		
		try {
			$this->model->find($id);
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testFindNoParser()
	{
		// params
		$id = 1;
		
		// response from parser
		$model = $this->getMockForAbstractClass('Cwcl_Services_Model_Abstract');
		
		// mock the client
		$mockClient = $this->getMock('Cwcl_Services_Client_Abstract', array('run', 'getServiceDescription'), array(), '', false);
		// check that client's run method is never called
		$mockClient->expects($this->never())->method('run');
		$this->model->setClient($mockClient);
		
		try {
			$this->model->find($id);
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testFindAllIeratorResult()
	{
		// params
		$serviceName = str_replace('mock_cwcl_services_model_', '', strtolower(get_class($this->model)));
		
		// model should pass equivalent request to client
		$mockRequest = new Cwcl_Services_Request(array(
			'urlParams' => array($serviceName . 's'), // can't disable plural
			'singleRecord' => FALSE
		));
		
		// response from parser
		$model = $this->getMock('Cwcl_Services_Model_Iterator', array(), array(), '', false);
		
		// mock response to be returned by client
		$mockResponse = $this->getMock('Cwcl_Services_Response');
		
		// mock the client
		$mockClient = $this->getMock('Cwcl_Services_Client_Abstract', array('run', 'getServiceDescription'), array(), '', false);
		$mockClient->expects($this->once())->method('run')->with($this->equalTo($mockRequest))->will($this->returnValue($mockResponse));
		$this->model->setClient($mockClient);
		
		// mock the parser
		$mockParser = $this->getMock('Cwcl_Services_Parser_Abstract', array('setInput', 'parse'));
		$mockParser->expects($this->once())->method('setInput')->with($mockResponse)->will($this->returnValue($mockParser));
		$mockParser->expects($this->once())->method('parse')->will($this->returnValue($model));
		$this->model->setParser($mockParser);
		
		$res = $this->model->findAll();
		
		$this->assertTrue($res instanceof Cwcl_Services_Model_Iterator);
	}
	
	public function testFindBadParserResponse()
	{
		// params
		$id = 1;
		$serviceName = str_replace('mock_cwcl_services_model_', '', strtolower(get_class($this->model)));
		
		// model should pass equivalent request to client
		$mockRequest = new Cwcl_Services_Request(array(
			'urlParams' => array($serviceName),
			'getParams' => array('id' => $id)
		));
		
		// response from parser
		$model = $this->getMockForAbstractClass('Cwcl_Services_Model_Abstract');
		
		// mock response to be returned by client
		$mockResponse = $this->getMock('Cwcl_Services_Response');
		
		// mock the client
		$mockClient = $this->getMock('Cwcl_Services_Client_Abstract', array('run', 'getServiceDescription'), array(), '', false);
		$mockClient->expects($this->once())->method('run')->with($this->equalTo($mockRequest))->will($this->returnValue($mockResponse));
		$this->model->setClient($mockClient);
		
		// mock the parser
		$mockParser = $this->getMock('Cwcl_Services_Parser_Abstract', array('setInput', 'parse'));
		$mockParser->expects($this->once())->method('setInput')->with($mockResponse)->will($this->returnValue($mockParser));
		$mockParser->expects($this->once())->method('parse')->will($this->returnValue('FAIL!'));
		$this->model->setParser($mockParser);
		
		try {
			$this->model->find($id);
		} catch(Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testMagicAccessor()
	{
		$storage = $this->getMock('Cwcl_Services_Model_Field_Container', array('offsetGet', 'offsetExists'), array(array()));
		$storage->expects($this->once())->method('offsetExists')->with('id')->will($this->returnValue(true));
		$storage->expects($this->once())->method('offsetGet')->with('id')->will($this->returnValue('bob'));
		$this->model->setStorage($storage);
		
		$this->assertEquals('bob', $this->model->id);
	}
	
	
	public function testMagicAccessorCachedKey()
	{
		$storage = $this->getMock('Cwcl_Services_Model_Field_Container', array('offsetGet', 'offsetExists'), array(array()));
		// the key should be cached from above test and isset shouldn't be called again
		$storage->expects($this->never())->method('offsetExists');
		$storage->expects($this->once())->method('offsetGet')->with('id')->will($this->returnValue('bob'));
		
		$this->model->setStorage($storage);
		
		// see above test
		$this->assertEquals('bob', $this->model->id);
	}
	
	public function testMagicAccessorWithMapping()
	{
		$storage = $this->getMock('Cwcl_Services_Model_Field_Container', array('offsetGet', 'offsetExists'), array(array()));
		$storage->expects($this->once())->method('offsetExists')->with('id')->will($this->returnValue(true));
		$storage->expects($this->once())->method('offsetGet')->with('id')->will($this->returnValue('bob'));
		
		$model = new Cwcl_Services_Model_Mock;
		$model->setStorage($storage);
		
		$this->assertEquals('bob', $model->uid);
	}
	
	public function testMagicAccessorNullProperty()
	{
		$storage = $this->getMock('Cwcl_Services_Model_Field_Container', array('offsetGet', 'offsetExists'), array(array()));
		$storage->expects($this->once())->method('offsetExists')->with('name')->will($this->returnValue(false));
		
		$this->model->setStorage($storage);
		
		$this->assertNull($this->model->name);
	}
	
	public function testMagicAccessorMappingKeyValueIdentical()
	{
		$model = new Cwcl_Services_Model_Mock;
		
		try {
			$res = $model->same;
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testMagicMutatorNonExistentProperty()
	{
		$storage = $this->getMock('Cwcl_Services_Model_Field_Container', array('offsetExists'), array(array()));
		$storage->expects($this->once())->method('offsetExists')->with('length')->will($this->returnValue(false));
		
		$this->model->setStorage($storage);
		
		try {
			$this->model->length = 500;
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
	
	public function testMagicMutator()
	{
		$storage = $this->getMock('Cwcl_Services_Model_Field_Container', array('offsetExists', 'offsetSet'), array(array()));
		$storage->expects($this->once())->method('offsetExists')->with('someval')->will($this->returnValue(true));
		$storage->expects($this->once())->method('offsetSet')->with('someval', 500);
		
		$this->model->setStorage($storage);
		$this->model->someval = 500;
		
		$this->assertTrue($this->model->isChanged());
		$this->assertFalse($this->model->isSaved());
	}
	
	public function testSave()
	{
		// mock storage
		$storage = $this->getMock('Cwcl_Services_Model_Field_Container', array('offsetExists', 'offsetSet', 'getValues', 'isValid'), array(array()));
		$storage->expects($this->once())->method('offsetSet')->with('someval', 200);
		$storage->expects($this->once())->method('getValues')->will($this->returnValue(array('someval' => 200)));
		$storage->expects($this->once())->method('isValid')->will($this->returnValue(true));
		
		$this->model->setStorage($storage);
		
		$mockResponse = $this->getMock('Cwcl_Services_Response');
		
		$objName = str_replace('mock_cwcl_services_model_', '', strtolower(get_class($this->model)));
		
		$mockRequest = new Cwcl_Services_Request(array(
			'urlParams'    => array($objName),
			'postParams'   => array('someval' => 200),
			'method'       => Cwcl_Services_Request::POST,
			'singleRecord' => TRUE,
		));
		
		// mock the client
		$mockClient = $this->getMock('Cwcl_Services_Client_Abstract', array('run', 'getServiceDescription'), array(), '', false);
		$mockClient->expects($this->once())->method('run')->with($this->equalTo($mockRequest))->will($this->returnValue($mockResponse));
		$this->model->setClient($mockClient);
		
		// mock the parser
		$mockParser = $this->getMock('Cwcl_Services_Parser_Abstract', array('setInput', 'parse'));
		$mockParser->expects($this->once())->method('setInput')->with($mockResponse)->will($this->returnValue($mockParser));
		$mockParser->expects($this->once())->method('parse')->will($this->returnValue(true));
		$this->model->setParser($mockParser);
		
		// ensure the model has changed
		$this->model->someval = 200;
		$this->model->save();
		
		// assert that the model has been saved and loaded
		$this->assertTrue($this->model->isLoaded());
		$this->assertTrue($this->model->isSaved());
	}
	
	public function testSaveModelInvalid()
	{
		$storage = $this->getMock('Cwcl_Services_Model_Field_Container', array('offsetExists', 'offsetSet', 'getValues', 'isValid'), array(array()));
		$storage->expects($this->once())->method('offsetSet')->with('someval', 'invalid value')->will($this->returnValue(true));
		$storage->expects($this->never())->method('getValues');
		$storage->expects($this->once())->method('isValid')->will($this->returnValue(false));
		$this->model->setStorage($storage);
		
		$this->model->someval = 'invalid value';
		
		try {
			$this->model->save();
		} catch (Exception $e) { return; }
		
		$this->fail('Exception expected');
	}
}
