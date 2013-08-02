<?php
namespace DreamFactory\Platform\Oasys\Stores;

use Kisma\Core\Exceptions;
use Kisma\Core\Interfaces;
use Kisma\Core\SeedBag;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility;

/**
 * Session
 * Session store for auth data
 */
class Session extends SeedBag
{
	//*************************************************************************
	//* Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const KEY_PREFIX = 'oasys.';

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param string|array $key
	 * @param mixed        $value
	 * @param mixed        $defaultValue
	 *
	 * @return mixed|null
	 */
	public function config( $key, $value = null, $defaultValue = null )
	{
		$key = Inflector::neutralize( $key );
		$_config = $this->get( 'config', array() );

		if ( null !== $value || is_array( $key ) )
		{
			Option::set( $_config, $key, $value );
			$this->set( 'config', $_config );

			return;
		}

		return Option::get( $_config, $key, $defaultValue );
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
		return parent::get( static::KEY_PREFIX . $key, $defaultValue, $burnAfterReading );
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $overwrite
	 *
	 * @return SeedBag
	 */
	public function set( $key, $value = null, $overwrite = true )
	{
		return parent::set( static::KEY_PREFIX . $key, $value, $overwrite );
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function remove( $key )
	{
		return parent::remove( static::KEY_PREFIX . $key );
	}

	/**
	 * @param string $keyPart
	 */
	function removeMany( $keyPart )
	{
		foreach ( $this as $_key => $_value )
		{
			if ( false !== stripos( $_key, static::KEY_PREFIX . $keyPart ) )
			{
				$this->remove( $_key );
			}
		}
	}
}
