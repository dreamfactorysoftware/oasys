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
namespace DreamFactory\Oasys\Components;

use DreamFactory\Oasys\Components\OAuth\Enums\OAuthGrantTypes;
use DreamFactory\Oasys\Components\OAuth\Enums\OAuthTokenTypes;
use DreamFactory\Oasys\Components\OAuth\Enums\OAuthTypes;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\AuthorizationCode;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\ClientCredentials;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\Password;
use DreamFactory\Oasys\Components\OAuth\GrantTypes\RefreshToken;
use DreamFactory\Oasys\Components\OAuth\Interfaces\OAuthServiceLike;
use DreamFactory\Oasys\Enums\EndpointTypes;
use DreamFactory\Oasys\Enums\OAuthFlows;
use DreamFactory\Oasys\Interfaces\ProviderLike;
use DreamFactory\Oasys\Interfaces\StorageProviderLike;
use DreamFactory\Oasys\Configs\OAuthProviderConfig;
use Kisma\Core\Exceptions\NotImplementedException;
use Kisma\Core\Interfaces\HttpMethod;
use Kisma\Core\Seed;
use Kisma\Core\SeedBag;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;

/**
 * OAuthClient
 * An base that knows how to talk OAuth2
 */
class OAuthClient extends Seed implements OAuthServiceLike, HttpMethod
{
	//**************************************************************************
	//* Members
	//**************************************************************************

	/**
	 * @var StorageProviderLike
	 */
	protected $_store;
	/**
	 * @var OAuthProviderConfig
	 */
	protected $_config;
	/**
	 * @var int The default OAuth authentication type
	 */
	protected $_authType = OAuthTypes::URI;
	/**
	 * @var string The default grant type is 'authorization_code'
	 */
	protected $_grantType = OAuthGrantTypes::AUTHORIZATION_CODE;
	/**
	 * @var string The OAuth access token parameter name for the requests
	 */
	protected $_accessTokenParamName = 'access_token';
	/**
	 * @var string The value to put in the "Authorization" header (i.e. Authorization: OAuth OAUTH-TOKEN). This can vary from service to service
	 */
	protected $_authHeaderName = 'OAuth';
	/**
	 * @var string The service authorization URL
	 */
	protected $_authorizeUrl = null;
	/**
	 * @var string The type of access token desired
	 */
	protected $_accessTokenType = OAuthTokenTypes::URI;
	/**
	 * @var string The access token secret key
	 */
	protected $_accessTokenSecret = null;
	/**
	 * @var string The hash algorithm used for signing requests
	 */
	protected $_hashAlgorithm = null;
	/**
	 * @var string
	 */
	protected $_redirectProxyUrl = null;

	//**************************************************************************
	//* Methods
	//**************************************************************************

	/**
	 * @param OAuthProviderConfig $config
	 * @param StorageProviderLike $store
	 *
	 * @throws \InvalidArgumentException
	 *
	 * @return \DreamFactory\Oasys\Components\OAuthClient
	 */
	public function __construct( $config, $store )
	{
		parent::__construct();

		$this->_config = $config;
		$this->_store = $store;

		foreach ( $config->toArray() as $_key => $_value )
		{
			$_member = Inflector::deneutralize( $_key );
			$_altMember = '_' . $_member;

			if ( !property_exists( $this, $_member ) && !property_exists( $this, $_altMember ) )
			{
				continue;
			}

			if ( method_exists( $this, 'set' . $_member ) )
			{
				$this->{'set' . $_member}( $_value );
			}
		}

		if ( null === $this->_config->getRedirectUri() )
		{
			$this->_config->setRedirectUri( Option::get( $options, 'redirect_uri', Curl::currentUrl( false ) ) );
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
	 * @param bool $startFlow        If true, and we are not authorized, checkAuthenticationProgress() is called.
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
				return $this->checkAuthenticationProgress( OAuthFlows::CLIENT_SIDE == $this->_config->getFlowType() );
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
			case OAuthGrantTypes::AUTHORIZATION_CODE:
				return AuthorizationCode::validatePayload( $payload );

			case OAuthGrantTypes::PASSWORD:
				return Password::validatePayload( $payload );

			case OAuthGrantTypes::CLIENT_CREDENTIALS:
				return ClientCredentials::validatePayload( $payload );

			case OAuthGrantTypes::REFRESH_TOKEN:
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

			if ( OAuthFlows::SERVER_SIDE == $this->_config->getFlowType() )
			{
				throw new RedirectRequiredException( $_redirectUrl );
			}

			header( 'Location: ' . $_redirectUrl );
			exit();
		}

		//	Got a code, now get a token
		$_token = $this->requestAccessToken(
			OAuthGrantTypes::AUTHORIZATION_CODE,
			array_merge(
				Option::clean( $this->_config->getPayload() ),
				array(
					 'code'         => $_code,
					 'redirect_uri' => $this->_config->getRedirectUri(),
				)
			)
		);

		$_info = null;

		if ( null !== ( $_result = Option::get( $_token, 'result' ) ) )
		{
			parse_str( $_token['result'], $_info );
		}

		if ( null !== ( $_error = Option::get( $_info, 'error' ) ) )
		{
			//	Error
			Log::error( 'Error returned from oauth token request: ' . print_r( $_info, true ) );

			return false;
		}

		$this->set( 'access_token', Option::get( $_info, 'access_token' ) );

		return true;
	}

