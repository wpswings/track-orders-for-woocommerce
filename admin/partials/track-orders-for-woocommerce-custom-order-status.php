<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html field for general tab.
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
$tofw_custom_order_status =
// desc - filter for trial.
apply_filters( 'tofw_custom_order_status_array', array() );
?>
<!--  template file for admin settings. -->
<form action="" method="POST" class="wps-tofw-gen-section-form">
	<div class="tofw-secion-wrap">
		<?php
		$tofw_custom_order_status_html = $wps_tofw_obj->wps_std_plug_generate_html( $tofw_custom_order_status );
		echo esc_html( $tofw_custom_order_status_html );
		wp_nonce_field( 'admin_save_data', 'wps_tabs_nonce' );
		?>
	</div>
</form>
