<?php

/**
 * This model class represents the business logic associated with the "Token"
 * model.
 * 
 * @category Model
 * @package Model_Token
 * @version $Id: Token.php 687 2009-08-11 09:51:40Z w123461 $
 */
class Sso_Model_Token extends Sso_Model
{
	/**
	 * @var string
	 */
	static $_mapperClass = 'Model_TokenMapper';

	protected $_hash;

	protected $_username;

	protected $_accessTime;

	protected $_mapper;

	public function getHash()
	{
		return $this->_hash;
	}

	public function setHash($hash)
	{
		$this->_hash = $hash;
		return $this;
	}

	public function getUsername()
	{
		return $this->_username;
	}

	public function setUsername($username)
	{
		$this->_username = $username;
		return $this;
	}

	public function getAccessTime()
	{
		return $this->_accessTime;
	}

	public function setAccessTime($accessTime)
	{
		$this->_accessTime = (int) $accessTime;
		return $this;
	}

	/**
	 * Get data mapper
	 * 
	 * @return Model_TokenMapper
	 */
	public function getMapper()
	{
		if (null === $this->_mapper) {
			$className = self::$_mapperClass;
			$this->setMapper(new $className());
		}
		return $this->_mapper;
	}

	/**
	 * Set data mapper
	 * 
	 * @param  Model_TokenMapper $mapper
	 * @return Model_Token
	 */
	public function setMapper(Model_TokenMapper $mapper)
	{
		$this->_mapper = $mapper;
		return $this;
	}

    /**
     * Find a token
     *
     * Resets entry state if matching token found.
     * 
     * @param  string $hash
     * @return Model_Token
     */
    public function find($hash)
    {
        $this->getMapper()->find($hash, $this);
        return $this;
    }

    public function delete()
    {
    	$this->getMapper()->delete($this);
    	return $this;
    }
}