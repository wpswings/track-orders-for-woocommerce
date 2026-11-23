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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


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
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'wps_tofw_delivery_alerts_daily_check', array(), 'wps-tofw-delivery-alerts' );
			// No args/group: batch actions carry a page argument, so cancel every action of the hook.
			as_unschedule_all_actions( 'wps_tofw_delivery_alerts_process_batch' );
		}
		delete_transient( 'wps_tofw_delivery_alerts_schedule_checked' );

	}

}
