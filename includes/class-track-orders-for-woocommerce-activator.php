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

		$email    = get_option( 'admin_email', false );
		$admin    = get_user_by( 'email', $email );
		$admin_id = ! empty( $admin->ID ) ? $admin->ID : 1;

		$wps_tofw_pages = get_option( 'wps_tofw_tracking_page', array() );

		$pages = array(
			'wps_track_order_page' => array(
				'post_name'  => 'track-your-order',
				'post_title' => __( 'Track Order', 'track-orders-for-woocommerce' ),
			),
			'wps_guest_track_order_page' => array(
				'post_name'  => 'guest-track-order-form',
				'post_title' => __( 'Track Your Order', 'track-orders-for-woocommerce' ),
			),
			'wps_fedex_track_order' => array(
				'post_name'  => 'track-fedex-order',
				'post_title' => __( 'Shipment Tracking', 'track-orders-for-woocommerce' ),
			),
		);

		foreach ( $pages as $key => $page_data ) {

			$existing_page = get_page_by_path( $page_data['post_name'], OBJECT, 'page' );

			if ( ! empty( $existing_page->ID ) ) {
				$wps_tofw_pages['pages'][ $key ] = $existing_page->ID;
				continue;
			}

			$page_id = wp_insert_post(
				array(
					'post_author' => $admin_id,
					'post_name'   => $page_data['post_name'],
					'post_title'  => $page_data['post_title'],
					'post_type'   => 'page',
					'post_status' => 'publish',
				)
			);

			if ( ! is_wp_error( $page_id ) && $page_id ) {
				$wps_tofw_pages['pages'][ $key ] = $page_id;
			}
		}

		update_option( 'wps_tofw_tracking_page', $wps_tofw_pages );
		update_option( 'tofw_invoice_template', 'template_1' );
	}
}