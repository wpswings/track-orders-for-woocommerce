<?php
/**
 * Fired during plugin deactivation
 *
 * @link  https://wpswings.com/
 * @since 1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */
class Track_Orders_For_Woocommerce_Deactivator {


	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since 1.0.0
	 */
	public static function track_orders_for_woocommerce_deactivate() {
		wp_clear_scheduled_hook( 'wps_tofw_daily_notification' );
		delete_option( 'wps_tofw_warning_notification_message' );
		delete_option( 'wps_tofw_warning_notification' );

		$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );

		if ( isset( $wps_tofw_pages['pages'] ) && ! empty( $wps_tofw_pages['pages'] ) ) {
			$pages = $wps_tofw_pages['pages'];
			foreach ( $pages as $page_id ) {
				wp_delete_post( $page_id, true );
			}
		}
		delete_option( 'wps_tofw_tracking_page' );

	}

}
