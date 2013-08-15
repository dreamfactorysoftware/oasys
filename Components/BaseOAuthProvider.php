<?php
namespace DreamFactory\Oasys\Components;

use DreamFactory\Oasys\Components\OAuth\Interfaces\OAuthServiceLike;
use DreamFactory\Oasys\Exceptions\AuthenticationException;
use DreamFactory\Oasys\Exceptions\OasysConfigurationException;
use DreamFactory\Oasys\Exceptions\RedirectRequiredException;
use Kisma\Core\Interfaces\HttpMethod;
use Kisma\Core\Utility\Curl;

/**
 * BaseOAuthProvider
 */
class BaseOAuthProvider extends BaseProvider implements OAuthServiceLike, HttpMethod
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Initialize the OAuth provider
	 */
	public function init()
	{
		parent::init();

		if ( !$this->get( 'client_id' ) || !$this->get( 'client_secret' ) )
		{
			throw new OasysConfigurationException( 'Invalid or missing credentials.' );
		}

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

ww			// have to refresh?
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

	/**
	 * Execute a request
	 *
	 * @param string $url
	 * @param mixed  $payload
	 * @param string $method
	 * @param array  $headers Array of HTTP headers to send in array( 'header: value', 'header: value', ... ) format
	 *
	 * @throws AuthenticationException
	 * @return array
	 */
	protected function _makeRequest( $url, $payload = array(), $method = self::Get, array $headers = null )
	{
		$headers = Option::clean( $headers );

		$_agent = $this->get( 'user_agent' );

		if ( !empty( $_agent ) )
		{
			$headers[] = 'User-Agent: ' . $_agent;
		}

		$_curlOptions = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_HTTPHEADER     => $headers,
		);

		if ( static::Get == $method && false === strpos( $url, '?' ) && !empty( $payload ) )
		{
			$url .= '?' . ( is_array( $payload ) ? http_build_query( $payload, null, '&' ) : $payload );
			$payload = array();
		}

//		if ( !empty( $this->_certificateFile ) )
//		{
//			$_curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
//			$_curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
//			$_curlOptions[CURLOPT_CAINFO] = $this->_certificateFile;
//		}

		if ( false === ( $_result = Curl::request( $method, $url, $payload, $_curlOptions ) ) )
		{
			throw new AuthenticationException( Curl::getErrorAsString() );
		}

		return array(
			'result'       => $_result,
			'code'         => Curl::getLastHttpCode(),
			'content_type' => Curl::getInfo( 'content_type' ),
		);
	}
}
