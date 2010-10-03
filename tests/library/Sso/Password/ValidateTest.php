<?php

require_once 'TestHelper.php';
require_once('PHPUnit/Framework.php');

class Test_PasswordValidator extends PHPUnit_Framework_TestCase {

    
    public function testValidatorNoSymbols()
    {
         $validator = new Sso_Password_Validate(6, false);
         
         $this->assertFalse($validator->isValid(''));
         
         $this->assertFalse($validator->isValid('t'));
         $this->assertFalse($validator->isValid('foobaz'));
         $this->assertFalse($validator->isValid('FooBaz'));
         $this->assertFalse($validator->isValid('FooBa'));
         $this->assertFalse($validator->isValid('foob0'));
         $this->assertFalse($validator->isValid('foob00'));
         $this->assertTrue($validator->isValid('FooB04'));
         $this->assertTrue($validator->isValid('FooB04kjdsfGFH'));
    }
    
    public function testValidateWithSymbols()
    {
        $password = 'FooB04tf';   
        $symbolCollection = Sso_Password::getSymbolList();
        $validator = new Sso_Password_Validate(8, true);
        $this->assertFalse($validator->isValid($password, $symbolCollection));
        $this->assertTrue($validator->isValid($password . $symbolCollection[0], $symbolCollection));
    }

}