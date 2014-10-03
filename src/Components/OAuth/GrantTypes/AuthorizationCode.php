<?php
/**
 * This file is part of the DreamFactory Oasys (Open Authentication SYStem)
 *
 * DreamFactory Oasys (Open Authentication SYStem) <https://www.dreamfactory.com/>
 * Copyright 2012-2014 DreamFactory Software, Inc. <support@dreamfactory.com>
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
namespace DreamFactory\Oasys\Components\OAuth\GrantTypes;

use DreamFactory\Oasys\Interfaces\GrantTypeLike;
use Kisma\Core\Utility\Option;

/**
 * AuthorizationCode
 */
class AuthorizationCode implements GrantTypeLike
{
	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param array|\stdClass $payload
	 *
	 * @throws \InvalidArgumentException
	 * @return array|\stdClass|void
	 */
	public static function validatePayload( $payload )
	{
		if ( null === Option::get( $payload, 'code' ) )
		{
			throw new \InvalidArgumentException( 'The "code" parameter must be specified to use this grant type.' );
		}

		if ( null === Option::get( $payload, 'redirect_uri' ) )
		{
			throw new \InvalidArgumentException( 'The "redirect_uri" parameter must be specified to use this grant type.' );
		}

		return $payload;
	}
}
