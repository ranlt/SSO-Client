<?php
/**
 * Cwcl_Exception_Service_ErrorResponse_Minuco_AvailabilityCheck
 *
 * Exception class for error responses from Minuco Availability Checker
 *
 * @category  Cwcl
 * @package   Cwcl_Exception
 * @copyright Copyright (c) 2009 Cable&Wireless
 * @author	  Johanna Cherry <johanna@ibuildings.com>
 */
class Cwcl_Exception_Service_ErrorResponse_Minuco_AvailabilityCheck extends Cwcl_Exception_Service_ErrorResponse
{
	/**
	 * An array of error messages. The element key is a StatusCode from Minuco
	 *
	 * @var array
	 **/
	static public $messages = array(
			'1028' => 'There is no data available for this number.',
	);
}