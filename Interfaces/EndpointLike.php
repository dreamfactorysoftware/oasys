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

/**
 * EndpointLike
 * A thing that looks like an endpoint
 */
interface EndpointLike
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const AUTHORIZE = 'authorize';
	/**
	 * @var string
	 */
	const REQUEST_TOKEN = 'request_token';
	/**
	 * @var string
	 */
	const ACCESS_TOKEN = 'access_token';
	/**
	 * @var string
	 */
	const REFRESH_TOKEN = 'refresh_token';
	/**
	 * @var string
	 */
	const SERVICE = 'service';
	/**
	 * @var string The "revoke access" endpoint
	 */
	const REVOKE = 'revoke';
	/**
	 * @var string The endpoint to retrieve a user identity/profile
	 */
	const IDENTITY = 'identity';

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array[] $endpointMap
	 *
	 * @return $this
	 */
	public function setEndpointMap( $endpointMap );

	/**
	 * @return array[]
	 */
	public function getEndpointMap();

	/**
	 * Returns the endpoint array for $type
	 *
	 * @param int|string $type    endpoint map type (@see EndpointTypes). Defaults to the main service endpoint
	 * @param bool       $urlOnly If true, only the URL is returned in a string
	 *
	 * @return array|string
	 */
	public function getEndpoint( $type = self::SERVICE, $urlOnly = false );

	/**
	 * Returns the endpoint URL for $type
	 *
	 * @param int|string $type endpoint map type (@see EndpointTypes). Defaults to the main service endpoint
	 *
	 * @return string
	 */
	public function getEndpointUrl( $type = self::SERVICE );

	/**
	 * Returns the parameters for the endpoint of $type
	 *
	 * @param int|string $type endpoint map type (@see EndpointTypes). Defaults to the main service endpoint
	 *
	 * @return array
	 */
	public function getEndpointParameters( $type = self::SERVICE );

	/**
	 * Maps one or more endpoints for this provider
	 *
	 * @param int|array[]  $type       An EndpointTypes constant or an array of mappings
	 * @param string|array $endpoint   Call with null to remove a mapping
	 * @param array        $parameters KVPs of additional parameters
	 *
	 * @throws \InvalidArgumentException
	 * @return $this
	 */
	public function mapEndpoint( $type, $endpoint = null, $parameters = null );

}
