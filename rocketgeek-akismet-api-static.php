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
 * @package    {Your Project Name}
 * @subpackage RocketGeek_Akismet_API
 * @version    1.0.0
 *
 * @link       https://akismet.com/development/api/
 * @link       https://github.com/rocketgeek/akismet_api/
 * @author     Chad Butler <https://butlerblog.com>
 * @author     RocketGeek <https://rocketgeek.com>
 * @copyright  Copyright (c) 2019 Chad Butler
 * @license    https://github.com/rocketgeek/jquery_tabs/blob/master/LICENSE.md GNU General Public License 3
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( ! class_exists( 'RocketGeek_Akismet_API' ) ):

class RocketGeek_Akismet_API {
	
	public static $version = '1.0.0';
	public static $api_key_option = 'rktgk_akismet_api_key';
	public static $api_key;
	public static $api_endpoint = '<key>.rest.akismet.com';
	public static $blog;
	public static $default_enabled = true;
	public static $text_domain = 'rktgk-akismet-api';
	public static $test_akismet = false;
	
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
	public static function init( $args ) { //$default_enabled = true, $api_key = false ) {
		
		self::$default_enabled = ( isset( $args['default_enabled'] ) ) ? $args['default_enabled'] : self::$default_enabled;
		self::$blog            = ( isset( $args['blog']            ) ) ? $args['blog']            : get_option( 'home' );
		self::$text_domain     = ( isset( $args['text_domain']     ) ) ? $args['text_domain']     : self::$text_domain; // @todo Temporary until I figure out something better.
		self::$test_akismet    = ( isset( $args['test_akismet']    ) ) ? $args['test_akismet']    : self::$test_akismet;
		self::$api_key_option  = ( isset( $args['api_key_option']  ) ) ? $args['api_key_option']  : self::$api_key_option;
		self::$api_key         = ( isset( $args['api_key']         ) ) ? $args['api_key']         : self::get_api_key();    // Do this last so that the option name is correctly set.

		// Load hooks.
		if ( true === self::$default_enabled ) {
			self::load_hooks();
		}
	}

	/**
	 * Load filter and action hooks.
	 * 
	 * @since 1.0.0
	 */
	private static function load_hooks() {
		add_filter( 'registration_errors', array( 'RocketGeek_Akismet_API', 'default_validation' ), 10, 3 );
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
	public static function get_api_key() {
		$wp_api_key = get_option( 'wordpress_api_key' );
		return ( $wp_api_key ) ? $wp_api_key : get_option( self::$api_key_option );
	}

	/**
	 * Validate registration directly.
	 *
	 * @since 1.0.0
	 *
	 * @param  array    $args
	 * @return boolean         True if it's spam, otherwise false.
	 */
	public static function reg_validate( $args ) {
		
		$user_ip    = ( isset( $args['user_ip'] ) ) ? $args['user_ip'] : self::get_user_ip(); // Required
		$user_email = ( isset( $args['user_email'] ) ) ? $args['user_email'] : false;         // Optional
		$user_login = ( isset( $args['user_login'] ) ) ? $args['user_login'] : false;         // Optional
		
		return ( self::is_spam( $user_ip, $user_email, $user_login ) ) ? true : false;
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
	public static function default_validation( $errors, $sanitized_user_login, $user_email ) {
		// Do not run if email is not set
		if ( $user_email ) {
			$ip = self::get_user_ip();

			// Add error if conditional returns true
			if ( self::is_spam( $ip, $user_email, $sanitized_user_login ) ) {
				$errors->add( 'likely_spammer', __( '<strong>ERROR</strong>: Cannot register. Please contact site administrator for assistance.', self::$text_domain ) );
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
	public static function get_user_ip() {
		return ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Gets the endpoint for API key verification.
	 *
	 * @since 1.0.0
	 *
	 * @return string The endpoint for verifying an API key.
	 */
	private static function get_verification_endpoint() {
		return self::get_api_endpoint( false );
	}
	
	/**
	 * Gets API endpoints.
	 *
	 * @since 1.0.0
	 *
	 * @param  boolean  $add_key  If the API key should be added to the endpoint (optional|default:true).
	 * @return string             The API endpoint.
	 */
	private static function get_api_endpoint( $add_key = true ) {
		$old = ( $add_key ) ? '<key>' : '<key>.';
		$new = ( $add_key ) ? self::$api_key : '';
		return str_replace( $old, $new, self::$api_endpoint );
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
	public static function is_spam( $ip, $email = false, $username = false ) {

		$data = array(
			'blog'                 => self::$blog,
			'user_ip'              => $ip,
			'user_agent'           => $_SERVER['HTTP_USER_AGENT'],
			'referrer'             => $_SERVER['HTTP_REFERER'],
			'comment_type'         => 'signup',
		);
		
		if ( $email ) {		
			$data['comment_author_email'] = $email;
		}
		
		if ( $username ) {
			$data['comment_author'] = $username;
		}
		
		if ( 1 == self::$test_akismet ) {
			$data['is_test'] = true;
		}

		$request = '';
		foreach ( $data as $key => $value ) {
			$request .= ( '' != $request ) ? '&' : '';
			$request .= $key . '=' . urlencode( $data[ $key ] );
		}
	
		$host = self::$api_key . '.' . self::get_api_endpoint();;
		$path = '/1.1/comment-check';
		
		$response = self::http_request( $host, $path, $request );

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
	public static function verify_key( $key ) {

		$request  = 'key='. $key .'&blog='. urlencode( self::$blog );
		$host     = self::get_verification_endpoint();
		$path     = '/1.1/verify-key';
		$response = self::http_request( $host, $path, $request );

		return ( 'valid' == $response[1] ) ? true : false;
	}

	/**
	 * Saves the Akismet API key.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean Result of update_option().
	 */
	public static function save_key( $key ) {
		return update_option( self::$api_key_option, $key );
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
	private static function http_request( $host, $path, $request ) {

		$port           = 443;
		$akismet_ua     = "WordPress/" . get_bloginfo( 'version' ) . " | RocketGeek_Akismet_API/" . self::$version;
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

}      // End of RocketGeek_Akismet_API Class.
endif; // End of checking if class exists.