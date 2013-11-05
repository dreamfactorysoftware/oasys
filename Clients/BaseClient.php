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
namespace DreamFactory\Oasys\Clients;

use DreamFactory\Oasys\Configs\BaseProviderConfig;
use DreamFactory\Oasys\Enums\DataFormatTypes;
use DreamFactory\Oasys\Exceptions\AuthenticationException;
use DreamFactory\Oasys\Enums\EndpointTypes;
use DreamFactory\Oasys\Interfaces\ProviderClientLike;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Option;
use Kisma\Core\Utility\Xml;

/**
 * BaseClient
 *
 * An abstract base class for customized authentication clients.
 * Provides basic fetch() method with hook for adding authentication headers and
 * simple request/response translation.
 */
abstract class BaseClient extends Seed implements ProviderClientLike
{
	//**************************************************************************
	//* Members
	//**************************************************************************

	/**
	 * @var BaseProviderConfig
	 */
	protected $_config = null;
	/**
	 * @var int The format of data sent to the provider
	 */
	protected $_requestFormat = DataFormatTypes::RAW;
	/**
	 * @var int The format of data received by the provider
	 */
	protected $_responseFormat = DataFormatTypes::JSON;
	/**
	 * @var mixed The last response from the provider
	 */
	protected $_lastResponse = null;
	/**
	 * @var int The last HTTP response from the provider
	 */
	protected $_lastResponseCode = null;
	/**
	 * @var string The provider's last error returned
	 */
	protected $_lastError = null;
	/**
	 * @var int The provider's last error code returned
	 */
	protected $_lastErrorCode = null;

	//**************************************************************************
	//* Methods
	//**************************************************************************

	/**
	 * @param BaseProviderConfig $config
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\OasysConfigurationException
	 * @return \DreamFactory\Oasys\Clients\BaseClient
	 */
	public function __construct( $config )
	{
		parent::__construct( array( 'config' => $config ) );
	}

	/**
	 * Fetch a protected resource
	 *
	 * @param string $resource
	 * @param array  $payload
	 * @param string $method
	 * @param array  $headers
	 *
	 * @param array  $curlOptions
	 *
	 * @return array
	 */
	public function fetch( $resource, $payload = array(), $method = self::Get, array $headers = array(), array $curlOptions = array() )
	{
		$_headers = $headers ? : array();
		$_payload = $payload ? : array();

		//	Get the service endpoint and make the url spiffy
		if ( false === strpos( $resource, 'http://', 0 ) && false === strpos( $resource, 'https://', 0 ) )
		{
			$_endpoint = $this->_config->getEndpoint( EndpointTypes::SERVICE );
			$_url = rtrim( $_endpoint['endpoint'], '/' ) . '/' . ltrim( $resource, '/' );
		}
		else
		{
			//	Use given url
			$_url = $_endpoint = $resource;
		}

		//	Add pre-defined endpoint parameters, if any
		if ( null !== ( $_parameters = Option::get( $_endpoint, 'parameters' ) ) && is_array( $_payload ) && is_array( $_parameters ) )
		{
			$_payload = array_merge(
				$_parameters,
				$_payload
			);
		}

		//	Add any authentication headers/parameters required by the provider
		$this->_getAuthParameters( $_headers, $_payload );

		//	Make the actual HTTP request
		return $this->_makeRequest( $_url, $_payload, $method, $_headers, $curlOptions );
	}

	/**
	 * Execute a request
	 *
	 * @param string $url         Request URL
	 * @param mixed  $payload     The payload to send
	 * @param string $method      The HTTP method to send
	 * @param array  $headers     Array of HTTP headers to send in array( 'header: value', 'header: value', ... ) format
	 * @param array  $curlOptions Array of options to pass to CURL
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\AuthenticationException
	 * @return array
	 */
	protected function _makeRequest( $url, array $payload = array(), $method = self::Get, array $headers = array(), array $curlOptions = array() )
	{
		static $_defaultCurlOptions = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
		);

		//	Start clean...
		$this->_resetRequest();

		//	Add in any user-supplied CURL options
		$_curlOptions = array_merge( $_defaultCurlOptions, $curlOptions );

		//	Add certificate info for SSL
		if ( null !== ( $_certificateFile = $this->getConfig( 'certificate_file' ) ) )
		{
			$_curlOptions[CURLOPT_SSL_VERIFYPEER] = true;
			$_curlOptions[CURLOPT_SSL_VERIFYHOST] = 2;
			$_curlOptions[CURLOPT_CAINFO] = $_certificateFile;
		}

		//	And finally our headers
		if ( null !== ( $_agent = $this->getConfig( 'user_agent' ) ) )
		{
			$headers[] = 'User-Agent: ' . $_agent;
		}

		$_curlOptions[CURLOPT_HTTPHEADER] = $headers;

		//	Convert payload to query string for a GET
		if ( static::Get == $method && !empty( $payload ) )
		{
			$url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . http_build_query( $payload );
			$payload = array();
		}

		//	And finally make the request
		if ( false === ( $_result = Curl::request( $method, $url, $this->_translatePayload( $payload, false ), $_curlOptions ) ) )
		{
			throw new AuthenticationException( Curl::getErrorAsString() );
		}

		//	Save off response
		$this->_lastResponseCode = $_code = Curl::getLastHttpCode();

