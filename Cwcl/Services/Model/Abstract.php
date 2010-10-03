<?php
/**
 * Cwcl Services Model provides basic model
 * functionality for all service requests and
 * responses.
 * 
 * This model is a composite of the following
 * component parts :
 * 
 * @uses Cwcl_Services_Client_Abstract
 * @uses Cwcl_Services_Request_Abstract
 * @uses Cwcl_Services_Parser_Abstract
 *
 * @package Cwcl_Services
 * @author Sam de Freyssinet
 */
abstract class Cwcl_Services_Model_Abstract implements Serializable 
{
	/**
	 * MODEL HEURISTICS 
	 */

	/**
	 * The storage container for data returned
	 * from the client/parser
	 *
	 * @var Cwcl_Services_Model_Field_Container
	 */
	protected $_storage = array();

	/**
	 * Mapping of values to properties named something other than
	 * their defined description
	 * 
	 * @example
	 * array(
	 *      'uid'  => 'id',
	 *      'name' => 'fullname',
	 * );
	 * 
	 * In the example above, access to the property 'uid' will actually
	 * map to the service property 'id'. 'name' will map to the property
	 * 'fullname'.
	 * 
	 * Any fields not defined here will default to the _description
	 * definition.
	 *
	 * @var array
	 */
	protected $_mapping = array();

	/**
	 * The request object that was last
	 * generated
	 *
	 * @var Cwcl_Services_Request
	 */
	protected $_request;

	/**
	 * Allow the service description discovery to
	 * be overridden manually in the model
	 *
	 * @var boolean
	 */
	protected $_allowServiceDescriptionOverride = TRUE;

	/**
	 * The client used to connect to the
	 * service
	 *
	 * @var Cwcl_Service_Client_Abstract
	 */
	protected $_client;

	/**
	 * The parser used to parse the returned
	 * data into the correct model/iterator
	 *
	 * @var Cwcl_Service_Parser_Abstract
	 */
	protected $_parser;

	/**
	 * The name that this object should be
	 * referred to. This usually is
	 * automatically resolved, but can
	 * be overloaded.
	 *
	 * @var string
	 */
	protected $_objectName;

	/**
	 * The name of the service that this
	 * model maps to. This usually is
	 * automatically be resolved, but
	 * can be overloaded here.
	 *
	 * @var string
	 */
	protected $_serviceName;

	/**
	 * Disable the pluralisation of words
	 * using Cwcl_Services_Inflector
	 * 
	 * This only effects the findAll() method
	 * in this class
	 *
	 * @var boolean
	 */
	protected $_disablePlural = FALSE;

	/**
	 * MODEL STATE
	 */

	/**
	 * Loaded state
	 *
	 * @var boolean
	 */
	protected $_loaded;

	/**
	 * Saved state
	 *
	 * @var boolean
	 */
	protected $_saved;

	/**
	 * Changed state. Will list the
	 * fields that have been modified
	 * since the last load. (requires
	 * save)
	 *
	 * @var array
	 */
	protected $_changed = array();

	/**
	 * Dispatch Lock status. If multiple
	 * requests are to be dispatched together
	 * this needs to be TRUE
	 *
	 * @var boolean
	 */
	protected $_dispatchLock;

	/**
	 * Error state.
	 *
	 * @var array
	 */
	protected $_errors = array();

	/**
	 * Order by parameters
	 *
	 * @var array
	 */
	protected $_orderBy = array();

	/**
	 * Limit parameters
	 *
	 * @var array
	 */
	protected $_limit = array();

	/**
	 * Primary key field used for loading
	 * records
	 *
	 * @var string
	 */
	protected $_primaryKey = 'id';
	
	/**
	 * Tell your gateway to use multipart encoding
	 *
	 * @var boolean
	 **/
	protected $_multipart = false;

