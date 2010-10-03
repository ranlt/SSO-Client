<?php
/**
 * Cwcl_Services_Form_Wizard is a multi-form container/wizard
 * for Scil models. It contains pages, models and views.
 * 
 * All validation is handled by the Scil model internally,
 * with the model decorators outputting errors for fields
 * if required.
 *
 * @package Services Form Wizard
 * @category Cwcl
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Form_Wizard implements Serializable, Countable
{
	/**
	 * The pages within this wizard
	 *
	 * @var array
	 */
	protected $_pages = array();

	/**
	 * The current page
	 *
	 * @var int
	 */
	protected $_currentPage;

	/**
	 * Form name/id
	 *
	 * @var string
	 */
	protected $_name;

	/**
	 * Construct the object, injecting dependencies as
	 * required
	 *
	 * @param array $dependencies 
	 * @access public
	 */
	public function __construct(array $dependencies = array())
	{
		foreach ($dependencies as $key => $value) {
			$_key = 'set'.ucfirst($key);

			if ( ! method_exists($this, $_key)) {
				throw new Cwcl_Services_Form_Wizard_Exception('Trying to set a property that has no accessor method : '.$key);
			}

			// Set the value to the model using accessor method
			$this->$_key($value);
		}

		// Initialise the current page
		$this->_currentPage = 0;

		// Initialise the Wizard ID
		if (NULL === $this->_name) {
			$this->setName($this->getName());
		}
	}

	/**
	 * Return the name of this wizard
	 *
	 * @return string
	 * @access public
	 */
	public function getName()
	{
		return isset($this->_name) ? $this->_name : spl_object_hash($this);
	}

	/**
	 * Sets the name and id of this form
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
	 * Add a new page to the end of the wizard
	 *
	 * @param Cwcl_Services_Form_Wizard_Page $page 
	 * @return self
	 * @access public
	 */
	public function addPage(Cwcl_Services_Form_Wizard_Page $page)
	{
		$this->_pages[] = $page;
		return $this;
	}

	/**
	 * Get the page at a specified number
	 *
	 * @param integer $number 
	 * @return Cwcl_Services_Form_Wizard_Page|void
	 * @access public
	 */
	public function getPage($number)
	{
		return isset($this->_pages[$number]) ? $this->_pages[$number] : NULL;
	}

	/**
	 * Set the page to a specific index
	 *
	 * @param integer $page 
	 * @return self
	 * @access public
	 */
	public function setPage($number)
	{
		if ( ! isset($this->_pages[$number])) {
			throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' The page index : '.$number.' does not exists');
		}

		$this->_currentPage = $number;
		return $this;
	}


	/**
	 * Get the breadcrumb for the wizard, includes all
	 * page numbers, titles, descriptions and current
	 * status
	 *
	 * @return array
	 * @access public
	 */
	public function getBreadcrumb()
	{
		$pages = $this->getPages();
		$currentPage = $this->getCurrentPage();
		$breadcrumb = array();

		foreach ($pages as $number => $page) {
			$breadcrumb[$number] = array(
				'name'        => $page->getName(),
				'title'       => $page->getTitle(),
				'description' => $page->getDescription(),
				'currentPage' => ($currentPage === $number),
				'uri'         => $page->getUri(),
			);
		}

		return $breadcrumb;
	}

	/**
	 * Sets the current page number
	 *
	 * @param integer $number 
	 * @return self
	 * @access public
	 */
	public function setCurrentPage($number)
	{
		$this->_currentPage = (int) $number;
		return $this;
	}

	/**
	 * Gets the current page number (not page)
	 *
	 * @return integer
	 * @access public
	 */
	public function getCurrentPage()
	{
		return $this->_currentPage;
	}

	/**
	 * Moves onto the next page
	 *
	 * @return self
	 * @access public
	 */
	public function nextPage()
	{
		$this->_currentPage++;

		if ($this->_currentPage >= $this->count()) {
			$this->_currentPage = 0;
		}

		return $this;
	}

	/**
	 * Moves to the previous page
	 *
	 * @return self
	 * @access public
	 */
	public function previousPage()
	{
		$this->_currentPage--;

		if ($this->_currentPage < 0) {
			$this->_currentPage = $this->count()-1;
		}

		return $this;
	}

	/**
	 * Loads the current page
	 *
	 * @return Cwcl_Services_Form_Page
	 * @access public
	 */
	public function loadCurrentPage()
	{
		return $this->getPage($this->getCurrentPage());
	}

	/**
	 * Tests if the whole wizard is valid, or
	 * a specific page if supplied
	 *
	 * @param integer $page [Optional]
	 * @return void|boolean
	 * @access public
	 */
	public function isValid($page = FALSE)
	{
		if (FALSE !== $page) {
			$page = $this->getPage($page);
			
			return ($page instanceof Cwcl_Services_Form_Wizard_Page) ?
				$page->isValid() :
				NULL;
		}

		$pages = $this->getPages();
		$result = NULL;

		foreach ($pages as $page => $object) {
			$valid = $object->isValid();
			if (FALSE !== $result) {
				$result = $valid;
			}
		}

		return $result;
	}

	/**
	 * Sets pages to this model, checking for correct
	 * type
	 *
	 * @param array $pages 
	 * @return self
	 * @access public
	 */
	public function setPages(array $pages)
	{
		foreach ($pages as $page) {
			$this->addPage($page);
		}
		return $this;
	}

	/**
	 * Get all the pages from this method
	 *
	 * @return array
	 * @access public
	 */
	public function getPages()
	{
		return $this->_pages;
	}

	/**
	 * Remove all pages from the wizard
	 *
	 * @return self
	 * @access public
	 */
	public function clearPages()
	{
		$this->_pages = array();
		return $this;
	}

	/**
	 * Removes a page from the wizard
	 *
	 * @param integer $number 
	 * @return void
	 * @access public
	 */
	public function removePage($number)
	{
		if ( ! isset($this->_pages[$number])) {
			return;
		}

		unset($this->_pages[$number]);
	}

	/**
	 * Serialises the wizard to a string
	 *
	 * @param array $toSerialize [Optional]
	 * @return string
	 * @access public
	 */
	public function serialize(array $toSerialize = array())
	{
		$toSerialize += array(
			'_pages'       => $this->_pages,
			'_currentPage' => $this->_currentPage,
			'_name'        => $this->_name,
		);

		return serialize($toSerialize);
	}

	/**
	 * Unserialises the wizard from a string
	 * 
	 * [SPL] Serializable
	 *
	 * @param string $string 
	 * @return void
	 * @access public
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

	/**
	 * Counts the number of pages in the wizard
	 * 
	 * [SPL] Countable
	 *
	 * @return integer
	 * @access public
	 */
	public function count()
	{
		return count($this->_pages);
	}

	/**
	 * Renders the wizard at the current page
	 *
	 * @param array $zendFormOptions [Optional] Zend_Form options
	 * @param array $sections [Optional] sections to load, leave empty for all sections
	 * @param int $page [Optional] override which page is shown
	 * @return Zend_View
	 * @access public
	 */
	public function render(array $zendFormOptions = array(), array $sections = array(), $page = NULL)
	{
		// If page has been supplied, set current page to value
		if (NULL !== $page) {
			$this->setCurrentPage($page);
		}

		// Throw an exception if the current page is not available
		if (NULL === ($page = $this->loadCurrentPage())) {
			throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' there is no page available to load, current page : '.$this->getCurrentPage());
		}

		// Get the form name
		$name = $this->getName();

		// Ensure that a form name/id is set to the form
		$zendFormOptions += array(
			'name' => $name,
		);

		// Create zend form
		$form = new Zend_Form($zendFormOptions);
		$form->addElement('hidden', 'currentPage', array('value' => $this->getCurrentPage()))
			->addElement('hidden', 'wizardId', array('value' => $name));

		// Render current page
		return $page->render($form, $sections);
	}
}