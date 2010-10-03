<?php

class Sso_Model_Mapper
{
	protected function _clearCache($cacheId)
	{
		$cache = Zend_Registry::get('cache');
		$cache->remove($cacheId);
	}
}