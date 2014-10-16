<?php

class Pods_Deploy {

	/**
	 * @var int The elapsed time since process started.
	 * @since 0.3.0
	 */
	public static $elapsed_time;

	/**
	 * @var array Deploy params
	 *
	 * @since 0.5.0
	 */
	public static $settings;

	/**
	 * @var string URL to deploy to.
	 *
	 * @since 0.5.0
	 */
	public static $remote_url;

	/**
	 * @var string Remote site public key
	 *
	 * @since 0.5.0
	 */
	private static $public_key;

	/**
	 * @var string Remote site private key
	 *
	 * @since 0.5.0
	 */
	private static $private_key;

	/**
	 * @var string Auth token
	 *
	 * @since 0.5.0
	 */
	private static $token;

	/**
	 * @var int Timeout for requests
	 *
	 * @since 0.5.0
	 */
	public static $timeout;

	/**
	 * @var array Pods to deploy
	 *
	 * @since 0.5.0
	 */
	public static $pods;


	/**
	 * The deploy sequence
	 *
	 * @since 0.3.0 ?
	 *
	 * @param $deploy_params
	 */
	public static function deploy( $deploy_params ) {
		$remote_url =self::$remote_url = pods_v( 'remote_url', $deploy_params );
		$public_key = self::$public_key = pods_v( 'public_key', $deploy_params );
		$private_key = self::$private_key = pods_v( 'private_key', $deploy_params );
		$timeout = self::$timeout = pods_v( 'timeout', $deploy_params, 60 );
		$pods = self::$pods = pods_v( 'pods', $deploy_params, self::pod_names() );

		$token = self::$token = Pods_Deploy_Auth::generate_token( $public_key, $private_key );
		
		if ( is_null( $pod_names = pods_v( 'pods', $deploy_params ) ) ) {
			$pod_names = self::pod_names();
		}

		self::$pods = $pod_names;

		if ( ! $remote_url ||  ! $public_key || ! $private_key ) {
			echo self::output_message( __( 'Invalid parameters:( You shall not pass! ', 'pods-deploy' ) );

			return false;
			
		}

		$deploy_components = self::do_deploy_components( pods_v( 'components', $deploy_params ) );

		if ( false == $deploy_components ) {
			echo self::output_message( __( 'Components could not be activated on remote site. Deployment aborted.', 'pods-deploy' ) );
			return false;
		}


		$fail = false;

		self::$elapsed_time = microtime( true );

		if ( ! class_exists(  'Pods_Migrate_Packages' ) ) {
			return new WP_Error( 'pods-deploy-need-packages',  __( 'You must activate the Packages Component on both the site sending and receiving this package.', 'pods-deploy' ) );
		}

		//@todo add options for these params
		$params = array(
		//	'pods' => true,
			'templates' => true,
			'page' => true,
			'helpers' => true,
		);

		foreach ( $pods as $pod ) {
			$pod_id = self::pod_id( $pod );

			if ( 0 < $pod_id  ) {
				$single = array ( 'pods' => $pod_id );
				self::do_deploy( $single );
			}
			else{
				self::output_message( __( sprintf( 'The Pod ID for the Pod could not be determined for %1s, which is highly suspicious.', $pod ), 'pods-deploy' )  );
			}

		}

		// Deploy everything other than the pods
		self::do_deploy( $params );

		self::do_deploy_relationships();

	}

	/**
	 * Deploy a package
	 *
	 * @since 0.3.0 ?
	 *
	 * @param array $params Pods_Migrate_Packages::export() params
	 */
	private static function do_deploy( $params ) {

		$fail = false;

		$data = Pods_Migrate_Packages::export( $params );

		$url = trailingslashit( self::$remote_url ) . 'pods-components?package';

		$url = Pods_Deploy_Auth::add_to_url( self::$public_key, self::$token, $url );

		$response = wp_remote_post( $url, array (
				'method'    => 'POST',
				'body'      => $data,
				'timeout'   => self::$timeout,
			)
		);

		$pod_name = '';
		if ( ! is_null( $pods = pods_v( 'pods', $params ) ) ) {
			if ( is_array( $pods ) ) {
				$pod_name = pods_serial_comma( $pods );
			}
			else {
				$pod_name = $pods;
			}
		}

		if ( self::check_return( $response ) ) {
			echo self::output_message( __( sprintf( 'Package deployed successfully for %1s.', $pod_name ), 'pods-deploy' ), $url );


			if ( ! $fail ) {
				echo self::output_message( __( 'Deployment complete :)', 'pods-deploy' ) );
			}
			else {
				echo self::output_message( __( 'Deployment completed with mixed results :|', 'pods-deploy' ) );
			}

		}
		else{
			echo self::output_message( __( sprintf( 'Package could not be deployed for %1s:(', $pod_name ), 'pods-deploy' ) );
			var_dump( $response );
		}


	}

