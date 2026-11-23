<?php
/**
 * Dokan integration for vendor tracking.
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
 * Dokan creates one sub-order (child order) per vendor, so vendor shipments
 * are stored on the sub-order itself. When an order contains products of a
 * single vendor Dokan does not create sub-orders and the order itself
 * belongs to the vendor.
 *
 * @since 1.3.0
 */
class Track_Orders_For_Woocommerce_Vendor_Dokan extends Track_Orders_For_Woocommerce_Vendor_Integration {

	/**
	 * Unique key.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_key() {
		return 'dokan';
	}

	/**
	 * Label.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_label() {
		return 'Dokan';
	}

	/**
	 * Whether Dokan is active.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public static function is_marketplace_active() {
		return function_exists( 'dokan_get_seller_id_by_order' );
	}

	/**
	 * Register Dokan vendor dashboard hooks.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function register_hooks() {
		/**
		 * Dokan vendor dashboard hooks the tracking form is rendered on.
		 *
		 * @since 1.3.0
		 * @param string[] $hooks Hook names, each receiving the WC_Order as first argument.
		 */
		$hooks = apply_filters( 'wps_tofw_dokan_vendor_tracking_hooks', array( 'dokan_order_detail_after_order_items' ) );
		foreach ( (array) $hooks as $hook ) {
			add_action( $hook, array( $this, 'render_dashboard_form' ), 20, 1 );
		}
	}

	/**
	 * Current vendor id (vendor staff resolve to their vendor).
	 *
	 * @since 1.3.0
	 * @return int
	 */
	public function get_current_vendor_id() {
		$vendor_id = function_exists( 'dokan_get_current_user_id' ) ? (int) dokan_get_current_user_id() : get_current_user_id();
		if ( $vendor_id <= 0 ) {
			return 0;
		}
		if ( function_exists( 'dokan_is_user_seller' ) && ! dokan_is_user_seller( $vendor_id ) ) {
			return 0;
		}
		return $vendor_id;
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
		if ( function_exists( 'dokan_get_store_info' ) ) {
			$info = dokan_get_store_info( $vendor_id );
			$name = ! empty( $info['store_name'] ) ? $info['store_name'] : '';
		}
		if ( '' === $name ) {
			$user = get_userdata( $vendor_id );
			$name = $user ? $user->display_name : '';
		}
		return $name;
	}

	/**
	 * Seller id of an order.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return int
	 */
	protected function get_order_seller_id( $order ) {
		$seller_id = dokan_get_seller_id_by_order( $order->get_id() );
		// Older Dokan versions return an array of sellers for multi-vendor parent orders.
		return is_numeric( $seller_id ) ? (int) $seller_id : 0;
	}

	/**
	 * Whether the vendor owns the (sub-)order.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order     Order.
	 * @param int      $vendor_id Vendor id.
	 * @return bool
	 */
	public function vendor_owns_order( $order, $vendor_id ) {
		if ( ! $order instanceof WC_Order || $vendor_id <= 0 ) {
			return false;
		}
		return $this->get_order_seller_id( $order ) === (int) $vendor_id;
	}

	/**
	 * All items of a Dokan sub-order belong to its vendor.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order     Order.
	 * @param int      $vendor_id Vendor id.
	 * @return WC_Order_Item_Product[]
	 */
	public function get_vendor_items( $order, $vendor_id ) {
		if ( ! $this->vendor_owns_order( $order, $vendor_id ) ) {
			return array();
		}
		return $order->get_items( 'line_item' );
	}

	/**
	 * Vendor groups: the Dokan sub-orders of a parent order, or the order itself.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return array[]
	 */
	public function get_customer_groups( $order ) {
		$groups = array();

		$sub_orders = wc_get_orders(
			array(
				'parent' => $order->get_id(),
				'type'   => 'shop_order',
				'limit'  => -1,
			)
		);

		foreach ( $sub_orders as $sub_order ) {
			// Skip child orders created by the plugin's own partial shipment feature.
			if ( 'yes' === $sub_order->get_meta( '_wps_is_child_order' ) ) {
				continue;
			}
			$seller_id = $this->get_order_seller_id( $sub_order );
			if ( $seller_id > 0 ) {
				$groups[] = array(
					'vendor_id' => $seller_id,
					'order'     => $sub_order,
				);
			}
		}

		if ( empty( $groups ) ) {
			$seller_id = $this->get_order_seller_id( $order );
			if ( $seller_id > 0 ) {
				$groups[] = array(
					'vendor_id' => $seller_id,
					'order'     => $order,
				);
			}
		}

		return $groups;
	}
}
