<?php

abstract class Cw_Automodeller_Query_Abstract {

	/**
	 * AND WHERE ..., OR WHERE... items
	 *
	 * @var array
	 */
	protected $_where = array(), $_orWhere = array();

	/**
	 * LIMIT and OFFSET statements
	 *
	 * @var array
	 */
	protected $_limit = array();

	/**
	 * ORDER BY statements
	 *
	 * @var array
	 */
	protected $_orderBy = array();

	/**
	 * Parameters to be bound to the query
	 *
	 * @var array
	 */
	protected $_parameters = array();

	/**
	 * Serialisation properties
	 *
	 * @return array
	 * @access public
	 */
	public function __sleep()
	{
		return array('_where', '_orWhere', '_limit', '_orderBy', '_parameters');
	}

	/**
	 * Handles casting to string
	 *
	 * @return string
	 * @access public
	 */
	public function __toString()
	{
		return $this->render();
	}


	public function where($expression, $parameter = NULL)
	{
		$this->_filterStatementConstruct($this->_where, $expression, $parameter);

		return $this;
	}

	public function orWhere($expression, $parameter = NULL)
	{
		$this->_filterStatementConstruct($this->_orWhere, $expression, $parameter);

		return $this;
	}

	public function limit($limit, $offset = NULL)
	{
		if (is_array($limit)) {
			// Explicit assignment used to ensure correct formatting
			list($limit, $offset) = $limit;
			$this->_limit = array('limit' => $limit, 'offset' => $offset);
		}
		else {
			$this->_limit = array('limit' => $limit, 'offset' => $offset);
		}

		return $this;
	}

	/**
	 * ORDER BY statement, can accept multiple columns to order
	 * by
	 *
	 * @param string|array $column 
	 * @param string $direction [Optional]
	 * @return self
	 */
	public function orderBy($column, $direction = 'DESC')
	{
		if (is_array($column)) {
			$this->_orderBy = $column;
		}
		else {
			$this->_orderBy = array($column => $direction);
		}

		return $this;
	}

	/**
	 * Returns the parameters to bind to
	 * this query. Used by Zend_Db
	 *
	 * @return array
	 */
	public function _getParameters()
	{
		return $this->_parameters;
	}

	/**
	 * Renders the query into ANSI SQL
	 * format.
	 *
	 * @return string
	 */
	abstract public function render();

	/**
	 * Renders any AS statements
	 *
	 * @param array $statements 
	 * @return string
	 */
	protected function _asRender(array $statements)
	{
		$items = array();

		foreach ($statements as $key => $value) {
			if (is_int($key)) {
				$items[] = $value;
			}
			else {
				$items[] = $key.' AS '.$value;
			}
		}
		$output = implode(', ', $items);

		return $output;
	}

	/**
	 * Renders the WHERE statements
	 *
	 * @return string
	 */
	protected function _whereRender()
	{
		// Query store
		$where = '';

		// Storage for the query segments
		$_andWhere = array();
		$_orWhere = array();

		// AND WHERE
		foreach ($this->_where as $param) {
			$_andWhere[] = $param;
		}

		// OR WHERE
		foreach ($this->_orWhere as $param) {
			$_orWhere[] = $param;
		}

		if ($_andWhere) {
			$where = implode(' AND ', $_andWhere);
		}

		if ($_orWhere) {
			$w = implode(' OR ', $_orWhere);
			$where .= $w;
		}

		if ( ! empty($where)) {
			return 'WHERE '.$where;
		}
		else {
			return $where;
		}
	}

	/**
	 * Renders the LIMIT statement
	 *
	 * @return string
	 */
	protected function _limitRender()
	{
		if ($this->_limit) {
			if (NULL === $this->_limit['offset']) {
				return 'LIMIT '.$this->_limit['limit'];
			}
			else {
				return 'LIMIT '.$this->_limit['offset'].', '.$this->_limit['limit'];
			}
		}

		return '';
	}

	/**
	 * Order By rendering
	 *
	 * @return string
	 */
	protected function _orderByRender()
	{
		if ($this->_orderBy) {
			$_orderBy = array();

			foreach ($this->_orderBy as $column => $direction) {
				$_orderBy[] = $column.' '.strtoupper($direction);
			}

			$ob = implode(', ', $_orderBy);

			return 'ORDER BY '.$ob;
		}
		return '';
	}

	/**
	 * Creates a filter statement based on
	 * the expression, parameter and assigns
	 * it to the target
	 *
	 * @param string $expression 
	 * @param mixed $parameter 
	 * @param array $target 
	 * @return void
	 */
	protected function _filterStatementConstruct(array & $target, $expression, $parameter = NULL)
	{
		if (is_array($expression)) {
			foreach ($expression as $expr => $param) {
				$target[] = $expr;

				// Parse the parameters
				$this->_parseParameters($expr, $param);
			}
		}
		else {
			$target[] = $expression;
			$this->_parseParameters($expression, $parameter);
		}
	}

	/**
	 * Parses any parameter tokens contained in the
	 * supplied expression, and binds the parameter
	 * value to it.
	 *
	 * @param string $expr 
	 * @param mixed $param 
	 * @return void
	 * @access protected
	 */
	protected function _parseParameters($expr, $param = NULL)
	{
		if (NULL === $param) {
			return;
		}

		if (preg_match_all('/(?<param>:\w+)/', $expr, $matches)) {
			$this->_parameters[$matches['param'][0]] = $param;
		}
	}

	/**
	 * Constructs the statement with AS clauses
	 * considered
	 *
	 * @param array $query 
	 * @param string $target 
	 * @return void
	 * @access protected
	 */
	protected function _asStatementConstruct(array $query, array & $target)
	{
		foreach ($query as $key => $value) {
			if (is_int($key)) {
				$target[] = $value;
			}
			else {
				$target[$key] = $value;
			}
		}
	}
}