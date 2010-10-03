<?php
/**
 * Data mapper class for SSO results
 * 
 * @package Sso
 *
 */
class Sso_Model_Base
{
	/**
	 * MultiCall model pool
	 *
	 * @var array
	 * @static
	 */
	static protected $_multiCurls = array();

	/**
	 * The namespace for the Sso Models
	 *
	 * @var string|bool
	 * @static
	 */
	static protected $_namespace = 'Sso';

	/**
	 * Factory method
	 *
	 * @param string $objectClass 
	 * @param array $id [Optional]
	 * @param boolean $multiCurl [Optional] controls whether to load this model using curl_multi
	 * @return Sso_Model_Base
	 * @static
	 */
	public static function factory($objectClass = 'Base', $id = null, $multiCurl = false)
	{
		// Generate the class name
		$class = Sso_Model_Base::getModelName($objectClass);
		// create empty model
		return new $class($id, $multiCurl);
	}

	/**
	 * Cached factory method
	 *
	 * @param string $objectClass 
	 * @param array $id [Optional]
	 * @param boolean $multiCurl [Optional] controls whether to load this model using curl_multi
	 * @return Sso_Model_Base
	 */
	public static function factoryCached($objectClass = 'Base', $id = null, $multiCurl = false)
	{
		if (NULL !== $id && Zend_Registry::isRegistered('cache')) {
			$cache = Zend_Registry::get('cache');
			$cacheId = 'ModelFactoryCache__' . strtolower($objectClass) . '__';
			$cacheId .= is_string($id) ? md5($id) : md5(serialize($id));
			if (FALSE === ($obj = $cache->load($cacheId))) {
				$obj = self::factory($objectClass, $id, $multiCurl);
				if ($obj->isLoaded() && Zend_Registry::isRegistered('mycwConfig')) {
					$cacheConfig = Zend_Registry::get('mycwConfig')->modelcache->toArray();
					$cacheTimeout = 0;
					if (isset($cacheConfig[strtolower($objectClass)])) {
						$cacheTimeout = (int) $cacheConfig[strtolower($objectClass)];
					} elseif (isset($cacheConfig['default'])) {
						$cacheTimeout = (int) $cacheConfig['default'];
					}
					if ($cacheTimeout > 0) {
						$cache->save($obj, $cacheId, array(), $cacheTimeout);
					}
				}
			}
			return $obj;
		}
		self::factory($objectClass, $id, $multiCurl);
	}

	/**
	 * Sets the namespace for this model
	 *
	 * @param string|bool $namespace
	 * @return void
	 * @static
	 */
	public static function setNamespace($namespace)
	{
		// Filter out empty namespaces
		if (is_string($namespace) AND $namespace === '') {
			$namespace = FALSE;
		}

		Sso_Model_Base::$_namespace = $namespace;
	}

	/**
	 * Gets the namespace for this model
	 *
	 * @return string
	 * @static
	 */
	public static function getNamespace()
	{
		return Sso_Model_Base::$_namespace;
	}

	/**
	 * Generates the model name based on the
	 * objectClass supplied and the namespace
	 * setting
	 *
	 * @param string $objectClass 
	 * @return string
	 * @static
	 */
	public static function getModelName($objectClass)
	{
		// Return the formatted name
		return (Sso_Model_Base::$_namespace === FALSE) ? 'Model_'.ucfirst($objectClass) : Sso_Model_Base::$_namespace.'_Model_'.ucfirst($objectClass);
	}

    /**
     * Internal storage for data
     *
     * @var array
     */
    protected $_storage = array();
    
    /**
     * Name of the object class from LDAP
     *
     * @var string
     */
	protected $_objectClass = '';

	/**
	 * Models internal mapping. This property
	 * needs to define all of the available
	 * properties, including any aliases
	 *
	 * @var array
	 */
	protected $_mapping = array();

	/**
	 * Validation for this model. Uses the Zend_Validate
	 * library to perform validation and a straightforward
	 * syntax:
	 * 
	 * @example
	 * 
	 * array(
	 *      'field1'     => 'StringLength[1,200] required', // Field is required, string must be between 1 and 200 in length
	 *      'field2'     => 'Between[0,100]',               // Field is not required, must be integer between 0,100
	 * );
	 * 
	 * @var array
	 */
	protected $_validation = array();

	/***
	 * Default values for fields, if not set by incoming data
	 */
	protected $_fieldDefaults = array();
	
	/**
	 * Has many relationship
	 *
	 * @var array
	 * @example array('roles');
	 */
	protected $_hasMany = array();

	/**
	 * Has one relationship
	 *
	 * @var array
	 * @example array('token')
	 */
	protected $_hasOne = array();

