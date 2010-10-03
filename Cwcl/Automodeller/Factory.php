<?php
/**
 * Automodeller factory method for creating new models, allows chaining.
 *
 * @package  Cwcl
 * @category Automodeller
 * @uses     Cwcl_Automodeller_Abstract
 * @uses     ReflectionClass
 * @author   Sam de Freyssinet
 */
class Cwcl_Automodeller_Factory {

	/**
	 * Contains existing models created using the factory method
	 *
	 * @var  array
	 */
	static protected $_instances = array();

	/**
	 * Factory method for creating new Automodeller
	 * objects
	 *
	 * @param string $model 
	 * @param mixed $id [Optional]
	 * @return Cwcl_Automodeller_Abstract
	 * @static
	 * @throws Cwcl_Automodeller_Exception
	 */
	public static function factory($model, $id = NULL, array $options = array())
	{
		// If this model has already been instantiated
		if (Cwcl_Automodeller_Factory::$_instances[$model]) {
			// Clone the existing instance
			$model = clone(Cwcl_Automodeller_Factory::$_instances[$model]);

			// Apply any options
			if ($options) {
				$model->__initOptions($options);
			}

			// Return the model
			return ($id === NULL) ? $model : $model->find($id);
		}

		$reflection = new ReflectionClass($model);

		if ( ! $reflection->isSubclassOf('Cwcl_Automodeller_Abstract')) {
			throw new Cwcl_Automodeller_Exception(__METHOD__.' '.get_class($model).' is not an instanceof Cwcl_Automodeller_Abstract');
		}

		// Create a new instance of the model and cache it and return it
		return Cwcl_Automodeller_Factory::$_instances[$model] = $reflection->newInstanceArgs(array($id, $options));
	}

	/**
	 * Maintains factory pattern
	 *
	 * @final
	 */
	final private function __construct() {}
}