<?php
/**
 * Automodeller Iterator provides iteration
 * support for multiple Automodeller model result
 * sets.
 *
 * @package  Cwcl
 * @category Automodeller
 * @author   Sam de Freyssinet
 */
class Cwcl_Automodeller_Iterator implements Countable, Iterator, SeekableIterator, ArrayAccess {

	/**
	 * The rows in the database result
	 *
	 * @var array
	 */
	protected $_rows;

	/**
	 * The number of rows in this iterator
	 *
	 * @var int
	 */
	protected $_count;

	/**
	 * The current pointer value
	 *
	 * @var int
	 */
	protected $_index;

	/**
	 * The type of model to produce from
	 * the data
	 *
	 * @var string
	 */
	protected $_type;

	/**
	 * Constructor
	 *
	 * @param string $type 
	 * @param array $data 
	 * @access public
	 */
	public function __construct($type, array $data)
	{
		// Setup the model type
		$this->_type = $type;

		// Setup the count
		$this->_count = count($data);

		// Reset the index
		$this->_index = 0;

		// Apply the data to the model
		$this->_insertData($data);
	}

	/**
	 * Serialization method, returns the properties
	 * to be persistently stored
	 *
	 * @return array
	 * @access public
	 */
	public function __sleep()
	{
		return array('_rows', '_count', '_index', '_type');
	}

	/**
	 * Count of the number of rows
	 * 
	 * Countable
	 *
	 * @return int
	 * @access public
	 */
	public function count()
	{
		return $this->_count;
	}

	/**
	 * Current item at current pointer
	 *
	 * Iterator
	 * 
	 * @return Cwcl_Automodeller_Abstract
	 * @access public
	 */
	public function current()
	{
		return $this->offsetGet($this->_index);
	}

	/**
	 * Move the pointer onto the next
	 * index
	 * 
	 * Iterator
	 *
	 * @return self
	 * @access public
	 */
	public function next()
	{
		$this->_index++;
		return $this;
	}

	/**
	 * Move the pointer backwards by one
	 *
	 * @return self
	 * @access public
	 */
	public function previous()
	{
		$this->_index--;
		return $this;
	}

	/**
	 * Rewind the index to the zero
	 * 
	 * Iterator
	 *
	 * @return self
	 * @access public
	 */
	public function rewind()
	{
		$this->_index = 0;
		return $this;
	}

	/**
	 * Is the current index valid
	 * 
	 * Iterator
	 *
	 * @return boolean
	 * @access public
	 */
	public function valid()
	{
		return ! (($this->_index >= $this->_count) OR $this->_index < 0);
	}

	/**
	 * Get the current key pointer
	 * 
	 * Iterator
	 *
	 * @return int
	 * @access public
	 */
	public function key()
	{
		return $this->_index;
	}

	/**
	 * Seek within the iterator to the
	 * desired key
	 * 
	 * SeekableIterator
	 *
	 * @param string $key 
	 * @return boolean
	 * @access public
	 */
	public function seek($key)
	{
		if ($this->offsetExists($key)) {
			$this->_index = $key;
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Return the row at the index supplied
	 * 
	 * ArrayAccess
	 *
	 * @param int $key 
	 * @return Cwcl_Automodeller_Abstract|void
	 * @access public
	 */
	public function offsetGet($key)
	{
		if ($this->offsetExists($key)) {
			return Cwcl_Automodeller_Factory::factory($this->_type)->loadIteratorResult($key, $this);
		}
		
		return NULL;
	}

	/**
	 * Offset Set is disabled as this
	 * iterator is readonly
	 * 
	 * ArrayAccess
	 *
	 * @param string $key 
	 * @param mixed $value 
	 * @return void
	 * @access public
	 * @throws Cwcl_Automodeller_Exception
	 */
	public function offsetSet($key, $value)
	{
		throw new Cwcl_Automodeller_Exception(__METHOD__.'('.$key.', '.$value.') Cwcl_Automodeller_Iterator is read only!');
	}

	/**
	 * Checks for the existence of an offset
	 * 
	 * ArrayAccess
	 *
	 * @param string $key 
	 * @return boolean
	 * @access public
	 */
	public function offsetExists($key)
	{
		return array_key_exists($key, $this->_rows);
	}

	/**
	 * Unsets an index value from the iterator.
	 * Disabled due to readonly
	 * 
	 * ArrayAccess
	 *
	 * @param string $key 
	 * @return void
	 * @access public
	 * @throws Cwcl_Automodeller_Exception
	 */
	public function offsetUnset($key)
	{
		throw new Cwcl_Automodeller_Exception(__METHOD__.'('.$key.') Cwcl_Automodeller_Iterator is read only!');
	}

	/**
	 * Return the values of this iterator as an
	 * array. Optionally you can flatten the
	 * models within the array as well, resulting
	 * in a pure multi-dimensional array
	 *
	 * @param boolean $flattenModels 
	 * @return array
	 * @access public
	 */
	public function getValues($flattenModels = FALSE)
	{
		$output = $this->_rows;

		if ($flattenModels) {
			return $output;
		}

		foreach ($output as $key => $value) {
			$output[$key] = $this->offsetGet($key);
		}

		return $output;
	}

	/**
	 * Injects the data from the iterator into
	 * the model
	 *
	 * @param string $key 
	 * @return array
	 * @access public
	 * @throws Cwcl_Automodeller_Exception
	 */
	public function _injectData($key)
	{
		if ($this->offsetExists($key)) {
			return $this->_rows[$key];
		}

		throw new Cwcl_Automodeller_Exception(__METHOD__.'('.$key.') could not load data from iterator');
	}

	/**
	 * Inserts the data supplied into this model
	 *
	 * @param array $data 
	 * @return void
	 * @access protected
	 */
	protected function _insertData(array $data)
	{
		$this->_rows = array();
		foreach ($data as $entry) {
			$this->_rows[] = $entry;
}
	}
}
// End Cwcl_Automodeller_Iterator