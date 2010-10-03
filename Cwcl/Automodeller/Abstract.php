<?php
/**
 * Automodeller provides simple OO access to
 * the database. This model is lightweight
 * compared to Zend_Db_Table/Zend_Db_Row
 *
 * Automodeller now has full Object Relational
 * Mapper support
 *
 * @package  Cwcl
 * @category Automodeller
 * @uses     Cwcl_Inflector
 * @uses     Cwcl_Automodeller_Query_Abstract
 * @uses     Zend_Db
 * @uses     Cwcl_Automodeller_NS
 * @uses     Cwcl_Automodeller_Exception
 * @uses     Cwcl_Automodeller_Iterator
 * @uses     ReflectionClass
 * @author   Sam de Freyssinet
 */
abstract class Cwcl_Automodeller_Abstract implements Serializable {

	/**
	 * Given a name and and value, will transform this to an SQL where clause,
	 * that can be bound with the bindparams it stores in the $bindparams array.
	 *
	 * Supports the following:
	 * - strings
	 * - numbers
	 * - NULL
	 * - arrays
	 *
	 * @note Uses named bindparameters, so not use MySQLi native driver, but PDO
	 *
	 * @param array  $bindparams
	 * @param string $attribname
	 * @param mixed  $attribvalue
	 */
	protected static function _getWhereClauseForAttribute(array & $bindparams, $attribname, $attribvalue)
	{
		// - NULL
		if ($attribvalue === null) {
			return "$attribname IS NULL";
		}

		// - strings
		// - numbers
		if (!is_array($attribvalue)) {
			$bindparams[$attribname] = $attribvalue;
			return "$attribname = :$attribname";
		}

		// - arrays (empty)
		if (empty($attribvalue)) {
			return "0 /* $attribname was passed empty collection! */";
		} 

		// - arrays (not empty)
		$subbindkeys = array();
		foreach ($attribvalue as $subkey=>$subval) {
			$bindparams[$attribname.$subkey] = $subval;
			$subbindkeys[] = ":{$attribname}{$subkey}";
		}
		return "$attribname IN (".implode(',',$subbindkeys).")";
	}

	/**
	 * The previous query executed by all Automodeller
	 * model instances
	 *
	 * @var Cwcl_Automodeller_Query_Select
	 */
	static protected $_previousQuery;

	/**
	 * Has one relationships
	 *
	 * @var array
	 */
	protected $_hasOne = array();

	/**
	 * Has many relationships
	 *
	 * @var array
	 */
	protected $_hasMany = array();

	/**
	 * Belongs to relationships
	 *
	 * @var array
	 */
	protected $_belongsTo = array();

	/**
	 * Has and belongs to many
	 *
	 * @var array
	 */
	protected $_hasAndBelongsToMany = array();

	/**
	 * The query construct for this model
	 *
	 * @var Cwcl_Automodeller_Query_Abstract
	 */
	protected $_query;

	/**
	 * Database connection
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $_db;

	/**
	 * Zend Caching to save number of queries
	 *
	 * @var Zend_Cache
	 */
	protected $_cache;

	/**
	 * Voucher properties
	 *
	 * @var array
	 */
	protected $_storage = array();

	/**
	 * Table properties
	 *
	 * @var array
	 */
	protected $_description = array();

	/**
	 * Store the changed state
	 *
	 * @var boolean
	 */
	protected $_changed = array();

	/**
	 * Store the loaded state
	 *
	 * @var boolean
	 */
	protected $_loaded = FALSE;

	/**
	 * Controls whether the table
	 * names are plural. Should be
	 * TRUE by default
	 *
	 * @var boolean
	 */
	protected $_tableNamesPlural = TRUE;

	/**
	 * The table that stores the voucher
	 *
	 * @var string
	 */
	protected $_tableName;

	/**
	 * The name of this object
	 *
	 * @var string
	 */
	protected $_objectName;

	/**
	 * Relationships that have changed
	 * since loading
	 *
	 * @var array
	 */
	protected $_changedRelations = array();

	/**
	 * Store the saved state
	 *
	 * @var boolean
	 */
	protected $_saved = FALSE;

	/**
	 * The primary key for the table
	 *
	 * @var string
	 */
	protected $_primaryKey = 'id';

