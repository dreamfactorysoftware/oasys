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
use DreamFactory\Oasys\Enum\OAuthAccessTypes;
use DreamFactory\Oasys\Enum\OAuthFlows;

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
	protected $_flow = OAuthFlows::SERVER_SIDE;
	/**
	 * @var int
	 */
	protected $_accessType = OAuthAccessTypes::OFFLINE;
	/**
	 * @var string
	 */
	protected $_accessToken;
	/**
	 * @var string
	 */
	protected $_refreshToken;

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

		if ( null !== ( $_uri = Option::get( $contents, 'authorization_endpoint', null, true ) ) )
		{
			$this->mapEndpoint( static::AUTHORIZE, $_uri );
		}

		if ( null !== ( $_uri = Option::get( $contents, 'access_token_endpoint', null, true ) ) )
		{
			$this->mapEndpoint( static::ACCESS_TOKEN, $_uri );
		}
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
	 * @param int $flow
	 *
	 * @return OAuthProviderConfig
	 */
	public function setFlow( $flow )
	{
		$this->_flow = $flow;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getFlow()
	{
		return $this->_flow;
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

}
