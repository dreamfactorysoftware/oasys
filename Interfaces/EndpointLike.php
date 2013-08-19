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
 * Acts like an endpoint
 */
interface EndpointLike
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var int
	 */
	const AUTHORIZE = 'authorize';
	/**
	 * @var int
	 */
	const REQUEST_TOKEN = 'request_token';
	/**
	 * @var int
	 */
	const ACCESS_TOKEN = 'access_token';
	/**
	 * @var int
	 */
	const REFRESH_TOKEN = 'refresh_token';
	/**
	 * @var int
	 */
	const SERVICE = 'service';

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
	 * @param int|string $type endpoint map type (@see EndpointTypes). Defaults to the main service endpoint
	 * @param bool       $urlOnly
	 *
	 * @return array
	 */
	public function getEndpoint( $type = self::SERVICE, $urlOnly = false );

	/**
	 * @param int|string $type endpoint map type (@see EndpointTypes). Defaults to the main service endpoint
	 *
	 * @return string
	 */
	public function getEndpointUrl( $type = self::SERVICE );

	/**
	 * @param int|string $type endpoint map type (@see EndpointTypes). Defaults to the main service endpoint
	 *
	 * @return array
	 */
	public function getEndpointParameters( $type = self::SERVICE );
}
