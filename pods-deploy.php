<?php
/*
Plugin Name: Pods Deploy
Plugin URI: http://pods.io/
Description: Automated Pods config deploy via the WordPress REST API.
Version: 0.5.0
Author: Pods Framework Team
Author URI: http://pods.io/about/
Text Domain: pods-deploy
Domain Path: /languages/

Copyright 2014  Pods Foundation, Inc  (email : contact@podsfoundation.org)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'PODS_DEPLOY_VERSION', '0.5.0' );
define( 'PODS_DEPLOY_DIR', plugin_dir_path( __FILE__ ) );
define( 'PODS_DEPLOY_MIN_JSON_API_VERSION', '0.2.2' );
define( 'PODS_DEPLOY_MIN_PODS_VERSION', '2.4.3' );

if ( ! defined( 'PODS_DEPLOY_DEV_MODE' ) ) {
	define( 'PODS_DEPLOY_DEV_MODE', false );
}

if ( ! defined( 'PODS_DEPLOY_DONT_OBSCURE_KEYS' ) ) {
	define( 'PODS_DEPLOY_DONT_OBSCURE_KEYS', false );
}


/**
 * An array of dependencies to check for before loading class.
 *
 * @since 0.1.0
 *
 * @return array
 */
function pods_deploy_dependencies() {
	return array(
		'Pods' => 'PODS_VERSION',
		'Pods JSON API' => 'PODS_JSON_API_VERSION',
		'WordPress REST API' => 'JSON_API_VERSION',
	);
}

/**
 * Check for dependencies and load main class if decencies are present
 *
 * @since 0.1.0
 */
add_action( 'plugins_loaded', 'pods_deploy_load_plugin' );
function pods_deploy_load_plugin() {

	if ( pods_deploy_dependency_check() ) {
		include_once( PODS_DEPLOY_DIR . 'class-pods-deploy-auth.php' );
		include_once( PODS_DEPLOY_DIR . 'class-pods-deploy-ui.php' );
		include_once( PODS_DEPLOY_DIR . 'class-pods-deploy.php' );

		$ui = new Pods_Deploy_UI();
		add_filter( 'pods_admin_menu', array( $ui, 'menu') );
		add_action( 'init', 'pods_deploy_auth' );
	}

}

/**
 * Check for dependencies and versions.
 *
 * @since 0.3.0
 *
 * @return bool
 */
function pods_deploy_dependency_check() {
	$fail = false;
	foreach( pods_deploy_dependencies() as $dependency => $constant) {
		if ( ! defined( $constant ) ){
			$fail[] = __( sprintf( '<p>Pods Deploy requires %1s which is not activate.</p>', $dependency ), 'pods-deploy' );
		}

	}

	if ( ! is_array( $fail ) ) {
		if ( ! version_compare( PODS_JSON_API_VERSION, PODS_DEPLOY_MIN_JSON_API_VERSION, '>=' ) ) {
			$fail[] = sprintf( 'Pods Deploy requires Pods JSON API version %1s or later. Current version is %2s.', PODS_DEPLOY_MIN_JSON_API_VERSION, PODS_JSON_API_VERSION );
		}

		if ( ! version_compare( PODS_VERSION, PODS_DEPLOY_MIN_PODS_VERSION, '>=' ) ) {
			$fail[] = sprintf( 'Pods Deploy requires Pods version %1s or later. Current version is %2s.', PODS_DEPLOY_MIN_PODS_VERSION, PODS_VERSION );
		}

		if ( ! version_compare( PHP_VERSION, '5.3.0', '>=' ) ) {
			$fail[] = sprintf( 'Pods Deploy requires PHP version %1s or later. Current version is %2s.', '5.3.0', PHP_VERSION );

		}

	}

	if ( is_array( $fail ) ) {

		if (  is_admin() ) {
			foreach ( $fail as $message ) {
				echo sprintf( '<div id="message" class="error"><p>%s</p></div>',
					$message
				);
			}
		}

		return false;

	}

	return true;

}

/**
 * Run the deployment
 *
 * @TODO Ability to deploy only specific Pods.
 *
 * @since 0.1.0
 *
 * @param $remote_url
 */
function pods_deploy( $params ) {

	$params[ 'remote_url' ] = pods_v( 'remote_url', $params );
	$params[ 'public_key' ] = pods_v( 'public_key', $params );
	$params[ 'private_key' ] = pods_v( 'private_key', $params );

	return Pods_Deploy::deploy( $params );

}

/**
 * Allow remote deployments to site if enabled in UI and keys match.
 *
 * @since 0.3.0
 */
function pods_deploy_auth() {
	if ( class_exists( 'Pods_Deploy_Auth' ) && get_option( Pods_Deploy_Auth::$allow_option_name, false ) ) {

		include_once( PODS_DEPLOY_DIR . 'class-pods-deploy-auth.php' );
		
		return Pods_Deploy_Auth::allow_access();

	}

}

/**
 * Output a list of field names.
 *
 * @since 0.4.0
 *
 * @return array|mixed
 */
function pods_deploy_types() {
	$names = array();

	$api = pods_api();

	$pods = $api->load_pods( array( 'fields' => false ) );
	if ( is_array( $pods ) && !empty( $pods ) ) {
		foreach ( $pods as $item ) {
			$names['pods'][] = $item['id'];
		}
	}
	$pod_templates = $api->load_templates( );
	if ( is_array( $pod_templates ) && !empty( $pod_templates ) ) {
		foreach ( $pod_templates as $item ) {
			$names[ 'templates' ][] = $item[ 'id' ];
		}
	}
	$pod_pages = $api->load_pages( );
	if ( is_array( $pod_pages ) && !empty( $pod_pages ) ) {
		foreach ( $pod_pages as $item ) {
			$names[ 'pages' ][] = $item[ 'id' ];
		}
	}

	$pod_helpers = $api->load_helpers( );
	if ( is_array( $pod_helpers ) && !empty( $pod_helpers ) ) {
		foreach ( $pod_helpers as $item ) {
			$names[ 'helpers' ][] = $item[ 'id' ];
		}
	}

	return $names;
}

/**
 * Deactivation
 *
 * Disables deployments to this site and erases the stored keys.
 *
 * @since 0.4.0
 */
register_deactivation_hook( __FILE__, 'pods_deploy_deactivate' );
function pods_deploy_deactivate() {
	include_once( PODS_DEPLOY_DIR . 'class-pods-deploy-auth.php' );
	Pods_Deploy_Auth::revoke_keys();
	Pods_Deploy_Auth::clear_local_keys();

}



