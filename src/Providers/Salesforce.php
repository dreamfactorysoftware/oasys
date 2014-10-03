<?php
namespace DreamFactory\Oasys\Providers;

use DreamFactory\Oasys\Components\ForceContainer;
use DreamFactory\Oasys\Components\GenericUser;
use DreamFactory\Oasys\Configs\BaseProviderConfig;
use DreamFactory\Oasys\Enums\DataFormatTypes;
use DreamFactory\Oasys\Enums\EndpointTypes;
use DreamFactory\Oasys\Interfaces\ProviderConfigLike;
use DreamFactory\Platform\Exceptions\InternalServerErrorException;
use Kisma\Core\Enums\HttpResponse;
use Kisma\Core\Exceptions\HttpException;
use Kisma\Core\Utility\Convert;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\Option;

/**
 * Salesforce
 * A Salesforce.com provider
 */
class Salesforce extends BaseOAuthProvider
{
	//**************************************************************************
	//* Constants
	//**************************************************************************

	/**
	 * @var string
	 */
	const DEFAULT_SCOPE = 'api refresh_token web';
	/**
	 * @var string The version of the SF API to target
	 */
	const DEFAULT_API_VERSION = 'v29.0';
	/**
	 * @var string The starting/default instance
	 */
	const DEFAULT_INSTANCE_NAME = 'login.salesforce.com';
	/**
	 * @var string The starting/default sandbox instance
	 */
	const DEFAULT_SANDBOX_INSTANCE_NAME = 'test.salesforce.com';
	/**
	 * @var string The name of the property containing the continuation url
	 */
	const NEXT_RECORDS_URL = 'nextRecordsUrl';
	/**
	 * @var string
	 */
	const SERVICE_ENDPOINT_PATTERN = 'https://{{instance_name}}';
	/**
	 * @var string
	 */
	const API_VERSION_TAG = '{{api_version}}';

	//**************************************************************************
	//* Members
	//**************************************************************************

	/**
	 * @var string The name of our instance
	 */
	protected $_instanceName = null;
	/**
	 * @var string Can be used to both identify the user as well as query for more information about the user.
	 */
	protected $_identityUrl = null;
	/**
	 * @var string The continuation url
	 */
	protected $_nextRecordsUrl = null;
	/**
	 * @var int
	 */
	protected $_totalSize = null;
	/**
	 * @var int
	 */
	protected $_queryDone = null;
	/**
	 * @var bool If true, instance targets the sandbox
	 */
	protected $_useSandbox = false;
	/**
	 * @var string The API version to use
	 */
	protected $_apiVersion = self::DEFAULT_API_VERSION;

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * @param string                                $providerId
	 * @param ProviderConfigLike|BaseProviderConfig $config
	 * @param string                                $fromTemplate The template to use if different from $providerId
	 */
	public function __construct( $providerId, $config, $fromTemplate = 'salesforce' )
	{
		parent::__construct( $providerId, $config, $fromTemplate );

		//	Our data formats...
		$this->_responseFormat = DataFormatTypes::JSON;

		//	Pretend it's a payload...
		$this->_processReceivedToken( $config instanceof ProviderConfigLike ? $config->toArray() : $config );
	}

