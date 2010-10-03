<?php

class Sso_Model_Resource extends Sso_Model_Base
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
		'parent'
	);
	
	/**
	 * @var array Belongs to relationship
	 */
	protected $_belongsTo = array('resource');
	
	protected $_belongsToFields = array('resource' => 'parent');
}