	/**
	 * Setting whether the primaryKey is
	 * auto-incremented
	 *
	 * @var boolean
	 */
	protected $_autoIncrement = FALSE;

	/**
	 * The probability (percentage) that gc will run
	 *
	 * @var integer [1-100]
	 */
	protected $_garbageCollectionProbability = 10;

	/**
	 * Controls whether __initOptions throws an exception if
	 * the supplied $key does not match an accessor method or
	 * property.
	 *
	 * @var boolean
	 */
	protected $_exceptionIfKeyNotFound = TRUE;

	/**
	 * The Time To Live for the models table description. This
	 * allows the table description to be cached for the
	 * supplied number of seconds.
	 * 
	 * Only used if a Zend_Cache object is supplied.
	 *
	 * @var integer
	 */
	protected $_cacheTableDescriptionTTL = 3600;

	/**
	 * Contstructor to create a new model
	 *
	 * @param int $id [Optional]
	 */
	public function __construct($id = NULL, array $options = array())
	{
		// Load the object name if required
		if (NULL === $this->_objectName) {
			$this->_objectName = get_class($this);
		}

		// Load the table name if required
		if (NULL === $this->_tableName) {
			$this->_tableName = $this->_resolveTableName();
		}
		// Load the default select query
		$this->_query = new Cwcl_Automodeller_Query_Select($this->_tableName.'.*', $this->_tableName);

		// Re-instate voucher
		$this->__initOptions($options);

		// Load table desciption from db
		$this->_description = $this->_getDescription();

		// Reload the voucher
		if ($id !== NULL) {
			return $this->find($id);
		}
	}

	/**
	 * Initialises the Automodeller object with dependency injection.
	 * Uses reflection to detect accessor points for the model.
	 *
	 * @param   array $options 
	 * @return  void
	 * @throws  Cwcl_Automodeller_Exception
	 */
	public function __initOptions(array $options)
	{
		foreach ($options as $key => $value) {
			// Possible options
			$method = 'set'.ucfirst($key);
			$property = '_'.$key;

			$reflection = new ReflectionClass($this);

			if ($relection->hasMethod($method)) {
				$method = $reflection->getMethod($method);
				$method->invokeArgs($this, array($value));
			}
			else if ($reflection->hasProperty($property)) {
				$property = $reflection->getProperty($property);
				$property->setValue($this, $value);
			}
			else if ($reflection->hasProperty($key)) {
				$property = $reflection->getProperty($key);
				$property->setValue($this, $value);
			}
			else {
				if ($this->_exceptionIfKeyNotFound) {
					throw new Cwcl_Automodeller_Exception(__METHOD__.' could not find accessor method or property');
				}
				continue;
			}
		}
		return;
	}

	/**
	 * Resets the model to a sterile state upon cloning
	 *
	 * @return  void
	 */
	public function __clone()
	{
		$this->reset();
	}

	/**
	 * Get the value based on the key
	 * from storage.
	 *
	 * @param string $key
	 * @return mixed
	 * @access public
	 */
	public function __get($key)
	{
		// Check relations first, Has One...
		if ($inArray = in_array($key, $this->_hasOne) OR array_key_exists($key, $this->_hasOne)) {
			return $inArray ? $this->_hasOne($key) : $this->_hasOne($this->_hasOne[$key]);
		}

		// Has many...
		if ($inArray = in_array($key, $this->_hasMany) OR array_key_exists($key, $this->_hasMany)) {
			return $inArray ? $this->_hasMany($key) : $this->_hasMany($this->_hasMany[$key]);
		}

		// Has and belongs to many... (HABTM)
		if ($inArray = in_array($key, $this->_hasAndBelongsToMany) OR array_key_exists($key, $this->_hasAndBelongsToMany)) {
			return $inArray ? $this->_hasAndBelongsToMany($key) : $this->_hasAndBelongsToMany($this->_hasAndBelongsToMany($key));
		}

		// Belongs to...
		if ($inArray = in_array($key, $this->_belongsTo) OR array_key_exists($key, $this->_belongsTo)) {
			return $inArray ? $this->_belongsTo($key) : $this->_belongsTo($this->_belongsTo[$key]);
		}

		if (array_key_exists($key, $this->_description)) {
			// $this->_getDescription fills self::$_storage with all the keys
			return $this->_fieldParse($key, $this->_storage[$key]);
		}
		else {
			return NULL;
		}
	}

