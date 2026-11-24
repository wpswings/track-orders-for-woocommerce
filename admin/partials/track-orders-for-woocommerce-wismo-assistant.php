<?php
/**
 * Admin tab: "Where is my order?" assistant settings.
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
global $wps_tofw_obj;
$tofw_wismo_settings =
/**
 * Filter the WISMO assistant settings fields.
 *
 * @since 1.3.0
 */
apply_filters( 'wps_tofw_wismo_settings_array', array() );
?>
<div class="tofwp-license-tab__hero">
	<div class="tofwp-license-tab__hero-copy">
		<p class="tofwp-license-tab__eyebrow"><?php esc_html_e( 'SECTION', 'track-orders-for-woocommerce' ); ?></p>
		<h2 class="tofwp-license-tab__hero-title"><?php esc_html_e( 'Order Assistant', 'track-orders-for-woocommerce' ); ?></h2>
		<p class="tofwp-license-tab__hero-text"><?php esc_html_e( 'A "Where is my order?" chat widget. Customers verify an order with its number and billing e-mail (or pick one of their own orders when logged in) and get answers about status, tracking and delivery. Works without any API key; AI answers are optional.', 'track-orders-for-woocommerce' ); ?></p>
	</div>
</div>
<form action="" method="POST" class="wps-tofw-gen-section-form">
	<div class="tofw-secion-wrap">
		<?php
		$wps_tofw_obj->wps_std_plug_generate_html( $tofw_wismo_settings );
		wp_nonce_field( 'admin_save_data', 'wps_tabs_nonce' );
		?>
	</div>
</form>
