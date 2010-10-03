<?php
/**
 * Sso_Model_Iterator model that stores multiple
 * Sso_Model_Base objects, providing the Countable and Iterator
 * interfaces
 *
 * @package Sso_Mode
 * @implements Countable, Iterator, SeekableIterator, ArrayAccess
 */
class Sso_Model_Iterator implements Countable, Iterator, SeekableIterator, ArrayAccess
{
	/**
	 * Current index of the iterator
	 *
	 * @var integer
	 */
	protected $_index;

	/**
	 * Array of records in this iterator
	 *
	 * @var array
	 */
	protected $_records;

	/**
	 * Count of the records
	 *
	 * @var integer
	 */
	protected $_count;

	/**
	 * Additional metadata for this
	 * iterator
	 *
	 * @var array
	 */
	protected $_metadata;

	/**
	 * The model type to return from each record
	 *
	 * @var string
	 */
	protected $_model;

	/**
	 * Constructs the iterator ready for use.
	 *
	 * @param string|Sso_Model_Base|array $model
	 * @param array $data [Optional]
	 */
	public function __construct($model, array $records = array())
	{
		// Dirty hack for now (must remove when pagination is working properly)
		$rawData = array();

		// If model is an array, all the data is there
		if (is_array($model)) {
			// Copy the model data into Raw
			$rawData = $model;

			// Get the contentType and destroy array element
			$model = $rawData['contentType'];
			unset($rawData['contentType']);

			// Get the real records and destroy array element
			$records = $rawData['data'];
			unset($rawData['data']);

		}

		// Get the model name
		$this->_model = $this->_formatClassName($model);

		// Assign the data to the store
		$this->setRecords($records);

		// Set the metadata
		$this->_setMetadata($rawData);
	}

	/**
	 * Magic __get() method to allow
	 * access to metadata properties
	 *
	 * @param string $key
	 * @return mixed|void
	 */
	public function __get($key)
	{
		if (isset($this->_metadata[$key])) {
			return $this->_metadata[$key];
		}
		else {
			return NULL;
		}
	}

	/**
	 * Handles serialisation of the model
	 *
	 * @return array
	 * @access public
	 */
	public function __sleep()
	{
		return array('_index', '_records', '_count', '_metadata', '_model');
	}

	/**
	 * Checks the model is valid and formats it for use
	 *
	 * @param string|object $name
	 * @return string
	 * @throws Sso_Model_Exception
	 */
	protected function _formatClassName($model)
	{
		// If the model is not an object, format the name
		if ( ! is_object($model)) {
			$model = Sso_Model_Base::getModelName(Sso_Inflector::singular($model));
			$model = new $model;
		}

		// Test that the model is of the correct type
		if ($model instanceof Sso_Model_Base) {
			return get_class($model);
		}
		else {
			// Throw an exception if the basename is not right
			throw new Sso_Model_Exception('Object supplied is not an instance of Sso_Model_Base');
		}
	}

	/**
	 * Sets the metadata to the iterator
	 * and also figures out pagination if
	 * enough data is available.
	 *
	 * @param array $metadata
	 * @return void
	 */
	protected function _setMetadata(array $metadata)
	{
		// Apply the metadata to the model
		$this->_metadata = $metadata;

		// There was some metadata
		if ($this->_metadata && isset($this->_metadata['limit']) && $this->_metadata['limit'] > 0) {
			// If there is a total of search results supplied
			if (isset($this->_metadata['total']) AND $this->_metadata['total'] > 0)
			{
				// Discover if pagination can be used
				if (isset($this->_metadata['offset']) AND isset($this->_metadata['limit'])) {
					// Calculate the total number of pages
					$this->_metadata['totalPages'] = (int) ceil($this->_metadata['total'] / $this->_metadata['limit']);
					$this->_metadata['currentPage'] = ($this->_metadata['offset'] > 0) ? (int) floor($this->_metadata['offset'] / $this->_metadata['limit'])+1 : 1;
				}
			}
		}
		// There was no metadate - either there was a problem, or pagination wasn't required
		else {
			$data = array_fill_keys(array('totalPages', 'currentPage'), 1);
			$data['offset'] = 0;
			$data['total'] = $data['limit'] = $this->_count;
			$this->_metadata = $data;
		}
	}

