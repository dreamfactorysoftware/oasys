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
namespace DreamFactory\Oasys\Components\OAuth;

use DreamFactory\Oasys\Components\OAuth\Interfaces\LegacyOAuthServiceLike;
use DreamFactory\Oasys\Enums\EndpointTypes;
use DreamFactory\Oasys\Components\OAuth\Enums\Flows;
use DreamFactory\Oasys\Exceptions\OasysConfigurationException;
use DreamFactory\Oasys\Exceptions\RedirectRequiredException;
use DreamFactory\Oasys\Interfaces\ProviderConfigLike;
use DreamFactory\Oasys\Configs\LegacyOAuthProviderConfig;
use Kisma\Core\Exceptions\NotImplementedException;
use Kisma\Core\Interfaces\HttpMethod;
use Kisma\Core\Seed;
use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Log;
use Kisma\Core\Utility\FilterInput;
use Kisma\Core\Utility\Inflector;
use Kisma\Core\Utility\Option;

/**
 * LegacyOAuthClient
 * An base that knows how to talk OAuth2
 */
class LegacyOAuthClient extends Seed implements ProviderClientLike, LegacyOAuthServiceLike, HttpMethod
{
	//**************************************************************************
	//* Members
	//**************************************************************************

	/**
	 * @var LegacyOAuthProviderConfig|ProviderConfigLike
	 */
	protected $_config;

	//**************************************************************************
	//* Methods
	//**************************************************************************

	/**
	 * @param LegacyOAuthProviderConfig|ProviderConfigLike $config
	 *
	 * @throws \InvalidArgumentException
	 * @return \DreamFactory\Oasys\Components\OAuth\LegacyOAuthClient
	 */
	public function __construct( $config )
	{
		parent::__construct( $config );

		$this->_client = new \OAuth(
			$this->_config->getConsumerKey(),
			$this->_config->getConsumerSecret(),
			$this->_config->getSignatureMethod(),
			$this->_config->getAuthType()
		);

		//	Set up for fetchin'
		if ( 2 == $this->_config->getState() )
		{
			$this->_setToken();
		}
	}

	/**
	 * Check if we are authorized or not...
	 *
	 * @param bool $startFlow If true, and we are not authorized, checkAuthenticationProgress() is called.
	 *
	 * @return bool|string
	 */
	public function authorized( $startFlow = false )
	{
		$_token = $this->_config->getAccessToken();

		if ( empty( $_token ) )
		{
			if ( false !== $startFlow )
			{
				return $this->checkAuthenticationProgress();
			}

			return false;
		}

		return true;
	}

	/**
	 * Checks the progress of any in-flight OAuth requests
	 *
	 *
	 * @throws \Exception|\OAuthException
	 * @throws \DreamFactory\Oasys\Exceptions\RedirectRequiredException
	 * @return string
	 */
	public function checkAuthenticationProgress()
	{
		if ( $this->_config->getAccessToken() )
		{
			return true;
		}

		$_state = $this->_config->getState();

		if ( null === ( $_token = Option::request( 'oauth_token' ) ) && 1 == $_state )
		{
			$this->_config->setState( $_state = 0 );
		}

		try
		{
			//	No auth yet
			if ( 0 == $_state && null === $_token )
			{
				$_token = $this->_client->getRequestToken( $this->_config->getEndpoint( EndpointTypes::REFRESH_TOKEN ) );

				$this->_config->setState( 1 );
				$this->_config->setAccessTokenSecret( Option::get( $_token, 'oauth_token_secret' ) );

				//	Construct the redirect for authorization
				$_redirectUrl = $this->_config->getEndpoint( EndpointTypes::AUTHORIZE ) . '?oauth_token=' . Option::get( $_token, 'oauth_token' );

				if ( !empty( $this->_redirectProxyUrl ) )
				{
					$_redirectUrl = $this->_redirectProxyUrl . '?redirect=' . urlencode( $_redirectUrl );
				}

				$this->_config->setAuthorizeUrl( $_redirectUrl );

				if ( Flows::SERVER_SIDE == $this->_config->getFlowType() )
				{
					throw new RedirectRequiredException( $_redirectUrl );
				}

				header( 'Location: ' . $_redirectUrl );
				exit();
			}
			//	Got the request token, upgrade it
			else if ( 1 == $_state )
			{
				$_requestToken = Option::get( $_GET, 'oauth_token' );
				$_tokenSecret = Option::get( $this->_config->getAccessTokenSecret() );

				$this->_client->setToken( $_requestToken, $_tokenSecret );
				$_token = $this->_client->getAccessToken( $this->_config->getEndpoint( EndpointTypes::ACCESS_TOKEN ) );

				$this->_config->setState( $_state = 2 );
				$this->_config->setAccessToken( Option::get( $_token, 'oauth_token' ) );
				$this->_config->setAccessTokenSecret( Option::get( $_token, 'oauth_token_secret' ) );
			}

			//	Set the token, now ready for action
			$this->_setToken();
		}
		catch ( \OAuthException $_ex )
		{
			Log::error( 'OAuth exception: ' . $_ex->getMessage() );
			throw $_ex;
		}

		return true;
	}

	/**
	 * Fetch a protected resource
	 *
	 * @param string $resource
	 * @param array  $payload
	 * @param string $method
	 * @param array  $headers
	 *
	 * @throws \DreamFactory\Oasys\Exceptions\OasysConfigurationException
	 * @throws \Kisma\Core\Exceptions\NotImplementedException
	 * @return array
	 */
	public function fetch( $resource, $payload = array(), $method = self::Get, array $headers = array() )
	{
		$_url = $resource;

		if ( false === stripos( $_url, 'http://', 0 ) && false === stripos( $_url, 'https://', 0 ) )
		{
			$_endpoint = $this->_config->getEndpoint( EndpointTypes::SERVICE );

			$payload = array_merge(
				Option::get( $_endpoint, 'parameters', array() ),
				$payload
			);

			//	Spiffify
			$_url = rtrim( $_endpoint['endpoint'], '/' ) . '/' . ltrim( $resource, '/' );
		}

		if ( false !== $this->_client->fetch( $_url, $payload, $method, $headers ) )
		{
			$_response = $this->_client->getLastResponse();
			$_info = $this->_client->getLastResponseInfo();

			return array(
				'result'       => $_response,
				'info'         => $_info,
				'code'         => $_info[CURLINFO_HTTP_CODE],
				'content_type' => $_info[CURLINFO_CONTENT_TYPE],
			);
		}
	}

	/**
	 * @param string $token
	 * @param string $secret
	 *
	 * @return bool
	 */
	protected function _setToken( $token = null, $secret = null )
	{
		return $this->_client->setToken(
			$token ? : $this->_config->getAccessToken(),
			$secret ? : $this->_config->getAccessTokenSecret()
		);
	}

	/**
	 * @param \DreamFactory\Oasys\Configs\LegacyOAuthProviderConfig $config
	 *
	 * @return LegacyOAuthClient
	 */
	public function setConfig( $config )
	{
		$this->_config = $config;

		return $this;
	}

	/**
	 * @return \DreamFactory\Oasys\Configs\LegacyOAuthProviderConfig
	 */
	public function getConfig()
	{
		return $this->_config;
	}
}
