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
		if ( get_option( 'tofw_radio_reset_license', false ) ) {
			$license_key = get_option( 'wps_tofw_license_key', false );
			$api_params  = array(
				'slm_action' => 'slm_deactivate',
				'secret_key' => TRACK_ORDERS_FOR_WOOCOMMERCE_SPECIAL_SECRET_KEY,
				'license_key' => $license_key,
				'registered_domain' => $_SERVER['SERVER_NAME'],
				'item_reference' => urlencode( TRACK_ORDERS_FOR_WOOCOMMERCE_ITEM_REFERENCE ),
				'product_reference' => 'wpsPK-2965',
			);
			$query       = esc_url_raw( add_query_arg( $api_params, TRACK_ORDERS_FOR_WOOCOMMERCE_LICENSE_SERVER_URL ) );
			$response    = wp_remote_get(
				$query,
				array(
					'timeout' => 20,
					'sslverify' => false,
				)
			);

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			if ( isset( $license_data->result ) && 'success' === $license_data->result ) {
				   delete_option( 'wps_tofw_license_check' );
				   delete_option( 'wps_tofw_license_key' );
			}
		}

	}

}
