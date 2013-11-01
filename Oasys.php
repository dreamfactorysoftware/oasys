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

use DreamFactory\Oasys\Enums\ProviderConfigTypes;
use DreamFactory\Oasys\Interfaces\ProviderLike;
use DreamFactory\Oasys\Interfaces\ProviderConfigLike;
use DreamFactory\Oasys\Interfaces\StorageProviderLike;
use DreamFactory\Oasys\OasysException;
use DreamFactory\Oasys\Providers\BaseProvider;
use DreamFactory\Oasys\Stores\FileSystem;
use DreamFactory\Oasys\Stores\Session;
use Kisma\Core\Interfaces;
use Kisma\Core\SeedUtility;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;

/**
 * Oasys
 * The mother ship
 */
class Oasys extends SeedUtility
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const DEFAULT_PROVIDER_NAMESPACE = 'DreamFactory\\Oasys\\Providers';
	/**
	 * @var string The prefix to provider IDs that want to use the generic
	 */
	const GENERIC_PROVIDER_PATTERN = 'generic:';

	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var StorageProviderLike
	 */
	protected static $_store = null;
	/**
	 * @var array Oasys configuration options
	 */
	protected static $_options = array();
	/**
	 * @var ProviderLike[]
	 */
	protected static $_providerCache = array();
	/**
	 * @var array A namespace => path mapping of provider classes
	 */
	protected static $_providerPaths = array();
	/**
	 * @var array
	 */
	protected static $_classMap = array();
	/**
	 * @var bool
	 */
	protected static $_initialized = false;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array $settings
	 *
	 * @throws
	 * @throws \DreamFactory\Oasys\OasysException
	 */
	public static function initialize( $settings = array() )
	{
		if ( static::$_initialized )
		{
			return;
		}

		//	Set the default Providers path.
		if ( empty( static::$_providerPaths ) )
		{
			static::$_providerPaths = array( static::DEFAULT_PROVIDER_NAMESPACE => __DIR__ . '/Providers' );
		}

		if ( is_string( $settings ) && is_file( $settings ) && is_readable( $settings ) )
		{
			$settings = file_get_contents( $settings );
		}

		//	No store provided, make one...
		if ( empty( static::$_store ) && isset( $_SESSION ) && PHP_SESSION_DISABLED != session_status() )
		{
			static::$_store =
				( 'cli' == PHP_SAPI ? new FileSystem( \hash( 'sha256', getmypid() . microtime( true ) ), null, $settings ) : new Session( $settings ) );
		}

		//	Render any stored errors
		if ( null !== ( $_error = static::getOptions( 'error', null, true ) ) )
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

		static::_mapProviders();
		static::$_initialized = true;
	}

	/**
	 * Synchronize with store
	 */
	public static function sync()
	{
		$_store = static::getStore();
		$_cache = static::getProviderCache();

		//	Save off the store
		if ( !empty( $_store ) && !empty( $_cache ) )
		{
			foreach ( $_cache as $_id => $_provider )
			{
				foreach ( $_provider->getConfig()->toArray() as $_key => $_value )
				{
					$_store->set( $_id . '.' . $_key, $_value );
				}
			}
			//	Store will sync when it's destroyed...
		}
	}

	/**
	 * @param string $namespace
	 * @param string $path
	 */
	public static function addProviderPath( $namespace, $path )
	{
		static::$_providerPaths[$namespace] = $path;
		static::_mapProviders();
	}

	/**
	 * Create a provider and return it
	 *
	 * @param string                   $providerId
	 * @param array|ProviderConfigLike $config
	 * @param bool                     $createIfNotFound If false and provider not already created, NULL is returned
	 *
	 * @throws \InvalidArgumentException
	 * @return ProviderLike
	 */
	public static function getProvider( $providerId, $config = null, $createIfNotFound = true )
	{
		if ( !static::$_initialized )
		{
			static::initialize();
		}

		$_providerId = $_mapKey = $providerId;
		$_type = null;
		$_generic = false;

		if ( false === strpos( $_providerId, static::GENERIC_PROVIDER_PATTERN, 0 ) )
		{
			$_providerId = static::_cleanProviderId( $providerId );
		}
		else
		{
			$_parts = explode( ':', $_providerId );

			if ( empty( $_parts ) || 3 != sizeof( $_parts ) )
			{
				throw new \InvalidArgumentException( 'Invalid provider ID specified. Use predefined or generic "generic:providerId:type" format.' );
			}

			$_providerId = static::_cleanProviderId( $_parts[1] );
			$_type = str_ireplace( 'oauth', 'OAuth', ProviderConfigTypes::nameOf( $_parts[2] ) );
			$_mapKey = 'generic' . $_type;
			$_generic = ':' . $_providerId;
		}

		//	Cached?
		if ( null === ( $_provider = Option::get( static::$_providerCache, $_mapKey . ( $_generic ? : null ) ) ) )
		{
			//	Get the class mapping...
			if ( null === ( $_map = Option::get( static::$_classMap, $_mapKey ) ) )
			{
				throw new \InvalidArgumentException( 'The provider "' . $_providerId . '" has no associated mapping. Cannot create.' );
			}

			if ( true !== $createIfNotFound && null === $config )
			{
				return null;
			}

			if ( null !== $config && !( $config instanceof ProviderConfigLike ) && !is_array( $config ) && !is_object( $config ) )
			{
				throw new \InvalidArgumentException( 'The $config specified my be null, an array, or an instance of ProviderConfigLike.' );
			}

			/** @noinspection PhpIncludeInspection */
			require $_map['path'];

			if ( empty( $config ) )
			{
				$config = array();
			}

			//	Fill the config with the store values if any
			if ( null !== $_type && null == Option::get( $config, 'type' ) )
			{
				Option::set( $config, 'type', $_type );
			}

			$_className = $_map['namespace'] . '\\' . $_map['class_name'];
			$_mirror = new \ReflectionClass( $_className );

			//	Load any stored configuration
			$config = static::_loadConfigFromStore( $_providerId, $config );

			//	Instantiate!
			/** @var BaseProvider $_provider */
			$_provider = $_mirror->newInstanceArgs(
				array(
					 $_providerId,
					 $config
				)
			);

			//	Keep a copy...
			Option::set(
				static::$_providerCache,
				$_mapKey . ( $_generic ? : null ),
				$_provider
			);

			//	Jam the store
			static::sync();
		}

		return $_provider;
	}

	/**
	 * Return true if current user is connected with a given provider
	 *
	 * @param string                   $providerId
	 * @param ProviderConfigLike|array $config
	 * @param bool                     $startFlow
	 *
	 * @return bool
	 */
	public static function authorized( $providerId, $config = null, $startFlow = false )
	{
		return static::getProvider( $providerId, $config )->authorized( $startFlow );
	}

	/**
	 * Return all available providers and their status
	 */
	public static function getProviders()
	{
		$_response = array();

		foreach ( static::$_options['providers'] as $_providerId => $_config )
		{
			$_response[] = $_providerId;
		}

		return $_response;
	}

	/**
	 * Deauthorize a single provider
	 */
	public static function resetProvider( $providerId )
	{
		static::getProvider( $providerId )->resetAuthorization();
	}

	/**
	 * @param string                   $providerId
	 * @param ProviderConfigLike|array $config
	 *
	 * @return array
	 */
	protected static function _loadConfigFromStore( $providerId, $config )
	{
		$_check = $providerId . '.';
		$_checkLength = strlen( $_check );
		$_defaults = array();

		$_storedConfig = Oasys::getStore()->get();

		foreach ( $_storedConfig as $_key => $_value )
		{
			if ( $_check == substr( $_key, 0, $_checkLength ) && ( '' !== $_value && null !== $_value ) )
			{
				$_key = substr( $_key, $_checkLength );
				Option::set( $_defaults, $_key, $_value );
			}
		}

		if ( $config instanceof ProviderConfigLike )
		{
			$config->mergeSettings( $_defaults );
		}
		else
		{
			$config = array_merge(
				$_defaults,
				$config
			);
		}

		unset( $_defaults, $_storedConfig );

		//	Clean up blanks in the config as to not overwrite defaults
		foreach ( $config as $_key => $_value )
		{
			$_value = is_string( $_value ) ? trim( $_value ) : $_value;

			if ( null === $_value || '' === $_value )
			{
				unset( $config[$_key] );
			}
		}

		return $config;
	}

	/**
	 * @param string $providerId A provider name/ID or provider class name. Facebook => facebook, GitHub => github
	 *
	 * @return string
	 */
	protected static function _cleanProviderId( $providerId )
	{
		return Inflector::neutralize(
			strtolower(
				str_ireplace(
					array(
						 'Provider.php',
						 '.php'
					),
					null,
					$providerId
				)
			)
		);
	}

	/**
	 * Makes a hash of providers and their associated classes
	 *
	 * @return void
	 */
	protected static function _mapProviders()
	{
		$_classMap = array();

		foreach ( static::$_providerPaths as $_namespace => $_path )
		{
			$_classes = glob( $_path . '/*.php' );

			foreach ( $_classes as $_class )
			{
				$_className = str_ireplace( '.php', null, basename( $_class ) );
				$_providerId = Inflector::neutralize( $_className );

				//	Skip base classes in these directories...
				if ( 'base_' == substr( $_providerId, 0, 4 ) )
				{
					continue;
				}

				$_classMap[$_providerId] = array(
					'class_name' => $_className,
					'path'       => $_class,
					'namespace'  => $_namespace
				);

				unset( $_className, $_providerId, $_class );
			}
		}

		//	Merge in the found classes
		static::$_classMap = array_merge( static::$_classMap, $_classMap );
//		Log::debug( 'Classes mapped: ' . print_r( static::$_classMap, true ) );
	}

	/**
	 * Gets a global Oasys option
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param mixed  $defaultValue
	 * @param bool   $burnAfterReading
	 *
	 * @return mixed
	 */
	public static function getOption( $key, $value = null, $defaultValue = null, $burnAfterReading = false )
	{
		return Option::get( static::$_options, $key, $value, $defaultValue, $burnAfterReading );
	}

	/**
	 * Sets a global Oasys option
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return mixed
	 */
	public static function setOption( $key, $value = null )
	{
		return Option::set( static::$_options, $key, $value );
	}

	/**
	 * @param array $classMap
	 */
	public static function setClassMap( $classMap )
	{
		static::$_classMap = $classMap;
	}

	/**
	 * @return array
	 */
	public static function getClassMap()
	{
		return static::$_classMap;
	}

	/**
	 * @param array $options
	 */
	public static function setOptions( $options )
	{
		static::$_options = $options;
	}

	/**
	 * @return array
	 */
	public static function getOptions()
	{
		return static::$_options;
	}

	/**
	 * @param ProviderLike[] $providerCache
	 */
	public static function setProviderCache( $providerCache )
	{
		static::$_providerCache = $providerCache;
	}

	/**
	 * @return ProviderLike[]
	 */
	public static function getProviderCache()
	{
		return static::$_providerCache;
	}

	/**
	 * @param array $providerPaths
	 */
	public static function setProviderPaths( $providerPaths )
	{
		static::$_providerPaths = $providerPaths;
		static::_mapProviders();
	}

	/**
	 * @return array
	 */
	public static function getProviderPaths()
	{
		return static::$_providerPaths;
	}

	/**
	 * @param StorageProviderLike $store
	 * @param bool                $carryForward If true, prior existing store merged into new store
	 */
	public static function setStore( $store, $carryForward = false )
	{
		if ( false !== $carryForward && !empty( static::$_store ) )
		{
			$store->merge( static::$_store->get() );
		}

		static::$_store = $store;
	}

	/**
	 * @return StorageProviderLike
	 */
	public static function getStore()
	{
		return static::$_store;
	}
}

/**
 * Initialize Oasys
 */
Oasys::initialize();