	/**
	 * Belongs to relationship
	 *
	 * @var array('organisation')
	 */
	protected $_belongsTo = array();

	/**
	 * Name of the field describing the belongs to relationship 
	 * @var array('organisation' => 'parent')
	 */
	protected $_belongsToFields = array();

	/**
	 * Allow these fields to be sent to the service layer even if empty.
	 *
	 * @var array
	 */
	protected $_allowEmpty = array();

	/**
	 * Sso client to use for requests
	 *
	 * @var Sso_Client
	 */
	protected $_ssoClient = NULL;

	/**
	 * Internal state indicating whether data has been loaded or set
	 *
	 * @var boolean
	 */
	protected $_loaded;

	/**
	 * Internal state indicating whether data has been saveed or not
	 *
	 * @var boolean
	 */
	protected $_saved;

	/**
	 * A store of changed properties
	 *
	 * @var array
	 */
	protected $_changed = array();

	/**
	 * Stores the last error, cleared before each new request
	 *
	 * @var Sso_Error
	 */
	protected $_error = NULL;

	/**
	 * Iternal setting for registering this as part of a multi curl request
	 *
	 * @var boolean
	 */
	protected $_multiCurl = FALSE;

	/**
	 * Sso Request object for use with multiCurl requests
	 *
	 * @var Sso_Request
	 */
	protected $_ssoRequest;

	/**
	 * State property for multiCurl
	 *
	 * @var boolean
	 */
	protected $_saveModel = FALSE;

	/**
	 * Order by clauses to sort searches
	 *
	 * @var array
	 */
	protected $_orderBy = array();
	
	/**
	 * Limit and offset
	 *
	 * @var array
	 */
	protected $_limit = array();

	/**
	 * Errors from validation
	 * 
	 * @var array
	 */
	protected $_errors = array();

	/**
	 * The default iterator to use
	 *
	 * @var string
	 */
	protected $_defaultIterator = 'Sso_Model_Iterator';

	/**
	 * Create new one of these bad bois!
	 *
	 * @param array $id
	 */
	public function __construct($id = NULL, $multiCurl = FALSE)
	{
		// Get the real class name
		$this->_objectClass = $this->getObjectClass();

		// Assign the multiCurl status
		$this->_multiCurl = (bool) $multiCurl;

		// Initialise the Sso_Client
		$this->__initialise($id);
    }

	/**
	 * Initialise the model, setup the Sso_Client
	 *
	 * @return void
	 */
	public function __initialise($id = NULL)
	{
		// Load an Sso_Client
		$this->_ssoClient = Zend_Registry::isRegistered('Sso_Client') ? Zend_Registry::get('Sso_Client') : Sso_Client::getInstance();

		if ($id !== NULL) {
			// Create the Sso_Request object
			$this->_ssoRequest = new Sso_Request(array(
				'urlParams' => array($this->_objectClass, $id),
				'allowedErrors' => array(400, 404)
			));

			// Load the object
			$this->loadResult();
		}
	}

	/**
	 * Wrapper for setting stored values
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value)
	{
		// Give access to selected restricted properties
		if (in_array($key, array('error'))) {
			$key = '_'.$key;
			return $this->$key;
		}

		$method = 'set' . ucfirst($key);
		if (method_exists($this, $method)) {
			$this->$method($value);
		}
		else {
			// Trying to access property (reassign to _key so key is retained for exception)
			if ($_key = $this->_resolveMap($key)) {
				$this->_storage[$_key] = $value;
				$this->_changed[] = $_key;
				$this->_saved = FALSE;
			}
			else {
				throw new Sso_Model_Exception('Trying to access a property that does not exist : '.$key);
			}
		}
	}

	/**
	 * Wrapper for accessing stored values
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		// First check relations
		// Has Many
		if (in_array($key, $this->_hasMany)) {
			return $this->findChildren(Sso_Inflector::singular($key));
		}
		// Has One
		elseif (in_array($key, $this->_hasOne)) {
			return $this->findChildren($key);
		}

		// Give access to predefined properties
		if (in_array($key, array('ssoRequest', 'saved', 'loaded', 'changed', 'hasOne', 'hasMany', 'belongsTo', 'saveModel'))) {
			$key = '_'.$key;
			return $this->$key;
		}

		$method = 'get' . ucfirst($key);

		if (method_exists($this, $method)) {
			return $this->$method();
		}
		else {
			// trying to access data property

			if ($key = $this->_resolveMap($key)) {
				return isset($this->_storage[$key]) ? $this->_storage[$key] : NULL;
			} elseif (isset($this->_fieldDefaults[$key])) {
				return $this->_fieldDefaults[$key];
			} else {
				return '';
			}
		}
	}

	/**
	 * This provides direct access to the storage
	 * array. It should ONLY be used if you need
	 * to access the storage array directly.
	 * 
	 * The justification of this method is due to
	 * the __get() magic method above, which provides
	 * magic property linking to getMethods() within
	 * the model. THIS SHOULD BE REFACTORED IN FUTURE
	 *
	 * @param string $key 
	 * @return mixed|null
	 * @access protected
	 */
	protected function _get($key)
	{
		return isset($this->_storage[$key]) ? $this->_storage[$key] : NULL;
	}

