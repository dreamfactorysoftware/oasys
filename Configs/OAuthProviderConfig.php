<?php
/**
 * This file is part of the DreamFactory Oasys (Open Authentication SYStem)
 *
 * DreamFactory Oasys (Open Authentication SYStem) <http://dreamfactorysoftware.github.io>
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
namespace DreamFactory\Oasys\Configs;

use DreamFactory\Oasys\Enums\Flows;
use DreamFactory\Oasys\Enums\GrantTypes;
use DreamFactory\Oasys\Enums\TokenTypes;
use DreamFactory\Oasys\Enums\OAuthTypes;
use DreamFactory\Oasys\Enums\AccessTypes;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;

/**
 * OAuthProviderConfig
 * A generic OAuth 2.0 provider
 */
class OAuthProviderConfig extends BaseProviderConfig
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected $_clientId;
	/**
	 * @var string
	 */
	protected $_clientSecret;
	/**
	 * @var string The redirect URI registered with provider
	 */
	protected $_redirectUri;
	/**
	 * @var array The scope of the authorization
	 */
	protected $_scope;
	/**
	 * @var int
	 */
	protected $_flowType = Flows::SERVER_SIDE;
	/**
	 * @var int
	 */
	protected $_accessType = AccessTypes::OFFLINE;
	/**
	 * @var int
	 */
	protected $_accessTokenType = TokenTypes::URI;
	/**
	 * @var int The default OAuth authentication type (URI/Form, or Basic)
	 */
	protected $_authType = OAuthTypes::URI;
	/**
	 * @var string The default grant type is 'authorization_code'
	 */
	protected $_grantType = GrantTypes::AUTHORIZATION_CODE;
	/**
	 * @var string The OAuth access token parameter name for the requests
	 */
	protected $_accessTokenParamName = 'access_token';
	/**
	 * @var string The value to put in the "Authorization" header (i.e. Authorization: OAuth OAUTH-TOKEN). This may not be the same across all providers
	 */
	protected $_authHeaderName = 'OAuth';
	/**
	 * @var string The service authorization URL
	 */
	protected $_authorizeUrl = null;
	/**
	 * @var string
	 */
	protected $_redirectProxyUrl = null;
	/**
	 * @var string
	 */
	protected $_accessToken;
	/**
	 * @var string
	 */
	protected $_accessTokenSecret;
	/**
	 * @var int
	 */
	protected $_accessTokenExpires;
	/**
	 * @var string
	 */
	protected $_refreshToken;
	/**
	 * @var int
	 */
	protected $_refreshTokenExpires;
	/**
	 * @var string Full file name of a certificate to use for this connection
	 */
	protected $_certificateFile;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array $contents
	 *
	 * @return \DreamFactory\Oasys\Configs\OAuthProviderConfig
	 */
	public function __construct( $contents = array() )
	{
		Option::set( $contents, 'type', static::OAUTH );

		parent::__construct( $contents );
	}

	/**
	 * @param bool $returnAll If true, all configuration values are returned. Otherwise only a subset are available
	 *
	 * @return string JSON-encoded representation of this config
	 */
	public function toJson( $returnAll = false )
	{
		return parent::toJson(
			$returnAll,
			array(
				 'clientId',
				 'clientSecret',
				 'redirectUri',
				 'scope',
				 'certificateFile',
				 'authorizeUrl',
				 'grantType',
				 'authType',
				 'accessType',
				 'flowType',
				 'accessTokenParamName',
				 'authHeaderName',
				 'accessToken',
				 'accessTokenType',
				 'accessTokenSecret',
				 'accessTokenExpires',
				 'refreshToken',
				 'refreshTokenExpires',
			)
		);
	}

	/**
	 * @param string $clientId
	 *
	 * @return OAuthProviderConfig
	 */
	public function setClientId( $clientId )
	{
		$this->_clientId = $clientId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getClientId()
	{
		return $this->_clientId;
	}

	/**
	 * @param string $clientSecret
	 *
	 * @return OAuthProviderConfig
	 */
	public function setClientSecret( $clientSecret )
	{
		$this->_clientSecret = $clientSecret;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getClientSecret()
	{
		return $this->_clientSecret;
	}

	/**
	 * @param int $accessType
	 *
	 * @return OAuthProviderConfig
	 */
	public function setAccessType( $accessType )
	{
		$this->_accessType = $accessType;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getAccessType()
	{
		return $this->_accessType;
	}

	/**
	 * @param string $accessToken
	 *
	 * @return OAuthProviderConfig
	 */
	public function setAccessToken( $accessToken )
	{
		$this->_accessToken = $accessToken;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAccessToken()
	{
		return $this->_accessToken;
	}

	/**
	 * @param string $redirectUri
	 *
	 * @return OAuthProviderConfig
	 */
	public function setRedirectUri( $redirectUri )
	{
		$this->_redirectUri = $redirectUri;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRedirectUri()
	{
		return $this->_redirectUri;
	}

	/**
	 * @param string $refreshToken
	 *
	 * @return OAuthProviderConfig
	 */
	public function setRefreshToken( $refreshToken )
	{
		$this->_refreshToken = $refreshToken;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getRefreshToken()
	{
		return $this->_refreshToken;
	}

	/**
	 * @param array $scope
	 *
	 * @return OAuthProviderConfig
	 */
	public function setScope( $scope )
	{
		$this->_scope = $scope;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getScope()
	{
		return $this->_scope;
	}

	/**
	 * @param int $flowType
	 *
	 * @return OAuthProviderConfig
	 */
	public function setFlowType( $flowType )
	{
		$this->_flowType = $flowType;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getFlowType()
	{
		return $this->_flowType;
	}

	/**
	 * @param string $certificateFile
	 *
	 * @return OAuthProviderConfig
	 */
	public function setCertificateFile( $certificateFile )
	{
		$this->_certificateFile = $certificateFile;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCertificateFile()
	{
		return $this->_certificateFile;
	}

	/**
	 * @param int $accessTokenExpires
	 *
	 * @return OAuthProviderConfig
	 */
	public function setAccessTokenExpires( $accessTokenExpires )
	{
		$this->_accessTokenExpires = $accessTokenExpires;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getAccessTokenExpires()
	{
		return $this->_accessTokenExpires;
	}

	/**
	 * @param int $refreshTokenExpires
	 *
	 * @return OAuthProviderConfig
	 */
	public function setRefreshTokenExpires( $refreshTokenExpires )
	{
		$this->_refreshTokenExpires = $refreshTokenExpires;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getRefreshTokenExpires()
	{
		return $this->_refreshTokenExpires;
	}

	/**
	 * @param string $accessTokenSecret
	 *
	 * @return OAuthProviderConfig
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
	 * @param int $accessTokenType
	 *
	 * @return OAuthProviderConfig
	 */
	public function setAccessTokenType( $accessTokenType )
	{
		$this->_accessTokenType = $accessTokenType;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getAccessTokenType()
	{
		return $this->_accessTokenType;
	}

	/**
	 * @param string $accessTokenParamName
	 *
	 * @return OAuthProviderConfig
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
	 * @param string $authHeaderName
	 *
	 * @return OAuthProviderConfig
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
	 * @param int $authType
	 *
	 * @return OAuthProviderConfig
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
	 * @return OAuthProviderConfig
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
	 * @return OAuthProviderConfig
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
	 * @param string $redirectProxyUrl
	 *
	 * @return OAuthProviderConfig
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

}
