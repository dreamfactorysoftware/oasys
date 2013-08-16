<?php
require_once dirname( dirname( __DIR__ ) ) . '/vendor/autoload.php';

use DreamFactory\Oasys\Components\GateKeeper;
use DreamFactory\Oasys\Components\OAuth\Enums\Flows;
use DreamFactory\Oasys\Stores\FileSystem;
use Kisma\Core\Utility\Log;

Log::setDefaultLog( __DIR__ . '/../log/error.log' );

$_gk = new GateKeeper( array_merge(
	array(
		 'store' => new FileSystem( __FILE__ )
	),
	require( dirname( __DIR__ ) . '/config/oasys.config.php' )
) );

//	Facebook
//$_provider = $_gk->getProvider(
//	'facebook',
//	array(
//		 'flow_type'     => Flows::CLIENT_SIDE,
//		 'client_id'     => '1392217090991437',
//		 'client_secret' => 'd5dd3a24b1ec6c5f204a300ed24c60d0',
//	)
//);
//
//if ( $_provider->handleRequest() )
//{
//	header( 'Content-Type: application/json' );
//	echo json_encode( $_provider->fetch( '/me' ) );
//}

//	Github
$_provider = $_gk->getProvider(
	'github',
	array(
		 'flow_type'     => Flows::CLIENT_SIDE,
		 'client_id'     => 'caf2ba694afc90d62c2a',
		 'client_secret' => '8f5b38a65ddfc0761febe0c113a2e128c43bac9e',
	)
);

if ( $_provider->handleRequest() )
{
	header( 'Content-Type: application/json' );
	echo json_encode( $_provider->fetch( '/user' ) );
}
