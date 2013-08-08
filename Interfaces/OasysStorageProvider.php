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
namespace DreamFactory\Oasys\Interfaces;

/**
 * OasysStorageProvider
 */
interface OasysStorageProvider
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
	 * @param mixed        $defaultValue
	 * @param bool         $burnAfterReading
	 *
	 * @return mixed
	 */
	public function get( $key = null, $defaultValue = null, $burnAfterReading = false );

	/**
	 * @param string|array $key
	 * @param mixed        $value
	 * @param bool         $overwrite
	 *
	 * @return void|mixed
	 */
	public function set( $key, $value = null, $overwrite = true );

	/**
	 * @param string|array $key
	 *
	 * @return bool
	 */
	public function remove( $key );

	/**
	 * @param string $pattern The preg pattern to match on the key(s) to remove
	 *
	 * @return mixed|void
	 */
	public function removeMany( $pattern );
}