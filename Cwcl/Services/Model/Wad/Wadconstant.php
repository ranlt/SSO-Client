<?php
/**
 * Common Constants used for WAD orders
 *
 * @category WAD
 * @package Orders
 * @author Anita Lau
 */
class Cwcl_Services_Model_Wad_Wadconstant
{
	//wsdl files
	const WSDL 			  = 'WSDL';
	const WSDL_FULFILMENT = 'fulfilment';
	const WSDL_DIALOGUE   = 'dialogue';
	const WSDL_COMPANY_ID = 'getCompanyIdRequest';
	
	
	//Order type
	const PRODUCT_ORDER_TYPE_PROVIDE  = 'Provide';
	const PRODUCT_ORDER_TYPE_CEASE    = 'Cease';
	
	//The products
	//anything that has the word double, is an mpf
	//anything with the word single is a smpf etc
	const PRODUCT_MPF_KEYWORD         = 'double';  
	const PRODUCT_SMPF_KEYWORD        = 'single'; 
	const PRODUCT_SDSL_KEYWORD        = 'sdsl';
	const PRODUCT_IPSTREAM_KEYWORD    = 'offnet';
	
	 //TODO
	 const PRODUCT_TYPE_MPF   = 'MPF';
	 const PRODUCT_TYPE_SDSL  = 'SDSL'; 
	 
	 //TODO: these have to change depending on what i get from the product section
	 const PRODUCT_TYPE_SMPF_NO_LINE  = 'SMPF_NO_LINE';
	 const PRODUCT_TYPE_SMPF_LINE     = 'SMPF_LINE';

	 const AMENDED_ORDER              = 'amend'; 
	
	//Maximum lead time is 90 calender days for both MPF and SMPF
	const MAXIMUM_LEAD_TIME  = 90;
	
	//minimum lead time for an order is 4 working days
	const MINIMUM_AMEND_ORDER_LEAD_TIME    = 4;
	
	//MPF Minimum lead time is 11 workings days = 15 calendar days
	const MINIMUM_MPF_LEAD_TIME = 15;
	
	// Where there is no existing data service on the line it's 5 working days = 7 calendar days
	const MINIMUM_SMPF_NO_LINE_LEAD_TIME  = 7;
	
	//Where there is an existing data service on the line (SMPF singleton migration) its 7 working days = 9 calendar days
	const MINIMUM_SMPF_LINE_LEAD_TIME     = 9;
	
	const DATE_SATURDAY_INT = 6;
	const DATE_SUNDAY_INT   = 7;
	
	//PONR = Point of no return
	//following products have a 2 day (midday) PONR. All other products have 1 day (midday) of PONR
	const PONR_LEADTIME_DEFAULT  = 1;
	const PONR_LEADTIME_TWO_DAY  = 2;
	
	//BT Status codes
	const STATUS_CODE_BT_PENDING  = 101;
	const STATUS_CODE_BT_AMEND    = 540;
	const STATUS_CODE_BT_CANCEL   = 501;
	
	const STATUS_CODE_BT_ACCEPTED    = 2001; 
	const STATUS_CODE_BT_COMMITTED   = 3001;
	const STATUS_CODE_BT_COMPLETE_OK = 4001;
	
}
