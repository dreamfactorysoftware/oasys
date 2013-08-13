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

use DreamFactory\Oasys\Interfaces\OasysContainer;
use Kisma\Core\SeedBag;
use Kisma\Core\Utility\Option;

/**
 * BaseProviderConfig
 */
abstract class BaseProviderConfig extends SeedBag implements OasysContainer
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected $_endpoint;
	/**
	 * @var array
	 */
	protected $_endpointMap;
	/**
	 * @var string
	 */
	protected $_redirectUri;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param string $endpoint
	 *
	 * @return BaseProviderConfig
	 */
	public function setEndpoint( $endpoint )
	{
		$this->_endpoint = $endpoint;

		return $this;
	}

	/**
	 * @param string $which Optional endpoint map (i.e., 'auth', 'service', etc.). User defined...
	 *
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function getEndpoint( $which = null )
	{
		$_endpoint = $this->_endpoint;

		if ( null !== $which )
		{
			if ( null === ( $_endpoint = Option::get( $this->_endpointMap, $which ) ) )
			{
				throw new \InvalidArgumentException( 'The endpoint for "' . $which . '" is not mapped.' );
			}
		}

		return $_endpoint;
	}

	/**
	 * @param string $type
	 * @param string $endpoint Call with null to remove a mapping
	 *
	 * @return BaseProviderConfig
	 */
	public function mapEndpoint( $type, $endpoint = null )
	{
		if ( null === $endpoint )
		{
			Option::remove( $this->_endpointMap, $type );

			return $this;
		}

		Option::set( $this->_endpointMap, $type, $endpoint );

		return $this;
	}

	/**
	 * @param array $endpointMap
	 *
	 * @return BaseProviderConfig
	 */
	public function setEndpointMap( $endpointMap )
	{
		$this->_endpointMap = $endpointMap;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getEndpointMap()
	{
		return $this->_endpointMap;
	}

	/**
	 * @param string $redirectUri
	 *
	 * @return BaseProviderConfig
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
