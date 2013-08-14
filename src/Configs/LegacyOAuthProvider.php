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

/**
 * LegacyOAuthProvider
 */
class LegacyOAuthProvider extends BaseProviderConfig
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
	protected $_token;
	/**
	 * @var string
	 */
	protected $_signatureMethod = OAUTH_SIG_METHOD_HMACSHA1;
	/**
	 * @var string
	 */
	protected $_timestamp;
	/**
	 * @var string
	 */
	protected $_nonce;
	/**
	 * @var string
	 */
	protected $_version = '1.0';

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array $contents
	 *
	 * @throws \RuntimeException
	 */
	public function __construct( $contents = array() )
	{
		//	Require pecl oauth library...
		if ( !extension_loaded( 'oauth' ) )
		{
			throw new \RuntimeException( 'Use of the LegacyOAuthProvider requires the PECL "oauth" extension.' );
		}

		Option::set( $contents, 'type', static::LEGACY_OAUTH );

		if ( null !== ( $_uri = Option::get( $contents, 'authorize_uri', null, true ) ) )
		{
			$this->mapEndpoint( static::AUTHORIZE, $_uri );
		}

		if ( null !== ( $_uri = Option::get( $contents, 'request_token_uri', null, true ) ) )
		{
			$this->mapEndpoint( static::REQUEST_TOKEN, $_uri );
		}

		if ( null !== ( $_uri = Option::get( $contents, 'access_token_uri', null, true ) ) )
		{
			$this->mapEndpoint( static::ACCESS_TOKEN, $_uri );
		}

		if ( null !== ( $_uri = Option::get( $contents, 'refresh_token_uri', null, true ) ) )
		{
			$this->mapEndpoint( static::REFRESH_TOKEN, $_uri );
		}

		parent::__construct( $contents );
	}

	/**
	 * @param string $consumerKey
	 *
	 * @return LegacyOAuthProvider
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
	 * @return LegacyOAuthProvider
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
	 * @param string $nonce
	 *
	 * @return LegacyOAuthProvider
	 */
	public function setNonce( $nonce )
	{
		$this->_nonce = $nonce;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getNonce()
	{
		return $this->_nonce;
	}

	/**
	 * @param string $signatureMethod
	 *
	 * @return LegacyOAuthProvider
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
	 * @param string $timestamp
	 *
	 * @return LegacyOAuthProvider
	 */
	public function setTimestamp( $timestamp )
	{
		$this->_timestamp = $timestamp;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTimestamp()
	{
		return $this->_timestamp;
	}

	/**
	 * @param string $token
	 *
	 * @return LegacyOAuthProvider
	 */
	public function setToken( $token )
	{
		$this->_token = $token;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getToken()
	{
		return $this->_token;
	}

	/**
	 * @param string $version
	 *
	 * @return LegacyOAuthProvider
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
}
