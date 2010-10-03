<?php
/**
 * Abstract class for Cwcl_Services_Form_Wizard_Pages, including requirement
 * for render() method, plus the serializable interface methods
 *
 * @package Services Wizard
 * @category Cwcl
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Form_Wizard_Page
	extends Cwcl_Services_Form_Wizard_Node
{
	/**
	 * Sections to display in this form
	 *
	 * @var array
	 */
	protected $_sections = array();

	/**
	 * The name of the view script to render
	 *
	 * @var string
	 */
	protected $_scriptName;

	/**
	 * The uri for this page to submit to
	 *
	 * @var string
	 */
	protected $_uri;

	/**
	 * The form submission button text
	 *
	 * @var string
	 */
	protected $_submitText = 'Submit';

	/**
	 * Controls whether a reset button is allowed
	 *
	 * @var boolean
	 */
	protected $_resetButton = FALSE;

	/**
	 * Serialises the page
	 *
	 * @param array $toSerialize 
	 * @return string
	 * @access public
	 */
	public function serialize(array $toSerialize = array())
	{
		$toSerialize += array(
			'_sections'    => $this->_sections,
			'_scriptName'  => $this->_scriptName,
			'_uri'         => $this->_uri,
			'_submitText'  => $this->_submitText,
			'_resetButton' => $this->_resetButton,
		);

		return parent::serialize($toSerialize);
	}

	/**
	 * Add sections to the page
	 *
	 * @param array $sections 
	 * @return self
	 * @access public
	 */
	public function setSections(array $sections)
	{
		foreach ($sections as $section) {
			$this->addSection($section);
		}

		return $this;
	}

	/**
	 * Get all the sections assigned to
	 * this page
	 *
	 * @return array
	 * @access public
	 */
	public function getSections()
	{
		return $this->_sections;
	}

	/**
	 * Return a particular section by
	 * specific id
	 *
	 * @param int $id 
	 * @return Cwcl_Services_Form_Wizard_Section_Abstract|void
	 * @access public
	 */
	public function getSection($id)
	{
		return isset($this->_sections[$id]) ? $this->_sections[$id] : NULL;
	}

	/**
	 * Returns the sections who's keys are supplied. Will ignore non-existent
	 * section requests
	 * 
	 * @example getSectionsBySelection(array(0,2,4,6))   // returns sections 0,2,4,6
	 *          getSectionsBySelection(array('0-6', 8))  // returns sections 0 through 6, and 8
	 *          getSectionsBySelection(array('8-4', 1))  // returns sections 8 down to 4, and 1
	 *
	 * @param array $selection 
	 * @return array
	 * @access public
	 * @throws Cwcl_Services_Form_Wizard_Exception
	 */
	public function getSectionsBySelection(array $selection)
	{
		$sections = $this->getSections();
		$selectedSections = array();

		foreach ($selection as $search) {
			$type = gettype($search);
			if ('string' == $type) {
				if ( ! preg_match_all('/(\d+)\-(\d+)/', $search, $matches)) {
						throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' incorrectly formatted range string : '.$search);
				}

				// Format start/finish values and discover range direction
				$start = intval($matches[1][0]);
				$finish = intval($matches[2][0]);
				$ascending = $start < $finish;

				/*
				 * This is a disgusting piece of code, but is optimised for
				 * looping through the defined range depending on resolved 
				 * direction.
				 * 
				 * This is faster than using php array_range() or associated
				 * methods, but looks very ugly - SdF
				 */
				for ($i = $ascending ? $start : $finish;
						$ascending ? $i <= $finish : $i >= $start; 
						$ascending ? $i++ : $i--
					) {
					if (isset($sections[$i])) {
						$selectedSections[] = $sections[$i];
					}
				}
			}
			elseif ('int' == $type) {
				if (isset($sections[$search])) {
					$selectedSections[] = $sections[$search];
				}
			}
			else {
				throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' unrecognised selection value : '.$search.' the type must be either integer or range in string');
			}
		}

		return $selectedSections;
	}

	/**
	 * A section to a new id. If the id does not exist
	 * it will be appended with the new section id NOTE that
	 * this will break concurrency.
	 * 
	 * IF the section does exist, it will be replaced
	 *
	 * @param int $id 
	 * @param Cwcl_Services_Form_Wizard_Section_Abstract $section 
	 * @return self
	 * @access public
	 * @throws Cwcl_Services_Form_Wizard_Exception
	 */
	public function setSection($id, Cwcl_Services_Form_Wizard_Section_Abstract $section)
	{
		if ( ! is_int($id)) {
			throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' section identifiers must be of type integer : '.$id.' of type '.gettype($id).' supplied.');
		}

		$this->_sections[$id] = $section;
		return $this;
	}

	/**
	 * Adds a new section to the page, appending the
	 * section to the end of the current pool
	 *
	 * @param Cwcl_Services_Form_Wizard_Section_Abstract $section 
	 * @return self
	 * @access public
	 */
	public function addSection(Cwcl_Services_Form_Wizard_Section_Abstract $section)
	{
		$this->_sections[] = $section;
		return $this;
	}

	/**
	 * Inserts a section before the supplied section
	 *
	 * @param Cwcl_Services_Form_Wizard_Section_Abstract $needle 
	 * @param Cwcl_Services_Form_Wizard_Section_Abstract $insert 
	 * @return self
	 * @access public
	 */
	public function insertSectionBefore(Cwcl_Services_Form_Wizard_Section_Abstract $needle, Cwcl_Services_Form_Wizard_Section_Abstract $insert)
	{
		if (FALSE === ($key = array_search($needle, $this->_sections, TRUE))) {
			return $this;
		}

		return $this->insertSection($insert, $key, TRUE);
	}

	/**
	 * Inserts a section after the supplied section
	 *
	 * @param Cwcl_Services_Form_Wizard_Section_Abstract $needle 
	 * @param Cwcl_Services_Form_Wizard_Section_Abstract $insert 
	 * @return self
	 * @access public
	 */
	public function insertSectionAfter(Cwcl_Services_Form_Wizard_Section_Abstract $needle, Cwcl_Services_Form_Wizard_Section_Abstract $insert)
	{
		if (FALSE === ($key = array_search($needle, $this->_sections, TRUE))) {
			return $this;
		}

		return $this->insertSection($insert, $key);
	}

	/**
	 * Removes a section from the page
	 *
	 * @param int $id 
	 * @return void
	 * @access public
	 */
	public function removeSection($id)
	{
		if (isset($this->_sections[$id])) {
			unset($this->_sections[$id]);
		}
		return $this->refactorSections();
	}

	/**
	 * Clears all the sections from this page
	 *
	 * @return void
	 * @author Sam de Freyssinet
	 */
	public function clearSections()
	{
		$this->setSections(array());
		return;
	}

	/**
	 * Get the view script name
	 *
	 * @return string
	 * @access public
	 */
	public function getScriptName()
	{
		return $this->_scriptName;
	}

	/**
	 * Set the view script name
	 *
	 * @param string $scriptName 
	 * @return self
	 * @access public
	 */
	public function setScriptName($scriptName)
	{
		$this->_scriptName = $scriptName;
		return $this;
	}

	/**
	 * Gets the uri for the page
	 *
	 * @return string
	 * @access public
	 */
	public function getUri()
	{
		return $this->_uri;
	}

	/**
	 * Sets the submit text for the page button
	 *
	 * @param string $submitText 
	 * @return self
	 * @access public
	 */
	public function setSubmitText($submitText)
	{
		$this->_submitText = $submitText;
		return $this;
	}

	/**
	 * Gets the submit text for the page button
	 *
	 * @return string
	 * @access public
	 */
	public function getSubmitText()
	{
		return $this->_submitText;
	}

	/**
	 * Sets the reset button value, if TRUE
	 * then a clear form button will be
	 * added to the page
	 *
	 * @param boolean $value 
	 * @return self
	 * @access public
	 */
	public function setResetButton($value)
	{
		$this->_resetButton = (bool) $value;
		return $this;
	}

	/**
	 * Gets the reset button value
	 *
	 * @return boolean
	 * @access public
	 */
	public function getResetButton()
	{
		return $this->_resetButton;
	}

	/**
	 * Sets the uri for the current page
	 *
	 * @param string $uri 
	 * @return self
	 * @access public
	 */
	public function setUri($uri)
	{
		$this->_uri = $uri;
		return $this;
	}

	/**
	 * The pages valid state based on the models contained
	 * within
	 *
	 * @return void|boolean
	 * @access public
	 */
	public function isValid()
	{
		$result = NULL;
		$sections = $this->getSections();

		foreach ($section as $section) {
			$valid = $section->isValid();
			if (FALSE !== $result) {
				$result = $valid;
			}
		}

		return $result;
	}

	/**
	 * Renders the form
	 *
	 * @param Zend_Form $form 
	 * @param array $sections [Optional] only load specific sections from this page
	 * @return Zend_View
	 * @access public
	 */
	public function render(Zend_Form $form, array $sections = array())
	{
		// Try to load the view
		if (NULL === ($view = $this->getView())) {
			throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' was unable to load view for : '.get_class($this));
		}

		// Load all sections
		if ( ! $sections) {
			$sections = $this->getSections();
		}
		// Or just the ones defined
		else {
			$sections = $this->getSectionsbySelection($sections);
		}

		$renderedSections = array();
		// Process each section rendering the form elements
		foreach ($sections as $id => $section) {
//			var_dump($section);
			$result = $section->render($form);

			if ($result instanceof Zend_View) {
				$renderedSections[] = $result;
			}
		}

		// Setup the form uri
		$uri = $this->getUri();
		$form->setAction((NULL === $uri) ? '' : $uri);

		// Add the reset button if required
		if (TRUE === ($reset = $this->getResetButton())) {
			$form->addElement($form->createElement('reset', 'resetForm', array('label' => 'Clear form')));
		}

		// Finally, add the submit button
		$form->addElement($form->createElement('submit', 'submit', array('label' => $this->getSubmitText())));

		// Load the view
		$view->title = $this->getTitle();
		$view->description = $this->getDescription();
		$view->form = $form;

		if ($renderedSections) {
			$view->content = $renderedSections;
		}

		// Return the form with the added sections
		return $view->render($this->getScriptName());
	}

	/**
	 * Inserts a section into the sections array either before or
	 * after the supplied key
	 *
	 * @param Cwcl_Services_Form_Wizard_Section_Abstract $section 
	 * @param mixed $key 
	 * @param boolean $insertBefore [Optional]
	 * @return self
	 * @access protected
	 */
	protected function insertSection(Cwcl_Services_Form_Wizard_Section_Abstract $section, $key, $insertBefore = FALSE)
	{
		$newSections = array();
		$sections = $this->getSections();

		foreach ($sections as $_key => $value) {
			if ($key === $_key) {
				if ($insertBefore) {
					$newSections[] = $location;
					$newSections[] = $value;
				}
				else {
					$newSections[] = $value;
					$newSections[] = $location;
				}
			}
			else {
				$newSections[] = $value;
			}
		}

		return $this->setSections($newSections);
	}

	/**
	 * Refactors the sections so they are ordered
	 * sequentially incrementing integers
	 *
	 * @return void
	 * @access protected
	 */
	protected function refactorSections()
	{
		if ( ! ($currentSections = $this->getSections())) {
			return;
		}

		$newSections = array();
		foreach ($currentSections as $value) {
			$newSections[] = $value;
		}

		$this->setSections($newSections);
		return;
	}
}