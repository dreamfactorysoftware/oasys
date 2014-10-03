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
namespace DreamFactory\Oasys\Providers;

use DreamFactory\Oasys\Components\GenericUser;

/**
 * GenericOAuth
 * A GenericOAuth provider for general use
 */
class GenericOAuth extends BaseOAuthProvider
{
	/**
	 * Returns the normalized provider's user profile
	 *
	 * @param string $resource
	 * @param array  $payload
	 * @param string $method
	 * @param array  $headers
	 * @param array  $curlOptions
	 *
	 * @return GenericUser
	 */
	public function getUserData( $resource = null, $payload = array(), $method = self::Get, $headers = array(), array $curlOptions = array() )
	{
		return $this->fetch( $resource, $payload, $method, $headers, $curlOptions );
	}
}
