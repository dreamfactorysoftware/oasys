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
	 * @var \DreamFactory\Oasys\Providers\Facebook
	 */
	protected $_provider;

	protected function setUp()
	{
		$_store = new FileSystem( __FILE__ );

		$this->_gk = new GateKeeper( array_merge( array( 'store' => $_store ), require( dirname( __DIR__ ) . '/config/oasys.config.php' ) ) );

		$this->_provider = $this->_gk->getProvider(
			'facebook',
			array(
				 'client_id'     => '1392217090991437',
				 'client_secret' => 'd5dd3a24b1ec6c5f204a300ed24c60d0',
			)
		);

		$this->_provider->authorized();
		$this->_provider->handleRequest();

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
