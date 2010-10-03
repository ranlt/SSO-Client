<?php
/**
 * Services field container that stores Model field
 * objects.
 *
 * @package Cwcl Services Model Field
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Container implements ArrayAccess, Iterator, Countable, Serializable
{
	/**
	 * Creates a new field container and returns it
	 *
	 * @param array $fields 
	 * @return Cwcl_Services_Model_Field_Container
	 * @access public
	 * @static
	 */
	static public function factory(array $fields)
	{
		return new Cwcl_Services_Model_Field_Container($fields);
	}

	/**
	 * The fields within this container
	 *
	 * @var string
	 */
	protected $_fields = array();

	/**
	 * The count of elements
	 *
	 * @var integer
	 */
	protected $_count;

	/**
	 * Internal pointer of iterator
	 *
	 * @var integer
	 */
	protected $_pointer;

	/**
	 * Internal map of keys
	 *
	 * @var array
	 */
	protected $_keys;

	/**
	 * Valid status
	 *
	 * @var string
	 */
	protected $_valid;

	/**
	 * Constructs a new container ready for fields
	 *
	 * @param array $fields 
	 * @access public
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	public function __construct(array $fields)
	{
		$this->initialiseContainerFields($fields);

		// Setup the count and keys/pointer
		$this->_count = count($this->_fields);
		$this->_keys = array_keys($this->_fields);
		$this->_pointer = current($this->_keys);
	}

	/**
	 * Magic get access to the containers field object
	 *
	 * @param string $key 
	 * @return Cwcl_Services_Model_Field_Abstract|void
	 * @access public
	 */
	public function __get($key)
	{
		return isset($this->_fields[$key]) ? $this->_fields[$key] : NULL;
	}

	/**
	 * Magic set() access to a container field
	 *
	 * @param string $key 
	 * @param Cwcl_Services_Model_Field_Abstract $value 
	 * @return void
	 * @access public
	 */
	public function __set($key, Cwcl_Services_Model_Field_Abstract $value)
	{
		$this->_fields[$key] = $value;
		return;
	}

	/**
	 * Allows isset() to be used on the container
	 *
	 * @param string $key 
	 * @return boolean
	 * @access public
	 */
	public function __isset($key)
	{
		return isset($this->_fields[$key]);
	}

	/**
	 * Allows unset() to be used on the container
	 *
	 * @param string $key 
	 * @return void
	 * @access public
	 */
	public function __unset($key)
	{
		$this->$key = NULL;
		return;
	}

	/**
	 * Return the number of fields in this container
	 *
	 * @return integer
	 * @access public
	 */
	public function count()
	{
		return $this->_count;
	}

	/**
	 * Return the current key
	 *
	 * @return mixed
	 * @access public
	 */
	public function key()
	{
		return $this->_pointer;
	}

	/**
	 * Move the pointer to the next key
	 * and return it
	 *
	 * @return mixed
	 * @access public
	 */
	public function next()
	{
		$this->_pointer = next($this->_keys);
		return isset($this->_fields[$this->_pointer]) ? $this->_fields[$this->_pointer] : NULL;
	}

	/**
	 * Rewind the pointer to the first
	 * key entry
	 *
	 * @return mixed
	 * @access public
	 */
	public function rewind()
	{
		reset($this->_keys);
		$this->_pointer = current($this->_keys);
		return isset($this->_fields[$this->_pointer]) ? $this->_fields[$this->_pointer] : NULL;
	}

	/**
	 * Return the value at the curent
	 *
	 * @return void
	 * @access public
	 */
	public function current()
	{
		return $this->_fields[$this->_pointer];
	}

	/**
	 * Return the whether this containers pointer
	 * is valid
	 *
	 * @return boolean
	 * @access public
	 */
	public function valid()
	{
		return isset($this->_fields[$this->_pointer]);
	}

	/**
	 * Return the value at the current key
	 *
	 * @param string $key 
	 * @return mixed|null
	 * @access public
	 */
	public function offsetGet($key)
	{
		if ( ! isset($this->_fields[$key])) {
			return NULL;
		}

		// If the field is a Field object, use that
		if ($this->_fields[$key] instanceof Cwcl_Services_Model_Field_Abstract) {
			return $this->_fields[$key]->getValue($key);
		}

		// Use the interface
		return $this->_fields[$key]->getValues($key);
	}

	/**
	 * Set a value to the field identified by
	 * key
	 *
	 * @param string $key 
	 * @param mixed $value 
	 * @return void
	 * @access public
	 * @throws Cwcl_Services_Model_Field_Exception
	 */
	public function offsetSet($key, $value)
	{
		if ( ! $this->offsetExists($key)) {
			throw new Cwcl_Services_Model_Field_Exception(__METHOD__.' offset does not exist : '.$key);
		}

		// If the field is a Field object, use that
		if ($this->_fields[$key] instanceof Cwcl_Services_Model_Field_Abstract) {
			$this->_fields[$key]->setValue($value);
		} else {

		// Use the interface
		$this->_fields[$key]->setValues($value);
		}

		return;
	}

	/**
	 * Unset a value in the container
	 *
	 * @param string $key 
	 * @return void
	 * @access public
	 */
	public function offsetUnset($key)
	{
		$this->_fields[$key]->setValue(NULL);
		return;
	}

	/**
	 * Check at an offset in the container
	 * exists
	 *
	 * @param string $key 
	 * @return bool
	 * @access public
	 */
	public function offsetExists($key)
	{
		return isset($this->_fields[$key]);
	}

	/**
	 * Return the valid state of the container
	 *
	 * @return boolean|void
	 * @access public
	 */
	public function isValid()
	{
		if (NULL === $this->_valid) {
			return $this->validate();
		}

		return $this->_valid;
	}

	/**
	 * Validate the the fields in the container
	 *
	 * @return boolean|void
	 * @access public
	 */
	public function validate()
	{
		$result = NULL;

		foreach ($this->_fields as $value) {
			$valid = $value->validate();
			if (FALSE !== $result) {
				$result = $valid;
			}
		}

		return $this->_valid = $result;
	}

	/**
	 * Get errors from validation
	 *
	 * @return array
	 * @access public
	 */
	public function getErrors()
	{
		$errors = array();

		foreach ($this->_fields as $key => $value) {
			$errors[$key] = $value->getErrors();
		}

		return $errors;
	}

	/**
	 * Get error messages from validation
	 *
	 * @return array
	 * @access public
	 */
	public function getMessages()
	{
		$messages = array();

		foreach ($this->_fields as $key => $value) {
			$messages[$key] = $value->getMessages();
		}

		return $messages;
	}

	/**
	 * Returns all the containers values in an
	 * associated array
	 *
	 * @return array
	 * @access public
	 */
	public function getValues()
	{
		$result = array();

		foreach ($this->_fields as $key => $value) {
			if ($value instanceof Cwcl_Services_Model_Field_Abstract) {
				$result[$key] = $value->getValue();
			}
			elseif ($value instanceof Cwcl_Services_Model_Abstract) {
				$result[$key] = $value->getValues();
			}
			elseif ($value instanceof Cwcl_Services_Model_Iterator) {
				$result[$key] = $value->getValues();
			}
		}

		return $result;
	}

	/**
	 * Serialises the model for persistent storage
	 * or transmission
	 * 
	 * [SPL Serilizable] 
	 *
	 * @param array $toSerialize 
	 * @return string
	 * @access public
	 */
	public function serialize(array $toSerialize = array())
	{
		$toSerialize += array(
			'_fields'    => $this->_fields,
			'_count'     => $this->_count,
			'_pointer'   => $this->_pointer,
			'_keys'      => $this->_keys,
			'_valid'     => $this->_valid,
		);

		return serialize($toSerialize);
	}

	/**
	 * Unserialise the model based on serialised
	 * string
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
		
		return;
	}

	/**
	 * Initialise the fields based on an array description
	 *
	 * @param array $description 
	 * @return void
	 * @access protected
	 * @throws Cwcl_Services_Model_Exception
	 */
	protected function initialiseContainerFields(array $description)
	{
		$storage = array();

		foreach ($description as $key => $value) {

			// Handle known types
			$knownTypes = array(
				'bool',
				'string',
				'integer',
				'float',
				'auto',
				'enum',
				'set',
				'datetime',
				'stdclass',
				'array',
				'filetransfer',
			);

			$knownType = in_array(strtolower($value['type']), $knownTypes);
			$class = ($knownType) ? 
				'Cwcl_Services_Model_Field_'.ucfirst($value['type']) : $value['type'];

			// If the class does not exist, try and load the full type
			if ( ! class_exists($class, FALSE)) {
				if ( ! Zend_Loader_Autoloader::autoload($class)) {
					throw new Cwcl_Services_Model_Exception('Could not find Field class : '.$class);
				}
			}

			// Create field
			$field = ($knownType) ? new $class($key, $value) : new $class();

			// Check if unknown type implements the correct interface
			if ( ! $knownType) {
				if ( ! $field instanceof Cwcl_Services_Model_Field_ObjectInterface) {
					throw new Cwcl_Services_Model_Exception('Class : '.$class.' must be an implement the Cwcl_Services_Model_Field_ObjectInterface interface');
				}
			} 

			$storage[$key] = $field;
		}

		$this->_fields = $storage;

		return;
	}
}