	/**
	 * Like _get(), this method provides direct
	 * access to saving values to the storage array.
	 * The justification is identical to _get()
	 *
	 * @param string $key 
	 * @param mixed $value 
	 * @return void
	 * @access protected
	 */
	protected function _set($key, $value)
	{
		// Trying to access property (reassign to _key so key is retained for exception)
		if ($_key = $this->_resolveMap($key)) {
			$this->_storage[$_key] = $value;
			$this->_changed[] = $_key;
			$this->_saved = FALSE;
		}
		else {
			throw new Sso_Model_Exception('Trying to access a property that does not exist : '.$key);
		}
	}

	/**
	 * Wrapper for checking setness of a key
	 *
	 * @param string $key
	 * @return bool
	 */
	public function __isset($key)
	{
		$method = 'get' . ucfirst($key);
		if (method_exists($this, $method)) {
			return true;
		} else {
			return array_key_exists($key, $this->_storage);
		}
	}
    
	/**
	 * Wrapper for unsetting a key
	 *
	 * @param string $key
	 */
	public function __unset($key)
	{
		$method = 'set' . ucfirst($key);
		if (method_exists($this, $method)) {
			$this->$method(null);
		} else {
			unset($this->_storage[$key]);
		}
	}

	/**
	 * Sleep magic method to define which
	 * properties are serialized upon sleep
	 *
	 * @return array
	 */
	public function __sleep()
	{
		// Return a list of class properties that should be serialized
		return array('_storage', '_objectClass', '_loaded', '_saved', '_mapping', 
			'_orderBy', '_limit', '_hasOne', '_hasMany', '_belongsTo', '_saveModel', 
			'_error', '_changed', '_multiCurl', '_fieldDefaults', '_ssoRequest');
	}

	/**
	 * Wakeup magic method to re-init the Sso_Client
	 *
	 * @return void
	 */
	public function __wakeup()
	{
		// Load an Sso_Client
		$this->_ssoClient = Sso_Client::getInstance();
	}

	/**
	 * Attaches a model ready for Multi Curl.
	 *
	 * @param Sso_Model_Base $model 
	 * @return self
	 */
	public function attach(Sso_Model_Base $model)
	{
		// If multiCurl is not set, do not attach
		if ( ! $model->getMultiCurl()) {
			return $this;
		}

		do {
			// Get the UID of the object
			$id = sha1(spl_object_hash($model).mt_rand(0,pow(10,100)));
		}
		while (array_key_exists($id, self::$_multiCurls));

		// Apply the model by reference to the multi call array
		self::$_multiCurls[$id] = $model;

		// Return self for chaining
		return $this;
	}

	/**
	 * Executes a multiCurl call with all of
	 * Sso_Models in the _multiCurl store.
	 * 
	 * The models have been attached by reference,
	 * so they will automatically be updated with
	 * the new loaded model at the end of the
	 * call.
	 *
	 * @return boolean
	 * @throws Sso_Model_Exception
	 */
	public function exec()
	{
		// Bail if there are no multiCurls
		if ( ! self::$_multiCurls) {
			return FALSE;
		}

		// Bail if there is only a single request
		if (count(self::$_multiCurls) == 1 AND isset(self::$_multiCurls['single'])) {
			return FALSE;
		}

		// Loop through each of the models in the pool
		foreach (self::$_multiCurls as $id => $model) {
			// Get the Sso_Request
			$request = $model->ssoRequest;

			// Ensure the request is processed as associative
			$request->setResultType(Sso_Request::RESULT_ASSOC);

			// Attach it to the client
			$this->_ssoClient->storeRequest($request, $id);
		}

		// Execute the request
		$this->_ssoClient->run();

		// Re-loop through the pool to set the new data to the existing model
		foreach (self::$_multiCurls as $id => $model) {
			// Get the response by id
			$response = $this->_ssoClient->getResponse($id);

			// If the response is an error, set the error to the model
			if ($response instanceof Sso_Error) {
				$model->error = $response;
				$model->setMultiCurl(FALSE);
				unset(self::$_multiCurls[$id]);
				continue;
			}

			// If the response is not an array, take no prisoners
			if ( ! is_array($response)) {
				throw new Sso_Model_Exception('Sso_Model_Base::exec() expects $response to be array. Received '.gettype($response));
			}

			// If the Sso_Request was not a delete
			if ($model->ssoRequest->getMethod() !== Sso_Request::DELETE) {
				// If the model was saved
				if ($model->saveModel === TRUE) {
					$model->setMultiCurl(FALSE)->find($model->id);
				}
				// Else if the result count is more than one, create an iterator
				else {
					$iterator = $this->_defaultIterator;
					$model = new $iterator($response['contentType'], $response['data']);
				}
			}
			// Otherwise, model was deleted, reset it
			else {
				$model->reset();
			}

			// unset the model from the pool to break the pointer
			unset(self::$_multiCurls[$id]);
		}

		// Finally clear the pool completely
		self::$_multiCurls = array();

		return TRUE;
	}

