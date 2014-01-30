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
use DreamFactory\Oasys\Exceptions\OasysException;
use DreamFactory\Oasys\Interfaces\UserLike;
use Kisma\Core\Utility\Option;

/**
 * StackExchange
 * A StackExchange provider
 */
class StackExchange extends BaseOAuthProvider
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const DEFAULT_SCOPE = 'no_expiry,private_info';

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Returns this user as a GenericUser
	 *
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\OasysException
	 * @throws \InvalidArgumentException
	 * @return UserLike
	 */
	public function getUserData()
	{
		$_response = $this->fetch( '/me' );

		if ( 200 != ( $_code = Option::get( $_response, 'code' ) ) )
		{
			throw new OasysException( 'Unexpected response code: ' . print_r( $_response, true ) );
		}

		$_profile = Option::get( $_response, 'result' );

		if ( empty( $_profile ) )
		{
			throw new \InvalidArgumentException( 'No profile available to convert.' );
		}

		$_profileId = Option::get( $_profile, 'id' );

		$_name = array(
			'formatted'  => Option::get( $_profile, 'name' ),
			'familyName' => Option::get( $_profile, 'last_name' ),
			'givenName'  => Option::get( $_profile, 'first_name' ),
		);

		return new GenericUser(
			array(
				 'user_id'            => $_profileId,
				 'published'          => Option::get( $_profile, 'updated_time' ),
				 'updated'            => Option::get( $_profile, 'updated_time' ),
				 'display_name'       => $_name['formatted'],
				 'name'               => $_name,
				 'preferred_username' => Option::get( $_profile, 'username' ),
				 'gender'             => Option::get( $_profile, 'gender' ),
				 'email_address'      => Option::get( $_profile, 'email' ),
				 'urls'               => array( Option::get( $_profile, 'link' ) ),
				 'relationships'      => Option::get( $_profile, 'friends' ),
				 'thumbnail_url'      => $this->_config->getEndpointUrl() . '/' . $_profileId . '/picture?width=150&height=150',
				 'user_data'          => $_profile,
			)
		);
	}
}
