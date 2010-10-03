<?php
/**
 * Abstract Join Class is designed to support join results that
 * span multiple tables.
 * 
 * This is a hack to allow structured questions join results to
 * be used with Automodeller, until the structured questions
 * library can be refactored completely.
 * 
 * This code should and must be replaced.
 *
 * @package  Cwcl
 * @category Automodeller
 * @author   Sam de Freyssinet
 */
class Cw_Automodeller_Abstract_Join extends Cw_Automodeller_Abstract
{
	/**
	 * Disallowed method
	 *
	 * @return void
	 */
	public function save()
	{
		throw new Cw_Automodeller_Exception(__METHOD__.' Abstract Join models do not support manipulation');
	}

	/**
	 * Disallowed method
	 *
	 * @return void
	 */
	public function find()
	{
		throw new Cw_Automodeller_Exception(__METHOD__.' Abstract Join models do not support manipulation');
	}

	/**
	 * Disallowed method
	 *
	 * @return void
	 */
	public function findAll()
	{
		throw new Cw_Automodeller_Exception(__METHOD__.' Abstract Join models do not support manipulation');
	}

	/**
	 * Disallowed method
	 *
	 * @return void
	 */
	public function delete()
	{
		throw new Cw_Automodeller_Exception(__METHOD__.' Abstract Join models do not support manipulation');
	}

	/**
	 * Disallowed method
	 *
	 * @param string $select 
	 * @return void
	 */
	public function select($select)
	{
		throw new Cw_Automodeller_Exception(__METHOD__.' Abstract Join models do not support manipulation');
	}

	/**
	 * Disallowed method
	 *
	 * @param string $expression 
	 * @param string $parameter 
	 * @return void
	 */
	public function where($expression, $parameter = NULL)
	{
		throw new Cw_Automodeller_Exception(__METHOD__.' Abstract Join models do not support manipulation');
	}

	/**
	 * Disallowed method
	 *
	 * @param string $expression 
	 * @param string $parameter 
	 * @return void
	 */
	public function orWhere($expression, $parameter = NULL)
	{
		throw new Cw_Automodeller_Exception(__METHOD__.' Abstract Join models do not support manipulation');
	}

	/**
	 * Disallowed method
	 *
	 * @param string $limit 
	 * @param string $offset 
	 * @return void
	 */
	public function limit($limit, $offset = NULL)
	{
		throw new Cw_Automodeller_Exception(__METHOD__.' Abstract Join models do not support manipulation');
	}

	/**
	 * Disallowed method
	 *
	 * @param string $column 
	 * @param string $direction 
	 * @return void
	 */
	public function orderBy($column, $direction = 'DESC')
	{
		throw new Cw_Automodeller_Exception(__METHOD__.' Abstract Join models do not support manipulation');
	}

	/**
	 * Disallowed method
	 *
	 * @param string $column 
	 * @return void
	 */
	public function groupBy($column)
	{
		throw new Cw_Automodeller_Exception(__METHOD__.' Abstract Join models do not support manipulation');
	}

	/**
	 * Disallowed method
	 *
	 * @param string $type 
	 * @param string $table 
	 * @param string $on 
	 * @return void
	 */
	public function join($type, $table, $on)
	{
		throw new Cw_Automodeller_Exception(__METHOD__.' Abstract Join models do not support manipulation');
	}

	/**
	 * Overloading the description method
	 *
	 * @return array
	 * @access protected
	 * @throws Cw_Automodeller_Exception
	 */
	protected function _getDescription()
	{
		if (NULL !== $this->_description) {
			return $this->_description;
		}

		throw new Cw_Automodeller_Exception(__METHOD__.' Model has no description defined. All Join models must be described within the model.');
	}
}