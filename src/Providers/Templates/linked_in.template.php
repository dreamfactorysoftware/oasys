<?php
/**
 * This file is part of DreamFactory Oasys(tm)
 *
 * Copyright (c) 2014 DreamFactory Software, Inc. <support@dreamfactory.com>
 */
namespace DreamFactory\Oasys\Providers\Templates;

use DreamFactory\Oasys\Enums\EndpointTypes;
use DreamFactory\Oasys\Enums\ProviderConfigTypes;
use DreamFactory\Oasys\Enums\TokenTypes;
use DreamFactory\Oasys\Providers\LinkedIn;

/**
 * linked_in.template.php
 *
 * This is the template for connecting to LinkedIn.
 *
 * LinkedIn scopes are listed here: https://help.salesforce.com/apex/HTViewHelpDoc?id=remoteaccess_oauth_scopes.htm&language=en
 */

return array(
	'id'                 => 'linked_in',
	'type'               => ProviderConfigTypes::OAUTH,
	'access_token_type'  => TokenTypes::BEARER,
	'client_id'          => '{{client_id}}',
	'client_secret'      => '{{client_secret}}',
	'scope'              => LinkedIn::DEFAULT_SCOPE,
	'redirect_proxy_url' => 'https://oasys.cloud.dreamfactory.com/oauth/authorize',
	'endpoint_map'       => array(
		EndpointTypes::AUTHORIZE    => 'https://www.linkedin.com/uas/oauth2/authorization',
		EndpointTypes::ACCESS_TOKEN => 'https://www.linkedin.com/uas/oauth2/accessToken',
		EndpointTypes::SERVICE      => 'https://api.linkedin.com/v1',
		EndpointTypes::IDENTITY     => '/people/~',
	),
	'referrer_domain'    => 'facebook.com',
);
