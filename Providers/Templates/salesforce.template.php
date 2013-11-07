<?php
/**
 * This file is part of the DreamFactory Salesforce Jetpack
 *
 * Copyright 2013 DreamFactory Software, Inc. <support@dreamfactory.com>
 */
namespace DreamFactory\Oasys\Providers\Templates;

use DreamFactory\Oasys\Enums\TokenTypes;
use DreamFactory\Oasys\Enums\EndpointTypes;
use DreamFactory\Oasys\Enums\ProviderConfigTypes;
use DreamFactory\Oasys\Providers\Salesforce;

/**
 * salesforce.template.php
 *
 * This is the template for connecting to Salesforce.
 *
 * Salesforce scopes are listed here: https://help.salesforce.com/apex/HTViewHelpDoc?id=remoteaccess_oauth_scopes.htm&language=en
 */
return array(
	'type'               => ProviderConfigTypes::OAUTH,
	'access_token_type'  => TokenTypes::BEARER,
	'client_id'          => '{{client_id}}',
	'client_secret'      => '{{client_secret}}',
	'redirect_proxy_url' => 'https://oasys.cloud.dreamfactory.com/oauth/authorize',
	'scope'              => Salesforce::DEFAULT_SCOPE,
	'use_sandbox'        => false,
	'endpoint_map'       => array(
		EndpointTypes::AUTHORIZE    => 'https://login.salesforce.com/services/oauth2/authorize',
		EndpointTypes::ACCESS_TOKEN => 'https://login.salesforce.com/services/oauth2/token',
		EndpointTypes::SERVICE      => 'https://{{instance_name}}',
		EndpointTypes::REVOKE       => 'https://login.salesforce.com/services/oauth2/revoke',
	),
	'referrer_domain'    => 'salesforce.com',
);
