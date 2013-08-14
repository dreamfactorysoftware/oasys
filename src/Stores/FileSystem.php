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

use Kisma\Core\Exceptions;
use Kisma\Core\Interfaces;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility;

/**
 * FileSystem
 * Local file system store for auth data
 */
class FileSystem extends BaseOasysStore
{
	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var string The path to store data. Defaults to /tmp
	 */
	protected $_storagePath;
	/**
	 * @var string The ID of the data being stored
	 */
	protected $_storageId;
	/**
	 * @var string The file name used to store/retrieve data
	 */
	protected $_fileName;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param string $storageId
	 * @param string $storagePath
	 * @param array  $contents
	 *
	 * @return \DreamFactory\Oasys\Stores\FileSystem
	 */
	public function __construct( $storageId, $storagePath = null, $contents = array() )
	{
		$this->_storageId = $storageId;
		$this->_storagePath = $storagePath ? : Option::get( $contents, 'storage_path', rtrim( sys_get_temp_dir(), DIRECTORY_SEPARATOR ), true );
		$this->_fileName = Option::get( $contents, 'file_name', static::KEY_PREFIX . sha1( static::KEY_PREFIX . $storageId ), true );

		parent::__construct( $contents );

		$this->_load();
	}

	/**
	 * @return bool
	 */
	public function sync()
	{
		return $this->_save();
	}

	/**
	 * Saves off any data to the file system
	 */
	protected function _save()
	{
		$_file = $this->_storagePath . DIRECTORY_SEPARATOR . $this->_fileName;

		if ( false === file_put_contents( $_file, Utility\Storage::freeze( $this->contents() ) ) )
		{
			Utility\Log::error( 'Unable to store Oasys data in "' . $_file . '". System error.' );

			return false;
		}

		return true;
	}

	/**
	 * Loads any stored data for this ID
	 *
	 * @return bool
	 */
	protected function _load()
	{
		$_file = $this->_storagePath . DIRECTORY_SEPARATOR . $this->_fileName;

		if ( is_file( $_file ) && file_exists( $_file ) && is_readable( $_file ) )
		{
			if ( false !== ( $_data = Utility\Storage::defrost( file_get_contents( $_file ) ) ) )
			{
				//	If it wasn't frozen, a JSON string may be returned
				if ( is_string( $_data ) && false !== json_decode( $_data ) )
				{
					$_data = json_decode( $_data );
				}

				$this->merge( $_data );

				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $storagePath
	 *
	 * @return FileSystem
	 */
	public function setStoragePath( $storagePath )
	{
		$this->_storagePath = $storagePath;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getStoragePath()
	{
		return $this->_storagePath;
	}

	/**
	 * @param string $fileName
	 *
	 * @return FileSystem
	 */
	public function setFileName( $fileName )
	{
		$this->_fileName = $fileName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFileName()
	{
		return $this->_fileName;
	}

	/**
	 * @param string $storageId
	 *
	 * @return FileSystem
	 */
	public function setStorageId( $storageId )
	{
		$this->_storageId = $storageId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getStorageId()
	{
		return $this->_storageId;
	}

}