		//	Shift result from array...
		if ( is_array( $_result ) && isset( $_result[0] ) && sizeof( $_result ) == 1 && $_result[0] instanceof \stdClass )
		{
			$_result = $_result[0];
		}

		$_contentType = Curl::getInfo( 'content_type' );

		if ( DataFormatTypes::JSON == $this->_responseFormat && false !== stripos( $_contentType, 'application/json', 0 ) )
		{
			$_result = $this->_translatePayload( $_result );
		}

		return $this->_lastResponse = array(
			'result'       => $_result,
			'code'         => $_code,
			'content_type' => $_contentType,
		);
	}

	/**
	 * Called before a request to get any additional auth header(s) or payload parameters
	 * (query string for non-POST-type requests) needed for the call.
	 *
	 * Append them to the $headers array as strings in "header: value" format:
	 *
	 * <code>
	 *        $_contentType = 'Content-Type: application/json';
	 *        $_origin = 'Origin: teefury.com';
	 *
	 *        $headers[] = $_contentType;
	 *        $headers[] = $_origin;
	 * </code>
	 *
	 * and/or append them to the $payload array in $key => $value format:
	 *
	 * <code>
	 *        $payload['param1'] = 'value1';
	 *        $payload['param2'] = 'value2';
	 *        $payload['param3'] = 'value3';
	 * </code>
	 *
	 * @param array $headers The current headers that are going to be sent
	 * @param array $payload
	 */
	abstract protected function _getAuthParameters( &$headers = array(), &$payload = array() );

	/**
	 * @param array $payload
	 * @param bool  $response   If true, the $responseFormat will be used, otherwise the $requestFormat
	 * @param bool  $assocArray If true, decoded JSON is returned in an array
	 *
	 * @return array|string
	 */
	protected function _translatePayload( $payload = array(), $response = true, $assocArray = true )
	{
		$_format = $response ? $this->_responseFormat : $this->_requestFormat;
		$_payload = $payload;

		switch ( $_format )
		{
			case DataFormatTypes::JSON:
				if ( true === $response )
				{
					if ( is_string( $_payload ) && !empty( $_payload ) )
					{
						$_payload = json_decode( $payload, $assocArray );
					}
				}
				else
				{
					if ( !is_string( $_payload ) )
					{
						$_payload = json_encode( $_payload );
					}
				}

				if ( false === $_payload )
				{
					//	Revert because it failed to go to JSON
					$_payload = $payload;
				}
				break;

			case DataFormatTypes::XML:
				if ( is_object( $payload ) )
				{
					$_payload = Xml::fromObject( $payload );
				}
				else if ( is_array( $payload ) )
				{
					$_payload = Xml::fromArray( $payload );
				}
				break;

		}

		return $_payload;
	}

	/**
	 * Clean up members for a new request
	 */
	protected function _resetRequest()
	{
		$this->_lastResponse = $this->_lastResponseCode = $this->_lastError = $this->_lastErrorCode = null;
	}

	/**
	 * @param string $property
	 * @param mixed  $value
	 * @param bool   $overwrite
	 *
	 * @return $this
	 */
	public function setConfig( $property, $value = null, $overwrite = true )
	{
		if ( $property instanceof BaseProviderConfig )
		{
			$this->_config = $property;
		}
		else if ( is_string( $property ) )
		{
			Option::set( $this->_config, $property, $value, $overwrite );
		}

		return $this;
	}

	/**
	 * @param string $property
	 * @param mixed  $defaultValue
	 * @param bool   $burnAfterReading
	 *
	 * @return ProviderConfigLike|array
	 */
	public function getConfig( $property = null, $defaultValue = null, $burnAfterReading = false )
	{
		if ( null !== $property )
		{
			return Option::get( $this->_config, $property, $defaultValue, $burnAfterReading );
		}

		return $this->_config;
	}

	/**
	 * @param string $lastError
	 *
	 * @return BaseClient
	 */
	public function setLastError( $lastError )
	{
		$this->_lastError = $lastError;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLastError()
	{
		return $this->_lastError;
	}

	/**
	 * @param int $lastErrorCode
	 *
	 * @return BaseClient
	 */
	public function setLastErrorCode( $lastErrorCode )
	{
		$this->_lastErrorCode = $lastErrorCode;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLastErrorCode()
	{
		return $this->_lastErrorCode;
	}

	/**
	 * @param mixed $lastResponse
	 *
	 * @return BaseClient
	 */
	public function setLastResponse( $lastResponse )
	{
		$this->_lastResponse = $lastResponse;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getLastResponse()
	{
		return $this->_lastResponse;
	}

	/**
	 * @param mixed $lastResponseCode
	 *
	 * @return BaseClient
	 */
	public function setLastResponseCode( $lastResponseCode )
	{
		$this->_lastResponseCode = $lastResponseCode;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getLastResponseCode()
	{
		return $this->_lastResponseCode;
	}

	/**
	 * @param int $requestFormat
	 *
	 * @return BaseClient
	 */
	public function setRequestFormat( $requestFormat )
	{
		$this->_requestFormat = $requestFormat;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getRequestFormat()
	{
		return $this->_requestFormat;
	}

	/**
	 * @param int $responseFormat
	 *
	 * @return BaseClient
	 */
	public function setResponseFormat( $responseFormat )
	{
		$this->_responseFormat = $responseFormat;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getResponseFormat()
	{
		return $this->_responseFormat;
	}

}
