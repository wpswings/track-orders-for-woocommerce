<?php
/**
 * WooCommerce Shipment Tracking extension auto import.
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

if ( ! class_exists( 'WPS_TOFW_Integration_Shipment_Tracking' ) ) {

	/**
	 * WooCommerce Shipment Tracking.
	 *
	 * Watches the "_wc_shipment_tracking_items" order meta (items with
	 * tracking_number, tracking_provider, custom_tracking_provider). Many
	 * label tools (ShipStation, WooCommerce Shipping, ...) write there too.
	 *
	 * @since 1.3.0
	 */
	class WPS_TOFW_Integration_Shipment_Tracking extends WPS_TOFW_Abstract_Shipping_Integration {

		/**
		 * ID.
		 *
		 * @return string
		 */
		public function get_id() {
			return 'shipment_tracking';
		}

		/**
		 * Label.
		 *
		 * @return string
		 */
		public function get_label() {
			return __( 'WooCommerce Shipment Tracking', 'track-orders-for-woocommerce' );
		}

		/**
		 * Detect the extension.
		 *
		 * @return bool
		 */
		public function is_plugin_active() {
			return class_exists( 'WC_Shipment_Tracking_Actions' )
				|| function_exists( 'wc_st_add_tracking_number' )
				|| $this->is_any_plugin_folder_active( array( 'woocommerce-shipment-tracking' ) );
		}

		/**
		 * Meta keys.
		 *
		 * @return string[]
		 */
		public function get_watched_meta_keys() {
			return array( '_wc_shipment_tracking_items' );
		}

		/**
		 * Extract shipments from tracking items.
		 *
		 * @param string $meta_key   Meta key.
		 * @param mixed  $meta_value Items.
		 * @return array[]
		 */
		public function extract_from_meta( $meta_key, $meta_value ) {
			if ( ! is_array( $meta_value ) ) {
				return array();
			}
			$shipments = array();
			foreach ( $meta_value as $item ) {
				$item = (array) $item;
				if ( empty( $item['tracking_number'] ) ) {
					continue;
				}
				$carrier     = ! empty( $item['custom_tracking_provider'] ) ? $item['custom_tracking_provider'] : ( isset( $item['tracking_provider'] ) ? $item['tracking_provider'] : '' );
				$shipments[] = array(
					'tracking_number' => (string) $item['tracking_number'],
					'carrier'         => (string) $carrier,
				);
			}
			return $shipments;
		}
	}
}