	/**
	 * Returns this user as a GenericUser
	 *
	 * @param \stdClass|array $profile
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\ProviderException
	 * @throws \InvalidArgumentException
	 * @return UserLike
	 */
	public function getUserData( $profile = null )
	{
		/** @noinspection PhpUndefinedMethodInspection */
		if ( null === ( $_url = $this->getIdentityUrl() ) )
		{
			Log::notice( 'No salesforce identity url.' );

			return new GenericUser();
		}

		$_profile = $this->fetch( $_url );

//		Log::debug( 'Profile retrieved: ' . print_r( $_profile, true ) );

		if ( empty( $_profile ) )
		{
			throw new \InvalidArgumentException( 'No profile available to convert.' );
		}

		$_profileId = Option::get( $_profile, 'user_id' );
		$_login = Option::get( $_profile, 'username' );

		$_name = array(
			'familyName' => Option::get( $_profile, 'last_name', $_login ),
			'givenName'  => Option::get( $_profile, 'first_name', $_login ),
			'formatted'  => Option::get( $_profile, 'display_name', $_login ),
		);

		return new GenericUser(
			array(
				'provider_id'        => $this->getProviderId(),
				'user_id'            => $_profileId,
				'published'          => Option::get( $_profile, 'last_modified_date' ),
				'display_name'       => $_name['formatted'],
				'name'               => $_name,
				'email_address'      => Option::get( $_profile, 'email' ),
				'preferred_username' => $_login,
				'urls'               => (array)Option::get( $_profile, 'urls', array() ),
				'thumbnail_url'      => Option::getDeep( $_profile, 'photos', 'thumbnail' ),
				'updated'            => Option::get( $_profile, 'last_modified_date' ),
				'relationships'      => array(),
				'user_data'          => $_profile,
			)
		);
	}

	/**
	 * @return bool|mixed
	 */
	public function getAvailableResources()
	{
		return $this->fetch( '/services/data/' . static::API_VERSION_TAG . '/' );
	}

	/**
	 * Executes a SOQL query and iterates through the data or returns the results
	 *
	 * @param string   $sql
	 * @param \Closure $callback
	 *
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	public function executePagedQuery( $sql, $callback = null )
	{
		if ( !is_callable( $callback ) )
		{
			throw new \InvalidArgumentException( 'The $callback value provided is not callable. Try again.' );
		}

		if ( false === ( $_rows = $this->executeQuery( $sql ) ) )
		{
			Log::error( 'Error: ' . $this->_lastError );

			return false;
		}

		$_resultSetCount = 0;

		if ( !empty( $_rows ) )
		{
			while ( true )
			{
				$_records = $_rows->getRecords();

				if ( empty( $_records ) || 0 == count( $_records ) )
				{
					Log::debug( 'Empty records object received, re-pulling' );
					break;
				}
				else
				{
					Log::debug( 'Result set #' . ++$_resultSetCount . ' contains ' . count( $_records ) . ' record(s)' );

					foreach ( $_records as $_record )
					{
						if ( !is_object( $_record ) )
						{
							Log::error( 'Data record returned from Salesforce is invalid: ' . print_r( $_record, true ) );
							continue;
						}

						//	Call the callback
						$callback( $_record );
					}
				}

				if ( $_rows->getDone() )
				{
					break;
				}

				$_nextUrl = $_rows->getNextRecordsUrl();
				Log::debug( 'Looking for next result set from: ' . $_nextUrl );

				$this->_nextRecordsUrl = $_nextUrl;
				unset( $_rows, $_records );

				$_rows = new ForceContainer( $this->getNextRecordSet() );
			}
		}
	}

	/**
	 * Retrieves the next set of records if a continuation url exists
	 *
	 * @return bool|mixed
	 */
	public function getNextRecordSet()
	{
		if ( null === ( $_url = $this->_nextRecordsUrl ) )
		{
			return false;
		}

		return $this->fetch( $_url );
	}

	/**
	 * @return bool|mixed
	 */
	public function getObjectList()
	{
		return $this->fetch( '/services/data/' . static::API_VERSION_TAG . '/sobjects/' );
	}

	/**
	 * @param $object
	 *
	 * @return bool|mixed
	 */
	public function getObjectMetaData( $object )
	{
		return $this->fetch( '/services/data/' . static::API_VERSION_TAG . '/sobjects/' . $object . '/' );
	}

	/**
	 * @param $object
	 *
	 * @return bool|mixed
	 */
	public function getObjectDescription( $object )
	{
		return $this->fetch( '/services/data/' . static::API_VERSION_TAG . '/sobjects/' . $object . '/describe/' );
	}

