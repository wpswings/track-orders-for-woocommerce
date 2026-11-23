<?php
/**
 * Base class for the marketplace (multi-vendor) integrations.
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
 * Every marketplace integration (Dokan, WCFM, WC Vendors) extends this class.
 *
 * An integration answers marketplace specific questions (who is the current
 * vendor, does the vendor own this order, which items belong to the vendor,
 * where are the vendor shipments stored) and renders the vendor form at the
 * right place of the marketplace vendor dashboard.
 *
 * @since 1.3.0
 */
abstract class Track_Orders_For_Woocommerce_Vendor_Integration {

	/**
	 * Shared multi-vendor manager.
	 *
	 * @since 1.3.0
	 * @var   Track_Orders_For_Woocommerce_Multi_Vendor
	 */
	protected $manager;

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 * @param Track_Orders_For_Woocommerce_Multi_Vendor $manager Multi-vendor manager.
	 */
	public function __construct( $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Unique key of the marketplace, e.g. `dokan`.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	abstract public function get_key();

	/**
	 * Human readable marketplace name.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Whether the marketplace plugin is active.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	abstract public static function is_marketplace_active();

	/**
	 * Register the vendor dashboard hooks of the marketplace.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	abstract public function register_hooks();

	/**
	 * Vendor id of the logged in user, 0 when the user is not a vendor.
	 *
	 * @since 1.3.0
	 * @return int
	 */
	abstract public function get_current_vendor_id();

	/**
	 * Store name of a vendor.
	 *
	 * @since 1.3.0
	 * @param int $vendor_id Vendor id.
	 * @return string
	 */
	abstract public function get_store_name( $vendor_id );

	/**
	 * Line items of the order that belong to the vendor.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order     Order (the storage order of the vendor).
	 * @param int      $vendor_id Vendor id.
	 * @return WC_Order_Item_Product[] Keyed by item id.
	 */
	abstract public function get_vendor_items( $order, $vendor_id );

	/**
	 * Vendor groups of a customer order.
	 *
	 * Each group is an array with the keys `vendor_id` (int) and `order`
	 * (WC_Order the shipments of that vendor are stored on).
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order the customer is looking at.
	 * @return array[]
	 */
	abstract public function get_customer_groups( $order );

	/**
	 * Whether the vendor may add tracking to the order.
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
		return ! empty( $this->get_vendor_items( $order, $vendor_id ) );
	}

	/**
	 * Render the vendor tracking form for an order on the vendor dashboard.
	 *
	 * Accepts an order object or an order id because the marketplaces pass
	 * different arguments to their hooks.
	 *
	 * @since 1.3.0
	 * @param WC_Order|int $order Order or order id.
	 * @return void
	 */
	public function render_dashboard_form( $order ) {
		static $rendered = array();

		if ( ! $order instanceof WC_Order ) {
			$order = is_numeric( $order ) ? wc_get_order( absint( $order ) ) : false;
		}
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$vendor_id = $this->get_current_vendor_id();
		if ( ! $this->vendor_owns_order( $order, $vendor_id ) ) {
			return;
		}

		// Some marketplaces fire more than one of the hooks we listen to.
		$render_key = $this->get_key() . '-' . $order->get_id();
		if ( isset( $rendered[ $render_key ] ) ) {
			return;
		}
		$rendered[ $render_key ] = true;

		$this->manager->render_vendor_form( $this, $order, $vendor_id );
	}
}
