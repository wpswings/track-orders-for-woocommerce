<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html for system status.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Template for showing information about system status.
global $wps_tofw_obj;
$tofw_default_status    = $wps_tofw_obj->wps_std_plug_system_status();
$tofw_wordpress_details = is_array( $tofw_default_status['wp'] ) && ! empty( $tofw_default_status['wp'] ) ? $tofw_default_status['wp'] : array();
$tofw_php_details       = is_array( $tofw_default_status['php'] ) && ! empty( $tofw_default_status['php'] ) ? $tofw_default_status['php'] : array();
?>
<div class="track-orders-for-woocommerce-table-wrap">
	<div class="wps-col-wrap">
		<div id="track-orders-for-woocommerce-table-inner-container" class="table-responsive mdc-data-table">
			<div class="mdc-data-table__table-container">
				<table class="track-orders-for-woocommerce-table mdc-data-table__table wps-table" id="track-orders-for-woocommerce-wp">
					<thead>
						<tr>
							<th class="mdc-data-table__header-cell"><?php esc_html_e( 'WP Variables', 'track-orders-for-woocommerce' ); ?></th>
							<th class="mdc-data-table__header-cell"><?php esc_html_e( 'WP Values', 'track-orders-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody class="mdc-data-table__content">
						<?php if ( is_array( $tofw_wordpress_details ) && ! empty( $tofw_wordpress_details ) ) { ?>
							<?php foreach ( $tofw_wordpress_details as $wp_key => $wp_value ) { ?>
								<?php if ( isset( $wp_key ) && 'wp_users' != $wp_key ) { ?>
									<tr class="mdc-data-table__row">
										<td class="mdc-data-table__cell"><?php echo esc_html( $wp_key ); ?></td>
										<td class="mdc-data-table__cell"><?php echo esc_html( $wp_value ); ?></td>
									</tr>
								<?php } ?>
							<?php } ?>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<div class="wps-col-wrap">
		<div id="track-orders-for-woocommerce-table-inner-container" class="table-responsive mdc-data-table">
			<div class="mdc-data-table__table-container">
				<table class="track-orders-for-woocommerce-table mdc-data-table__table wps-table" id="track-orders-for-woocommerce-sys">
					<thead>
						<tr>
							<th class="mdc-data-table__header-cell"><?php esc_html_e( 'System Variables', 'track-orders-for-woocommerce' ); ?></th>
							<th class="mdc-data-table__header-cell"><?php esc_html_e( 'System Values', 'track-orders-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody class="mdc-data-table__content">
						<?php if ( is_array( $tofw_php_details ) && ! empty( $tofw_php_details ) ) { ?>
							<?php foreach ( $tofw_php_details as $php_key => $php_value ) { ?>
								<tr class="mdc-data-table__row">
									<td class="mdc-data-table__cell"><?php echo esc_html( $php_key ); ?></td>
									<td class="mdc-data-table__cell"><?php echo esc_html( $php_value ); ?></td>
								</tr>
							<?php } ?>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