	/**
	 * Setups up the model ready for use
	 *
	 * @param array $dependencies [Optional]
	 * @param mixed $id [Optional]
	 * @access public
	 */
	public function __construct($id = NULL, array $dependencies = NULL)
	{
		if (NULL !== $dependencies)
		{
			// Apply all the dependencies
			foreach ($dependencies as $key => $value) {
				$key = 'set'.ucwords($key);
				$this->$key($value);
			}
		}

		// Check the objectname
		if (NULL === $this->_objectName) {
			$this->_objectName = $this->resolveObjectName();
		}

		// Check the service name
		if (NULL === $this->_serviceName) {
			$this->_serviceName = $this->resolveServiceName($this->_objectName);
		}

		// Initialise the model
		$this->__init();

		// Initialise the models state
		$this->_loaded = FALSE;
		$this->_saved = FALSE;
		$this->_dispatchLock = FALSE;

		// If an ID is supplied, attempt to load the id
		if (NULL !== $id) {
			$this->find($id);
		}
	}

	/**
	 * Magic get() method for accessing model relationships
	 * and service properties.
	 *
	 * @param string $key 
	 * @return mixed
	 * @access public
	 */
	public function __get($key)
	{
		// Resolve key
		if (FALSE === ($_key = $this->resolveMapping($key))) {
			return NULL;
		}
		
		return $this->_storage[$_key];
	}

	/**
	 * Magic set() method for setting values
	 * to storage ready for persistent storage
	 *
	 * @param string $key 
	 * @param mixed $value 
	 * @return void
	 * @access public
	 * @throws Cwcl_Services_Model_Exception
	 */
	public function __set($key, $value)
	{
		if (FALSE === ($_key = $this->resolveMapping($key))) {
			throw new Cwcl_Services_Model_Exception(__METHOD__.' the key \''.$key.'\' is not available in this model');
		}

		// Set the model state
		$this->_storage[$_key] = $value;
		$this->_saved = FALSE;
		$this->_changed[] = $_key; 
	}

	/**
	 * Magic isset(), allowing use of isset($this->property)
	 *
	 * @param string $key 
	 * @return boolean
	 * @access public
	 */
	public function __isset($key)
	{
		return isset($this->_storage[$key]);
	}

	/**
	 * Magic unset(), allowing use of unset($this->property)
	 *
	 * @param string $key 
	 * @return void
	 * @access public
	 */
	public function __unset($key)
	{
		$this->_storage[$key]->setValue(NULL);
		return;
	}

	/**
	 * Magic clone method to ensure model clones successfully
	 *
	 * @return void
	 * @access public
	 */
	public function __clone()
	{
		if ($this->_client instanceof Cwcl_Services_Client_Abstract) {
			// Re-initialise the client and parser
			$this->setClient(Cwcl_Services_Client_Abstract::getInstance($this->_client->getClientName(), array('gateway' => $this->_client->getGateway(), 'uri' => $this->_client->getUri())));
		}

		if ($this->_client instanceof Cwcl_Services_Parser_Abstract) {
			$this->setParser(Cwcl_Services_Parser_Factory::factory($this->_parser->getParserName()));
		}
	}

