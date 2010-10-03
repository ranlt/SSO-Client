<?php
if (!class_exists('Sso_Model_User', false)) {

class Sso_Model_User extends Sso_Model_Base {
	
	const USER_TYPE_ADMIN 	= 'ADMIN';
	const USER_TYPE_API 	= 'API';
	const USER_TYPE_NORMAL 	= 'NORMAL';
	
	public static $USER_TYPES = array(self::USER_TYPE_ADMIN, self::USER_TYPE_API, self::USER_TYPE_NORMAL);

	/**
	 * Models internal mapping. This property
	 * needs to define all of the available
	 * properties, including any aliases
	 *
	 * DO NOT PUT RELATIONSHIPS IN HERE AS PROPERTIES!!!!
	 * NAUGHTY BAD NAUGHTY!
	 *
	 * @var array
	 */
	protected $_mapping = array
	(
		'id' => 'username',    // Id is being aliased to the real property name
		'organisation',
		'fullName',
		'emailAddress',
		'phoneNumber',
		'location',
		'livelinkUsername',
		'livelinkPassword',
		'crystalReportUsername',
		'crystalReportPassword',
		'password',
		'title',
		'userType'
	);

	/**
	 * @var array Has many relationship
	 */
	protected $_hasMany = array('roles', 'rights');

	/**
	 * Cache of rights
	 *
	 * @var Sso_Model_Iterator
	 */
	protected $_cacheRights = NULL;

	/**
	 * Cache of rights lookup for this
	 * request. (High speed)
	 *
	 * @var array
	 */
	protected $_cacheInstanceRights = NULL;

	/**
	 * Cache of roles
	 *
	 * @var Sso_Model_Iterator
	 */
	protected $_cacheRoles = NULL;

	/**
	 * Cache of roles look for this
	 * request. (High speed)
	 *
	 * @var string
	 */
	protected $_cacheInstanceRoles = NULL;

	/**
	 * The Garbage Collection probability
	 *
	 * @var bool|int (Between 1 - 100)
	 */
	protected $_cacheGCProbability = 2;

	/**
	 * The cache engine
	 *
	 * @var Zend_Cache
	 */
	protected $_cache;

	/**
	 * Add the roles and rights caching properties
	 * to the serialisation method
	 *
	 * @return array
	 * @access public
	 */
	public function __sleep()
	{
		$fields = parent::__sleep();
		$fields[] = '_cacheRoles';
		$fields[] = '_cacheRights';
		$fields[] = '_cacheGCProbability';
		$fields[] = '_cacheInstanceRights';
		$fields[] = '_cacheInstanceRoles';
		return $fields;
	}

	public function  __wakeup() {
		parent::__wakeup();
		$this->__initialise();
	}

	/**
	 * Overload the __init() method to
	 * provide local caching of the cache library
	 *
	 * @return void
	 * @access public
	 */
	public function __initialise($id = NULL)
	{
		parent::__initialise($id);

		if (Zend_Registry::isRegistered('cache')) {
			$this->_cache = Zend_Registry::get('cache');
		}
	}

	/**
	 * Make sure the rights for this user are cached.
	 *
	 * Load the rights for this user and store them in the local cache variable,
	 * only if the rights aren't cached already.
	 *
	 * @return array Cached rights in array with SSO objects
	 */
	private function _loadCacheRights()
	{
		// Already cached, just return the cache...
		if ($this->_cacheRights) {
			return $this->_cacheRights;
		}

		// If caching is off...
		if (!$this->_cache) {
			$this->_cacheRights = $this->_loadRights();
			return $this->_cacheRights;
		}

		$this->_runCacheGC();

		// Try and load rights from the cache
		$cacheId = $this->_createCacheId($this->id).'__rights';
		$rights = $this->_cache->load($cacheId);
		if (false === $rights) {
			// Load the rights for this user
			$rights = $this->_loadRights();

			// Save the rights for this user to cache
			$this->_cache->save($rights, $cacheId);
		}

		// Store the rights loaded
		$this->_cacheRights = $rights;
		return $this->_cacheRights;
	}

	/**
	 * Make sure the roles for this user are cached.
	 *
	 * Load the roles for this user and store them in the local cache variable,
	 * only if the roles aren't cached already.
	 *
	 * @return array Cached roles in array with SSO objects
	 */
	private function _loadCacheRoles()
	{
		// Already cached, just return the cache...
		if ($this->_cacheRoles) return $this->_cacheRoles;

		// If caching is off...
		if (!$this->_cache) {
			$this->_cacheRoles = $this->_loadRoles();
			return $this->_cacheRoles;
		}

		$this->_runCacheGC();

		// Try and load roles from the cache
		$cacheId = $this->_createCacheId($this->id).'__roles';
		$roles = $this->_cache->load($cacheId);
		if (false === $roles) {
			// Load the roles for this user
			$roles = $this->_loadRoles();

				// Save the roles for this user to cache
			$this->_cache->save($roles, $cacheId);
		}

		// Store the roles loaded
		$this->_cacheRoles = $roles;
		return $this->_cacheRoles;
	}

	/**
	 * Run garbage collection on this users caches, MIGHT clear local caches
	 * and, if caching is on, the general cache for rights and roles.
	 *
	 * Step right up! Step right up!
	 * Take a spin! You might end up with cleared caches!
	 */
	private function _runCacheGC()
	{
		if (mt_rand(1,100) <= $this->_cacheGCProbability) {
			$this->_clearCaches();
		}
	}

	/**
	 * Set new role(s) to this user, completely
	 * replacing the current assigned roles.
	 *
	 * @param array $roles
	 * @return self
	 * @access public
	 */
	public function setRoles(array $roles)
	{
		// Existing data
		$data = $this->getValues();

		// Filter empty values from post
		foreach ($data as $key => $value) {
			if (NULL === $value) {
				unset($data[$key]);
			}
		}

		$this->_addRolesParams($roles, $data);
	
		// Setup the Sso_Request
		$this->_ssoRequest = new Sso_Request(array(
			'urlParams'      => array($this->getApiName(), $this->id),
			'allowedErrors'  => array(401, 404, 400),
			'method'         => Sso_Request::PUT,
			'postParams'     => $data,
		));

		// Save the model
		return $this->loadResult();
	}

	/**
	 * Add one or more roles to the current
	 * collection
	 *
	 * @param string|array $roles
	 * @return self
	 * @access public
	 */
	public function addRoles($roles)
	{
		$this->_loadCacheRoles();

		// Existing data
		$data = $this->getValues();

		// Filter empty values from post
		foreach ($data as $key => $value) {
			if (NULL === $value) {
				unset($data[$key]);
			}
		}

		$_roles = array();

		// Setup the changed state
		$changed = FALSE;

		if (is_array($roles)) {
			foreach ($roles as $role) {
				if ( ! in_array($role, $_roles)) {
					$_roles[] = $role;
					$changed = TRUE;
				}
			}
		}
		elseif (is_string($roles)) {
			if ( ! in_array($roles, $_roles)) {
				$_roles[] = $roles;
				$changed = TRUE;
			}
		}

		if ( ! $changed) {
			return $this;
		}

		// Add the roles
		$this->_addRolesParams($_roles, $data);
		
		// Setup the Sso_Request
		$this->_ssoRequest = new Sso_Request(array(
			'urlParams'      => array($this->getApiName(), $this->id),
			'allowedErrors'  => array(401, 404, 400),
			'method'         => Sso_Request::PUT,
			'postParams'     => $data,
		));


		// Clear all roles/rights cache
		$this->_clearCaches();

		// Run this SSO query
		return $this->loadResult();
	}

	/**
	 * Remove one or more roles from
	 * the current collection
	 *
	 * @param string|array $roles
	 * @return self
	 * @access public
	 */
	public function removeRoles($roles)
	{
		$this->_loadCacheRoles();

		// Existing data
		$data = $this->getValues();

		// Filter empty values from post
		foreach ($data as $key => $value) {
			if (NULL === $value) {
				unset($data[$key]);
			}
		}

		// Existing roles
		$_existingRoles = $this->_existingRolesAsArray();

		$changed = FALSE;

		$_newRoles = array();

		if (is_array($roles)) {
			foreach ($_existingRoles as $_role) {
				if ( ! in_array($_role, $roles)) {
					$_newRoles[] = $_role;
				}
			}
		}
		elseif (is_string($roles)) {
			foreach ($_existingRoles as $_role) {
				if ($_role !== $roles) {
					$_newRoles[] = $_role;
				}
			}
		}

		if ( ! $changed) {
			return $this;
		}

		// Add the roles
		$this->_addRolesParams($_newRoles, $data);

		// Setup the Sso_Request
		$this->_ssoRequest = new Sso_Request(array(
			'urlParams'      => array($this->getApiName(), $this->id),
			'allowedErrors'  => array(401, 404, 400),
			'method'         => Sso_Request::PUT,
			'postParams'     => $data,
		));

		// Clear all roles/rights cache
		$this->_clearCaches();

		// Return self
		return $this->loadResult();
	}

	/**
	 * Return whether a user has a right on a resource
	 *
	 * @param  string $right
	 * @param  string $resource
	 * @return boolean
	 */
	public function hasRight($right, $resource)
	{
		$this->_loadCacheRights();

		// If there are no rights loaded, return FALSE
		if ( ! $this->_cacheRights instanceof Sso_Model_Iterator) {
			return FALSE;
		}

		// Check the cache
		$key = md5($right.$resource);

		if ( ! isset($this->_cacheInstanceRights[$key])) {
			$this->_loadCacheInstanceRight($resource, $right, $key);
		}

		$result = $this->_cacheInstanceRights[$key];

		// Return the result
		return $result;
	}
	
	/**
	 * Is this user of type $type
	 * 
	 * @param string $type
	 * @return boolean
	 * @throws InvalidArgumentException if $type not in Sso_Model_User::$USER_TYPES
	 */
	public function isUserType($type)
	{
		if (!in_array($type, self::$USER_TYPES)) {
			throw new InvalidArgumentException('Parameter $type should be one of Sso_Model_User::$USER_TYPES');
		}
		
		$data = $this->getValues();
		
		return $data['userType'] == $type;
	}

	private function _loadCacheInstanceRight($resource, $right, $key)
	{
		// Setup the result
		$result = FALSE;

		foreach ($this->_cacheRights as $_right) {
			if ($_right->resource !== $resource) {
				continue;
			}

			if ($right === $_right->right) {
				$result = TRUE;
				break;
			}
		}

		// Internally cache the result
		$this->_cacheInstanceRights[$key] = $result;
	}

	/**
	 * Return whether a user has a role
	 * @param  string $role
	 * @return boolean
	 */
	public function hasRole($role)
	{
		$this->_loadCacheRoles();

		// setup the result
		$result = FALSE;

		if ( ! $this->_cacheRoles instanceof Sso_Model_Iterator) {
			return FALSE;
		}

		if ( ! isset($this->_cacheInstanceRoles[$role])) {
			$this->_loadCacheInstanceRole($role);
		}

		$result = $this->_cacheInstanceRoles[$role];

		return $result;
	}

	private function _loadCacheInstanceRole($role)
	{
		foreach ($this->_cacheRoles as $_role) {
			if ($role !== $_role->name) {
				continue;
			}

			if ($role === $_role->name) {
				$result = TRUE;
				break;
			}
		}

		$this->_cacheInstanceRoles[$role] = $result;
	}

	/**
	 * Load the rights for this user from the SSO
	 * service
	 *
	 * @return Sso_Model_Iterator
	 * @access protected
	 */
	protected function _loadRights()
	{
		return $this->rights;
	}

	/**
	 * Loads the roles for this user from the SSO
	 * service
	 *
	 * @return Sso_Model_Iterator
	 * @access protected
	 */
	protected function _loadRoles()
	{
		return $this->roles;
	}

	/**
	 * Add the roles parameters to the request
	 *
	 * @param array $roles
	 * @param array $postData
	 * @return void
	 * @access protected
	 */
	protected function _addRolesParams(array $roles, array & $postData)
	{
		// Parse the supplied roles into parameters
		$i = 0;
		foreach ($roles as $role) {
			$postData['role['.$i.']'] = $role;
			$i++;
		}

		// Set the saved status to FALSE
		$this->_saved = FALSE;
	}

	/**
	 * Extract the existing roles as an array
	 *
	 * @return array
	 * @access protected
	 */
	protected function _existingRolesAsArray()
	{
		$output = array();

		$roles = $this->roles;
		foreach ($roles as $role) {
			$output[] = $role->name;
		}

		return $output;
	}

	/**
	 * Clears all the caches from this model and Zend_Cache
	 *
	 * @return void
	 * @access protected
	 */
	protected function _clearCaches()
	{
		$this->_cacheRights = NULL;
		$this->_cacheRoles = NULL;
		$this->_cacheInstanceRights = NULL;
		$this->_cacheInstanceRoles = NULL;
		if (isset($this->_cache)) {
			$this->_cache->remove($this->_createCacheId($this->id).'__rights');
			$this->_cache->remove($this->_createCacheId($this->id).'__roles');
		}
	}
}

}
