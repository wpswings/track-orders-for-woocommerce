<?php
/**
 * Printful tracking auto import.
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

if ( ! class_exists( 'WPS_TOFW_Integration_Printful' ) ) {

	/**
	 * Printful (best-effort).
	 *
	 * The "Printful Integration for WooCommerce" plugin exposes no public
	 * shipment action; Printful reports shipments back through the WooCommerce
	 * REST API (order note / status). Notes mentioning Printful are parsed.
	 *
	 * @since 1.3.0
	 */
	class WPS_TOFW_Integration_Printful extends WPS_TOFW_Abstract_Shipping_Integration {

		/**
		 * ID.
		 *
		 * @return string
		 */
		public function get_id() {
			return 'printful';
		}

		/**
		 * Label.
		 *
		 * @return string
		 */
		public function get_label() {
			return __( 'Printful', 'track-orders-for-woocommerce' );
		}

		/**
		 * Note signature.
		 *
		 * @return string
		 */
		protected function get_note_signature() {
			return '/printful/i';
		}

		/**
		 * Detect the Printful plugin.
		 *
		 * @return bool
		 */
		public function is_plugin_active() {
			return class_exists( 'Printful_Base' )
				|| $this->is_any_plugin_folder_active( array( 'printful-shipping-for-woocommerce' ) );
		}
	}
}