	/**
	 * Initialise the storage ready for use.
	 * 
	 * Description of the model/service. This
	 * is vitally important as it governs how
	 * this model will behave. Each entry of
	 * the array should be formatted following
	 * the schema described below. All the
	 * fields/properties of the service that
	 * must be present have to be defined here.
	 * 
	 * The description must be defined as an array.
	 * Services can return a description of their method
	 * using the Client::getServiceDescription() method.
	 * 
	 * If $this->_allowServiceDescriptionOverride is TRUE,
	 * define the description as below and then call
	 * parent::__init($description);
	 * 
	 * @example
	 * $description = array(
	 *      'id' => array(
	 *          'type'      => 'integer',             // Type of fields [Required]
	 *          'length'    => 12,                    // Length/Size [Optional]
	 *          'null'      => TRUE,                  // Null value allowed [Optional]
	 *          'default'   => NULL,                  // The default value [Optional]
	 *          'extra'     => 'unsigned'             // Extra options [Optional]
	 *          'validate'  => 'StringLength[0,200]'  // Zend_Validate class [Optional]
	 *          'preFilter' => 'Alnum'                // Zend_Filter class [Optional]
	 *          'postFilter'=> 'Alpha'                // Zend_Filter class [Optional]
	 *      ),
	 *      ...,
	 * );
	 * 
	 * // Call the parent override
	 * parent::__init($description);
	 *
	 * (string) Type :
	 * The type is the datatype this model is dealing with. It should be either,
	 * 'integer', 'float', 'varchar' or 'datetime'. Although datetime and
	 * varchar are interchangeable, it is best to distinguish between them when
	 * modelling.
	 * 
	 * - 'auto' (for auto increment field)
	 * - 'integer' (for 'int', 'tinyint', 'mediumint', 'bigint')
	 * - 'bool' (for 'boolean')
	 * - 'float' (for 'float', 'double', 'decimal')
	 * - 'string' (for 'char', 'tinytext', 'text', 'mediumtext', 'longtext', 'blob', 'tinyblob', 'mediumblob', 'longblob')
	 * - (NOT YET IMPLEMENTED!!!) 'datetime' (for 'datetime', 'date', 'timestamp')
	 * 
	 * (integer) Length :
	 * The length is either the max length of strings, or size of ints/floats. Should always be
	 * an integer value
	 * 
	 * (boolean) Null :
	 * Boolean switch to control whether a NULL value is allowed
	 * 
	 * (mixed) Default :
	 * The default value to apply to the field
	 * 
	 * (string) Extra :
	 * Extra options to apply to this field. Currently supported are
	 * - 'unsigned' for unsigned values
	 * - 'zerofill' for zero filled values, uses the 'length' value to fill
	 * - 'unsigned zerofill' for unsigned and zero filled value (see above)
	 *
	 *
	 * @return void
	 * @access public
	 * @throws Cwcl_Services_Model_Exception
	 */
	public function __init(array $description = array())
	{
		// If there is no description
		if ( ! $description)
		{
			try {
				// Try and ascertain the description from the service (slow)
				$description = $this->getServiceDescription();
			}
			catch (Cwcl_Services_Model_Exception $e) {
				if ( ! $this->_allowServiceDescriptionOverride) {
					throw $e;
				}
			}
		}

		// Initialise the storage
		$this->initialiseStorageWithDescription($description);

		return;
	}

	/**
	 * Serializes this object when passed to serialize().
	 * 
	 * Accept an array as a argument so additional
	 * properties can be passed to parent::serialize()
	 * 
	 * [SPL Serializable]
	 *
	 * @param array $toSerialize [Optional]
	 * @return string
	 * @access public
	 */
	public function serialize(array $toSerialize = array())
	{
		$client = ($this->_client instanceof Cwcl_Services_Client_Abstract) 
			? $this->_client->getClientName()
			: NULL;

		$parser = ($this->_parser instanceof Cwcl_Services_Parser_Abstract)
			? $this->_parser->getParserName()
			: NULL;

		$toSerialize += array(
			'_storage'            => $this->_storage,
			'_mapping'            => $this->_mapping,
			'_client'             => $client,
			'_parser'             => $parser,
			'_objectName'         => $this->_objectName,
			'_serviceName'        => $this->_serviceName,
			'_loaded'             => $this->_loaded,
			'_saved'              => $this->_saved,
			'_changed'            => $this->_changed,
			'_dispatchLock'       => $this->_dispatchLock,
			'_request'            => $this->_request,
			'_orderBy'            => $this->_orderBy,
			'_limit'              => $this->_limit,
		);

		return serialize($toSerialize);
	}

	/**
	 * Unserialises this object based on the string
	 * passed in.
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

		// Re-initialise the client and parser
		if (NULL !== $this->_client) {
			$this->setClient(Cwcl_Services_Client_Factory::factory($this->_client));
		}
		
		if (NULL !== $this->_parser) {
			$this->setParser(Cwcl_Services_Parser_Factory::factory($this->_parser));
		}

		return;
	}

	/**
	 * Get the primary key for this model
	 *
	 * @return string
	 * @access public
	 */
	public function primaryKey()
	{
		return $this->_primaryKey;
	}

	/**
	 * Returns all of the models fields as a
	 * container object
	 *
	 * @return Cwcl_Services_Model_Field_Container
	 * @access public
	 */
	public function getFieldsAsContainer()
	{
		return $this->_storage;
	}

	/**
	 * Sets a client to this model
	 *
	 * @param Cwcl_Services_Client_Abstract $client 
	 * @return self
	 * @access public
	 */
	public function setClient(Cwcl_Services_Client_Abstract $client)
	{
		$this->_client = $client;
		return $this;
	}

	/**
	 * Retrieve the client in this model
	 *
	 * @access public
	 */
	public function getClient()
	{
		return $this->_client;
	}

