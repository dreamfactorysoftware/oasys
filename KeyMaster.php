<?php
namespace DreamFactory\Platform\Oasys;

class KeyMaster extends GateKeeper
{
	protected $request = null;
	protected $storage = null;

	// --------------------------------------------------------------------

	function __construct( StorageInterface $storage = null )
	{
		$this->storage = $storage ? $storage : new Session();
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

		$adapterFactory = new AdapterFactory( $this->storage->config( "CONFIG" ), $this->storage );

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
			$this->storage->set( "error.status", 1 );
			$this->storage->set( "error.message", $e->getMessage() );
			$this->storage->set( "error.code", $e->getCode() );
			$this->storage->set( "error.exception", $e );

			$this->_returnToCallbackUrl( $provider_id );
		}
	}

	// --------------------------------------------------------------------

	function processAdapterLoginFinish()
	{
		$this->_authInit();

		$provider_id = trim( strip_tags( $this->request["hauth_done"] ) );

		$adapterFactory = new AdapterFactory( $this->storage->config( "CONFIG" ), $this->storage );

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
			$this->storage->set( "error.status", 1 );
			$this->storage->set( "error.message", $e->getMessage() );
			$this->storage->set( "error.code", $e->getCode() );
			$this->storage->set( "error.exception", $e );
		}

		$this->_returnToCallbackUrl( $provider_id );
	}

	// --------------------------------------------------------------------

	/**
	 * Checks if enpoint accessed directly?
	 */
	private function _authInit()
	{
		if ( !$this->storage->config( "CONFIG" ) )
		{
			header( "HTTP/1.0 404 Not Found" );

			die( "You cannot access this page directly." );
		}
	}

	// --------------------------------------------------------------------

	/**
	 * redirect the user to hauth_return_to (the callback url)
	 */
	private function _returnToCallbackUrl( $providerId )
	{
		$callback_url = $this->storage->get( "{$providerId}.hauth_return_to" );

		$this->storage->delete( "{$providerId}.hauth_return_to" );
		$this->storage->delete( "{$providerId}.hauth_endpoint" );
		$this->storage->delete( "{$providerId}.options" );

		Util::redirect( $callback_url );
	}
}
