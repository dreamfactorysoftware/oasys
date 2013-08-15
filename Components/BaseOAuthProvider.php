<?php
namespace DreamFactory\Oasys\Components;

use DreamFactory\Oasys\Exceptions\OasysConfigurationException;
use DreamFactory\Oasys\Exceptions\RedirectRequiredException;

/**
 * BaseOAuthProvider
 */
class BaseOAuthProvider extends BaseProvider
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * adapter initializer
	 */
	public function init()
	{
		parent::init();

		if ( !$this->get( 'client_id' ) || !$this->get( 'client_secret' ) )
		{
			throw new OasysConfigurationException( 'Invalid or missing credentials.' );
		}

		//	Create a new OAuth2 client instance
		$this->_client = new OAuth2Client( $this->config["keys"]["id"], $this->config["keys"]["secret"], $this->endpoint );

		// If we have an access token, set it
		if ( $this->token( "access_token" ) )
		{
			$this->api->access_token = $this->token( "access_token" );
			$this->api->refresh_token = $this->token( "refresh_token" );
			$this->api->access_token_expires_in = $this->token( "expires_in" );
			$this->api->access_token_expires_at = $this->token( "expires_at" );
		}

		// Set curl proxy if exist
		if ( isset( Hybrid_Auth::$config["proxy"] ) )
		{
			$this->api->curl_proxy = Hybrid_Auth::$config["proxy"];
		}
	}

	function refreshToken()
	{
		// have an access token?
		if ( $this->api->access_token )
		{

			// have to refresh?
			if ( $this->api->refresh_token && $this->api->access_token_expires_at )
			{

				// expired?
				if ( $this->api->access_token_expires_at <= time() )
				{
					$response = $this->api->refreshToken( array( "refresh_token" => $this->api->refresh_token ) );

					if ( !isset( $response->access_token ) || !$response->access_token )
					{
						// set the user as disconnected at this point and throw an exception
						$this->setUserUnconnected();

						throw new Exception( "The Authorization Service has return an invalid response while requesting a new access token. " .
											 (string)$response->error );
					}

					// set new access_token
					$this->api->access_token = $response->access_token;

					if ( isset( $response->refresh_token ) )
					{
						$this->api->refresh_token = $response->refresh_token;
					}

					if ( isset( $response->expires_in ) )
					{
						$this->api->access_token_expires_in = $response->expires_in;

						// even given by some idp, we should calculate this
						$this->api->access_token_expires_at = time() + $response->expires_in;
					}
				}
			}

			// re store tokens
			$this->token( "access_token", $this->api->access_token );
			$this->token( "refresh_token", $this->api->refresh_token );
			$this->token( "expires_in", $this->api->access_token_expires_in );
			$this->token( "expires_at", $this->api->access_token_expires_at );
		}
	}

	/**
	 * Begin the authorization process
	 *
	 * @throws RedirectRequiredException
	 */
	public function startAuthorization()
	{
		$this->_redirect( $this->api->authorizeUrl( array( "scope" => $this->scope ) ) );
	}

	/**
	 * Complete the authorization process
	 */
	public function completeAuthorization()
	{
		$error = ( array_key_exists( 'error', $_REQUEST ) ) ? $_REQUEST['error'] : "";

		// check for errors
		if ( $error )
		{
			throw new Exception( "Authentication failed! {$this->providerId} returned an error: $error", 5 );
		}

		// try to authenicate user
		$code = ( array_key_exists( 'code', $_REQUEST ) ) ? $_REQUEST['code'] : "";

		try
		{
			$this->api->authenticate( $code );
		}
		catch ( Exception $e )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
		}

		// check if authenticated
		if ( !$this->api->access_token )
		{
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		// store tokens
		$this->token( "access_token", $this->api->access_token );
		$this->token( "refresh_token", $this->api->refresh_token );
		$this->token( "expires_in", $this->api->access_token_expires_in );
		$this->token( "expires_at", $this->api->access_token_expires_at );

		// set user connected locally
		$this->setUserConnected();
	}

	/**
	 * Checks to see if user is authorized with this provider
	 *
	 * @return bool
	 */
	public function authorized()
	{
	}

	/**
	 * Unlink/disconnect/logout user from provider locally.
	 * Does nothing on the provider end
	 *
	 * @return void
	 */
	public function deauthorize()
	{
	}
}
