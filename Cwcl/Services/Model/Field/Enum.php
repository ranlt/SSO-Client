<?php
/**
 * Enumerated Value field for Cwcl Model Field,
 * allows one value from a set to be defined
 *
 * @package Cwcl Services Model Field 
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Enum extends Cwcl_Services_Model_Field_Abstract
{
	/**
	 * The value type
	 *
	 * @var string
	 */
	protected $_type = 'enum';
	
	/**
	 * Allowed value list
	 *
	 * @var array
	 */
	protected $_enumeration = array();

	/**
	 * Sets the enumerated values allowed
	 *
	 * @param array $values 
	 * @return self
	 * @access public
	 */
	public function setEnumeration(array $values)
	{
		$this->_enumeration = $values;
		return $this;
	}

	/**
	 * Returns the enumerated values
	 *
	 * @return array
	 * @access public
	 */
	public function getEnumeration()
	{
		return $this->_enumeration;
	}

	/**
	 * Overload the parent init to add specific field
	 * validation
	 *
	 * @return void
	 * @access public
	 */
	public function __init()
	{
		// Init the parent
		parent::__init();

		if ($this->_validators instanceof Zend_Validate) {
			$this->addFieldValidator($this->_validators);
		}
	}

	/**
	 * Applies the correct field validation
	 *
	 * @param Zend_Validate $validators 
	 * @return void
	 * @access protected
	 */
	protected function addFieldValidator(Zend_Validate $validators)
	{
		$validators->addValidator(new Zend_Validate_InArray($this->getEnumeration()));
		return;
	}

	/**
	 * Parses a value. Takes the input value
	 * and converts it to the Field type
	 *
	 * @param mixed $value 
	 * @return mixed
	 * @access protected
	 */
	protected function parseValue($value)
	{
		return $value;
	}
}