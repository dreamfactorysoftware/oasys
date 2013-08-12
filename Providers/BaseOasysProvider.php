<?php
namespace DreamFactory\Platform\Oasys\Providers;

use DreamFactory\Oasys\GateKeeper;
use DreamFactory\Oasys\Interfaces\OasysProviderClient;
use DreamFactory\Oasys\Interfaces\OasysStorageProvider;
use Kisma\Core\SeedBag;

/**
 * BaseOasysProvider
 *
 * @package DreamFactory\Platform\Oasys\Providers
 */
abstract class BaseOasysProvider extends SeedBag
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string
	 */
	protected $_providerId;
	/**
	 * @var GateKeeper
	 */
	protected $_gatekeeper;
	/**
	 * @var OasysStorageProvider
	 */
	protected $_store;
	/**
	 * @var OasysProviderClient
	 */
	protected $_client;
	/**
	 * @var array
	 */
	protected $_settings;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param GateKeeper $gatekeeper
	 * @param array      $settings
	 */
	public function __construct( $gatekeeper, $settings = array() )
	{
		$this->_setGateKeeper( $gatekeeper );

		$this->_settings = $this->_store->get( $this->_providerId . '.options' );

		parent::__construct( $settings );

		$this->_endpoint = $this->_endpoint ? : $this->_gatekeeper->getStorage()->get( $this->_providerId . '.oasys_endpoint' );

		$this->configure();
	}

	/**
	 * @param \DreamFactory\Oasys\GateKeeper $gatekeeper
	 *
	 * @return BaseOasysProvider
	 */
	protected function _setGatekeeper( $gatekeeper )
	{
		$this->_gatekeeper = $gatekeeper;
		$this->_store = $gatekeeper->getStore();

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
	 * @param array $settings
	 *
	 * @return void
	 */
	abstract public function configure( $settings = array() );

	/**
	 * ...
	 */
	public function authenticate( $parameters = array() )
	{
		if ( $this->isAuthorized() )
		{
			return $this;
		}

		foreach ( $this->_gatekeeper->getStorage()->config( 'providers' ) as $_providerId => $_options )
		{
			$this->_gatekeeper->getStorage()->remove( "{$idpid}.oasys_redirect_uri" );
			$this->_gatekeeper->getStorage()->remove( "{$idpid}.oasys_endpoint" );
			$this->_gatekeeper->getStorage()->remove( "{$idpid}.options" );
		}

		$this->_gatekeeper->getStorage()->removeMany( "{$this->providerId}." );

		$base_url = $this->getHybridauthConfig( 'base_url' );

		$defaults = array(
			'oasys_redirect_uri' => Util::getCurrentUrl(),
			'oasys_endpoint'     => $base_url . ( strpos( $base_url, '?' ) ? '&' : '?' ) . "oasys.complete={$this->providerId}",
			'hauth_start_url'    => $base_url . ( strpos( $base_url, '?' ) ? '&' : '?' ) . "oasys.start={$this->providerId}&oasys.time=" . time(),
		);

		$parameters = array_merge( $defaults, (array)$parameters );

		$this->_gatekeeper->getStorage()->set( $this->providerId . ".oasys_redirect_uri", $parameters["oasys_redirect_uri"] );
		$this->_gatekeeper->getStorage()->set( $this->providerId . ".oasys_endpoint", $parameters["oasys_endpoint"] );
		$this->_gatekeeper->getStorage()->set( $this->providerId . ".options", $parameters );

		// store config
		$this->_gatekeeper->getStorage()->config( "CONFIG", $this->getHybridauthConfig() );

		// redirect user to start url
		Util::redirect( $parameters["hauth_start_url"] );
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	function isAuthorized()
	{
		return false;
	}

	// --------------------------------------------------------------------

	/**
	 * Erase adapter stored data
	 */
	function disconnect()
	{
		$this->_gatekeeper->getStorage()->removeMany( "{$this->providerId}." );
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getHybridauthEndpointUri()
	{
		return $this->hybridauthEndpoint;
	}

	/**
	 * ...
	 */
	public final function setHybridauthEndpointUri( $uri )
	{
		$this->hybridauthEndpoint = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getTokens()
	{
		return $this->_gatekeeper->getStorage()->get( $this->providerId . '.tokens' ) ? $this->_gatekeeper->getStorage()->get( $this->providerId . '.tokens' ) : $this->tokens;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public final function storeTokens( $tokens )
	{
		$this->tokens = $tokens;

		$this->_gatekeeper->getStorage()->set( $this->providerId . '.tokens', $this->tokens );
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getApplicationId()
	{
		return $this->application->id;
	}

	/**
	 * Set Application Key if not Null
	 */
	public final function letApplicationId( $id )
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
	public final function setApplicationId( $id )
	{
		$this->application->id = $id;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getApplicationKey()
	{
		return $this->application->key;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Application Key if not Null
	 */
	public final function letApplicationKey( $key )
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
	public final function setApplicationKey( $key )
	{
		$this->application->key = $key;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getApplicationSecret()
	{
		return $this->application->secret;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public final function letApplicationSecret( $secret )
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
	public final function setApplicationSecret( $secret )
	{
		$this->application->secret = $secret;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getApplicationScope()
	{
		return $this->application->scope;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public final function letApplicationScope( $scope )
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
	public final function setApplicationScope( $scope )
	{
		$this->application->scope = $scope;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getEndpointBaseUri()
	{
		return $this->endpoints->baseUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public final function letEndpointBaseUri( $uri )
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
	public final function setEndpointBaseUri( $uri )
	{
		$this->endpoints->baseUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getEndpointRedirectUri()
	{
		return $this->endpoints->redirectUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public final function letEndpointRedirectUri( $uri )
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
	public final function setEndpointRedirectUri( $uri )
	{
		$this->endpoints->redirectUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getEndpointAuthorizeUri()
	{
		return $this->endpoints->authorizeUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public final function letEndpointAuthorizeUri( $uri )
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
	public final function setEndpointAuthorizeUri( $uri )
	{
		$this->endpoints->authorizeUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getEndpointRequestTokenUri()
	{
		return $this->endpoints->requestTokenUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public final function letEndpointRequestTokenUri( $uri )
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
	public final function setEndpointRequestTokenUri( $uri )
	{
		$this->endpoints->requestTokenUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getEndpointAccessTokenUri()
	{
		return $this->endpoints->accessTokenUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public final function letEndpointAccessTokenUri( $uri )
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
	public final function setEndpointAccessTokenUri( $uri )
	{
		$this->endpoints->accessTokenUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getEndpointTokenInfoUri()
	{
		return $this->endpoints->tokenInfoUri;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public final function letEndpointTokenInfoUri( $uri )
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
	public final function setEndpointTokenInfoUri( $uri )
	{
		$this->endpoints->tokenInfoUri = $uri;
	}

	// ====================================================================

	/**
	 * ...
	 */
	public final function getEndpointAuthorizeUriAdditionalParameters()
	{
		return $this->endpoints->authorizeUriParameters;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public final function setEndpointAuthorizeUriAdditionalParameters( $parameters = array() )
	{
		$this->endpoints->authorizeUriParameters = $parameters;
	}

	// --------------------------------------------------------------------

	/**
	 * ...
	 */
	public final function letEndpointAuthorizeUriAdditionalParameters( $parameters = array() )
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

		$html = sprintf( '<h1>%s</h1>', $title );
		$html .= sprintf( '<pre>%s</pre>', print_r( $this, 1 ) );
		$html .= '<h2>Session</h2>';
		$html .= sprintf( '<pre>%s</pre>', print_r( $_SESSION, 1 ) );
		$html .= '<h2>Backtrace</h2>';
		$html .= sprintf( '<pre>%s</pre>', print_r( debug_backtrace(), 1 ) );

		return sprintf(
			"<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:38px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>",
			$title,
			$html
		);
	}
}
