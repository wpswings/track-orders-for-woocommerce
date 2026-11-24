<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the estimated delivery date settings tab.
 *
 * @link       https://wpswings.com/
 * @since      1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_woocommerce' ) ) {
	return;
}

$wps_tofw_edd          = Track_Orders_For_Woocommerce_Estimated_Delivery::get_settings();
$wps_tofw_edd_zones    = Track_Orders_For_Woocommerce_Estimated_Delivery::get_zones_with_methods();
$wps_tofw_edd_weekdays = array(
	1 => __( 'Monday', 'track-orders-for-woocommerce' ),
	2 => __( 'Tuesday', 'track-orders-for-woocommerce' ),
	3 => __( 'Wednesday', 'track-orders-for-woocommerce' ),
	4 => __( 'Thursday', 'track-orders-for-woocommerce' ),
	5 => __( 'Friday', 'track-orders-for-woocommerce' ),
	6 => __( 'Saturday', 'track-orders-for-woocommerce' ),
	0 => __( 'Sunday', 'track-orders-for-woocommerce' ),
);
$wps_tofw_edd_toggles  = array(
	'enable'        => __( 'Enable rules based estimated delivery date', 'track-orders-for-woocommerce' ),
	'show_product'  => __( 'Show on product page', 'track-orders-for-woocommerce' ),
	'show_cart'     => __( 'Show on cart & checkout (classic and block checkout)', 'track-orders-for-woocommerce' ),
	'show_email'    => __( 'Show in order emails', 'track-orders-for-woocommerce' ),
	'show_tracking' => __( 'Show on order details & tracking page', 'track-orders-for-woocommerce' ),
);
?>
<div class="tofwp-license-tab__hero">
	<div class="tofwp-license-tab__hero-copy">
		<p class="tofwp-license-tab__eyebrow"><?php esc_html_e( 'SECTION', 'track-orders-for-woocommerce' ); ?></p>
		<h2 class="tofwp-license-tab__hero-title"><?php esc_html_e( 'Estimated Delivery', 'track-orders-for-woocommerce' ); ?></h2>
		<p class="tofwp-license-tab__hero-text"><?php esc_html_e( 'Calculate the estimated delivery date from processing and transit days per shipping method, skipping non-working days and holidays. A delivery date entered manually on an order always overrides the calculated estimate.', 'track-orders-for-woocommerce' ); ?></p>
	</div>