	/**
	 * Derives the real class name, minus the name space and
	 * Model prefix
	 *
	 * @return string
	 */
	public function getObjectClass()
	{
		// Get the classname
		$class = get_class($this);

		// Remove the namespace if there is one
		if (($namespace = Sso_Model_Base::getNamespace()) !== FALSE) {
			$class = explode($namespace.'_', $class);
			$class = $class[1];
		}

		// This has to be done outside of the array_pop
		// http://the-stickman.com/web-development/php/php-505-fatal-error-only-variables-can-be-passed-by-reference/
		$xp = explode('Model_', $class, 2);

		// Return just the model name, sans Model_ prefix
		$tmp = strtolower(array_pop($xp));
		return $tmp;
	}

	/**
	 * Returns the actual api call name
	 * from the object class
	 *
	 * @return string
	 */
	public function getApiName()
	{
		// Clean the object name of any remaining prefixes
		$name = explode('_', $this->_objectClass);
		return array_pop($name);
	}

	/**
	 * Maps a property key to the correct storage location
	 * Handles all aliases as well
	 *
	 * @param string $key 
	 * @return string|boolean
	 */
	protected function _resolveMap($key) {
		// First look for key in aliases
		if (array_key_exists($key, $this->_mapping)) {
			return $this->_mapping[$key];
		}

		// If the key is mapped, or exists in storage already, return the key
		if (in_array($key, $this->_mapping) OR array_key_exists($key, $this->_storage)) {
			return $key;
		}

		// Otherwise return false
		return FALSE;
	}

	/**
	 * Clears the storage data and returns model
	 * data to cleansed state
	 *
	 * @return void
	 */
	protected function _clearStorageData()
	{
		// If storage data is an array
		if (is_array($this->_storage)) {
			// Set all values to null
			foreach ($this->_storage as $key => $value) {
				$this->_storage[$key] = null;
			}

			// Set the saved and loaded values to FALSE
			$this->_loaded = FALSE;
			$this->_saved = FALSE;
			$this->_changed = array();
			$this->_saveModel = FALSE;
		}
	}
	
	/**
	 * Return loaded status
	 * 
	 * @return boolean
	 */
	public function isLoaded()
	{
		return $this->_loaded;
	}
	
	/**
	 * Return saved status
	 * 
	 * @return boolean
	 */
	public function isSaved()
	{
		return $this->_saved;
	}

	/**
	 * Clears the error state
	 *
	 * @return void
	 */
	protected function _clearError()
	{
		$this->_error = null;
	}

	/**
	 * Sets the self::_multiCurl property
	 *
	 * @param boolean $setting 
	 * @return self
	 */
	public function setMultiCurl($setting)
	{
		$this->_multiCurl = (bool) $setting;
		return $this;
	}

	/**
	 * Gets the self::_multiCurl property
	 *
	 * @return bool
	 */
	public function getMultiCurl()
	{
		return $this->_multiCurl;
	}

	/**
	 * Checks whether the client is present
	 *
	 * @return void
	 * @throws Sso_Model_Exception
	 */
	public function checkClient()
	{
		// pretty sure this isn't required
		if (!$this->_ssoClient instanceof Sso_Client_Abstract) {
			throw new Sso_Model_Exception('No SSO client provided');
		}
	}
	
	/**
	 * Sets an order by/direction parameter for querying
	 *
	 * @param string|array $field 
	 * @param string $direction asc/dsc
	 * @return self
	 */
	public function orderBy($field, $direction = 'desc')
	{
		if (is_array($field)) {
			// No merging, as unlike SQL, we cannot do multiple orderby statements
			$this->_orderBy = $field;
		}
		else {
			// Apply the orderby field and direction
			$this->_orderBy = array($field => $direction);
		}

		// Return self
		return $this;
	}
	
