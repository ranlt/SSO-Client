<?php
class Sso_Controller_Action_Helper_Password extends Zend_Controller_Action_Helper_Abstract 
{
    
    
    protected $_options;
    /**
     * The validator used for passwords
     *
     * @var Zend_Validate_Abstract
     */
    protected $_validator;
    
    /**
     * Passthru to validate()
     *
     * @param string $password
     * @param array|Zend_Config $params
     * @return boolean
     */
    public function direct($password, $params = null)
    {
        return $this->validate($password, $params);
    }
    
    
    /**
     * Enter description here...
     *
     * @param string $password
     * @param array|Zend_Config $password
     * @return boolean
     */
    public function validate($password, $params = null)
    {
        if ($params) {
            $this->setOptions($params);
        }
        
        if (!$validator = $this->getValidator()) {
            throw new Sso_Exception('Validator not set');
        }
        
        return $validator->isValid($password);
    }
    
    public function getErrors()
    {
        if ($validator = $this->getValidator()) {
            return $validator->getErrors();
        }
    }
    
    
    
    /**
     * Get the validator for this helper
     *
     * @return Zend_Validate_Abstract
     */
    public function getValidator()
    {
        if (!$this->_validator) {
            $options = $this->getOptions();

            if (array_key_exists('validator', $options)) {
                $class = $options['validator'];
                $minChars = null;
                if (array_key_exists('min_chars', $options)) {
                    $minChars = $options['min_chars'];
                }
                $symbols = Sso_Password::getSymbolList();
                
                $validator = new $class($minChars, true, $symbols);
             
                
                $this->setValidator($validator);
            }
        }
        return $this->_validator;
    }
    
    public function setValidator(Zend_Validate_Abstract $validator)
    {
        $this->_validator = $validator;
    }
    
    public function getOptions()
    {
        return $this->_options;
    }
    
    /**
     * Public interface for setting options
     *
     * @param array|Zend_Config $options
     */
    public function setOptions($options)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }
        $this->_setOptions($options);
    }
    
    /**
     * Enter description here...
     *
     * @param array $options
     */
    protected function _setOptions(array $options = array())
    {
        $this->_options = $options;
   
    }
    
}
?>