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

use DreamFactory\Library\Utility\IfSet;
use DreamFactory\Oasys\Components\GenericUser;
use DreamFactory\Oasys\Exceptions\OasysException;
use DreamFactory\Oasys\Interfaces\UserLike;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Option;

/**
 * A LinkedIn provider
 */
class LinkedIn extends BaseOAuthProvider
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const DEFAULT_SCOPE = 'r_basicprofile r_emailaddress r_contactinfo';

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

		$_profileId = IfSet::get( $_profile, 'id' );

		$_name = array(
			'formatted'   => IfSet::get( $_profile, 'formatted-name' ),
			'last_name'   => IfSet::get( $_profile, 'last-name' ),
			'first_name'  => IfSet::get( $_profile, 'first-name' ),
			'maiden_name' => IfSet::get( $_profile, 'maiden-name' ),
		);

		return new GenericUser(
			array(
				'user_id'       => $_profileId,
				'display_name'  => $_name['formatted'],
				'name'          => $_name,
				'email_address' => IfSet::get( $_profile, 'email-address' ),
				'thumbnail_url' => IfSet::get( $_profile, 'picture-url' ),
				'user_data'     => $_profile,
			)
		);
	}
}
