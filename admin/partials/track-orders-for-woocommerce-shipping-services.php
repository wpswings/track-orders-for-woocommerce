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
$tofw_shipping_services_settings =
// desc - filter for trial.
apply_filters( 'tofw_shipping_services_settings_array', array() );
?>
<!--  template file for admin settings. -->
<form action="" method="POST" class="wps-tofw-gen-section-form">
	<div class="tofw-secion-wrap">
		<?php
		$tofw_general_html = $wps_tofw_obj->wps_std_plug_generate_html( $tofw_shipping_services_settings );
		echo esc_html( $tofw_general_html );
		wp_nonce_field( 'admin_save_data', 'wps_tabs_nonce' );
		?>
	</div>
</form>
