<?php
/**
 * This file is part of the DreamFactory Oasys (Open Authentication SYStem)
 *
 * DreamFactory Oasys (Open Authentication SYStem) <http://dreamfactorysoftware.github.io>
 * Copyright 2014 DreamFactory Software, Inc. <support@dreamfactory.com>
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
namespace DreamFactory\Oasys\Stores;

use DreamFactory\Oasys\Interfaces\StorageProviderLike;
use DreamFactory\Oasys\Oasys;
use Kisma\Core\Exceptions;
use Kisma\Core\Interfaces;
use Kisma\Core\SeedBag;
use Kisma\Core\Utility;

/**
 * BaseOasysStore
 * A base class for storing Oasys data
 */
abstract class BaseOasysStore extends SeedBag implements StorageProviderLike
{
	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * Save off the data to the file system
	 */
	public function __destruct()
	{
		//	Sync or swim
		Oasys::sync();

		$this->sync();

		parent::__destruct();
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
		//	Return all if null
		if ( empty( $key ) )
		{
			return $this->contents();
		}

		return parent::get( $key, $defaultValue, $burnAfterReading );
	}

	/**
	 * Merge data into this bag....
	 *
	 * @param array|\Traversable $data
	 * @param bool               $overwrite
	 * @param bool               $force If true, $data overwrites unconditionally
	 *
	 * @return $this|array|\Kisma\Core\SeedBag
	 */
	public function merge( $data = array(), $overwrite = true, $force = false )
	{
		if ( empty( $data ) )
		{
			$data = array();
		}

		foreach ( $data as $_key => $_value )
		{
			$_local = static::get( $_key );

			$_nullLocal = empty( $_local ) && !empty( $_value );
			$_notNullLocal = !empty( $_local ) && !empty( $_value );
			$_unequal = is_scalar( $_value ) ? $_value != $_local : sizeof( $_value ) != sizeof( $_local );

			if ( true === $force || $_nullLocal || ( $_notNullLocal && $_unequal ) )
			{
				static::set( $_key, $_value, false !== $force ? true : $overwrite );
			}
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $overwrite
	 *
	 * @return \Kisma\Core\SeedBag|BaseOasysStore
	 */
	public function set( $key, $value = null, $overwrite = true )
	{
		return parent::set( $key, $value, $overwrite );
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function remove( $key )
	{
		return parent::remove( $key );
	}

	/**
	 * @param string $pattern
	 *
	 * @return array|null An array of the removed keys and their values
	 */
	public function removeMany( $pattern )
	{
		$_removed = null;

		foreach ( $this as $_key => $_value )
		{
			if ( 0 != preg_match( $pattern, $_key ) )
			{
				$_removed[$_key] = $_value;
				$this->remove( $_key );
			}
		}

		return $_removed;
	}
}
