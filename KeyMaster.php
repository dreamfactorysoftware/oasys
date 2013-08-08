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
use Kisma\Core\Utility;

/**
 * KeyMaster
 */
class KeyMaster extends Seed
{
	/**
	 * @var mixed
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
	/**
	 * @var array
	 */
	protected $_tokens;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param GateKeeper $gateKeeper
	 * @param array      $settings
	 */
	public function __construct( GateKeeper $gateKeeper, $settings = array() )
	{
		$this->_gatekeeper = $gateKeeper;
		$this->_store = $gateKeeper->getStore();

		parent::__construct( $settings );

		//	Get default settings...
		if ( empty( $this->_parameters ) )
		{
			$this->_parameters = $this->_store->get( $this->_providerId . '.options' );
		}

		$this->_endpoint = $this->_endpoint ? : $this->_store->get( $this->_providerId . '.oasys_endpoint' );

		$this->initialize();
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
			$this->_store->remove( $_providerId . '.oasys_redirect_uri' );
			$this->_store->remove( $_providerId . '.oasys_endpoint' );
			$this->_store->remove( $_providerId . '.options' );
		}

		$this->disconnect();

		$_baseUrl = $this->_gatekeeper->get( 'base_url' );
		$_baseUrl .= ( strpos( $_baseUrl, '?' ) ? '&' : '?' );

		$_options = array(
			'oasys_redirect_uri' => Utility\Curl::currentUrl(),
			'oasys_startpoint'   => $_baseUrl . 'oasys.start=' . $this->_providerId . '&oasys.timestamp=' . time(),
			'oasys_endpoint'     => $_baseUrl . 'oasys.complete=' . $this->_providerId,
		);

		$_parameters = array_merge( $_options, Option::clean( $parameters ) );

		$this->_store->set( $this->_providerId . '.oasys_redirect_uri', $_parameters['oasys_redirect_uri'] );
		$this->_store->set( $this->_providerId . '.oasys_endpoint', $_parameters['oasys_endpoint'] );
		$this->_store->set( $this->_providerId . '.options', $_parameters );

