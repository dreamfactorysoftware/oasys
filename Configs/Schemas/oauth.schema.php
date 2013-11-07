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
use DreamFactory\Oasys\Enums\TokenTypes;
use Kisma\Core\Utility\Curl;

return array(
	'client_id'               => array( 'type' => 'text', 'maxlength' => 64, 'class' => 'required' ),
	'client_secret'           => array( 'type' => 'text', 'maxlength' => 128, 'class' => 'required' ),
	'redirect_uri'            => array(
		'type'        => 'text',
		'maxlength'   => 1024,
		'class'       => 'required',
		'placeholder' => Curl::currentUrl( false, false ),
	),
	'scope'                   => array( 'type' => 'textarea', 'hint' => 'Comma-separated list of desired scopes.' ),
	'certificate_file'        => array( 'type' => 'textarea', 'maxlength' => 1024, 'placeholder' => 'Provider Default' ),
	'authorize_url'           => array( 'type' => 'text', 'maxlength' => 1024, 'placeholder' => 'Provider Default' ),
	'grant_type'              => array(
		'type'  => 'select',
		'value' => GrantTypes::AUTHORIZATION_CODE,
		'data'  => GrantTypes::getDefinedConstants( true, null, true ),
	),
	'auth_type'               => array( 'type' => 'select', 'value' => OAuthTypes::URI, 'data' => OAuthTypes::getDefinedConstants( true, null, true ) ),
	'access_type'             => array( 'type' => 'select', 'value' => AccessTypes::OFFLINE, 'data' => AccessTypes::getDefinedConstants( true, null, true ) ),
	'flow_type'               => array( 'type' => 'select', 'value' => Flows::SERVER_SIDE, 'data' => Flows::getDefinedConstants( true, null, true ) ),
	'access_token_param_name' => array(
		'type'      => 'text',
		'maxlength' => 64,
		'hint'      => 'The name of the parameter to use when sending the access token via URL.'
	),
	'auth_header_name'        => array(
		'type'      => 'text',
		'maxlength' => 64,
		'hint'      => 'The name of the parameter to use when sending the access token via HTTP header.'
	),
	'access_token_type'       => array(
		'type'    => 'select',
		'default' => TokenTypes::URI,
		'data'    => TokenTypes::getDefinedConstants( true, null, true ),
		'hint'    => 'The type of, and way the provider expects to receive, the token.'
	),
	'access_token'            => array( 'type' => 'text', 'maxlength' => 128, 'placeholder' => 'Not Stored', 'private' => true ),
	'access_token_secret'     => array( 'type' => 'text', 'maxlength' => 128, 'placeholder' => 'Not Stored', 'private' => true ),
	'access_token_expires'    => array( 'type' => 'text', 'class' => 'number', 'private' => true ),
	'refresh_token'           => array( 'type' => 'text', 'maxlength' => 128, 'private' => true ),
	'refresh_token_expires'   => array( 'type' => 'text', 'class' => 'number', 'private' => true ),
	'redirect_proxy_url'      => array( 'type' => 'text', 'maxlength' => 1024 ),
);