	/**
	 * @param       $object
	 * @param       $id
	 * @param array $fields
	 *
	 * @return bool|mixed
	 */
	public function getObject( $object, $id, $fields = array() )
	{
		return $this->fetch(
			'/services/data/' .
			static::API_VERSION_TAG .
			'/sobjects/' .
			$object .
			'/' .
			$id .
			( !empty( $fields ) ? '?fields=' . implode( ',', $fields ) : null )
		);
	}

	/**
	 * Response is always empty from this call. HTTP response code of 204 is success. Anything is an error.
	 *
	 * @param string $object
	 * @param string $id
	 * @param array  $fields
	 *
	 * @throws InternalServerErrorException
	 * @return bool|mixed
	 */
	public function updateObject( $object, $id, $fields = array() )
	{
		$_response = $this->fetch(
			'/services/data/' . static::API_VERSION_TAG . '/sobjects/' . $object . '/' . $id,
			json_encode( $fields ),
			static::Patch
		);

		//	Curl error is false...
		if ( false === $_response )
		{
			return false;
		}

		if ( HttpResponse::NoContent == Curl::getLastHttpCode() )
		{
			return true;
		}

		//	Sometimes they send back xml...
		if ( is_string( $_response ) && false !== stripos( $_response, '<?xml' ) )
		{
			try
			{
				if ( null === ( $_response = Convert::toObject( simplexml_load_string( $_response ) ) ) )
				{
					throw new InternalServerErrorException( 'Unrecognizable response from server: ' . print_r( $_response, true ) );
				}
				//	Otherwise we have a nice object which we return as json
			}
			catch ( \Exception $_ex )
			{
				//	error...
				Log::error( 'Exception parsing response: ' . print_r( $_response, true ) );
			}
		}

		return $_response;
	}

	/**
	 * @param $id
	 *
	 * @return bool|mixed
	 */
	public function getAttachment( $id )
	{
		return $this->fetch( '/services/data/' . static::API_VERSION_TAG . '/sobjects/attachment/' . $id . '/body' );
	}

	/**
	 * @param string $query
	 *
	 * @return ForceContainer
	 */
	public function executeQuery( $query )
	{
		if ( false === ( $_response = $this->fetch( '/services/data/' . static::API_VERSION_TAG . '/query/?q=' . urlencode( $query ) ) ) )
		{
			return false;
		}

		return new ForceContainer( $_response );
	}

	/**
	 * @param string $resource
	 * @param array  $payload
	 * @param string $method
	 * @param array  $headers
	 *
	 * @throws HttpException
	 * @return array|bool|mixed
	 */
	public function fetch( $resource, $payload = array(), $method = self::Get, array $headers = array() )
	{
		//	Dynamically insert Force API version
		$_resource = str_ireplace( static::API_VERSION_TAG, $this->_apiVersion ?: static::DEFAULT_API_VERSION, $resource );

		//	Add our default headers
		if ( false === array_search( 'X-PrettyPrint: 1', $headers ) )
		{
			$headers = array_merge(
				array(
					'Content-type: application/json',
					'X-PrettyPrint: 1'
				),
				$headers
			);
		}

		//	Send as JSON
		$this->_requestFormat = DataFormatTypes::JSON;

		//	Fetch the resource
		$_response = parent::fetch( $_resource, $payload, $method, $headers );

		//	Check the result
		$_result = Option::get( $_response, 'result' );
		$_code = Option::get( $_response, 'code' );

		//	Not a "denied" error...
		if ( $_code < 400 )
		{
			//	Grab the request meta-results
			$this->_totalSize = Option::get( $_result, 'totalSize' );
			$this->_queryDone = Option::get( $_result, 'done', 0 );
			$this->_nextRecordsUrl = Option::get( $_result, static::NEXT_RECORDS_URL );
		}

		$_error = null;

		if ( HttpResponse::NotFound == $_code )
		{
			$_error = 'Resource not found';
		}
		elseif ( is_array( $_result ) && isset( $_result[0]->message ) && isset( $_result[0]->errorCode ) )
		{
			$_error = $_result[0]->message . ' (' . $_result[0]->errorCode . ')';
		}
		else
		{
			return $_result;
		}

		$this->_lastError = $_error;
		$this->_lastErrorCode = $_code;

		throw new HttpException( $_code, $_error );
	}

