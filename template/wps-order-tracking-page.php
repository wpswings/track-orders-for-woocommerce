<?php
/**
 * Guest track order page.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' );


/**
 * Add content.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_before_main_content' );
$flag = false;
$value_check = isset( $_POST['wps_track_package_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_track_package_nonce_name'] ) ) : '';
wp_verify_nonce( $value_check, 'wps_track_package_nonce' );
if ( isset( $_POST['wps_track_package'] ) ) {
	$flag = true;
	$wps_user_order_id = isset( $_POST['wps_user_order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_user_order_id'] ) ) : '';
	update_option( 'wps_tofw_user_order_id', $wps_user_order_id );
}

?>
<div>
	<legend><h3><?php esc_html_e( 'Shipment Tracking', 'track-orders-for-woocommerce' ); ?></h3></legend>
	
	<form action="" method="POST" role="form">
		
		<div class="form-group">
			<label for=""><?php esc_html_e( ' Enter Order Id ', 'track-orders-for-woocommerce' ); ?></label>
			<input type="text" class="form-control" id="wps_tofw_order_id" name="wps_user_order_id"  placeholder=<?php esc_attr_e( 'Enter Order id', 'track-orders-for-woocommerce' ); ?> >
		</div>
		<input type="hidden" name="wps_track_package_nonce_name" value="<?php wp_create_nonce( 'wps_track_package_nonce' ); ?>">
		<button type="submit" class="btn btn-primary wps_tofw_button_track" name="wps_track_package"><?php esc_html_e( 'Submit', 'track-orders-for-woocommerce' ); ?></button>
	</form>
</div>



<?php
if ( $flag ) {
	$wps_tofw_selected_shipping_method = get_post_meta( $wps_user_order_id, 'wps_tofw_selected_shipping_service', true );

	if ( isset( $wps_tofw_selected_shipping_method ) && ( 'fedex' == $wps_tofw_selected_shipping_method ) ) {
		include_once TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'integration/class-track-orders-for-woocommerce-with-fedex.php';
		$request = new Track_Orders_For_Woocommerce_With_FedEx();
		$request->fedex_request( $wps_user_order_id );
	} else if ( isset( $wps_tofw_selected_shipping_method ) && ( 'canada_post' == $wps_tofw_selected_shipping_method ) ) {

		do_action( 'wps_track_orders_canada_post', $wps_user_order_id );
		include_once TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'integration/class-track-orders-for-woocommerce-with-fedex.php';


	} else if ( isset( $wps_tofw_selected_shipping_method ) && ( 'usps' == $wps_tofw_selected_shipping_method ) ) {
		do_action( 'wps_track_orders_usps', $wps_user_order_id );

		?>
			<div class="wps_tofw_shipment_tracking_warning_msg">
				<h4><?php esc_html_e( 'Service Not Available', 'track-orders-for-woocommerce' ); ?></h4>	
			</div>
		<?php
	}
}


/**
 * Add content.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
?>
