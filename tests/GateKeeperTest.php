<?php
/**
 * Created by PhpStorm.
 * User: jablan
 * Date: 8/11/13
 * Time: 10:37 PM
 */

namespace DreamFactory\Oasys;

/**
 * GateKeeperTest
 */
class GateKeeperTest extends \PHPUnit_Framework_TestCase
{
	protected $_gk;

	protected function setUp()
	{
		$this->_gk = new GateKeeper( dirname( __DIR__ ) . '/config/oasys.config.php' );

		parent::setUp();
	}
}
