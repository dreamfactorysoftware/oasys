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
namespace DreamFactory\Oasys\Stores;

use DreamFactory\Oasys\Interfaces\StorageProviderLike;
use Kisma\Core\Exceptions;
use Kisma\Core\Interfaces;
use Kisma\Core\SeedBag;
use Kisma\Core\Utility\Inflector;
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
		//	Sync before death
		$this->sync();

		parent::__destruct();
	}

	/**
	 * Adds the prefix and normalizes the key if a string...
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	protected function _normalizeKey( $key )
	{
		if ( !is_string( $key ) || empty( $key ) )
		{
			return $key;
		}

		return static::KEY_PREFIX . Inflector::neutralize( $key );
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	protected function _denormalizeKey( $key )
	{
		return str_ireplace( static::KEY_PREFIX, null, $key );
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
			$_contents = array();

			foreach ( $this->contents() as $_key => $_value )
			{
				$_contents[$this->_denormalizeKey( $_key )] = $_value;
			}

			return $_contents;
		}

		return parent::get( $this->_normalizeKey( $key ), $defaultValue, $burnAfterReading );
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
		return parent::set( $this->_normalizeKey( $key ), $value, $overwrite );
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function remove( $key )
	{
		return parent::remove( $this->_normalizeKey( $key ) );
	}

	/**
	 * @param string $pattern
	 *
	 * @return array|null
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

	/**
	 * Synchronize any in-memory data with the store itself
	 *
	 * @return bool True if work was done
	 */
	public function sync()
	{
		return false;
	}
}
