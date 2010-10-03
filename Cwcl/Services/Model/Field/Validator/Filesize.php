<?php
/**
 * Filesize validator
 *
 * @package  Cwcl
 * @author   Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Validator_Filesize extends Zend_Validate_Abstract
{
	const MAX_SIZE  = 'max_size';
	const MIN_SIZE  = 'min_size';
	const NAF       = 'naf';

	/**
	 * @var   integer    min/max sizes
	 */
	public $min;
	public $max;

	protected $_messageVariables = array(
		'min' => 'min',
		'max' => 'max',
	);

	protected $_messageTemplates = array(
		self::MIN_SIZE => "'%value%' must be at least '%min%' bytes",
		self::MAX_SIZE => "'%value%' must be no more than '%max%' bytes",
		self::NAF      => "'%value%' must be a valid file",
	);

	/**
	 * Sets up the validation class
	 *
	 * @param   int      $max size in bytes
	 * @param   int      $min size in bytes
	 */
	public function __construct($max, $min = NULL)
	{
		$this->min = (int) $min;
		$this->max = (int) $max;
	}

	/**
	 * Validates the file size against the given criteria
	 *
	 * @param   string   $value path to the file that is to be validated
	 * @return  boolean
	 */
	public function isValid($value)
	{
		if ( ! is_file($value)) {
			$this->_error(self::NAF);
			return FALSE;
		}
		else if (($filesize = filesize($value)) === FALSE) {
			$this->_error(self::NAF);
			return FALSE;
		}

		// Test min size if required
		if ($this->min !== NULL and $filesize < $this->min) {
			$this->_error(self::MIN_SIZE);
			return FALSE;
		}

		// Test max size if required
		if ($this->max < $filesize) {
			$this->_error(self::MAX_SIZE);
			return FALSE;
		}

		return TRUE;
	}
}