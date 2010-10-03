<?php
/**
 * Model Field class that contains all information
 * and methods for each field
 *
 * @package Cwcl Services Model
 * @author Sam de Freyssinet
 */
abstract class Cwcl_Services_Model_Field_Abstract implements Serializable
{
	/**
	 * The id of the field property
	 *
	 * @var string
	 */
	protected $_id;

	/**
	 * The value of the field property
	 *
	 * @var mixed
	 */
	protected $_value;

	/**
	 * The datatype of this field
	 *
	 * @var string
	 */
	protected $_type;

	/**
	 * The scale/length of the field
	 *
	 * @var integer|boolean
	 */
	protected $_length = FALSE;

	/**
	 * Allow NULL values
	 *
	 * @var boolean
	 */
	protected $_allowNull = FALSE;

	/**
	 * Use a default value if not set
	 *
	 * @var boolean
	 */
	protected $_defaultValue = NULL;

	/**
	 * The description of the field, used by forms
	 * and data services
	 *
	 * @var string
	 */
	protected $_description;

	/**
	 * Additional options
	 * - unsigned
	 * - zerofill
	 *
	 * @var array
	 */
	protected $_options = array();

	/**
	 * Pre-filters to apply before the
	 * validation
	 *
	 * @var Zend_Filter
	 */
	protected $_preFilters;

	/**
	 * Pre filters string for serialisation
	 *
	 * @var string
	 */
	protected $_preFilter;

	/**
	 * Validators to run on the field
	 *
	 * @var Zend_Validate
	 */
	protected $_validators;

	/**
	 * String containing the validate rules
	 *
	 * @var string
	 */
	protected $_validate;

	/**
	 * Post-filters to apply after validation
	 *
	 * @var Zend_Filter
	 */
	protected $_postFilters;

	/**
	 * Post-filter string for serialisation
	 *
	 * @var string
	 */
	protected $_postFilter;

	/**
	 * Field valid state
	 *
	 * @var boolean
	 */
	protected $_valid;

	/**
	 * Controls whether the field is editable
	 *
	 * @var boolean
	 */
	protected $_editable = TRUE;

	/**
	 * Constructs the field object ready for use
	 *
	 * @param string $id 
	 * @param array $description 
	 * @access public
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	public function __construct($id, array $description)
	{
		// Setup the id
		$this->_id = $id;

		// Parse the description
		foreach ($description as $key => $value) {
			$key = '_'.$key;
			if ( ! property_exists($this, $key)) {
				throw new Cwcl_Services_Model_Field_Exception($key.' property does not exist in '.__CLASS__);
			}

			// Setting the value to the key
			$this->$key = $value;
		}

		// Setup the validation and filters on this field
		$this->__init();
	}

	/**
	 * Returns this model as a string representation
	 *
	 * @return string
	 * @access public
	 */
	public function __toString()
	{
		return (string) $this->_value;
	}

	/**
	 * Setup the field ready for use
	 *
	 * @return void
	 * @access public
	 */
	public function __init()
	{
		// If not editable, bail
		if ( ! $this->_editable) {
			return;
		}

		// Parse pre-filters
		$this->_preFilters = $this->__initFilters($this->parseValidateFilterString($this->_preFilter));

		// Check options for additional filters
		foreach ($this->_options as $option) {
			switch ($option) {
				case 'unsigned' : {
					$this->_preFilters->addFilter(new Cwcl_Services_Model_Field_Filter_Unsigned);
					break;
				}
				case 'zerofill' : {
					if (NULL !== ($length = $this->getLength())) {
						$this->_preFilters->addFilter(new Cwcl_Services_Model_Field_Filter_Zerofill($length));
					}
					break;
				}
			}
		}

		// Check for conditionals in field properties
		$length = $this->getLength();
		if ( ! in_array($length, array(NULL, FALSE), TRUE)) {
			$this->_validate .= in_array($this->_type, array('integer', 'float')) ? 
				' LessThan['.(pow(10, $length)-1).']' : ' StringLength[0,'.$length.']';
		}

		// Check for empty fields
		if (FALSE === $this->getAllowNull() && strpos($this->_validate, 'NotEmpty') === FALSE) {
			$this->_validate .= ' NotEmpty';
		}
		
		// Parse validators
		$this->_validators = $this->__initValidators($this->parseValidateFilterString($this->_validate));

		// Parse post-filters
		$this->_postFilters = $this->__initFilters($this->parseValidateFilterString($this->_postFilter));

		// Run validation
		$this->validate();

		return;
	}


	/**
	 * Initialise the validators
	 *
	 * @param array $validators
	 * @return void|Zend_Validate
	 * @access public
	 */
	public function __initValidators(array $validators)
	{
		if ( ! $this->_editable or ! $validators) {
			return;
		}

		$chain = new Zend_Validate;

		foreach ($validators as $validator) {
			$chain->addValidator($this->createZendValidator($validator));
		}

		return $chain;
	}

