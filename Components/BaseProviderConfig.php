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
namespace DreamFactory\Oasys\Components;

use DreamFactory\Oasys\Enums\EndpointTypes;
use DreamFactory\Oasys\Interfaces\EndpointLike;
use DreamFactory\Oasys\Interfaces\ProviderConfigLike;
use DreamFactory\Oasys\Interfaces\ProviderConfigTypes;
use Kisma\Core\Exceptions;
use Kisma\Core\Interfaces;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;

/**
 * BaseProviderConfig
 * A simple container to hold a provider's configuration elements
 */
abstract class BaseProviderConfig extends Seed implements ProviderConfigLike, EndpointLike
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string The name/ID of this provider (i.e. github, facebook, twitter, etc.)
	 */
	protected $_providerId;
	/**
	 * @var int The type of provider authentication
	 */
	protected $_type;
	/**
	 * @var array[] The endpoints available for this provider
	 */
	protected $_endpointMap;
	/**
	 * @var
	 */
	protected $_templateLoader;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array $contents
	 */
	public function __construct( $contents = array() )
	{
		parent::__construct( $contents );

		if ( empty( $this->_providerId ) )
		{
			$this->_providerId = Inflector::neutralize( str_ireplace( 'provider.php', null, basename( get_class( $this ) ) ) );
		}
	}

	/**
	 * @param array[] $endpointMap
	 *
	 * @return BaseProviderConfig
	 */
	public function setEndpointMap( $endpointMap )
	{
		$this->_endpointMap = $endpointMap;

		return $this;
	}

	/**
	 * @return array[]
	 */
	public function getEndpointMap()
	{
		return $this->_endpointMap;
	}

	/**
	 * @param int $type endpoint map type (@see EndpointTypes)
	 *
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function getEndpoint( $type )
	{
		if ( !EndpointTypes::contains( $type ) )
		{
			throw new \InvalidArgumentException( 'The endpoint type "' . $type . '" is not valid.' );
		}

		if ( null === ( $_endpoint = Option::get( $this->_endpointMap, $type ) ) )
		{
			if ( empty( $this->_endpointMap ) )
			{
				throw new \InvalidArgumentException( 'The endpoint for "' . $type . '" is not mapped, nor is there a default mapping.' );
			}

			$_endpoint = current( $this->_endpointMap );
		}

		return $_endpoint;
	}

	/**
	 * @param int|array[] $type       An EndpointTypes constant or an array of mappings
	 * @param string      $endpoint   Call with null to remove a mapping
	 * @param array       $parameters KVPs of additional parameters
	 *
	 * @throws \InvalidArgumentException
	 * @return BaseProviderConfig
	 */
	public function mapEndpoint( $type, $endpoint = null, $parameters = null )
	{
		//	Allow for an array of endpoints to be passed in...
		if ( is_array( $type ) && null === $endpoint )
		{
			foreach ( $type as $_endpointType => $_endpoint )
			{
				$this->mapEndpoint( $_endpointType, $_endpoint );
			}
		}

		if ( !EndpointTypes::contains( $type ) )
		{
			throw new \InvalidArgumentException( 'The endpoint type "' . $type . '" is not valid.' );
		}

		if ( null === $endpoint )
		{
			Option::remove( $this->_endpointMap, $type );

			return $this;
		}

		Option::set( $this->_endpointMap, $type, is_array( $endpoint ) ? $endpoint : array( 'endpoint' => $endpoint, 'parameters' => $parameters ) );

		return $this;
	}

	/**
	 * @param int $type
	 *
	 * @return BaseProviderConfig
	 */
	public function setType( $type )
	{
		$this->_type = $type;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @param string $providerId
	 *
	 * @return BaseProviderConfig
	 */
	public function setProviderId( $providerId )
	{
		$this->_providerId = $providerId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getProviderId()
	{
		return $this->_providerId;
	}

	/**
	 * @return string JSON-encoded representation of this config
	 */
	public function toJson()
	{
		$_json = array();

		foreach ( get_object_vars( $this ) as $_property )
		{
			$_property = ltrim( '_' );

			if ( method_exists( $this, 'get' . $_property ) )
			{
				$_json[$_property] = $this->{'get' . $_property}();
			}
		}

		return json_encode( $_json );
	}

	/**
	 * @return array The config in an array
	 */
	public function toArray()
	{
		return json_decode( $this->toJson(), true );
	}
}
