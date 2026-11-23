<?php
/**
 * Tracking provider contract used by the automatic carrier status sync.
 *
 * @link  https://wpswings.com/
 * @since 1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes/carrier-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'Wps_Tofw_Tracking_Provider' ) ) {

	/**
	 * A tracking provider fetches the live shipment status for a tracking number
	 * from a carrier (or aggregator) API and returns it in a normalized shape.
	 *
	 * Register additional providers with the `wps_tofw_carrier_sync_providers` filter.
	 *
	 * @since 1.3.0
	 */
	interface Wps_Tofw_Tracking_Provider {

		/**
		 * Unique provider id, e.g. `17track`.
		 *
		 * @since 1.3.0
		 * @return string
		 */
		public function get_id();

		/**
		 * Human readable provider name.
		 *
		 * @since 1.3.0
		 * @return string
		 */
		public function get_label();

		/**
		 * Whether the provider has everything it needs (credentials, PHP extensions...).
		 *
		 * @since 1.3.0
		 * @return bool
		 */
		public function is_configured();

		/**
		 * Whether the provider can track shipments of the given carrier.
		 *
		 * @since 1.3.0
		 * @param string $carrier Carrier slug stored on the order (e.g. `fedex`), may be empty.
		 * @return bool
		 */
		public function supports( $carrier );

		/**
		 * Fetch the current shipment status.
		 *
		 * Must return a WP_Error on failure or an array:
		 * array(
		 *     'status'     => string One of Track_Orders_For_Woocommerce_Carrier_Sync::get_status_labels() keys.
		 *     'raw_status' => string Carrier specific status code/text.
		 *     'events'     => array  List of array( 'time' => int (UTC timestamp), 'description' => string, 'location' => string, 'status' => string ).
		 * )
		 *
		 * @since 1.3.0
		 * @param string $carrier         Carrier slug stored on the order, may be empty.
		 * @param string $tracking_number Tracking number.
		 * @return array|WP_Error
		 */
		public function get_status( $carrier, $tracking_number );
	}
}
