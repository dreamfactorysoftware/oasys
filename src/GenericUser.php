<?php
namespace DreamFactory\Oasys;

use DreamFactory\Oasys\Interfaces\OasysUser;
use Kisma\Core\Exceptions\NotImplementedException;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Convert;
use Kisma\Core\Utility\Option;

/**
 * GenericUser
 * A base class for a user base on the Portable Contact format (poco). See http://portablecontacts.net/ for more information.
 */
class GenericUser extends Seed implements OasysUser
{
	//*************************************************************************
	//* Members
	//*************************************************************************

	/**
	 * @var string The provider's name/ID
	 */
	protected $_providerId;
	/**
	 * @var string The provider's ID for the user
	 */
	protected $_userId;
	/**
	 * @var string This guy's etag
	 */
	protected $_etag;
	/**
	 * @var string Primary email
	 */
	protected $_emailAddress;
	/**
	 * @var string The date this Contact was first added to the user's address book or friends list (i.e. the creation date of this entry).
	 * The value MUST be a valid xs:dateTime (e.g. 2008-01-23T04:56:22Z).
	 */
	protected $_published;
	/**
	 * @var string The most recent date the details of this Contact were updated (i.e. the modified date of this entry).
	 * The value MUST be a valid xd:dateTime (e.g. 2008-01-23T04:56:22Z). If this Contact has never been modified since its initial creation,
	 * the value MUST be the same as the value of published.
	 */
	protected $_updated;
	/**
	 * @var string The name of this Contact, suitable for display to end-users. Each Contact returned MUST
	 * include a non-empty displayName value. The name SHOULD be the full name of the Contact being described if known (e.g. Joseph Smarr or
	 * Mr. Joseph Robert Smarr, Esq.), but MAY be a username or handle, if that is all that is available (e.g. jsmarr). The value provided
	 * SHOULD be the primary textual label by which this Contact is normally displayed by the Service Provider when presenting it to end-users.
	 */
	protected $_displayName;
	/**
	 * @var array The broken-out components and fully formatted version of the contact's real name, as described in Section 5.3.
	 */
	protected $_name = array();
	/**
	 * @var string The casual way to address this Contact in real life, e.g. "Bob" or "Bobby" instead of "Robert". This field SHOULD NOT be used to represent a user's username (e.g. jsmarr or daveman692); the latter should be represented by the preferredUsername field.
	 */
	protected $_nickname;
	/**
	 * @var string The birthday of this contact. The value MUST be a valid xs:date (e.g. 1975-02-14. The year
	 * value MAY be set to 0000 when
	 * the age of the Contact is private or the year is not available.
	 */
	protected $_birthday;
	/**
	 * @var string The wedding anniversary of this contact. The value MUST be a valid xs:date (e.g. 1975-02-14.
	 * The year value MAY be set to
	 * 0000 when the year is not available.
	 */
	protected $_anniversary;
	/**
	 * @var string The gender of this contact. Service Providers SHOULD return one of the following Canonical
	 * Values, if appropriate: male,
	 * female, or undisclosed, and MAY return a different value if it is not covered by one of these Canonical
	 * Values.
	 */
	protected $_gender;
	/**
	 * @var string Notes about this contact, with an unspecified meaning or usage (normally contact notes by
	 * the user about this contact).
	 * This field MAY contain newlines.
	 */
	protected $_note;
	/**
	 * @var string The preferred username of this contact on sites that ask for a username (e.g. jsmarr or
	 * daveman692). This field may be
	 * more useful for describing the owner (i.e. the value when /@me/@self is requested) than the user's
	 * contacts,
	 * e.g. Consumers MAY wish to use this value to pre-populate a username for this user when signing up for a
	 * new service.
	 */
	protected $_preferredUsername;
	/**
	 * @var mixed The data feed of contacts for this contact
	 */
	protected $_dataFeed = null;
	/**
	 * @var string The offset from UTC of this Contact's current time zone, as of the time this response was
	 * returned. The value MUST conform
	 * to the offset portion of xs:dateTime, e.g. -08:00. Note that this value MAY change over time due to
	 * daylight saving time,
	 * and is thus meant to signify only the current value of the user's timezone offset.
	 */
	protected $_utcOffset;
	/**
	 * @var bool Boolean value indicating whether the user and this Contact have established a bi-directionally asserted connection of some kind on the Service Provider's service. The value MUST be either true or false. The value MUST be true if and only if there is at least one value for the relationship field, described below, and is thus intended as a summary value indicating that some type of bi-directional relationship exists, for Consumers that aren't interested in the specific nature of that relationship. For traditional address books, in which a user stores information about other contacts without their explicit acknowledgment, or for services in which users choose to "follow" other users without requiring mutual consent, this value will always be false.
	 */
	protected $_connected = false;
	/**
	 * Plural fields
	 */
	/**
	 * @var mixed E-mail address for this Contact. The value SHOULD be canonicalized by the Service Provider,
	 * e.g. joseph@plaxo.com instead of joseph@PLAXO.COM.
	 */
	protected $_emails = array();
	/**
	 * @var mixed URL of a web page relating to this Contact. The value SHOULD be canonicalized by the Service
	 * Provider,
	 * e.g. http://josephsmarr.com/about/ instead of JOSEPHSMARR.COM/about/. In addition to the standard
	 * Canonical Values for type,
	 * this field also defines the additional Canonical Values blog and profile.
	 */
	protected $_urls = array();
	/**
	 * @var mixed Phone number for this Contact. No canonical value is assumed here. In addition to the
	 * standard Canonical Values for
	 * type, this field also defines the additional Canonical Values mobile, fax, and pager.
	 */
	protected $_phoneNumbers = array();
	/**
	 * @var mixed Instant messaging address for this Contact. No official canonicalization rules exist for all
	 * instant messaging
	 * addresses, but Service Providers SHOULD remove all whitespace and convert the address to lowercase,
	 * if this is appropriate for the service this IM address is used for. Instead of the standard Canonical
	 * Values for type,
	 * this field defines the following Canonical Values to represent currently popular IM services: aim,
	 * gtalk, icq, xmpp, msn, skype, qq,
	 * and yahoo.
	 */
	protected $_ims = array();
	/**
	 * @var mixed URL of a photo of this contact. The value SHOULD be a canonicalized URL,
	 * and MUST point to an actual image file (e.g. a
	 * GIF, JPEG, or PNG image file) rather than to a web page containing an image. Service Providers MAY
	 * return the same image at different
	 * sizes, though it is recognized that no standard for describing images of various sizes currently exists.
	 * Note that this field SHOULD
	 * NOT be used to send down arbitrary photos taken by this user, but specifically profile photos of the
	 * contact suitable for display when
	 * describing the contact.
	 */
	protected $_photos = array();
	/**
	 * @var mixed A user-defined category or label for this contact, e.g. "favorite" or "web20". These values
	 * SHOULD be case-insensitive, and there SHOULD NOT be multiple tags provided for a given contact that differ
	 * only in case. Note that this field is a Simple Field, meaning each instance consists only of a string value.
	 */
	protected $_tags = array();
	/**
	 * @var mixed A bi-directionally asserted relationship type that was established between the user and this
	 * contact by the Service
	 * Provider. The value SHOULD conform to one of the XFN relationship values (e.g. kin, friend, contact,
	 * etc.) if appropriate,
	 * but MAY be an alternative value if needed. Note this field is a parallel set of category labels to the
	 * tags field,
	 * but relationships MUST have been bi-directionally confirmed, whereas tags are asserted by the user
	 * without acknowledgment by this
	 * Contact. Note that this field is a Simple Field, meaning each instance consists only of a string value.
	 */
	protected $_relationships = array();
	/**
	 * @var mixed A physical mailing address for this Contact, as described in Section 5.4.
	 */
	protected $_addresses = array();
	/**
	 * @var mixed A current or past organizational affiliation of this Contact, as described in Section 5.5.
	 */
	protected $_organizations = array();
	/**
	 * @var mixed An online account held by this Contact, as described in Section 5.6.
	 */
	protected $_accounts = array();
	/**
	 * @var string The user's profile url
	 */
	protected $_profileUrl;
	/**
	 * @var string
	 */
	protected $_thumbnailUrl;
	/**
	 * @var array
	 */
	protected $_groupMemberships = array();
	/**
	 * @var array
	 */
	protected $_extendedProperties = array();
	/**
	 * @var array
	 */
	protected $_employer
		= array(
			'companyName' => null,
			'title'       => null,
		);
	/**
	 * @var mixed Either the actual source data from the provider or a user-defined value. Not part of the PoCo spec.
	 */
	protected $_source;

