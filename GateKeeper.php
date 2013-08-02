<?php
namespace DreamFactory\Platform\Oasys;

use DreamFactory\Platform\Exceptions\OasysException;
use DreamFactory\Platform\Oasys\Stores\Session;
use Kisma\Core\Seed;

/**
 * GateKeeper
 */
class GateKeeper extends Seed
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var Session
	 */
	protected $_store = null;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array $settings
	 *
	 * @throws Exception
	 * @throws \InvalidArgumentException
	 * @throws
	 * @internal param array|\stdClass $options
	 */
	public function __construct( $settings = array() )
	{
		if ( is_string( $settings ) && is_file( $settings ) && is_readable( $settings ) )
		{
			$settings = file_get_contents( $settings );
		}

		if ( empty( $settings ) || !is_array( $settings ) )
		{
			throw new \InvalidArgumentException( '"$settings" must be either an array of settings or a path to an include-able file.' );
		}

		parent::__construct( $settings );

		// setup storage manager
		$this->_storage = $this->_store ? : new Session();

		//	Render any stored errors
		if ( null !== ( $_error = $this->_store->get( 'error', null, true ) ) )
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
	}

	public function authenticate( $providerId, $parameters = array() )
	{
		return $this->getClient( $providerId )->authenticate( $parameters );
	}

	/**
	 * Return the client instance for a provider
	 */
	public function getClient( $providerId = null )
	{
		return Provider::createClient( $this, $providerId );
	}

	// --------------------------------------------------------------------

	/**
	 * Return true if current user is connected with a given provider
	 */
	function isConnected( $providerId )
	{
		return $this->getClient( $providerId )->isAuthorized();
	}

	// --------------------------------------------------------------------

	/**
	 * Return a list of authenticated providers
	 */
	function getConnectedProviders()
	{
		$idps = array();

		foreach ( $this->options ['providers'] as $idpid => $params )
		{
			if ( $this->isConnectedWith( $idpid ) )
			{
				$idps[] = $idpid;
			}
		}

		return $idps;
	}

	// --------------------------------------------------------------------

	/**
	 * Return a list of enabled providers as well as a flag if you are connected.
	 */
	function getEnabledProviders()
	{
		$idps = array();

		foreach ( $this->options ['providers'] as $idpid => $params )
		{
			if ( $params['enabled'] )
			{
				$idps[$idpid] = array( 'connected' => false );

				if ( $this->isConnectedWith( $idpid ) )
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
	function logoutAllProviders()
	{
		$idps = $this->getConnectedProviders();

		foreach ( $idps as $idp )
		{
			$adapter = $this->getClient( $idp );

			$adapter->logout();
		}
	}

	/**
	 * @param \DreamFactory\Platform\Oasys\Stores\Session $storage
	 *
	 * @return GateKeeper
	 */
	public function setStorage( $storage )
	{
		$this->_storage = $storage;

		return $this;
	}

	/**
	 * @return \DreamFactory\Platform\Oasys\Stores\Session
	 */
	public function getStorage()
	{
		return $this->_storage;
	}
}
