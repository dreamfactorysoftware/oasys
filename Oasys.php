<?php
namespace DreamFactory\Platform\Oasys;

use Kisma\Core\SeedBag;
use Kisma\Core\Utility\Log;

/**
 * Oasys
 */
class Oasys extends SeedBag
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string The storage key prefix
	 */
	const KEY_PREFIX = 'oasys.';

	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var array
	 */
	protected $_providerPaths = array();

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array $settings
	 *
	 * @throws Exception
	 */
	public function __construct( $settings = array() )
	{
		parent::__construct( $settings );

		if ( empty( $this->_providerPaths ) )
		{
			$this->_providerPaths = array(
				__DIR__ . '/Providers',
			);
		}

		$this->set( 'session.id', session_id() );

//		if ( Hybrid_Error::hasError() )
//		{
//			$m = Hybrid_Error::getErrorMessage();
//			$c = Hybrid_Error::getErrorCode();
//			$p = Hybrid_Error::getErrorPrevious();
//
//			Log::error( "Oasys initialize: A stored Error found, Throw an new Exception and delete it from the store: Error#$c, '$m'" );
//
//			Hybrid_Error::clearError();
//
//			// try to provide the previous if any
//			// Exception::getPrevious (PHP 5 >= 5.3.0) http://php.net/manual/en/exception.getprevious.php
//			if ( version_compare( PHP_VERSION, '5.3.0', '>=' ) && ( $p instanceof Exception ) )
//			{
//				throw new Exception( $m, $c, $p );
//			}
//			else
//			{
//				throw new Exception( $m, $c );
//			}
//		}

		Log::info( 'Oasys system initialized.' );
	}

	/**
	 * Try to authenticate the user with a given provider.
	 *
	 * If the user is already connected we just return and instance of provider adapter,
	 * ELSE, try to authenticate and authorize the user with the provider.
	 *
	 * $options is generally an array with required info in order for this provider and HybridAuth to work,
	 *  like :
	 *          hauth_return_to: URL to call back after authentication is done
	 *        openid_identifier: The OpenID identity provider identifier
	 *           google_service: can be "Users" for Google user accounts service or "Apps" for Google hosted Apps
	 */
	public function authenticate( $provider, $options = null )
	{
		$_adapter = $this->_createProvider( $provider, $options );

		if ( !$this->get( 'session.' . $provider . '.logged_in' ) )
		{
			$_adapter->login();
		}
	}

	/**
	 * Setup an adapter for a given provider
	 */
	public function _createProvider( $provider, $options = null )
	{
		if ( empty( $options ) )
		{
			$options = $this->get( 'session.' . $provider . '.provider_options', array() );
		}

		$_returnUrl = $this->get( 'return_url', $this->getCurrentUrl() );

		$_provider = new Hybrid_Provider_Adapter();

		$provider->factory( $provider, $options );

		return $provider;
	}

	// --------------------------------------------------------------------

	/**
	 * Check if the current user is connected to a given provider
	 */
	public function isConnected( $provider )
	{
		return $this->get( 'session.' . $provider . '.logged_in' );
	}

	// --------------------------------------------------------------------

	/**
	 * Return array listing all authenticated providers
	 */
	public function getConnectedProviders()
	{
		$idps = array();

		foreach ( Oasys::$config["providers"] as $idpid => $options )
		{
			if ( Oasys::isConnectedWith( $idpid ) )
			{
				$idps[] = $idpid;
			}
		}

		return $idps;
	}

	// --------------------------------------------------------------------

	/**
	 * Return array listing all enabled providers as well as a flag if you are connected.
	 */
	public static function getProviders()
	{
		$idps = array();

		foreach ( Oasys::$config["providers"] as $idpid => $options )
		{
			if ( $options['enabled'] )
			{
				$idps[$idpid] = array( 'connected' => false );

				if ( Oasys::isConnectedWith( $idpid ) )
				{
					$idps[$idpid]['connected'] = true;
				}
			}
		}

		return $idps;
	}

	// --------------------------------------------------------------------

	/**
	 * A generic function to logout all connected provider at once
	 */
	public static function logoutAllProviders()
	{
		$idps = Oasys::getConnectedProviders();

		foreach ( $idps as $idp )
		{
			$adapter = Oasys::getAdapter( $idp );

			$adapter->logout();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Utility function, redirect to a given URL with php header or using javascript location.href
	 */
	public static function redirect( $url, $mode = "PHP" )
	{
		Log::info( "Enter Oasys::redirect( $url, $mode )" );

		if ( $mode == "PHP" )
		{
			header( "Location: $url" );
		}
		elseif ( $mode == "JS" )
		{
			echo '<html>';
			echo '<head>';
			echo '<script type="text/javascript">';
			echo 'function redirect(){ window.top.location.href="' . $url . '"; }';
			echo '</script>';
			echo '</head>';
			echo '<body onload="redirect()">';
			echo 'Redirecting, please wait...';
			echo '</body>';
			echo '</html>';
		}

		die();
	}

	// --------------------------------------------------------------------

	/**
	 * Utility function, return the current url. TRUE to get $_SERVER['REQUEST_URI'], FALSE for $_SERVER['PHP_SELF']
	 */
	public static function getCurrentUrl( $request_uri = true )
	{
		if (
			isset( $_SERVER['HTTPS'] ) && ( $_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1 )
			|| isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
		)
		{
			$protocol = 'https://';
		}
		else
		{
			$protocol = 'http://';
		}

		$url = $protocol . $_SERVER['HTTP_HOST'];

		// use port if non default
		if ( isset( $_SERVER['SERVER_PORT'] ) && strpos( $url, ':' . $_SERVER['SERVER_PORT'] ) === FALSE )
		{
			$url .= ( $protocol === 'http://' && $_SERVER['SERVER_PORT'] != 80 && !isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) )
					|| ( $protocol === 'https://' && $_SERVER['SERVER_PORT'] != 443 && !isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) )
				? ':' . $_SERVER['SERVER_PORT']
				: '';
		}

		if ( $request_uri )
		{
			$url .= $_SERVER['REQUEST_URI'];
		}
		else
		{
			$url .= $_SERVER['PHP_SELF'];
		}

		// return current url
		return $url;
	}

	/**
	 * Clean up the key and write it
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $overwrite
	 *
	 * @return SeedBag
	 */
	public function set( $key, $value = null, $overwrite = true )
	{
		return parent::set( static::KEY_PREFIX . Inflector::neutralize( $key ), $value, $overwrite );
	}

	/**
	 * @param string $key
	 * @param mixed  $defaultValue
	 * @param bool   $burnAfterReading
	 *
	 * @return mixed
	 */
	public function get( $key = null, $defaultValue = null, $burnAfterReading = false )
	{
		return parent::get( $key ? static::KEY_PREFIX . Inflector::neutralize( $key ) : null, $defaultValue, $burnAfterReading );
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function remove( $key )
	{
		return parent::remove( static::KEY_PREFIX . Inflector::neutralize( $key ) );
	}
}
