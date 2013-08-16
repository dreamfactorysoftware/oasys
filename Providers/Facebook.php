<?php
namespace DreamFactory\Oasys\Providers;

use DreamFactory\Oasys\Components\BaseOAuthProvider;
use DreamFactory\Oasys\Enums\EndpointTypes;
use DreamFactory\Oasys\Exceptions\OasysConfigurationException;
use DreamFactory\Oasys\Exceptions\RedirectRequiredException;
use DreamFactory\Oasys\GenericUser;
use DreamFactory\Oasys\Interfaces\UserLike;
use Hybridauth\Exception;
use Kisma\Core\Utility\Option;

/**
 * Facebook
 * A facebook provider
 */
class Facebook extends BaseOAuthProvider
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @var string
	 */
	const DEFAULT_SCOPE = 'email,user_about_me,user_birthday,user_hometown,user_website,read_stream,offline_access,publish_stream,read_friendlists';

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Returns this user as a GenericUser
	 *
	 * @param \stdClass|array $profile
	 *
	 * @throws \InvalidArgumentException
	 * @return UserLike
	 */
	public function toGenericUser( $profile = null )
	{
		$_contact = new GenericUser();
		$_contact->setProviderId( 'facebook' );

		$_profile = $profile ? : $this->get( 'user_data' );

		if ( empty( $_profile ) )
		{
			throw new \InvalidArgumentException( 'No profile available to convert.' );
		}

		$_profileId = Option::get( $_profile, 'id' );

		$_name = array(
			'formatted'  => Option::get( $_profile, 'name' ),
			'familyName' => Option::get( $_profile, 'last_name' ),
			'givenName'  => Option::get( $_profile, 'first_name' ),
		);

		return $_contact
			   ->setUserId( $_profileId )
			   ->setPublished( Option::get( $_profile, 'updated_time' ) )
			   ->setUpdated( Option::get( $_profile, 'updated_time' ) )
			   ->setDisplayName( $_name['formatted'] )
			   ->setName( $_name )
			   ->setPreferredUsername( Option::get( $_profile, 'username' ) )
			   ->setGender( Option::get( $_profile, 'gender' ) )
			   ->setEmails( array( Option::get( $_profile, 'email' ) ) )
			   ->setUrls( array( Option::get( $_profile, 'link' ) ) )
			   ->setRelationships( Option::get( $_profile, 'friends' ) )
			   ->setPhotos( array( static::BASE_API_URL . '/' . $_profileId . '/picture?width=150&height=150' ) )
			   ->setUserData( $_profile );
	}

	/**
	 * @throws ProviderLikeException
	 * @return UserLike
	 */
	public function getUserData()
	{
		if ( false === ( $_response = $this->fetch( '/me' ) ) || isset( $_response->error ) || !isset( $_response->id ) )
		{
			throw new ProviderLikeException( 'Error retrieving authenticated resource.' );
		}

		$_data = static::toGenericUser( $_response );

		$profile = new Profile();

		$profile->setIdentifier( $parser( 'id' ) );
		$profile->setFirstName( $parser( 'first_name' ) );
		$profile->setLastName( $parser( 'last_name' ) );
		$profile->setDisplayName( $parser( 'name' ) );
		$profile->setProfileURL( $parser( 'link' ) );
		$profile->setWebSiteURL( $parser( 'website' ) );
		$profile->setGender( $parser( 'gender' ) );
		$profile->setDescription( $parser( 'bio' ) );
		$profile->setEmail( $parser( 'email' ) );
		$profile->setLanguage( $parser( 'locale' ) );
		$profile->setPhotoURL( 'https://graph.facebook.com/' . $profile->getIdentifier() . '/picture?width=150&height=150' );

		if ( $parser( 'birthday' ) )
		{
			list ( $m, $d, $y ) = explode( "/", $parser( 'birthday' ) );

			$profile->setBirthDay( $d );
			$profile->setBirthMonth( $m );
			$profile->setBirthYear( $y );
		}

		if ( $parser( 'verified' ) )
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
	 *    $data = $hybridauth->authenticate( "Facebook" )->getUserContacts();
	 */
	function getUserContacts()
	{
		$response = $this->signedRequest( 'me/friends' );
		$response = json_decode( $response );

		// Provider Errors shall not pass silently
		if ( !$response || isset( $response->error ) )
		{
			throw new
			Exception(
				'User contacts request failed: Provider returned an invalid response. ' .
				'HTTP client state: (' . $this->httpClient->getState() . ')',
				Exception::USER_CONTACTS_REQUEST_FAILED,
				$this
			);
		}

		$parser = function ( $property ) use ( $response )
		{
			return property_exists( $response, $property ) ? $response->$property : null;
		};

		$contacts = array();

		if ( isset( $response->data ) && is_array( $response->data ) )
		{
			foreach ( $response->data as $item )
			{
				$uc = new Profile();

				$profile->setIdentifier( $parser( 'id' ) );
				$profile->setDisplayName( $parser( 'name' ) );
				$profile->setProfileURL( 'https://www.facebook.com/profile.php?id=' . $profile->getIdentifier() );
				$profile->setPhotoURL( 'https://graph.facebook.com/' . $profile->getIdentifier() . '/picture?width=150&height=150' );

				$contacts [] = $uc;
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
	 *    $data = $hybridauth->authenticate( "Facebook" )->setUserStatus( _STATUS_ );
	 *
	 *    $data = $hybridauth->authenticate( "Facebook" )->setUserStatus( _PARAMS_ );
	 */
	function setUserStatus( $status )
	{
		throw new Exception( "Unsupported", Exception::UNSUPPORTED_FEATURE, null, $this );
	}

	/**
	 * Checks to see if user is authorized with this provider
	 *
	 * @return bool
	 */
	public function authorized()
	{
		return $this->_client->authorized();
	}

	/**
	 * Unlink/disconnect/logout user from provider locally.
	 * Does nothing on the provider end
	 *
	 * @return void
	 */
	public function deauthorize()
	{
		$this->_client->deauthorize();
	}
}
