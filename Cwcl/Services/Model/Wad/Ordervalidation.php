<?php
/**
 * The WAD order validation controller 
 * 	- Checks to see if an order can be cancelled.
 *
 * @category WAD
 * @package Orders
 * @author Anita Lau
 */
class Cwcl_Services_Model_Wad_Ordervalidation
{
	public $errorMsg;

	/**
	 * Only orders that have a status code of YYYY or higher can continue
	 * E.g Only orders that have a status code of 2001 or higher can cancelled
	 * E.g Only orders that have a status code of 3001 or higher can be amended
	 * 
	 * @param $statusValues Status code of the current order we want to cancel
	 * @return boolean;
	 */
	public function hasMinimumStatusCodeAllowed($minStatusCode, $statusValues)
	{
		foreach($statusValues as $key => $responseValues)
		{
			if($responseValues->StatusCode == $minStatusCode)
			{
				return true;		
			}
		}

		$this->errorMsg = 'Order has not recieved a ' . $minStatusCode . ' status code';
		return false;
	}
	
	/**
	 * Ensures the order is not 
	 * 		- in the process of being cancelled OR
	 *      - in the process of being amended 
	 * 
	 * @param $statusCode varchar Status code of the current order 
	 * @return boolean
	 */
	public function isOrderCancelledOrAmended($statusValues)
	{
		$this->errorMsg = null;
		$statusCode = $statusValues->ResponseStatus->StatusCode;
		
		switch($statusCode)
		{
			case Cwcl_Services_Model_Wad_Wadconstant::STATUS_CODE_BT_AMEND:
					$this->errorMsg = 'The order is in an order amendment state.';	
				break;
				
			case Cwcl_Services_Model_Wad_Wadconstant::STATUS_CODE_BT_CANCEL:
					$this->errorMsg = 'The order has already been cancelled.';
				break;
				
			case Cwcl_Services_Model_Wad_Wadconstant::STATUS_CODE_BT_PENDING:
					$this->errorMsg = 'The order is in an order pending state.';
				break;	
				
			default:
				return false;	
		}
		
		return true;
	}
	
	/**
	 * Checks to see if the order has already past the Point of no return (PONR)
	 * 
	 * @param $ccd Is the Customer Confirmed Date
	 * @param $orderType Order Product type
	 * @return boolean
	 */
	public function isPONR($CCD, $productType, $productName, $isNewLine)
	{
		if(is_null($CCD))
		{
			$this->errorMsg = 'Customer confirmed date has not been provided. Unable to calculate PONR.';	
		}
		
		$leadTime = $this->_getPONRLeadTime($productType, $productName, $isNewLine);
		if(date('Y-m-d 12:00:00', strtotime('+' . $leadTime) ) > $CCD)
		{
			$this->errorMsg = 'The order is already past it\'s Point of No Return date'; 
			return true;
		}
		
		return false;
	}
	
	/**
	 * Differnt products have different PONR lead times. This function returns the correct lead time for a particular product
	 * 
	 * @param $orderType product type
	 * @return integer
	 * 
	 */
	private function _getPONRLeadTime($productType, $productName, $isNewLine)
	{
		switch(ucfirst($productType))
		{
			case Cwcl_Services_Model_Wad_Wadconstant::PRODUCT_ORDER_TYPE_PROVIDE:
				if(stristr($productName, Cwcl_Services_Model_Wad_Wadconstant::PRODUCT_MPF_KEYWORD))
				{
					if($isNewLine)
					{
						$leadTime = Cwcl_Services_Model_Wad_Wadconstant::PONR_LEADTIME_TWO_DAY;
					}
					else
					{
						$leadTime = Cwcl_Services_Model_Wad_Wadconstant::PONR_LEADTIME_DEFAULT;
					}
				}
				else if(stristr($productName, Cwcl_Services_Model_Wad_Wadconstant::PRODUCT_SDSL_KEYWORD))
				{
					$leadTime = Cwcl_Services_Model_Wad_Wadconstant::PONR_LEADTIME_TWO_DAY;
				}		
				else 
				{
					$leadTime = Cwcl_Services_Model_Wad_Wadconstant::PONR_LEADTIME_DEFAULT;
				}
				break;
				
			case Cwcl_Services_Model_Wad_Wadconstant::PRODUCT_ORDER_TYPE_CEASE:
					$leadTime = Cwcl_Services_Model_Wad_Wadconstant::PONR_LEADTIME_TWO_DAY;
				break;	
		}

		return $leadTime;
	}
	
	/**
	 * User requests to change appoinment dates, the CRD date requested must be
	 * later than the current/original CRD date
	 * 
	 *  @param $crd  Current/Original CRD
	 *  @param $requestedCrd The requested CRD
	 * 
	 * @return boolean
	 */
	public function isRequestCRDChangeValid($crd, $requestedCrd)
	{
		if(strtotime($requestedCrd) < strtotime($crd))
		{	
			$this->errorMsg = 'Your new requested CRD date must be later that the current original CRD date.';
			return false;
		}
		
		return true;
	}
	
	/**
	 * Some product types do not require an appointment 
	 * 
	 *  @param $productName - to distinguish it;s type
	 *  @param $isNewLine   - boolean
	 *  
	 *  @return boolean
	 */
	public function isAppointmentRequired($productName, $isNewLine)
	{
		//TODO: how do i tell if it's a recovered line
		if( (stristr($productName, Cwcl_Services_Model_Wad_Wadconstant::PRODUCT_MPF_KEYWORD) && $isNewLine) ||
		    (stristr($productName, Cwcl_Services_Model_Wad_Wadconstant::PRODUCT_SDSL_KEYWORD)   && $isNewLine) )
		{
			return true;
		}
		
		return false;
	}	
	
	
}
