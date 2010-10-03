<?php
/**
 * Abstract class for Cwcl_Services_Form_Wizard_Pages, including requirement
 * for render() method, plus the serializable interface methods
 *
 * @package Services Wizard
 * @category Cwcl
 * @abstract
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Form_Wizard_Page_Content
	extends Cwcl_Services_Form_Wizard_Page
{
	/**
	 * The page content (HTML/Zend_View)
	 *
	 * @var string|Zend_View
	 */
	protected $_content;

	/**
	 * Returns the content set to this page
	 *
	 * @return void|string|Zend_View
	 * @access public
	 */
	public function getContent()
	{
		return $this->_content;
	}

	/**
	 * Sets content to this page
	 *
	 * @param string|Zend_View $content 
	 * @return self
	 * @access public
	 */
	public function setContent($content)
	{
		if ($content instanceof Zend_View) {
			$this->_content = $content;
			return $this;
		}

		if (is_scalar($content)) {
			$this->_content = (string) $content;
			return $this;
		}

		throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' content type not recognised : '.gettype($content).', must be Zend_View or scalar.');
	}

	/**
	 * Renders the page to a string ready for display
	 *
	 * @param  Zend_Form
	 * @return string
	 * @access public
	 * @throws Cwcl_Services_Form_Wizard_Exception
	 */
	public function render(Zend_Form $form)
	{
		// Get the current view
		if ( ! ($view = $this->getView()) instanceof Zend_View) {
			throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' no view found, unable to render');
		}

		// Set the title and description
		$view->title = $this->getTitle();
		$view->description = $this->getDescription();

		// Apply rendered form to the view
		$view->content = $this->getContent();
		$view->form = $form;

		// Return the view
		return $view;
	}

	/**
	 * The pages valid state based on the models contained
	 * within. Always returns true in this case as content
	 * pages are static
	 *
	 * @return void|boolean
	 * @access public
	 */
	public function isValid()
	{
		return TRUE;
	}

	/**
	 * Serialises the model for persistent storage
	 * or transmission
	 *
	 * [SPL] Serializable
	 * 
	 * @param array $toSerialize [Optional]
	 * @return string
	 * @access public
	 * @abstract
	 */
	public function serialize(array $toSerialize = array())
	{
		$toSerialize += array(
			'_content'    => $this->_content,
		);
	}
}