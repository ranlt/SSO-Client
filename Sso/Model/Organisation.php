<?php

class Sso_Model_Organisation extends Sso_Model_Base
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
		'description',
		'parent',
		'roles',
		'contacts',             // stores the set of company contacts for this organisation
		'pid',                  // Company ID
	);
	
	/***
	 * Default values for fields, if not set by incoming data
	 */
	protected $_fieldDefaults = array
	(
		'roles' => array(),
	);
	
	/**
	 * @var array Belongs to relationship
	 */
	protected $_belongsTo = array('organisation');
	
	/**
	 * @var array Field to identify the belongs to relationship
	 */
	protected $_belongsToFields = array('organisation' => 'parent');
	
	/**
	 * @var array Has many relationship
	 */
	protected $_hasMany = array('organisation', 'users', 'role');
	
	/**
	 * Convert internal roles into an array data packet for the request, 
	 * format looks like:
	 * 
	 * array(
	 *   name 		=> 'My Org',
	 *   role[0]	=> 'user',
	 *   role[0]	=> 'operator',
	 *   ...
	 * )
	 * 
	 * @param array $data
	 * @return array
	 */
	public function preSaveFormatData($data)
	{
		$newdata = $data;
		
		if (array_key_exists('roles', $newdata)) {
			// get rid of existing permissions, we are totally rebuilding
			// that data
			unset($newdata['roles']);
			
			$c = 0;
			foreach ($data['roles'] as $role) {
				$newdata['role[' . $c . ']'] = $role;
				
				$c++;
			}
		}
		
		// JSON encode the company contacts information
		if (array_key_exists('contacts', $newdata)) {
			unset($newdata['contacts']);
			$newdata['contacts'] = json_encode($data['contacts']);
		}

		return $newdata;
	}


	public function getAvailableUsers($allUsers) 
	{
		if ($this->contacts) {
			$list = json_decode($this->contacts, true);
			
			if (!is_null($list)) {
				
				$availableUsers = array();
				$companyContacts = array_keys($list);
				
				foreach($allUsers as $user) {
					if (!in_array($user->username, $companyContacts)) {
						$availableUsers[] = $user;
					}
				}
				return $availableUsers;
			}
		}
		return $allUsers;
	}
}