	/**
	 * Set a limit and offset for the results
	 *
	 * @param integer|array $limit limit, or array of limit and offset
	 * @param integer $offset [Optional]
	 * @return self
	 */
	public function limit($limit, $offset = 0)
	{
		// If limit is an array
		if (is_array($limit)) {
			// Apply it to limit immediatly
			$this->_limit = $limit;
		}
		else {
			// Else, assign limit and offset individually
			$this->_limit = array($limit, $offset);
		}

		// Return self
		return $this;
	}


	/**
	 * Finds a SSO record based on an ID
	 *
	 * @param string $id 
	 * @return Sso_Model_Base|self  returns Sso_Model if not in multiCurl mode
	 */
	public function find($id)
	{
		// Create the Sso_Request object
		$this->_ssoRequest = new Sso_Request(array(
			'urlParams' => array($this->getApiName(), $id),
			'allowedErrors' => array(400, 404)
		));

		// Loads the result an returns the model
		return $this->loadResult();
	}

	/**
	 * Finds all the records matching the parameters
	 *
	 * @param array $urlParams 
	 * @return Sso_Model_Iterator|self  returns Sso_Model_Iterator if not in multiCurl mode
	 */
	public function findAll($urlParams = array())
	{
		// Create the Sso_Request object
		$this->_ssoRequest = new Sso_Request(array(
			'path' => '/' . $this->getApiName(),
			'urlParams' => $urlParams
		));
		
		// add the lmit and sorting params
		$this->_addExtraParams();
		
		// Load the result
		return $this->loadResult(TRUE);
	}

	/**
	 * Add limit and sorting params to the request
	 * 
	 * @return Sso_Model_Base
	 */
	protected function _addExtraParams()
	{
		$getParams = $this->_ssoRequest->getGetParams();
		
		// Add the orderby clause in
		if ($this->_orderBy) {
			$getParams['order'] = key($this->_orderBy);
			$getParams['direction'] = current($this->_orderBy);
		}
				
		// Set the limit to the request
		if ($this->_limit) {
			$getParams['limit'] = $this->_limit[0];
			$getParams['offset'] = (isset($this->_limit[1]) ) ? $this->_limit[1] : 0;
		}
		
		$this->_ssoRequest->setGetParams($getParams);
	}
	
	/**
	 * Clear ordering and limiting params
	 * 
	 * @return unknown_type
	 */
	protected function _clearExtraParams()
	{
		$getParams = $this->_ssoRequest->getGetParams();
		
		unset($getParams['order']);
		unset($getParams['direction']);
		unset($getParams['limit']);
		unset($getParams['offset']);
				
		$this->_ssoRequest->setGetParams($getParams);
	}
	
	/**
	 * Recursively finds all entities at a given sub-tree (null for top) and
	 * their children. Note, only works on entities for which there are
	 * children, eg resources, organisations and roles
	 * 
	 * @param Sso_Model_Base|string $entity Start point 
	 * @param array $childObjectClass
	 * @param array $urlParams
	 * @return array
	 */
	protected function _doFindAllRecursive($childObjectClass = 'children', $urlParams = array())
	{
		if ($this->_loaded) {
			// grab this level's children
			$entities = $this->findChildren($childObjectClass);
		} else {
			// no start point, grab the top level entities
			$entities = $this->findAll($urlParams);
		}
		
		if (null === $entities) {
			// no children, back out of the recursion
			return array();
		}
		
		$results = array();
		foreach ($entities as $entity) {
			// store the current entity as an array of values
			$results[] = $entity->getValues();
			
			// merge in all our children
			$results = array_merge($results, $entity->_doFindAllRecursive($childObjectClass, $urlParams));
		}

		return $results;
	}
	
	/**
	 * Iterator wrapper around _doFindAllRecursive()
	 * 
	 * @param Sso_Model_Base|string $entity Start point 
	 * @param array $childObjectClass
	 * @param array $urlParams
	 * @return Sso_Model_Iterator
	 */
	public function findAllRecursive($childObjectClass = 'children', $urlParams = array())
	{
		return new Sso_Model_Iterator($this, $this->_doFindAllRecursive($childObjectClass, $urlParams));
	}

	/**
	 * Finds all the children of this class
	 *
	 * @param string $childParam 
	 * @return Sso_Model_Iterator
	 */
	public function findChildren($childParam = 'children')
	{
		// Setup the Sso_Request
		$this->_ssoRequest = new Sso_Request(array(
			'urlParams' => array($this->getApiName(), $this->id, $childParam),
			'allowedErrors' => array(401, 404, 400),
		));

		// add the lmit and sorting params
		$this->_addExtraParams();
		
		// Return the result
		return $this->loadResult(TRUE);
	}
	
