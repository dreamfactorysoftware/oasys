<?php
/**
 * Created by PhpStorm.
 * User: jablan
 * Date: 8/11/13
 * Time: 10:37 PM
 */
namespace DreamFactory\Tests\Oasys\Components;

use DreamFactory\Oasys\Components\GateKeeper;
use DreamFactory\Oasys\Stores\FileSystem;
use Kisma\Core\Utility\Log;

/**
 * GateKeeperTest
 */
class GateKeeperTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var \DreamFactory\Oasys\Components\GateKeeper
	 */
	protected $_gk;
	/**
	 * @var \DreamFactory\Oasys\Providers\Twitter
	 */
	protected $_provider;

	protected function setUp()
	{
		Log::setDefaultLog( __DIR__ . '/../log/error.log' );

		$_store = new FileSystem( __FILE__ );

		$this->_gk = new GateKeeper( array_merge( array( 'store' => $_store ), require( dirname( __DIR__ ) . '/config/oasys.config.php' ) ) );

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

		$this->assertTrue( sizeof( $_SERVER ) > 1 );
	}

	protected function tearDown()
	{
		$this->_gk = null;

		parent::tearDown();
	}
}