	/**
	 * Initialise the filters
	 *
	 * @param array $filters 
	 * @return void|Zend_Filter
	 * @access public
	 */
	public function __initFilters(array $filters)
	{
		if ( ! $this->_editable) {
			return;
		}

		$chain = new Zend_Filter;

		foreach ($filters as $filter) {
			$chain->addFilter($this->createZendFilter($filter));
		}

		return $chain;
	}

	/**
	 * Serialise the field
	 * 
	 * [SPL Serializable]
	 *
	 * @param array $toSerialize 
	 * @return string
	 * @access public
	 */
	public function serialize(array $toSerialize = array())
	{
		$toSerialize += array(
			'_id'             => $this->_id,
			'_value'          => $this->_value,
			'_type'           => $this->_type,
			'_length'         => $this->_length,
			'_allowNull'      => $this->_allowNull,
			'_defaultValue'   => $this->_defaultValue,
			'_options'        => $this->_options,
			'_preFilter'      => $this->_preFilter,
			'_validate'       => $this->_validate,
			'_postFilter'     => $this->_postFilter,
			'_valid'          => $this->_valid,
		);

		return serialize($toSerialize);
	}

	/**
	 * Unserialise the field and reinitialise the
	 * the field
	 * 
	 * [SPL Serializable]
	 *
	 * @param string $serialized 
	 * @return void
	 * @access public
	 */
	public function unserialize($serialized)
	{
		$unserialized = unserialize($serialized);

		foreach ($unserialized as $key => $value) {
			$this->$key = $value;
		}

		// Re-initialise the field
		$this->__init();

		return;
	}

	/**
	 * Run the filters and validation on this field
	 *
	 * @return boolean
	 * @access public
	 */
	public function validate()
	{
		if (TRUE === $this->_allowNull and NULL === $this->_value) {
			return $this->_valid = TRUE;
		}

		return $this->_valid = ($this->_validators instanceof Zend_Validate) ? $this->_validators->isValid($this->_value) : TRUE;
	}

	/**
	 * Get the id of this field
	 *
	 * @return mixed
	 * @access public
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Set the id of this field
	 *
	 * @param mixed $id 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	public function setId($id)
	{
		if ( ! $this->_editable) {
//			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' cannot set id value to non-editable field');
		}

		$this->_id = $id;
		return $this;
	}
	
	/**
	 * Get the type of field
	 *
	 * @return string
	 * @access public
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * Get the value for this field
	 *
	 * @return mixed
	 * @access public
	 */
	public function getValue()
	{
		if (NULL !== $this->_value) {
			return ($this->_postFilters instanceof Zend_Filter) ? $this->_postFilters->filter($this->_value) : $this->_value;
		}

		if (NULL !== ($value = $this->getDefaultValue())) {
			return ($this->_postFilters instanceof Zend_Filter) ? $this->_postFilters->filter($value) : $value;
		}

		return NULL;
	}

	/**
	 * Set the value for this field
	 *
	 * @param string $value 
	 * @return self
	 * @access public
	 */
	public function setValue($value)
	{
		// Do filtering/validation
		$this->_value = ($this->_preFilters instanceof Zend_Filter) ? $this->_preFilters->filter($this->parseValue($value)) : $this->parseValue($value);
		$this->validate();
		return $this;
	}

	/**
	 * Get the length/size of this field
	 *
	 * @return integer
	 * @access public
	 */
	public function getLength()
	{
		return $this->_length;
	}

	/**
	 * Set the length/size of this field
	 *
	 * @param integer $length 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	public function setLength($length)
	{
		if ( ! $this->_editable) {
			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' cannot set id value to non-editable field');
		}

		$this->_length = $length;
		$this->__init();
		return $this;
	}

	/**
	 * Get the allowNull value
	 *
	 * @return boolean
	 * @access public
	 */
	public function getAllowNull()
	{
		return $this->_allowNull;
	}

	/**
	 * Set the allowNull value
	 *
	 * @param boolean $null 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	public function setAllowNull($null)
	{
		if ( ! $this->_editable) {
			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' cannot set id value to non-editable field');
		}

		$this->_allowNull = (bool) $null;
		$this->__init();
		return $this;
	}

	/**
	 * Get the default value
	 *
	 * @return mixed
	 * @access public
	 */
	public function getDefaultValue()
	{
		return $this->_defaultValue;
	}

	/**
	 * Set the default value
	 *
	 * @param mixed $value 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	public function setDefaultValue($value)
	{
		if ( ! $this->_editable) {
			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' cannot set id value to non-editable field');
		}

		$this->_defaultValue = $value;
		return $this;
	}

	/**
	 * Get the description of the field
	 *
	 * @return string
	 * @access public
	 */
	public function getDescription()
	{
		return $this->_description;
	}

	/**
	 * Set the description of the field
	 *
	 * @param string $description 
	 * @return self
	 * @access public
	 */
	public function setDescription($description)
	{
		$this->_description = (string) $description;
		return $this;
	}