	/**
	 * Sets a parser to this model
	 *
	 * @param Cwcl_Services_Parser_Abstract $parser 
	 * @return self
	 * @access public
	 */
	public function setParser(Cwcl_Services_Parser_Abstract $parser)
	{
		$this->_parser = $parser;
		return $this;
	}

	/**
	 * Retrieve the parser in this model
	 *
	 * @access public
	 */
	public function getParser()
	{
		return $this->_parser;
	}

	/**
	 * Returns the request object from this
	 * model
	 *
	 * @access public
	 * @return Cwcl_Services_Request
	 */
	public function getRequest()
	{
		return $this->_request;
	}

	/**
	 * Get the service name for this model
	 *
	 * @return string
	 * @access public
	 */
	public function getServiceName()
	{
		return $this->_serviceName;
	}

	/**
	 * Get the loaded status
	 *
	 * @return bool
	 * @access public
	 */
	public function isLoaded()
	{
		return $this->_loaded;
	}

	/**
	 * Get the saved status
	 *
	 * @return bool
	 * @access public
	 */
	public function isSaved()
	{
		return $this->_saved;
	}

	/**
	 * Get the changed status
	 *
	 * @return bool
	 * @access public
	 */
	public function isChanged()
	{
		return (bool) $this->_changed;
	}

	/**
	 * 
	 *
	 * @return bool
	 * @access public
	 */
	public function validate()
	{ 
		return $this->valid();
	}
	
	/**
	 * States whether the model is currently
	 * valid
	 *
	 * @return bool|void
	 * @access public
	 */
	public function valid()
	{
		return $this->_storage->isValid();
	}

	/**
	 * Return all the errors for each field
	 * in an associative array
	 *
	 * @return array
	 * @access public
	 * @throws Cwcl_Services_Model_Exception
	 */
	public function getErrors()
	{
		return $this->_storage->getErrors();
	}

	/**
	 * Return all the error messages for each
	 * field in an associative array
	 *
	 * @return array
	 * @access public
	 * @throws Cwcl_Services_Model_Exception
	 */
	public function getMessages()
	{
		return $this->_storage->getMessages();
	}

	/**
	 * Get the changed values' keys
	 *
	 * @return array
	 * @access public
	 */
	public function changedValues()
	{
		return $this->_changed;
	}

	/**
	 * Get the dispatch lock state
	 *
	 * @return bool
	 * @access public
	 */
	public function getDispatchLock()
	{
		return $this->_dispatchLock;
	}

	/**
	 * Set the dispatch lock state
	 *
	 * @param bool $state
	 * @return self
	 * @access public
	 */
	public function setDispatchLock($state)
	{
		$this->_dispatchLock = (bool) $state;
		return $this;
	}

	/**
	 * Find a record by the id
	 *
	 * @param mixed $id 
	 * @return Cwcl_Services_Model_Abstract
	 * @access public
	 */
	public function find($id)
	{
		// Get the correct service name
		$serviceName = $this->getServiceName();

		// Create a request object
		$this->_request = new Cwcl_Services_Request(array(
			'urlParams' => array($serviceName),
			'getParams'	=> array($this->_primaryKey => $id),
		));

		// run the request, load the result and return the Model
		return $this->loadResult();
	}

	/**
	 * Find multiple records by parameters
	 *
	 * @param array $urlParams [Optional]
	 * @return Cwcl_Services_Model_Iterator
	 * @access public
	 */
	public function findAll(array $urlParams = array())
	{
		// Get the correct service name
		$serviceName = ($this->_disablePlural) ? $this->getServiceName() : Cwcl_Services_Inflector::plural($this->getServiceName());

		// initialise $params as a blank array
		$params = array();

		// Create the params list
		if ($urlParams) {
			$params['search'] = 'search';
			foreach ($urlParams as $key => $value) {
				$params[$key] = $value;
			}
		}

		// create a request object
		$this->_request = new Cwcl_Services_Request(array(
			'urlParams'    => array($serviceName),
			'getParams'    => $params,
			'singleRecord' => FALSE,
		));
		// run the request, load the result and return the Model
		return $this->loadResult(TRUE);
	}