	/**
	 * Set a value to this model
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 * @access public
	 */
	public function __set($key, $value)
	{
		if (array_key_exists($key, $this->_description)) {
			$this->_storage[$key] = $this->_fieldParse($key, $value);

			if ( ! in_array($key, $this->_changed)) {
				$this->_changed[] = $key;
			}

			// Set the saved state to FALSE
			$this->_saved = FALSE;
		}
	}

	/**
	 * Handles the serialization of this object
	 *
	 * @param array $toSerialize 
	 * @return void
	 */
	public function serialize(array $toSerialize = array())
	{
		$reflection = new ReflectionClass($this);
		$properties = $reflection->getProperties();
		$output = array();

		foreach ($properties as $value) {
			$output[$value->getName()] = $value->getValue();
		}

		// Merge the arrays, ensuring output overrides $toSerialize
		$output += $toSerialize;

		if (isset($output['_cache'])) {
			
		}

		return serialize($output);
	}

	/**
	 * Unserializes the string representation of this object
	 *
	 * @param  string $serializedString 
	 * @return void
	 */
	public function unserialize($serializedString)
	{
		$array = unserialize($serializedString);
		$this->__initOptions($array);
	}

	/**
	 * Magic __isset() method to handle
	 * isset($obj->key);
	 *
	 * @param mixed $key 
	 * @return boolean
	 * @access public
	 */
	public function __isset($key)
	{
		return isset($this->_storage[$key]);
	}

	/**
	 * Magic __unset() method
	 *
	 * @param string $key 
	 * @return void
	 * @author Sam de Freyssinet
	 */
	public function __unset($key)
	{
		return isset($this->_storage[$key]) ? $this->$key = NULL : NULL;
	}

	/**
	 * Gets the Zend_Db class assigned to this model
	 *
	 * @return  void|Zend_Db
	 */
	public function getDb()
	{
		return $this->_db;
	}

	/**
	 * Sets the Zend_Db class assigned to this model
	 *
	 * @param Zend_Db $db 
	 * @return self
	 */
	public function setDb(Zend_Db $db)
	{
		$this->_db = $db;
		return $this;
	}

	/**
	 * Gets the Zend_Cache object assigned to the class
	 *
	 * @return Zend_Cache
	 */
	public function getCache()
	{
		return $this->_cache;
	}

	/**
	 * Sets the Zend_Cache class to this model
	 *
	 * @param Zend_Cache $cache 
	 * @return self
	 */
	public function setCache(Zend_Cache $cache)
	{
		$this->_cache = $cache;
		return $this;
	}

	/**
	 * Get the query that has been generated within this model
	 *
	 * @return Cwcl_Automodeller_Query_Abstract
	 */
	public function getQuery()
	{
		return $this->_query;
	}

	/**
	 * Set a query to this model. Can be used to create custom
	 * more obscure queries
	 *
	 * @param Cwcl_Automodeller_Query_Abstract $query 
	 * @return self
	 */
	public function setQuery(Cwcl_Automodeller_Query_Abstract $query)
	{
		$this->_query = $query;
		return $this;
	}

	/**
	 * This should be overloaded by
	 * inherited models
	 *
	 * @param mixed $id [Optional]
	 * @return mixed
	 * @access public
	 */
	public function uniqueKey($id = NULL)
	{
		return $this->primaryKey();
	}

	/**
	 * Returns just the primaryKey
	 *
	 * @return string
	 * @access public
	 */
	public function primaryKey()
	{
		return $this->_primaryKey;
	}

	/**
	 * Is the model saved. This will be true if
	 * the model state is identical to the
	 * database record.
	 *
	 * @return boolean
	 * @access public
	 */
	public function isSaved()
	{
		return $this->_saved;
	}

	/**
	 * Is the model loaded. This will be true
	 * if the originating data was the database.
	 * This will still be true if the data
	 * is augmented after loading.
	 *
	 * @return boolean
	 * @access public
	 */
	public function isLoaded()
	{
		return $this->_loaded;
	}

