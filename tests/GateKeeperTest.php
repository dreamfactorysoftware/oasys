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
	/**
	 * @var GateKeeper
	 */
	protected $_gk;

	protected function setUp()
	{
		$this->_gk = new GateKeeper( dirname( __DIR__ ) . '/config/oasys.config.php' );

		parent::setUp();
	}

	public function testGetClient()
	{
		$_client = $this->_gk->getClient();

		$this->assertTrue( $_client == 'client' );
	}

	public function testSetClient()
	{
		$this->_gk->setClient( 'client' );

		$this->assertTrue( $this->_gk->getClient() == 'client' );
	}

	protected function tearDown()
	{
		$this->_gk = null;

		parent::tearDown();
	}
}