	/**
	 * Find our parent and return a model for it
	 * 
	 * @param string $parentParam
	 * @return Sso_Model_Base
	 */
	public function findParent()
	{
		if (count($this->_belongsTo) == 0) {
			// no belongs to relationship defined
			return null;
		}

		$belongsTo = $this->_belongsTo[0];
		if (array_key_exists($belongsTo, $this->_belongsToFields)) {
			$belongsToField = $this->_belongsToFields[$belongsTo];
		} elseif (isset($this->$belongsTo)) {
			$belongsToField = $belongsTo;
		} else {
			$belongsToField = 'parent';
		}
		
		if (!isset($this->$belongsToField) || empty($this->$belongsToField)) {
			// nothing in the parent field
			return null;
		}
		
		return self::factory($belongsTo, $this->$belongsToField);
	}
	
	/**
	 * Find all our ancestors
	 * 
	 * @return array
	 */
	public function findParentRecursive($includeSelf = false)
	{
		if (!$this->_loaded) {
			return array();
		}
		
		$parent = $this->findParent();
		
		if ($includeSelf) {
			$results = array($this);
		} else {
			$results = array();
		}
		
		if (null === $parent) {
			// no parent
			return $results;
		}
		
		$results[] = $parent;
		return array_merge($results, $parent->findParentRecursive());
	}

	/**
	 * Loads and returns either a Sso_Model_Base or
	 * Sso_Model_Iterator based on the array argument.
	 * 
	 * This replaces the old self::load($id) method and
	 * should not be public.
	 *
	 * @param boolean $array 
	 * @return void|Sso_Model_Base|Sso_Model_Iterator
	 */
	protected function loadResult($array = FALSE)
	{
		// If this model is part of a multicurl call this cannot run
		if ($this->_multiCurl === TRUE) {
			return $this;
		}

		// Ensures the client is available and all errors are cleared
		$this->checkClient();
		$this->_clearError();

		// Ensure that the result will always be an associative array
		$this->_ssoRequest->setResultType(Sso_Request::RESULT_ASSOC);

		// Run the query
		try {
			$result = $this->_ssoClient
				->run($this->_ssoRequest)
				->getResponse();
		} catch (Exception $e) {
			// check if its an allowed error
			$code = $e->getCode();
			$message = $e->getMessage();

			if (in_array($code, $this->_ssoRequest->getAllowedErrors())) {
				$this->_error = new Sso_Error(array('errorCode' => $code, 'errorMessage' => $message));
				
				return $this;
			} else {
				throw $e;
			}
		}

		// If $array is TRUE, return the iterator
		if ($array === TRUE) {
			return new Sso_Model_Iterator($result);
		}

		// If this is a save model event
		if ($this->_saveModel === TRUE) {

			// Reset the save model status
			$this->_saveModel = FALSE;

			// Check for instance of Sso_Error
			if ($result instanceof Sso_Error) {
				$this->_error = $result;
			} else {
				// Else everything is okay, reload the model form the service
				$this->find($this->id);
			}

			// Reset the save model state
			$this->_saveModel = FALSE;
		} else if ($this->_ssoRequest->getMethod() !== Sso_Request::DELETE) {
			// Set the values to this model
			$this->setValues($result['data'][0]);

			$this->_loaded = TRUE;
			$this->_saved = TRUE;
		} else {
			// This model has been been deleted
			$this->reset();
		}

		// Return this object
		return $this;
	}

	/**
	 * Return an error reported by the client
	 *
	 * @return void
	 * @access public
	 */
	public function getError()
	{
		return $this->_error;
	}

    /**
     * Return whether there has was an error in the last request
     *
     * @return boolean
     */
    public function hasError()
    {
    	return $this->_error !== null;
    }
    
    /**
     * Set the sso client object
     *
     * @param Sso_Client $ssoClient
     */
    public function setClient($ssoClient)
    {
    	$this->_ssoClient = $ssoClient;
    }
    
    /**
     * Pre-save hook, can perform validations, etc
     * - returning false will abort the save
     * 
     * @return boolean
     */
    public function preSave()
    {
    	// stub
    	return true;
    }
    
    /**
     * Presave data formatter, use when the format of the data to save
     * is not mapped directly from the model's internal state
     * 
     * @param array $data
     * @return array
     */
    public function preSaveFormatData($data)
    {
    	// stub
    	return $data;
    }

