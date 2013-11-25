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
namespace DreamFactory\Oasys\Providers;

use DreamFactory\Oasys\Components\GenericUser;
use DreamFactory\Oasys\Exceptions\ProviderException;
use DreamFactory\Oasys\Interfaces\UserLike;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * Github
 * A Github provider
 */
class Github extends BaseOAuthProvider
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const DEFAULT_SCOPE = 'user:email';

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Returns this user as a GenericUser
	 *
	 * @param \stdClass|array $profile
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\ProviderException
	 * @throws \InvalidArgumentException
	 * @return UserLike
	 */
	public function getUserData( $profile = null )
	{
		$_result = parent::getUserData();

		if ( empty( $_result ) )
		{
			throw new \InvalidArgumentException( 'No profile available to convert.' );
		}

		if ( null === ( $_profile = Option::get( $_result, 'result' ) ) )
		{
			throw new ProviderException( 'No profile available to convert.' );
		}

//		Log::debug( 'Profile retrieved: ' . print_r( $_profile, true ) );

		$_profileId = Option::get( $_profile, 'id' );

		$_login = Option::get( $_profile, 'login' );
		$_formatted = Option::get( $_profile, 'name', $_login );
		$_parts = explode( ' ', $_formatted );

		$_name = array(
			'formatted' => $_formatted,
		);

		if ( !empty( $_parts ) )
		{
			if ( sizeof( $_parts ) >= 1 )
			{
				$_name['givenName'] = $_parts[0];
			}

			if ( sizeof( $_parts ) > 1 )
			{
				$_name['familyName'] = $_parts[1];
			}
		}

		return new GenericUser(
			array(
				 'provider_id'        => $this->getProviderId(),
				 'user_id'            => $_profileId,
				 'published'          => Option::get( $_profile, 'created_at' ),
				 'display_name'       => $_formatted,
				 'name'               => $_name,
				 'email_address'      => Option::get( $_profile, 'email' ),
				 'preferred_username' => $_login,
				 'urls'               => array( Option::get( $_profile, 'url' ) ),
				 'thumbnail_url'      => Option::get( $_profile, 'avatar_url' ),
				 'updated'            => Option::get( $_profile, 'updated_at' ),
				 'relationships'      => Option::get( $_profile, 'followers' ),
				 'user_data'          => $_profile,
			)
		);
	}
}