	/**
	 * Has the model changed since it was loaded
	 *
	 * @return boolean
	 * @access public
	 */
	public function isChanged()
	{
		return ! $this->_changed;
	}

	/**
	 * Find a record from the database based on ID
	 *
	 * @param mixed $id
	 * @return Cwcl_Automodeller_Abstract
	 * @access public
	 */
	public function find($id = NULL)
	{
		if (NULL !== $id) {
			// Get the uniqueKey from the id type
			$uniqueKey = $this->uniqueKey($id);

			// Add the where parameter
			$this->where($uniqueKey.' = :'.$uniqueKey, $id);
		}

		// Execute the query
		return $this->_exec(TRUE);
	}

	/**
	 * Finds all the records for this table
	 *
	 * @return Cwcl_Automodeller_Iterator
	 * @access public
	 */
	public function findAll()
	{
		return $this->_exec();
	}

	/**
	 * Save this model to the database
	 *
	 * @return self
	 * @access public
	 */
	public function save()
	{
		// If there are no changes, get out of here
		if ($this->_saved) {
			return $this;
		}

		// If this model has changed data in it, save those first
		if ($this->_changed) {
			// Update statement required
			if ($this->_loaded) {

				// Set the uniqueKey
				$uniqueKey = $this->uniqueKey();

				// create the update values
				$update = array();
				foreach ($this->_changed as $key) {
					$update[$key] = $this->$key;
				}

				// Update the model database representation
				$this->_db->update($this->_tableName, $update, array($uniqueKey.' = ?' => $this->$uniqueKey));

				// Set the saved and changed states
				$this->_saved = TRUE;
				$this->_changed = array();
			}
			// Insert statement required
			else {
				// Create the insert values
				$insert = $this->getValues();

				// If this model has auto-incrementing
				if ($this->_autoIncrement) {
					unset($insert[$this->_primaryKey]);
				}

				// Insert the model into the database
				$result = $this->_db->insert($this->_tableName, $insert);

				// Reload the model from the db
				($this->_autoIncrement === TRUE) ? $this->find($this->_db->lastInsertId()) : $this->find($this->{$this->primaryKey()});
			}
		}

		// Process changed relations if there are any
		if ($this->_changedRelations) {
			foreach ($this->_changedRelations as $key => $value) {
				if ($key === 'add') {
					foreach ($value as $jt => $data) {
						$values = array
						(
							$this->foreignKey() => $this->{$this->primaryKey()},
							current($data) => key($data),
						);
						$this->_db->insert($jt, $values);
					}
				}
				elseif ($key === 'remove') {
					foreach ($value as $jt => $data) {
						$where = array
						(
							$this->foreignKey().' = '.$this->{$this->primaryKey()},
							current($data).' = '.key($data),
						);
						$_where = implode(' AND ', $where);
						$this->_db->delete($jt, $_where);
					}
				}
			}
			// Update the saved state
			$this->_saved = TRUE;

			$this->_changedRelations = array();
		}

		// Return self
		return $this;
	}

	/**
	 * Deletes the model record from the table
	 *
	 * @return boolean
	 * @access public
	 */
	public function delete()
	{
		// If this model isn't loaded, it cannot be deleted
		if ( ! $this->_loaded) {
			return FALSE;
		}

		// Get the unique key
		$primaryKey = $this->primaryKey();

		// Delete this record
		$this->_db->delete($this->_tableName, "$primaryKey = '{$this->$primaryKey}'");

		// Reset the model
		$this->reset();

		return TRUE;
	}

	/**
	 * SELECT ... statement
	 *
	 * @param string|array $select
	 * @return self
	 * @access public
	 */
	public function select($select)
	{
		$this->_query->select($select);
		return $this;
	}

	/**
	 * WHERE ... AND ... search parameters
	 *
	 * @param string|array $expression 'id = :id'|array('id = :id', 5)
	 * @param mixed $parameter [Optional] used if $expression is string
	 * @return self
	 * @access public
	 */
	public function where($expression, $parameter = NULL)
	{
		$this->_query->where($expression, $parameter);
		return $this;
	}

