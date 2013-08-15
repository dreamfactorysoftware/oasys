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
 * ProviderConfigLike
 * The supported types of provider configurations
 */
interface ProviderConfigLike
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var int OAuth 2.0 (default)
	 */
	const OAUTH = 0;
	/**
	 * @var int OAuth 1.0
	 */
	const LEGACY_OAUTH = 1;
	/**
	 * @var int OpenID
	 */
	const OPENID = 2;
	/**
	 * @var int LDAP
	 */
	const LDAP = 3;
	/**
	 * @var int Active Directory
	 */
	const ACTIVE_DIRECTORY = 4;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @return string
	 */
	public function toJson();

	/**
	 * @return array
	 */
	public function toArray();
}
