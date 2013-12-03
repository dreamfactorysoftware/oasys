<?php
/**
 * This file is part of the DreamFactory Oasys (Open Authentication SYStem)
 *
 * DreamFactory Oasys (Open Authentication SYStem) http://dreamfactorysoftware.github.io
 * Copyright 2013 DreamFactory Software, Inc. support@dreamfactory.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace DreamFactory\Oasys\Providers;

use DreamFactory\Oasys\Components\GenericUser;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\AuthorizationCode;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\ClientCredentials;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\Password;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\RefreshToken;
use DreamFactory\Oasys\Enums\DataFormatTypes;
use DreamFactory\Oasys\Enums\EndpointTypes;
use DreamFactory\Oasys\Enums\Flows;
use DreamFactory\Oasys\Enums\GrantTypes;
use DreamFactory\Oasys\Enums\OAuthTypes;
use DreamFactory\Oasys\Enums\TokenTypes;
use DreamFactory\Oasys\Interfaces\OAuthServiceLike;
use DreamFactory\Oasys\Exceptions\AuthenticationException;
use DreamFactory\Oasys\Exceptions\OasysConfigurationException;
use DreamFactory\Oasys\Exceptions\RedirectRequiredException;
use DreamFactory\Oasys\Interfaces\ProviderConfigLike;
use DreamFactory\Oasys\Oasys;
use DreamFactory\Platform\Yii\Stores\ProviderUserStore;
use Kisma\Core\Exceptions\NotImplementedException;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Storage;

/**
 * BaseOAuthProvider
 */
abstract class BaseOAuthProvider extends BaseProvider implements OAuthServiceLike
{
	//********************************************************************************
	//* Members
	//********************************************************************************

	/**
	 * @var bool
	 */
	protected $_needProfileUserId = false;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param string             $providerId
	 * @param ProviderConfigLike $config
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\OasysConfigurationException
	 * @return \DreamFactory\Oasys\Providers\BaseOAuthProvider
	 */
	public function __construct( $providerId, $config = null )
	{
		parent::__construct( $providerId, $config );

		//	Sanity checks for OAuth
		if ( null === $this->getConfig( 'client_id' ) || null === $this->getConfig( 'client_secret' ) )
		{
			throw new OasysConfigurationException( 'Invalid or missing credentials.' );
		}

		//	Set a default redirect URI if none specified
		if ( null === $this->getConfig( 'redirect_uri' ) )
		{
			Log::debug( 'Redirect URI set to current URL: ' . ( $_url = Curl::currentUrl( false ) ) );
			$this->setConfig( 'redirect_uri', $_url );
		}

		//	Make sure, if specified, certificate file is valid
		if ( null !== ( $_certificateFile = $this->getConfig( 'certificate_file' ) ) && ( !is_file( $_certificateFile ) || !is_readable(
					$_certificateFile
				) )
		)
		{
			throw new OasysConfigurationException( 'The specified certificate file "' . $_certificateFile . '" was not found or cannot be read.' );
		}
	}

	/**
	 * Checks to see if user is authorized with this provider
	 *
	 * @param bool $startFlow If true, and not authorized, the login flow will commence presently
	 *
	 * @return bool
	 */
	public function authorized( $startFlow = false )
	{
		$_token = $this->getConfig( 'access_token' );
		$_result = !empty( $_token );

		if ( !$_result && $startFlow )
		{
			$_result = $this->checkAuthenticationProgress( true );
		}

		//	Is a profile refresh is needed...
		if ( $_result && $this->_needProfileUserId )
		{
			$this->_updateUserProfile();
		}

		return $_result;
	}

