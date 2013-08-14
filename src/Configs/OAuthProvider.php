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

use DreamFactory\Oasys\Exceptions\RedirectRequiredException;

/**
 * OAuthProvider
 * A generic OAuth 2.0 provider
 */
abstract class OAuthProvider extends BaseProviderConfig
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
	 * @var string
	 */
	protected $_token;
	/**
	 * @var string
	 */
	protected $_version = '2.0';

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array $contents
	 *
	 * @return \DreamFactory\Oasys\Configs\OAuthProvider
	 */
	public function __construct( $contents = array() )
	{
		Option::set( $contents, 'type', static::OAUTH );

		if ( null !== ( $_uri = Option::get( $contents, 'authentication_uri', null, true ) ) )
		{
			$this->mapEndpoint( static::AUTHENTICATION, $_uri );
		}

		if ( null !== ( $_uri = Option::get( $contents, 'service_uri', null, true ) ) )
		{
			$this->mapEndpoint( static::SERVICE, $_uri );
		}

		parent::__construct( $contents );
	}

	/**
	 * @param string $clientId
	 *
	 * @return OAuthProvider
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
	 * @return OAuthProvider
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
	 * @param string $token
	 *
	 * @return OAuthProvider
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
	 * @return OAuthProvider
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
