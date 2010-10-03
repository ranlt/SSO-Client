<?php 

/**
 * Gearman worker object for creating MAC codes
 *
 * @package Cwcl
 **/
class Cwcl_Gearman_Worker_GetWadOrders extends Cwcl_Gearman_Worker_Base
{
	/**
	 * The Gearman Worker object
	 *
	 * @var GearmanWorker
	 **/
	public $worker;
	
	/**
	 * Create the object, instantiate the worker
	 *
	 * @return void
	 **/
	public function init(GearmanWorker $worker)
	{
		parent::init($worker);
		echo 'Adding function GetWadOrders' . PHP_EOL;
		$this->worker->addFunction("GetWadOrders", __CLASS__ . "::GetWadOrders");
	}

	/**
	 * Check the criteria for availability
	 *
	 * @param array $criteria the postcodes or phone numbers to check
	 *
	 * @return boolean true on success, false on failure
	 **/
	static public function GetWadOrders($job)
	{
		$workload = unserialize($job->workload());

		return 'Hello World';
	}
}