	/**
	 * Checks the progress of any in-flight OAuth requests
	 *
	 * @param bool $skipTokenCheck If true, assume there is no token
	 *
	 * @throws NotImplementedException
	 * @throws \DreamFactory\Oasys\Exceptions\RedirectRequiredException
	 * @return string
	 */
	public function checkAuthenticationProgress( $skipTokenCheck = false )
	{
		if ( false === $skipTokenCheck && $this->getConfig( 'access_token' ) )
		{
			return true;
		}

		if ( GrantTypes::AUTHORIZATION_CODE != $this->getConfig( 'grant_type' ) )
		{
			throw new NotImplementedException();
		}

		$_code = FilterInput::get( INPUT_GET, 'code' );

		//	No code is present, request one
		if ( empty( $_code ) )
		{
			$_redirectUrl = $this->getAuthorizationUrl();

			if ( Flows::SERVER_SIDE == $this->getConfig( 'flow_type' ) )
			{
				throw new RedirectRequiredException( $_redirectUrl );
			}

			header( 'Location: ' . $_redirectUrl );
			exit();
		}

		//	Figure out where the redirect goes...
		$_redirectUri = $this->getConfig( 'redirect_uri' );
		$_proxyUrl = $this->getConfig( 'redirect_proxy_url' );

		if ( !empty( $_proxyUrl ) )
		{
			$_redirectUri = $_proxyUrl;
		}

		//	Got a code, now get a token
		$_token = $this->requestAccessToken(
			GrantTypes::AUTHORIZATION_CODE,
			array(
				 'code'         => $_code,
				 'redirect_uri' => $_redirectUri,
				 'state'        => Option::request( 'state' ),
			)
		);

		$_info = null;

		if ( isset( $_token, $_token['result'] ) )
		{
			if ( !is_string( $_token['result'] ) )
			{
				$_info = $_token['result'];
			}
			else
			{
				parse_str( $_token['result'], $_info );
			}

			$this->_responsePayload = $_info;
		}

		if ( ( !is_array( $_info ) && !is_object( $_info ) ) || null !== ( $_error = Option::get( $_info, 'error' ) ) )
		{
			//	Error
			Log::error( 'Error returned from oauth token request: ' . print_r( $_info, true ) );

			$this->_revokeAuthorization();

			return false;
		}

		return $this->_processReceivedToken( $_info );
	}

	/**
	 * Revoke prior auth
	 *
	 * @param string $providerUserId
	 */
	public function revoke( $providerUserId = null )
	{
		$this->_revokeAuthorization( $providerUserId );
	}

	/**
	 * @param array|\stdClass $data
	 *
	 * @return bool
	 */
	protected function _processReceivedToken( $data )
	{
		$_tokenFound = false;

		$this->setConfig( 'access_token_expires', Option::get( $data, 'expires' ) );

		if ( null !== ( $_token = Option::get( $data, 'refresh_token' ) ) )
		{
			$this->setConfig( 'refresh_token', $_token );
		}

		if ( null !== ( $_scope = Option::get( $data, 'scope' ) ) )
		{
			$this->setConfig( 'scope', $_scope );
		}

		//	New token? Update user profile with current stuff
		if ( null !== ( $_token = Option::get( $data, 'access_token' ) ) )
		{
			$_tokenFound = true;
			$this->setConfig( 'access_token', $_token );
			$this->_needProfileUserId = true;
		}

		return $_tokenFound;
	}

	/**
	 * @param string $providerUserId
	 */
	protected function _revokeAuthorization( $providerUserId = null )
	{
		$this->setConfig(
			array(
				 'access_token'          => null,
				 'access_token_expires'  => null,
				 'refresh_token'         => null,
				 'refresh_token_expires' => null,
			)
		);

		Log::debug( 'Revoked/reset any session authorization' );

		return;
	}

	/**
	 * @param string $grantType
	 * @param array  $payload
	 *
	 * @return array|false
	 * @throws \InvalidArgumentException
	 */
	public function requestAccessToken( $grantType = GrantTypes::AUTHORIZATION_CODE, array $payload = array() )
	{
		$_payload = $this->_validateGrantType( $grantType, $payload );
		$_payload['grant_type'] = $grantType;

		$_headers = array();

		switch ( $this->getConfig( 'auth_type' ) )
		{
			case OAuthTypes::URI:
			case OAuthTypes::FORM:
				$_payload['client_id'] = $this->getConfig( 'client_id' );
				$_payload['client_secret'] = $this->getConfig( 'client_secret' );
				break;

			case OAuthTypes::BASIC:
				$_payload['client_id'] = $this->getConfig( 'client_id' );
				$_headers[] = 'Authorization: Basic ' . base64_encode( $this->getConfig( 'client_id' ) . ':' . $this->getConfig( 'client_secret' ) );
				break;

			default:
				throw new \InvalidArgumentException( 'The configured authorization type "' . $this->getConfig( 'auth_type' ) . '" is invalid.' );
		}

		$_map = $this->getConfig()->getEndpoint( EndpointTypes::ACCESS_TOKEN );

		$_payload = array_merge(
			Option::get( $_map, 'parameters', array() ),
			$_payload
		);

		return $this->_makeRequest( $_map['endpoint'], $_payload, static::Post, $_headers );
	}

