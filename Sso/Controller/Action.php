<?php

/**
 * Base controller
 * 
 * @package Controllers
 *
 */
class Sso_Controller_Action extends Zend_Controller_Action
{
	/**
	 * For sending messages across requests
	 * 
	 * @var Zend_Controller_Action_Helper_FlashMessenger
	 */
	protected $_flashMessenger;
	
	/**
	 * Logger
	 * 
	 * @var Zend_Log
	 */
	protected $_logger = null;
	
	/**
	 * Currently logged in user
	 * 
	 * @var Sso_Model_User
	 */
	private $_loggedInUser;
	
	/**
	 * Common init routine
	 */
	public function init()
    {
        $this->_flashMessenger = $this->_helper->getHelper('FlashMessenger');
        
        if (Zend_Registry::isRegistered('activityLog')) {
        	$this->_logger = Zend_Registry::get('activityLog');
        }
    }
    
    /**
     * Configure the url paramaters for sorting and pagination
     * 
     * @param Sso_Model_Base $model
     */
    public function configureModelUrlParams($model, $objectClass = null)
    {
    	$data = $this->getRequest()->getParams();
    	
    	// params may be prepended with the object class
    	// - for showing multiple tables on a single page - eg
    	//   users and organisations
    	$objectClass = (null !== $objectClass) ? $objectClass : $model->getObjectClass();
    	
    	if (isset($data[$objectClass . '_limit'])) {
    		$limit = (int) $data[$objectClass . '_limit'];
    	} elseif (isset($data['limit'])) {
    		$limit = (int) $data['limit'];
    	} else {
    		$limit = 15;
    	}    	
    	
    	$limit = ($limit < 1) ? 1 : $limit;
    	if (isset($data[$objectClass . '_page'])) {
    		$page = (int) $data[$objectClass . '_page'];
    	} elseif (isset($data['page'])) {
    		$page = (int) $data['page'];
    	} else {
    		$page = 1;
    	}
    	
    	$page = ($page < 1) ? 1 : $page;
    	
    	$start = $limit * ($page - 1);
    	
    	$model->limit($limit, $start);
    	
    	// look for order by and direction params
    	$order = null;
    	if (isset($data[$objectClass . '_order'])) {
    		$order = $data[$objectClass . '_order'];
    	} elseif (isset($data['order'])) {
    		$order = $data['order'];
    	}
    	
    	if ($order) {
    		// can't specify direction only
	    	$direction = null;
	    	if (isset($data[$objectClass . '_direction'])) {
	    		$direction = $data[$objectClass . '_direction'];
	    	} elseif (isset($data['direction'])) {
	    		$direction = $data['direction'];
	    	} else {
	    		$direction = 'asc';
	    	}
	    	
	    	$model->orderBy($order, $direction);
    	}
    	
    	return $model; 
    }
    
    /**
     * Write an entry to the log
     * 
     * @param int $level
     * @param string $message
     */
    public function _log($level, $message)
    {
    	if (null !== $this->_logger) {
    		$this->_logger->$level($message);
    	}
    }
    
    /**
     * Call before dispatching the action
     */
	public function preDispatch()
	{
		$this->view->messages = $this->_flashMessenger->getMessages();
		$this->view->controller = $this->getRequest()->getControllerName();
		$this->view->currentUrl = $this->getRequest()->getRequestUri();
		$this->view->currentQuery = $this->getRequest()->getQuery();
	}
	
	/**
	 * Get currently logged in user
	 * 
	 * @return Sso_Model_User
	 */
	protected function _getLoggedInUser()
	{
		if ($this->_loggedInUser == null) {
        	$identity = Zend_Auth::getInstance()->getIdentity();
        	if (is_object($identity) && property_exists($identity, 'username')) {
        		$this->_loggedInUser = new Sso_Model_User($identity->username);
        	}
        	
        }
        
        return $this->_loggedInUser;
	}
}