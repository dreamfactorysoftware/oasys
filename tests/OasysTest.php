<?php
/**
 * This file is part of the DreamFactory Oasys (Open Authentication SYStem)
 *
 * DreamFactory Oasys (Open Authentication SYStem) <http://dreamfactorysoftware.github.io>
 * Copyright 2013 DreamFactory Software, Inc. <support@dreamfactory.com>
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
namespace DreamFactory\Oasys;

use DreamFactory\Oasys\Stores\FileSystem;
use Kisma\Core\Utility\Log;

/**
 * OasysTest
 * Tests the methods in the Oasys class
 */
class OasysTest extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		Log::setDefaultLog( __DIR__ . '/log/error.log' );

		Oasys::setStore( new FileSystem( __FILE__ ) );

		parent::setUp();
	}

	public function testGetProvider()
	{
		//	Good
		$_provider = Oasys::getProvider( 'facebook' );
		$this->assertInstanceOf( 'DreamFactory\\Oasys\\Providers\\BaseProvider', $_provider );

		//	Bad
		$this->setExpectedException( '\\InvalidArgumentException' );
		Oasys::getProvider( 'yoohoo' );
	}
}
