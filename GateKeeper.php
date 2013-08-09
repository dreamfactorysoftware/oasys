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
namespace DreamFactory\Oasys;

use DreamFactory\Oasys\Interfaces\OasysStorageProvider;
use DreamFactory\Oasys\OasysException;
use DreamFactory\Oasys\Stores\Session;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Option;

/**
 * GateKeeper
 */
class GateKeeper extends Seed
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var OasysStorageProvider
	 */
	protected $_store = null;
	/**
	 * @var KeyMaster
	 */
	protected $_client = null;
	/**
	 * @var array Oasys configuration options
	 */
	protected $_options = array();
	/**
	 * @var array
	 */
	protected $_providerCache = array();

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array $settings
	 *
	 * @throws Exception
	 * @throws \InvalidArgumentException
	 * @throws
	 * @internal param array|\stdClass $options
	 */
	public function __construct( $settings = array() )
	{
		if ( is_string( $settings ) && is_file( $settings ) && is_readable( $settings ) )
		{
			$settings = file_get_contents( $settings );
		}

		if ( empty( $settings ) || !is_array( $settings ) )
		{
			throw new \InvalidArgumentException( '"settings" must be either an array of settings or a path to an include-able file.' );
		}

		parent::__construct( $settings );

		$this->_store = $this->_store ? : new Session();

		//	Render any stored errors
		if ( null !== ( $_error = $this->_store->get( 'error', null, true ) ) )
		{
			if ( isset( $_error['exception'] ) )
			{
				throw $_error['exception'];
			}

			if ( isset( $_error['code'] ) && isset( $_error['message'] ) )
			{
				throw new OasysException( Option::get( $_error, 'message' ), Option::get( $_error, 'code', 500 ) );
			}
		}
	}

	/**
	 * @param string $providerId
	 * @param array  $parameters
	 *
	 * @return mixed
	 */
	public function authenticate( $providerId, $parameters = array() )
	{
		return $this->getClient( $providerId )->authenticate( $parameters );
	}

	/**
	 * Return true if current user is connected with a given provider
	 */
	public function connected( $providerId )
	{
		return $this->getClient( $providerId )->authorized();
	}

	/**
	 * Return a list of authenticated providers
	 */
	public function connectedProviders()
	{
		$_response = array();

		foreach ( $this->_options['providers'] as $_providerId => $_config )
		{
			if ( $this->connected( $_providerId ) )
			{
				$_response[] = $_providerId;
			}
		}

		return $_response;
	}

	/**
	 * Return all available providers and their status
	 */
	public function getProviders()
	{
		$_response = array();

		foreach ( $this->_options['providers'] as $_providerId => $_config )
		{
			$_response[$_providerId] = array(
				'connected' => $this->connected( $_providerId )
			);
		}

		return $_response;
	}

	/**
	 * Deauthorize a single provider
	 */
	public function unlinkProvider( $providerId )
	{
		return $this->getProvider( $providerId )->logout();
	}

	/**
	 * Deauthorize all linked providers
	 */
	public function unlinkProviders()
	{
		foreach ( $this->connectedProviders() as $_providerId )
		{
			$this->unlinkProvider( $_providerId );
		}
	}

	/**
	 * @param \DreamFactory\Oasys\KeyMaster $client
	 *
	 * @return GateKeeper
	 */
	public function setClient( $client )
	{
		$this->_client = $client;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\KeyMaster
	 */
	public function getClient()
	{
		return $this->_client;
	}

	/**
	 * @param \DreamFactory\Oasys\Interfaces\OasysStorageProvider $store
	 *
	 * @return GateKeeper
	 */
	public function setStore( $store )
	{
		$this->_store = $store;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\Interfaces\OasysStorageProvider
	 */
	public function getStore()
	{
		return $this->_store;
	}

	/**
	 * @param array $options
	 *
	 * @return GateKeeper
	 */
	public function setOptions( $options )
	{
		$this->_options = $options;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Convenience shortcut to the GateKeeper's goodie bag
	 *
	 * @param string $key
	 * @param mixed  $defaultValue
	 * @param bool   $burnAfterReading
	 *
	 * @throws OasysException
	 * @return mixed
	 */
	public function get( $key, $defaultValue = null, $burnAfterReading = false )
	{
		return Option::get( $this->_options, $key, $defaultValue, $burnAfterReading );
	}

	/**
	 * Convenience shortcut to the GateKeeper's goodie bag
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $overwrite
	 *
	 * @throws OasysException
	 * @return mixed|void
	 */
	public function set( $key, $value = null, $overwrite = true )
	{
		return Option::set( $this->_options, $key, $value, $overwrite );
	}

	/**
	 * @param string $providerId
	 *
	 * @return KeyMaster
	 */
	public function getProvider( $providerId = null )
	{
		if ( null === $providerId && 1 == sizeof( $this->_providerCache ) )
		{
			return current( $this->_providerCache );
		}
		//	Generate a new provider object
	}
}
