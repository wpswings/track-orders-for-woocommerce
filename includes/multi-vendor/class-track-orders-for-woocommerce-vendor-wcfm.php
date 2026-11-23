<?php
/**
 * WCFM Marketplace integration for vendor tracking.
 *
 * @link  https://wpswings.com/
 * @since 1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes/multi-vendor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCFM does not split orders, so vendor shipments are stored on the parent
 * order keyed by vendor id. A vendor owns the line items whose product
 * belongs to the vendor.
 *
 * @since 1.3.0
 */
class Track_Orders_For_Woocommerce_Vendor_Wcfm extends Track_Orders_For_Woocommerce_Vendor_Integration {

	/**
	 * Unique key.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_key() {
		return 'wcfm';
	}

	/**
	 * Label.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_label() {
		return 'WCFM';
	}

	/**
	 * Whether WCFM (Marketplace) is active.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public static function is_marketplace_active() {
		return function_exists( 'wcfm_get_vendor_id_by_post' ) && function_exists( 'wcfm_is_vendor' );
	}

	/**
	 * Register WCFM vendor dashboard hooks.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function register_hooks() {
		/**
		 * WCFM order details hooks the tracking form is rendered on.
		 * Each hook must pass the order id (or WC_Order) as first argument.
		 *
		 * @since 1.3.0
		 * @param string[] $hooks Hook names.
		 */
		$hooks = apply_filters( 'wps_tofw_wcfm_vendor_tracking_hooks', array( 'end_wcfm_orders_details' ) );
		foreach ( (array) $hooks as $hook ) {
			add_action( $hook, array( $this, 'render_dashboard_form' ), 20, 1 );
		}
	}

	/**
	 * Current vendor id.
	 *
	 * @since 1.3.0
	 * @return int
	 */
	public function get_current_vendor_id() {
		if ( ! is_user_logged_in() || ! wcfm_is_vendor() ) {
			return 0;
		}
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WCFM core filter.
		return absint( apply_filters( 'wcfm_current_vendor_id', get_current_user_id() ) );
	}

	/**
	 * Store name.
	 *
	 * @since 1.3.0
	 * @param int $vendor_id Vendor id.
	 * @return string
	 */
	public function get_store_name( $vendor_id ) {
		$name = '';
		if ( function_exists( 'wcfm_get_vendor_store_name' ) ) {
			$name = wp_strip_all_tags( (string) wcfm_get_vendor_store_name( $vendor_id ) );
		}
		if ( '' === $name ) {
			$user = get_userdata( $vendor_id );
			$name = $user ? $user->display_name : '';
		}
		return $name;
	}

	/**
	 * Vendor id of a line item.
	 *
	 * @since 1.3.0
	 * @param WC_Order_Item_Product $item Line item.
	 * @return int
	 */
	protected function get_item_vendor_id( $item ) {
		$vendor_id = (int) $item->get_meta( '_vendor_id', true );
		if ( $vendor_id <= 0 ) {
			$vendor_id = (int) wcfm_get_vendor_id_by_post( $item->get_product_id() );
		}
		return $vendor_id;
	}

	/**
	 * Items of the order belonging to the vendor.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order     Order.
	 * @param int      $vendor_id Vendor id.
	 * @return WC_Order_Item_Product[]
	 */
	public function get_vendor_items( $order, $vendor_id ) {
		$items = array();
		if ( ! $order instanceof WC_Order || $vendor_id <= 0 ) {
			return $items;
		}
		foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
			if ( $this->get_item_vendor_id( $item ) === (int) $vendor_id ) {
				$items[ $item_id ] = $item;
			}
		}
		return $items;
	}

	/**
	 * One group per vendor found in the order items.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return array[]
	 */
	public function get_customer_groups( $order ) {
		$groups = array();
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$vendor_id = $this->get_item_vendor_id( $item );
			if ( $vendor_id > 0 && ! isset( $groups[ $vendor_id ] ) ) {
				$groups[ $vendor_id ] = array(
					'vendor_id' => $vendor_id,
					'order'     => $order,
				);
			}
		}
		return array_values( $groups );
	}
}
