<?php
/**
 * Cwcl Services Model decorator to create form elements from the
 * Cwcl_Services_Model_Abstract description, values and validation
 * rules. This is not designed to automatically create forms, but
 * will aid in creating the required fields for forms that directly
 * relate to Cwcl_Services_Model_Abstract models.
 *
 * @category Cwcl
 * @package Model
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Decorator_Form implements Serializable
{
	/**
	 * The model to decorate
	 *
	 * @var Cwcl_Services_Model_Abstract
	 */
	protected $_model;

	/**
	 * Custom labels to apply to the field names,
	 * if not defined then the field name will be
	 * used
	 *
	 * @var array
	 */
	protected $_labels = array();

	/**
	 * If FALSE then all the models fields are processed
	 * If array of values, then only values in the array
	 * matching field ids will have form elements created
	 *
	 * @var boolean|array
	 */
	protected $_selectSpecificFields = FALSE;

	/**
	 * Store of initialised Zend_Form_Elements ready for
	 * manipulation
	 *
	 * @var array
	 */
	protected $_formFields = array();

	/**
	 * NS values
	 *
	 * @var array
	 */
	protected $_namespace = array();

	/**
	 * The separating string for NS
	 *
	 * @var string
	 */
	protected $_namespaceSeparator = '.';

	/**
	 * Creates a new form decorator
	 *
	 * @param array $dependencies 
	 * @access public
	 */
	public function __construct(array $dependencies = array())
	{
		foreach ($dependencies as $key => $value) {
			$key = 'set'.ucfirst($key);
			if ( ! method_exists($this, $key)) {
				throw new Cwcl_Services_Model_Decorator_Exception(__CLASS__.' does not have method : '.$key);
			}
			$this->$key($value);
		}
	}

	/**
	 * Magic method to handle being cast to string
	 *
	 * @return string
	 * @access public
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Serialises the object to string form
	 * 
	 * [SPL] Serializable
	 *
	 * @param array $toSerialize [Optional]
	 * @return string
	 * @access public
	 */
	public function serialize(array $toSerialize = array())
	{
		$toSerialize += array(
			'_model'                  => $this->_model,
			'_labels'                 => $this->_labels,
			'_selectSpecificFields' => $this->_selectSpecificFields,
			'_formFields'             => $this->_formFields,
			'_namespace'              => $this->_namespace,
			'_namespaceSeparator'     => $this->_namespaceSeparator,
		);

		return serialize($toSerialize);
	}

	/**
	 * Unserialises the object from a string
	 * 
	 * [SPL] Serializable
	 *
	 * @param string $string 
	 * @return void
	 * @access public
	 */
	public function unserialize($string)
	{
		$unserialized = unserialize($string);

		if ( ! is_array($unserialized)) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' unserialize failed to produce correct property array from string : '.$string);
		}

		foreach ($unserialized as $key => $value) {
			if ( ! property_exists($this, $key)) {
				throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' trying to set a property that does not exists : '.$key);
			}

			$this->$key = $value;
		}
	}

	/**
	 * Generates the form fields ready for use
	 *
	 * @return self
	 * @access public
	 */
	public function createFields()
	{
		$this->_formFields = $this->createModelFormFields();
		return $this;
	}

	/**
	 * Returns the model applied to the decorator
	 *
	 * @return Cwcl_Services_Model_Abstract
	 * @access public
	 */
	public function getModel()
	{
		return $this->_model;
	}

	/**
	 * Sets the model to decorate
	 *
	 * @param Cwcl_Services_Model_Abstract $model 
	 * @return self
	 * @access public
	 */
	public function setModel(Cwcl_Services_Model_Abstract $model)
	{
		$this->_model = $model;
		return $this;
	}

	/**
	 * Returns the preset labels for the fields
	 *
	 * @return array
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	public function getLabels()
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		// Get the fields
		$fields = $model->getFieldsAsContainer();
		$labels = array();

		foreach ($fields as $key => $value) {
			$labels[$key] = isset($this->_labels[$key]) ? $this->_labels[$key] : $key;
		}

		return $this->_labels;
	}

	/**
	 * Set labels for each field within the model
	 *
	 * @param array $labels 
	 * @return self
	 * @access public
	 */
	public function setLabels(array $labels)
	{
		$this->_labels = $labels;
		return $this;
	}

	/**
	 * Sets the namespace for this decorator
	 *
	 * @param array $namespace 
	 * @return self
	 * @access public
	 */
	public function setNamespace(array $namespace)
	{
		$this->_namespace = $namespace;
		return $this;
	}

	/**
	 * Gets the current namespace
	 *
	 * @return array
	 * @access public
	 */
	public function getNamespace()
	{
		return $this->_namespace;
	}

	/**
	 * Sets the namespace separator
	 *
	 * @param string $separator 
	 * @return self
	 * @access public
	 */
	public function setNamespaceSeparator($separator)
	{
		$this->_namespaceSeparator = $separator;
		return $this;
	}

	/**
	 * Gets the namespace separator
	 *
	 * @return string
	 * @access public
	 */
	public function getNamespaceSeparator()
	{
		return $this->_namespaceSeparator;
	}

	/**
	 * Sets an attribute to a specific field
	 *
	 * @param string $field 
	 * @param string $name 
	 * @param string $value 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	public function setAttrib($field, $name, $value)
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		if ( ! $this->_formFields[$field] instanceof Zend_Form_Element) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' The field : '.$field.' does not exist!');
		}

		$this->_formFields[$field]->setAttrib($name, $value);
		return $this;
	}

	/**
	 * Sets multiple attributes to a specific field
	 *
	 * @param string $field 
	 * @param array $values 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	public function setAttribs($field, array $values)
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		if ( ! $this->_formFields[$field] instanceof Zend_Form_Element) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' The field : '.$field.' does not exist!');
		}

		$this->_formFields[$field]->setAttribs($values);
		return $this;
	}

	/**
	 * Get the attribute by name on a specific field
	 *
	 * @param string $field 
	 * @param string $name 
	 * @return string
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	public function getAttrib($field, $name)
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		if ( ! $this->_formFields[$field] instanceof Zend_Form_Element) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' The field : '.$field.' does not exist!');
		}

		return $this->_formFields[$field]->getAttrib($name);
	}

	/**
	 * Get all the attributes on a specific field
	 *
	 * @param string $field 
	 * @return array
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	public function getAttribs($field)
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		if ( ! $this->_formFields[$field] instanceof Zend_Form_Element) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' The field : '.$field.' does not exist!');
		}

		return $this->_formFields[$field]->getAttribs();
	}

	/**
	 * Adds a field decorator to a specified field name
	 *
	 * @param string $field 
	 * @param string|Zend_Decorator $decorator 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	public function addFieldDecorator($field, $decorator)
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		if ( ! $this->_formFields[$field] instanceof Zend_Form_Element) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' The field : '.$field.' does not exist!');
		}

		$this->_formFields[$field]->addDecorator($decorator);
		return $this;
	}

	/**
	 * Add multiple decorators to a field
	 *
	 * @param string $field 
	 * @param array $decorators 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	public function addFieldDecorators($field, array $decorators)
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		if ( ! $this->_formFields[$field] instanceof Zend_Form_Element) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' The field : '.$field.' does not exist!');
		}

		$this->_formFields[$field]->addDecorators($decorators);
		return $this;
	}

	/**
	 * Set multiple field decorators to a field
	 *
	 * @param string $field 
	 * @param array $decorators 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	public function setFieldDecorators($field, array $decorators)
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		if ( ! $this->_formFields[$field] instanceof Zend_Form_Element) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' The field : '.$field.' does not exist!');
		}

		$this->_formFields[$field]->setDecorators($decorators);
		return $this;
	}

	/**
	 * Get the decorators applied to a field
	 *
	 * @param string $field 
	 * @return array
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	public function getFieldDecorators($field)
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		if ( ! $this->_formFields[$field] instanceof Zend_Form_Element) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' The field : '.$field.' does not exist!');
		}

		return $this->_formFields[$field]->getDecorators();
	}

	/**
	 * Remove a field decorator from a specific field
	 *
	 * @param string $field 
	 * @param string $name 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	public function removeFieldDecorator($field, $name)
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		if ( ! $this->_formFields[$field] instanceof Zend_Form_Element) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' The field : '.$field.' does not exist!');
		}

		$this->_formFields[$field]->removeDecorator($name);
		return $this;
	}

	/**
	 * Clear field decorators from a specified field
	 *
	 * @param string $field 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	public function clearFieldDecorators($field)
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		if ( ! $this->_formFields[$field] instanceof Zend_Form_Element) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' The field : '.$field.' does not exist!');
		}

		$this->_formFields[$field]->clearDecorators();
		return $this;
	}

	/**
	 * Gets a list of fields that will be decorated
	 *
	 * @return array|boolean
	 * @access public
	 */
	public function getSelectSpecificFields()
	{
		return $this->_selectSpecificFields;
	}

	/**
	 * Sets a list of field that will be decorated
	 *
	 * @param array $fields 
	 * @return self
	 * @access public
	 */
	public function setSelectSpecificFields(array $fields)
	{
		$this->_selectSpecificFields = $fields;
		return $this;
	}

	/**
	 * Creates the Zend_Form fields for the model, can also
	 * automatically insert them into supplied Zend_Form
	 *
	 * @param Zend_Form $form [Optional]
	 * @return array|Zend_Form
	 * @access public
	 */
	public function createModelFormFields(Zend_Form $form = NULL)
	{
		// Process the fields into an array
		$fields = $this->processFields();

		if (NULL !== $form) {
			return $form->addElements($form);
		}

		return $fields;
	}

	/**
	 * Creates the zend form elements required to represent
	 * the model
	 *
	 * @param Zend_Form $form [Optional]
	 * @return string
	 * @access public
	 */
	public function render(Zend_Form $form = NULL)
	{
		$form = $this->createModelFormFields($form);
		if ($form instanceof Zend_Form) {
			return (string) $form;
		}

		$buffer = '';
		foreach ($form as $field) {
			$buffer .= $field->render();
		}

		return $buffer;
	}

	/**
	 * Renders the name with namespace
	 *
	 * @param string $name 
	 * @return string
	 * @access public
	 */
	public function renderNamespace($name)
	{
		$ns = $this->getNamespace();

		if ( ! $ns) {
			return $name;
		}

		$sep = $this->getNamespaceSeparator();
		$ns = implode($this->getNamespaceSeparator(), $ns);

		return $ns.$sep.$name;
	}

	/**
	 * Process each field and create some zend form elements
	 *
	 * @return array
	 * @access public
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	protected function processFields()
	{
		// Check for existence of a model
		if (NULL === ($model = $this->getModel())) {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' no model available to process!');
		}

		// Get the fields
		$fields = $model->getFieldsAsContainer();

		// Create output array
		$zendFormElements = array();

		// Process each form field
		foreach ($fields as $key => $value) {
			if (FALSE === $this->_selectSpecificFields or in_array($key, $this->_selectSpecificFields)) {
				$zendFormElements[$key] = $this->createZendFormElement($value);
			}
		}

		// Return the rendered elements
		return $zendFormElements;
	}

	/**
	 * Creates a Zend_Form_Element based on the Cwcl_Services_Model_Field_Abstract field
	 * supplied. Will setup value, label, description, id, name plus if the field
	 * has any errors.
	 *
	 * @param Cwcl_Services_Model_Field_Abstract|Cwcl_Services_Model_Field_ObjectInterface $field 
	 * @return Zend_Form_Element
	 * @access protected
	 * @throws Cwcl_Services_Model_Decorator_Exception
	 */
	protected function createZendFormElement($field)
	{
		// Test for iterator
		if ($iterator = ($field instanceof Cwcl_Services_Model_Field_ObjectInterface)) {
			$fieldType = 'iterator';
		}
		elseif ($field instanceof Cwcl_Services_Model_Field_Abstract) {
			// Discover the field type and filter out the garbage
			$fieldType = $field->getType();
		}
		else {
			throw new Cwcl_Services_Model_Decorator_Exception(__METHOD__.' unable to determine field type.');
		}

		// Create the basic options array
		$id = $field->getId();
		$name = $this->renderNamespace($id);

		// Get the form element label
		$label = isset($this->_labels[$id]) ? $this->_labels[$id] : $name;

		$options = array(
			'name'         => $name,
			'id'           => $name,
			'value'        => $field->getValue(),
			'description'  => $field->getDescription(),
			);

		switch ($fieldType) {
			case 'iterator' : {
				$options['options'] = $field->getValues(TRUE);
				$zendField = new Zend_Form_Element_Select($options);
				$zendField->setLabel($label);
				break;
			}
			case 'bool' : {
				$options['options'] = array(
					'yes'  => 'Yes',
					'no'   => 'No',
					);

				if (TRUE === $options['value']) {
					$options['value'] = 'yes';
				}
				elseif (FALSE === $options['value']) {
					$options['value'] = 'no';
				}
				else {
					$options['value'] = NULL;
				}

				$zendField = new Zend_Form_Element_MultiCheckbox($options);
				$zendField->setLabel($label);
				break;
			}
			case 'set' : {
				$options['options'] = $field->getEnumeration();
				$options['value'] = explode(',', $options['value']);
				$zendField = new Zend_Form_Element_MultiSelect($options);
				$zendField->setLabel($label);
				break;
			}
			case 'enum' : {
				$options['options'] = $field->getEnumeration();
				$zendField = new Zend_Form_Element_MultiSelect($options);
				$zendField->setLabel($label);
				break;
			}
			case 'auto' : {
				$options['readonly'] = 'readonly';
				$zendField = new Zend_Form_Element_Hidden($options);
				break;
			}
			case 'string' : {
				if (NULL !== ($length = $field->getLength())) {
					$options['maxlength'] = $length;
				}
			}
			case 'float' :
			case 'integer' :
			default : {
				$zendField = new Zend_Form_Element_Text($options);
				$zendField->setLabel($label);
				break;
			}
		}


		// Handle validation
		if ( ! $field->validate()) {
			$zendField->addErrors($field->getMessages());
		}

		return $zendField;
	}
}