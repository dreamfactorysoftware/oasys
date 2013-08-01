<?php
/**
 * The ProviderUser class represents the current loggedin user
 */
class ProviderUser
{
	protected $_provider = NULL;
	protected $_timestamp = NULL;

	/* user profile, contains the list of fields available in the normalized user profile structure used by HybridAuth. */
	protected $_profile = NULL;

	/**
	* inisialize the user object,
	*/
	function __construct()
	{
		$this->timestamp = time();

		$this->profile   = new Hybrid_User_Profile();
	}
}