	/**
	 * Clears the iterator of all records
	 *
	 * @return void
	 */
	protected function _clearRecords()
	{
		$this->_records = array();
		$this->_index = 0;
		$this->_count = 0;
	}

	/**
	 * Sets the supplied records to the iterator
	 *
	 * @param array $records
	 * @return void
	 */
	public function setRecords(array $records)
	{
		// Clear any existing records
		$this->_clearRecords();

		// Set the records
		$this->_records = $records;

		// Create the count value
		$this->_count = count($this->_records);
	}


	/**
	 * Return the record at the current index
	 *
	 * Iterator: current
	 *
	 * @return Sso_Model_Base
	 */
	public function current()
	{
		// Return record at the current index
		return $this->offsetGet($this->_index);
	}

	/**
	 * Move the index to the next record
	 *
	 * Iterator: next
	 *
	 * @return self
	 */
	public function next()
	{
		++$this->_index;
		return $this;
	}

	/**
	 * Move the index to the previous record
	 *
	 * (Pseudo)Iterator : prev
	 * - this is not actually in the iterator interface, but should be!!!
	 *
	 * @return self
	 */
	public function prev()
	{
		--$this->_index;
		return $this;
	}

	/**
	 * Rewind the index to the first record
	 *
	 * Iterator: rewind
	 *
	 * @return self
	 */
	public function rewind()
	{
		// rewind the current index to beginning
		$this->_index = 0;
		return $this;
	}

	/**
	 * Return the current index
	 *
	 * Iterator: key
	 *
	 * @return integer
	 */
	public function key()
	{
		// Return the current index
		return $this->_index;
	}

	/**
	 * Seeks an index within the records,
	 * sets the index if the record exists
	 *
	 * SeekableIterator: seek
	 *
	 * @param integer $index
	 * @return boolean
	 */
	public function seek($index)
	{
		// If the index exists
		if ($this->offsetExists($index)) {
			// Set the current index to that value and return TRUE
			$this->_index = $index;
			return TRUE;
		}
		else {
			// Return FALSE on fail
			return FALSE;
		}
	}

	/**
	 * Checks if the current index is valid
	 *
	 * Iterator: valid
	 *
	 * @return boolean
	 */
	public function valid()
	{
		// Return whether the current index is valid
		return $this->offsetExists($this->_index);
	}

	/**
	 * Gets a record based on the offset supplied
	 *
	 * ArrayAccess: offsetGet
	 *
	 * @param integer $offset
	 * @return Sso_Model_Base
	 */
	public function offsetGet($offset)
	{
		if ($this->seek($offset)) {
			$model = new $this->_model;
			return $model->setValues($this->_records[$this->_index], TRUE);
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Required by the ArrayAccess interface
	 *
	 * ArrayAccess: offsetSet
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 * @throws Sso_Model_Exception
	 */
	public function offsetSet($offset, $value)
	{
		throw new Sso_Model_Exception('Sso results are read-only!');
	}

	/**
	 * Required by the ArrayAccess interface
	 *
	 * ArrayAccess: offsetUnset
	 *
	 * @param mixed $offset
	 * @return void
	 * @throws Sso_Model_Exception
	 */
	public function offsetUnset($offset)
	{
		throw new Sso_Model_Exception('Sso results are read-only!');
	}

	/**
	 * Checks whether the supplied offset exists
	 *
	 * ArrayAccess : offsetExists
	 *
	 * @param integer $offset
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		// If there are records
		if ($this->_count > 0) {
			// Setup the max and min boundaries
			$max = $this->_count-1;
			$min = 0;
			// Return the result
			return ! ($offset < $min OR $offset > $max);
		}
		// Return FALSE if no records
		return FALSE;
	}

	/**
	 * Get the number of items in this iterator.
	 * Required by the Countable interface.
	 *
	 * You can use either $iterator->count() or count($iterator)
	 *
	 * @return integer
	 */
	public function count()
	{
		return $this->_count;
	}

	/**
	 * Flatten this iterator to an array of Sso_Base_Models
	 *
	 * @return array
	 */
	public function getRecords()
	{
		if ($this->_count > 0) {
			$result = array();
			foreach ($this->_records as $key => $value) {
				$result[$key] = $this->offsetGet($key);
			}
			return $result;
		}
		else {
			$this->_records;
		}
	}

	/**
	 * Get the unprocessed records.
	 */
	public function getRecordsPlain()
	{
	   return $this->_records;
	}
}