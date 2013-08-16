<?php
require_once dirname( dirname( __DIR__ ) ) . '/vendor/autoload.php';

use DreamFactory\Oasys\Components\GateKeeper;
use DreamFactory\Oasys\Enums\OAuthFlows;
use DreamFactory\Oasys\Stores\FileSystem;

$_store = new FileSystem( __FILE__ );

$_gk = new GateKeeper( array_merge( array( 'store' => $_store ), require( dirname( __DIR__ ) . '/config/oasys.config.php' ) ) );

$_provider = $_gk->getProvider(
	'facebook',
	array(
		 'flow_type'     => OAuthFlows::CLIENT_SIDE,
		 'client_id'     => '1392217090991437',
		 'client_secret' => 'd5dd3a24b1ec6c5f204a300ed24c60d0',
	)
);

$_provider->handleRequest();
