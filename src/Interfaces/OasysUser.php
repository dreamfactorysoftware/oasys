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
namespace DreamFactory\Oasys\Interfaces;

/**
 * OasysUser
 * A generic Oasys user object
 */
interface OasysUser
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @return int|string
	 */
	public function getId();

	/**
	 * @return int|string
	 */
	public function getUserId();

	/**
	 * @param int|string $userId
	 *
	 * @return $this
	 */
	public function setUserId( $userId );

	/**
	 * @return string The ID of the providers
	 */
	public function getProviderId();

	/**
	 * @param string $providerId
	 *
	 * @return $this
	 */
	public function setProviderId( $providerId );

	/**
	 * @return string
	 */
	public function getUserName();

	/**
	 * @param string $userName
	 *
	 * @return $this
	 */
	public function setUserName( $userName );

	/**
	 * @return array|\stdClass The provider-supplied user profile for this user, if any
	 */
	public function getUserData();

	/**
	 * @param array|\stdClass $userData
	 *
	 * @return $this
	 */
	public function setUserData( $userData = array() );
}
