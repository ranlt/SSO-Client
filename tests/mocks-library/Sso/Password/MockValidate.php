<?php
class Sso_Password_MockValidate extends Zend_Validate_Abstract
{
    public function isValid($password)
    {
        return true;
    }
}
?>