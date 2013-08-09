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
use Kisma\Core\Seed;
use Kisma\Core\SeedBag;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Option;

/**
 * KeyMaster
 */
abstract class KeyMaster extends Seed
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var SeedBag
	 */
	protected $_request;
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
	 * @var array
	 */
	protected $_providerOptions;
	/**
	 * @var array
	 */
	protected $_parameters;
	/**
	 * @var string
	 */
	protected $_endpoint;


	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param GateKeeper $gateKeeper
	 * @param array      $settings
	 *
	 * @throws OasysException
	 */
	public function __construct( GateKeeper $gateKeeper, $settings = array() )
	{
		$this->_gatekeeper = $gateKeeper;
		$this->_store = $gateKeeper->getStore();

		if ( empty( $this->_store ) )
		{
			throw new OasysException( 'No storage mechanism configured.' );
		}

		if ( empty( $this->_providerId ) )
		{
			throw new OasysException( 'No provider specified.' );
		}

		$_provider = $this->_gatekeeper->getProvider( $this->_providerId );


		$_provider->

		$this->set( $settings );

		parent::__construct( $settings );

		//	Get default settings...
		if ( empty( $this->_parameters ) )
		{
			$this->_parameters = $this->get( 'options' );
		}

		$this->_endpoint = $this->_endpoint ? : $this->get( 'oasys_endpoint' );

		$this->initialize();
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

	/**
	 * @param array $parameters
	 *
	 * @return $this|void
	 */
	public function authenticate( $parameters = array() )
	{
		if ( $this->authorized() )
		{
			return $this;
		}

		foreach ( $this->_gatekeeper->getProviders() as $_providerId => $_options )
		{
			$this->_clearProvider( $_providerId );
		}

		$this->disconnect();

		$_baseUrl = $this->getConfig( 'base_url' );
		$_baseUrl .= ( strpos( $_baseUrl, '?' ) ? '&' : '?' );

		$_options = array(
			'oasys_redirect_uri' => Curl::currentUrl(),
			'oasys_startpoint'   => $_baseUrl . 'oasys.start=' . $this->_providerId . '&oasys.timestamp=' . time(),
			'oasys_endpoint'     => $_baseUrl . 'oasys.complete=' . $this->_providerId,
		);

		$_parameters = array_merge( $_options, Option::clean( $parameters ) );

		$this->set( 'oasys_redirect_uri', $_parameters['oasys_redirect_uri'] );
		$this->set( 'oasys_endpoint', $_parameters['oasys_endpoint'] );
		$this->set( 'options', $_parameters );

		//	Store the configuration
		$this->getConfig( ' 'config', $this->_providerOptions );

		// redirect user to start url
		header( 'Location: ' . $_parameters['oasys_startpoint'] );

		//	And... we're spent
		die();
	}

	/**
	 * @return bool
	 */
	public function authorized()
	{
		return false;
	}

	/**
	 * Clear out this connection
	 */
	public function disconnect()
	{
		$this->removeMany( '/^' . $this->_providerId . '\\./' );
	}

	/**
	 * @param string $result
	 *
	 * @return \Kisma\Core\SeedBag
	 */
	protected function parseRequestResult( $result )
	{
		if ( is_string( $result ) && false !== json_decode( $result ) )
		{
			return json_decode( $result );
		}

		parse_str( $result, $_query );

		return $this->_request = new SeedBag( $_query );
	}

	/**
	 * @return string
	 */
	function debug()
	{
		$title = 'Hybridauth Adapter Debug';

		$html = sprintf( ' < h1>%s </h1 > ', $title );
		$html .= sprintf( '<pre >%s </pre > ', print_r( $this, 1 ) );
		$html .= '<h2 > Session</h2 > ';
		$html .= sprintf( '<pre >%s </pre > ', print_r( $_SESSION, 1 ) );
		$html .= '<h2 > Backtrace</h2 > ';
		$html .= sprintf( '<pre >%s </pre > ', print_r( debug_backtrace(), 1 ) );

		return sprintf(
			"<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:38px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>",
			$title,
			$html
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Process the current request
	 *
	 * $request - The current request parameters. Leave as NULL to default to use $_REQUEST.
	 */
	function process( $request = null )
	{
		$this->request = $request;

		if ( is_null( $this->request ) )
		{
			if ( strrpos( $_SERVER["QUERY_STRING"], '?' ) )
			{
				$_SERVER["QUERY_STRING"] = str_replace( "?", "&", $_SERVER["QUERY_STRING"] );

				parse_str( $_SERVER["QUERY_STRING"], $_REQUEST );
			}

			$this->request = $_REQUEST;
		}

		if ( isset( $this->request["oasys_start"] ) )
		{
			$this->processAdapterLoginBegin();
		}

		elseif ( isset( $this->request["oasys_done"] ) )
		{
			$this->processAdapterLoginFinish();
		}
	}

	// --------------------------------------------------------------------

	function processAdapterLoginBegin()
	{
		$this->_authInit();

		$provider_id = trim( strip_tags( $this->request["oasys_start"] ) );

		$adapterFactory = new AdapterFactory( $this->config( "CONFIG" ), $this->_store );

		$adapter = $adapterFactory->setup( $provider_id );

		if ( !$adapter )
		{
			header( "HTTP/1.0 404 Not Found" );

			die( "Invalid parameter! Please return to the login page and try again." );
		}

		try
		{
			$adapter->loginBegin();
		}
		catch ( Exception $e )
		{
			$this->set( "error.status", 1 );
			$this->set( "error.message", $e->getMessage() );
			$this->set( "error.code", $e->getCode() );
			$this->set( "error.exception", $e );

			$this->_returnToCallbackUrl( $provider_id );
		}
	}

	// --------------------------------------------------------------------

	function processAdapterLoginFinish()
	{
		$this->_authInit();

		$provider_id = trim( strip_tags( $this->request["oasys_done"] ) );

		$adapterFactory = new AdapterFactory( $this->get( 'config' ), $this->_store );

		$adapter = $adapterFactory->setup( $provider_id );

		if ( !$adapter )
		{
			header( "HTTP/1.0 404 Not Found" );

			die( "Invalid parameter! Please return to the login page and try again." );
		}

		try
		{
			$adapter->loginFinish();
		}
		catch ( Exception $e )
		{
			$this->set( "error.status", 1 );
			$this->set( "error.message", $e->getMessage() );
			$this->set( "error.code", $e->getCode() );
			$this->set( "error.exception", $e );
		}

		$this->_returnToCallbackUrl( $provider_id );
	}

	/**
	 * @param string $providerId
	 */
	protected function _clearProvider( $providerId )
	{
		$this->remove( $providerId . '.oasys_redirect_uri' );
		$this->remove( $providerId . '.oasys_endpoint' );
		$this->remove( $providerId . '.options' );
	}

	/**
	 * @return void
	 */
	protected function _authInit()
	{
		if ( !$this->get( 'config' ) )
		{
			header( 'HTTP/1.1 404 Not Found' );
			die( 'You didn\'t say the magic word.' );
		}
	}

	/**
	 * @param string $providerId
	 */
	protected function _returnToCallbackUrl( $providerId )
	{
		$_url = $this->get( $providerId . '.oasys_redirect_uri' );
		$this->_clearProvider( $providerId );

		//	Redirect
		header( 'Location: ' . $_url );

		//	And... we're spent
		die();
	}

	/**
	 * @param string|null $key
	 *
	 * @return array|mixed
	 */
	protected function _getParameters( $key = null )
	{
		if ( null === $key )
		{
			return $this->_parameters;
		}

		return Option::get( $this->_parameters, $key );
	}

	/**
	 * @param array $parameters
	 *
	 * @return $this
	 */
	protected function _setParameters( $parameters = array() )
	{
		$this->_parameters = $parameters;

		return $this;
	}
}