	/**
	 * Update relationships
	 *
	 * @since 0.3.0 ?
	 *
	 * @return bool
	 */
	private static function do_deploy_relationships( ) {

		$fail = false;

		$responses = array();

		$pod_names = self::$pods;

		$data = Pods_Deploy::get_relationships();
		$pods_api_url = trailingslashit( self::$remote_url ) . 'pods-api/';

		foreach( $pod_names as $pod_name ) {
			$url = $pods_api_url. "{$pod_name}/update_rel";
			$url = Pods_Deploy_Auth::add_to_url( self::$public_key, self::$token, $url );
			$responses[] = $response = wp_remote_post( $url, array (
					'method'      => 'POST',
					'body'        => json_encode( $data ),
					'timeout'     => self::$timeout,
				)
			);

			if ( self::check_return( $response ) ) {
				echo self::output_message(
					__( sprintf( 'Relationships for the %1s Pod were updated.', $pod_name )
						, 'pods-deploy' ),
					$url
				);
			}
			else {
				$fail = true;
				echo self::output_message(
					__( sprintf( 'Relationships for the %1s Pod were not updated.', $pod_name )
						, 'pods-deploy' ),
					$url
				);

				var_dump( $response );

			}

		}

		return $fail;

	}

	/**
	 * Update components
	 *
	 * @since 0.3.0 ?
	 *
	 * @param null $components
	 *
	 * @return array|bool|WP_Error
	 */
	private static function do_deploy_components( $components = null ) {

		if ( true === $components ) {

			$components = self::active_components();

		}

		if ( ! is_array( $components ) ) {
			$components = array( 'migrate-packages' => 'Migrate Packages' );
		}

		if ( ! array_key_exists( 'migrate-packages', $components ) ) {
			$components[ 'migrate-packages' ] = 'Migrate Packages';
		}

		$url = trailingslashit(  self::$remote_url ) . 'pods-components';

		$url = Pods_Deploy_Auth::add_to_url( self::$public_key, self::$token, $url );

		$response = wp_remote_post( $url, array (
				'method'    => 'GET',
				'timeout'   => self::$timeout,
			)
		);

		if ( ! self::check_return( $response ) ) {
			echo self::output_message( __( 'Could not get activate components from remote site:(', 'pod-deploy' ), $url );
			var_dump( $response );
			return false;
		}
		else{
			echo self::output_message( __( 'Remote site active components determined:)', 'pods-deploy'), $url );
		}


		$remote_components = json_decode( wp_remote_retrieve_body( $response ) );



		if ( ! is_object( $remote_components ) ) {
			$data = array( 'migrate-packages' );
		}
		else {
			$data = array ();
			foreach ( $components as $component => $label ) {

				if ( false == pods_v( $component, $remote_components ) ) {

					$data[ ] = $component;
				}

			}
		}

		if ( ! empty( $data ) ) {
			$data = array ( 'activate' => $data );
		}


		$response = wp_remote_post( $url, array (
				'method'    => 'PUT',
				'body'      => json_encode( $data ),
				'timeout'   => self::$timeout,
			)
		);

		if ( self::check_return( $response ) ) {
			self::output_message( __( 'Successfully activated remote components.', 'pods-deploy' ), $url );
		}
		else {
			self::output_message( __( 'Remote component activation failed.', 'pods-deploy' ), $url );
			var_dump( $response );
			return false;
		}

		return $response;

	}

	/**
	 * Gets relationships
	 *
	 * @since 0.2.0
	 *
	 * @return bool
	 */
	public static function get_relationships(){
		$relationships = false;

		$api = pods_api();
		$pods = $api->load_pods();

		foreach( $pods as $pod ) {
			$pod_name = pods_v( 'name', $pod );
			if ( ! is_null( $local_fields = pods_v( 'fields', $pod ) ) ) {
				foreach ( $local_fields as $field_name => $field ) {
					if ( '' !== ( $sister_id = pods_v( 'sister_id', $field ) ) ) {

						$relationships[ pods_v( 'name', $field ) ] = array (
							'from' => array (
								'pod_name'   => $pod_name,
								'field_name' => pods_v( 'name', $field ),
							),
							'to'   => self::find_by_id( $sister_id, $pods ),
						);

					}

				}


			}

		}

		return $relationships;

	}

