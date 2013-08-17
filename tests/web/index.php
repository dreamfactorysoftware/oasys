<?php
require_once dirname( dirname( __DIR__ ) ) . '/vendor/autoload.php';

use DreamFactory\Oasys\Components\GateKeeper;
use DreamFactory\Oasys\Components\GenericUser;
use DreamFactory\Oasys\Components\OAuth\Enums\Flows;
use DreamFactory\Oasys\Stores\FileSystem;
use Kisma\Core\Utility\Convert;
use Kisma\Core\Utility\Log;

Log::setDefaultLog( __DIR__ . '/../log/error.log' );

$_config = array();

if ( file_exists( dirname( __DIR__ ) . '/config/oasys.config.php' ) )
{
	$_config = require( dirname( __DIR__ ) . '/config/oasys.config.php' );
}

$_gk = new GateKeeper(
	array_merge(
		array(
			 'store' => new FileSystem( __FILE__ )
		),
		$_config
	)
);

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
//$_provider = $_gk->getProvider(
//	'github',
//	array(
//		 'flow_type'     => Flows::CLIENT_SIDE,
//		 'client_id'     => 'caf2ba694afc90d62c2a',
//		 'client_secret' => '8f5b38a65ddfc0761febe0c113a2e128c43bac9e',
//	)
//);

//	Twitter

$_provider = $_gk->getProvider(
	'twitter',
	array(
		 'consumer_key'    => 'slPaWdUIv2TYsgywu1pJ6w',
		 'consumer_secret' => 'w9GP5LibncziUUoISao7itVs1FxD9vEZb8BwCVSN4',
	)
);

if ( $_provider->handleRequest() )
{
	/** @var GenericUser $_profile */
	$_profile = $_provider->fetchUserData();

	header( 'Content-Type: application/json' );
	echo $_profile->getUserData();
	die();
}
