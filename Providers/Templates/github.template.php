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
namespace DreamFactory\Oasys\Providers\Templates;

use DreamFactory\Oasys\Enums\EndpointTypes;
use DreamFactory\Oasys\Enums\ProviderConfigTypes;

/**
 * github.config.php.dist
 *
 * This is the template for connecting GitHub.
 *
 * GitHub scopes are listed here: http://developer.github.com/v3/oauth/#scopes
 */
return array(
	'type'          => ProviderConfigTypes::OAUTH,
	'client_id'     => '{{client_id}}',
	'client_secret' => '{{client_secret}}',
	'scope'         => 'user:email',
	'endpoint_map'  => array(
		EndpointTypes::AUTHORIZE    => 'https://github.com/login/oauth/authorize',
		EndpointTypes::ACCESS_TOKEN => 'https://github.com/login/oauth/access_token',
		EndpointTypes::SERVICE      => 'https://api.github.com',
	),
);
