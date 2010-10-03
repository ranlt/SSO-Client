<?php 

/**
 * Gearman worker object for creating MAC codes
 *
 * @package Cwcl
 **/
class Cwcl_Gearman_Worker_AddMACRequest extends Cwcl_Gearman_Worker_Base
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
		echo 'Adding function AddMACRequest' . PHP_EOL;
		$this->worker->addFunction("AddMACRequest", __CLASS__ . "::AddMACRequest");
	}

	/**
	 * Check the criteria for availability
	 *
	 * @param array $criteria the postcodes or phone numbers to check
	 *
	 * @return boolean true on success, false on failure
	 **/
	static public function AddMACRequest($job)
	{
		$workload = unserialize($job->workload());

		$c = Cw_Sorm_Model_Base::factory('Site')->setContext($workload->username);
		try {
			$sites = $c->findAll();
			return 'got ' . $sites->count() . ' sites at ' . date('d/m/Y H:i:s') . ' for user ' . $workload->username;
		} catch (Exception $e) {
			return 'failed: ' . $e->getMessage();
		}

		// This method is not implemented yet.
		// The full MyCW application environment needs to be 
		// loaded here. We need to make calls to the service layer
		// and should be using all the magic the application
		// has to do so. Implement this method once we figure out
		// how to do that.
	}
	

}