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

use DreamFactory\Oasys\Exceptions\OasysException;
use Kisma\Core\Exceptions;
use Kisma\Core\Interfaces;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Storage;

/**
 * Session
 * Session store for auth data
 */
class Session extends BaseOasysStore
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param array $contents
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\OasysException
	 */
	public function __construct( $contents = array() )
	{
		if ( !isset( $_SESSION ) || PHP_SESSION_DISABLED == session_status() )
		{
			throw new OasysException( 'No session active. Session storage not available.' );
		}

		$_data = array();

		if ( null !== ( $_parcel = Option::get( $_SESSION, static::KEY_PREFIX . '.data' ) ) )
		{
			$_data = Storage::defrost( $_parcel );
		}

		if ( is_array( $_data ) )
		{
			$_data = array_merge( $_data, $contents );
		}

		parent::__construct( $_data );
	}

	/**
	 * @return bool
	 * @throws \DreamFactory\Oasys\Exceptions\OasysException
	 */
	public function sync()
	{
		if ( !isset( $_SESSION ) || PHP_SESSION_DISABLED == session_status() )
		{
			throw new OasysException( 'No session active. Session storage not available.' );
		}

		$_settings = $this->contents();

		if ( !empty( $_settings ) )
		{
			$_SESSION[static::KEY_PREFIX . '.data'] = Storage::freeze( $_settings );
		}

		return true;
	}

	/**
	 * Revoke stored token
	 *
	 * @param bool $delete If true (default), row is deleted from storage
	 *
	 * @return bool
	 */
	public function revoke( $delete = true )
	{
		if ( !isset( $_SESSION ) || PHP_SESSION_DISABLED == session_status() )
		{
			return true;
		}

		if ( isset( $_SESSION[static::KEY_PREFIX . '.data'] ) )
		{
			unset( $_SESSION[static::KEY_PREFIX . '.data'] );

			return true;
		}

		return false;
	}
}
