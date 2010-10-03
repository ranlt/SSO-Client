<?php
/**
 * undocumented class
 *
 * @package default
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Validator_Filetype extends Zend_Validate_Abstract
{
	// Invalid constant
	const INVALID  = 'invalid';

	/**
	 * @var   array       filetype to evaluate
	 */
	public $filetypes;

	/**
	 * @var   array       Zend message variables
	 */
	protected $_messageVariables = array(
		'filetype' => 'filetype',
	);

	/**
	 * @var   array       Zend message templates
	 */
	protected $_messageTemplates = array(
		self::INVALID      => "'%value%' must be a valid file format, '%filetype%' is unsupported",
	);

	/**
	 * Constructs the class ready for use, takes the supported filetypes as either an
	 * array or multiple string arguments. E.g.
	 * 
	 * // Use this at all other times
	 * new Cwcl_Services_Model_Field_Validator_FileType(array('txt', 'pdf', 'xml', 'doc'));
	 * 
	 * // Only use this when defining a Cwcl model
	 * new Cwcl_Services_Model_Field_Validator_FileType('txt', 'pdf', 'xml', 'doc');
	 * 
	 * The second option is required due to the Model definition within the Cwcl_Services_Model_Abstract::init()
	 * method.
	 * 
	 * @author  Sam de Freyssinet
	 */
	public function __construct()
	{
		$argc = func_num_args();

		if ($argc == 1) {
			if (is_array($argv = func_get_arg(1))) {
				$this->filetypes = $argv;
			}
			else {
				$this->filetypes[] = (string) $argv;
			}
		}
		else if ($argc > 1) {
			$this->filetypes = func_get_args();
		}
	}

	/**
	 * Tests the file extension against the supported filetypes
	 *
	 * @param   string   $value 
	 * @return  boolean
	 */
	public function isValid($value)
	{
		// Get the filename details
		return in_array(pathinfo($value, PATHINFO_EXTENSION), $this->filetypes);
	}
}