	/**
	 * Order the results by field and direction
	 *
	 * @param string|array $field 
	 * @param string $direction [Optional]  desc/asc 
	 * @return self
	 * @access public
	 */
	public function orderBy($field, $direction = 'desc')
	{
		if ( ! is_array($field)) {
			$field = array($field);
			$field[1] = $direction;
		}

		$field[1] = strtolower($field[1]);

		// Validate direction
		if ( ! in_array($direction, array('asc', 'desc'))) {
			throw new Cwcl_Services_Model_Exception(__METHOD__.' trying to set an invalid order by direction : '.$field[1]);
		}

		// Apply to the model
		$this->_orderBy = $field;

		return $this;
	}

	/**
	 * Limit the output and set the starting
	 * offsets
	 *
	 * @param integer|array $limit 
	 * @param integer $offset [Optional]
	 * @return self
	 * @access public
	 */
	public function limit($limit, $offset = 0)
	{
		if ( ! is_array($limit)) {
			$limit = array($limit);
			$limit[1] = $offset;
		}

		if (0 > $limit[1]) {
			throw new Cwcl_Services_Model_Exception(__METHOD__.' trying to set an offset less than zero : '.$limit[1]);
		}

		$this->_limit = $limit;

		return $this;
	}

	/**
	 * Save the model, either creating a new
	 * record or updating existing record
	 *
	 * @return self
	 * @access public
	 */
	public function save()
	{
		if ($this->isSaved() or ! $this->isChanged()) {
			return $this;
		}

		// Check for model validity
		if ( ! $this->valid()) {
			$messages = array();
			foreach ($this->_storage->getMessages() as $field=>$msg) {
				if (!empty($msg)) {
					$messages[] = "field: $field\nmessage: " . implode("\n", $msg);
				}
			}
			$messages = implode ("\n", $messages);
			throw new Cwcl_Services_Model_Exception(__METHOD__.' the model is not valid: ' . $messages);
		}

		// Get the payload ready for sending
		$payload = $this->packagePayload();

		//Scil Order returns payload in an array, when we send to SL, it must be a string so it decodes properly
		if(is_array($payload) && isset($payload['payload']))
		{
			$payload = $payload['payload'];
		}

		if ( ! $this->isLoaded())
		{
			$this->_request = new Cwcl_Services_Request(array(
				'urlParams'    => array($this->getServiceName()),
				'postParams'   => $payload,
				'method'       => Cwcl_Services_Request::POST,
				'singleRecord' => TRUE,
			));
		}
		else
		{
			$this->_request = new Cwcl_Services_Request(array(
				'urlParams'    => array($this->getServiceName(), $this->{$this->primaryKey()}),
				'postParams'   => $payload,
				'method'       => Cwcl_Services_Request::PUT,
				'singleRecord' => TRUE,
			));
		}

		return $this->loadResult();
	}

	/**
	 * Deletes this record from the service
	 *
	 * @return self
	 * @access public
	 */
	public function delete()
	{
		if ( ! $this->isLoaded()) {
			return $this;
		}

		$this->_request = new Cwcl_Services_Request(array(
			'urlParams'    => array($this->_objectName, $this->{$this->primaryKey()}),
			'method'       => Cwcl_Services_Request::DELETE,
		));

		$this->loadResult();

		return $this->reset();
	}

	/**
	 * Return the values outputted as an
	 * associative array
	 *
	 * @return array
	 * @access public
	 */
	public function getValues()
	{
		return $this->_storage->getValues();
	}

	/**
	 * Set values to this model from an
	 * associative array
	 *
	 * @param array $values 
	 * @return self
	 * @access public
	 */
	public function setValues(array $values)
	{
		foreach ($values as $key => $value) {
			/**
			 * This automatically handles the serialized values
			 * that may be present due to Cwcl_Services_Model_Abstract::packagePayload()
			 * 
			 * @see http://stackoverflow.com/questions/2158138/posting-an-array-with-curl-setopt
			 * 
			 * @author Sam de Freyssinet
			 */
			$pos = strpos($key, '__serialized|');
			if ($pos !== FALSE and $pos == 0) {
				$key = str_replace('__serialized|', '', $key);
				$this->$key = unserialize($value);
			}
			else {
				$this->$key = $value;
			}
		}

		return $this;
	}

