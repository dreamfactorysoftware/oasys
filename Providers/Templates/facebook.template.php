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
 * facebook.config.php.dist
 *
 * This is the template for connecting Facebook.
 */
return array(
	'type'              => ProviderConfigTypes::OAUTH,
	'client_id'         => '{{client_id}}',
	'client_secret'     => '{{client_secret}}',
	'scope'             => 'email,user_about_me,user_birthday,user_hometown,user_website,read_stream,offline_access,publish_stream,read_friendlists',
	'endpoint_map'      => array(
		EndpointTypes::AUTHORIZE    => 'https://www.facebook.com/dialog/oauth',
		EndpointTypes::ACCESS_TOKEN => 'https://graph.facebook.com/oauth/access_token',
		EndpointTypes::SERVICE      => 'https://graph.facebook.com',
	),
	'referrer_domain'   => 'facebook.com',
	'identity_resource' => '/me',
);
