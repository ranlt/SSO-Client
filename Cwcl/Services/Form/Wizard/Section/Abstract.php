<?php
/**
 * The section abstract class adds the isValid() abstract
 * method to the node
 *
 * @package Services Form Wizard Section
 * @category Cwcl
 * @author Sam de Freyssinet
 * @abstract
 */
abstract class Cwcl_Services_Form_Wizard_Section_Abstract
	extends Cwcl_Services_Form_Wizard_Node
{
	/**
	 * The pages valid state based on the models contained
	 * within
	 *
	 * @return void|boolean
	 * @access public
	 * @abstract
	 */
	abstract public function isValid();
}
