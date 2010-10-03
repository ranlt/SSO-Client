<?php

class Cw_Automodeller_Query_Delete extends Cw_Automodeller_Query_From {

	/**
	 * Constructor
	 *
	 * @param string $from 
	 * @param array $where 
	 * @param array $limit 
	 * @access public
	 */
	public function __construct($from = NULL, array $where = NULL, array $limit = NULL)
	{
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

		// DELETE
		$statement['delete'] = 'DELETE';

		// FROM
		$statement['from'] = $this->_fromRender();

		// WHERE, IN (et al)
		$statement['where'] = $this->_whereRender();

		// LIMIT OFFSET
		$statement['limit'] = $this->_limitRender();

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


}