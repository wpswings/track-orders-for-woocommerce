<?php
/**
 * Fired during plugin activation
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */
class Track_Orders_For_Woocommerce_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function track_orders_for_woocommerce_activate() {
		$email = get_option( 'admin_email', false );
		$admin = get_user_by( 'email', $email );
		$admin_id = $admin->ID;

		$wps_tofw_tracking = array(
			'post_author'    => $admin_id,
			'post_name'      => 'track-your-order',
			'post_title'     => __( 'Track Order', 'track-orders-for-woocommerce' ),
			'post_type'      => 'page',
			'post_status'    => 'publish',

		);

		$page_id = wp_insert_post( $wps_tofw_tracking );
		$wps_tofw_pages = array();
		if ( $page_id ) {
			$wps_tofw_pages['pages']['wps_track_order_page'] = $page_id;
		}

		$wps_tofw_guest_request_form = array(
			'post_author'    => $admin_id,
			'post_name'      => 'guest-track-order-form',
			'post_title'     => __( 'Track Your Order', 'track-orders-for-woocommerce' ),
			'post_type'      => 'page',
			'post_status'    => 'publish',

		);

		$page_id = wp_insert_post( $wps_tofw_guest_request_form );

		if ( $page_id ) {
			$wps_tofw_pages['pages']['wps_guest_track_order_page'] = $page_id;
		}
		$wps_tofw_fed_ex_tracking = array(
			'post-author'   => $admin_id,
			'post_name'     => 'track-fedEx-order',
			'post_title'    => __( 'Shipment Tracking', 'track-orders-for-woocommerce' ),
			'post_type'     => 'page',
			'post_status'   => 'publish',

		);

		$page_id = wp_insert_post( $wps_tofw_fed_ex_tracking );
		if ( $page_id ) {
			$wps_tofw_pages['pages']['wps_fedex_track_order'] = $page_id;
		}

		update_option( 'wps_tofw_tracking_page', $wps_tofw_pages );

		wp_clear_scheduled_hook( 'wpswings_tracker_send_event' );
		wp_schedule_event( time() + 10, apply_filters( 'wpswings_tracker_event_recurrence', 'daily' ), 'wpswings_tracker_send_event' );
	}

}