	/**
	 * WHERE ... OR ... search parameters
	 *
	 * @param string|array $expression 'id = :id'|array('id = :id', 5)
	 * @param mixed $parameter [Optional] used if $expression is string
	 * @return self
	 * @access public
	 */
	public function orWhere($expression, $parameter = NULL)
	{
		$this->_query->orWhere($expression, $parameter);
		return $this;
	}

	/**
	 * LIMIT n, o
	 *
	 * @param int|array $limit
	 * @param int $offset [Optional]
	 * @return self
	 * @access public
	 */
	public function limit($limit, $offset = NULL)
	{
		$this->_query->limit($limit, $offset);
		return $this;
	}

	/**
	 * ORDER BY col, direction
	 *
	 * @param string $column
	 * @param string $direction
	 * @return self
	 * @access public
	 */
	public function orderBy($column, $direction = 'DESC')
	{
		$this->_query->orderBy($column, $direction);
		return $this;
	}

	/**
	 * GROUP BY col
	 *
	 * @param string $column
	 * @param string $direction
	 * @return self
	 * @access public
	 */
	public function groupBy($column)
	{
		$this->_query->groupBy($column);
		return $this;
	}

	/**
	 * type JOIN table ON (table1.col = table2.col)
	 *
	 * @param string $type
	 * @param string $table
	 * @param array $on
	 * @return self
	 * @access public
	 */
	public function join($type, $table, $on)
	{
		$this->_query->join($type, $table, $on);
		return $this;
	}

	/**
	 * Gets the values in this model and
	 * returns them as an associative array
	 *
	 * @return array
	 * @access public
	 */
	public function getValues()
	{
		// Setup the output array
		$output = array();

		// Foreach of the storage params
		foreach ($this->_storage as $key => $value) {
			// Assign it to the array with transformations
			$output[$key] = $this->$key;
		}

		// Return the output
		return $output;
	}

	/**
	 * Set values to this model
	 *
	 * @param array $array
	 * @param Cwcl_Automodeller_Iterator $iterator [Optional]
	 * @return self
	 * @access public
	 */
	public function setValues(array $array)
	{
		// Set the values to the model
		foreach ($array as $key => $value) {
			$this->$key = $value;
		}
		return $this;
	}

	/**
	 * Checks whether this model has a relationship
	 * the supplied model
	 *
	 * @param Cwcl_Automodeller_Abstract $model (must be loaded)
	 * @return boolean
	 * @access public
	 */
	public function has(Cwcl_Automodeller_Abstract $model)
	{
		if ( ! $model->isLoaded() or ! $this->_loaded) {
			return FALSE;
		}

		// Load the join table (saves calling it many times)
		$jt = $model->joinTable($this->_tableName);
		$pk = $model->primaryKey();
		$fk = $model->foreignKey();
		$_fk = $this->foreignKey();

		// Check whether the relationship is set, but the relationship has not been saved
		if (isset($this->_changedRelations['add'][$jt][$model->$pk])) {
			return TRUE;
		}

		$query = new Cwcl_Automodeller_Query_Select('*', $jt, array($fk.' = :'.$fk => $model->$pk, $_fk.' = :'.$_fk => $this->$pk));

		// Find the join table rows
		$result = $this->_db->fetchAssoc($query, $query->_getParameters());

		// Return the result
		return (bool) $result;
	}

	/**
	 * Creates a HABTM relationship between this
	 * and the supplied model
	 *
	 * @param Cwcl_Automodeller_Abstract $model (must be loaded)
	 * @return self
	 * @access public
	 */
	public function add(Cwcl_Automodeller_Abstract $model)
	{
		if ( ! $model->isLoaded()) {
			return $this;
		}

		// Preload the object name (saves on method calls)
		$jt = $this->joinTable($model->_tableName);
		$primaryKey = $model->primaryKey();

		// Test the relationship does not already exist
		if ($this->has($model)) {
			return $this;
		}

		if (isset($this->_changedRelations['add'][$jt][$model->$primaryKey])) {
			return $this;
		}

		// Add the new relationship
		$this->_changedRelations['add'][$jt][$model->$primaryKey] = $model->foreignKey();

		// Set the saved status to FALSE
		$this->_saved = FALSE;

		// Return this
		return $this;
	}

