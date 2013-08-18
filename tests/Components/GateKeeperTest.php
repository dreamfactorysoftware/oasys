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

		$this->_gk = new GateKeeper( array_merge( array( 'store' => $_store ) ) );

		parent::setUp();
	}

	public function testGetSet()
	{
		$this->assertTrue( isset( $_SERVER ) );
	}

	protected function tearDown()
	{
		$this->_gk = null;

		parent::tearDown();
	}
}
