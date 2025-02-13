<?php
// Include WooCommerce functions if necessary.
if ( function_exists( 'wc_get_order_statuses' ) ) {
	wps_render_order_status_settings_page();
}

if (
	isset( $_POST['wps_order_status_conditions'] ) &&
	current_user_can( 'manage_options' ) &&
	check_admin_referer( 'wps_order_status_settings-options' )
) {
	$conditions = array_map(
		function ( $condition ) {
			return array(
				'from' => sanitize_text_field( $condition['from'] ),
				'to' => sanitize_text_field( $condition['to'] ),
			);
		},
		$_POST['wps_order_status_conditions']
	);

	update_option( 'wps_order_status_conditions', $conditions );

	// Redirect back to the settings page with a success message.
	wp_redirect(
		add_query_arg(
			array(
				'page' => 'track_orders_for_woocommerce_menu',
				'tofw_tab' => 'track-orders-for-woocommerce-pro-order-status-auto',
				'saved_now'     => 'true', // Add a flag to indicate a successful save.
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

function wps_render_order_status_settings_page() {
	$conditions = get_option( 'wps_order_status_conditions', array() );

	$conditions = [
		[
			'from' => 'wc-processing',
			'to'   => 'wc-completed'
		]
	];
	
	?>
	<div class="wrap-tofw-main">
		<form method="post" action="">
			<?php
			settings_fields( 'wps_order_status_settings' );
			do_settings_sections( 'wps-order-status-settings' );
			?>
			<table id="wps-conditions-table" class="form-table">
				<thead>
					<tr>
						<th class="wps_tofw_pro_tag"><?php esc_html_e('From Status','track-orders-for-woocommerce'); ?></th>
						<th class="wps_tofw_pro_tag"><?php esc_html_e('To Status','track-orders-for-woocommerce'); ?></th>
						<th class="wps_tofw_pro_tag"><?php esc_html_e('Action','track-orders-for-woocommerce'); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $conditions ) ) : ?>
						<?php foreach ( $conditions as $index => $condition ) : ?>
							<tr>
								<td>
									<select name="wps_order_status_conditions[<?php echo $index; ?>][from]" class="from-status">
										<?php foreach ( wc_get_order_statuses() as $key => $label ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $condition['from'], $key ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
								<td>
									<select name="wps_order_status_conditions[<?php echo $index; ?>][to]" class="to-status">
										<?php foreach ( wc_get_order_statuses() as $key => $label ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $condition['to'], $key ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
								<td>
									<button type="button" class="button wps-remove-condition"><?php esc_html_e('Remove','track-orders-for-woocommerce'); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
			<button type="button" id="wps-add-condition" class="button wps_tofw_pro_feature wps_tofw_pro_tag"><?php esc_html_e('+ Add Condition','track-orders-for-woocommerce'); ?></button>
			<p class="submit"><input type="button" name="submit" id="submit" class="button wps_tofw_pro_feature button-primary" value="Save Changes"></p>
		</form>
	</div>
	<?php
}
include_once plugin_dir_path( dirname( __FILE__ ) ) . '/partials/track-order-for-woocommerce-go-pro.php';