	/**
	 * Removes a HABTM relationship between this
	 * and the supplied model
	 *
	 * @param Cwcl_Automodeller_Abstract $model (must be loaded)
	 * @return self
	 * @access public
	 */
	public function remove(Cwcl_Automodeller_Abstract $model)
	{
		if ( ! $model->isLoaded()) {
			return $this;
		}

		// Preload the object name (saves on method calls)
		$jt = $this->joinTable($model->tableName());
		$primaryKey = $model->primaryKey();

		// If the model has already been assigned to be removed, return
		if (isset($this->_changedRelations['remove'][$jt][$model->$primaryKey])) {
			return $this;
		}

		// If the model does not have a relationship to this model, return
		if ( ! $this->has($model)) {
			return $this;
		}

		// Remove the relationship
		$this->_changedRelations['remove'][$jt][$model->$primaryKey] = $model->foreignKey();

		// Set the saved status to FALSE
		$this->_saved = FALSE;

		// Return this
		return $this;
	}

	/**
	 * Grabs the previous query
	 *
	 * @return Cwcl_Automodeller_Query_Select|void
	 */
	public function previousQuery()
	{
		return Cwcl_Automodeller_Abstract::$_previousQuery;
	}

	/**
	 * Creates the FK column name for this
	 * table, based on singular table name
	 * plus the primary key
	 *
	 * @return string
	 * @access public
	 */
	public function foreignKey($table = NULL, $prefixTable = NULL)
	{
		return Cwcl_Inflector::singular($this->_tableName).'_'.$this->primaryKey();
	}

	/**
	 * Creates the join table name based on the
	 * table you are trying to join to. All
	 * join tables should be in alphabetical
	 * order, separated by an underscore '_'
	 *
	 * @param string $table
	 * @return string
	 * @access public
	 */
	public function joinTable($table)
	{
		if ($this->_tableName > $table) {
			return $table.'_'.$this->_tableName;
		}
		else {
			return $this->_tableName.'_'.$table;
		}
	}

	/**
	 * Returns the name of this table
	 *
	 * @return string
	 * @access public
	 */
	public function tableName()
	{
		return $this->_tableName;
	}

	/**
	 * Return the name of this object
	 *
	 * @return string
	 * @access public
	 */
	public function objectName()
	{
		return $this->_objectName;
	}

	/**
	 * Data is loaded into the model using the delegate
	 * pattern. The id and iterator are passed to the
	 * model. The model then requests data from the
	 * iterator based on the id
	 *
	 * @param int $id
	 * @param Cwcl_Automodeller_Iterator $iterator
	 * @return self
	 */
	public function loadIteratorResult($id, Cwcl_Automodeller_Iterator $iterator)
	{
		// Reset this model
		$this->reset();

		// Get the iterator model
		$this->setValues($iterator->_injectData($id));
		// Set the model state to loaded
		$this->_changed = array();
		$this->_saved = TRUE;
		$this->_loaded = TRUE;

		// Return this model
		return $this;
	}

	/**
	 * Resets the model back to clean factory
	 * state
	 *
	 * @return void
	 */
	public function reset()
	{
		// Wipe the storage data
		$this->_storage = array();

		// Reset the metadata
		$this->_loaded = FALSE;
		$this->_saved = FALSE;
		$this->_changed = array();
		$this->_changedRelations = array();

		return;
	}

	/**
	 * Returns a has one relation to this model
	 *
	 * @param string $relationship
	 * @return Cwcl_Automodeller_Abstract|void
	 * @throws Cwcl_Automodeller_Exception
	 */
	protected function _hasOne($relationship)
	{
		// If the model is not loaded,
		if ( ! $this->_loaded) {
			throw new Cwcl_Automodeller_Exception(__METHOD__.'() cannot load relationship on unloaded model');
		}

		// Load the relationship model
		$relationship = Cwcl_Automodeller_NS::factory($relationship, $this);

		$fk = $this->foreignKey();

		$relation = Cwcl_Automodeller_Factory::factory($relationship->name())
			->where($fk.' = :'.$fk, $this->{$this->primaryKey()})
			->find();

		// If the relation is loaded, return it else return null
		return $relation->isLoaded() ? $relation : NULL;
	}

