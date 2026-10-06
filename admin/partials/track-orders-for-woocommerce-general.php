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
$tofw_genaral_settings =
// desc - filter for trial.
apply_filters( 'tofw_general_settings_array', array() );
?>
<div class="tofwp-license-tab__hero">
	<div class="tofwp-license-tab__hero-copy">
		<p class="tofwp-license-tab__eyebrow"><?php esc_html_e( 'SECTION', 'track-orders-for-woocommerce' ); ?></p>
		<h2 class="tofwp-license-tab__hero-title"><?php esc_html_e( 'General Setting', 'track-orders-for-woocommerce' ); ?></h2>
		<p class="tofwp-license-tab__hero-text"><?php esc_html_e( 'Review and update the settings available in this section.', 'track-orders-for-woocommerce' ); ?></p>
	</div>
	<a class="tofwp-license-tab__doc-link button button-primary" target="_blank" rel="noopener noreferrer" href="https://docs.wpswings.com/track-orders-for-woocommerce/?utm_source=ot-org-page&utm_medium=referral&utm_campaign=ot-doc-free"><?php esc_html_e( 'Read Documentation', 'track-orders-for-woocommerce' ); ?></a>
</div>
<!--  template file for admin settings. -->
<form action="" method="POST" class="wps-tofw-gen-section-form">
	<div class="tofw-secion-wrap">
		<?php
		$tofw_general_html = $wps_tofw_obj->wps_std_plug_generate_html( $tofw_genaral_settings );
		echo esc_html( $tofw_general_html );
		wp_nonce_field( 'admin_save_data', 'wps_tabs_nonce' );
		?>
	</div>
</form>
