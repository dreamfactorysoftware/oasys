<?php
/**
 * Created by PhpStorm.
 * User: jablan
 * Date: 8/11/13
 * Time: 10:37 PM
 */
namespace DreamFactory\Tests\Oasys;

use DreamFactory\Oasys\GateKeeper;

require_once dirname( __DIR__ ) . '/GateKeeper.php';

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
		$this->_gk = new GateKeeper( require_once( __DIR__ . '/config/oasys.config.php' ) );

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
