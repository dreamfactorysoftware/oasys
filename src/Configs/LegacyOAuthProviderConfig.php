<?php
/**
 * This file is part of the DreamFactory Oasys (Open Authentication SYStem)
 *
 * DreamFactory Oasys (Open Authentication SYStem) <http://dreamfactorysoftware.github.io>
 * Copyright 2014 DreamFactory Software, Inc. <support@dreamfactory.com>
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
use Kisma\Core\Utility\Option;

/**
 * LegacyOAuthProviderConfig
 */
class LegacyOAuthProviderConfig extends BaseProviderConfig
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected $_consumerKey;
	/**
	 * @var string
	 */
	protected $_consumerSecret;
	/**
	 * @var string
	 */
	protected $_signatureMethod = OAUTH_SIG_METHOD_HMACSHA1;
	/**
	 * @var string The redirect URI registered with provider
	 */
	protected $_redirectUri;
	/**
	 * @var string
	 */
	protected $_accessToken;
	/**
	 * @var string
	 */
	protected $_accessTokenSecret;
	/**
	 * @var array
	 */
	protected $_token;
	/**
	 * @var string The service authorization URL
	 */
	protected $_authorizeUrl = null;
	/**
	 * @var int The type of request
	 */
	protected $_authType = OAUTH_AUTH_TYPE_URI;
	/**
	 * @var int
	 */
	protected $_flowType = Flows::CLIENT_SIDE;
	/**
	 * @var int The current auth state
	 */
	protected $_state = 0;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array $contents
	 *
	 * @throws \RuntimeException
	 * @return \DreamFactory\Oasys\Configs\LegacyOAuthProviderConfig
	 */
	public function __construct( $contents = array() )
	{
		//	Require pecl oauth library...
		if ( !extension_loaded( 'oauth' ) )
		{
			throw new \RuntimeException( 'Use of the LegacyOAuthProviderConfig requires the PECL "oauth" extension.' );
		}

		Option::set( $contents, 'type', static::LEGACY_OAUTH );

		parent::__construct( $contents );
	}

	/**
	 * @param bool $returnAll
	 * @param bool $returnAll If true, all configuration values are returned. Otherwise only a subset are available
	 *
	 * @return bool JSON-encoded representation of this config
	 */
	public function toJson( $returnAll = false )
	{
		return parent::toJson(
			$returnAll,
			array(
				'consumerKey',
				'consumerSecret',
				'signatureMethod',
				'authorizeUrl',
				'authType',
				'flowType',
				'token',
				'accessToken',
				'accessTokenSecret',
				'accessTokenExpires',
				'refreshToken',
				'refreshTokenExpires',
				'redirectUri',
			)
		);
	}

	/**
	 * @param string $consumerKey
	 *
	 * @return LegacyOAuthProviderConfig
	 */
	public function setConsumerKey( $consumerKey )
	{
		$this->_consumerKey = $consumerKey;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getConsumerKey()
	{
		return $this->_consumerKey;
	}

	/**
	 * @param string $consumerSecret
	 *
	 * @return LegacyOAuthProviderConfig
	 */
	public function setConsumerSecret( $consumerSecret )
	{
		$this->_consumerSecret = $consumerSecret;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getConsumerSecret()
	{
		return $this->_consumerSecret;
	}

	/**
	 * @param string $signatureMethod
	 *
	 * @return LegacyOAuthProviderConfig
	 */
	public function setSignatureMethod( $signatureMethod )
	{
		$this->_signatureMethod = $signatureMethod;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getSignatureMethod()
	{
		return $this->_signatureMethod;
	}

	/**
	 * @param string $redirectUri
	 *
	 * @return LegacyOAuthProviderConfig
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
	 * @param string $accessToken
	 *
	 * @return LegacyOAuthProviderConfig
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
	 * @param string $accessTokenSecret
	 *
	 * @return LegacyOAuthProviderConfig
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
	 * @param array $token
	 *
	 * @return LegacyOAuthProviderConfig
	 */
	public function setToken( $token )
	{
		$this->_token = $token;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getToken()
	{
		return $this->_token;
	}

	/**
	 * @param string $authorizeUrl
	 *
	 * @return LegacyOAuthProviderConfig
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
	 * @param int $authType
	 *
	 * @return LegacyOAuthProviderConfig
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
	 * @param int $state
	 *
	 * @return LegacyOAuthProviderConfig
	 */
	public function setState( $state )
	{
		$this->_state = $state;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getState()
	{
		return $this->_state;
	}

	/**
	 * @param int $flowType
	 *
	 * @return LegacyOAuthProviderConfig
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
}
