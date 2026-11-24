<?php
/**
 * Contract for shipping-plugin tracking auto import integrations.
 *
 * @link       https://wpswings.com/
 * @since      1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes/integrations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( 'WPS_TOFW_Shipping_Integration_Interface' ) ) {

	/**
	 * Each supported shipping / label plugin implements this interface.
	 *
	 * Integrations never write to the order directly: they hand extracted
	 * shipments to the manager, which maps the carrier, de-duplicates and saves
	 * through WPS_TOFW_Tracking_Service (the manual tracking code path).
	 *
	 * @since 1.3.0
	 */
	interface WPS_TOFW_Shipping_Integration_Interface {

		/**
		 * Unique integration ID (used in the option name).
		 *
		 * @return string
		 */
		public function get_id();

		/**
		 * Human readable name.
		 *
		 * @return string
		 */
		public function get_label();

		/**
		 * Whether the third-party plugin / connection is detected on this site.
		 *
		 * @return bool
		 */
		public function is_plugin_active();

		/**
		 * Short text describing how the integration is detected / hooked.
		 *
		 * @return string
		 */
		public function get_status_text();

		/**
		 * Whether the merchant enabled the auto import for this integration.
		 *
		 * @return bool
		 */
		public function is_enabled();

		/**
		 * Register integration specific hooks (only called when enabled).
		 *
		 * @return void
		 */
		public function register_hooks();

		/**
		 * Order meta keys the integration writes tracking data to.
		 *
		 * @return string[]
		 */
		public function get_watched_meta_keys();

		/**
		 * Extract shipments from one of the watched meta values.
		 *
		 * @param string $meta_key   Meta key.
		 * @param mixed  $meta_value Meta value.
		 * @return array[] List of array( 'tracking_number' => string, 'carrier' => string ).
		 */
		public function extract_from_meta( $meta_key, $meta_value );

		/**
		 * Extract a shipment from an order note written by the integration.
		 *
		 * @param string $note Plain note content.
		 * @return array|null array( 'tracking_number' => string, 'carrier' => string ) or null.
		 */
		public function extract_from_note( $note );
	}
}
