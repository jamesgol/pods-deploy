<form action="?page=pods-deploy" method="post">

	<div id="icon-tools" class="icon32"><br></div>
	<h2>
		<?php _e( sprintf( 'Pods Deploy: %1s', $key_gen_header ),  'pods-deploy' ); ?>
	</h2>

	<input type="hidden" class="" name="allow-deploy" id="allow-deploy" value="<?php echo ! $deploy_active ?>">
	<?php echo PodsForm::field( '_wpnonce', wp_create_nonce( 'pods-deploy' ), 'hidden' ); ?>

	<p class="submit">
		<input type="submit" class="button button-primary" name="pods-deploy-key-gen-submit" value="<?php echo $key_gen_submit; ?>">
	</p>
</form>

<?php if ( $deploy_active ) : ?>
	<div id="current-keys">
		<p>
			<?php _e( sprintf( 'Public Key: %1s', $public_remote, 'pods-deploy' ) ); ?>
		</p>
		<p>
			<?php _e( sprintf( 'Private Key: %1s', $private_remote, 'pods-deploy' ) ); ?>
		</p>

	</div>
<?php endif; ?>
