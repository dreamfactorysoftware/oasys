<?php
namespace DreamFactory\Oasys\Providers;

use DreamFactory\Oasys\BaseProvider;
use DreamFactory\Oasys\Exceptions\RedirectRequiredException;

/**
 * GooglePlus
 *
 * @package DreamFactory\Oasys\Providers
 */
class GooglePlus extends \Hybrid_Provider_Model_OAuth2
{
	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Returns user profile
	 *
	 * Examples:
	 *
	 *    $data = $hybridauth->authenticate( "Google" )->getUserProfile();
	 */
	function getUserProfile()
	{
		$response = $this->signedRequest( "https://www.googleapis.com/oauth2/v1/userinfo" );
		$response = json_decode( $response );

		// Provider Errors shall not pass silently
		if ( !$response || !isset( $response->id ) )
		{
			throw new
			Exception(
				'User profile request failed: Provider returned an invalid response. ' .
				'HTTP client state: (' . $this->httpClient->getState() . ')',
				Exception::AUTHENTIFICATION_FAILED,
				$this
			);
		}

		$parser = function ( $property ) use ( $response )
		{
			return property_exists( $response, $property ) ? $response->$property : null;
		};

		$profile = new Profile();

		$profile->setIdentifier( $parser( 'id' ) );
		$profile->setFirstName( $parser( 'given_name' ) );
		$profile->setLastName( $parser( 'family_name' ) );
		$profile->setDisplayName( $parser( 'name' ) );
		$profile->setPhotoURL( $parser( 'picture' ) );
		$profile->setProfileURL( $parser( 'link' ) );
		$profile->setGender( $parser( 'gender' ) );
		$profile->setEmail( $parser( 'email' ) );
		$profile->setLanguage( $parser( 'locale' ) );

		if ( $parser( 'birthday' ) )
		{
			list( $y, $m, $d ) = explode( '-', $response->birthday );

			$profile->setBirthDay( $d );
			$profile->setBirthMonth( $m );
			$profile->setBirthYear( $y );
		}

		if ( $parser( 'verified_email' ) )
		{
			$profile->setEmailVerified( $profile->getEmail() );
		}

		return $profile;
	}

	// --------------------------------------------------------------------

	/**
	 * Returns user contacts list
	 *
	 * Examples:
	 *
	 *    $data = $hybridauth->authenticate( "Google" )->getUserContacts( array( "max-results" => 10 ) );
	 */
	function getUserContacts( $args = array() )
	{
		// refresh tokens if needed
		$this->refreshToken();

		$url = "https://www.google.com/m8/feeds/contacts/default/full?"
			   . http_build_query( array_merge( array( 'alt' => 'json' ), $args ) );

		$response = $this->signedRequest( $url );
		$response = json_decode( $response );

		if ( !$response || isset( $response->error ) )
		{
			throw new
			Exception(
				'User contacts request failed: Provider returned an invalid response. ' .
				'HTTP client state: (' . $this->httpClient->getState() . ')',
				Exception::USER_PROFILE_REQUEST_FAILED,
				$this
			);
		}

		$contacts = array();

		if ( isset( $response->feed ) && is_array( $response->feed ) )
		{
			foreach ( $response->feed->entry as $idx => $entry )
			{
				$profile = new Profile();

				$email = isset( $entry->{'gd$email'} [0]->address ) ? (string)$entry->{'gd$email'} [0]->address : '';
				$displayName = isset( $entry->title->{'$t'} ) ? (string)$entry->title->{'$t'} : '';

				$profile->setIdentifier( $email );
				$profile->setDisplayName( $displayName );
				$profile->setEmail( $email );

				$contacts[] = $profile;
			}
		}

		return $contacts;
	}

	// --------------------------------------------------------------------

	/**
	 * Updates user status
	 *
	 * Examples:
	 *
	 *    $data = $hybridauth->authenticate( "Google" )->setUserStatus( _STATUS_ );
	 */
	function setUserStatus( $status )
	{
		throw new Exception( "Unsupported", Exception::UNSUPPORTED_FEATURE, null, $this );
	}

	/**
	 * Begin the authorization process
	 *
	 * @throws RedirectRequiredException
	 */
	public function startAuthorization()
	{
		// TODO: Implement startAuthorization() method.
	}

	/**
	 * Complete the authorization process
	 */
	public function completeAuthorization()
	{
		// TODO: Implement completeAuthorization() method.
	}

	/**
	 * Checks to see if user is authorized with this provider
	 *
	 * @return bool
	 */
	public function authorized()
	{
		// TODO: Implement authorized() method.
	}

	/**
	 * Unlink/disconnect/logout user from provider locally.
	 * Does nothing on the provider end
	 *
	 * @return void
	 */
	public function deauthorize()
	{
		// TODO: Implement deauthorize() method.
	}
}
