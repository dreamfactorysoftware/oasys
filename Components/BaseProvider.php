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

use DreamFactory\Oasys\Enums\ProviderConfigTypes;
use DreamFactory\Oasys\Exceptions\OasysConfigurationException;
use DreamFactory\Oasys\Exceptions\RedirectRequiredException;
use DreamFactory\Oasys\Interfaces\ProviderClientLike;
use DreamFactory\Oasys\Interfaces\ProviderLike;
use DreamFactory\Oasys\Interfaces\StorageProviderLike;
use Kisma\Core\Enums\HttpMethod;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;

/**
 * BaseProvider
 * A base class for all providers
 * Automatically prefixes all key values with provider ID
 */
abstract class BaseProvider extends Seed implements ProviderLike
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string The default namespace for authentication configuration classes. NOTE TRAILING SLASHES!
	 */
	const DEFAULT_CONFIG_NAMESPACE = 'DreamFactory\\Oasys\\Configs\\';

	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string The ID of this provider
	 */
	protected $_providerId;
	/**
	 * @var int The type of authentication this provider provides
	 */
	protected $_type;
	/**
	 * @var GateKeeper Our Gate Keeper
	 */
	protected $_keeper;
	/**
	 * @var StorageProviderLike Our storage mechanism
	 */
	protected $_store;
	/**
	 * @var BaseProviderConfig The configuration options for this provider
	 */
	protected $_config;
	/**
	 * @var ProviderClientLike Additional provider-supplied client/SDK that interacts with provider (i.e. Facebook PHP SDK)
	 */
	protected $_client;
	/**
	 * @var bool If true, the user will be redirected if necessary. Otherwise the URL of the expected redirect is returned
	 */
	protected $_interactive = false;
	/**
	 * @var array The payload of the request, if any.
	 */
	protected $_payload;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param GateKeeper               $keeper
	 * @param string                   $providerId The name/ID of this provider
	 * @param BaseProviderConfig|array $config
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\OasysConfigurationException
	 * @throws \InvalidArgumentException
	 * @return \DreamFactory\Oasys\Components\BaseProvider
	 */
	public function __construct( GateKeeper $keeper, $providerId, $config = null )
	{
		$this->_providerId = $providerId;
		$this->_keeper = $keeper;
		$this->_store = $keeper->getStore();

		if ( empty( $this->_config ) && ( null === $config || !( $config instanceof BaseProviderConfig ) ) )
		{
			$this->_config = $this->_createConfiguration( $config );
		}

		if ( empty( $this->_store ) )
		{
			throw new \InvalidArgumentException( 'No storage mechanism configured.' );
		}

		if ( empty( $this->_providerId ) )
		{
			throw new \InvalidArgumentException( 'No provider specified.' );
		}

		$this->init();

		//	By this point, $_config is required.
		if ( empty( $this->_config ) )
		{
			throw new OasysConfigurationException( 'No configuration was specified or set.' );
		}
	}

	/**
	 * @param array $config
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\OasysConfigurationException
	 * @throws \InvalidArgumentException
	 * @return
	 */
	protected function _createConfiguration( $config = null )
	{
		$_defaults = array();

		//	See if there is a default template and load up the defaults
		$_template = dirname( __DIR__ ) . '/Providers/Templates/' . $this->_providerId . '.template.php';

		if ( is_file( $_template ) && is_readable( $_template ) )
		{
			/** @noinspection PhpIncludeInspection */
			$_defaults = require( $_template );
		}

		//	Merge in the template, stored stuff and user supplied stuff
		$_config = array_merge(
			$_defaults,
			Option::clean( $config )
		);

		if ( null === ( $this->_type = Option::get( $_config, 'type' ) ) )
		{
			throw new OasysConfigurationException( 'You must specify the "type" of provider when using auto-generated configurations.' );
		}

		//	Build the class name for the type of authentication of this provider
		$_class = str_ireplace(
			'oauth',
			'OAuth',
			static::DEFAULT_CONFIG_NAMESPACE . ucfirst( Inflector::deneutralize( strtolower( ProviderConfigTypes::nameOf( $this->_type ) ) . '_provider_config' ) )
		);

		//	Instantiate!
		return new $_class( $_config );
	}

	/**
	 * @param array $payload If empty, request query string is used
	 *
	 * @return \DreamFactory\Oasys\Exceptions\RedirectRequiredException
	 * @return mixed
	 */
	public function handleRequest( $payload = null )
	{
		$_payload = $this->_parseResult( $payload );

		if ( empty( $_payload ) )
		{
			$_payload = $this->_payload;
		}

		if ( !$this->authorized() )
		{
			return $this->startAuthorization();
		}

		return $this->completeAuthorization();
	}

	/**
	 * Called after construction of the provider
	 *
	 * @return bool
	 */
	public function init()
	{
		$_config = Option::clean( $this->_config->toArray() );

		//	Store our config in the store...
		foreach ( $_config as $_key => $_value )
		{
			$this->set( $_key, $_value );
		}

		//	Parse the inbound payload
		$this->_parseRequest();

		return true;
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
	 * Clear out any settings for this provider
	 *
	 * @return $this
	 */
	public function resetAuthorization()
	{
		$this->_store->clear();

		return $this;
	}

	/**
	 * @param array $payload Additional values to send to authenticator
	 *
	 * @return string The redirect URI
	 */
	public function authenticate( $payload = array() )
	{
		if ( $this->authorized() )
		{
			return $this;
		}

		$this->resetAuthorization();

		$_baseUrl = $this->get( 'redirect_uri' );
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
			Option::clean( $payload )
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
	 * Reset the authorization adn redirect back to our redirect
	 */
	protected function _resetRedirect()
	{
		$_uri = $this->get( 'redirect_uri' );
		$this->resetAuthorization();
		$this->_redirect( $_uri );
	}

	/**
	 * Internally used redirect method.
	 *
	 * @param string $uri
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\RedirectRequiredException
	 */
	protected function _redirect( $uri )
	{
		//	Throw redirect exception for non-interactive
		if ( false === $this->_interactive )
		{
			throw new RedirectRequiredException( $uri );
		}

		//	Redirect!
		header( 'Location: ' . $uri );

		//	And... we're spent
		die();
	}

	/**
	 * Parse  a JSON or HTTP query string into an array
	 *
	 * @param string $result
	 *
	 * @return mixed
	 */
	protected function _parseResult( $result )
	{
		if ( is_string( $result ) && false !== json_decode( $result ) )
		{
			$_result = json_decode( $result );
		}
		else
		{
			parse_str( $result, $_result );
		}

		return $_result;
	}

	/**
	 * Parses the inbound request + query string into a single KVP array
	 *
	 * @return array
	 */
	protected function _parseRequest()
	{
		if ( !empty( $_REQUEST ) )
		{
			$this->_payload = $_REQUEST;
		}

		//	Bust it wide open
		parse_str( Option::server( 'QUERY_STRING' ), $_query );

		//	Set it and forget it
		return $this->_payload = array_merge( $_query, Option::clean( $this->_payload ) );
	}

	/**
	 * @param ProviderClientLike $client
	 *
	 * @return BaseProvider
	 */
	protected function _setClient( $client )
	{
		$this->_client = $client;

		return $this;
	}

	/**
	 * @return ProviderClientLike
	 */
	public function getClient()
	{
		return $this->_client;
	}

	/**
	 * @param GateKeeper $keeper
	 *
	 * @return BaseProvider
	 */
	protected function _setKeeper( $keeper )
	{
		$this->_keeper = $keeper;

		return $this;
	}

	/**
	 * @return GateKeeper
	 */
	public function getKeeper()
	{
		return $this->_keeper;
	}

	/**
	 * @param string $providerId
	 *
	 * @return BaseProvider
	 */
	protected function _setProviderId( $providerId )
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
	 * @param mixed $request
	 *
	 * @return BaseProvider
	 */
	public function _setRequest( $request )
	{
		$this->_parseResult( $request );

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
	 * @param \DreamFactory\Oasys\Interfaces\StorageProviderLike $store
	 *
	 * @return BaseProvider
	 */
	protected function _setStore( $store )
	{
		$this->_store = $store;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\Interfaces\StorageProviderLike
	 */
	public function getStore()
	{
		return $this->_store;
	}

	/**
	 * @param boolean $interactive
	 *
	 * @return BaseProvider
	 */
	protected function _setInteractive( $interactive )
	{
		$this->_interactive = $interactive;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getInteractive()
	{
		return $this->_interactive;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	protected function _cleanStoreKey( $key )
	{
		return !empty( $key ) ? $this->_providerId . '.' . $key : $key;
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
	public function get( $key = null, $defaultValue = null, $burnAfterReading = false )
	{
		$_configKey = str_ireplace( $this->_providerId . '.', null, $this->_cleanStoreKey( $key ) );

		return Option::get( $this->_config, $_configKey, $defaultValue, $burnAfterReading );
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
		$_configKey = str_ireplace( $this->_providerId . '.', null, $this->_cleanStoreKey( $key ) );

		return Option::set( $this->_config, $_configKey, $value, $overwrite );
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $overwrite
	 *
	 * @return BaseProvider
	 */
	public function setGlobal( $key, $value = null, $overwrite = true )
	{
		$this->_keeper->setGlobal( $key, $value, $overwrite );

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $defaultValue
	 * @param bool   $burnAfterReading
	 *
	 * @return mixed
	 */
	public function getGlobal( $key, $defaultValue = null, $burnAfterReading = false )
	{
		return $this->_keeper->getGlobal( $key, $defaultValue, $burnAfterReading );
	}

	/**
	 * Returns all global settings
	 *
	 * @return array
	 */
	public function getGlobals()
	{
		return $this->_keeper->getOptions();
	}

	/**
	 * @param int $type
	 *
	 * @return BaseProvider
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
	 * @param \DreamFactory\Oasys\Components\BaseProviderConfig $config
	 *
	 * @return BaseProvider
	 */
	public function setConfig( $config )
	{
		$this->_config = $config;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\Components\BaseProviderConfig
	 */
	public function getConfig()
	{
		return $this->_config;
	}
}