	/**
	 * Build an array of field names and IDs per Pod.
	 *

	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public static function fields_by_name_id( $pods ) {
		$fields = false;

		if ( is_array( $pods ) ) {
			foreach ( $pods as  $pod ) {

				$pod_name = pods_v( 'name', $pod );

				$local_fields = pods_v( 'fields', $pod );
				if ( $local_fields ) {
					foreach ( $local_fields as $field_name => $data ) {
						$fields[ $pod_name ][ $field_name ] = $data[ 'id' ];
					}
				}



			}

		}

		return $fields;
	}

	/**
	 * Get a field name by ID
	 *
	 * @param int       $id                 The field's ID.

	 *
	 * @since 0.1.0
	 *
	 * @return array                        Name of Pod and field name.
	 */
	public static function find_by_id( $id, $pods ) {
		$fields_by_id = self::fields_by_name_id( $pods );
		if ( is_array( $fields_by_id ) ) {
			foreach( $fields_by_id as $pod_name => $fields ) {
				$search = array_search( $id, $fields );

				if ( $search ) {

					return array(
						'pod_name' => $pod_name,
						'field_name' => $search,
					);
				}

			}

		}

	}

	/**
	 * Get a field ID by name.
	 *
	 * @param string $name              The field's name

	 *
	 *
	 * @since 0.0.3
	 *
	 * @return array                    Name of Pod and field ID
	 */
	public static function find_by_name( $name, $pods ) {
		$fields_by_name = self::fields_by_name_id( $pods );

		if ( is_array( $fields_by_name ) ) {
			$fields_by_name = array_flip( $fields_by_name );
			foreach( $fields_by_name as $pod_name => $fields ) {
				$search = array_search( $name, $fields );

				if ( $search ) {
					return array(
						'pod_name'   => $pod_name,
						'field_name' => $search,
					);
				}

			}

		}

	}

	/**
	 * Output a message during deployment, with the time elpased since deploy started.
	 *
	 * @param  string   $message Message to show.
	 * @param string    $url Optional. The URL to show for message.
	 *
	 * @since 0.3.0
	 *
	 * @return string
	 */
	public static function output_message( $message, $url = '' ){
		if ( is_string( $message ) ) {
			$time = self::elapsed_time();

			return sprintf( '<div class="pods-deploy-message"><p>%1s</p> <span="pods-deploy-message-time">Elapsed time: %2s</span>  <span="pods-deploy-message-url">%3s</span></div>', $message, $time, $url );
		}

	}

	/**
	 * Calculate elapsed time since process began.
	 *
	 * @param bool $return_formatted
	 *
	 * @since 0.3.0
	 *
	 * @return int
	 */
	private static function elapsed_time( $return_formatted = true ) {
		$time_end = microtime( true );
		$time = $time_end - self::$elapsed_time;
		if ( $return_formatted ) {
			$hours = (int) ( $time/60/60);
			$minutes = (int)( $time/60)-$hours*60;
			$seconds = (int) $time-$hours*60*60-$minutes*60;
		}
		else{
			$seconds = $time;
		}

		return $seconds;

	}

	/**
	 * Check if HTTP request response is valid.
	 *
	 * @param      $response The response.
	 * @param bool|array $allowed_codes Optional. An array of allowed response codes. If false, the default, response code 200 and 201 are allowed.
	 *
	 * @since 0.3.0
	 *
	 * @return bool
	 */
	private static function check_return( $response, $allowed_codes = false ) {
		if ( ! is_array( $allowed_codes )  ) {
			$allowed_codes = array( 200, 201 );
		}

		if ( ! is_wp_error( $response ) && in_array( wp_remote_retrieve_response_code( $response ), $allowed_codes ) ) {
			return true;
		}

	}

	/**
	 * Output a list of field names.
	 *
	 * @since 0.4.0
	 *
	 * @return array|mixed
	 */
	static function pod_names() {

		return pods_deploy_pod_names();

	}

	/**
	 * Get an array of active components
	 *
	 * @since 0.4.0
	 *
	 * @return array|void
	 */
	public static function active_components() {
		$the_active_components = false;
		$components = new PodsComponents();
		$components = $components->get_components();
		$component_names = $components = wp_list_pluck( $components, 'Name'  );
		$active_components = get_option( 'pods_component_settings' );
		$active_components =  json_decode( $active_components );
		$active_components = pods_v( 'components', $active_components );
		$active_components =  array_keys( (array) $active_components );

		foreach( $active_components as $component ) {
			if ( ! is_null( pods_v( $component,$component_names ) ) ) {

				$the_active_components[ $component ] = $component_names[ $component ];

			}

		}

		return $the_active_components;

	}

	/**
	 * Get a Pod's ID
	 *
	 * @param $name
	 *
	 * @return int
	 *
	 * @since 0.5.0
	 */
	public static function pod_id( $name ) {
		$api = pods_api( $name );

		return (int) $api->pod_id;

	}


}
