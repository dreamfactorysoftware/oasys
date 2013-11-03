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
namespace DreamFactory\Oasys\Providers;

use DreamFactory\Oasys\Interfaces\OAuthServiceLike;
use DreamFactory\Oasys\Clients\OAuthClient;
use DreamFactory\Oasys\Exceptions\AuthenticationException;
use DreamFactory\Oasys\Exceptions\OasysConfigurationException;
use DreamFactory\Oasys\Exceptions\RedirectRequiredException;
use Kisma\Core\Interfaces\HttpMethod;
use Kisma\Core\Utility\Curl;

/**
 * BaseOAuthProvider
 */
abstract class BaseOAuthProvider extends BaseProvider implements OAuthServiceLike, HttpMethod
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

		if ( empty( $this->_client ) )
		{
			$this->_client = new OAuthClient( $this->_config );
		}
	}

	/**
	 * Checks to see if user is authorized with this provider
	 *
	 * @param bool $startIfNot If true, and not authorized, the login flow will commence presently
	 *
	 * @return bool
	 */
	public function authorized( $startIfNot = false )
	{
		return $this->_client->authorized( $startIfNot );
	}

	/**
	 * @param string $resource
	 * @param array  $payload
	 * @param string $method
	 * @param array  $headers
	 *
	 * @return mixed|void
	 */
	public function fetch( $resource, $payload = array(), $method = self::Get, array $headers = array() )
	{
		return $this->_client->fetch( $resource, $payload, $method, $headers );
	}
}