	//*************************************************************************
	//* Methods
	//*************************************************************************

	/**
	 * @param \stdClass $feed
	 *
	 * @return array
	 */
	public static function fromOpenSocialFeed( $feed )
	{
		$_contacts = array();

		/** @var $_entry \stdClass */
		foreach ( $feed->entry as $_key => $_entry )
		{
			$_contact = new GenericUser( $_entry );

			/**
			 * System fields
			 */
			$_contact->setUserId( $_entry->id );
			$_contact->setProviderId( 'opensocial' );
			$_contacts[$_key] = $_contact;

			unset( $_entry, $_contact );
		}

		return $_contacts;
	}

	/**
	 * From a portable contact record
	 *
	 * @param \stdClass $poco
	 *
	 * @return GenericUser
	 */
	public static function fromPoCo( $poco )
	{
		return new GenericUser( $poco );
	}

	/**
	 * Parses a Google Data "entry" into a GenericUser
	 *
	 * @param \SimpleXMLElement|array $entry
	 * @param GenericUser             $contact
	 *
	 * @throws \Kisma\Core\Exceptions\NotImplementedException
	 * @return \GenericUser
	 */
	public static function fromGoogleEntry( $entry, $contact = null )
	{
		throw new NotImplementedException();
	}

	/**
	 * @param array|\stdClass $contact
	 *
	 * @throws NotImplementedException
	 */
	public function fromYahoo( $contact )
	{
		throw new NotImplementedException();
	}

