<?php
/**
 * This file is part of the DreamFactory Oasys (Open Authentication SYStem)
 *
 * DreamFactory Oasys (Open Authentication SYStem) <https://www.dreamfactory.com/>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
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
namespace DreamFactory\Oasys\Components;

use Kisma\Core\Seed;

/**
 * ForceContainer
 * A generic container for returned query data from Salesforce
 *
 * @property int    $totalSize
 * @property bool   $done
 * @property string $nextRecordsUrl
 * @property array  $records
 */
class ForceContainer extends Seed
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var int
	 */
	protected $_totalSize;
	/**
	 * @var bool
	 */
	protected $_done = false;
	/**
	 * @var string
	 */
	protected $_nextRecordsUrl = null;
	/**
	 * @var array
	 */
	protected $_records = null;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param boolean $done
	 *
	 * @return ForceContainer
	 */
	public function setDone( $done )
	{
		$this->_done = !empty( $done );

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getDone()
	{
		return $this->_done;
	}

	/**
	 * @param string $nextRecordsUrl
	 *
	 * @return ForceContainer
	 */
	public function setNextRecordsUrl( $nextRecordsUrl )
	{
		$this->_nextRecordsUrl = $nextRecordsUrl;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getNextRecordsUrl()
	{
		return $this->_nextRecordsUrl;
	}

	/**
	 * @param array $records
	 *
	 * @return ForceContainer
	 */
	public function setRecords( $records )
	{
		$this->_records = $records;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getRecords()
	{
		return $this->_records;
	}

	/**
	 * @param int $totalSize
	 *
	 * @return ForceContainer
	 */
	public function setTotalSize( $totalSize )
	{
		$this->_totalSize = $totalSize;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getTotalSize()
	{
		return $this->_totalSize;
	}
}
