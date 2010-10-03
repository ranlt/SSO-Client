<?php 
/**
 * Default factory class for instantiating Cwcl_Gearman_Client_* objects
 *
 * @package Cwcl
 **/
class Cwcl_Gearman_Client_Factory_Default
{
	/**
	 * An array of classed which can be instantiated
	 * The key is the last word of the object name
	 * The value determines whether it should be a new
	 * object (multiple) or a singleton (single)
	 *
	 * @var array
	 **/
	static $classes = array(
		'AddMACRequest' => 'single',
		'Availability' => 'single',
		'GetWadOrders' => 'single',
		);
	
	/**
	 * An array of objects which are already instantiated
	 *
	 * @var string
	 **/
	static $objects = array();
	
	/**
	 * Get an instance of the specified object
	 *
	 * @return object
	 **/
	static function get($name)
	{
		if (array_key_exists($name, self::$classes)) {
			if (self::$classes[$name] == 'single') {
				if (array_key_exists($name, self::$objects)) {
					return self::$objects[$name];
				} else {
					$object = self::getNewObject($name);
					if (is_object($object)) {
						self::$objects[$name] = $object;
						return self::$objects[$name];
					}
				}
			} elseif (self::$classes[$name] == 'multiple') {
				$object = self::getNewObject($name);
				if (is_object($object)) {
					return $object;
				}
			}
		}
		throw new Cwcl_Exception("This object does not exist.", 1);
	}
	
	/**
	 * Logic for making a new object
	 *
	 * @return void
	 * @author work
	 **/
	static function getNewObject($name)
	{
		$className = 'Cwcl_Gearman_Client_' . $name;
		if (class_exists($className)) {
			$object = new $className;
			$client = new GearmanClient;
			$client->addServer();
			$object->init($client);
			return $object;
		}
		return false;
	}
}