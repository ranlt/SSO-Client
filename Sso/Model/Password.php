<?php
class Sso_Model_Password extends Sso_Model_Base 
{
    const EMAIL_INVALID               = 401;
    const USER_NOT_FOUND              = 404;
    const USER_INSUFFICIENT_RIGHTS    = 403;
    
    protected $_mapping = array(
        'id',
        'key',
        'created'
    );
    
    protected $_emailValidator;
    
    
    public function reset($password)
    {
        if (!$validator = $this->getEmailValidator()) {
            throw new Sso_Model_Exception('Unable to get a validator for emails');
        }
        if (!$validator->isValid($emailAddress)) {
            throw new Sso_Model_Exception('Invalid email address', self::EMAIL_INVALID);
        }
        /** ok, down to the nuts and bolts
         * let's check to see if this email address is associated with a valid account
         * we define an account as valid if:
         * - if it exists (duh)
         * - has role
         **/
        
        if ($userModel = Sso_Model_Base::factory('User')) {
            if (!$userModel->fetchDisUserByDereEmailAddyKthx($emailAddress)) {
                throw new Sso_Model_Exception('Email address not found', self::USER_NOT_FOUND);
            }
            // ok, they exist and have rights after all that.

            $key = $this->_generateRandomOneTimeKey();
            
            $this->_saveOneTimeKey($userModel->id, $key);
            
        }
        
        return true;
    }
    
    protected function _saveOneTimeKey($userId, $key)
    {
        
    }
    
    
    
    protected function _generateRandomOneTimeKey($keyLength = 64)
    {
        // seed the randomizer
        Sso_Password::seed();
         // makes a random alpha numeric string of a given lenth 
        $aZ09 = array_merge(range('A', 'Z'), range('a', 'z'),range(0, 9)); 
        $key =''; 
        for ($c=0; $c < $keyLength; $c++) { 
            $key .= $aZ09[mt_rand(0,count($aZ09)-1)]; 
        } 
        return $key;        
    }
    
    
    public function getEmailValidator()
    {
        if (!$this->_emailValidator) {
            $this->_emailValidator = new Zend_Validate_EmailAddress();
        }
        return $this->_emailValidator;
    }
    
    
    public function setEmailValidator(Zend_Validate_Interface $validator)
    {
        $this->_emailValidator = $validator;
    }
    
    
    public function getEmailErrors()
    {
        if ($validator = $this->getEmailValidator()) {
            return $validator->getErrors();
        }
    }
}
?>