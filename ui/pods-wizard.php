<?php
/* This code was ripped from Migrate-Packages/ui/wizard.php to hopefully be re-useable.
   Temporarily dumped it here for testing
*/
$api = pods_api();

$pods = $api->load_pods( array( 'fields' => false ) );
$pod_templates = $api->load_templates();
$pod_pages = $api->load_pages();
$pod_helpers = $api->load_helpers();

?>

<div class="pods-wizard-option-content pods-deploy-ui" id="pods-wizard-export">
	<div class="pods-wizard-content">
		<p><?php _e( 'Packages allow you to import/export your Pods, Fields, and other settings between any Pods sites.', 'pods' ); ?></p>
	</div>
	<?php
	wizard_form( $pods, 'id', 'pods', 'Choose which Pods to export' );
	wizard_form( $pod_templates, 'id', 'templates', 'Choose which Pod Templates to export' );
	wizard_form( $pod_pages, 'id', 'pages', 'Choose which Pod Pages to export' );
	wizard_form( $pod_helpers, 'id', 'helpers', 'Choose which Pod Helpers to export' );

function wizard_form( $data = null, $name = null, $data_name, $data_text, $checked = true ) {
	if ( !empty( $data ) ) {
		?>
		<div class="stuffbox pods-package-import-group">
			<h3><label for="link_name"><?php _e( $data_text, 'pods' ); ?></label></h3>

			<div class="inside pods-manage-field pods-dependency">
				<div class="pods-field-option-group">
					<p>
						<a href="#toggle" class="button pods-wizard-toggle-all"
						   data-toggle="<?php echo $data_name; ?>"><?php _e( 'Toggle all on / off', 'pods' ); ?></a>
					</p>

					<div class="pods-pick-values pods-pick-checkbox pods-zebra">
						<ul>
							<?php
							$zebra = false;

							foreach ( $data as $item ) {
								$class = ( $zebra ? 'even' : 'odd' );

								$zebra = ( ! $zebra );
								?>
								<li class="pods-zebra-<?php echo $class; ?>">
									<?php echo PodsForm::field( $data_name . '[' . $item[ $name ] . ']', $checked, 'boolean', array( 'boolean_yes_label' => $item['name'] . ( ! empty( $item['label'] ) ? ' (' . $item['label'] . ')' : '' ) ) ); ?>
								</li>
							<?php
							}
							?>
						</ul>
					</div>
				</div>
			</div>
		</div>
<?php
	}
}

do_action( 'pods_packages_export_options', $pods, $pod_templates, $pod_pages, $pod_helpers );
?>
</div>
<script>
	jQuery( function ( $ ) {
/*		$( document ).Pods( 'validate' );
		$( document ).Pods( 'submit' );
		$( document ).Pods( 'wizard' );
		$( document ).Pods( 'dependency' );
		$( document ).Pods( 'advanced' );
		$( document ).Pods( 'confirm' );
		$( document ).Pods( 'sluggable' );*/

		var toggle_all = {};

		$( '.pods-wizard-toggle-all' ).on( 'click', function ( e ) {
			e.preventDefault();

			if ( 'undefined' == typeof toggle_all[ $( this ).data( 'toggle' ) ] )
				toggle_all[ $( this ).data( 'toggle' ) ] = true;

			$( this ).closest( '.pods-field-option-group' ).find( '.pods-field.pods-boolean input[type="checkbox"]' ).prop( 'checked', ( !toggle_all[ $( this ).data( 'toggle' ) ] ) );

			toggle_all[ $( this ).data( 'toggle' ) ] = ( !toggle_all[ $( this ).data( 'toggle' ) ] );
		} );
	} );
</script>
