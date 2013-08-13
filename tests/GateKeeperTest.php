<?php
/**
 * Created by PhpStorm.
 * User: jablan
 * Date: 8/11/13
 * Time: 10:37 PM
 */
namespace DreamFactory\Tests\Oasys;

use DreamFactory\Oasys\GateKeeper;
use DreamFactory\Oasys\Stores\FileSystem;

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
		$_store = new FileSystem( __FILE__ );

		$this->_gk = new GateKeeper( array_merge( array( 'store' => $_store ), require( __DIR__ . '/config/oasys.config.php' ) ) );

		parent::setUp();
	}

	public function testGetSet()
	{
		$_id = 1234567;
		$_store = $this->_gk->getStore();

		$_store->set( 'id', $_id );

		foreach ( $_SERVER as $_key => $_value )
		{
			$_store->set( $_key, $_value );
		}

		$this->assertEquals( sizeof( $_SERVER ) + 1, sizeof( $_store->get() ) );
	}

	protected function tearDown()
	{
		$this->_gk = null;

		parent::tearDown();
	}
}
