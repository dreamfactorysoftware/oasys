<?php
/**
 * This file is part of the DreamFactory Oasys (Open Authentication SYStem)
 *
 * DreamFactory Oasys (Open Authentication SYStem) <http://dreamfactorysoftware.github.io>
 * Copyright 2014 DreamFactory Software, Inc. <support@dreamfactory.com>
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
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\Curl;
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
	const DEFAULT_SCOPE = 'profile email https://www.google.com/m8/feeds/';

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Returns this user as a GenericUser
	 *
	 * @return UserLike
	 * @throws OasysException
	 * @throws \InvalidArgumentException
	 */
	public function getUserData()
	{
		$_response = parent::getUserData();

		if ( HttpResponse::Ok != ( $_code = Option::get( $_response, 'code', Curl::getLastHttpCode() ) ) )
		{
			throw new OasysException( 'Unexpected response code', $_code, null, $_response );
		}

		$_profile = Option::get( $_response, 'result' );

		if ( empty( $_profile ) )
		{
			throw new \InvalidArgumentException( 'No profile available to convert.' );
		}

		$_profileId = Option::get( $_profile, 'id' );
		$_nameGoogle = Option::get( $_profile, 'name' );

		$_name = array(
			'formatted'  => Option::get( $_profile, 'displayName' ),
			'familyName' => Option::get( $_nameGoogle, 'familyName' ),
			'givenName'  => Option::get( $_nameGoogle, 'givenName' ),
		);

		$_email = null;

		// Get the account email. Google returns a list of emails.
		foreach ( Option::get( $_profile, 'emails' ) as $_emailIteration )
		{
			if ( Option::get( $_emailIteration, 'type' ) == 'account' )
			{
				$_email = Option::get( $_emailResult, 'value' );
				break; // ugly, but works
			}
		}

		return new  GenericUser(
			array(
				'user_id'       => $_profileId,
				'name'          => $_name,
				'gender'        => Option::get( $_profile, 'gender' ),
				'email_address' => $_email,
				'urls'          => array( Option::get( $_profile, 'url' ) ),
				'thumbnail_url' => Option::getDeep( $_profile, 'image', 'url' ),
				'user_data'     => $_profile,
			)
		);
	}

	/**
	 * Google Plus does not allow the state parameter when making access tokens requests, so it is removed from the
	 * payload.
	 *
	 * @param string $grantType
	 * @param array  $payload
	 *
	 * @return array|false
	 * @throws \InvalidArgumentException
	 */
	public function requestAccessToken( $grantType = GrantTypes::AUTHORIZATION_CODE, array $payload = array() )
	{
		Option::remove( $payload, 'state' );

		return parent::requestAccessToken( $grantType, $payload );
	}
}
