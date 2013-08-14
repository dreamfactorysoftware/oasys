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

use DreamFactory\Oasys\Configs\BaseProviderConfig;
use DreamFactory\Oasys\Interfaces\OasysStorageProvider;
use DreamFactory\Oasys\OasysException;
use DreamFactory\Oasys\Stores\FileSystem;
use DreamFactory\Oasys\Stores\Session;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Inflector;
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
	 * @var array Oasys configuration options
	 */
	protected $_options = array();
	/**
	 * @var array
	 */
	protected static $_providerCache = array();

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array $settings
	 *
	 * @throws
	 * @throws \InvalidArgumentException
	 * @throws OasysException
	 * @return \DreamFactory\Oasys\GateKeeper
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

		//	No store provided, make one...
		if ( empty( $this->_store ) )
		{
			$this->_store = ( 'cli' == PHP_SAPI ? new FileSystem( $this->getId() ) : new Session() );
		}

		//	No redirect URI, make one...
		if ( null === $this->get( 'redirect_uri' ) )
		{
			$this->set( 'redirect_uri', Curl::currentUrl() );
		}

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
	 * Create a provider and return it
	 *
	 * @param string                                               $providerId
	 * @param array|\DreamFactory\Oasys\Configs\BaseProviderConfig $config
	 *
	 * @return BaseProviderConfig
	 */
	public function createProvider( $providerId, $config = null )
	{
		//	Cached?
		if ( null === ( $_provider = Option::get( static::$_providerCache, $providerId ) ) )
		{
			//	No config, no provider
			if ( empty( $config ) )
			{
				return null;
			}

			if ( !( $config instanceof BaseProviderConfig ) )
			{
				$config = static::createProviderConfig( $providerId, $config );
			}

			Option::set(
				static::$_providerCache,
				$providerId,
				$_provider = new KeyMaster( $this, $providerId, $config )
			);
		}

		return $_provider;
	}

	/**
	 * Provider config factory
	 *
	 * @param string $providerId
	 * @param array  $config
	 *
	 * @throws \InvalidArgumentException
	 * @return BaseProviderConfig
	 */
	public function createProviderConfig( $providerId, array $config = array() )
	{
		$_defaults = array();
		$providerId = $this->_cleanProviderId( $providerId );

		//	See if there is a default template
		$_template = __DIR__ . '/Configs/Templates/Providers/' . $providerId . 'config.php.dist';

		if ( is_file( $_template ) && is_readable( $_template ) )
		{
			$_defaults = array_merge( $_defaults, @include( $_template ) );
		}

		$_options = array_merge( $_defaults, $config );

		if ( null === ( $_class = Option::get( $_options, 'class' ) ) )
		{
			throw new \InvalidArgumentException( 'Provider template not found, does not contain a "class" element, or no "class" element found in $config parameter.' );
		}

		return new $_class( array_merge( $_defaults, $config ) );
	}

	/**
	 * Returns the cached provider if any, or, if $config is not empty, creates a new provider
	 *
	 * @param string $providerId
	 * @param array  $config
	 *
	 * @return KeyMaster
	 */
	public function getProvider( $providerId, $config = array() )
	{
		return $this->createProvider( $providerId, $config );
	}

	/**
	 * @param string $providerId
	 * @param array  $parameters
	 *
	 * @return mixed
	 */
	public function authenticate( $providerId, $parameters = array() )
	{
		return $this->getProvider( $providerId )->getConfig()->authenticate( $parameters );
	}

	/**
	 * Return true if current user is connected with a given provider
	 */
	public function connected( $providerId )
	{
		return $this->getProvider( $providerId )->getConfig()->authorized();
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
		return $this->getProvider( $providerId )->getConfig()->deauthorize();
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
	 * @param string $providerId A provider name/ID or provider class name. Facebook => facebook, GitHub => github
	 *
	 * @return string
	 */
	protected function _cleanProviderId( $providerId )
	{
		return Inflector::neutralize( strtolower( str_ireplace( array( 'Provider.php', '.php' ), null, basename( $providerId ) ) ) );
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
	 * Convenience shortcut to the GateKeeper's goody bag
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
}
