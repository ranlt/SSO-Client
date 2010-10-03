<?php

abstract class Cw_Automodeller_Query_From extends Cw_Automodeller_Query_Abstract {

	/**
	 * FROM ... statement
	 *
	 * @var array
	 */
	protected $_from = array();

	/**
	 * Overloading the parent sleep
	 * magic method to add the _from
	 * property
	 *
	 * @return array
	 * @access public
	 */
	public function __sleep()
	{
		$array = parent::__sleep();
		$array[] = '_from';
		return $array;
	}

	/**
	 * Creates a from statement
	 *
	 * @param string|array $from 
	 * @return self
	 * @access public
	 */
	public function from($from)
	{
		if (is_string($from)) {
			$this->_from[] = $from;
		}
		elseif (is_array($from)) {
			$this->_asStatementConstruct($from, $this->_from);
		}

		return $this;
	}

	/**
	 * Renders the FROM statement
	 *
	 * @return string
	 * @access protected
	 */
	protected function _fromRender()
	{
		return 'FROM '.$this->_asRender($this->_from);
	}

}