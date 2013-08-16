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

use DreamFactory\Oasys\Components\BaseProviderConfig;
use Kisma\Core\Enums\HttpMethod;

/**
 * ProviderLike
 */
interface ProviderLike
{
	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Returns the provider configuration
	 *
	 * @return BaseProviderConfig
	 */
	public function getConfig();

	/**
	 * Checks to see if user is authorized with this provider
	 *
	 * @return bool
	 */
	public function authorized();

	/**
	 * Unlink/disconnect/logout user from provider locally.
	 * Does nothing on the provider end
	 *
	 * @return void
	 */
	public function deauthorize();

	/**
	 * Returns true/false if user is authorized to talk to this provider
	 *
	 * @param array $options Authentication options
	 *
	 * @return $this|ProviderLike|void
	 */
	public function authenticate( $options = array() );

	/**
	 * @param string $resource
	 * @param array  $payload
	 * @param string $method
	 * @param array  $headers
	 *
	 * @return mixed
	 */
	public function fetch( $resource, $payload = array(), $method = HttpMethod::Get, array $headers = array() );
}
