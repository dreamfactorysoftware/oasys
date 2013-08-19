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
	 * @throws \Exception|\OAuthException
	 * @throws \DreamFactory\Oasys\Exceptions\OasysException
	 * @return bool|GenericUser
	 */
	public function getUserData()
	{
	}
}
