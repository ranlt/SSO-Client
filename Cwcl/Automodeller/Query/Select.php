<?php
/**
 * Automodeller Query Builder
 * 
 * Provides a simple interface to build sql queries
 * for execution
 *
 * @package Cw_Automodeller
 * @author Sam Clark
 */
class Cw_Automodeller_Query_Select extends Cw_Automodeller_Query_From {

	/**
	 * SELECT ... items
	 *
	 * @var array
	 */
	protected $_select = array();

	/**
	 * JOIN statements
	 *
	 * @var array
	 */
	protected $_join = array();

	/**
	 * Standard constructor, with optional initial
	 * query parameters
	 *
	 * @param string|array $select 
	 * @param string|array $from 
	 * @param array $where 
	 * @param array $limit 
	 * @access public
	 */
	public function __construct($select = '*', $from = NULL, array $where = NULL, array $limit = NULL)
	{
		if (NULL !== $select) {
			$this->select($select);
		}

		if (NULL !== $from) {
			$this->from($from);
		}

		if (NULL !== $where) {
			$this->where($where);
		}

		if (NULL !== $limit) {
			$this->limit($limit);
		}
	}

	/**
	 * Serialisation properties
	 *
	 * @return array
	 * @access public
	 */
	public function __sleep()
	{
		$sleep = parent::__sleep();
		$sleep[] = '_select';
		$sleep[] = '_join';
		return $sleep;
	}

	/**
	 * SELECT ... statements
	 *
	 * @param string|array $select 
	 * @return self
	 * @access public
	 */
	public function select($select)
	{
		// If this select statement is a string
		if (is_string($select)) {
			// Add it straight to the select
			$this->_select[] = $select;
		}
		// Else if it is an array
		elseif (is_array($select)) {
			$this->_asStatementConstruct($select, $this->_select);
		}

		// Return this
		return $this;
	}

	/**
	 * JOIN statement
	 *
	 * @param string $type [inner, left, right]
	 * @param string $table 
	 * @param string|array $on 
	 * @return self
	 * @access public
	 */
	public function join($type = NULL, $table, $on)
	{
		$type === NULL ? $this->_join[][] = array($table => $on) : $this->_join[$type][] = array($table => $on);

		return $this;
	}

	/**
	 * Renders the query into ANSI SQL
	 * format.
	 *
	 * @return string
	 * @access public
	 */
	public function render()
	{
		// Statement parts
		$statement = array();

		// SELECT
		$statement['select'] = $this->_selectRender();

		// FROM
		$statement['from'] = $this->_fromRender();

		// JOIN
		$statement['join'] = $this->_joinRender();

		// WHERE, IN (et al)
		$statement['where'] = $this->_whereRender();

		// LIMIT OFFSET
		$statement['limit'] = $this->_limitRender();

		// ORDERBY
		$statement['orderby'] = $this->_orderByRender();

		// Setup the output string
		$output = '';
		foreach ($statement as $component) {
			if ('' !== $component) {
				$output .= $component.' ';
			}
		}

		// Return the statement witout trailing whitespace
		return rtrim($output);
	}

	/**
	 * Renders the SELECT statement
	 *
	 * @return string
	 * @access protected
	 */
	protected function _selectRender()
	{
		return 'SELECT '.$this->_asRender($this->_select);
	}

	/**
	 * Renders the JOIN statements
	 *
	 * @return string
	 * @access protected
	 */
	protected function _joinRender()
	{
		$join = '';

		// If there is no join, get out of here
		if ( ! $this->_join) {
			return $join;
		}

		// will store the composed join predicate
		$_joins = array();

		foreach ($this->_join as $type => $statements) {
			
			foreach ($statements as $_j) {
				
				// @todo: should do a test on $_joinList being an array
				list($_table, $_joinList) = each($_j);

				$_joinQuery = is_int($type) ? sprintf('JOIN %s', $_table) : sprintf('%s JOIN %s', $type, $_table);

				$_joinSub = '';

				/*
				 * as joins can be either singular or multi-conditional,
				 * the array needs to be iterated over for the full condition
				 * set.
				 */
				if (is_array($_joinList)) {
					foreach ($_joinList as $_join) {
							if (empty($_joinSub)) {
								$_joinSub .= sprintf(' ON %s', $_join);
							} else {
								$_joinSub .= sprintf(' AND %s', $_join);
						}
					}
				}
				else {
					$_joinSub = sprintf(' ON %s',$_joinList);
				}

				$_joins[] = $_joinQuery . $_joinSub;
			}
		}

		// Render all the joins predicates into one composite predicate
		$join = implode(' ', $_joins);
		
		return $join;
	}
}