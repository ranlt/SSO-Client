<?php

class Cwcl_Automodeller_NS {

	static public function factory($name, Cwcl_Automodeller_Abstract $family = NULL)
	{
		return new Cwcl_Automodeller_NS($name, $family);
	}

	protected $_name;

	protected $_parsed;

	protected $_match = '/(?<prefix>.*_)?(?<type>Model_|Automodeller_)(?<name>\w+)/';

	/**
	 * To string method
	 * - full (Full unparsed name)
	 * - prefix
	 * - type
	 * - name
	 *
	 * @var string
	 */
	protected $_toString = 'full';

	/**
	 * Parses the supplied name to get the model name
	 *
	 * @param string $name 
	 * @param Cw_Automodeller_Abstract $family 
	 */
	final private function __construct($name, Cwcl_Automodeller_Abstract $family = NULL) 
	{
		$this->_name = $name;

		// Decode the model name
		if (preg_match_all($this->_match, $name, $matches)) {
			foreach ($matches as $key => $part) {
				if (is_string($key)) {
					$this->_parsed[$key] = rtrim($part[0], '_');
				}
			}
			return;
		}

		if (NULL === $family) {
			$this->_parsed['name'] = trim($name, '_');
			return;
		}

		$_ns = $family->_getNamespace();

		$_name = $_ns->components();
		array_pop($_name);
		$_name[] = $name;
		$_name = trim(implode('_', $_name), '_');

		$this->_name = $_name;

		if (preg_match_all($this->_match, $_name, $matches)) {
			foreach ($matches as $key => $part) {
				if (is_string($key)) {
					$this->_parsed[$key] = rtrim($part[0], '_');
				}
			}
		}
		else {
			$this->_parsed['name'] = $_name;
		}

	}

	public function name()
	{
		return $this->_name;
	}

	public function component($name)
	{
		return isset($this->_parsed[$name]) ? $this->_parsed[$name] : NULL;
	}

	public function components()
	{
		return $this->_parsed;
	}

	public function setToStringMode($mode)
	{
		if (in_array($mode, array('full', 'prefix', 'type', 'name'))) {
			$this->_toString = $mode;
			return $this;
		}

		throw new Cwcl_Automodeller_Exception('Cw_Automodeller_NS::__toString() can only accept: full; prefix; type; or name');
	}

	public function __toString()
	{
		// If the model is set to full, output the name
		if ($this->_toString === 'full') {
			return $this->_name;
		}

		// Get the name
		$output = $this->component($this->_toString);

		// If the output is null, return empty string
		return (NULL === $output) ? '' : $output;
	}

	public function __sleep()
	{
		return array('_name', '_toString', '_parsed', '_match');
	}
}