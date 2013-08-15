<?php
/**
 * Copyright 2013 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace DreamFactory\Platform\Services\Portal;

use DreamFactory\Platform\Enums\PlatformServiceTypes;
use DreamFactory\Platform\Resources\BaseSystemRestResource;
use DreamFactory\Platform\Services\BasePlatformService;
use DreamFactory\Oasys\OAuth\Exceptions\AuthenticationException;
use Kisma\Core\Interfaces\ConsumerLike;
use Kisma\Core\Interfaces\HttpMethod;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;

/**
 * BasePortalClient
 * An portal client base that knows how to talk OAuth2
 *
 * Subclasses must implement _loadToken and _saveToken methods
 */
abstract class BasePortalClient extends BaseSystemRestResource implements ConsumerLike, HttpMethod
{
	//**************************************************************************
	//* Constants
	//**************************************************************************

	/**
	 * @const int
	 */
	const ApplicationContent = 0;
	/**
	 * @const int
	 */
	const MultipartContent = 1;
	/**
	 * @const string
	 */
	const DEFAULT_REDIRECT_URI = 'https://api.cloud.dreamfactory.com/oauth/authorize';

	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var string An optional user agent to send
	 */
	protected $_userAgent = null;
	/**
	 * @var string The base URL for this service's authentication server (i.e. https://oauth.server.com)
	 */
	protected $_authEndpoint = null;
	/**
	 * @var string The base URL for this service (i.e. https://api.server.com)
	 */
	protected $_serviceEndpoint = null;
	/**
	 * @var string The base URL for authenticated resource requests, if different from service URL (i.e. Github's are different)
	 */
	protected $_resourceEndpoint = null;
	/**
	 * @var string The client certificate file
	 */
	protected $_certificateFile = null;

	//**************************************************************************
	//* Methods
	//**************************************************************************

	/**
	 * @param \DreamFactory\Platform\Services\BasePlatformService $consumer
	 * @param array|\stdClass                                     $options
	 *
	 * @throws \RuntimeException
	 * @return \DreamFactory\Platform\Services\Portal\BasePortalClient
	 */
	public function __construct( $consumer, $options = array() )
	{
		if ( !extension_loaded( 'curl' ) )
		{
			throw new \RuntimeException( 'The "php-curl" extension is required to use this class.' );
		}

		//	Auto-set some stuff
		$options['api_name'] = $this->_apiName
			? :
			Option::get(
				$options,
				'api_name',
				str_ireplace( array( 'portal', 'client' ), null, Inflector::neutralize( get_class( $this ) ) )
			);

		$options['type'] = $this->_type ? : Option::get( $options, 'type', 'Local Portal Service' );
		$options['type_id'] = $this->_typeId ? : Option::get( $options, 'type_id', PlatformServiceTypes::LOCAL_PORTAL_SERVICE );

		parent::__construct( $consumer, $options );
	}

	/**
	 * Execute a request
	 *
	 * @param string $url
	 * @param mixed  $payload
	 * @param string $method
	 * @param array  $headers Array of HTTP headers to send in array( 'header: value', 'header: value', ... ) format
	 * @param int    $contentType
	 *
	 * @throws AuthenticationException
	 * @internal param array $_headers HTTP Headers
	 * @return array
	 */
	protected function _makeRequest( $url, $payload = array(), $method = self::Get, array $headers = null, $contentType = self::MultipartContent )
	{
		$headers = Option::clean( $headers );

		if ( !empty( $this->_userAgent ) )
		{
			$headers[] = 'User-Agent: ' . $this->_userAgent;
		}

		$_curlOptions = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_HTTPHEADER     => $headers,
		);

		if ( static::Get == $method && false === strpos( $url, '?' ) && !empty( $payload ) )
		{
			$url .= '?' . ( is_array( $payload ) ? http_build_query( $payload, null, '&' ) : $payload );
			$payload = array();
		}

		if ( !empty( $this->_certificateFile ) )
		{
			$_curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
			$_curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
			$_curlOptions[CURLOPT_CAINFO] = $this->_certificateFile;
		}

		if ( false === ( $_result = Curl::request( $method, $url, $payload, $_curlOptions ) ) )
		{
			throw new AuthenticationException( Curl::getErrorAsString() );
		}

		return array(
			'result'       => $_result,
			'code'         => Curl::getLastHttpCode(),
			'content_type' => Curl::getInfo( 'content_type' ),
		);
	}

	/**
	 * Given a path, build a full url
	 *
	 * @param string|null $path
	 *
	 * @return string
	 */
	public function getServiceEndpoint( $path = null )
	{
		return rtrim( $this->_serviceEndpoint, '/ ' ) . '/' . ltrim( $path, '/ ' );
	}

	/**
	 * @param null $resourceEndpoint
	 *
	 * @return BasePortalClient
	 */
	public function setResourceEndpoint( $resourceEndpoint = null )
	{
		$this->_resourceEndpoint = $resourceEndpoint;

		return $this;
	}

	/**
	 * Given a path, build a full url to the resource.
	 * Falls back to the service endpoint if no resource endpoint has been set
	 *
	 * @param string|null $path
	 *
	 * @return string
	 */
	public function getResourceEndpoint( $path = null )
	{
		if ( empty( $this->_resourceEndpoint ) )
		{
			return $this->getServiceEndpoint( $path );
		}

		return rtrim( $this->_resourceEndpoint, '/ ' ) . '/' . ltrim( $path, '/ ' );
	}

	/**
	 * @param mixed $userAgent
	 *
	 * @return BasePortalClient
	 */
	public function setUserAgent( $userAgent )
	{
		$this->_userAgent = $userAgent;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getUserAgent()
	{
		return $this->_userAgent;
	}

	/**
	 * @param string $serviceEndpoint
	 *
	 * @return BasePortalClient
	 */
	public function setServiceEndpoint( $serviceEndpoint )
	{
		$this->_serviceEndpoint = $serviceEndpoint;

		return $this;
	}

	/**
	 * @param string $certificateFile
	 *
	 * @return BasePortalClient
	 */
	public function setCertificateFile( $certificateFile )
	{
		$this->_certificateFile = $certificateFile;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCertificateFile()
	{
		return $this->_certificateFile;
	}

	/**
	 * @param string $authEndpoint
	 *
	 * @return BasePortalClient
	 */
	public function setAuthEndpoint( $authEndpoint )
	{
		$this->_authEndpoint = $authEndpoint;

		return $this;
	}

	/**
	 * Given a path, build a full url
	 *
	 * @param string|null $path
	 *
	 * @return string
	 */
	public function getAuthEndpoint( $path = null )
	{
		if ( empty( $this->_authEndpoint ) )
		{
			return $this->getServiceEndpoint( $path );
		}

		return rtrim( $this->_authEndpoint, '/ ' ) . '/' . ltrim( $path, '/ ' );
	}

}
