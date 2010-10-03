<?php

class Cwcl_Services_Form_Wizard_Section_Content
	extends Cwcl_Services_Form_Wizard_Section_Abstract
{
	/**
	 * Renders the page to a string ready for display
	 *
	 * @param Zend_Form $form
	 * @return Zend_View
	 * @access public
	 * @throws Cwcl_Services_Form_Wizard_Exception
	 * @abstract
	 */
	public function render(Zend_Form $form)
	{
		if (NULL === ($view = $this->getView())) {
			throw new Cwcl_Services_Form_Wizard_Exception(__METHOD__.' no view available to load');
		}

		// Add this model properties to the view
		$view->title = $this->getTitle();
		$view->description = $this->getDescription();

		return $view;
	}

	/**
	 * Content sections will always be valid
	 *
	 * @return void
	 * @author Sam de Freyssinet
	 */
	public function isValid()
	{
		return TRUE;
	}
}