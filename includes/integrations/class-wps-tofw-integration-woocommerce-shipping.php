<?php
/**
 * WooCommerce Shipping (and legacy WooCommerce Shipping & Tax) label auto import.
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

if ( ! class_exists( 'WPS_TOFW_Integration_WooCommerce_Shipping' ) ) {

	/**
	 * WooCommerce Shipping labels.
	 *
	 * The plugin exposes no public "label purchased" action, so the order meta
	 * holding the purchased labels is watched instead ("wc_connect_labels" in
	 * WooCommerce Shipping & Tax, "wcshipping_labels" in WooCommerce Shipping).
	 * Each label carries "tracking", "carrier_id" and "service_name". Tracking
	 * may arrive after purchase (status refresh); the meta update is caught then.
	 *
	 * @since 1.3.0
	 */
	class WPS_TOFW_Integration_WooCommerce_Shipping extends WPS_TOFW_Abstract_Shipping_Integration {

		/**
		 * ID.
		 *
		 * @return string
		 */
		public function get_id() {
			return 'woocommerce_shipping';
		}

		/**
		 * Label.
		 *
		 * @return string
		 */
		public function get_label() {
			return __( 'WooCommerce Shipping', 'track-orders-for-woocommerce' );
		}

		/**
		 * Detect WooCommerce Shipping / WooCommerce Shipping & Tax.
		 *
		 * @return bool
		 */
		public function is_plugin_active() {
			return class_exists( 'WC_Connect_Loader' )
				|| defined( 'WCSHIPPING_VERSION' )
				|| $this->is_any_plugin_folder_active( array( 'woocommerce-shipping', 'woocommerce-services' ) );
		}

		/**
		 * Label meta keys.
		 *
		 * @return string[]
		 */
		public function get_watched_meta_keys() {
			/**
			 * Filter the WooCommerce Shipping label meta keys.
			 *
			 * @since 1.3.0
			 * @param string[] $keys Meta keys.
			 */
			return (array) apply_filters( 'wps_tofw_woocommerce_shipping_label_meta_keys', array( 'wc_connect_labels', 'wcshipping_labels', '_wcshipping-labels' ) );
		}

		/**
		 * Extract shipments from the labels array.
		 *
		 * @param string $meta_key   Meta key.
		 * @param mixed  $meta_value Labels (array or JSON string).
		 * @return array[]
		 */
		public function extract_from_meta( $meta_key, $meta_value ) {
			if ( is_string( $meta_value ) ) {
				$decoded    = json_decode( $meta_value, true );
				$meta_value = is_array( $decoded ) ? $decoded : array();
			}
			if ( ! is_array( $meta_value ) ) {
				return array();
			}

			$shipments = array();
			// Labels are stored newest first; import oldest first so the newest ends up current.
			foreach ( array_reverse( $meta_value ) as $label ) {
				$label = (array) $label;
				if ( empty( $label['tracking'] ) ) {
					continue;
				}
				if ( ! empty( $label['refund'] ) ) {
					continue; // Refunded / voided label.
				}
				if ( isset( $label['status'] ) && in_array( strtoupper( (string) $label['status'] ), array( 'PURCHASE_ERROR', 'ANONYMIZED' ), true ) ) {
					continue;
				}
				$carrier = '';
				if ( ! empty( $label['carrier_id'] ) ) {
					$carrier = (string) $label['carrier_id'];
				} elseif ( ! empty( $label['service_name'] ) ) {
					$carrier = (string) $label['service_name'];
				}
				$shipments[] = array(
					'tracking_number' => (string) $label['tracking'],
					'carrier'         => $carrier,
				);
			}
			return $shipments;
		}
	}
}