	/**
	 * Returns a has one relation to this model
	 *
	 * @param string $relationship
	 * @return Cwcl_Automodeller_Iterator
	 * @access protected
	 * @throws Cwcl_Automodeller_Exception
	 */
	protected function _hasMany($relationship)
	{
		// If the model is not loaded,
		if ( ! $this->_loaded) {
			throw new Cwcl_Automodeller_Exception(__METHOD__.'() cannot load relationship on unloaded model');
		}

		// Get the model name
		$relationship = Cwcl_Automodeller_NS::factory(Cwcl_Inflector::singular($relationship), $this);

		$fk = $this->foreignKey();

 		return Cwcl_Automodeller_Factory::factory($relationship->name())
			->where($fk.' = :'.$fk, $this->{$this->primaryKey()})
			->findAll();
	}

	/**
	 * Returns the belongsTo relationship to this model, or NULL
	 *
	 * @param string $relationship
	 * @return Cwcl_Automodeller_Abstract|void
	 * @access public
	 * @throws Cwcl_Automodeller_Exception
	 */
	protected function _belongsTo($relationship)
	{
		// If the model is not loaded,
		if ( ! $this->_loaded) {
			throw new Cwcl_Automodeller_Exception(__METHOD__.'() cannot load relationship on unloaded model');
		}

		// Load the relation model name
		$relationship = Cwcl_Automodeller_NS::factory($relationship, $this);

		// Load the model
		$model = Cwcl_Automodeller_Factory::factory($relationship->name());
		$fk = $model->foreignKey();
		$pkValue = $this->$fk;
		$pk = $model->primaryKey();

		// Find the model
		$relation = $model->where($pk.' = :'.$pk, $pkValue)
			->find();

		return $relation->isLoaded() ? $relation : NULL;
	}

	/**
	 * Has And Belongs To Many (HABTM) relationships loads
	 * all models related to this one.
	 *
	 * @example To use HABTM relationships, you need a
	 * join table that has very strict naming conventions
	 *
	 * Assuming Model_User and Model_Address are HABTM to
	 * each other, a join table called addresses_users
	 * will be required. The table must have only two
	 * columns: address_id and user_id, notice that they
	 * are singular.
	 *
	 * The '_id' suffix is the name of the primaryKey
	 * for both.
	 *
	 * Finally you need to define :
	 * protected $_hasAndBelongsToMany = array('users')
	 * in Model_Address, and :
	 * protected $_hasAndBelongsToMany = array('addresses')
	 * in Model_User (note the plural naming again)
	 *
	 * @param string $relationship
	 * @return Cwcl_Automodeller_Iterator
	 * @throws Cwcl_Automodeller_Exception
	 */
	protected function _hasAndBelongsToMany($relationship)
	{
		// If the model is not loaded,
		if ( ! $this->_loaded) {
			throw new Cwcl_Automodeller_Exception(__METHOD__.'() cannot load relationship on unloaded model');
		}

		// Load the relationship model
		$model = Cwcl_Automodeller_Factory::factory(Cwcl_Automodeller_NS::factory(Cwcl_Inflector::singular($relationship), $this)->name());

		// Load the join table (saves calling it many times)
		$jt = $model->joinTable($this->_tableName);

		return $model->join(NULL, $jt, $jt.'.'.$model->foreignKey().' = '.$model->tableName().'.'.$model->primaryKey())
			->where(array($jt.'.'.$this->foreignKey().' = :'.$this->primaryKey() => $this->{$this->primaryKey()}))
			->findAll();
	}

	/**
	 * Execute the query stored in this model
	 *
	 * @param boolean $singleResult [Optional]
	 * @return self|Cwcl_Automodeller_Iterator
	 * @throws Cwcl_Automodeller_Exception
	 */
	protected function _exec($singleResult = FALSE)
	{
		if ($this->getDb() === NULL) {
			throw new Cwcl_Automodeller_Exception(__METHOD__.' unable to load Zend_Db from model : '.get_class($this));
		}

		// Get the results from the query
		$result = $this->getDb()->fetchAssoc($this->_query->render(), $this->_query->_getParameters());

		// Reset the query
		Cwcl_Automodeller_Abstract::$_previousQuery = $this->_query;
		$this->_query = new Cwcl_Automodeller_Query_Select;

		// Caller expects multiple results, so return an iterator
		if ( ! $singleResult) {
			return new Cwcl_Automodeller_Iterator($this->_objectName, $result);
		}

		// If $result is not an empty array, there was a successful execution
		// If the result is empty, the model should remain in the current state
		if ( ! $result) {
			return $this;
		}

		// Set the values (dirty hack to get stdObject to array)
		$current = current($result);
		$this->setValues($current);

		// Update the status
		$this->_loaded = TRUE;
		$this->_saved = TRUE;
		$this->_changed = array();

		// Return this
		return $this;
	}