	/**
	 * @param mixed $accounts
	 *
	 * @return GenericUser
	 */
	public function setAccounts( $accounts )
	{
		$this->_accounts = $accounts;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAccounts()
	{
		return $this->_accounts;
	}

	/**
	 * @param mixed $addresses
	 *
	 * @return GenericUser
	 */
	public function setAddresses( $addresses )
	{
		$this->_addresses = $addresses;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAddresses()
	{
		return $this->_addresses;
	}

	/**
	 * @param string $anniversary
	 *
	 * @return GenericUser
	 */
	public function setAnniversary( $anniversary )
	{
		$this->_anniversary = $anniversary;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAnniversary()
	{
		return $this->_anniversary;
	}

	/**
	 * @param string $birthday
	 *
	 * @return GenericUser
	 */
	public function setBirthday( $birthday )
	{
		$this->_birthday = $birthday;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBirthday()
	{
		return $this->_birthday;
	}

	/**
	 * @param boolean $connected
	 *
	 * @return GenericUser
	 */
	public function setConnected( $connected )
	{
		$this->_connected = $connected;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getConnected()
	{
		return $this->_connected;
	}

	/**
	 * @param mixed $dataFeed
	 *
	 * @return GenericUser
	 */
	public function setDataFeed( $dataFeed )
	{
		$this->_dataFeed = $dataFeed;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getDataFeed()
	{
		return $this->_dataFeed;
	}

	/**
	 * @param string $displayName
	 *
	 * @return GenericUser
	 */
	public function setDisplayName( $displayName )
	{
		$this->_displayName = $displayName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getDisplayName()
	{
		return $this->_displayName;
	}

	/**
	 * @param string $emailAddress
	 *
	 * @return GenericUser
	 */
	public function setEmailAddress( $emailAddress )
	{
		$this->_emailAddress = $emailAddress;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEmailAddress()
	{
		return $this->_emailAddress;
	}

	/**
	 * @param mixed $emails
	 *
	 * @return GenericUser
	 */
	public function setEmails( $emails )
	{
		$this->_emails = $emails;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEmails()
	{
		return $this->_emails;
	}

	/**
	 * @param array $employer
	 *
	 * @return GenericUser
	 */
	public function setEmployer( $employer )
	{
		$this->_employer = $employer;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getEmployer()
	{
		return $this->_employer;
	}

	/**
	 * @param string $etag
	 *
	 * @return GenericUser
	 */
	public function setEtag( $etag )
	{
		$this->_etag = $etag;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEtag()
	{
		return $this->_etag;
	}

	/**
	 * @param array $extendedProperties
	 *
	 * @return GenericUser
	 */
	public function setExtendedProperties( $extendedProperties )
	{
		$this->_extendedProperties = $extendedProperties;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getExtendedProperties()
	{
		return $this->_extendedProperties;
	}

	/**
	 * @param string $gender
	 *
	 * @return GenericUser
	 */
	public function setGender( $gender )
	{
		$this->_gender = $gender;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getGender()
	{
		return $this->_gender;
	}

	/**
	 * @param array $groupMemberships
	 *
	 * @return GenericUser
	 */
	public function setGroupMemberships( $groupMemberships )
	{
		$this->_groupMemberships = $groupMemberships;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getGroupMemberships()
	{
		return $this->_groupMemberships;
	}

	/**
	 * @param mixed $ims
	 *
	 * @return GenericUser
	 */
	public function setIms( $ims )
	{
		$this->_ims = $ims;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getIms()
	{
		return $this->_ims;
	}

	/**
	 * @param array $name
	 *
	 * @return GenericUser
	 */
	public function setName( $name )
	{
		$this->_name = $name;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param string $nickname
	 *
	 * @return GenericUser
	 */
	public function setNickname( $nickname )
	{
		$this->_nickname = $nickname;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getNickname()
	{
		return $this->_nickname;
	}

	/**
	 * @param string $note
	 *
	 * @return GenericUser
	 */
	public function setNote( $note )
	{
		$this->_note = $note;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getNote()
	{
		return $this->_note;
	}

	/**
	 * @param mixed $organizations
	 *
	 * @return GenericUser
	 */
	public function setOrganizations( $organizations )
	{
		$this->_organizations = $organizations;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getOrganizations()
	{
		return $this->_organizations;
	}

	/**
	 * @param mixed $phoneNumbers
	 *
	 * @return GenericUser
	 */
	public function setPhoneNumbers( $phoneNumbers )
	{
		$this->_phoneNumbers = $phoneNumbers;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPhoneNumbers()
	{
		return $this->_phoneNumbers;
	}

	/**
	 * @param mixed $photos
	 *
	 * @return GenericUser
	 */
	public function setPhotos( $photos )
	{
		$this->_photos = $photos;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPhotos()
	{
		return $this->_photos;
	}

	/**
	 * @param string $preferredUsername
	 *
	 * @return GenericUser
	 */
	public function setPreferredUsername( $preferredUsername )
	{
		$this->_preferredUsername = $preferredUsername;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPreferredUsername()
	{
		return $this->_preferredUsername;
	}

	/**
	 * @param string $profileUrl
	 *
	 * @return GenericUser
	 */
	public function setProfileUrl( $profileUrl )
	{
		$this->_profileUrl = $profileUrl;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getProfileUrl()
	{
		return $this->_profileUrl;
	}

	/**
	 * @param string $published
	 *
	 * @return GenericUser
	 */
	public function setPublished( $published )
	{
		$this->_published = $published;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPublished()
	{
		return $this->_published;
	}

	/**
	 * @param mixed $relationships
	 *
	 * @return GenericUser
	 */
	public function setRelationships( $relationships )
	{
		$this->_relationships = $relationships;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getRelationships()
	{
		return $this->_relationships;
	}

	/**
	 * @param mixed $tags
	 *
	 * @return GenericUser
	 */
	public function setTags( $tags )
	{
		$this->_tags = $tags;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getTags()
	{
		return $this->_tags;
	}

	/**
	 * @param string $thumbnailUrl
	 *
	 * @return GenericUser
	 */
	public function setThumbnailUrl( $thumbnailUrl )
	{
		$this->_thumbnailUrl = $thumbnailUrl;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getThumbnailUrl()
	{
		return $this->_thumbnailUrl;
	}

	/**
	 * @param string $updated
	 *
	 * @return GenericUser
	 */
	public function setUpdated( $updated )
	{
		$this->_updated = $updated;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUpdated()
	{
		return $this->_updated;
	}

	/**
	 * @param mixed $urls
	 *
	 * @return GenericUser
	 */
	public function setUrls( $urls )
	{
		$this->_urls = $urls;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getUrls()
	{
		return $this->_urls;
	}

	/**
	 * @param string $utcOffset
	 *
	 * @return GenericUser
	 */
	public function setUtcOffset( $utcOffset )
	{
		$this->_utcOffset = $utcOffset;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUtcOffset()
	{
		return $this->_utcOffset;
	}

	/**
	 * @param string $providerId
	 *
	 * @return GenericUser
	 */
	public function setProviderId( $providerId )
	{
		$this->_providerId = $providerId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getProviderId()
	{
		return $this->_providerId;
	}

	/**
	 * @param string $userId
	 *
	 * @return GenericUser
	 */
	public function setUserId( $userId )
	{
		$this->_userId = $userId;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUserId()
	{
		return $this->_userId;
	}

	/**
	 * @return string
	 */
	public function getUserName()
	{
		return $this->_preferredUsername;
	}

	/**
	 * @param string $userName
	 *
	 * @return $this
	 */
	public function setUserName( $userName )
	{
		$this->_preferredUsername = $userName;

		return $this;
	}

	/**
	 * @return array|\stdClass The provider-supplied user profile for this user, if any
	 */
	public function getUserData()
	{
		return $this->_source;
	}

	/**
	 * @param array|\stdClass $userData
	 *
	 * @return $this
	 */
	public function setUserData( $userData = array() )
	{
		$this->_source = $userData;

		return $this;
	}
}
