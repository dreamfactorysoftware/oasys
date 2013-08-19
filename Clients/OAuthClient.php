<?php
/**
 * Copyright 2013 DreamFactory Software, Inc. <support@dreamfactory.com>
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
namespace DreamFactory\Oasys\Clients;

use DreamFactory\Oasys\Enums\GrantTypes;
use DreamFactory\Oasys\Enums\TokenTypes;
use DreamFactory\Oasys\Enums\OAuthTypes;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\AuthorizationCode;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\ClientCredentials;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\Password;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\RefreshToken;
use DreamFactory\Oasys\Interfaces\OAuthServiceLike;
use DreamFactory\Oasys\Enums\EndpointTypes;
use DreamFactory\Oasys\Enums\Flows;
use DreamFactory\Oasys\Exceptions\OasysConfigurationException;
use DreamFactory\Oasys\Exceptions\RedirectRequiredException;
use DreamFactory\Oasys\Interfaces\ProviderClientLike;
use DreamFactory\Oasys\Configs\OAuthProviderConfig;
use DreamFactory\Oasys\Interfaces\ProviderConfigLike;
use Kisma\Core\Exceptions\NotImplementedException;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;

/**
 * OAuthClient
 * An base that knows how to talk OAuth2
 */
class OAuthClient extends Seed implements ProviderClientLike, OAuthServiceLike
{
	//**************************************************************************
	//* Members
	//**************************************************************************

	/**
	 * @var OAuthProviderConfig|ProviderConfigLike
	 */
	protected $_config;

	//**************************************************************************
	//* Methods
	//**************************************************************************

	/**
	 * @param OAuthProviderConfig|ProviderConfigLike $config
	 *
	 * @throws \InvalidArgumentException
	 * @return \DreamFactory\Oasys\Clients\OAuthClient
	 */
	public function __construct( $config )
	{
		parent::__construct();

		$this->_config = $config;

		if ( null === $config->getRedirectUri() )
		{
			$this->_config->setRedirectUri( Curl::currentUrl( false ) );
		}

		$_cert = $config->getCertificateFile();

		if ( !empty( $_cert ) && ( !is_file( $_cert ) || !is_readable( $_cert ) ) )
		{
			throw new \InvalidArgumentException( 'The specified certificate file "' . $_cert . '" was not found' );
		}
	}