	/**
	 * @param array|\stdClass $data
	 *
	 * @return bool|void
	 */
	protected function _processReceivedToken( $data )
	{
		$_data = $data;

		if ( !is_array( $data ) && !( $data instanceof \stdClass ) && !( $data instanceof \Traversable ) )
		{
			$_data = Option::clean( $data );

			if ( !empty( $_data ) )
			{
				$_data = current( $_data );
			}
		}

		foreach ( $_data as $_key => $_value )
		{
			switch ( $_key )
			{
				case 'instance_url':
					$this->_instanceName = trim( str_ireplace( array( 'https://', 'http://', '/' ), null, $_value ) );

					$_endpoint = $this->getConfig()->getEndpoint( EndpointTypes::SERVICE );
					$_endpoint['endpoint'] = str_ireplace( '{{instance_name}}', $this->_instanceName, static::SERVICE_ENDPOINT_PATTERN );
					$this->getConfig()->mapEndpoint( EndpointTypes::SERVICE, $_endpoint );
					break;

				case 'id':
					$this->_identityUrl = $_value;
					break;
			}
		}

		return parent::_processReceivedToken( $data );
	}

	/**
	 * @return string
	 */
	protected function _getDefaultInstance()
	{
		return ( $this->_useSandbox ? static::DEFAULT_SANDBOX_INSTANCE_NAME : static::DEFAULT_INSTANCE_NAME );
	}

	/**
	 * Clear out the last errors and stuff from last request
	 *
	 * @return void
	 */
	protected function _resetRequest()
	{
		parent::_resetRequest();

		$this->_queryDone = $this->_totalSize = $this->_nextRecordsUrl = null;
	}

	/**
	 * @param string $identityUrl
	 *
	 * @return $this
	 */
	public function setIdentityUrl( $identityUrl )
	{
		$this->_identityUrl = $identityUrl;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getIdentityUrl()
	{
		return $this->_identityUrl;
	}

	/**
	 * @param string $instanceName
	 *
	 * @return $this
	 */
	public function setInstanceName( $instanceName )
	{
		$this->_instanceName = $instanceName;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getInstanceName()
	{
		return $this->_instanceName;
	}

	/**
	 * @param string $nextRecordsUrl
	 *
	 * @return $this
	 */
	public function setNextRecordsUrl( $nextRecordsUrl )
	{
		$this->_nextRecordsUrl = $nextRecordsUrl;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getNextRecordsUrl()
	{
		return $this->_nextRecordsUrl;
	}

	/**
	 * @param int $queryDone
	 *
	 * @return $this
	 */
	public function setQueryDone( $queryDone )
	{
		$this->_queryDone = $queryDone;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getQueryDone()
	{
		return $this->_queryDone;
	}

	/**
	 * @param int $totalSize
	 *
	 * @return $this
	 */
	public function setTotalSize( $totalSize )
	{
		$this->_totalSize = $totalSize;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getTotalSize()
	{
		return $this->_totalSize;
	}

	/**
	 * @param boolean $useSandbox
	 *
	 * @return $this
	 */
	public function setUseSandbox( $useSandbox )
	{
		$this->_useSandbox = $useSandbox;

		return $this;
	}

	/**
	 * @return boolean
	 */
	public function getUseSandbox()
	{
		return $this->_useSandbox;
	}

	/**
	 * @return string
	 */
	public function getApiVersion()
	{
		return $this->_apiVersion;
	}

	/**
	 * Set the Salesforce API version to use. Defaults to client default, currently "v29.0"
	 *
	 * @param string $apiVersion
	 *
	 * @return $this
	 */
	public function setApiVersion( $apiVersion = self::DEFAULT_API_VERSION )
	{
		$this->_apiVersion = $apiVersion;

		return $this;
	}
}
