<?php 

/**
 * Gearman worker object for bulk availability class
 *
 * @package Cwcl
 **/
class Cwcl_Gearman_Worker_Availability extends Cwcl_Gearman_Worker_Base
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
		$this->worker->addFunction("bulk_check", "Cwcl_Gearman_Worker_Availability::bulkCheck");
	}

	/**
	 * Check the criteria for availability
	 *
	 * @param array $criteria the postcodes or phone numbers to check
	 *
	 * @return boolean true on success, false on failure
	 **/
	static public function bulkCheck($criteria)
	{
		$criteria = self::getDataFromWorkload($criteria->workload());
		$availabilityModel = new Order_Model_BulkAvailabilityCheck($criteria['user']);
		$availabilityModel->setBulkCheckData($criteria['data']);
		if ($results = $availabilityModel->run($criteria['availability_requests_id'])) {
			$config = Zend_Registry::get('mycwConfig');
			$mail = new Zend_Mail();
			$mail->setBodyText("
Dear {$criteria['full_name']},
Your bulk availability request is now complete. Please visit the portal to view it.

Kind Regards,
Online Portal Team
");
			$mail->setFrom($config->online->portal->team->email, 'C&W Online Portal Team');
			$mail->addTo($criteria['email'], $criteria['full_name']);
			$mail->setSubject('Bulk Availability Check Complete');
			try {
				$mail->send();
			} catch (Exception $e) {
				// do not handle exceptions as a failure here is not critical
			}
			return true;
		}
	}
}