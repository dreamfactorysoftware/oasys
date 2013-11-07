<?php
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
