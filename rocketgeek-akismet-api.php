<?php
/**
 * RocketGeek RocketGeek_Akismet_API Library
 *
 * Provides a class to check Akismet's API in WordPress plugins and themes.  
 * See the readme.md for initial instructions.
 *
 * This library is open source and GPL licensed. I hope you find it useful
 * for your project(s). Attribution is appreciated ;-)
 *
 * @package    RocketGeek_Akismet_API
 * @version    1.1.0
 *
 * @link       https://akismet.com/development/api/
 * @link       https://github.com/rocketgeek/akismet_api/
 * @author     Chad Butler <https://butlerblog.com>
 * @author     RocketGeek <https://rocketgeek.com>
 * @copyright  Copyright (c) 2021 Chad Butler
 * @license    Apache-2.0
 *
 * Copyright [2021] Chad Butler, RocketGeek
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     https://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'RocketGeek_Akismet_API' ) ):

class RocketGeek_Akismet_API {
	
	public $version = '1.1.0';
	public $api_endpoint = '<key>.rest.akismet.com';
	public $blog;
	public $api_key_option = 'rktgk_akismet_api_key';
	public $api_key;
	public $default_enabled = true;
	public $text_domain = 'rktgk-akismet-api';
	public $test_akismet = false;
	public $stem = "rktgk_akismet";
	
	/**
	 * Plugin initialization function.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $args {
	 *     An array of possible default overrides.
	 *
	 *     @type boolean  $default_enabled
	 *     @type string   $api_key
	 *     @type string   $api_key_option
	 *     @type string   $text_domain
	 *     @type string   $test_akismet
	 * }
	 */
	public function __construct( $args ) {

		$this->blog = get_option( 'home' );
		$this->default_enabled = ( isset( $args['default_enabled'] ) ) ? $args['default_enabled'] : $this->default_enabled;
		$this->api_key_option  = ( isset( $args['api_key_option']  ) ) ? $args['api_key_option']  : $this->api_key_option;
		$this->text_domain     = ( isset( $args['text_domain']     ) ) ? $args['text_domain']     : $this->text_domain; // @todo Temporary until I figure out something better.
		$this->test_akismet    = ( isset( $args['test_akismet']    ) ) ? $args['test_akismet']    : $this->test_akismet;
		$this->api_key         = ( isset( $args['api_key']         ) ) ? $args['api_key']         : $this->get_api_key();  // Do this last so that the option name is correctly set.
		$this->stem            = ( isset( $args['stem']            ) ) ? $args['stem']            : $this->stem;
	
		// Load hooks.
		if ( true === $this->default_enabled ) {
			$this->load_hooks();
		}
	}

	/**
	 * Load filter and action hooks.
	 * 
	 * @since 1.0.0
	 */
	private function load_hooks() {
		add_filter( 'registration_errors', array( $this, 'default_validation' ), 10, 3 );
	}

	/**
	 * Gets the API key.
	 *
	 * If the "official" Akismet WP plugin is installed, use the existing 
	 * api key. Otherwise, load from the library's $api_key_option value.
	 *
	 * @since 1.0.0
	 *
	 * @return string $api_key
	 */
	public function get_api_key() {		
		$wp_api_key = get_option( 'wordpress_api_key' );
		return ( false !== $wp_api_key ) ? $wp_api_key : get_option( $this->api_key_option );
	}

	/**
	 * Validate registration directly.
	 *
	 * @since 1.0.0
	 *
	 * @param  array    $args
	 * @return boolean         False if it's spam, otherwise true.
	 */
	public function reg_validate( $args ) {
		
		$user_ip    = ( isset( $args['user_ip']    ) ) ? $args['user_ip']    : $this->get_user_ip(); // Required
		$user_email = ( isset( $args['user_email'] ) ) ? $args['user_email'] : false; // Optional
		$user_login = ( isset( $args['user_login'] ) ) ? $args['user_login'] : false; // Optional
		
		return ( true === $this->is_spam( $user_ip, $user_email, $user_login ) ) ? false : true;
	}
	
	/**
	 * Checks WP registration for spam registrations
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $errors
	 * @param  string $sanitized_user_login
	 * @param  string $user_email
	 * @return array  $errors
	 */
	public function default_validation( $errors, $sanitized_user_login, $user_email ) {
		// Do not run if email is not set
		if ( $user_email ) {
			$ip = $this->get_user_ip();

			// Add error if conditional returns true
			if ( $this->is_spam( $ip, $user_email, $sanitized_user_login ) ) {
				/**
				 * Filter the error message.
				 * 
				 * @since 1.1.0
				 * 
				 * @param  array  $error_values  {
				 *     The values for the error message
				 * 
				 *     @type string $tag     The message tag
				 *     @type string $message The message
				 * }
				 */
				$error_values = apply_filters( $this->stem . 'error_msg', array(
					'tag'     => 'likely_spammer',
					'message' => __( '<strong>ERROR</strong>: Cannot register. Please contact site administrator for assistance.', $this->text_domain ),
				));
				$errors->add( $error_values['tag'], $error_values['message'] );
			}
		}

		return $errors;
	}

	/**
	 * Get the request IP address.
	 *
	 * @since 1.0.0
	 *
	 * @return string $ip
	 */
	public function get_user_ip() {
		return ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Gets the endpoint for API key verification.
	 *
	 * @since 1.0.0
	 *
	 * @return string The endpoint for verifying an API key.
	 */
	private function get_verification_endpoint() {
		return $this->get_api_endpoint( false );
	}
	
	/**
	 * Gets API endpoints.
	 *
	 * @since 1.0.0
	 *
	 * @param  boolean  $add_key  If the API key should be added to the endpoint (optional|default:true).
	 * @return string             The API endpoint.
	 */
	private function get_api_endpoint( $add_key = true ) {
		$old = ( $add_key ) ? '<key>' : '<key>.';
		$new = ( $add_key ) ? $this->api_key : '';
		return str_replace( $old, $new, $this->api_endpoint );
	}

	/**
	 * Conditional function to check registration for spam.
	 *
	 * @since 1.0.0
	 *
	 * @param   string    $ip
	 * @param   string    $email
	 * @param   string    $username
	 * @return  boolean
	 */
	public function is_spam( $ip, $email = false, $username = false ) {

		$data = array(
			'blog'         => $this->blog,
			'user_ip'      => $ip,
			'user_agent'   => $_SERVER['HTTP_USER_AGENT'],
			'referrer'     => $_SERVER['HTTP_REFERER'],
			'comment_type' => 'signup',
		);
		
		if ( $email ) {		
			$data['comment_author_email'] = $email;
		}
		
		if ( $username ) {
			$data['comment_author'] = $username;
		}
		
		if ( 1 == $this->test_akismet ) {
			$data['is_test'] = true;
		}

		$request = '';
		foreach ( $data as $key => $value ) {
			$request .= ( '' != $request ) ? '&' : '';
			$request .= $key . '=' . urlencode( $data[ $key ] );
		}
	
		$host = $this->get_api_endpoint();;
		$path = '/1.1/comment-check';

		$response = $this->http_request( $host, $path, $request );

		return ( 'true' == $response[1] ) ? true : false;
	}
	
	/**
	 * Verify Akismet API key.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $key  The API key being validated.
	 * @return boolean       True if valid, otherwise false.
	 */
	public function verify_key( $key ) {

		$request  = 'key='. $key .'&blog='. urlencode( $this->blog );
		$host     = $this->get_verification_endpoint();
		$path     = '/1.1/verify-key';
		$response = $this->http_request( $host, $path, $request );

		return ( 'valid' == $response[1] ) ? true : false;
	}

	/**
	 * Saves the Akismet API key.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean Result of update_option().
	 */
	public function save_key( $key ) {
		return update_option( $this->api_key_option, $key );
	}
	
	/**
	 * Handles the HTTP request for API queries.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $host
	 * @param  string  $path
	 * @param  string  $request
	 * @return array
	 */
	private function http_request( $host, $path, $request ) {

		$port           = 443;
		$akismet_ua     = $this->get_user_agent();
		$content_length = strlen( $request );
		
		$http_request  = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$http_request .= "Content-Length: {$content_length}\r\n";
		$http_request .= "User-Agent: {$akismet_ua}\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;
		$response = '';
	
		// query the endpoint.
		$fs = @fsockopen( 'ssl://' . $host, $port, $errno, $errstr, 10 );

		if ( false != ( $fs ) ) {
			fwrite( $fs, $http_request );
			while ( ! feof( $fs ) ) {
				$response .= fgets( $fs, 1160 ); // One TCP-IP packet
			}	
			fclose( $fs );
			$response = explode( "\r\n\r\n", $response, 2 );
		}

		return $response;
	}
	
	/**
	 * API request user agent.
	 *
	 * @since 1.0.0
	 */
	public function get_user_agent() {
		return "WordPress/" . get_bloginfo( 'version' ) . " | RocketGeek_Akismet_API/" . $this->version;
	}

}      // End of RocketGeek_Akismet_API Class.
endif; // End of checking if class exists.