    /**
     * Save the current data using the client
     *
     * @return Sso_Model_Base
     */
	public function save()
	{
		$this->checkClient();
		$this->_clearError();

		// try pre save hook
		if ($this->preSave() === false) {
			// pre-save failed, possibly validation, return immediately
			return $this;
		}
		
		$data = $this->getValues();
		unset($data['id']);

		// clear out empty fields, can cause issues with the service
		// @todo remove this code once the service is happier with empty fields
		
		foreach ($data as $key => $value) {
			if (empty($value) && !isset($this->_allowEmpty[$key])) {
				unset($data[$key]);
			}
		}

		// look for pre save data formatter
		$data = $this->preSaveFormatData($data);
		if ( ! $this->_loaded) {
			// creation
			$urlParams = array($this->_objectClass);

			if (array_key_exists('parent', $data)) {
				// we have to put parent in the url, not post it
				$urlParams[] = $data['parent'];
				unset($data['parent']);
			}
			$this->_ssoRequest = new Sso_Request(array(
				'urlParams'     => $urlParams,
				'postParams'    => $data,
				'method'        => Sso_Request::POST,
				'allowedErrors' => array(400, 409)
			));
		} else {
			if (Zend_Registry::isRegistered('cache')) {
				$cacheId = 'ModelFactoryCache__' . strtolower($this->_objectClass) . '__' . md5($this->id);
				Zend_Registry::get('cache')->remove($cacheId);
			}

			// update
			$this->_ssoRequest = new Sso_Request(array(
				'urlParams'     => array($this->_objectClass, $this->id),
				'postParams'    => $data,
				'method'        => Sso_Request::PUT,
				'allowedErrors' => array(400, 404)
			));
		}

		// Load the result
		$this->loadResult();
		// look for post save hook
		if (!$this->hasError() && method_exists($this, 'postSave')) {
			 $this->postSave($data);
		}

		// Return this (for chaining)
		return $this;
	}

	/**
	 * Validate the model in its current state.
	 * 
	 * To create a validation rule, create a method
	 * that is named '_validate<PropertyName>' ensuring
	 * the property name is camelCased.
	 * 
	 * This method can optionally save after validation
	 * if the optional argument is set to TRUE
	 * 
	 * If validation fails a test on one field, further
	 * tests for that field are halted, however tests on
	 * subsequent fields will continue.
	 *
	 * @param boolean $saveIfValid [Optional]
	 * @return boolean
	 * @access public
	 */
	public function validate($saveIfValid = FALSE)
	{
		$validationState = TRUE;

		foreach ($this->_validation as $key => $value) {
			// Parse the validation requirements
			$validate[$key] = $this->_parseValidationString($value);
		}

		foreach ($validate as $field => $tests) {
			foreach ($tests as $key => $params) {
				if ('required' == $key) {
					$result = $this->_runRequiredTest($field, $params);
				}
				else {
					$result = $this->_runValidation($field, $args);
				}

				if (TRUE === $result) {
					continue;
				}

				$this->_errors[$field] = $result;
				$validationState = FALSE;

				if (FALSE === $validationState) {
					continue 2;
				}
			}
		}

		// Save the model if valid
		if ($saveIfValid and $validationState) {
			$this->save();
		}

		return $validationSate;
	}

	/**
	 * Returns the internal errors as an associative
	 * array
	 *
	 * @return array
	 * @access public
	 */
	public function getValidationErrors()
	{
		return $this->_errors;
	}

	/**
	 * Return a single error based on the property
	 * name. If there is no error, NULL will be
	 * returned.
	 *
	 * @param string $property 
	 * @return string|void
	 * @access public
	 */
	public function getValidationError($property)
	{
		return isset($this->_errors[$property]) ? $this->_errors[$property] : NULL;
	}

	/**
	 * Delete the current entry
	 *
	 * @return Sso_Model_Base
	 */
	public function delete()
	{
		if ($this->_loaded) {
			if (Zend_Registry::isRegistered('cache')) {
				$cacheId = 'ModelFactoryCache__' . strtolower($this->_objectClass) . '__' . md5($this->id);
				Zend_Registry::get('cache')->remove($cacheId);
			}

			$this->_ssoRequest = new Sso_Request(array(
				'urlParams' => array($this->_objectClass, $this->id),
				'method' => Sso_Request::DELETE,
				'allowedErrors' => array(400, 404)
			));

			$this->loadResult();
			if (!$this->hasError()) {
				$this->_loaded = FALSE;
				$this->_saved = FALSE;
			}
		}
		
		return $this;
	}
    
