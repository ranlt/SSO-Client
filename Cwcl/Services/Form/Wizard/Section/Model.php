<?php

class Cwcl_Services_Form_Wizard_Section_Model
	extends Cwcl_Services_Form_Wizard_Section_Abstract
{
	/**
	 * Constant used to decorate all models
	 */
	const DECORATE_DEFAULT = 100;

	/**
	 * The model(s) that page interfaces
	 * with
	 *
	 * @var array
	 */
	protected $_models = array();

	/**
	 * The decorators for one or all models.
	 * 
	 *
	 * @var string
	 */
	protected $_decorators = array();

	/**
	 * Serialises the model for persistent storage
	 * or transmission
	 *
	 * [SPL] Serializable
	 * 
	 * @param array $toSerialize 
	 * @return string
	 * @access public
	 */
	public function serialize(array $toSerialize = array())
	{
		// Serialised properties
		$toSerialize += array(
			'_models'      => $this->_models,
			'_decorators'  => $this->_decorators,
			);

		// Return the serialized string
		return parent::serialize($toSerialize);
	}

	/**
	 * Return a decorator for a specific model
	 * id
	 *
	 * @param string $id The id of the model to get the decorator for
	 * @return Cwcl_Services_Model_Decorator_Form|void
	 * @access public
	 */
	public function getDecoratorFor($id)
	{
		return isset($this->_decorators[$id]) ? $this->_decorators[$id] : NULL;
	}

	/**
	 * Sets a decorator for an ID, or use self::DECORATE_DEFAULT to apply
	 * $decorator to all unspecified models
	 *
	 * @param string|integer $id 
	 * @param Cwcl_Services_Model_Decorator_Form $decorator 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Form_Wizard_Exception
	 */
	public function setDecoratorFor($id, Cwcl_Services_Model_Decorator_Form $decorator)
	{
		if ($id === self::DECORATE_DEFAULT) {
			$this->_decorators[$id] = $decorator;
			return $this;
		}

		if ( ! isset($this->_models[$id])) {
			throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' the id : '.$id.' was not found in this page');
		}

		$this->_decorators[$id] = $decorator;
		return $this;
	}

	/**
	 * Get all of the decorators applied to this
	 * page
	 *
	 * @return array
	 * @access public
	 */
	public function getDecorators()
	{
		return $this->_decorators;
	}

	/**
	 * Set multiple decorators as an array
	 *
	 * @param array $array array of decorators
	 * @return self
	 * @access public
	 */
	public function setDecorators(array $array)
	{
		foreach ($array as $key => $value) {
			$this->setDecoratorFor($key, $value);
		}

		return $this;
	}

	/**
	 * Gets the values of all models on this page
	 *
	 * @return array
	 * @access public
	 */
	public function getValues()
	{
		$values = array();

		foreach ($this->_models as $key => $models) {
			$modelValues = $models->getValues();
			foreach ($modelValues as $_k => $_v) {
				$values[$key.'.'.$_k] = $_v;
			}
		}

		return $values;
	}

	/**
	 * Set the values to this page, names are namespaced
	 * 
	 * @example
	 * Model address name : foo
	 * Property name      : bar
	 * 
	 * The array key to set a value to model 'foo', property 'bar' is set as such
	 * array('foo.bar' => $myValue);
	 *
	 * @param array $array 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Form_Wizard_Exception
	 */
	public function setValues(array $array)
	{
		// Parse the values from a key that is model.property formatted
		$parsedArray = array();
		foreach ($array as $key => $value) {
			$decoded = explode('.', $key);

			$model = array_shift($decoded);
			$property = array_shift($decoded);

			if (NULL === $field) {
				throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' error parsing the key : '.$key);
			}

			$parsedArray[$model][$property] = $value;
		}

		// Now apply the values
		foreach ($parsedArray as $key => $value) {
			if ( ! isset($this->_models[$key])) {
				throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' there is no model set to the key : '.$key);
			}

			// Try to set the values to the model, catch any exceptions
			try {
				$this->_models[$key]->setValues($value);
			}
			catch (Cwcl_Services_Model_Exception $e) {
				throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' there was a problem setting the values to the model : '.$key.' with message : '.$e->getMessage());
			}
		}

		return $this;
	}

	/**
	 * Get the model based on the name
	 *
	 * @param string $name 
	 * @return Cwcl_Services_Model_Abstract
	 * @access public
	 */
	public function getModel($name)
	{
		return (isset($this->_models[$name])) ? $this->_models[$name] : NULL;
	}

	/**
	 * Set a model to the page
	 *
	 * @param string $name the address name for the model (for setting/getting values)
	 * @param Cwcl_Services_Model_Abstract|Cwcl_Services_Model_Iterator $model 
	 * @return self
	 * @access public
	 */
	public function setModel($name, $model)
	{
		$this->_models[$name] = $model;
		return $this;
	}

	/**
	 * Get all the models on this page
	 *
	 * @return array
	 * @access public
	 */
	public function getModels()
	{
		return $this->_models;
	}

	/**
	 * Set an array of models to this page
	 *
	 * @param string $models 
	 * @return self
	 * @access public
	 */
	public function setModels(array $models)
	{
		foreach ($models as $key => $value) {
			$this->setModel($key, $value);
		}
		return $this;
	}

	/**
	 * The pages valid state based on the models contained
	 * within
	 *
	 * @return void|boolean
	 * @access public
	 */
	public function isValid()
	{
		$result = NULL;
		foreach ($this->_models as $key => $value) {
			$valid = $value->valid();
			if (FALSE !== $result) {
				$result = $valid;
			}
		}

		return $result;
	}

	/**
	 * Renders the page to a string ready for display
	 *
	 * @param Zend_Form $form
	 * @return array
	 * @access public
	 * @throws Cwcl_Services_Form_Wizard_Exception
	 */
	public function render(Zend_Form $form)
	{
		// Check for existence of decorators
		if ( ! ($decorators = $this->getDecorators())) {
			throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' no decorators found, unable to render');
		}

		// For each model, render form using decorator
		$models = $this->getModels();
		$fields = array();

		// Foreach model in the page, render the model with a decorator
		foreach ($models as $key => $value) {

			// Try to get a decorator for the model, else use default, else throw exception
			if (isset($decorators[$key])) {
				$decorator = $decorators[$key];
			}
			elseif (isset($decorators[self::DECORATE_DEFAULT])) {
				$decorator = $decorators[self::DECORATE_DEFAULT];
			}
			else {
				throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' unable to locate decorator for model with id : '.$id);
			}

			// Try to render the decorator
			try {
				$decorator->setModel($value);
				$fields += $decorator->createModelFormFields();
			}
			catch (Cwcl_Services_Model_Decorator_Exception $e) {
				throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' unable to render form due to Decorator exception : '.$e->getMessage());
			}

			// Add any error messages to the form
			$form->addErrorMessages($value->getMessages());
		}

		// Add elements to form object
		$form->addElements($fields);

		// Create a new display group for this section
		$form->addDisplayGroup(array_keys($fields), 'id', array('legend' => $this->getTitle(), 'description' => $this->getDescription()));


		// Apply rendered form to the view
		return $fields;
	}
}