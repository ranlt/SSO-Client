<?php
/**
 * DateTime field for handling dateTime values
 *
 * @deprecated THIS CLASS ONLY WORKS IN PHP >= 5.3.0
 * 
 * @category Cwcl
 * @package Services/Model
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Model_Field_Datetime extends Cwcl_Services_Model_Field_Abstract
{
	protected $_type = 'dateTime';
	
	/**
	 * The current timezone. Defaults
	 * to system default
	 *
	 * @var DateTimeZone
	 */
	protected $_dateTimeZone;

	/**
	 * The DateTime object for all
	 * transformations
	 *
	 * @var DateTime
	 */
	protected $_dateTime;

	/**
	 * The input/output format of
	 * the the datetime
	 *
	 * @var string
	 */
	protected $_format;

	/**
	 * Overload the init
	 *
	 * @return void
	 * @access public
	 */
	public function __init()
	{
		// If there is no format defined, use a standard datetime format
		if (NULL === $this->getFormat()) {
			$this->setFormat('Y-m-d H:i:s');
		}

		// If there is no timezone set, set one bases on system settings
		if (NULL === $this->getTimezone()) {
			$this->setTimezone(new DateTimeZone(date_default_timezone_get()));
		}

		// If the value is null, create a new datetime object with defaults
		if (NULL === $this->getValue())
		{
			if (NULL === $this->getDateTime()) {
				$dateTime = new DateTime;
				$dateTime->setTimezone($this->getTimezone());
				$this->setDateTime($dateTime);
			}
		}
		// Otherwise setup the DateTime object based on the value
		else {
			$this->setDateTime(DateTime::createFromFormat($this->getFormat(), $this->_value, $this->getTimezone()));
		}

		return parent::__init();
	}

	/**
	 * Overload the serialize method to add the additional properties
	 * of this class to be serialized
	 *
	 * @param array $toSerialize [Optional]
	 * @return string
	 * @access public
	 */
	public function serialize(array $toSerialize = array())
	{
		$toSerialize += array(
			'_dateTimeZone'   => $this->_dateTimeZone,
			'_dateTime'       => $this->_dateTime,
			'_format'         => $this->_format,
		);

		return parent::serialize($toSerialize);
	}

	/**
	 * Overload the getValue method to return a string based on
	 * the format
	 *
	 * @return string
	 * @access public
	 */
	public function getValue()
	{
		return (NULL === $this->_value) ? NULL : $this->getDateTime()->format($this->getFormat());
	}

	/**
	 * Overload the setValue method to set a new date
	 * to the objects
	 *
	 * @param string $value 
	 * @return self
	 * @access public
	 */
	public function setValue($value)
	{
		$this->_value = $value;
		
		if (NULL !== $value) {
			$this->setDateTime(DateTime::createFromFormat($this->getFormat(), $value, $this->getTimezone()));
		}
		return $this;
	}

	/**
	 * Set a new DateTime object to the class
	 *
	 * @param DateTime $dateTime 
	 * @return self
	 * @access public
	 */
	public function setDateTime(DateTime $dateTime)
	{
		$this->_dateTime = $dateTime;
		return $this;
	}

	/**
	 * Get the DateTime object from this model
	 *
	 * @return DateTime
	 * @access public
	 */
	public function getDateTime()
	{
		return $this->_dateTime;
	}

	/**
	 * Set a new DateTimeZone object to this model
	 *
	 * @param DateTimeZone $dateTimeZone 
	 * @return self
	 * @access public
	 */
	public function setTimezone(DateTimeZone $dateTimeZone)
	{
		$this->_dateTimeZone = $dateTimeZone;
		return $this;
	}

	/**
	 * Return the timezone object from this model
	 *
	 * @return DateTimeZone
	 * @access public
	 */
	public function getTimezone()
	{
		return $this->_dateTimeZone;
	}

	/**
	 * Set the format for all DateTime transformations
	 * to string
	 *
	 * @param string $format 
	 * @return self
	 * @access public
	 */
	public function setFormat($format)
	{
		$this->_format = (string) $format;
		return $this;
	}

	/**
	 * Get the current date format
	 *
	 * @return string
	 * @access public
	 */
	public function getFormat()
	{
		return $this->_format;
	}

	/**
	 * Parses a value. Takes the input value
	 * and converts it to the Field type
	 *
	 * @param mixed $value 
	 * @return mixed
	 * @access protected
	 */
	protected function parseValue($value)
	{
		return $value;
	}
}