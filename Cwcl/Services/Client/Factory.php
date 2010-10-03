<?php
/**
 * Provides a factory method for creating clients
 *
 * @package Cwcl Services Client
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Client_Factory
{
	/**
	 * Create a new client based on the input
	 *
	 * @param string $type 
	 * @param Cwcl_Services_Response $input 
	 * @return Cwcl_Services_Client_Abstract
	 * @access public
	 * @static
	 */
	static public function factory($type, array $dependencies = array())
	{
		return Cwcl_Services_Client_Abstract::getInstance($type, $dependencies);
	}
}