	/**
	 * Check if we are authorized or not...
	 *
	 * @param bool $startFlow If true, and we are not authorized, checkAuthenticationProgress() is called.
	 *
	 * @return bool|string
	 */
	public function authorized( $startFlow = false )
	{
		$_token = $this->_config->getAccessToken();

		if ( empty( $_token ) )
		{
			if ( false !== $startFlow )
			{
				return $this->checkAuthenticationProgress();
			}

			return false;
		}

		return true;
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
				throw new \InvalidArgumentException( 'Invalid grant type "' . $grantType . '" specified.' );
		}
	}

	/**
	 * Checks the progress of any in-flight OAuth requests
	 *
	 * @throws RedirectRequiredException
	 *
	 * @return string
	 */
	public function checkAuthenticationProgress()
	{
		if ( $this->_config->getAccessToken() )
		{
			return true;
		}

		$_code = FilterInput::get( INPUT_GET, 'code' );

		//	No code is present, request one
		if ( empty( $_code ) )
		{
			$_payload = array_merge(
				Option::clean( $this->_config->getPayload() ),
				array(
					 'redirect_uri' => $this->_config->getRedirectUri(),
					 'client_id'    => $this->_config->getClientId(),
				)
			);

			$_redirectUrl = $this->getAuthorizationUrl( $_payload );

			if ( !empty( $this->_redirectProxyUrl ) )
			{
				$_redirectUrl = $this->_redirectProxyUrl . '?redirect=' . urlencode( $_redirectUrl );
			}

			if ( Flows::SERVER_SIDE == $this->_config->getFlowType() )
			{
				throw new RedirectRequiredException( $_redirectUrl );
			}

			header( 'Location: ' . $_redirectUrl );
			exit();
		}

		//	Got a code, now get a token
		$_token = $this->requestAccessToken(
			GrantTypes::AUTHORIZATION_CODE,
			array_merge(
				Option::clean( $this->_config->getPayload() ),
				array(
					 'code'         => $_code,
					 'redirect_uri' => $this->_config->getRedirectUri(),
				)
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
		}

		if ( null !== ( $_error = Option::get( $_info, 'error' ) ) )
		{
			//	Error
			Log::error( 'Error returned from oauth token request: ' . print_r( $_info, true ) );

			return false;
		}

		$this->_config->setAccessToken( Option::get( $_info, 'access_token' ) );
		$this->_config->setAccessTokenExpires( Option::get( $_info, 'expires' ) );

//		if ( null !== ( $_type = Option::get( $_info, 'token_type' ) ) )
//		{
//			switch ( strtolower( $_type ) )
//			{
//				case 'bearer':
//					$this->_config->setAccessTokenType( TokenTypes::BEARER );
//					break;
//
//				case 'oauth':
//					$this->_config->setAccessTokenType( TokenTypes::OAUTH );
//					break;
//			}
//		}

		return true;
	}

	/**
	 * @param string $grantType
	 * @param array  $payload
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function requestAccessToken( $grantType = GrantTypes::AUTHORIZATION_CODE, array $payload = array() )
	{
		$_payload = $this->_validateGrantType( $grantType, $payload );
		$_payload['grant_type'] = $grantType;

		$_headers = array();

		switch ( $this->_config->getAuthType() )
		{
			case OAuthTypes::URI:
			case OAuthTypes::FORM:
				$_payload['client_id'] = $this->_config->getClientId();
				$_payload['client_secret'] = $this->_config->getClientSecret();
				break;

			case OAuthTypes::BASIC:
				$_payload['client_id'] = $this->_config->getClientId();
				$_headers[] = 'Authorization: Basic ' . base64_encode( $this->_config->getClientId() . ':' . $this->_config->getClientSecret() );
				break;

			default:
				throw new \InvalidArgumentException( 'The configured authorization type "' . $this->_config->getAuthType() . '" is invalid.' );
		}

		$_map = $this->_config->getEndpoint( EndpointTypes::ACCESS_TOKEN );

		$_payload = array_merge(
			Option::get( $_map, 'parameters', array() ),
			$_payload
		);

		return $this->_makeRequest( $_map['endpoint'], $_payload, static::Post, $_headers );
	}

	/**
	 * Fetch a protected resource
	 *
	 * @param string $resource
	 * @param array  $payload
	 * @param string $method
	 * @param array  $headers
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\OasysConfigurationException
	 * @throws \Kisma\Core\Exceptions\NotImplementedException
	 * @throws \DreamFactory\Oasys\Exceptions\RedirectRequiredException
	 * @return array
	 */
	public function fetch( $resource, $payload = array(), $method = self::Get, array $headers = array() )
	{
		$_token = $this->_config->getAccessToken();

		//	Use the resource url if provided...
		if ( $_token )
		{
			$_authHeaderName = $this->_config->getAuthHeaderName();

			switch ( $this->_config->getAccessTokenType() )
			{
				case TokenTypes::URI:
					$payload[$this->_config->getAccessTokenParamName()] = $_token;
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
					throw new OasysConfigurationException( 'Unknown access token type "' . $this->_config->getAccessTokenType() . '".' );
			}

			if ( null !== $_authHeaderName )
			{
				$headers[] = 'Authorization: ' . $_authHeaderName . ' ' . $_token;
			}
		}

		$_endpoint = $this->_config->getEndpoint( EndpointTypes::SERVICE );

		$payload = array_merge(
			Option::get( $_endpoint, 'parameters', array() ),
			$payload
		);

		//	Make the url spiffy
		$_url = rtrim( $_endpoint['endpoint'], '/' ) . '/' . ltrim( $resource, '/' );

		$_response = $this->_makeRequest( $_url, $payload, $method, $headers );

		//	Authorization failure?
		if ( isset( $_response, $_response['result'], $_response['result']['error'] ) && $_response['code'] >= 400 )
		{
			//	Clear out our tokens and junk
			$this->_config->setAccessToken( null );
			$this->_config->setAccessTokenExpires( null );

			//	Jump back to the redirect URL
			throw new RedirectRequiredException();
		}

		return $_response;
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
		$_map = $this->_config->getEndpoint( EndpointTypes::AUTHORIZE );
		$_scope = $this->_config->getScope();

		$_payload = array_merge(
			array(
				 'response_type' => 'code',
				 'client_id'     => $this->_config->getClientId(),
				 'redirect_uri'  => $this->_config->getRedirectUri(),
				 'scope'         => is_array( $_scope ) ? implode( ',', $_scope ) : $_scope,
			),
			Option::clean( $payload ),
			Option::clean( Option::get( $_map, 'parameters', array() ) )
		);

		$_qs = http_build_query( $_payload );

		$this->_config->setAuthorizeUrl( $_authorizeUrl = ( $_map['endpoint'] . '?' . $_qs ) );

		return $_authorizeUrl;
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

		if ( null !== ( $_agent = $this->_config->getUserAgent() ) )
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

		if ( !empty( $this->_certificateFile ) )
		{
			$_curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
			$_curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
			$_curlOptions[CURLOPT_CAINFO] = $this->_config->getCertificateFile();
		}

		if ( false === ( $_result = Curl::request( $method, $url, $payload, $_curlOptions ) ) )
		{
			throw new AuthenticationException( Curl::getErrorAsString() );
		}

		$_contentType = Curl::getInfo( 'content_type' );

		if ( false !== stripos( $_contentType, 'application/json', 0 ) && !empty( $_result ) && is_string( $_result ) )
		{
			$_result = json_decode( $_result, true );
		}

		return array(
			'result'       => $_result,
			'code'         => Curl::getLastHttpCode(),
			'content_type' => $_contentType,
		);
	}

	/**
	 * @param \DreamFactory\Oasys\Configs\OAuthProviderConfig $config
	 *
	 * @return OAuthClient
	 */
	public function setConfig( $config )
	{
		$this->_config = $config;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\Configs\OAuthProviderConfig
	 */
	public function getConfig()
	{
		return $this->_config;
	}
}
