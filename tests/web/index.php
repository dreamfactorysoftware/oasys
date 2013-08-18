<?php
require_once dirname( __DIR__ ) . '/bootstrap.php';

use DreamFactory\Oasys\Components\GateKeeper;
use DreamFactory\Oasys\Components\GenericUser;
use DreamFactory\Oasys\Components\OAuth\Enums\Flows;
use DreamFactory\Oasys\Exceptions\OasysException;
use DreamFactory\Oasys\Stores\FileSystem;
use Kisma\Core\Utility\Convert;
use Kisma\Core\Utility\Log;

Log::setDefaultLog( __DIR__ . '/../log/error.log' );

$_config = null;

//	Choose the provider to test
//$_providerId = 'facebook';
//$_providerId = 'github';
$_providerId = 'twitter';

switch ( $_providerId )
{
	case 'facebook':
		$_config = array(
			'flow_type'     => Flows::CLIENT_SIDE,
			'client_id'     => FACEBOOK_CLIENT_ID,
			'client_secret' => FACEBOOK_CLIENT_SECRET,
		);
		break;
	case 'github':
		$_config = array(
			'flow_type'     => Flows::CLIENT_SIDE,
			'client_id'     => GITHUB_CLIENT_ID,
			'client_secret' => GITHUB_CLIENT_SECRET,
		);
		break;

	case 'twitter':
		array(
			'consumer_key'    => TWITTER_CONSUMER_KEY,
			'consumer_secret' => TWITTER_CONSUMER_SECRET,
		);
		break;

	default:
		throw new OasysException( 'Provider "' . $_providerId . '" not set supported here.' );
		break;
}

//	Start it up
$_oasys = new GateKeeper( array( 'store' => new FileSystem( __FILE__ ) ) );
$_provider = $_oasys->getProvider( $_providerId, $_config );

if ( $_provider->handleRequest() )
{
	/** @var GenericUser $_profile */
	$_profile = $_provider->fetchUserData();

	header( 'Content-Type: application/json' );
	echo $_profile->getUserData();
	die();
}
