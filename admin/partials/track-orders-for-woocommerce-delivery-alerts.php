<?php
/**
 * Delivery alerts settings tab.
 *
 * @link  https://wpswings.com/
 * @since 1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wps_tofw_obj;
$tofw_delivery_alerts_settings =
/**
 * Filter the delivery alerts settings fields.
 *
 * @since 1.3.0
 */
apply_filters( 'tofw_delivery_alerts_settings_array', array() );
$tofw_insights      = $wps_tofw_obj->tofw_get_shipment_insights();
$tofw_next_check    = function_exists( 'as_next_scheduled_action' ) ? as_next_scheduled_action( Track_Orders_For_Woocommerce_Shipment_Insights::DAILY_HOOK, array(), Track_Orders_For_Woocommerce_Shipment_Insights::AS_GROUP ) : false;
$tofw_active_alerts = $tofw_insights ? $tofw_insights->wps_tofw_get_active_alerts() : array();
?>
<div class="tofwp-license-tab__hero">
	<div class="tofwp-license-tab__hero-copy">
		<p class="tofwp-license-tab__eyebrow"><?php esc_html_e( 'SECTION', 'track-orders-for-woocommerce' ); ?></p>
		<h2 class="tofwp-license-tab__hero-title"><?php esc_html_e( 'Delivery Alerts', 'track-orders-for-woocommerce' ); ?></h2>
		<p class="tofwp-license-tab__hero-text"><?php esc_html_e( 'Get notified when a shipment is in Exception or has not been delivered within the expected time. Shipments are checked once a day and each issue is reported only once per order.', 'track-orders-for-woocommerce' ); ?></p>
	</div>
	<a class="tofwp-license-tab__doc-link button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=track_orders_for_woocommerce_menu&tofw_tab=' . Track_Orders_For_Woocommerce_Shipment_Insights::TAB_ANALYTICS ) ); ?>"><?php esc_html_e( 'Shipping Analytics', 'track-orders-for-woocommerce' ); ?></a>
</div>
<p class="wps-tofw-insights-meta">
	<?php
	if ( $tofw_next_check && true !== $tofw_next_check ) {
		/* translators: %s: date and time. */
		echo esc_html( sprintf( __( 'Next scheduled check: %s', 'track-orders-for-woocommerce' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $tofw_next_check ) ) );
	} elseif ( $tofw_next_check ) {
		esc_html_e( 'A check is currently running.', 'track-orders-for-woocommerce' );
	} else {
		esc_html_e( 'Alerts are not scheduled. Enable them and save the settings to schedule the daily check.', 'track-orders-for-woocommerce' );
	}
	echo ' ';
	/* translators: %d: number of shipments. */
	echo esc_html( sprintf( __( 'Shipments currently needing attention: %d', 'track-orders-for-woocommerce' ), count( $tofw_active_alerts ) ) );
	?>
</p>
<form action="" method="POST" class="wps-tofw-gen-section-form">
	<div class="tofw-secion-wrap">
		<?php
		$tofw_general_html = $wps_tofw_obj->wps_std_plug_generate_html( $tofw_delivery_alerts_settings );
		echo esc_html( $tofw_general_html );
		wp_nonce_field( 'admin_save_data', 'wps_tabs_nonce' );
		?>
	</div>
</form>
