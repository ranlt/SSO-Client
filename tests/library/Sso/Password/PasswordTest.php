<?php
require_once 'TestHelper.php';
require_once('PHPUnit/Framework.php');

class Test_Password extends PHPUnit_Framework_TestCase {
    
    protected $_password;
    
    public function testCreatePassword()
    {
        $password = Sso_Password::generate();
        $this->assertTrue($password instanceof Sso_Password_PasswordAbstract);
    }
    
}
