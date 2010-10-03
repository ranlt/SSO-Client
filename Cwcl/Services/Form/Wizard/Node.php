<?php
/**
 * The Wizard Node is a base template for all wizard entities.
 * It provides the Serializable interface plus base methods
 * and properties for all pages, sections and nodes.
 *
 * @package Services Form Wizard
 * @category Cwcl
 * @author Sam de Freyssinet
 * @abstract
 */
abstract class Cwcl_Services_Form_Wizard_Node
	implements Serializable
{

	/**
	 * Initialises a new empty Zend_View, including setting base path
	 *
	 * @return Zend_View
	 * @access public
	 * @throws Cwcl_Services_Form_Wizard_Exception
	 * @static
	 */
	static public function initView()
	{
		$frontController = Zend_Controller_Front::getInstance();

		$request = $frontController->getRequest();
		$module  = $request->getModuleName();
		$dirs    = $frontController->getControllerDirectory();
		if (empty($module) || !isset($dirs[$module])) {
			$module = $frontController->getDispatcher()->getDefaultModule();
		}
		$baseDir = dirname($dirs[$module]) . DIRECTORY_SEPARATOR . 'views';
		if (!file_exists($baseDir) || !is_dir($baseDir)) {
			throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' Missing base view directory ("' . $baseDir . '")');
		}

		return new Zend_View(array('basePath' => $baseDir));
	}


	/**
	 * The name of the page
	 *
	 * @var string
	 */
	protected $_name;

	/**
	 * The title of the form page
	 *
	 * @var string
	 */
	protected $_title;

	/**
	 * The description of the form page
	 *
	 * @var string
	 */
	protected $_description;
	/**
	 * The view for this page
	 *
	 * @var Zend_View
	 */
	protected $_view;

	/**
	 * Constructor, applies dependencies to the model
	 *
	 * @param array $dependencies [Optional]
	 * @access public
	 * @throws Cwcl_Services_Form_Wizard_Exception
	 */
	public function __construct(array $dependencies = array())
	{
		foreach ($dependencies as $key => $value) {
			$key = 'set'.ucfirst($key);
			if ( ! method_exists($this, $key)) {
				throw new Cwcl_Services_Form_Wizard_Exception('The method : '.$key.' does not exist!');
			}
			$this->$key($value);
		}

		// Create empty view
		$this->setView(self::initView());
	}

	/**
	 * Handles model being cast to string
	 *
	 * @return string
	 * @access public
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Returns the name of this page
	 *
	 * @return string
	 * @access public
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Sets the name of this page
	 *
	 * @param string $name
	 * @return self
	 * @access public
	 */
	public function setName($name)
	{
		$this->_name = (string) $name;
		return $this;
	}

	/**
	 * Return the title of this page
	 *
	 * @return string
	 * @access public
	 */
	public function getTitle()
	{
		return $this->_title;
	}

	/**
	 * Set the title of this page
	 *
	 * @param string $title 
	 * @return self
	 * @access public
	 */
	public function setTitle($title)
	{
		$this->_title = (string) $title;
		return $this;
	}

	/**
	 * Return the title of this page
	 *
	 * @return string
	 * @access public
	 */
	public function getDescription()
	{
		return $this->_description;
	}

	/**
	 * Set the title of this page
	 *
	 * @param string $title 
	 * @return self
	 * @access public
	 */
	public function setDescription($description)
	{
		$this->_description = (string) $description;
		return $this;
	}

	/**
	 * Get the view assigned to this wizard
	 *
	 * @return Zend_View
	 * @access public
	 */
	public function getView()
	{
		return $this->_view;
	}

	/**
	 * Set a view to this wizard. The view will be
	 * displayed on all pages
	 *
	 * @param Zend_View $view 
	 * @return self
	 * @access public
	 */
	public function setView(Zend_View $view)
	{
		$this->_view = $view;
		return $this;
	}

	/**
	 * Renders the page to a string ready for display
	 *
	 * @param  Zend_Form $form
	 * @return Zend_View
	 * @access public
	 * @throws Cwcl_Services_Form_Wizard_Exception
	 * @abstract
	 */
	abstract public function render(Zend_Form $form);

	/**
	 * Serialises the model for persistent storage
	 * or transmission
	 *
	 * [SPL] Serializable
	 * 
	 * @param array $toSerialize [Optional]
	 * @return string
	 * @access public
	 */
	public function serialize(array $toSerialize = array())
	{
		$toSerialize += array(
			'_name'        => $this->_name,
			'_title'       => $this->_title,
			'_description' => $this->_description,
			'_view'        => $this->_view,
		);

		return serialize($toSerialize);
	}

	/**
	 * Unserialises a model based on the supplied
	 * string.
	 * 
	 * [SPL] Serializable
	 *
	 * @param string $string 
	 * @return void
	 * @access public
	 * @throws Cwcl_Services_Form_Wizard_Exception
	 */
	public function unserialize($string)
	{
		$unserialized = unserialize($string);

		if ( ! is_array($unserialized)) {
			throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' failed to unserialise model using : '.$string);
		}

		foreach ($unserialized as $key => $value) {
			if ( ! property_exists($this, $key)) {
				throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' unknown property : '.$key.' for class : '.get_class($this));
			}

			$this->$key = $value;
		}
	}
}