	/**
	 * @param string $grantType
	 * @param array  $payload
	 *
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public function requestAccessToken( $grantType = OAuthGrantTypes::AUTHORIZATION_CODE, array $payload = array() )
	{
		$_payload = $this->_validateGrantType( $grantType, $payload );
		$_payload['grant_type'] = $grantType;

		$_headers = array();

		switch ( $this->_authType )
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
				throw new \InvalidArgumentException( 'The auth type "' . $this->_authType . '" is invalid.' );
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
	 * @throws \Kisma\Core\Exceptions\NotImplementedException
	 * @throws \InvalidArgumentException
	 *
	 * @return array
	 */
	public function fetch( $resource, $payload = array(), $method = 'GET', array $headers = array() )
	{
		//	Use the resource url if provided...
		if ( $this->_accessToken )
		{
			switch ( $this->_accessTokenType )
			{
				case OAuthTokenTypes::URI:
					if ( !empty( $this->_authHeaderName ) )
					{
						$headers[] = 'Authorization: ' . $this->_authHeaderName . ' ' . $this->_accessToken;
					}
					else
					{
						$payload[$this->_accessTokenParamName] = $this->_accessToken;
					}
					break;

				case OAuthTokenTypes::BEARER:
					if ( !empty( $this->_authHeaderName ) )
					{
						$headers[] = 'Authorization: ' . $this->_authHeaderName . ' ' . $this->_accessToken;
					}
					else
					{
						$headers[] = 'Authorization: Bearer ' . $this->_accessToken;
					}
					break;

				case OAuthTokenTypes::OAUTH:
					if ( !empty( $this->_authHeaderName ) )
					{
						$headers[] = 'Authorization: ' . $this->_authHeaderName . ' ' . $this->_accessToken;
					}
					else
					{
						$headers[] = 'Authorization: OAuth ' . $this->_accessToken;
					}
					break;

				case OAuthTokenTypes::MAC:
					throw new NotImplementedException();

				default:
					throw new \InvalidArgumentException( 'Unknown access token type.' );
			}
		}

		return $this->_makeRequest( $resource, $payload, $method, $headers );
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

		return $this->_authorizeUrl = ( $_map['endpoint'] . '?' . $_qs );
	}

