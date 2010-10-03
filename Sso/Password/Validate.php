<?php


class Sso_Password_Validate extends Zend_Validate_Abstract
{
    protected $_minLength;
    protected $_checkSymbols;
    
    protected $_symbolList;
    
    
    const PASSWORD_TOO_SHORT        = 'passTooShort';
    const PASSWORD_NO_LOWER_CASE    = 'passNoLowerCase';
    const PASSWORD_NO_UPPER_CASE    = 'passNoUpperCase';
    const PASSWORD_NO_NUMERICS      = 'passNoNumerals';
    const PASSWORD_NO_SYMBOLS       = 'passNoSymbols';
    
    
    /**
     * Constructor
     *
     * @param integer $length
     * @param boolean $symbolCheck
     * @param array $symbolCollection
     */
    public function __construct($length, $symbolCheck = true, array $symbolCollection = array())
    {
        $this->_minLength = intval($length);
        $this->_checkSymbols = $symbolCheck;
        
        if ($this->_checkSymbols) {
            $this->setSymbolsList($symbolCollection);
        }
    }
    
    /**
     * Set the list of symbols to validate against
     *
     * @param array $symbolCollection
     */
    public function setSymbolsList(array $symbolCollection = array())
    {
        $this->_symbolList = $symbolCollection;
    }
    
    
    /**
     * Validates a password against security requirements
     *
     * @param string $password
     * @param array $symbolCollection
     * @return boolean
     */
    public function isValid($password, array $symbolCollection = array())
    {
        $this->_setValue($password);
        $return = true;
        
        if (!(strlen($password) >= $this->_minLength)) {
            $this->_error('Password too short', self::PASSWORD_TOO_SHORT);
            $return = false;
        }
        if (!preg_match('|[a-z]|', $password)) {
            $this->_error("no lower case letters in password $password", self::PASSWORD_NO_LOWER_CASE);
            $return = false;
        }
        if (!preg_match('|[A-Z]|', $password)) {
             $this->_error("no upper case letters in password $password", self::PASSWORD_NO_UPPER_CASE);
             $return = false;
        }
        if (!preg_match('|[0-9]|', $password)) {
            $this->_error("no numerics found in $password", self::PASSWORD_NO_NUMERICS);
            $return = false;
        }
        
        if ($this->_checkSymbols) {
            if (!count($symbolCollection)) {  
               if (!count($this->_symbolList)) {
                   throw new Sso_Exception('Symbol check has been requested, but no list of symbols given');
               }
               $symbolCollection = $this->_symbolList;
            }
            $symbolsFound = false;
            foreach ($symbolCollection as $symbol) {
                if (strpos($password, $symbol) !== false) {
                    $symbolsFound = true;
                    break;
                }
            }
            if (!$symbolsFound) {
                $this->_error("No symbols found in $password", self::PASSWORD_NO_SYMBOLS);
                $return = false;
            }
        }
       
       return $return; 
    }
}