<?php
/**
 * Model iterator for iterating of Service Record Sets
 *
 * @package Cwcl Services Model
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Iterator implements Countable, Iterator, SeekableIterator, Serializable, Cwcl_Services_Model_Field_ObjectInterface
{
	/**
	 * Internal records array
	 *
	 * @var array
	 */
	protected $_records;

	/**
	 * Internal pointer to current record
	 *
	 * @var integer
	 */
	protected $_pointer;

	/**
	 * Internal counter of records
	 *
	 * @var integer
	 */
	protected $_count;

	/**
	 * Metadata information also returned
	 *
	 * @var array
	 */
	protected $_metaData = array();

	/**
	 * The output model
	 *
	 * @var Cwcl_Services_Model_Abstract
	 */
	protected $_model;

	protected $_keyValue = 'id';

	/**
	 * Constructs a new iterator based on
	 * the records and model type
	 *
	 * @param array $records 
	 * @param Cwcl_Services_Model_Abstract $model 
	 * @access public
	 */
	public function __construct(array $records = array(), Cwcl_Services_Model_Abstract $model)
	{
		// Setup iterator
		$this->setRecords($records)
			->setModel($model);

		$this->_pointer = 0;
		$this->_count = count($this->_records);
	}

	/**
	 * Return the count of the iterator
	 *
	 * @return integer
	 * @access public
	 */
	public function count()
	{
		return $this->_count;
	}

	/**
	 * Alias to setRecords
	 * 
	 * [Interface] Cwcl_Services_Model_Field_ObjectInterface
	 *
	 * @param array $values 
	 * @return self
	 * @access public
	 */
	public function setValues(array $values)
	{
		return $this->setRecords($values);
	}

	/**
	 * Alias to getRecords
	 * 
	 * [Interface] Cwcl_Services_Model_Field_ObjectInterface
	 *
	 * @return array
	 * @access public
	 */
	public function getValues()
	{
		return $this->getRecords(TRUE);
	}

	/**
	 * Set records to the iterator
	 *
	 * @param array $records 
	 * @return self
	 * @access public
	 */
	public function setRecords(array $records)
	{
		$this->_records = $records;
		$this->_count = count($this->_records);
		return $this;
	}

	/**
	 * Get records from the iterator. Either fully
	 * flattened to associative array, or an array
	 * of models
	 *
	 * @param boolean $assoc 
	 * @return array
	 * @access public
	 */
	public function getRecords($assoc = FALSE)
	{
		if (TRUE === $assoc) {
			return $this->_records;
		}

		$result = array();
		foreach ($this->_records as $key => $value) {
			$result[$key] = $this->_model->loadIteratorResult($value, $this->_model->getRequest());
		}

		return $result;
	}

	/**
	 * Return an associative array of selected key/value
	 * pairs. Perfect for creating Zend_Form_Multi elements
	 *
	 * @example Simple method
	 * array(
	 *   'key'    => 'id',
	 *   'value'  => 'title',
	 * );
	 * 
	 * @param array $select 
	 * @return array
	 * @access public
	 * @throws Cwcl_Services_Model_Exception
	 */
	public function getKeyValuePairs(array $select)
	{
		$result = array();

		foreach ($this->_records as $key => $value) {
			is_object($value) ? 
				$result[$value->{$select['key']}] = $value->{$select['value']} : 
				$result[$value[$select['key']]] = $value[$select['value']];
		}

		return $result;
	}

	/**
	 * Set metadata to the iterator
	 *
	 * @param array $metaData 
	 * @return self
	 * @access public
	 */
	public function setMetaData(array $metaData)
	{
		$this->_metaData = $metaData;
		return $this;
	}

	/**
	 * Get all metadata, or a specific entry
	 *
	 * @param string $key [Optional]
	 * @return array|mixed|void
	 * @access public
	 */
	public function getMetaData($key = NULL)
	{
		if (NULL === $key) {
			return $this->_metaData;
		}

		return isset($this->_metaData[$key]) ? $this->_metaData[$key] : NULL;
	}

	/**
	 * Sets a model to the iterator
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
	 * Gets the model from the iterator
	 *
	 * @return Cwcl_Services_Model_Abstract
	 * @access public
	 */
	public function getModel()
	{
		return $this->_model;
	}

	/**
	 * Return the current pointer key
	 *
	 * @return integer|boolean
	 * @access public
	 */
	public function key()
	{
		return $this->_pointer;
	}

	/**
	 * Move the pointer on one and
	 * return the item at the index
	 *
	 * @return Cwcl_Services_Model_Abstract|boolean
	 * @access public
	 */
	public function next()
	{
		$this->_pointer++;
		return $this->current();
	}

	/**
	 * Reset the pointer to the beginning
	 *
	 * @return void
	 * @access public
	 */
	public function rewind()
	{
		$this->_pointer = 0;
		return;
	}

	/**
	 * Return the current model at the
	 * current pointer
	 *
	 * @return Cwcl_Services_Model_Abstract|boolean
	 * @access public
	 */
	public function current()
	{
		if ( ! $this->valid()) {
			return FALSE;
		}

		return $this->_model->loadIteratorResult((array) $this->_records[$this->_pointer], $this->_model->getRequest());
	}

	/**
	 * Returns whether the current pointer is
	 * valid
	 *
	 * @return boolean
	 * @access public
	 */
	public function valid()
	{
		return isset($this->_records[$this->_pointer]);
	}

	/**
	 * Set the pointer to the key and return
	 * the value at that pointer
	 *
	 * @param string $key 
	 * @return Cwcl_Services_Model_Abstract|boolean
	 * @access public
	 */
	public function seek($key)
	{
		$this->_pointer = $key;
		return $this->current();
	}

	/**
	 * Serialises the object
	 *
	 * @param array $toArray [Optional]
	 * @return string
	 * @access public
	 */
	public function serialize(array $toArray = array())
	{
		$toArray += array(
			'_records'   => $this->_records,
			'_pointer'   => $this->_pointer,
			'_metaData'  => $this->_metaData,
			'_count'     => $this->_count,
			'_model'     => $this->_model,
		);

		return serialize($toArray);
	}

	/**
	 * Unserialises the object
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
}