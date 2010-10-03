<?php 
/**
 * Gearman client object with base functionality
 *
 * @package Cwcl
 **/
class Cwcl_Gearman_Client_Base implements Cwcl_Gearman_Client_Interface
{
	/**
	 * The GearmanClient object from init
	 *
	 * @var GearmanClient
	 **/
	public $client;
	
	/**
	 * The user requesting the availability request
	 *
	 * @var Cw_User
	 **/
	protected $_user;
	
	/**
	 * Create the object, instantiate the worker
	 *
	 * @param GearmanClient $client the Gearman Client object to request work
	 *
	 * @return void
	 **/
	public function init(GearmanClient $client)
	{
		$this->client = $client;
	}
	
	/**
	 * Set the user variable
	 *
	 * @param Cw_User the user object
	 *
	 * @return void
	 **/
	public function setUser($user)
	{
		$this->_user = $user;
	}
	
	/**
	 * Send the job to the worker
	 *
	 * @param string $name the name of the job to call
	 * @param array  $data an array of data to send to the worker
	 *
	 * @return boolean true on success, false on failure
	 **/
	public function sendJob($name, $data)
	{
		return $this->client->do($name, serialize($data));
	}
	
	/**
	 * Send the background job to the worker
	 *
	 * @param string $name the name of the job to call
	 * @param array  $data an array of data to send to the worker
	 *
	 * @return boolean true on success, false on failure
	 **/
	public function sendBackgroundJob($name, $data)
	{
		return $this->client->doBackground($name, serialize($data));
	}
}