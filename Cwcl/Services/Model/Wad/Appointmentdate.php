<?php
/**
 * Date validation class for appointment dates.
 * CW get charged for every call that is made to the servers ()
 * 
 * Putting in some validation code before a call is made.
 * - Ensure minimum and maximum lead times are met
 * - Ensure date is not on a weekend
 * - Ensire date is not a public holiday
 * 
 */
class Cwcl_Services_Model_Wad_Appointmentdate
{
	/**
	 * Ensure the preferred date is with the minimum and maximum lead times
	 * Ensure the date does not fall on a weekend or a public/bank holiday
	 * 
	 * @param $date string
	 * @param $boolean
	 */
	public function validatePreferredDate($date, $type, &$err)
	{
		$minimumLeadTime = $this->getMinimumLeadTime($type);
		$maximumLeadTime = $this->getMaximumLeadTime();

		//Ensure preferred date is between minimum and maximum lead times
		if( (strtotime($date) < strtotime($minimumLeadTime)) ||
		    (strtotime($date) > strtotime($maximumLeadTime)) )
		   {
				$err = 'The preferred date selected needs to fall within the following dates: ' . $minimumLeadTime . ' and ' . $maximumLeadTime;
		   	 	return false;
		   }
		
		if($this->isPublicHoliday($date) || $this->isWeekend($date))
		{
			    $err = 'Preferred date must be a business day: Mon - Fri excluding public holidays.';
				return false;
		}
		
		return true;
	}	 
	
	/**
	 * Get the maximum lead time date the user can place an order
	 * 
	 * @return string 
	 */
	public function getMaximumLeadTime()
	{
		return date('d-m-Y', strtotime('+'. Cwcl_Services_Model_Wad_Wadconstant::MAXIMUM_LEAD_TIME.'days'));
	}
		
	/**
	 * Retrieve the minimum lead time for a particular product. 
	 * Minimum lead time - the earlist date an appoinment can be made
	 * Different products have different lead times
	 * 
	 * @return date string
	 */
	public function getMinimumLeadTime($type, $date = NULL)
	{
		switch($type)
		{
			case Cwcl_Services_Model_Wad_Wadconstant::PRODUCT_TYPE_MPF:
					$date = $this->getProductLeadTime(Cwcl_Services_Model_Wad_Wadconstant::MINIMUM_MPF_LEAD_TIME);
				break;
				
			case Cwcl_Services_Model_Wad_Wadconstant::PRODUCT_TYPE_SMPF_NO_LINE:
					$date = $this->getProductLeadTime(Cwcl_Services_Model_Wad_Wadconstant::MINIMUM_SMPF_NO_LINE_LEAD_TIME);
				break;	
				
			case Cwcl_Services_Model_Wad_Wadconstant::PRODUCT_TYPE_SMPF_LINE:
					$date = $this->getProductLeadTime(Cwcl_Services_Model_Wad_Wadconstant::MINIMUM_SMPF_LINE_LEAD_TIME);
				break; 	
			case Cwcl_Services_Model_Wad_Wadconstant::AMENDED_ORDER;
					$date = $this->getProductLeadTime(Cwcl_Services_Model_Wad_Wadconstant::MINIMUM_AMEND_ORDER_LEAD_TIME);
				break;	
		}
	
		//ensure that date is not a weekend or public holiday
		return $this->validateLeadTimeDate($date);
	}
	

	/**
	 * Get the minimum product lead time
	 * 
	 * @return $date string
	 */
	private function getProductLeadTime($days)
	{
		return date('d-m-Y', strtotime('+'.$days.'days'));
	}
	
	/**
	 * Ensure the date provided does not fall on a public holiday or a weekend
	 * If it does get the next avaiable business working day
	 * 
	 * @param $date string
	 * @return $date string
	 */
	public function validateLeadTimeDate($date)
	{
		while($this->isPublicHoliday($date) || $this->isWeekend($date))
		{
			$date = date('d-m-Y', strtotime($date . '+ 1 day'));
		}
		
		return $date;
	}
	
	
	/**
	 * Ensure the date selected is not on a weekend
	 * 
	 * @param $date string
	 * @return boolean
	 */
	public function isWeekend($date)
	{
		return in_array(date('N', strtotime($date)), array(Cwcl_Services_Model_Wad_Wadconstant::DATE_SATURDAY_INT, Cwcl_Services_Model_Wad_Wadconstant::DATE_SUNDAY_INT)) ? true : false; 
	}
	
	
	/**
	 * Very Very crude way of determining public holildays.
	 * 
	 * @param $preferredDate
	 * @return boolean
	 */
	public function isPublicHoliday($date)
	{ 
		//public holidays 2010/2011
		$holidays = array('25-12-2009'
						 ,'28-12-2009'
						 ,'01-01-2010'
						 ,'02-04-2010'
						 ,'05-04-2010'
						 ,'03-05-2010'
						 ,'31-05-2010'
						 ,'30-08-2010'
						 ,'27-12-2010'
						 ,'28-12-2010'
						 ,'03-01-2011'
						 ,'22-04-2011'
						 ,'25-04-2011'
						 ,'02-05-2011'
						 ,'30-05-2011'
						 ,'29-08-2011'
						 ,'26-12-2011'
						 ,'27-12-2011');
						 
		if(in_array($date, $holidays))
		{				 
			return true;
		}

		return false;
	}	
}