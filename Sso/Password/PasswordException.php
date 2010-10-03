<?php


class Sso_Password_PasswordException extends Sso_Exception 
{
    /**
     * The password length has not been set at all.
     * @var integer
     */
    const LENGTH_NOT_SET        = 405;
    
    /**
     * The password is too short.
     *
     */
    const PASSWORD_TOO_SHORT    = 400;
}
?>