<?php
require_once 'TestHelper.php';
require_once('PHPUnit/Framework.php');

require_once 'Sso/Controller/Action/Helper/Password.php';

require_once 'Sso/Password/Validate.php';

class TestPasswordHelper extends PHPUnit_Framework_TestCase 
{
    
    protected $_zfConfig;
    
    public function setup()
    {
        $configDir = realpath(dirname(__FILE__)  . DIRECTORY_SEPARATOR . 'configs');
        
        $helperConfig = $configDir . DIRECTORY_SEPARATOR . 'helper.ini';
        
        $this->_zfConfig = new Zend_Config_Ini($helperConfig, 'testing');
    }
    
    
    public function testBadPassword()
    {
        $helper = new Sso_Controller_Action_Helper_Password();
        $helper->setOptions($this->_zfConfig->password);
        
        $this->assertFalse($helper->validate('foo')); // far too basic
        $this->assertFalse($helper->validate('fooB')); // no numbers or symbols
        $this->assertFalse($helper->validate('foob0')); // no symbols
        $this->assertFalse($helper->validate('foO0^')); // still too short

        
    }
    
    public function testGoodPassword()
    {
        $helper = new Sso_Controller_Action_Helper_Password();
        $helper->setOptions($this->_zfConfig->password);  

        $this->assertTrue($helper->validate('foOB^4Hg'));
    }
    
    public function testCanAddValidator()
    {
        $helper = new Sso_Controller_Action_Helper_Password();

        $helper->setValidator(new Sso_Password_MockValidate());
        
        $this->assertTrue($helper->getValidator() instanceof Sso_Password_MockValidate);
    }
}
?>