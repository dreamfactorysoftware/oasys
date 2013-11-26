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
use DreamFactory\Oasys\Enums\ProviderConfigTypes;
use DreamFactory\Oasys\Interfaces\ProviderLike;
use DreamFactory\Oasys\Interfaces\ProviderConfigLike;
use DreamFactory\Oasys\Interfaces\StorageProviderLike;
use DreamFactory\Oasys\OasysException;
use DreamFactory\Oasys\Providers\BaseProvider;
use DreamFactory\Oasys\Stores\BaseOasysStore;
use DreamFactory\Oasys\Stores\FileSystem;
use DreamFactory\Oasys\Stores\Session;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Exceptions\HttpException;
use Kisma\Core\Interfaces;
use Kisma\Core\SeedUtility;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Storage;

/**
 * Oasys
 * The mother ship
 *
 * @todo Add database caching of mappings for faster instantiation
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
	 * @throws \DreamFactory\Oasys\OasysException
	 */
	public static function initialize()
	{
		if ( static::$_initialized )
		{
			return;
		}

		//	Set the default Providers path if not already set
		if ( empty( static::$_providerPaths ) )
		{
			static::$_providerPaths = array();
		}

		Option::sins( static::$_providerPaths, static::DEFAULT_PROVIDER_NAMESPACE, __DIR__ . '/Providers' );

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
				$_store->merge( $_provider->getConfigForStorage() );
			}
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
	 * @return BaseProvider
	 */
	public static function getProvider( $providerId, $config = null, $createIfNotFound = true )
	{
		if ( !static::$_initialized )
		{
			static::initialize();
		}

		//	Look up provider in the cache and use it as a base, otherwise, create a new one using the defaults provided by the provider's author.
		return static::_createProvider( $providerId, $config, $createIfNotFound );
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
	 * Return all known provider IDs
	 *
	 * @return array
	 */
	public static function getProviders()
	{
		return array_keys( static::$_classMap );
	}

	/**
	 * De-authorize a single provider
	 */
	public static function resetProvider( $providerId )
	{
		static::getProvider( $providerId )->resetAuthorization();
	}

	/**
	 * Validates an inbound relay request
	 *
	 * @param string $state If not supplied, $_REQUEST['state'] is used.
	 *
	 * @throws \Kisma\Core\Exceptions\HttpException
	 * @return array
	 */
	public static function validateAuthState( $state = null )
	{
		$_state = static::_decodeState( $state );
		$_origin = Option::get( $_state, 'origin' );
		$_apiKey = Option::get( $_state, 'api_key' );

		if ( empty( $_origin ) || empty( $_apiKey ) )
		{
			throw new HttpException( HttpResponse::BadRequest, 'Invalid auth state' );
		}

		if ( $_apiKey != ( $_testKey = sha1( $_origin ) ) )
		{
			Log::error( 'API Key mismatch: ' . $_apiKey . ' != ' . $_testKey );
			throw new HttpException( HttpResponse::Forbidden, 'Invalid API key' );
		}

		return $_state;
	}

	/**
	 * Parses a provider ID spec ([generic:]providerId[:type])
	 *
	 * @param string $providerId
	 *
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected static function _normalizeProviderId( $providerId )
	{
		$_providerId = $_mapKey = $providerId;
		$_type = null;
		$_generic = false;

		if ( false === strpos( $_providerId, static::GENERIC_PROVIDER_PATTERN, 0 ) )
		{
			$_providerId = static::_cleanProviderId( $_providerId );
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

		return array( $_providerId, $_type, $_mapKey, $_generic );
	}

	/**
	 * Create/De-cache a provider and return it
	 *
	 * @param string                   $providerId
	 * @param array|ProviderConfigLike $config
	 * @param bool                     $createIfNotFound If false and provider not already created, NULL is returned
	 *
	 * @throws \InvalidArgumentException
	 * @return BaseProvider
	 */
	protected static function _createProvider( $providerId, $config = null, $createIfNotFound = true )
	{
		list( $_providerId, $_type, $_mapKey, $_generic ) = static::_normalizeProviderId( $providerId );

		$_cacheKey = $_mapKey . ( $_generic ? : null );

		if ( null === ( $_provider = Option::get( static::$_providerCache, $_cacheKey ) ) )
		{
			$_config = empty( $config ) ? array() : $config;

			//	Get the class mapping...
			if ( null === ( $_map = Option::get( static::$_classMap, $_mapKey ) ) )
			{
				throw new \InvalidArgumentException( 'The provider "' . $providerId . '" has no associated mapping. Cannot create.' );
			}

			if ( true !== $createIfNotFound && array() == $_config )
			{
				return null;
			}

			if ( !empty( $_config ) && !( $_config instanceof ProviderConfigLike ) && !is_array( $_config ) && !is_object( $_config ) )
			{
				throw new \InvalidArgumentException( 'The "$config" value specified must be null, an object, an array, or an instance of ProviderConfigLike.' );
			}

			//	Check the endpoint maps...
			$_template = BaseProviderConfig::getTemplate( $providerId );
			$_endpoints = Option::get( $_config, 'endpoint_map', array() );
			Option::set( $_config, 'endpoint_map', array_merge( Option::get( $_template, 'endpoint_map', array() ), $_endpoints ) );

			/** @noinspection PhpIncludeInspection */
			require $_map['path'];

			if ( empty( $_config ) )
			{
				$_config = array();
			}

			$_className = $_map['namespace'] . '\\' . $_map['class_name'];
			$_mirror = new \ReflectionClass( $_className );

			//	Fill the config with the store values if any
			if ( null !== $_type )
			{
				Option::sins( $_config, 'type', $_type );
			}

			//	Load any stored configuration
			$_config = static::_mergeConfigFromStore( $_providerId, $_config );

			//	Instantiate!
			$_provider = $_mirror->newInstanceArgs(
				array(
					 $_providerId,
					 $_config
				)
			);

			//	Cache the current version...
			Option::set( static::$_providerCache, $_cacheKey, $_provider );
		}

		return $_provider;
	}

	/**
	 * Given an inbound state string, convert to original defrosted state
	 *
	 * @param string $state If not supplied, $_REQUEST['state'] is used.
	 *
	 * @return array
	 */
	protected static function _decodeState( $state = null )
	{
		if ( null === ( $_state = $state ? : Option::request( 'state' ) ) )
		{
			return array();
		}

		return Storage::defrost( $_state );
	}

	/**
	 * Creates a compact string representing $data
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	protected static function _encodeState( $data = array() )
	{
		return Storage::freeze( $data );
	}

	/**
	 * @param string                   $providerId
	 * @param ProviderConfigLike|array $config
	 *
	 * @return array
	 */
	protected static function _mergeConfigFromStore( $providerId, $config )
	{
		$_storedConfig = static::getStore()->get();

		if ( empty( $_storedConfig ) )
		{
			return $config;
		}

		if ( $config instanceof ProviderConfigLike )
		{
			$config->mergeSettings( $_storedConfig );
		}
		else
		{
			$config = array_merge(
				$_storedConfig,
				$config
			);
		}

		unset( $_storedConfig );

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
	 * @return BaseOasysStore
	 */
	public static function getStore()
	{
		//	No store provided, make one...
		if ( empty( static::$_store ) )
		{
			//	Session or file store...
			if ( isset( $_SESSION ) && PHP_SESSION_DISABLED != session_status() && 'cli' != PHP_SAPI )
			{
				static::$_store = new Session();
			}
			else
			{
				static::$_store = new FileSystem( \hash( 'sha256', getmypid() . microtime( true ) ) );
			}
		}

		return static::$_store;
	}
}

/**
 * Initialize Oasys
 */
Oasys::initialize();