	/**
	 * Set all values passed in
	 *
	 * @param array $data
	 * @param boolean $realData [Optional] if TRUE, this data is from the SSO service
	 * @return self
	 */
	public function setValues(array $data, $realData = FALSE)
	{
		// seed any other fields with blank
		foreach ($this->_mapping as $entry) {
			if (is_array($entry)) {
				$entry = key($entry);
			}

			if (!isset($data[$entry])) {
				if (isset($this->_fieldDefaults[$entry])) {
					$data[$entry] = $this->_fieldDefaults[$entry];
				} else {
					$data[$entry] = '';
				}
			}
		}
		
		// store the values
		$this->_storage = $data;

		// Set the saved status
		$this->_saved = (bool) $realData;

		// If this is data from SSO service
		if ($realData === TRUE)
		{
			// Set the loaded status to TRUE too
			$this->_loaded = (bool) $realData;
		}

		// Return self
		return $this;
	}

	/**
	 * Gets the values stored
	 *
	 * @return array
	 */
	public function getValues()
	{
		$result = array();
		foreach ($this->_mapping as $key => $value) {
			$_val = is_int($key) ? $this->$value : $this->$key;
			$result[$value] = ($_val === '') ? NULL : $_val;

			// if the value for this field is null, try using the value as the field to map
			if(NULL === $result[$value] && !is_int($key)) {
				$_val = $this->$value;
				$result[$value] = ($_val === '') ? NULL : $_val;
			}
		}

		return $result;
	}

	/**
	 * Sterilises this model ready for
	 * fresh use
	 *
	 * @return void
	 */
	public function reset()
	{
		// Clear the storage data
		$this->_clearStorageData();

		// Clear any errors
		$this->_clearError();

		// Reset multiCurl
		$this->setMultiCurl(FALSE);
	}

	/**
	 * Creates a unique cache id
	 *
	 * @param string $uniqueId [Optional]
	 * @return string
	 * @access protected
	 */
	protected function _createCacheId($uniqueId = NULL)
	{
		if (NULL === $uniqueId) {
			return md5(get_class($this));
		}
		else {
			return sprintf('%s_%s', md5(get_class($this)), md5($uniqueId));
		}
	}

	/**
	 * Tests whether a field passes the required test
	 *
	 * @param string $field 
	 * @param boolean $required 
	 * @return boolean|array
	 * @access protected
	 */
	protected function _runValidationRequired($field, $required)
	{
		if (FALSE === $required) {
			return TRUE;
		}

		if ( ! empty($required)) {
			return TRUE;
		}

		return array($field.' is required!');
	}

	/**
	 * Run the field value through the Zend_Validate
	 * defined in $args.
	 *
	 * @param string $field 
	 * @param array $args 
	 * @return boolean|array
	 * @access protected
	 */
	protected function _runValidation($field, array $args)
	{
		$zendValidateClassName = array_shift($args);
		$args = array_shift($args);

		// Because call_user_func() is incredibly slow, try and use
		// conventional PHP constructor first
		switch (count($args)) {
			case 0: {
				$zendValidateClass = new $zendValidateClassName;
				break;
			}
			case 1: {
				$zendValidateClass = new $zendValidateClassName($args[0]);
				break;
			}
			case 2: {
				$zendValidateClass = new $zendValidateClassName($args[0], $args[1]);
				break;
			}
			case 3: {
				$zendValidateClass = new $zendValidateClassName($args[0], $args[1], $args[2]);
				break;
			}
			case 4 : {
				$zendValidateClass = new $zendValidateClassName($args[0], $args[1], $args[2], $args[3]);
				break;
			}
			default : {
				throw new Sso_Model_Exception(__METHOD__.' cannot pass more than four args to this method');
			}
		}

		// Validate field
		$result = $zendValidateClass->isValid($this->$field);

		// Return either TRUE or an array of error messages
		return (TRUE === $result) ? TRUE : $zendValidateClass->getMessages();
	}

	/**
	 * Parses the validation string to get the
	 * arguments from the Zend_Validate class
	 * name.
	 *
	 * @param string $string 
	 * @return array
	 * @access protected
	 */
	protected function _parseValidaitonString($string)
	{
		// Setup the validation rules array
		$validate = array('required' => FALSE);

		// Preparse the validation string
		$toParse = explode(' ', $string);

		// Parse the validation
		foreach ($toParse as $part) {
			if ($part != 'required') {
				$_val = explode('[', $part);
				$_args = array();

				if (count($_val) > 1) {
					// Process arguments
					$_args = array_pop($_val);
					$_args = explode(',', rtrim($_args, ']'));
				}

				$validate[] = array('Zend_Validate_'.$_val[0], $_args);
			}
			else {
				$validate['required'] = TRUE;
			}
		}

		// Return the validation
		return $validate;
	}
}
