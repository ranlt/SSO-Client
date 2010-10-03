<?php
// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../'));
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/tests/mocks-library'),
    realpath(APPLICATION_PATH),
    get_include_path(),
)));

require_once 'Cwcl/Autoload.php';
Cwcl_Autoload::registerAutoload();

//require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('Sso_');
$autoloader->registerNamespace('Cwcl_');

// set up registry dependencies
Zend_Registry::set('log', new Zend_Log(new Zend_Log_Writer_Null));
Zend_Registry::set('activityLog', new Zend_Log(new Zend_Log_Writer_Null));
Zend_Registry::set('session', new Zend_Session_Namespace());
Zend_Registry::set('ssoConfig', new Zend_Config(array('url' => 'http://ssoservice.staging.mycw.co.uk')));
