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

use DreamFactory\Oasys\Components\BaseProviderConfig;

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
	 * @var string
	 */
	protected $_version = '1.0';
	/**
	 * @var string The redirect URI registered with provider
	 */
	protected $_redirectUri;
	/**
	 * @var array The scope of the authorization
	 */
	protected $_scope;

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

		if ( null !== ( $_uri = Option::get( $contents, 'authorization_endpoint', null, true ) ) )
		{
			$this->mapEndpoint( static::AUTHORIZE, $_uri );
		}

		if ( null !== ( $_uri = Option::get( $contents, 'request_token_endpoint', null, true ) ) )
		{
			$this->mapEndpoint( static::REQUEST_TOKEN, $_uri );
		}

		if ( null !== ( $_uri = Option::get( $contents, 'access_token_endpoint', null, true ) ) )
		{
			$this->mapEndpoint( static::ACCESS_TOKEN, $_uri );
		}

		if ( null !== ( $_uri = Option::get( $contents, 'refresh_token_endpoint', null, true ) ) )
		{
			$this->mapEndpoint( static::REFRESH_TOKEN, $_uri );
		}
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
	 * @param string $version
	 *
	 * @return LegacyOAuthProviderConfig
	 */
	public function setVersion( $version )
	{
		$this->_version = $version;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return $this->_version;
	}

	/**
	 * @param array $scope
	 *
	 * @return LegacyOAuthProviderConfig
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
}
