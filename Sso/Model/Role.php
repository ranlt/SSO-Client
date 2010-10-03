<?php

class Sso_Model_Role extends Sso_Model_Base
{
	/**
	 * Models internal mapping. This property
	 * needs to define all of the available
	 * properties, including any aliases
	 *
	 * @var array
	 */
	protected $_mapping = array
	(
		'id',
		'name',
		'parent',
		'description',
		'permissions'
	);
	
	/***
	 * Default values for fields, if not set by incoming data
	 */
	protected $_fieldDefaults = array(
		'permissions' => array()
	);

	/**
	 * @var array Belongs to relationship
	 */
	protected $_belongsTo = array('role');
	
	/**
	 * @var array Map belongs to to a field
	 */
	protected $_belongsToFields = array('role' => 'parent');
	
	/**
	 * Convert internal permissions into an array data packet for the request, 
	 * format looks like:
	 * 
	 * array(
	 *   name => 'Senior Op',
	 *   resource[0]	=> 'users',
	 *   right[0]		=> 'create',
	 *   grant[0]		=> '1',
	 *   resource[1]	=> 'organisation',
	 *   right[1]		=> 'create',
	 *   grant[1]		=> 0
	 * )
	 * 
	 * @param array $data
	 * @return array
	 */
	public function preSaveFormatData($data)
	{
		$newdata = $data;
		if (array_key_exists('permissions', $newdata)) {
			// get rid of existing permissions, we are totally rebuilding
			// that data
			unset($newdata['permissions']);
			
			$c = 0;
			foreach ($data['permissions'] as $permission) {
				$index = '[' . $c . ']';
				foreach ($permission as $key => $value) {
					$newdata[$key.$index] = $value;
				}
				
				$c++;
			}
		} else {
		    $newdata['right'] = "";
		    $newdata['resource'] = "";
		    $newdata['grant'] = "";

		}

		return $newdata;
	}
	
	/**
	 * Merge in a right to an existing role
	 * 
	 * @param string $resource
	 * @param string $right
	 * @param boolean $grant
	 * @return Sso_Model_Base
	 */
	public function addRight($resource, $right, $grant = 'true')
	{
		$data = array(array(
			'resource'	=> $resource,
			'right'		=> $right,
			'grant'		=> $grant
		));

		if ($this->permissions) {
			foreach ($this->permissions as $row)
			{
				if ($row['resource'] != $resource || $row['right'] != $right) {
					// not the one we've just added in
					$data[] = $row;
				}
			}
		}

		$this->permissions = $data;

		return $this;
	}
	
	/**
	 * Remove a right from an existing role
	 * 
	 * @param string $resource
	 * @param string $right
	 * @return Sso_Model_Base
	 */
	public function removeRight($resource, $right)
	{
		//var_dump($resource, $right);
		$data = array();
		if ($this->permissions) {
			foreach ($this->permissions as $row)
			{
				//if this is the only resource to be removed then remove it anyway: count($this->permissions < 2)
				//var_dump($row['resource'], $row['right']);
				if ($row['resource'] != $resource || $row['right'] != $right) {
					// not the one we're trying to remove
					$data[] = $row;
				}
			}
			//die();
		}
		$this->permissions = $data;
		return $this;
	}

        public function getUsernames($resource)
        {
            // Currently use this to support getting all users that has a
            // particular role until a lower level service at sso service is
            // available
            $users = new Sso_Model_User();
            $usersWithResource = array();
            foreach ($users->findAll() as $user) {
                if ($user->hasRight("read", $resource)) {
                    $usersWithResource[] = $user->username;
                }
            }
            return $usersWithResource;
        }

}