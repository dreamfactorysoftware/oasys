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

use DreamFactory\Oasys\Exceptions\RedirectRequiredException;
use DreamFactory\Oasys\Interfaces\OasysContainer;
use DreamFactory\Oasys\Interfaces\OasysEndpointTypes;
use DreamFactory\Oasys\Interfaces\OasysProvider;
use DreamFactory\Oasys\Interfaces\OasysProviderClient;
use DreamFactory\Oasys\Interfaces\OasysProviderConfigTypes;
use DreamFactory\Oasys\Interfaces\OasysUser;
use Kisma\Core\Exceptions;
use Kisma\Core\Interfaces;
use Kisma\Core\SeedBag;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;

/**
 * BaseProviderConfig
 */
abstract class BaseProviderConfig extends SeedBag implements OasysContainer, OasysEndpointTypes, OasysProviderConfigTypes, OasysProvider
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
	protected $_type = self::OAUTH;
	/**
	 * @var string The endpoint for this provider
	 */
	protected $_endpoint;
	/**
	 * @var array[] The endpoints available for this provider
	 */
	protected $_endpointMap;
	/**
	 * @var string The redirect URI back to me
	 */
	protected $_redirectUri;
	/**
	 * @var OasysProviderClient Provider-supplied SDK/API client
	 */
	protected $_client;
	/**
	 * @var OasysUser The current user, if any
	 */
	protected $_user;
	/**
	 * @var array The scope of the authorization
	 */
	protected $_scope;

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
	 * Called after construction of the provider
	 *
	 * @return bool
	 */
	public function init()
	{
		return true;
	}

	/**
	 * Returns true/false if user is authorized to talk to this provider
	 *
	 * @param array $options Authentication options
	 *
	 * @return string The redirect URI
	 */
	public function authenticate( $options = array() )
	{
		if ( $this->authorized() )
		{
			return $this;
		}

		$this->resetAuthorization();

		$_baseUrl = $this->_redirectUri;
		$_ticket = sha1( $this->getId() . '.' . time() );

		$_startpoint = $_baseUrl . ( false !== strpos( $_baseUrl, '?' ) ? '&' : '?' ) . 'oasys.pid=' . $this->_providerId . '&oasys.ticket=' . $_ticket;

		$this->set( 'oasys.ticket', $_ticket );
		$this->set( 'oasys.pid', $this->_providerId );

		$_options = array_merge(
			array(
				 'redirect_uri' => Curl::currentUrl(),
				 'authorized'   => false,
				 'startpoint'   => $_startpoint,
				 'endpoint'     => $_baseUrl . 'oasys.endpoint=' . $this->_providerId,
			),
			Option::clean( $options )
		);

		//	Save options
		foreach ( $_options as $_key => $_value )
		{
			$this->set( $_key, $_value );
		}

		//	Do it
		return $_startpoint;
	}

	/**
	 * Begin the authorization process
	 *
	 * @throws RedirectRequiredException
	 */
	abstract public function startAuthorization();

	/**
	 * Complete the authorization process
	 */
	abstract public function completeAuthorization();

	/**
	 * @return $this
	 */
	public function resetAuthorization()
	{
		$this->remove( 'redirect_uri' );
		$this->remove( 'endpoint' );
		$this->remove( 'options' );

		return $this;
	}

	/**
	 * Clear out any settings for this provider
	 *
	 * @return $this
	 */
	public function deauthorize()
	{
		//	Clear out any configurations for this provider
		$this->clear();

		return $this;
	}

	/**
	 * Override that adds this provider ID to the key
	 *
	 * @param string $key
	 * @param mixed  $defaultValue
	 * @param bool   $burnAfterReading
	 *
	 * @return mixed
	 */
	public function get( $key = null, $defaultValue = null, $burnAfterReading = false )
	{
		return parent::get( $this->_providerId . '.' . $key, $defaultValue, $burnAfterReading );
	}

	/**
	 * Override that adds this provider ID to the key
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $overwrite
	 *
	 * @return mixed
	 */
	public function set( $key, $value = null, $overwrite = true )
	{
		return parent::set( $this->_providerId . '.' . $key, $value, $overwrite );
	}

	/**
	 * Override that adds this provider ID to the key
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function remove( $key )
	{
		return parent::remove( $this->_providerId . '.' . $key );
	}

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
	 * @param int $type Optional endpoint map (@see OasysEndpointTypes)
	 *
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	public function getEndpoint( $type = null )
	{
		$_endpoint = $this->_endpoint;

		if ( null !== $type )
		{
			if ( !\DreamFactory\Oasys\Enum\OasysEndpointTypes::contains( $type ) )
			{
				throw new \InvalidArgumentException( 'The endpoint type "' . $type . '" is not valid.' );
			}

			if ( null === ( $_endpoint = Option::get( $this->_endpointMap, $type ) ) )
			{
				throw new \InvalidArgumentException( 'The endpoint for "' . $type . '" is not mapped.' );
			}
		}

		return $_endpoint;
	}

	/**
	 * @param int|array[] $type       An OasysEndpointTypes constant or an array of mappings
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

		if ( !\DreamFactory\Oasys\Enum\OasysEndpointTypes::contains( $type ) )
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

	/**
	 * @param \DreamFactory\Oasys\Interfaces\OasysProviderClient $client
	 *
	 * @return BaseProviderConfig
	 */
	public function setClient( $client )
	{
		$this->_client = $client;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\Interfaces\OasysProviderClient
	 */
	public function getClient()
	{
		return $this->_client;
	}

	/**
	 * @param \DreamFactory\Oasys\Configs\OasysUser $user
	 *
	 * @return BaseProviderConfig
	 */
	public function setUser( $user )
	{
		$this->_user = $user;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\Configs\OasysUser
	 */
	public function getUser()
	{
		return $this->_user;
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
	 * @param array $scope
	 *
	 * @return BaseProviderConfig
	 */
	public function setScope( $scope )
	{
		//	Save an array
		if ( is_string( $scope ) )
		{
			$scope = explode( ',', str_replace( ' ', null, $scope ) );
		}

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