	/**
	 * Generate the MAC signature
	 *
	 * @param string $url         Called URL
	 * @param array  $payload     Parameters
	 * @param string $method      Http Method
	 *
	 * @throws \Kisma\Core\Exceptions\NotImplementedException
	 * @return string
	 */
	protected function _signRequest( $url, $payload, $method )
	{
		throw new NotImplementedException( 'This type of authorization is not not implemented.' );
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

		if ( !empty( $this->_userAgent ) )
		{
			$headers[] = 'User-Agent: ' . $this->_userAgent;
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

		return array(
			'result'       => $_result,
			'code'         => Curl::getLastHttpCode(),
			'content_type' => Curl::getInfo( 'content_type' ),
		);
	}

	/**
	 * @param string $accessTokenParamName
	 *
	 * @return OAuthClient
	 */
	public function setAccessTokenParamName( $accessTokenParamName )
	{
		$this->_accessTokenParamName = $accessTokenParamName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAccessTokenParamName()
	{
		return $this->_accessTokenParamName;
	}

	/**
	 * @param string $accessTokenSecret
	 *
	 * @return OAuthClient
	 */
	public function setAccessTokenSecret( $accessTokenSecret )
	{
		$this->_accessTokenSecret = $accessTokenSecret;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAccessTokenSecret()
	{
		return $this->_accessTokenSecret;
	}

	/**
	 * Set the access token type
	 *
	 * @param int    $accessTokenType Access token type
	 * @param string $secret          The secret key used to encrypt the MAC header
	 * @param string $algorithm       Algorithm used to encrypt the signature
	 *
	 * @return $this
	 * @return OAuthClient
	 */
	public function setAccessTokenType( $accessTokenType, $secret = null, $algorithm = null )
	{
		$this->_accessTokenType = $accessTokenType;
		$this->_accessTokenSecret = $secret;
		$this->_hashAlgorithm = $algorithm;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAccessTokenType()
	{
		return $this->_accessTokenType;
	}

	/**
	 * @param int $authType
	 *
	 * @return OAuthClient
	 */
	public function setAuthType( $authType )
	{
		$this->_authType = $authType;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getAuthType()
	{
		return $this->_authType;
	}

	/**
	 * @param string $authorizeUrl
	 *
	 * @return OAuthClient
	 */
	public function setAuthorizeUrl( $authorizeUrl )
	{
		$this->_authorizeUrl = $authorizeUrl;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAuthorizeUrl()
	{
		return $this->_authorizeUrl;
	}

	/**
	 * @param string $grantType
	 *
	 * @return OAuthClient
	 */
	public function setGrantType( $grantType )
	{
		$this->_grantType = $grantType;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getGrantType()
	{
		return $this->_grantType;
	}

	/**
	 * @param string $hashAlgorithm
	 *
	 * @return OAuthClient
	 */
	public function setHashAlgorithm( $hashAlgorithm )
	{
		$this->_hashAlgorithm = $hashAlgorithm;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getHashAlgorithm()
	{
		return $this->_hashAlgorithm;
	}

	/**
	 * @param string $authHeaderName
	 *
	 * @return OAuthClient
	 */
	public function setAuthHeaderName( $authHeaderName )
	{
		$this->_authHeaderName = $authHeaderName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAuthHeaderName()
	{
		return $this->_authHeaderName;
	}

	/**
	 * @param string $redirectProxyUrl
	 *
	 * @return OAuthClient
	 */
	public function setRedirectProxyUrl( $redirectProxyUrl )
	{
		$this->_redirectProxyUrl = $redirectProxyUrl;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRedirectProxyUrl()
	{
		return $this->_redirectProxyUrl;
	}

	/**
	 * Retrieves a value at the given key location, or the default value if key isn't found.
	 * Setting $burnAfterReading to true will remove the key-value pair from the bag after it
	 * is retrieved. Call with no arguments to get back a KVP array of contents
	 *
	 * @param string $key
	 * @param mixed  $defaultValue
	 * @param bool   $burnAfterReading
	 *
	 * @throws \Kisma\Core\Exceptions\BagException
	 * @return mixed
	 */
	public function get( $key = null, $defaultValue = null, $burnAfterReading = false )
	{
		return $this->_store->get( $key, $defaultValue, $burnAfterReading );
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $overwrite
	 *
	 * @throws \Kisma\Core\Exceptions\BagException
	 * @return SeedBag
	 */
	public function set( $key, $value, $overwrite = true )
	{
		return $this->_store->set( $key, $value, $overwrite );
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
	 * Returns true/false if user is authorized to talk to this provider
	 *
	 * @param array $options Authentication options
	 *
	 * @return $this|ProviderLike|void
	 */
	public function authenticate( $options = array() )
	{
	}

	/**
	 * @param \DreamFactory\Oasys\Interfaces\StorageProviderLike $store
	 *
	 * @return OAuthClient
	 */
	public function setStore( $store )
	{
		$this->_store = $store;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\Interfaces\StorageProviderLike
	 */
	public function getStore()
	{
		return $this->_store;
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
