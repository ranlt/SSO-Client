<?php
/**
 * Requests specific to the Sso service
 *
 * @package Sso
 */
class Sso_Request extends Sso_Request_Abstract
{
	/**
	 * Set the token
	 *
	 * @param string|Model_Token $token
	 * @return self
	 */
	public function setToken($token)
	{
		if ($token instanceof Model_Token) {
			$token = $token->getHash();
		}
		
		return $this->setCookie('token', $token);

	}

	/**
	 * Get the token
	 *
	 * @return string
	 */
	public function getToken()
	{
		return $this->getCookie('token');
	}

	/**
	 * Remove the current token
	 *
	 * @return self
	 */
	public function removeToken()
	{
		return $this->removeCookie('token');
	}
}