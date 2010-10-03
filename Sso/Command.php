<?php
//
//class Sso_Model_Base
//{
//	private $_className;
//	protected $_storage = array();
//	
//	public function __construct($data)
//	{
//		if (is_array) {
//			$this->setAll($data);
//		}
//	}
//	
//	public function find($id)
//	{
//		$result = $this->_client->run(new Sso_Request(array(
//			'urlParams' => array($this->_className => $id)
//		)));
//		
//		return $result[0];
//	}
//	
//	public function findAll()
//	{
//		// return iterator of self
//	}
//	
//	public function loadResult($data)
//	{
//		$class = get_class($this);
//		return new $class($data);
//		// do some funky stuff
//		return json_decode($this->_response['data']);
//	}
//	
//	public function loadValues(array $data)
//	{
//		// populate values
//		
//		foreach ($data as $key => $value) {
//			$this->$key = $value;
//		}
//
//		return $this;
//	}
//	
//	public function __set($key, $value)
//	{
////		if (method_exists...){}
//	}
//}
//
//class Sso_Model_User
//{
//}
//
