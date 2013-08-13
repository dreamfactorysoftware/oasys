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

use DreamFactory\Oasys\Exceptions\RedirectRequiredException;
use DreamFactory\Oasys\Interfaces\OasysContainer;
use DreamFactory\Oasys\Interfaces\OasysProvider;
use DreamFactory\Oasys\Interfaces\OasysProviderClient;
use DreamFactory\Oasys\Interfaces\OasysStorageProvider;
use DreamFactory\Tests\Oasys\GateKeeperTest;
use Kisma\Core\Seed;
use Kisma\Core\SeedBag;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Option;

/**
 * KeyMaster
 */
abstract class KeyMaster extends Seed implements OasysProvider
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var GateKeeper
	 */
	protected $_gatekeeper;
	/**
	 * @var OasysStorageProvider
	 */
	protected $_store;
	/**
	 * @var string
	 */
	protected $_providerId;
	/**
	 * @var OasysProviderConfig The configuration options for this provider
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
	 * @var mixed
	 */
	protected $_request;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param GateKeeper $gateKeeper
	 * @param array      $settings
	 *
	 * @throws \InvalidArgumentException
	 * @return \DreamFactory\Oasys\KeyMaster
	 */
	public function __construct( GateKeeper $gateKeeper, $settings = array() )
	{
		$this->_gatekeeper = $gateKeeper;
		$this->_store = $gateKeeper->getStore();

		if ( empty( $this->_store ) )
		{
			throw new \InvalidArgumentException( 'No storage mechanism configured.' );
		}

		if ( empty( $this->_providerId ) )
		{
			throw new \InvalidArgumentException( 'No provider specified.' );
		}

		parent::__construct( $settings );
	}

	/**
	 * Process the current request
	 *
	 * $request - The current request parameters. Leave as NULL to default to use $_REQUEST.
	 */

	/**
	 * @param array $payload If empty, request query string is used
	 */
	public function process( $payload = null )
	{
		$this->_parseResult( $payload );

		if ( empty( $this->_request ) )
		{
			if ( null !== ( $_query = Option::server( 'QUERY_STRING' ) ) && false !== strrpos( $_query, '?' ) )
			{
				$this->_parseResult( str_replace( '?', '&', $_query ) );
			}
		}

		if ( false === ( $_authorized = Option::getBool( $this->_request, 'oasys.authorized' ) ) )
		{
			if ( !$this->getConfig( 'config' ) )
			{
				header( 'HTTP/1.1 404 Not Found' );
				die( 'You didn\'t say the magic word.' );
			}

			$this->_startAuthorization();
		}

		$this->_completeAuthorization();
	}

	/**
	 * @return bool
	 */
	abstract public function authorized();

	/**
	 * @param array $options
	 *
	 * @throws Exceptions\RedirectRequiredException
	 * @return $this|void
	 */
	public function authenticate( $options = array() )
	{
		if ( $this->authorized() )
		{
			return $this;
		}

		$this->_resetAuthorization();

		$_baseUrl = $this->getConfig( 'base_url' );
		$_baseUrl .= ( false !== strpos( $_baseUrl, '?' ) ? '&' : '?' );
		$_ticket = sha1( $this->getId() . '.' . time() );
		$_startpoint = $_baseUrl . 'oasys.pid=' . $this->_providerId . '&oasys.ticket=' . $_ticket;

		$this->set( 'oasys.ticket', $_ticket );

		$_options = array_merge(
			array(
				 'oasys.redirect_uri' => Curl::currentUrl(),
				 'oasys.authorized'   => false,
				 'oasys.startpoint'   => $_startpoint,
				 'oasys.endpoint'     => $_baseUrl . 'oasys.endpoint=' . $this->_providerId,
				 'settings'           => $this->_settings,
			),
			Option::clean( $options )
		);

		//	Save options
		$this->set( $_options );

		//	Do it
		$this->_redirect( $_startpoint );
	}

	/**
	 * @return mixed
	 */
	abstract protected function _startAuthorization();

	/**
	 * @return mixed
	 */
	abstract protected function _completeAuthorization();

	/**
	 * Clear out any settings for this provider
	 *
	 * @return $this
	 */
	public function deauthorize()
	{
		//	Clear out any configurations for this provider
		$this->removeMany( '/' . constant( get_class( $this->_store ) . '::KEY_PREFIX' ) . '\\.' . $this->_providerId . '\\./' );

		return $this;
	}

	/**
	 * @param string $providerId
	 *
	 * @return $this
	 */
	protected function _resetAuthorization( $providerId = null )
	{
		$providerId = $providerId ? : $this->_providerId;
		$_settings = $this->_store->get( $providerId . '.' . 'settings', array() );

		Option::remove( $_settings, $providerId . '.oasys.redirect_uri' );
		Option::remove( $_settings, $providerId . '.oasys.endpoint' );
		Option::remove( $_settings, $providerId . '.options' );

		$this->set( 'settings', $_settings );

		return $this;
	}

	/**
	 * @param string $providerId
	 */
	protected function _resetRedirect( $providerId = null )
	{
		$providerId = $providerId ? : $this->_providerId;
		$_url = $this->_store->get( $providerId . '.oasys.redirect_uri' );

		$this->_resetAuthorization( $providerId );
		$this->_redirect( $_url );
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
	 * @param string $result
	 *
	 * @return \Kisma\Core\SeedBag
	 */
	protected function _parseResult( $result )
	{
		if ( is_string( $result ) && false !== json_decode( $result ) )
		{
			$_query = json_decode( $result );
		}
		else
		{
			parse_str( $result, $_query );
		}

		return $this->_request = new SeedBag( $_query );
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
	 * @param \DreamFactory\Oasys\GateKeeper $gatekeeper
	 *
	 * @return KeyMaster
	 */
	protected function _setGatekeeper( $gatekeeper )
	{
		$this->_gatekeeper = $gatekeeper;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\GateKeeper
	 */
	public function getGatekeeper()
	{
		return $this->_gatekeeper;
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
	 * @return mixed
	 */
	public function getRequest()
	{
		return $this->_request;
	}

	/**
	 * @param array $settings
	 *
	 * @return KeyMaster
	 */
	protected function _setSettings( $settings )
	{
		$this->_settings = $settings;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getSettings()
	{
		return $this->_settings;
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
	 * Wrapper around GateKeeper's options
	 *
	 * @param string $key
	 * @param mixed  $defaultValue
	 * @param bool   $burnAfterReading
	 *
	 * @return mixed
	 */
	public function getConfig( $key, $defaultValue = null, $burnAfterReading = false )
	{
		return $this->_gatekeeper->get( $key, $defaultValue, $burnAfterReading );
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
}
