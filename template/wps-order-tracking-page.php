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
$flag                 = false;
$wps_user_order_id    = '';
$wps_tofw_track_error = '';
if ( isset( $_POST['wps_track_package'] ) ) {
	$value_check = isset( $_POST['wps_track_package_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_track_package_nonce_name'] ) ) : '';
	if ( wp_verify_nonce( $value_check, 'wps_track_package_nonce' ) ) {
		// The order id is only needed for this request: never store it in a site option.
		$wps_user_order_id = isset( $_POST['wps_user_order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_user_order_id'] ) ) : '';

		// Only the order's customer, a guest who verified the billing email, or any visitor when
		// "track using order id only" is enabled may see the shipment details of an order.
		if ( function_exists( 'wps_tofw_can_view_order' ) && wps_tofw_can_view_order( $wps_user_order_id ) ) {
			$flag = true;
		} else {
			// Same message for unknown and foreign orders so order ids cannot be probed.
			$wps_tofw_track_error = __( 'No tracking details were found for this order. Please check the order id, or log in to the account that placed the order.', 'track-orders-for-woocommerce' );
		}
	} else {
		$wps_tofw_track_error = __( 'Your session has expired. Please enter the order id and submit the form again.', 'track-orders-for-woocommerce' );
	}
}

?>
<div>
	<legend><h3><?php esc_html_e( 'Shipment Tracking', 'track-orders-for-woocommerce' ); ?></h3></legend>
	<?php if ( '' !== $wps_tofw_track_error ) : ?>
		<div class="woocommerce-error wps_tofw_track_package_error" role="alert"><?php echo esc_html( $wps_tofw_track_error ); ?></div>
	<?php endif; ?>
	
	<form action="" method="POST" role="form">
		
		<div class="form-group">
			<label for=""><?php esc_html_e( ' Enter Order Id ', 'track-orders-for-woocommerce' ); ?></label>
			<input type="text" class="form-control" id="wps_tofw_order_id" name="wps_user_order_id"  placeholder=<?php esc_attr_e( 'Enter Order id', 'track-orders-for-woocommerce' ); ?> >
		</div>
		<?php wp_nonce_field( 'wps_track_package_nonce', 'wps_track_package_nonce_name' ); ?>
		<button type="submit" class="btn btn-primary wps_tofw_button_track" name="wps_track_package"><?php esc_html_e( 'Submit', 'track-orders-for-woocommerce' ); ?></button>
	</form>
</div>



<?php
if ( $flag ) {
	$wps_tofw_selected_shipping_method = wps_tofw_get_order_meta( $wps_user_order_id, 'wps_tofw_selected_shipping_service' );

	/** This action is documented in template/wps-track-order-myaccount-page-template1.php */
	do_action( 'wps_tofw_render_return_button', absint( $wps_user_order_id ) );

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

	/**
	 * Fires after the order tracking details (e.g. carrier status timeline).
	 *
	 * @since 1.3.0
	 * @param int $order_id Order ID.
	 */
	do_action( 'wps_tofw_after_tracking_details', absint( $wps_user_order_id ) );
}


/**
 * Add content.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_after_main_content' );
get_footer( 'shop' );
?>
