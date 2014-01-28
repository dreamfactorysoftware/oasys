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
use DreamFactory\Platform\Exceptions\BadRequestException;
use DreamFactory\Platform\Resources\User\Session;
use DreamFactory\Platform\Yii\Models\ProviderUser;
use Kisma\Core\Enums\HttpMethod;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * Facebook
 * A facebook provider
 */
class Facebook extends BaseOAuthProvider
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const DEFAULT_SCOPE = 'email,user_about_me,user_birthday,user_hometown,user_website,read_stream,offline_access,publish_stream,read_friendlists';

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Revoke a user's app auth on Facebook
	 *
	 * @param string $providerUserId
	 *
	 * @throws \DreamFactory\Platform\Exceptions\BadRequestException
	 */
	protected function _revokeAuthorization( $providerUserId = null )
	{
		$_id = $providerUserId ? : null;

		if ( empty( $providerUserId ) && null === ( $_id = $this->getConfig( 'provider_user_id' ) ) )
		{
			$_profile = $this->getUserData();

			if ( !empty( $_profile ) && null !== ( $_id = $_profile->getUserId() ) )
			{
				throw new BadRequestException( 'Revocation not possible without provider user ID.' );
			}
		}

		$_result = $this->fetch( '/' . $_id . '/permissions', array(), HttpMethod::Delete );

		if ( true !== ( $_success = Option::get( $_result, 'result', false ) ) )
		{
			if ( HttpResponse::BadRequest !== Option::get( $_result, 'code' ) )
			{
				Log::error( 'Facebook revocation for user ID "' . $_id . '" FAILED.' );

				return;
			}
			else
			{
				Log::debug( 'Facebook revocation for user ID "' . $_id . '" already completed.' );
			}
		}
		else
		{
			Log::debug( 'Facebook revocation for user ID "' . $_id . '" successful.' );
		}

		parent::_revokeAuthorization();
	}

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

		if ( 200 != ( $_code = Option::get( $_response, 'code', Curl::getLastHttpCode() ) ) )
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

		return new GenericUser( array(
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
								) );
	}
}
