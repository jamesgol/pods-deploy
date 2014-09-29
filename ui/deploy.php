<div id="pods-meta-box" class="postbox" style="width:100%;">
	<form action="?page=pods-deploy" method="post">

		<div id="icon-tools" class="icon32"><br></div>
		<h2>
			<?php _e( 'Deploy To Remote Site', 'pods-deploy' ); ?>
		</h2>
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



		<p class="submit">
			<input type="submit" class="button button-primary" name="pods-deploy-submit" value="Deploy">
		</p>
	</form>
</div>
<?php


