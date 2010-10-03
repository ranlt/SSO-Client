<?php

class Sso_Model_Right extends Sso_Model_Base {

	/**
	 * Models internal mapping. This property
	 * needs to define all of the available
	 * properties, including any aliases
	 *
	 * @var array
	 */
	protected $_mapping = array
	(
		'resource',
		'right',
		'grant',
	);

	/**
	 * @var array Belongs to relationship
	 */
	protected $_belongsTo = array('user');

	/**
	 * Cannot use
	 *
	 * @param array $urlParams 
	 * @return self
	 */
	public function findAll($urlParams = array())
	{
		return $this;
	}

	/**
	 * Save disabled as this model is
	 * read only
	 *
	 * @return self
	 */
	public function save()
	{
		return $this;
	}

	/**
	 * Delete disabled as this model is
	 * read only
	 *
	 * @return self
	 */
	public function delete()
	{
		return $this;
	}
}