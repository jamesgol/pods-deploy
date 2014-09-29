<?php
class Pods_Deploy_Auth {
	public static $public_key_option_name = 'pods_deploy_public_key';
	public static $private_key_option_name = 'pods_deploy_private_key';
	public static  $allow_option_name = 'pods_deploy_allow_deploy';

	/**
	 * Allows access to the needed end points if key auth matches
	 *
	 * @since 0.0.3
	 *
	 * @return bool|WP_Error
	 */
	public static function allow_access() {
		if ( true == self::check_auth() ) {
			add_filter( 'pods_json_api_access_components_package', '__return_true' );
			add_filter( 'pods_json_api_access_components_get_components', '__return_true' );
			add_filter( 'pods_json_api_access_components_activate_components', '__return_true' );
			add_filter( 'pods_json_api_access_api_update_rel', '__return_true' );
			return true;
		}
		else{
			$info = self::check_auth();
			return new WP_Error( 'pods-deploy-key-auth-fail', __( 'Key auth failed.', "pods-deploy" ), $info );

		}

	}

	/**
	 * Checks if key auth is legit
	 *
	 * @since 0.0.3
	 *
	 * @return bool
	 */
	public static function check_auth() {

		$token = self::get_request_token();
		$public  =  self::get_request_key();
		if ( $public  && $token ) {

			$private = pods_v( 'private', self::get_keys() );
			if ( $private && hash( 'md5', $public, $private ) === $token ) {

				return true;

			}

		}


	}

	/**
	 * Gets token from current request
	 *
	 * @since 0.0.3
	 *
	 * @return string
	 */
	private static function get_request_token() {

		if ( ! is_null( $token = pods_v( 'pods-deploy-token', self::query_string() ) ) ) {

			return urldecode( $token  );

		}

	}

	/**
	 * Gets public key from current request
	 *
	 * @since 0.0.3
	 *
	 * @return string
	 */
	private static function get_request_key() {

		if ( ! is_null( $key = pods_v( 'pods-deploy-key', self::query_string() ) ) ) {

			return urldecode( $key );

		}

	}

	/**
	 * Parses query string from current request
	 *
	 * @since 0.0.3
	 *
	 * @return array
	 */
	private static function query_string() {

		$query = pods_v_sanitized( 'QUERY_STRING', $_SERVER );

		if ( $query ) {
			return wp_parse_args( $query );
		}
	}

	/**
	 * Get public/private keys
	 *
	 * @param bool $remote Optional. If true, the default, gets saved keys for remote site to deploy to. If false get stored keys to use for deploying <em>to this site</em>.
	 *
	 * @since 0.0.3
	 *
	 * @return array
	 */
	public static function get_keys( $remote = true ) {
		if ( $remote ) {
			return array(
				'public' => get_option( self::$public_key_option_name ),
				'private' => get_option( self::$private_key_option_name ),
			);
		}

		return array(
			'public' => get_option( self::$public_key_option_name . '_local' ),
			'private' => get_option( self::$private_key_option_name . '_local' ),
		);


	}

	/**
	 * Generate public/private keys
	 *
	 * @since 0.3.0
	 */
	public static function generate_keys() {

		update_option( self::$public_key_option_name, self::generate_public_key() );
		update_option( self::$private_key_option_name, self::generate_private_key() );

	}

	/**
	 * Save keys used for deployment.
	 *
	 * @param string $key
	 * @param string $private
	 *
	 * @since 0.3.0
	 */
	public static function save_local_keys( $key, $private ) {

		update_option( self::$public_key_option_name . '_local', $key );
		update_option( self::$private_key_option_name . '_local', $private );

	}

	/**
	 * Generates a public key
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	private static function generate_public_key() {
		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$public   = hash( 'md5', self::random_string() . $auth_key . date( 'U' ) );


		return $public;

	}

	/**
	 * Generates a public key
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	private static function generate_private_key( ) {
		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$secret   = hash( 'md5', get_current_user_id() . $auth_key . date( 'U' ) );

		return $secret;

	}

	/**
	 * Generates a private key
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public static function generate_token( $public, $private ) {

		return hash( 'md5', $public, $private );

	}

	private static function random_string() {
		return substr( str_shuffle( '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ), 0, 42);

	}

	/**
	 * Add <strong>public key</strong> and token to url string
	 *
	 * @param $key
	 * @param $token
	 * @param $url
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public static function add_to_url( $key, $token, $url ) {
		$args = array(
			'pods-deploy-key' => urlencode( $key ),
			'pods-deploy-token' => urlencode( $token ),
		);

		return add_query_arg( $args, $url );

	}

	/**
	 * Toggle deployments to this site on or off
	 *
	 * NOTE: Does not generate or revoke keys.
	 *
	 * @param bool $allow Optional. If true deployments are allowed. If false they will not be.
	 *
	 * @since 0.3.0
	 */
	public static function allow_deploy( $allow = true ) {

		update_option( self::$allow_option_name, $allow );

	}

	/**
	 * Revokes keys and toggles deployment off for this site.
	 *
	 * @since 0.3.0
	 */
	public static function revoke_keys() {

		delete_option( self::$public_key_option_name );
		delete_option( self::$private_key_option_name );
		self::allow_deploy( false );

	}

	/**
	 * Checks if deployment is currently allowed for this site.
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	public static function deploy_active() {

		if ( get_option( self::$allow_option_name ) ) {

			return true;

		}
	}





} 
