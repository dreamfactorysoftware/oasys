<?php
/**
 * oasys.config.php
 * Master config file
 */
return
	array(
		'base_url'   => 'http://localhost/oauth/authorize/',
		'providers'  => array(
			'OpenID'     => array(
				'enabled' => true
			),
			'Yahoo'      => array(
				'enabled' => true,
				'keys'    => array( 'id' => null, 'secret' => null ),
			),
			'AOL'        => array(
				'enabled' => true
			),
			'Google'     => array(
				'enabled' => true,
				'keys'    => array( 'id' => null, 'secret' => null ),
			),
			'Facebook'   => array(
				'enabled' => true,
				'keys'    => array( 'id' => null, 'secret' => null ),
			),
			'Twitter'    => array(
				'enabled' => true,
				'keys'    => array( 'key' => null, 'secret' => null )
			),
			'Live'       => array(
				'enabled' => true,
				'keys'    => array( 'id' => null, 'secret' => null )
			),
			'MySpace'    => array(
				'enabled' => true,
				'keys'    => array( 'key' => null, 'secret' => null )
			),
			'LinkedIn'   => array(
				'enabled' => true,
				'keys'    => array( 'key' => null, 'secret' => null )
			),
			'Foursquare' => array(
				'enabled' => true,
				'keys'    => array( 'id' => null, 'secret' => null )
			),
		),
		'debug_mode' => false,
		'debug_file' => null,
	);