	/**
	 * @param array $payload
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\AuthenticationException
	 * @return mixed
	 */
	public function requestRefreshToken( array $payload = array() )
	{
		if ( null === ( $_refreshToken = $this->getConfig( 'refresh_token' ) ) )
		{
			return false;
		}

		Log::debug( 'Access token expired or bogus . Requesting refresh: ' . $_refreshToken );

		$_payload = array_merge(
			$payload,
			array(
				 'refresh_token' => $_refreshToken,
			)
		);

		if ( false === ( $_response = $this->requestAccessToken( GrantTypes::REFRESH_TOKEN, $_payload ) ) )
		{
			throw new AuthenticationException( 'Error requesting refresh token: ' . Curl::getErrorAsString() );
		}

		$_result = Option::get( $_response, 'result' );

		//	Did it work?
		if ( !empty( $_result ) )
		{
			$_payload = (array)$_result;
			$_token = $this->_processReceivedToken( $_payload );

			//	It worked! Or not...
			if ( null === $_token )
			{
				Log::error( 'No access token received: ' . print_r( $_payload, true ) );

				return false;
			}

			Log::debug( 'Refresh of access token successful for client_id: ' . $this->getConfig( 'client_id' ) );

			//	Update user profile with current stuff
			$this->_needProfileUserId = true;

			return true;
		}

		Log::error( 'Error refreshing token . Empty or error response: ' . print_r( $_result, true ) );

		return false;
	}

	/**
	 * Fetch a protected resource
	 *
	 * @param string $resource
	 * @param array  $payload
	 * @param string $method
	 * @param array  $headers
	 *
	 * @param array  $curlOptions
	 *
	 * @return array
	 */
	public function fetch( $resource, $payload = array(), $method = self::Get, array $headers = array(), array $curlOptions = array() )
	{
		$_headers = $headers ? : array();
		$_payload = $payload ? : array();

		//	Get the service endpoint and make the url spiffy
		if ( false === strpos( $resource, 'http://', 0 ) && false === strpos( $resource, 'https://', 0 ) )
		{
			$_endpoint = $this->getConfig()->getEndpoint( EndpointTypes::SERVICE );
			$_url = rtrim( $_endpoint['endpoint'], '/ ' ) . '/' . ltrim( $resource, '/ ' );
		}
		else
		{
			//	Use given url
			$_url = $_endpoint = $resource;
		}

		//	Add pre-defined endpoint parameters, if any
		if ( null !== ( $_parameters = Option::get( $_endpoint, 'parameters' ) ) && is_array( $_payload ) && is_array( $_parameters ) )
		{
			$_payload = array_merge(
				$_parameters,
				$_payload
			);
		}

		//	Add any authentication headers/parameters required by the provider
		$this->_getAuthParameters( $_headers, $_payload );

		//	Make the actual HTTP request
		return $this->_makeRequest( $_url, $_payload, $method, $_headers, $curlOptions );
	}

	/**
	 * Execute a request
	 *
	 * @param string $url         Request URL
	 * @param mixed  $payload     The payload to send
	 * @param string $method      The HTTP method to send
	 * @param array  $headers     Array of HTTP headers to send in array( 'header: value', 'header: value', ... ) format
	 * @param array  $curlOptions Array of options to pass to CURL
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\AuthenticationException
	 * @return array
	 */
	protected function _makeRequest( $url, array $payload = array(), $method = self::Get, array $headers = array(), array $curlOptions = array() )
	{
		static $_defaultCurlOptions = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
		);

		//	Start clean...
		$this->_resetRequest();

		//	Add in any user-supplied CURL options
		$_curlOptions = array_merge( $_defaultCurlOptions, $curlOptions );

		//	Add certificate info for SSL
		if ( null !== ( $_certificateFile = $this->getConfig( 'certificate_file' ) ) )
		{
			$_curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
			$_curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
			$_curlOptions[CURLOPT_CAINFO] = $_certificateFile;
		}

		//	And finally our headers
		if ( null !== ( $_agent = $this->getConfig( 'user_agent' ) ) )
		{
			$headers[] = 'User-Agent: ' . $_agent;
		}

		$_curlOptions[CURLOPT_HTTPHEADER] = $headers;

		//	Convert payload to query string for a GET
		if ( static::Get == $method && !empty( $payload ) )
		{
			$url .= Curl::urlSeparator( $url ) . http_build_query( $payload );
			$payload = array();
		}