	/**
	 * Reset the model to default state
	 *
	 * @return self
	 * @access public
	 */
	public function reset()
	{
		$this->_saved = FALSE;
		$this->_loaded = FALSE;
		$this->_changed = array();
		$this->_request = NULL;
		$this->_orderBy = array();
		$this->_limit = array();

		// Initialise storage
		$this->__init();

		return $this;
	}

	/**
	 * Returns the model bases on Iterator result
	 * data
	 *
	 * @param array $result 
	 * @param Cwcl_Services_Request $request 
	 * @return self
	 * @access public
	 */
	public function loadIteratorResult(array $result, Cwcl_Services_Request $request)
	{
		$this->setValues($result);
		$this->_saved = TRUE;
		$this->_loaded = TRUE;
		$this->_changed = array();
		$this->_request = $request;
		return $this;
	}
	
	/**
	 * Inject storage container
	 * This is intended for testing purposes
	 *
	 * @param Cwcl_Services_Model_Field_Container
	 * @return void
	 */
	public function setStorage(Cwcl_Services_Model_Field_Container $storage)
	{
		$this->_storage = $storage;
	}

	/**
	 * Perform a request to the client
	 * and pass the result to the parser
	 * for decoding
	 *
	 * @return mixed
	 * @access protected
	 * @throws Cwcl_Services_Model_Exception
	 */
	protected function loadResult()
	{
		if ($this->getDispatchLock()) {
			return $this;
		}

		// Check for available client
		if ( ! $this->getClient() instanceof Cwcl_Services_Client_Abstract) {
			throw new Cwcl_Services_Model_Exception(__METHOD__.' no valid client present!');
		}

		// Check for available client
		if ( ! $this->getParser() instanceof Cwcl_Services_Parser_Abstract) {
			throw new Cwcl_Services_Model_Exception(__METHOD__.' no valid parser present!');
		}

		// add and ordering and pagination to the request
		$this->addExtraParams();

		// Run the request
		$response = $this->_client->run($this->_request);
		
		// Try parsing the response (exceptions are caught)
		try {
			$result = $this->_parser
				->setInput($response)
				->parse($this);
				
			// If the result is an iterator, return it
			if ($result instanceof Cwcl_Services_Model_Iterator) {
				return $result;
			}
			// If the result is this class
			elseif ($result instanceof $this) {

				$this->_loaded = TRUE;
				$this->_saved = TRUE;
				$this->_changed = array();
				$this->_request = NULL;
				$this->_orderBy = array();
				$this->_limit = array();
				return $this;
			}
			// Else if the result is BOOLEAN true, set the models state to loaded and saved
			elseif (TRUE === $result) {

				$this->_loaded = TRUE;
				$this->_saved = TRUE;
				$this->_changed = array();
				$this->_request = NULL;
				$this->_orderBy = array();
				$this->_limit = array();
				return $this;
			}
			elseif (FALSE === $result) {
				return $this;
			}

			// Otherwise, unknow error occured. Shouldn't get here!
			throw new Cwcl_Services_Model_Exception(__METHOD__.' unknown error occurred parsing response : '.$response->getContent());
		} catch (Cwcl_Services_Parser_Exception $e) {
			throw new Cwcl_Services_Model_Exception(__METHOD__.' Failed to parse the response with message : '.$e->getMessage());
		}
	}

	/**
	 * Add limit and sorting params to the request
	 * 
	 * @return void
	 */
	protected function addExtraParams()
	{
		$getParams = $this->_request->getGetParams();

		// Add the orderby clause in
		if ($this->_orderBy) {
			$getParams['order'] = $this->_orderBy[0];
			$getParams['direction'] = $this->_orderBy[1];
		}

		// Set the limit to the request
		if ($this->_limit) {
			$getParams['limit'] = $this->_limit[0];
			$getParams['offset'] = (isset($this->_limit[1]) ) ? $this->_limit[1] : 0;
		}

		$this->_request->setGetParams($getParams);
		return;
	}
	
	/**
	 * Clear ordering and limiting params
	 * 
	 * @return void
	 */
	protected function clearExtraParams()
	{
		$getParams = $this->_request->getUrlParams();

		unset($getParams['order']);
		unset($getParams['direction']);
		unset($getParams['limit']);
		unset($getParams['offset']);

		$this->_request->setUrlParams($getParams);
		return;
	}

