<?php
/**
 * WC Vendors integration for vendor tracking.
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
 * WC Vendors does not split orders, so vendor shipments are stored on the
 * parent order keyed by vendor id.
 *
 * The free WC Vendors dashboard has no per-order details screen, so the form
 * is rendered below the dashboard when the `wps_tofw_vendor_order` query
 * argument is present (a "Tracking" link is added to the order actions), and
 * through the `[wps_tofw_vendor_tracking]` shortcode.
 *
 * @since 1.3.0
 */
class Track_Orders_For_Woocommerce_Vendor_Wcvendors extends Track_Orders_For_Woocommerce_Vendor_Integration {

	/**
	 * Unique key.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_key() {
		return 'wcvendors';
	}

	/**
	 * Label.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_label() {
		return 'WC Vendors';
	}

	/**
	 * Whether WC Vendors is active.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public static function is_marketplace_active() {
		return class_exists( 'WCV_Vendors' );
	}

	/**
	 * Register WC Vendors dashboard hooks.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function register_hooks() {
		/**
		 * WC Vendors (free / Pro) dashboard hooks below which the tracking form
		 * for the `wps_tofw_vendor_order` query argument is rendered.
		 *
		 * @since 1.3.0
		 * @param string[] $hooks Hook names.
		 */
		$hooks = apply_filters( 'wps_tofw_wcvendors_vendor_tracking_hooks', array( 'wcvendors_after_dashboard', 'wcv_pro_after_dashboard' ) );
		foreach ( (array) $hooks as $hook ) {
			add_action( $hook, array( $this, 'render_requested_order_form' ) );
		}
		add_filter( 'wcvendors_order_actions', array( $this, 'add_order_action' ), 20, 2 );
		add_shortcode( 'wps_tofw_vendor_tracking', array( $this, 'tracking_shortcode' ) );
	}

	/**
	 * Add a "Tracking" link to the WC Vendors order actions.
	 *
	 * Only touches the array when it uses the label/url structure WC Vendors
	 * expects, to avoid breaking other dashboard versions.
	 *
	 * @since 1.3.0
	 * @param mixed $actions Order actions.
	 * @param mixed $order   Order object or id.
	 * @return mixed
	 */
	public function add_order_action( $actions, $order = null ) {
		if ( ! is_array( $actions ) ) {
			return $actions;
		}
		$order_id = $order instanceof WC_Order ? $order->get_id() : absint( $order );
		if ( ! $order_id ) {
			return $actions;
		}
		$first = reset( $actions );
		if ( false !== $first && ! ( is_array( $first ) && isset( $first['label'], $first['url'] ) ) ) {
			return $actions;
		}
		$actions['wps_tofw_tracking'] = array(
			'label'  => __( 'Shipment Tracking', 'track-orders-for-woocommerce' ),
			'url'    => add_query_arg( 'wps_tofw_vendor_order', $order_id ),
			'target' => '_self',
			'class'  => 'wps-tofw-vendor-tracking-link',
		);
		return $actions;
	}

	/**
	 * Requested order id from the query string.
	 *
	 * @since 1.3.0
	 * @return int
	 */
	protected function get_requested_order_id() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, ownership is verified before rendering.
		return isset( $_GET['wps_tofw_vendor_order'] ) ? absint( wp_unslash( $_GET['wps_tofw_vendor_order'] ) ) : 0;
	}

	/**
	 * Render the form for the order requested in the query string.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function render_requested_order_form() {
		$order_id = $this->get_requested_order_id();
		if ( $order_id ) {
			$this->render_dashboard_form( $order_id );
		}
	}

	/**
	 * Shortcode `[wps_tofw_vendor_tracking order_id="123"]`.
	 *
	 * Without `order_id` the order is read from the `wps_tofw_vendor_order`
	 * query argument.
	 *
	 * @since 1.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function tracking_shortcode( $atts ) {
		$atts     = shortcode_atts( array( 'order_id' => 0 ), (array) $atts, 'wps_tofw_vendor_tracking' );
		$order_id = absint( $atts['order_id'] );
		if ( ! $order_id ) {
			$order_id = $this->get_requested_order_id();
		}
		if ( ! $order_id ) {
			return '';
		}
		ob_start();
		$this->render_dashboard_form( $order_id );
		return ob_get_clean();
	}

	/**
	 * Current vendor id.
	 *
	 * @since 1.3.0
	 * @return int
	 */
	public function get_current_vendor_id() {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 || ! WCV_Vendors::is_vendor( $user_id ) ) {
			return 0;
		}
		return $user_id;
	}

	/**
	 * Store name.
	 *
	 * @since 1.3.0
	 * @param int $vendor_id Vendor id.
	 * @return string
	 */
	public function get_store_name( $vendor_id ) {
		$name = method_exists( 'WCV_Vendors', 'get_vendor_shop_name' ) ? (string) WCV_Vendors::get_vendor_shop_name( $vendor_id ) : '';
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
		return (int) WCV_Vendors::get_vendor_from_product( $item->get_product_id() );
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
			if ( $vendor_id > 0 && WCV_Vendors::is_vendor( $vendor_id ) && ! isset( $groups[ $vendor_id ] ) ) {
				$groups[ $vendor_id ] = array(
					'vendor_id' => $vendor_id,
					'order'     => $order,
				);
			}
		}
		return array_values( $groups );
	}
}
