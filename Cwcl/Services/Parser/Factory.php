<?php
/**
 * Provides a factory method for creating parsers
 *
 * @package Cwcl Services Parser
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Parser_Factory
{
	/**
	 * Create a new parser based on the input
	 *
	 * @param string $type 
	 * @param Cwcl_Services_Response $input 
	 * @return Cwcl_Services_Parser_Abstract
	 * @access public
	 * @static
	 */
	static public function factory($type, Cwcl_Services_Response $input = NULL)
	{
		return new $type($input);
	}
}