	/**
	 * Package the payload ready for sending,
	 * cleans out any empty fields
	 *
	 * @return array
	 * @access protected
	 */
	public function packagePayload()
	{
		$data = $this->getValues();

		$payload = array();

		/**
		 * This has been refactored to automatically serialize objects or
		 * arrays that cannot be converted to strings. This is required
		 * for cURL to use with CURLOPT_POSTFIELDS
		 * 
		 * @todo  Optimise this as it is going to be bloody slow!!!
		 *
		 * @author Sam de Freyssinet
		 */
		foreach ($data as $key => $value) {
			if (NULL !== $value) {
				if (is_scalar($value) === FALSE) {
					// Test for object type
					if (is_object($value)) {
						$reflection = new ReflectionClass($value);

						if ($reflection->hasMethod('__toString') === FALSE) {
							$key = '__serialized|'.$key;
							$value = serialize($value);
						}
					}
					else {
						$key = '__serialized|'.$key;
						$value = serialize($value);
					}
				}

				$payload[$key] = $value;
			}
		}

		return $payload;
	}

	/**
	 * Get the service description from the client.
	 * 
	 * WARNING! Currently there are no service description
	 * available
	 *
	 * @return array
	 * @access protected
	 * @throws Cwcl_Services_Model_Exception
	 */
	protected function getServiceDescription()
	{
		if (NULL === $this->_client) {
			throw new Cwcl_Services_Model_Exception(__METHOD__.' no Cwcl_Services_Client_Abstract available to perform task');
		}

		try {
			return $this->_client->getServiceDescription($this->getServiceName());
		}
		catch (Cwcl_Services_Client_Exception $e)
		{
			throw new Cwcl_Services_Model_Exception($e->getMessage());
		}
	}

	/**
	 * Returns the default value for a field
	 *
	 * @param string $key 
	 * @return mixed|void
	 * @access protected
	 * @throws Cwcl_Services_Model_Exception
	 */
	protected function getDefaultValue($key)
	{
		if (FALSE === ($_key = $this->resolveMapping($key))) {
			throw new Cwcl_Services_Model_Exception(__METHOD__.' the key \''.$key.'\' could not be found in this model');
		}

		return $this->_storage->$_key->getDefaultValue();
	}

	/**
	 * Parses the input value based on the key
	 * and the service description
	 *
	 * @param array $description
	 * @return void
	 * @access protected
	 * @throws Cwcl_Services_Model_Exception
	 */
	protected function initialiseStorageWithDescription(array $description)
	{
		$this->_storage = new Cwcl_Services_Model_Field_Container($description);
		return;
	}

	/**
	 * Resolves the mapping internally
	 *
	 * @param string $key 
	 * @return string
	 * @access protected
	 * @throws Cwcl_Services_Model_Exception
	 */
	protected function resolveMapping($key)
	{
		// Store the original key
		$_key = $key;

		// Setup a static cache
		static $cache;

//		If the key has already been resolved, return the mapping
		if (isset($cache[$_key])) {
			return $cache[$key];
		}

		// If there is a map defined for this key, update key value
		if (isset($this->_mapping[$key])) {
			if ($key === $this->_mapping[$key]) {
				throw new Cwcl_Services_Model_Exception('Mapping key/value pairs cannot be identical');
			}

			$key = $this->_mapping[$key];
		}

		// If the key value is not found in the description, bail
		if ( ! isset($this->_storage[$key])) {
			$key = FALSE;
		}

		// Cache the key result
		$cache[$_key] = $key;

		return $key;
	}

	/**
	 * Resolves the object name from the
	 * name of the model
	 *
	 * @return class
	 * @access protected
	 */
	protected function resolveObjectName()
	{
		$modelname = explode('model_', strtolower(get_class($this)));

		return array_pop($modelname);
	}

	/**
	 * Resolves the service name from the
	 * name of the model
	 *
	 * @return class
	 * @access protected
	 */
	protected function resolveServiceName()
	{
		return $this->resolveObjectName();
	}
	
	/**
	 * Check if this model should use multipart encoding in
	 * the gateway for curl requests
	 *
	 * @return boolean true if multipart, false/default if URL encoded
	 * @author Johanna Cherry <johanna@ibuildings.com>
	 **/
	public function isMultipart()
	{
		return (bool) $this->_multipart;
	}
}