</div>
<?php if ( Track_Orders_For_Woocommerce_Estimated_Delivery::$settings_saved ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'track-orders-for-woocommerce' ); ?></p></div>
<?php endif; ?>
<form action="" method="POST" class="wps-tofw-gen-section-form wps-tofw-edd-form">
	<div class="tofw-secion-wrap">
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Display', 'track-orders-for-woocommerce' ); ?></th>
					<td>
						<?php foreach ( $wps_tofw_edd_toggles as $wps_tofw_key => $wps_tofw_label ) : ?>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="wps_tofw_edd_<?php echo esc_attr( $wps_tofw_key ); ?>" value="on" <?php checked( 'on', $wps_tofw_edd[ $wps_tofw_key ] ); ?> />
								<?php echo esc_html( $wps_tofw_label ); ?>
							</label>
						<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wps_tofw_edd_label"><?php esc_html_e( 'Label', 'track-orders-for-woocommerce' ); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="wps_tofw_edd_label" name="wps_tofw_edd_label" value="<?php echo esc_attr( $wps_tofw_edd['label'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Shown before the date range, e.g. "Estimated delivery: Mon, Dec 1 – Wed, Dec 3".', 'track-orders-for-woocommerce' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wps_tofw_edd_date_format"><?php esc_html_e( 'Date format', 'track-orders-for-woocommerce' ); ?></label></th>
					<td>
						<input type="text" class="small-text" style="width:120px;" id="wps_tofw_edd_date_format" name="wps_tofw_edd_date_format" value="<?php echo esc_attr( $wps_tofw_edd['date_format'] ); ?>" />
						<p class="description"><?php esc_html_e( 'PHP date format. Default: D, M j', 'track-orders-for-woocommerce' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Default rule', 'track-orders-for-woocommerce' ); ?></th>
					<td>
						<label><?php esc_html_e( 'Processing days', 'track-orders-for-woocommerce' ); ?>
							<input type="number" min="0" step="1" class="small-text" name="wps_tofw_edd_processing_days" value="<?php echo esc_attr( $wps_tofw_edd['processing_days'] ); ?>" />
						</label>
						&nbsp;
						<label><?php esc_html_e( 'Transit days (min)', 'track-orders-for-woocommerce' ); ?>
							<input type="number" min="0" step="1" class="small-text" name="wps_tofw_edd_transit_min" value="<?php echo esc_attr( $wps_tofw_edd['transit_min'] ); ?>" />
						</label>
						&nbsp;
						<label><?php esc_html_e( 'Transit days (max)', 'track-orders-for-woocommerce' ); ?>
							<input type="number" min="0" step="1" class="small-text" name="wps_tofw_edd_transit_max" value="<?php echo esc_attr( $wps_tofw_edd['transit_max'] ); ?>" />
						</label>
						<p class="description"><?php esc_html_e( 'Used for any shipping method without its own values below. All values are business days.', 'track-orders-for-woocommerce' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wps_tofw_edd_cutoff_time"><?php esc_html_e( 'Order cut-off time', 'track-orders-for-woocommerce' ); ?></label></th>
					<td>
						<input type="time" id="wps_tofw_edd_cutoff_time" name="wps_tofw_edd_cutoff_time" value="<?php echo esc_attr( $wps_tofw_edd['cutoff_time'] ); ?>" />
						<p class="description">
							<?php
							/* translators: %s: site timezone string. */
							echo esc_html( sprintf( __( 'Orders placed after this time start processing on the next business day. Site timezone: %s. Leave empty to disable.', 'track-orders-for-woocommerce' ), wp_timezone_string() ) );
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Non-working days', 'track-orders-for-woocommerce' ); ?></th>
					<td>
						<?php foreach ( $wps_tofw_edd_weekdays as $wps_tofw_day_num => $wps_tofw_day_name ) : ?>
							<label style="margin-right:12px;">
								<input type="checkbox" name="wps_tofw_edd_non_working_days[]" value="<?php echo esc_attr( $wps_tofw_day_num ); ?>" <?php checked( in_array( (int) $wps_tofw_day_num, array_map( 'intval', (array) $wps_tofw_edd['non_working_days'] ), true ) ); ?> />
								<?php echo esc_html( $wps_tofw_day_name ); ?>
							</label>
						<?php endforeach; ?>
						<p class="description"><?php esc_html_e( 'These days are skipped for processing and transit.', 'track-orders-for-woocommerce' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wps_tofw_edd_holidays"><?php esc_html_e( 'Holidays', 'track-orders-for-woocommerce' ); ?></label></th>
					<td>
						<textarea id="wps_tofw_edd_holidays" name="wps_tofw_edd_holidays" rows="5" cols="30" placeholder="2026-12-25"><?php echo esc_textarea( implode( "\n", (array) $wps_tofw_edd['holidays'] ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'One date per line in YYYY-MM-DD format. Holidays are skipped like non-working days.', 'track-orders-for-woocommerce' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Rules per shipping zone & method', 'track-orders-for-woocommerce' ); ?></h3>
		<p class="description"><?php esc_html_e( 'Leave a field empty to use the default rule. Check "Hide" to not show an estimate for that method (e.g. local pickup).', 'track-orders-for-woocommerce' ); ?></p>
		<?php if ( empty( $wps_tofw_edd_zones ) ) : ?>
			<p><?php esc_html_e( 'No WooCommerce shipping zones found.', 'track-orders-for-woocommerce' ); ?></p>
		<?php else : ?>
			<table class="widefat striped wps-tofw-edd-methods" style="margin-top:10px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Zone', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Shipping method', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Processing days', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Transit days (min)', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Transit days (max)', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Hide', 'track-orders-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $wps_tofw_edd_zones as $wps_tofw_zone ) : ?>
						<?php if ( empty( $wps_tofw_zone['methods'] ) ) : ?>
							<tr>
								<td><?php echo esc_html( $wps_tofw_zone['name'] ); ?></td>
								<td colspan="5"><em><?php esc_html_e( 'No shipping methods in this zone.', 'track-orders-for-woocommerce' ); ?></em></td>
							</tr>
							<?php continue; ?>
						<?php endif; ?>
						<?php foreach ( $wps_tofw_zone['methods'] as $wps_tofw_iid => $wps_tofw_method_title ) : ?>
							<?php
							$wps_tofw_row  = isset( $wps_tofw_edd['methods'][ $wps_tofw_iid ] ) ? (array) $wps_tofw_edd['methods'][ $wps_tofw_iid ] : array();
							$wps_tofw_row  = wp_parse_args(
								$wps_tofw_row,
								array(
									'processing' => '',
									'min'        => '',
									'max'        => '',
									'hide'       => 0,
								)
							);
							$wps_tofw_name = 'wps_tofw_edd_methods[' . absint( $wps_tofw_iid ) . ']';
							?>
							<tr>
								<td><?php echo esc_html( $wps_tofw_zone['name'] ); ?></td>
								<td><?php echo esc_html( $wps_tofw_method_title ); ?></td>
								<td><input type="number" min="0" step="1" class="small-text" name="<?php echo esc_attr( $wps_tofw_name ); ?>[processing]" value="<?php echo esc_attr( $wps_tofw_row['processing'] ); ?>" placeholder="<?php echo esc_attr( $wps_tofw_edd['processing_days'] ); ?>" /></td>
								<td><input type="number" min="0" step="1" class="small-text" name="<?php echo esc_attr( $wps_tofw_name ); ?>[min]" value="<?php echo esc_attr( $wps_tofw_row['min'] ); ?>" placeholder="<?php echo esc_attr( $wps_tofw_edd['transit_min'] ); ?>" /></td>
								<td><input type="number" min="0" step="1" class="small-text" name="<?php echo esc_attr( $wps_tofw_name ); ?>[max]" value="<?php echo esc_attr( $wps_tofw_row['max'] ); ?>" placeholder="<?php echo esc_attr( $wps_tofw_edd['transit_max'] ); ?>" /></td>
								<td><input type="checkbox" name="<?php echo esc_attr( $wps_tofw_name ); ?>[hide]" value="1" <?php checked( ! empty( $wps_tofw_row['hide'] ) ); ?> /></td>
							</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<p class="description" style="margin-top:12px;">
			<?php esc_html_e( 'Shortcode: [wps_tofw_estimated_delivery product_id="123"] (product_id is optional on product pages).', 'track-orders-for-woocommerce' ); ?>
		</p>

		<?php wp_nonce_field( 'wps_tofw_edd_save_settings', 'wps_tofw_edd_nonce' ); ?>
		<p class="submit">
			<button type="submit" name="wps_tofw_edd_save" value="1" class="button button-primary"><?php esc_html_e( 'Save Settings', 'track-orders-for-woocommerce' ); ?></button>
		</p>
	</div>
</form>
