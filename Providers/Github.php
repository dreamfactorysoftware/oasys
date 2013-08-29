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
use DreamFactory\Oasys\Interfaces\UserLike;
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
	 * @throws \InvalidArgumentException
	 * @return UserLike
	 */
	public function getUserData( $profile = null )
	{
		$_profile = $this->_client->fetch( '/user' );

		if ( empty( $_profile ) )
		{
			throw new \InvalidArgumentException( 'No profile available to convert.' );
		}

		$_profileId = Option::get( $_profile, 'id' );

		return new GenericUser(
			array(
				 'user_id'            => $_profileId,
				 'published'          => Option::get( $_profile, 'created_at' ),
				 'display_name'       => Option::get( $_profile, 'name' ),
				 'name'               => Option::get( $_profile, 'name' ),
				 'email'              => Option::get( $_profile, 'email' ),
				 'preferred_username' => Option::get( $_profile, 'login' ),
				 'urls'               => array( Option::get( $_profile, 'html_url' ) ),
				 'thumbnail_url'      => array( Option::get( $_profile, 'avatar_url' ) ),
				 'user_data'          => $_profile,
			)
		);
	}
}