		//	And finally make the request
		if ( false === ( $_result = Curl::request( $method, $url, $this->_translatePayload( $payload, false ), $_curlOptions ) ) )
		{
			throw new AuthenticationException( Curl::getErrorAsString() );
		}

		//	Save off response
		$this->_lastResponseCode = $_code = Curl::getLastHttpCode();

		//	Shift result from array...
		if ( is_array( $_result ) && isset( $_result[0] ) && sizeof( $_result ) == 1 && $_result[0] instanceof \stdClass )
		{
			$_result = $_result[0];
		}

		$_contentType = Curl::getInfo( 'content_type' );

		if ( DataFormatTypes::JSON == $this->_responseFormat && false !== stripos( $_contentType, 'application/json', 0 ) )
		{
			$_result = $this->_translatePayload( $_result );
		}

		return $this->_lastResponse = array(
			'result'       => $_result,
			'code'         => $_code,
			'content_type' => $_contentType,
		);
	}

	/**
	 * Construct a link to authorize the application
	 *
	 * @param array $payload
	 *
	 * @return string
	 */
	public function getAuthorizationUrl( $payload = array() )
	{
		$_map = $this->getConfig()->getEndpoint( EndpointTypes::AUTHORIZE );
		$_scope = $this->getConfig( 'scope' );
		$_redirectUri = $this->getConfig( 'redirect_uri', Curl::currentUrl() );
		$_origin = $this->getConfig( 'origin_uri', $_redirectUri );
		$_proxyUrl = $this->getConfig( 'redirect_proxy_url' );
//		$_payload = Option::clean( $payload );

		$_state = array(
			'request'      => array(
				'method'       => Option::server( 'REQUEST_METHOD' ),
				'payload'      => $this->_requestPayload,
				'query_string' => Option::server( 'QUERY_STRING' ),
				'referrer'     => Option::server( 'HTTP_REFERER' ),
				'remote_addr'  => Option::server( 'REMOTE_ADDR' ),
				'time'         => microtime( true ),
				'uri'          => Option::server( 'REQUEST_URI' ),
			),
			'origin'       => $_origin,
			'api_key'      => sha1( $_origin ),
			'redirect_uri' => $_redirectUri,
		);

		Log::debug( 'Request state built: ' . print_r( $_state, true ) );

		$_payload = array_merge(
			array(
				 'client_id'     => $this->getConfig( 'client_id' ),
				 'redirect_uri'  => $_redirectUri,
				 'response_type' => 'code',
				 'scope'         => is_array( $_scope ) ? implode( ' ', $_scope ) : $_scope,
				 'state'         => Storage::freeze( $_state ),
			),
			Option::clean( Option::get( $_map, 'parameters', array() ) )
		);

		if ( !empty( $_proxyUrl ) )
		{
			Log::info( 'Proxying request through: ' . $_proxyUrl );
			$_payload['redirect_uri'] = $_proxyUrl;
		}

		$_qs = http_build_query( $_payload );
		$this->setConfig( 'authorize_url', $_authorizeUrl = ( $_map['endpoint'] . Curl::urlSeparator( $_map['endpoint'] ) . $_qs ) );
		Log::debug( 'Authorization URL created: ' . $_authorizeUrl );

		return $_authorizeUrl;
	}

	/**
	 * Validate that the required parameters are supplied for the type of grant selected
	 *
	 * @param string          $grantType
	 * @param array|\stdClass $payload
	 *
	 * @return array|\stdClass|void
	 * @throws \InvalidArgumentException
	 */
	protected function _validateGrantType( $grantType, $payload )
	{
		switch ( $grantType )
		{
			case GrantTypes::AUTHORIZATION_CODE:
				return AuthorizationCode::validatePayload( $payload );

			case GrantTypes::PASSWORD:
				return Password::validatePayload( $payload );

			case GrantTypes::CLIENT_CREDENTIALS:
				return ClientCredentials::validatePayload( $payload );

			case GrantTypes::REFRESH_TOKEN:
				return RefreshToken::validatePayload( $payload );

			default:
				throw new \InvalidArgumentException( 'Invalid grant type "' . $grantType . '" specified . ' );
		}
	}

	/**
	 * @param array $payload
	 * @param bool  $response   If true, the $responseFormat will be used, otherwise the $requestFormat
	 * @param bool  $assocArray If true, decoded JSON is returned in an array
	 *
	 * @return array|string
	 */
	protected function _translatePayload( $payload = array(), $response = true, $assocArray = true )
	{
		$_payload = $payload;
		$_format = ( true === $response ? $this->_responseFormat : $this->_requestFormat );

		switch ( $_format )
		{
			case DataFormatTypes::JSON:
				if ( true === $response )
				{
					if ( is_string( $_payload ) && !empty( $_payload ) )
					{
						$_payload = json_decode( $payload, $assocArray );
					}
				}
				else
				{
					if ( !is_string( $_payload ) )
					{
						$_payload = json_encode( $_payload );
					}
				}

				if ( false === $_payload )
				{
					//	Revert because it failed to go to JSON
					$_payload = $payload;
				}
				break;

			case DataFormatTypes::XML:
				if ( is_object( $payload ) )
				{
					$_payload = Xml::fromObject( $payload );
				}
				else if ( is_array( $payload ) )
				{
					$_payload = Xml::fromArray( $payload );
				}
				break;
		}

		return $_payload;
	}

	/**
	 * Clean up members for a new request
	 */
	protected function _resetRequest()
	{
		$this->_responsePayload = $this->_lastResponse = $this->_lastResponseCode = $this->_lastError = $this->_lastErrorCode = null;
	}

	/**
	 * Called before a request to get any additional auth header(s) or payload parameters
	 * (query string for non-POST-type requests) needed for the call.
	 *
	 * Append them to the $headers array as strings in "header: value" format:
	 *
	 * <code>
	 *        $_contentType = 'Content-Type: application/json';
	 *        $_origin = 'Origin: teefury.com';
	 *
	 *        $headers[] = $_contentType;
	 *        $headers[] = $_origin;
	 * </code>
	 *
	 * and/or append them to the $payload array in $key => $value format:
	 *
	 * <code>
	 *        $payload['param1'] = 'value1';
	 *        $payload['param2'] = 'value2';
	 *        $payload['param3'] = 'value3';
	 * </code>
	 *
	 * @param array $headers The current headers that are going to be sent
	 * @param array $payload
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\OasysConfigurationException
	 * @throws NotImplementedException
	 * @return void
	 */
	protected function _getAuthParameters( &$headers = array(), &$payload = array() )
	{
		$_token = $this->getConfig( 'access_token' );

		//	Use the resource url if provided...
		if ( $_token )
		{
			$_authHeaderName = $this->getConfig( 'auth_header_name' );

			switch ( $this->getConfig( 'access_token_type' ) )
			{
				case TokenTypes::URI:
					Option::set( $payload, $this->getConfig( 'access_token_param_name' ), $_token );
					$_authHeaderName = null;
					break;

				case TokenTypes::BEARER:
					$_authHeaderName = $_authHeaderName ? : 'Bearer';
					break;

				case TokenTypes::OAUTH:
					$_authHeaderName = $_authHeaderName ? : 'OAuth';
					break;

				case TokenTypes::MAC:
					throw new NotImplementedException();

				default:
					throw new OasysConfigurationException( 'Unknown access token type "' . $this->getConfig( 'access_token_type' ) . '".' );
			}

			if ( null !== $_authHeaderName )
			{
				$headers[] = 'Authorization: ' . $_authHeaderName . ' ' . $_token;
			}
		}
	}

	/**
	 * Returns the normalized provider's user profile
	 *
	 * @return GenericUser
	 */
	public function getUserData()
	{
		if ( null === ( $_resource = $this->_config->getEndpointUrl( EndpointTypes::IDENTITY ) ) )
		{
			return null;
		}

		return $this->fetch( $_resource );
	}

	/**
	 * Retrieves the users' profile from the provider and stores it
	 */
	protected function _updateUserProfile()
	{
		$_profile = $this->getUserData();

		if ( !empty( $_profile ) )
		{
			//	For us...
			$_profile->setProviderId( $this->_providerId );
			$this->setConfig( 'provider_user_id', $_id = $_profile->getUserId() );

			//	For posterity
			/** @noinspection PhpUndefinedMethodInspection */
			Oasys::getStore()->setProviderUserId( $_id );

			//	A tag
			Log::debug( 'User profile updated [' . $this->_providerId . ':' . $_id . ']' );
		}
	}

	/**
	 * @param boolean $needProfileUserId
	 *
	 * @return BaseOAuthProvider
	 */
	public function setNeedProfileUserId( $needProfileUserId = false )
	{
		$this->_needProfileUserId = $needProfileUserId;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getNeedProfileUserId()
	{
		return $this->_needProfileUserId;
	}
}
