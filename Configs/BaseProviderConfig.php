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
abstract class BaseProviderConfig extends Seed implements ProviderConfigLike
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
	 * @var string The user agent to use for this provider, if any
	 */
	protected $_userAgent;
	/**
	 * @var array
	 */
	protected $_payload;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param bool  $returnAll         If true, all configuration values are returned. Otherwise only a subset are available
	 *
	 * @param array $allowedProperties An array of properties to emit. If empty, all properties with getters will be emitted
	 *
	 * @return string JSON-encoded representation of this config
	 * @return string
	 */
	public function toJson( $returnAll = false, $allowedProperties = array() )
	{
		static $_baseProperties
		= array(
			'type',
			'endpointMap',
			'userAgent',
		);

		$_properties = array_merge( $_baseProperties, $allowedProperties );

		$_json = array();

		foreach ( get_object_vars( $this ) as $_key => $_value )
		{
			$_key = ltrim( $_key, '_' );

			//	Filter
			if ( false === $returnAll && !in_array( $_key, $_properties ) )
			{
				continue;
			}

			if ( method_exists( $this, 'get' . $_key ) )
			{
				$_json[Inflector::neutralize( $_key )] = $_value;
			}
		}

		return json_encode( $_json );
	}

	/**
	 * @param array[] $endpointMap
	 *
	 * @return BaseProviderConfig
	 */
	public function setEndpointMap( $endpointMap )
	{
		foreach ( Option::clean( $endpointMap ) as $_type => $_endpoint )
		{
			$this->mapEndpoint( $_type, $_endpoint );
		}

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
	 * @param int|string $type
	 *
	 * @return string
	 */
	public function getEndpointUrl( $type = self::SERVICE )
	{
		return $this->getEndpoint( $type, true );
	}

	/**
	 * @param int|string $type
	 *
	 * @return array
	 */
	public function getEndpointParameters( $type = self::SERVICE )
	{
		$_map = $this->getEndpoint( $type );

		return Option::get( $_map, 'parameters', array() );
	}

	/**
	 * @param int|string $type endpoint map type (@see EndpointTypes)
	 * @param bool       $urlOnly
	 *
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function getEndpoint( $type = self::SERVICE, $urlOnly = false )
	{
		$type = $this->_getEndpointType( $type );

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

		if ( false !== $urlOnly )
		{
			return Option::get( $_endpoint, 'endpoint' );
		}

		return $_endpoint;
	}

	/**
	 * @param int|array[]  $type       An EndpointTypes constant or an array of mappings
	 * @param string|array $endpoint   Call with null to remove a mapping
	 * @param array        $parameters KVPs of additional parameters
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

			return $this;
		}

		$type = $this->_getEndpointType( $type );

		if ( !EndpointTypes::contains( $type ) )
		{
			throw new \InvalidArgumentException( 'The endpoint type "' . $type . '" is not valid.' );
		}

		if ( null === $endpoint )
		{
			Option::remove( $this->_endpointMap, $type );

			return $this;
		}

		if ( is_string( $endpoint ) )
		{
			$endpoint = array(
				'endpoint'   => $endpoint,
				'parameters' => $parameters ? : array()
			);
		}

		$this->_endpointMap[$type] = $endpoint;

		return $this;
	}

	/**
	 * Cleans up prior stored numerically indexed endpoints.
	 *
	 * @param string|int $type
	 *
	 * @return int|string
	 */
	protected function _getEndpointType( $type )
	{
		$_type = $type;

		//	Clean up old types if stored...
		if ( is_numeric( $_type ) )
		{
			switch ( $_type )
			{
				case 0:
					$_type = EndpointTypes::AUTHORIZE;
					break;

				case 1:
					$_type = EndpointTypes::REQUEST_TOKEN;
					break;

				case 2:
					$_type = EndpointTypes::ACCESS_TOKEN;
					break;

				case 3:
					$_type = EndpointTypes::REFRESH_TOKEN;
					break;

				case 4:
					$_type = EndpointTypes::SERVICE;
					break;
			}
		}

		return $_type;
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
	 * @param string $userAgent
	 *
	 * @return BaseProviderConfig
	 */
	public function setUserAgent( $userAgent )
	{
		$this->_userAgent = $userAgent;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUserAgent()
	{
		return $this->_userAgent;
	}

	/**
	 * @param bool $returnAll If true, all configuration values are returned. Otherwise only a subset are available
	 *
	 * @return array The config in an array
	 */
	public function toArray( $returnAll = false )
	{
		return json_decode( $this->toJson( $returnAll ), true );
	}

	/**
	 * @param array $payload
	 *
	 * @return BaseProviderConfig
	 */
	public function setPayload( $payload )
	{
		$this->_payload = $payload;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getPayload()
	{
		return $this->_payload;
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
}
