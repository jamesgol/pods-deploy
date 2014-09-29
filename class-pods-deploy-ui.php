<?php

class Pods_Deploy_UI {
	public static $remote_url_key = 'pods_deploy_remote_url';

	/**
	 * Callback for adding Pods Deploy to menus.
	 *
	 * Callback is in the activation function below.
	 *
	 * @since 0.4.0
	 */
	function menu ( $admin_menus ) {

		$admin_menus[ 'pods-deploy'] = array(
			'label' => __( 'Pods Deploy', 'pods-deploy' ),
			'function' => array( $this, 'deploy_handler' ),
			'access' => 'manage_options'

		);

		return $admin_menus;

	}

	/**
	 * Handles UI output and form processing
	 *
	 * @since 0.4.0
	 */
	function deploy_handler () {

		if ( pods_v_sanitized( 'pods-deploy-submit', 'post') ) {
			if ( ! pods_deploy_dependency_check() ) {
				return;
			}

			if ( ! ( $nonce = pods_v_sanitized( '_wpnonce', $_REQUEST ) ) || ! wp_verify_nonce( $nonce, 'pods-deploy' ) ) {
				pods_error( __( 'Bad nonce.', 'pods-deploy' ) );
			}

			$remote_url = pods_v_sanitized( 'remote-url', 'post', false, true );
			$private_key = pods_v_sanitized( 'private-key', 'post' );
			$public_key = pods_v_sanitized( 'public-key', 'post' );
			if ( $remote_url && $private_key && $public_key ) {
				Pods_Deploy_Auth::save_local_keys( $private_key, $public_key );

				update_option( self::$remote_url_key, $remote_url );

				$params = array(
					'remote_url' => $remote_url,
					'private_key' => $private_key,
					'public_key' => $public_key,

				);

				$pod_names = $this->pod_names();
				if ( is_array( $pod_names ) ) {
					foreach ( $pod_names as $name => $label ) {
						if ( pods_v_sanitized( $name, 'POST' ) ) {
							$params[ 'pods' ][ ] = $name;
						}

					}

				}

				if ( ! pods_v_sanitized( 'deploy-components', 'post' )  ) {
					$params[ 'components' ] = false;
				}
				else {
					$params[ 'components' ] = true;
				}




				pods_deploy( $params );

			}
			else{
				_e( 'Keys and URL for remote site not set', 'pods-deploy' );
				pods_error( var_dump( array($remote_url, $private_key, $public_key ) ) );
			}
		}
		elseif( pods_v_sanitized( 'pods-deploy-key-gen-submit', 'post' ) ) {
			$activate = pods_v_sanitized( 'allow-deploy', 'post' );
			if ( $activate ) {
				Pods_Deploy_Auth::allow_deploy();
				Pods_Deploy_Auth::generate_keys();
				$this->include_view();
			}
			else {
				Pods_Deploy_Auth::revoke_keys();
			}

			$this->include_view();
		}
		else {
			$this->include_view();
		}

	}

	/**
	 * Output a list of field names.
	 *
	 * @since 0.4.0
	 *
	 * @return array|mixed
	 */
	function pod_names() {

		return pods_deploy_pod_names();

	}

	/**
	 * Form fields for deploy form
	 *
	 * @since 0.4.0
	 *
	 * @return array
	 */
	function form_fields() {
		$keys = Pods_Deploy_Auth::get_keys( false );
		$public_local = pods_v_sanitized( 'public', $keys, '' );
		$private_local = pods_v_sanitized( 'private', $keys, '' );
		$remote_url = get_option( self::$remote_url_key, '' );

		$form_fields = array(
			'remote-url' =>
				array(
					'label' => __( 'URL To Remote Site API', 'pods-deploy' ),
					'help' => __( 'For example "http://example.com/wp-json"', 'pods-deploy' ),
					'value' => $remote_url,
					'options' => '',
				),
			'public-key' =>
				array(
					'label' => __( 'Remote Site Public Key', 'pods-deploy' ),
					'help' => __( 'Public key from remote site.', 'pods-deploy' ),
					'value' => $public_local,
					'options' => '',
				),
			'private-key' =>
				array(
					'label' => __( 'Remote Site Private Key', 'pods-deploy' ),
					'help' => __( 'Private key from remote site.', 'pods-deploy' ),
					'value' => $private_local,
					'options' => '',
				),
			'deploy-components' =>
				array(
					'label' => __( 'Activate Pods Components', 'pods-deploy' ),
					'help' => __( 'If checked, Pods Deploy will activate all components from this site on remote site. If false, it will only activate the Migrate Packages component, which is required.', 'pods-deploy' ),
					'value' => true,
					'type' => 'boolean',
				),

		);


		return $form_fields;

	}

	/**
	 * Include main UI view and add scope data into it.
	 *
	 * @since 0.4.0
	 *
	 * @return bool|string
	 */
	function include_view() {
		$keys = Pods_Deploy_Auth::get_keys( true );
		$public_remote = pods_v_sanitized( 'public', $keys, '' );
		$private_remote = pods_v_sanitized( 'private', $keys, '' );
		$deploy_active = Pods_Deploy_Auth::deploy_active();

		if ( $deploy_active ) {
			$key_gen_submit = __( 'Disable Deployments', 'pods-deploy' );
			$key_gen_header = __( 'Click to revoke keys and prevent deployments to this site.', 'pods-deploy' );

		}
		else{
			$key_gen_submit = __( 'Allow Deployments', 'pods-deploy' );
			$key_gen_header = __( 'Click to generate new keys and allow deployments to this site', 'pods-deploy' );
		}
		$form_fields = $this->form_fields();

		$data = compact( array( 'keys', 'public_local', 'private_local', 'public_remote', 'private_remote', 'deploy_active', 'key_gen_submit', 'key_gen_header', 'form_fields', ) );


		return pods_view( PODS_DEPLOY_DIR . 'ui/main.php', $data );

	}


} 
