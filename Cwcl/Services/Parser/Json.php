<?php
/**
 * Json parser for service responses
 *
 * @package Cwcl Services Parser
 * @author Sam de Freyssinet
 */
class Cwcl_Services_Parser_Json extends Cwcl_Services_Parser_Abstract
{
	/**
	 * Parse the input string into something
	 * more meaningful
	 *
	 * @param Cwcl_Services_Model_Abstract $model
	 * @return Cwcl_Services_Model_Iterator|Cwcl_Services_Model_Abstract|boolean
	 * @access public
	 * @throws Cwcl_Services_Parser_Exception Throws excpetion if the content in the response cannot be decoded or contains an unknown request method
	 */
	public function parse(Cwcl_Services_Model_Abstract $model)
	{
		// Parse the response
		$payload = new Cwcl_Services_Response_Payload_Json; 
		
		try {
			$payload->unserialize($this->getInput()->getContent());
		} catch (Exception $e) {
			throw new Cwcl_Services_Parser_Exception(__METHOD__.' unable to decode json successfully : '.$this->getInput()->getContent());
		}
		
		if (NULL === $payload->getPayload()) {
			throw new Cwcl_Services_Parser_Exception(__METHOD__.' unable to decode json successfully : '.$this->getInput()->getContent());
		}

		// Inspect the HTTP method type
		// GET REQUEST
		if ($this->_input->getRequest()->getMethod() === Cwcl_Services_Request::GET) {
			// Get the payload
			$data = $payload->getPayload();

			if (TRUE === $this->_input->getRequest()->getSingleRecord()) {
				$firstEntry = array_values(array_slice($data, 0, 1));
				return (0 < count($data)) ? $model->setValues((array) $firstEntry[0]) : $model->reset();
			}
			else {
				// Create a new iterator
				$iterator = new Cwcl_Services_Model_Iterator($data, $model);

				// Apply metadata
				return $iterator->setMetadata($payload->getMetadata());
			}
		}
		// DELETE REQUEST
		elseif ($this->_input->getRequest()->getMethod() === Cwcl_Services_Request::DELETE) {
			// , if DETETE then this is a delete request, empty the model
			$model->reset();
			return FALSE;
		}
		// PUT/POST REQUEST
		elseif (in_array($this->_input->getRequest()->getMethod(), array(Cwcl_Services_Request::PUT, Cwcl_Services_Request::POST))) {
            $data = $payload->getPayload();
            /**
             * @todo Tidy this up: ensure all the controllers from the service layer
             * return in a consistent format (i.e. the payload property should be an
             * array of elements
             */
			if (TRUE === $this->_input->getRequest()->getSingleRecord()) {
                if (isset($data[0]) && is_object($data[0])) {
                    $data = array_shift($data);
                }
                $model->setValues((array) $data);
                return TRUE;
			}
            throw new Cwcl_Services_Parser_Exception('Unexpected multiple record on POST/PUT');
		}
		else {
			throw new Cwcl_Services_Parser_Exception(__METHOD__.' unknown response from the server : '.$this->getInput()->getContent());
		}
	}
}