	/**
	 * Gets the namespace for this model
	 *
	 * @return Cwcl_Automodeller_NS
	 */
	public function _getNamespace()
	{
		return Cwcl_Automodeller_NS::factory(get_class($this));
	}

	/**
	 * Parses the field value based on the database
	 * property
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return mixed
	 * @throws Cwcl_Automodeller_Exception
	 */
	protected function _fieldParse($field, $value)
	{
		// Get out of here quickly
		if (NULL === $value) {
			return $value;
		}

		// Create an internal method static property for caching
		static $cache;

		// Create a unique cache key for this class/field/value combo
		$cacheKey = md5(get_class($this).$field.$value);

		// If the combo has already been processed, return that
		if (isset($cache[$cacheKey])) {
			return $cache[$cacheKey];
		}

		// Get the type
		$type = $this->_description[$field]['type'];

		if (in_array($type, array('int', 'bigint', 'tinyint', 'mediumint'))) {
			$type = 'int';
		}
		else if (in_array($type, array('text', 'tinytext', 'varchar', 'char', 'longtext', 'mediumtext', 'blob', 'tinyblob', 'longblob', 'mediumblob'))) {
			$type = 'string';
		}
		else if (in_array($type, array('float', 'double', 'decimal'))) {
			$type = 'float';
		}
		else if (in_array($type, array('datetime', 'date', 'timestamp'))) {
			$type = 'string';
		}
		else {
			throw new Cwcl_Automodeller_Exception('Type '.$type.' not recognised');
		}

		// Convert the value
		settype($value, $type);

		// Cache the value for future use
		$cache[$cacheKey] = $value;

		// Return the value
		return $value;
	}

	/**
	 * Provides the description of the table
	 *
	 * @return array
	 * @throws Cwcl_Automodeller_Exception
	 */
	protected function _getDescription()
	{
		$description = $this->_db->query('DESC '.$this->_tableName);

		// Result array
		$result = array();

		// Parse each field
		foreach ($description as $field) {

			// Get the type and length
			if ( ! preg_match('/^([\w\s]+)(?:\((\d+)\))?$/i', $field['Type'], $matches)) {
					throw new Cwcl_Automodeller_Exception(__METHOD__.'() could not decode table description');
			}

			// Get the table name
			$result[$field['Field']] = array
			(
				'type'     => $matches[1],
				'length'   => isset($matches[2]) ? (int) $matches[2] : NULL,
				'null'     => $field['Null'] !== 'NO',
			);

			// Set the primary key
			if ($field['Key'] === 'PRI' AND ! isset($this->_primaryKey)) {
				$this->_primaryKey = $field['Field'];
			}

			if ($field['Key'] === 'PRI') {
				$this->_autoIncrement = ($field['Extra'] === 'auto_increment');
			}
		}

		// Setup the model properties
		$this->_storage = array_fill_keys(array_keys($result), NULL);

		// Return the result
		return $result;
	}

	/**
	 * Automatically tries to resolve the table name
	 * if no table name supplied. This is by no means
	 * foolproof
	 *
	 * @return string
	 * @access protected
	 */
	protected function _resolveTableName()
	{
		// Split the object name on Automodeller
		$names = explode('automodeller_', strtolower($this->_objectName));

		// If names only has one element 'automodeller' was not found,
		// try splitting on 'model' instead
		if (count($names) == 1) {
			$names = explode('model_', strtolower($names[0]));
		}

		// Get the last name
		$name = array_pop($names);

		// return the correct name
		return Cwcl_Inflector::plural($name);
	}
}