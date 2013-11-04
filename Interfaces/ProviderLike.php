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
namespace DreamFactory\Oasys\Interfaces;

use DreamFactory\Oasys\Components\GenericUser;
use DreamFactory\Oasys\Configs\BaseProviderConfig;
use Kisma\Core\Enums\HttpMethod;

/**
 * ProviderLike
 * Describes something that acts like an authentication provider
 */
interface ProviderLike
{
	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param string|ProviderConfigLike $property Sets a single config setting or replace the entire configuration object
	 * @param mixed                     $value
	 * @param bool                      $overwrite
	 *
	 * @return $this
	 */
	public function setConfig( $property, $value = null, $overwrite = true );

	/**
	 * @param string|null $property         Sets a single config setting or the configuration object itself if $property is null
	 * @param mixed       $defaultValue
	 * @param bool        $burnAfterReading If true, $property is removed from the configuration settings after it has been read
	 *
	 * @return ProviderConfigLike|mixed
	 */
	public function getConfig( $property = null, $defaultValue = null, $burnAfterReading = false );

	/**
	 * Returns the provider configuration with keys prefixed with provider name
	 *
	 * @return array
	 */
	public function getConfigForStorage();

	/**
	 * @param array $payload If empty, request query string is used
	 *
	 * @return bool
	 * @throws \DreamFactory\Oasys\Exceptions\RedirectRequiredException
	 */
	public function handleRequest( $payload = null );

	/**
	 * Checks to see if user is authorized with this provider
	 *
	 * @param bool $startIfNot If true, and not authorized, the login flow will commence presently
	 *
	 * @return bool
	 * @throws \DreamFactory\Oasys\Exceptions\RedirectRequiredException
	 */
	public function authorized( $startIfNot = false );

	/**
	 * @param string $resource
	 * @param array  $payload
	 * @param string $method
	 * @param array  $headers
	 *
	 * @return mixed
	 */
	public function fetch( $resource, $payload = array(), $method = HttpMethod::Get, array $headers = array() );

	/**
	 * Reset the authorization locally
	 */
	public function resetAuthorization();

	/**
	 * Returns the normalized provider's user profile
	 *
	 * @return GenericUser
	 */
	public function getUserData();
}
