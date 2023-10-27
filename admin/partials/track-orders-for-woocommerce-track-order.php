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

global $wps_tofw_obj;
$tofw_track_order =
// desc - filter for trial.
apply_filters( 'tofw_track_order_array', array() );
?>
<!--  template file for admin settings. -->
<form action="" method="POST" class="wps-tofw-gen-section-form">
	<div class="tofw-secion-wrap">
		<?php
		$tofw_track_order_html = $wps_tofw_obj->wps_std_plug_generate_html( $tofw_track_order );
		echo esc_html( $tofw_track_order_html );
		wp_nonce_field( 'admin_save_data', 'wps_tabs_nonce' );
		?>
	</div>
</form>
