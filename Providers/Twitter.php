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
use DreamFactory\Oasys\Clients\LegacyOAuthClient;
use DreamFactory\Oasys\Exceptions\OasysException;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * Twitter
 * A Twitter provider
 */
class Twitter extends BaseLegacyOAuthProvider
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param bool $force If true, the data will be pull from the source, otherwise the last pulled copy is returned
	 *
	 * @throws \Exception|\OAuthException
	 * @throws \DreamFactory\Oasys\Exceptions\OasysException
	 * @return bool|GenericUser
	 */
	public function fetchUserData( $force = false )
	{
		try
		{
			$_response = $this->_client->fetch( '/account/verify_credentials.json' );
		}
		catch ( \OAuthException $_ex )
		{
			Log::error( 'OAuth exception: ' . $_ex->getMessage() );
			throw $_ex;
		}

		$_profile = json_decode( $_response['result'] );

		if ( !isset( $_profile, $_profile->id ) )
		{
			throw new OasysException( 'Invalid or error result: ' . print_r( $_response, true ) );
		}

		return new GenericUser(
			array(
				 'provider_id'         => 'twitter',
				 'user_id'             => Option::get( $_profile, 'id' ),
				 'first_name'          => Option::get( $_profile, 'name' ),
				 'display_name'        => Option::get( $_profile, 'screen_name' ),
				 'preferred_user_name' => Option::get( $_profile, 'screen_name' ),
				 'description'         => Option::get( $_profile, 'description' ),
				 'thumbnail_url'       => Option::get( $_profile, 'profile_image_url' ),
				 'profile_url'         => 'http://twitter.com/' . Option::get( $_profile, 'screen_name' ),
				 'urls'                => array( Option::get( $_profile, 'url' ) ),
				 'user_data'           => $_response['result'],
			)
		);
	}
}
