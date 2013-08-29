<?php
namespace DreamFactory\Tests\Oasys;

use DreamFactory\Oasys\Oasys;
use DreamFactory\Oasys\Stores\FileSystem;
use Kisma\Core\Utility\Log;

/**
 * OasysTest
 */
class OasysTest extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		Log::setDefaultLog( __DIR__ . '/../log/error.log' );

		Oasys::setStore( new FileSystem( __FILE__ ) );

		parent::setUp();
	}

	public function testGetProvider()
	{
		//	Good
		$_provider = Oasys::getProvider( 'stack_exchange' );
		$this->assertInstanceOf( 'DreamFactory\\Oasys\\Providers\\BaseProvider', $_provider );

		//	Bad
		$this->setExpectedException( '\\InvalidArgumentException' );
		Oasys::getProvider( 'yoohoo' );
	}

	protected function tearDown()
	{
		$this->_oasys = null;

		parent::tearDown();
	}
}
