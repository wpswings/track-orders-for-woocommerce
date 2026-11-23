<?php
/**
 * Sendcloud tracking auto import.
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

if ( ! class_exists( 'WPS_TOFW_Integration_Sendcloud' ) ) {

	/**
	 * Sendcloud (best-effort).
	 *
	 * The Sendcloud plugins expose no public "label created" action; Sendcloud
	 * posts the shipment back through the WooCommerce REST API as an order note,
	 * usually with a tracking.sendcloud.sc/forward?carrier=..&code=.. link.
	 * That link is parsed first, then the generic note patterns.
	 *
	 * @since 1.3.0
	 */
	class WPS_TOFW_Integration_Sendcloud extends WPS_TOFW_Abstract_Shipping_Integration {

		/**
		 * ID.
		 *
		 * @return string
		 */
		public function get_id() {
			return 'sendcloud';
		}

		/**
		 * Label.
		 *
		 * @return string
		 */
		public function get_label() {
			return __( 'Sendcloud', 'track-orders-for-woocommerce' );
		}

		/**
		 * Note signature.
		 *
		 * @return string
		 */
		protected function get_note_signature() {
			return '/sendcloud/i';
		}

		/**
		 * Detect a Sendcloud plugin or a Sendcloud REST API key.
		 *
		 * @return bool
		 */
		public function is_plugin_active() {
			return $this->is_any_plugin_folder_active( array( 'sendcloud-shipping', 'sendcloud-connected-shipping', 'sendcloud-shipping-v2' ) )
				|| $this->has_rest_api_key_for( 'Sendcloud' );
		}

		/**
		 * Parse the Sendcloud tracking forward link, then generic carrier links.
		 *
		 * @param string $text Note text including hrefs.
		 * @return array|null
		 */
		protected function extract_from_tracking_url( $text ) {
			if ( preg_match( '#https?://[^\s"\']*sendcloud\.sc/forward\?[^\s"\']+#i', $text, $m ) ) {
				$query = wp_parse_url( html_entity_decode( $m[0] ), PHP_URL_QUERY );
				$args  = array();
				parse_str( (string) $query, $args );
				if ( ! empty( $args['code'] ) && $this->looks_like_tracking_number( $args['code'] ) ) {
					return array(
						'tracking_number' => sanitize_text_field( $args['code'] ),
						'carrier'         => isset( $args['carrier'] ) ? sanitize_text_field( $args['carrier'] ) : '',
					);
				}
			}
			return parent::extract_from_tracking_url( $text );
		}
	}
}
