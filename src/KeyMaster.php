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
use DreamFactory\Oasys\Exceptions\RedirectRequiredException;
use DreamFactory\Oasys\Interfaces\OasysProviderClient;
use DreamFactory\Oasys\Interfaces\OasysStorageProvider;
use Kisma\Core\Enums\HttpMethod;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Option;

/**
 * KeyMaster
 */
class KeyMaster extends Seed
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string The ID of this provider
	 */
	protected $_providerId;
	/**
	 * @var GateKeeper Our Gate Keeper
	 */
	protected $_keeper;
	/**
	 * @var OasysStorageProvider Our storage mechanism
	 */
	protected $_store;
	/**
	 * @var BaseProviderConfig The configuration options for this provider
	 */
	protected $_config;
	/**
	 * @var OasysProviderClient Additional provider-supplied client/SDK that interacts with provider (i.e. Facebook PHP SDK)
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
	 * @param GateKeeper                 $keeper
	 * @param string                     $providerId The name/ID of this provider
	 * @param Configs\BaseProviderConfig $config
	 *
	 * @throws \InvalidArgumentException
	 * @return \DreamFactory\Oasys\KeyMaster
	 */
	public function __construct( GateKeeper $keeper, $providerId, BaseProviderConfig $config )
	{
		$this->_keeper = $keeper;
		$this->_providerId = $providerId;
		$this->_store = $keeper->getStore();

		if ( empty( $this->_store ) )
		{
			throw new \InvalidArgumentException( 'No storage mechanism configured.' );
		}

		if ( empty( $this->_providerId ) )
		{
			throw new \InvalidArgumentException( 'No provider specified.' );
		}

		$this->_parseRequest();
	}

	/**
	 * @param null  $payload
	 * @param array $payload If empty, request query string is used
	 *
	 * @return array The authorization URI if in interactive mode
	 */
	public function handleRequest( $payload = null )
	{
		$_payload = $this->_parseResult( $payload );

		if ( empty( $_payload ) )
		{
			$_payload = $this->_payload;
		}

		if ( false === ( $_authorized = Option::getBool( $_payload, 'oasys.authorized' ) ) )
		{
			$this->_config->startAuthorization();
		}

		$this->_config->completeAuthorization();
	}

	/**
	 * @param array $options
	 *
	 * @throws Exceptions\RedirectRequiredException
	 * @return $this|void
	 */
	public function authenticate( $options = array() )
	{
	}

	/**
	 * Reset the authorization adn redirect back to our redirect
	 */
	protected function _resetRedirect()
	{
		$_uri = $this->_config->get( 'redirect_uri' );
		$this->_config->resetAuthorization();
		$this->_redirect( $_uri );
	}

	/**
	 * Internally used redirect method.
	 *
	 * @param string $uri
	 *
	 * @throws Exceptions\RedirectRequiredException
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
	 * @return array
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
		return $this->_payload = array_merge( $_query, $this->_payload );
	}

	/**
	 * @param \DreamFactory\Oasys\Interfaces\OasysProviderClient $client
	 *
	 * @return KeyMaster
	 */
	protected function _setClient( $client )
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
	 * @param \DreamFactory\Oasys\GateKeeper $keeper
	 *
	 * @return KeyMaster
	 */
	protected function _setKeeper( $keeper )
	{
		$this->_keeper = $keeper;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\GateKeeper
	 */
	public function getKeeper()
	{
		return $this->_keeper;
	}

	/**
	 * @param string $providerId
	 *
	 * @return KeyMaster
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
	 * @return KeyMaster
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
	 * @param \DreamFactory\Oasys\Interfaces\OasysStorageProvider $store
	 *
	 * @return KeyMaster
	 */
	protected function _setStore( $store )
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
	 * @param boolean $interactive
	 *
	 * @return KeyMaster
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
		return $this->_store->get( $this->_providerId . '.' . $key, $defaultValue, $burnAfterReading );
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
		return $this->_store->set( $this->_providerId . '.' . $key, $value, $overwrite );
	}

	/**
	 * Convenience shortcut to the GateKeeper's goodie bag
	 *
	 * @param string $key
	 *
	 * @throws OasysException
	 * @return mixed|void
	 */
	public function remove( $key )
	{
		return $this->_store->remove( $this->_providerId . '.' . $key );
	}

	/**
	 * Convenience shortcut to the GateKeeper's goodie bag
	 *
	 * @param string $pattern preg_match-compatible pattern to match against the keys
	 *
	 * @throws OasysException
	 * @return mixed|void
	 */
	public function removeMany( $pattern )
	{
		return $this->_store->removeMany( $pattern );
	}

	/**
	 * @param \DreamFactory\Oasys\Configs\BaseProviderConfig $config
	 *
	 * @return KeyMaster
	 */
	public function setConfig( $config )
	{
		$this->_config = $config;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\Configs\BaseProviderConfig
	 */
	public function getConfig()
	{
		return $this->_config;
	}
}
