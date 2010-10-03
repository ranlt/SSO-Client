<?php

require_once 'TestHelper.php';
require_once('PHPUnit/Framework.php');

class Test_Error extends PHPUnit_Framework_TestCase {
	public $client;
	
	public $fixtures = array(
		'fail1' => 'fail',
		'fail2' => array('rubbish'),
		'multi' => array('errorCode' => 'SSO001', 'errorMessage' => array('messages' => array('Message 1', 'Message 2'))),
		'single' => array('errorCode' => 'SSO001', 'errorMessage' => 'Single Message')
	);

	public function setup()
	{
	}
	
	public function tearDown()
	{
	}

	public function testConstructorFailNotArray()
	{
		$error = new Sso_Error($this->fixtures['fail1']);
		$this->assertFalse($error->isValid());
	}

	public function testConstructorFailInvalidArray()
	{
		$error = new Sso_Error($this->fixtures['fail2']);
		$this->assertFalse($error->isValid());
	}
	
	public function testConstructorMulti()
	{
		$error = new Sso_Error($this->fixtures['multi']);
		$this->assertEquals('Message 1, Message 2', $error->getMessage());
		$this->assertEquals('SSO001', $error->getCode());
	}
	
	public function testConstructorSingle()
	{
		$error = new Sso_Error($this->fixtures['single']);
		$this->assertEquals('Single Message', $error->getMessage());
		$this->assertEquals('SSO001', $error->getCode());
	}
	
	public function testToArray()
	{
		$error = new Sso_Error($this->fixtures['single']);
		$error = $error->toArray();
		
		$this->assertType('array', $error);
		$this->assertEquals('Single Message', $error['message']);
		$this->assertEquals('SSO001', $error['code']);
	}
	
	public function testToString()
	{
		$error = new Sso_Error($this->fixtures['single']);
		$error = $error->__toString();
		
		$this->assertType('string', $error);
		$this->assertEquals('SSO001 Single Message', $error);
	}
}

