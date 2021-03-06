<div id="pods-meta-box" class="postbox pods-deploy-ui">
	<form action="?page=pods-deploy" method="post" class="pods-submittable">

		<div id="icon-tools" class="icon32"><br></div>
		<h2>
			<?php _e( 'Deploy To Remote Site', 'pods-deploy' ); ?>
		</h2>
		<div class="keys-and-url stuffbox">
			<?php
			$form = Pods_Form();
			$fields[] =  $form::field( '_wpnonce', wp_create_nonce( 'pods-deploy' ), 'hidden' );

			foreach( $form_fields as $name => $field ) {

				$fields[] = '<li>';
				$fields[] = $form::label(
					$name,
					pods_v( 'label', $field, '' ),
					pods_v( 'help', $field, '' )
				);
				$fields[] = $form::field(
					$name,
					pods_v( 'value', $field ),
					pods_v( 'type',  $field, 'text' ),
					pods_v( 'options', $field )
				);
				$fields[] = '</li>';

			}

			echo sprintf( '<ul>%1s</ul>', implode( $fields ) );
		?>
		</div>
		<div class="clearfix"></div>
		<?php
			pods_view( PODS_DEPLOY_DIR . 'ui/pods-wizard.php' );

		?>



		<p class="submit">
			<input type="submit" class="button button-primary" name="pods-deploy-submit" value="Deploy">
		</p>
	</form>
</div>
<?php


