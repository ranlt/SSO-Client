<?php

/**
 * Token data mapper
 *
 * Implements the Data Mapper design pattern:
 * http://www.martinfowler.com/eaaCatalog/dataMapper.html
 * 
 * @uses       Model_Token
 * @package    MyCW
 * @subpackage Model
 */
class Sso_Model_TokenMapper extends Sso_Model_Mapper
{
	const CACHE_TIMEOUT = 300; // 300 seconds = 5 mins
	const CACHE_ID_PREFIX = 'MyCW_SSO_Token_';

    /**
     * Save a token
     * 
     * @throws Cw_Exception
     */
    public function save(Model_Token $token)
    {
		throw new Sso_Model_Exception('You are not allowed to edit tokens');
    }

    /**
     * Delete a token (logout)
     * 
     * @param  Model_Token $token
     * @return Model_TokenMapper
     */
    public function delete(Model_Token $token)
    {
    	$sso = Zend_Registry::get('ssoClient');
    	$sso->deleteToken($token)
    	    ->run();
    	
    	$this->_clearCache(self::CACHE_ID_PREFIX . $token->getHash());

    	return $this;
    }

    /**
     * Find a token
     * 
     * @param  string $hash
     * @param  Model_Token $token 
     * @return Model_TokenMapper
     */
    public function find($hash, Model_Token $token)
    {
    	$cache = Zend_Registry::get('cache');
    	$cacheId = self::CACHE_ID_PREFIX . $hash;

    	if (false === ($tmpToken = $cache->load($cacheId))) {
    		$sso = Zend_Registry::get('ssoClient');
    		$tmpToken = $sso->fetchToken($hash)
    		                ->run()
    		                ->getToken($hash);
    		$cache->save($tmpToken, $cacheId, array(), self::CACHE_TIMEOUT);
    	}

    	$token->setHash($tmpToken->getHash());
    	$token->setUser($tmpToken->getUsername());
    	$token->setAccessTime($tmpToken->getAccessTime());

    	return $this;
    }
}
