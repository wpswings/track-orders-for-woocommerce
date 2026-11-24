<?php
/**
 * Shippo tracking auto import.
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

if ( ! class_exists( 'WPS_TOFW_Integration_Shippo' ) ) {

	/**
	 * Shippo (best-effort).
	 *
	 * Shippo connects through the WooCommerce REST API and writes the shipment
	 * back as an order note / order completion. No public WordPress hook
	 * exists, so notes mentioning Shippo are parsed for carrier + tracking.
	 *
	 * @since 1.3.0
	 */
	class WPS_TOFW_Integration_Shippo extends WPS_TOFW_Abstract_Shipping_Integration {

		/**
		 * ID.
		 *
		 * @return string
		 */
		public function get_id() {
			return 'shippo';
		}

		/**
		 * Label.
		 *
		 * @return string
		 */
		public function get_label() {
			return __( 'Shippo', 'track-orders-for-woocommerce' );
		}

		/**
		 * Plain tracking notes are accepted when this is the only note-based integration enabled.
		 *
		 * @return bool
		 */
		public function accepts_unsigned_notes() {
			return true;
		}

		/**
		 * Note signature.
		 *
		 * @return string
		 */
		protected function get_note_signature() {
			return '/shippo|goshippo\\.com/i';
		}

		/**
		 * Shippo has no WordPress plugin; it connects through the WooCommerce
		 * REST API, so it cannot be detected reliably and is always available.
		 *
		 * @return bool
		 */
		public function is_plugin_active() {
			return true;
		}

		/**
		 * Status text.
		 *
		 * @return string
		 */
		public function get_status_text() {
			return $this->has_rest_api_key_for( 'Shippo' )
				? __( 'Status: connected via the WooCommerce REST API (Shippo API key found). Tracking is read from the order notes Shippo adds.', 'track-orders-for-woocommerce' )
				: __( 'Status: no WooCommerce REST API key named "Shippo" found (Shippo connects via the REST API, not a plugin). Tracking is read from the order notes Shippo adds.', 'track-orders-for-woocommerce' );
		}
	}
}
