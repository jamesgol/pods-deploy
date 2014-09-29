<div class="wrap pods-admin">
	<form action="" method="post">

		<div id="icon-pods" class="icon32"><br /></div>

		<?php
		$default = 'deploy';

		$tabs = array(
			'deploy' => __( 'Deploy From This Site', 'pods-deploy' ),
			'key-gen' => __( 'Allow Deploying To This Site', 'pods-deploy' )
		);
		?>

		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( $tabs as $tab => $label ) {
				$class = '';

				if ( $tab == pods_v( 'tab', 'get', $default ) ) {
					$class = ' nav-tab-active';

					$label = $label;
				}

				$url = pods_query_arg( array( 'tab' => $tab ), array( 'page' ) );
				?>
				<a href="<?php echo $url; ?>" class="nav-tab<?php echo $class; ?>">
					<?php echo $label; ?>
				</a>
			<?php
			}
			?>
		</h2>
		<img src="<?php echo PODS_URL; ?>ui/images/pods-logo-notext-rgb-transparent.png" class="pods-leaf-watermark-right" />

		<?php
		$tab = pods_v( 'tab', 'get', $default );
		$tab = sanitize_title( $tab );

		$data = compact( array( 'keys', 'public_local', 'private_local', 'public_remote', 'private_remote', 'deploy_active', 'key_gen_submit', 'key_gen_header', 'form_fields' ) );
		echo pods_view( PODS_DEPLOY_DIR . 'ui/' . $tab . '.php', $data );
		?>
	</form>
</div>