		//	Store the configuration
		$this->_store->set( 'config', $this->_providerOptions );

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
		$this->_store->removeMany( '/^' . $this->_providerId . '\\./' );
	}

	/**
	 * @return array
	 */
	public function getTokens()
	{
		return
			$this->_tokens
				= $this->_store->get( $this->_providerId . '.tokens', $this->_tokens ? : array() );
	}

	public function setTokens( $tokens )
	{
		$this->_store->set( $this->_providerId . '.tokens', $this->_tokens = $tokens );
	}

	/**
	 * ...
	 */
	public function getApplicationId()
	{
		return $this->application->id;
	}

	/**
	 * Set Application Key if not Null
	 */
	public function letApplicationId( $id )
	{
		if ( $this->getApplicationId() )
		{
			return;
		}

		$this->setApplicationId( $id );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function setApplicationId( $id )
	{
		$this->application->id = $id;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public function getApplicationKey()
	{
		return $this->application->key;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Application Key if not Null
	 */
	public function letApplicationKey( $key )
	{
		if ( $this->getApplicationKey() )
		{
			return;
		}

		$this->setApplicationKey( $key );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function setApplicationKey( $key )
	{
		$this->application->key = $key;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public function getApplicationSecret()
	{
		return $this->application->secret;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function letApplicationSecret( $secret )
	{
		if ( $this->getApplicationSecret() )
		{
			return;
		}

		$this->setApplicationSecret( $secret );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function setApplicationSecret( $secret )
	{
		$this->application->secret = $secret;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public function getApplicationScope()
	{
		return $this->application->scope;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function letApplicationScope( $scope )
	{
		if ( $this->getApplicationScope() )
		{
			return;
		}

		$this->setApplicationScope( $scope );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function setApplicationScope( $scope )
	{
		$this->application->scope = $scope;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public function getEndpointBaseUri()
	{
		return $this->endpoints->baseUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function letEndpointBaseUri( $uri )
	{
		if ( $this->getEndpointBaseUri() )
		{
			return;
		}

		$this->setEndpointBaseUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function setEndpointBaseUri( $uri )
	{
		$this->endpoints->baseUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public function getEndpointRedirectUri()
	{
		return $this->endpoints->redirectUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function letEndpointRedirectUri( $uri )
	{
		if ( $this->getEndpointRedirectUri() )
		{
			return;
		}

		$this->setEndpointRedirectUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function setEndpointRedirectUri( $uri )
	{
		$this->endpoints->redirectUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public function getEndpointAuthorizeUri()
	{
		return $this->endpoints->authorizeUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function letEndpointAuthorizeUri( $uri )
	{
		if ( $this->getEndpointAuthorizeUri() )
		{
			return;
		}

		$this->setEndpointAuthorizeUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function setEndpointAuthorizeUri( $uri )
	{
		$this->endpoints->authorizeUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public function getEndpointRequestTokenUri()
	{
		return $this->endpoints->requestTokenUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function letEndpointRequestTokenUri( $uri )
	{
		if ( $this->getEndpointRequestTokenUri() )
		{
			return;
		}

		$this->setEndpointRequestTokenUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function setEndpointRequestTokenUri( $uri )
	{
		$this->endpoints->requestTokenUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public function getEndpointAccessTokenUri()
	{
		return $this->endpoints->accessTokenUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function letEndpointAccessTokenUri( $uri )
	{
		if ( $this->getEndpointAccessTokenUri() )
		{
			return;
		}

		$this->setEndpointAccessTokenUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function setEndpointAccessTokenUri( $uri )
	{
		$this->endpoints->accessTokenUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public function getEndpointTokenInfoUri()
	{
		return $this->endpoints->tokenInfoUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function letEndpointTokenInfoUri( $uri )
	{
		if ( $this->getEndpointTokenInfoUri() )
		{
			return;
		}

		$this->setEndpointTokenInfoUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function setEndpointTokenInfoUri( $uri )
	{
		$this->endpoints->tokenInfoUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public function getEndpointAuthorizeUriAdditionalParameters()
	{
		return $this->endpoints->authorizeUriParameters;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function setEndpointAuthorizeUriAdditionalParameters( $parameters = array() )
	{
		$this->endpoints->authorizeUriParameters = $parameters;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public function letEndpointAuthorizeUriAdditionalParameters( $parameters = array() )
	{
		if ( $this->getEndpointAuthorizeUriAdditionalParameters() )
		{
			return;
		}

		$this->setEndpointAuthorizeUriAdditionalParameters( $parameters );
	}

	// ====================================================================

	/**
	 * ...
	 */
	function getOpenidIdentifier()
	{
		return $this->openidIdentifier;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	function letOpenidIdentifier( $openidIdentifier )
	{
		if ( $this->getOpenidIdentifier() )
		{
			return;
		}

		$this->setOpenidIdentifier( $openidIdentifier );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	function setOpenidIdentifier( $openidIdentifier )
	{
		$this->openidIdentifier = $openidIdentifier;
	}

	// ====================================================================

	/**
	 * ...
	 */
	protected function getAdapterConfig( $key = null, $subkey = null )
	{
		if ( !$key )
		{
			return $this->config;
		}

		if ( !$subkey && isset( $this->config[$key] ) )
		{
			return $this->config[$key];
		}

		if ( isset( $this->config[$key] ) && isset( $this->config[$key][$subkey] ) )
		{
			return $this->config[$key][$subkey];
		}

		return null;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	protected function setAdapterConfig( $config = array() )
	{
		$this->config = $config;
	}

	// ====================================================================

	/**
	 * ...
	 */
	protected function getHybridauthConfig( $key = null, $subkey = null )
	{
		if ( !$key )
		{
			return $this->hybridauthConfig;
		}

		if ( !$subkey && isset( $this->hybridauthConfig[$key] ) )
		{
			return $this->hybridauthConfig[$key];
		}

		if ( isset( $this->hybridauthConfig[$key] ) && isset( $this->config[$key][$subkey] ) )
		{
			return $this->hybridauthConfig[$key][$subkey];
		}

		return null;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	protected function setHybridauthConfig( $config = array() )
	{
		$this->hybridauthConfig = $config;
	}

	// ====================================================================

	/**
	 * ...
	 */
	protected function getAdapterParameters( $key = null )
	{
		if ( !$key )
		{
			return $this->parameters;
		}

		if ( isset( $this->parameters[$key] ) )
		{
			return $this->parameters[$key];
		}

		return null;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	protected function setAdapterParameters( $parameters = array() )
	{
		$this->parameters = $parameters;
	}

	// ====================================================================

	/**
	 * ...
	 */
	protected function parseRequestResult( $result, $parser = 'json_decode' )
	{
		if ( json_decode( $result ) )
		{
			return json_decode( $result );
		}

		parse_str( $result, $ouput );

		$result = new \StdClass();

		foreach ( $ouput as $k => $v )
		{
			$result->$k = $v;
		}

		return $result;
	}

	// --------------------------------------------------------------------

	/**
	 * Shamelessly Borrowered from Slimframework, but to be removed/moved
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

		return sprintf( "<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:38px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>",
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
			if ( strrpos( $_SERVER["QUERY_STRING"],
						  '
									? '
			)
			)
			{
				$_SERVER["QUERY_STRING"] = str_replace( "?", "&", $_SERVER["QUERY_STRING"] );

				parse_str( $_SERVER["QUERY_STRING"], $_REQUEST );
			}

			$this->request = $_REQUEST;
		}

		if ( isset( $this->request["hauth_start"] ) )
		{
			$this->processAdapterLoginBegin();
		}

		elseif ( isset( $this->request["hauth_done"] ) )
		{
			$this->processAdapterLoginFinish();
		}
	}

	// --------------------------------------------------------------------

	function processAdapterLoginBegin()
	{
		$this->_authInit();

		$provider_id = trim( strip_tags( $this->request["hauth_start"] ) );

		$adapterFactory = new AdapterFactory( $this->_store->config( "CONFIG" ), $this->_store );

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
			$this->_store->set( "error.status", 1 );
			$this->_store->set( "error.message", $e->getMessage() );
			$this->_store->set( "error.code", $e->getCode() );
			$this->_store->set( "error.exception", $e );

			$this->_returnToCallbackUrl( $provider_id );
		}
	}

	// --------------------------------------------------------------------

	function processAdapterLoginFinish()
	{
		$this->_authInit();

		$provider_id = trim( strip_tags( $this->request["hauth_done"] ) );

		$adapterFactory = new AdapterFactory( $this->_store->config( "CONFIG" ), $this->_store );

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
			$this->_store->set( "error.status", 1 );
			$this->_store->set( "error.message", $e->getMessage() );
			$this->_store->set( "error.code", $e->getCode() );
			$this->_store->set( "error.exception", $e );
		}

		$this->_returnToCallbackUrl( $provider_id );
	}

	// --------------------------------------------------------------------

	/**
	 * Checks if enpoint accessed directly?
	 */
	private function _authInit()
	{
		if ( !$this->_store->config( "CONFIG" ) )
		{
			header( "HTTP/1.0 404 Not Found" );

			die( "You cannot access this page directly." );
		}
	}

	// --------------------------------------------------------------------

	/**
	 * redirect the user to oasys_redirect_uri (the callback url)
	 */
	private function _returnToCallbackUrl( $providerId )
	{
		$callback_url = $this->_store->get( "{$providerId}.oasys_redirect_uri" );

		$this->_store->delete( "{$providerId}.oasys_redirect_uri" );
		$this->_store->delete( "{$providerId}.oasys_endpoint" );
		$this->_store->delete( "{$providerId}.options" );

		Util::redirect( $callback_url );
	}

}
