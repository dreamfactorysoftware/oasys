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
namespace DreamFactory\Oasys\Providers\Schemas;

/**
 * oauth.schema.php
 * The schema for an OAuth service
 */
use DreamFactory\Oasys\Enums\AccessTypes;
use DreamFactory\Oasys\Enums\Flows;
use DreamFactory\Oasys\Enums\GrantTypes;
use DreamFactory\Oasys\Enums\OAuthTypes;

return array(
	'client_id'               => array( 'type' => 'string', 'length' => 64, 'required' => true ),
	'client_secret'           => array( 'type' => 'string', 'length' => 128, 'required' => true ),
	'redirect_uri'            => array( 'type' => 'string', 'length' => 1024, 'required' => true ),
	'scope'                   => array( 'type' => 'array', 'length' => 64, 'required' => false ),
	'certificate_file'        => array( 'type' => 'string', 'length' => 1024, 'required' => false ),
	'authorize_url'           => array( 'type' => 'string', 'length' => 1024, 'required' => false ),
	'grant_type'              => array( 'type' => 'int', 'required' => false, 'default' => GrantTypes::AUTHORIZATION_CODE, 'options' => GrantTypes::getDefinedConstants( true ) ),
	'auth_type'               => array( 'type' => 'int', 'required' => false, 'default' => OAuthTypes::URI, 'options' => OAuthTypes::getDefinedConstants( true ) ),
	'access_type'             => array( 'type' => 'int', 'required' => false, 'default' => AccessTypes::OFFLINE, 'options' => AccessTypes::getDefinedConstants( true ) ),
	'flow_type'               => array( 'type' => 'int', 'required' => false, 'default' => Flows::CLIENT_SIDE, 'options' => Flows::getDefinedConstants( true ) ),
	'access_token_param_name' => array( 'type' => 'string', 'length' => 64, 'required' => false ),
	'auth_header_name'        => array( 'type' => 'string', 'length' => 64, 'required' => false ),
	'access_token'            => array( 'type' => 'string', 'length' => 128, 'required' => false ),
	'access_token_type'       => array( 'type' => 'int', 'required' => false ),
	'access_token_secret'     => array( 'type' => 'string', 'length' => 128, 'required' => false ),
	'access_token_expires'    => array( 'type' => 'int', 'required' => false ),
	'refresh_token'           => array( 'type' => 'string', 'length' => 64, 'required' => false ),
	'refresh_token_expires'   => array( 'type' => 'int', 'required' => false ),
);
