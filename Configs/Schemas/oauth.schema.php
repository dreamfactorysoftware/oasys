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

/**
 * oauth.schema.php
 * The config schema for an OAuth service
 */
use DreamFactory\Oasys\Enums\AccessTypes;
use DreamFactory\Oasys\Enums\Flows;
use DreamFactory\Oasys\Enums\GrantTypes;
use DreamFactory\Oasys\Enums\OAuthTypes;

return array(
	'client_id'               => array( 'type' => 'text', 'maxlength' => 64, 'class' => 'required' ),
	'client_secret'           => array( 'type' => 'text', 'maxlength' => 128, 'class' => 'required' ),
	'redirect_uri'            => array( 'type' => 'text', 'maxlength' => 1024, 'class' => 'required' ),
	'scope'                   => array( 'type' => 'textarea' ),
	'certificate_file'        => array( 'type' => 'textarea', 'maxlength' => 1024 ),
	'authorize_url'           => array( 'type' => 'text', 'maxlength' => 1024 ),
	'grant_type'              => array( 'type' => 'select', 'default' => GrantTypes::AUTHORIZATION_CODE, 'data' => GrantTypes::getDefinedConstants( true ) ),
	'auth_type'               => array( 'type' => 'select', 'default' => OAuthTypes::URI, 'data' => OAuthTypes::getDefinedConstants( true ) ),
	'access_type'             => array( 'type' => 'select', 'default' => AccessTypes::OFFLINE, 'data' => AccessTypes::getDefinedConstants( true ) ),
	'flow_type'               => array( 'type' => 'select', 'default' => Flows::CLIENT_SIDE, 'data' => Flows::getDefinedConstants( true ) ),
	'access_token_param_name' => array( 'type' => 'text', 'maxlength' => 64 ),
	'auth_header_name'        => array( 'type' => 'text', 'maxlength' => 64 ),
	'access_token'            => array( 'type' => 'text', 'maxlength' => 128 ),
	'access_token_type'       => array( 'type' => 'select' ),
	'access_token_secret'     => array( 'type' => 'text', 'maxlength' => 128 ),
	'access_token_expires'    => array( 'type' => 'int' ),
	'refresh_token'           => array( 'type' => 'text', 'maxlength' => 64 ),
	'refresh_token_expires'   => array( 'type' => 'int' ),
);