	/**
	 * Get the options
	 *
	 * @return array
	 * @access public
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Set options to this field
	 *
	 * @param array $options 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	public function setOptions(array $options)
	{
		if ( ! $this->_editable) {
			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' cannot set id value to non-editable field');
		}

		$this->_options = $options;
		$this->__init();
		return $this;
	}

	/**
	 * Returns the error messages
	 *
	 * @return array
	 * @access public
	 */
	public function getMessages()
	{
		if ($this->_validators instanceof Zend_Validate) {
			return $this->_validators->getMessages();
		}

		return array();
	}

	/**
	 * Returns the errors
	 *
	 * @return array
	 * @access public
	 */
	public function getErrors()
	{
		if ($this->_validators instanceof Zend_Validate) {
			return $this->_validators->getErrors();
		}

		return array();
	}

	/**
	 * Return the validator string
	 *
	 * @return string
	 * @access public
	 */
	public function getValidator()
	{
		return $this->_validate;
	}

	/**
	 * Set the validator string
	 *
	 * @param string $validator 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	public function setValidator($validator)
	{
		if ( ! $this->_editable) {
			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' cannot set validator to non-editable field');
		}

		$this->_validator = (string) $validator;
		$this->__init();
		return $this;
	}

	/**
	 * Get the prefilter string
	 *
	 * @return string
	 * @access public
	 */
	public function getPreFilter()
	{
		return $this->_preFilter;
	}

	/**
	 * Set the pre-filter string
	 *
	 * @param string $filter 
	 * @return self
	 * @author Sam de Freyssinet
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	public function setPreFilter($filter)
	{
		if ( ! $this->_editable) {
			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' cannot set id value to non-editable field');
		}

		$this->_preFilter = (string) $filter;
		$this->__init();
		return $this;
	}

	/**
	 * Get the post-filter string
	 *
	 * @return string
	 * @access public
	 */
	public function getPostFilter()
	{
		return $this->_postFilter;
	}

	/**
	 * Set the post-filter string
	 *
	 * @param string $filter
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	public function setPostFilter($filter)
	{
		if ( ! $this->_editable) {
			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' cannot set id value to non-editable field');
		}

		$this->_postFilter = (string) $filter;
		$this->__init();
		return $this;
	}

	/**
	 * Create a new Zend_Validate class
	 *
	 * @param string $validator 
	 * @return Zend_Validate
	 * @access protected
	 */
	protected function createZendValidator($validator)
	{
		return (1 < count($validator)) ? $this->buildZendClass($validator[0], $validator[1]) : $this->buildZendClass($validator[0], array());
	}

	/**
	 * Create a new Zend_Filter class
	 *
	 * @param string $validator 
	 * @return Zend_Filter
	 * @access protected
	 */
	protected function createZendFilter($filter)
	{
		return (1 < count($filter)) ? $this->buildZendClass($filter[0], $filter[1], 'filter') : $this->buildZendClass($filter[0], array(), 'filter');
	}

	/**
	 * Builds a Zend class and returns it
	 *
	 * @param string $class
	 * @param array $argument
	 * @param string $type validator|filter
	 * @return mixed
	 * @access protected
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	protected function buildZendClass($class, array $arguments, $type = 'validator')
	{
		// First try and load the class directly
		if ( ! Zend_Loader_Autoloader::autoload($class)) {
			$class = ($type === 'validator') ? 'Zend_Validate_'.$class : 'Zend_Filter_'.$class;
			if ( ! Zend_Loader_Autoloader::autoload($class)) {
				throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' could not locate class : '.$class);
			} 
		}
		
		try
		{
			$class = new ReflectionClass($class);

			return $class->getConstructor() ? $class->newInstanceArgs($arguments) : $class->newInstance();
		}
		catch (Exception $e)
		{
			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' there was a problem creating an instance of '.$class->getName().': '.$e->getMessage());
		}
	}

	/**
	 * Parses the validate/filter string to resolve
	 * the filter/validation class
	 *
	 * @param string $string 
	 * @return array
	 * @access protected
	 */
	protected function parseValidateFilterString($string)
	{
		if (empty($string)) {
			return array();
		}

		$parsed = explode(' ', $string);
		$return = array();

		foreach ($parsed as $part) {
			if (empty($part)) {
				continue;
			}
			if ( ! preg_match_all('/(\w+)[(.+)]?/', $part, $matches)) {
				throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' could not parse the string : '.$string);
			}

			// Grab the parsed matches
			$arguments = $matches[1];
			$method = array_shift($arguments);

			$return[] = array($method, $arguments);
		}

		return $return;
	}

	/**
	 * Parses a value. Takes the input value
	 * and converts it to the Field type
	 *
	 * @param mixed $value 
	 * @return mixed
	 * @access protected
	 * @abstract
	 */
	abstract protected function parseValue($value);
}