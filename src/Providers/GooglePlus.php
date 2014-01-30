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
 * GooglePlus
 * A GooglePlus provider
 */
class GooglePlus extends BaseOAuthProvider
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const DEFAULT_SCOPE = 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.google.com/m8/feeds/';

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Returns this user as a GenericUser
	 *
	 * @return UserLike
	 */
	public function getUserData()
	{
		return new GenericUser();
		//		$_profile = empty( $profile ) ? array() : $profile;
		//
		//		if ( empty( $_profile ) )
		//		{
		//			throw new \InvalidArgumentException( 'No profile available to convert.' );
		//		}
		//
		//		$_profileId = Option::get( $_profile, 'id' );
		//
		//		$_name = array(
		//			'formatted'  => Option::get( $_profile, 'name' ),
		//			'familyName' => Option::get( $_profile, 'last_name' ),
		//			'givenName'  => Option::get( $_profile, 'first_name' ),
		//		);
		//
		//		return $_contact->setUserId( $_profileId )->setPublished( Option::get( $_profile, 'updated_time' ) )->setUpdated(
		//			Option::get( $_profile, 'updated_time' )
		//		)->setDisplayName( $_name['formatted'] )->setName( $_name )->setPreferredUsername( Option::get( $_profile, 'username' ) )->setGender(
		//				Option::get( $_profile, 'gender' )
		//			)->setEmails( array( Option::get( $_profile, 'email' ) ) )->setUrls( array( Option::get( $_profile, 'link' ) ) )->setRelationships(
		//				Option::get( $_profile, 'friends' )
		//			)->setPhotos( array( '/' . $_profileId . '/picture?width=150&height=150' ) )->setUserData( $_profile );
	}
}
