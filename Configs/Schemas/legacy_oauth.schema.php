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
namespace DreamFactory\Oasys\Configs\Schemas;

use DreamFactory\Oasys\Enums\Flows;
use DreamFactory\Oasys\Enums\OAuthTypes;

/**
 * legacy_oauth.schema.php
 * The config schema for an OAuth v1.x service
 */
return array(
	'consumer_key'        => array( 'type' => 'text', 'maxlength' => 64, 'class' => 'required' ),
	'consumer_secret'     => array( 'type' => 'text', 'maxlength' => 128, 'class' => 'required' ),
	'redirect_uri'        => array( 'type' => 'text', 'maxlength' => 1024, 'class' => 'required' ),
	'signature_method'    => array( 'type' => 'text', 'maxlength' => 16, 'class' => 'required', 'placeholder' => OAUTH_SIG_METHOD_HMACSHA1 ),
	'authorize_url'       => array( 'type' => 'text', 'maxlength' => 1024 ),
	'auth_type'           => array( 'type' => 'select', 'required' => false, 'value' => OAuthTypes::URI, 'data' => OAuthTypes::getDefinedConstants( true, null, true ) ),
	'flow_type'           => array( 'type' => 'select', 'required' => false, 'value' => Flows::CLIENT_SIDE, 'data' => Flows::getDefinedConstants( true, null, true ) ),
	'access_token'        => array( 'type' => 'text', 'maxlength' => 128, 'private' => true ),
	'access_token_secret' => array( 'type' => 'text', 'maxlength' => 128, 'private' => true ),
);
