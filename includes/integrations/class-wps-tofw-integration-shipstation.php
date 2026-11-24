<?php
/**
 * ShipStation tracking auto import.
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

if ( ! class_exists( 'WPS_TOFW_Integration_ShipStation' ) ) {

	/**
	 * ShipStation (WooCommerce ShipStation Integration).
	 *
	 * Primary: the "woocommerce_shipstation_shipnotify" action fired by the
	 * plugin's shipnotify endpoint with ( $order, array( 'tracking_number',
	 * 'carrier', 'ship_date', 'xml' ) ).
	 * Fallback: the "... shipped via <carrier> on <date> with tracking number
	 * <number>" order note the same endpoint adds.
	 *
	 * @since 1.3.0
	 */
	class WPS_TOFW_Integration_ShipStation extends WPS_TOFW_Abstract_Shipping_Integration {

		/**
		 * ID.
		 *
		 * @return string
		 */
		public function get_id() {
			return 'shipstation';
		}

		/**
		 * Label.
		 *
		 * @return string
		 */
		public function get_label() {
			return __( 'ShipStation', 'track-orders-for-woocommerce' );
		}

		/**
		 * Detect the ShipStation plugin.
		 *
		 * @return bool
		 */
		public function is_plugin_active() {
			return class_exists( 'WC_ShipStation_Integration' )
				|| defined( 'WC_SHIPSTATION_VERSION' )
				|| $this->is_any_plugin_folder_active( array( 'woocommerce-shipstation-integration', 'woocommerce-shipstation' ) );
		}

		/**
		 * Hook the shipnotify action.
		 *
		 * @return void
		 */
		public function register_hooks() {
			add_action( 'woocommerce_shipstation_shipnotify', array( $this, 'handle_shipnotify' ), 10, 2 );
		}

		/**
		 * Handle ShipStation shipnotify.
		 *
		 * @param WC_Order|int $order Order.
		 * @param array        $args  Shipment args.
		 * @return void
		 */
		public function handle_shipnotify( $order, $args = array() ) {
			$order = ( $order instanceof WC_Order ) ? $order : wc_get_order( $order );
			if ( ! $order || ! is_array( $args ) || empty( $args['tracking_number'] ) ) {
				return;
			}
			$this->manager->import_tracking( $order, $args['tracking_number'], isset( $args['carrier'] ) ? $args['carrier'] : '', $this->get_id() );
		}

		/**
		 * Note signature.
		 *
		 * @return string
		 */
		protected function get_note_signature() {
			return '/shipstation|shipped\s+via\s+.+?\s+with\s+tracking\s+number/i';
		}
	}
}
