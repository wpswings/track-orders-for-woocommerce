<?php
/**
 * Reusable WordPress / WooCommerce stubbing helpers for unit tests.
 *
 * Use through Tests\Unit\TestCase (which already uses this trait). All stubs are
 * Brain Monkey redefinitions, so they are reset automatically after every test.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Helpers;

use Brain\Monkey\Functions;
use Mockery;

/**
 * Helpers for options, WooCommerce orders, translation/escape functions and plugin files.
 */
trait WpTestHelpers {

	/**
	 * In-memory option store used by setOptions().
	 *
	 * @var array
	 */
	protected $wp_options = array();

	/**
	 * Orders registered with mockOrder(), keyed by order ID.
	 *
	 * @var array
	 */
	protected $wc_orders = array();

	/**
	 * Order meta of mocked orders: [ order_id => [ meta_key => value ] ].
	 *
	 * @var array
	 */
	protected $wc_order_meta = array();

	/**
	 * Reset the helper state. Called from TestCase::setUp().
	 */
	protected function resetWpTestHelpers() {
		$this->wp_options    = array();
		$this->wc_orders     = array();
		$this->wc_order_meta = array();
	}

	/**
	 * Stub translation (__, esc_html__, _e, _x, _n, ...) and escape (esc_html, esc_attr,
	 * esc_url, ...) functions plus a few sanitizers with pass-through behaviour.
	 */
	protected function stubCommonWpFunctions() {
		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();
		Functions\stubs(
			array(
				'sanitize_text_field' => static function ( $value ) {
					return trim( strip_tags( (string) $value ) );
				},
				'sanitize_key'        => static function ( $key ) {
					return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
				},
				'wp_unslash'          => static function ( $value ) {
					return is_string( $value ) ? stripslashes( $value ) : $value;
				},
				'wp_kses_post'        => null,
			)
		);
	}

	/**
	 * Back get_option/update_option/add_option/delete_option with an in-memory store.
	 *
	 * Options not present in the store return the $default passed to get_option().
	 * Can be called several times in one test; values are merged.
	 *
	 * @param array $options option_name => value.
	 */
	protected function setOptions( array $options = array() ) {
		$this->wp_options = array_merge( $this->wp_options, $options );

		$store = &$this->wp_options;

		Functions\when( 'get_option' )->alias(
			static function ( $name, $default = false ) use ( &$store ) {
				return array_key_exists( $name, $store ) ? $store[ $name ] : $default;
			}
		);
		Functions\when( 'update_option' )->alias(
			static function ( $name, $value ) use ( &$store ) {
				$store[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'add_option' )->alias(
			static function ( $name, $value = '' ) use ( &$store ) {
				if ( array_key_exists( $name, $store ) ) {
					return false;
				}
				$store[ $name ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_option' )->alias(
			static function ( $name ) use ( &$store ) {
				$existed = array_key_exists( $name, $store );
				unset( $store[ $name ] );
				return $existed;
			}
		);
	}

	/**
	 * Read an option from the in-memory store (e.g. to assert what code saved).
	 *
	 * @param string $name    Option name.
	 * @param mixed  $default Value when the option is not set.
	 * @return mixed
	 */
	protected function getOption( $name, $default = null ) {
		return array_key_exists( $name, $this->wp_options ) ? $this->wp_options[ $name ] : $default;
	}

	/**
	 * Create a Mockery WC_Order and register it so wc_get_order( $id ) returns it.
	 *
	 * Supported $props: id (int, default 1), status (default 'processing'),
	 * billing_email (default 'customer@example.com'), customer_id (default 0),
	 * meta (array of meta_key => value). Any other key that is a method name
	 * (e.g. 'get_total' => '10.00' or a callable) is returned from that method.
	 *
	 * Meta is live: update_meta_data()/delete_meta_data() change what get_meta()
	 * returns; inspect it with getOrderMeta().
	 *
	 * @param array $props Order properties.
	 * @return \Mockery\MockInterface|\WC_Order
	 */
	protected function mockOrder( array $props = array() ) {
		$id = isset( $props['id'] ) ? (int) $props['id'] : 1;

		$this->wc_order_meta[ $id ] = isset( $props['meta'] ) ? (array) $props['meta'] : array();
		$meta                       = &$this->wc_order_meta[ $id ];

		$order = Mockery::mock( 'WC_Order' );
		$order->shouldReceive( 'get_id' )->andReturn( $id )->byDefault();
		$order->shouldReceive( 'get_order_number' )->andReturn( (string) $id )->byDefault();
		$order->shouldReceive( 'get_status' )->andReturn( isset( $props['status'] ) ? $props['status'] : 'processing' )->byDefault();
		$order->shouldReceive( 'get_billing_email' )->andReturn( isset( $props['billing_email'] ) ? $props['billing_email'] : 'customer@example.com' )->byDefault();
		$order->shouldReceive( 'get_customer_id' )->andReturn( isset( $props['customer_id'] ) ? (int) $props['customer_id'] : 0 )->byDefault();
		$order->shouldReceive( 'get_meta' )->andReturnUsing(
			static function ( $key = '', $single = true ) use ( &$meta ) {
				if ( '' === $key ) {
					return $meta;
				}
				if ( ! array_key_exists( $key, $meta ) ) {
					return $single ? '' : array();
				}
				return $meta[ $key ];
			}
		)->byDefault();
		$order->shouldReceive( 'update_meta_data' )->andReturnUsing(
			static function ( $key, $value ) use ( &$meta ) {
				$meta[ $key ] = $value;
			}
		)->byDefault();
		$order->shouldReceive( 'add_meta_data' )->andReturnUsing(
			static function ( $key, $value ) use ( &$meta ) {
				$meta[ $key ] = $value;
			}
		)->byDefault();
		$order->shouldReceive( 'delete_meta_data' )->andReturnUsing(
			static function ( $key ) use ( &$meta ) {
				unset( $meta[ $key ] );
			}
		)->byDefault();
		$order->shouldReceive( 'save' )->andReturn( $id )->byDefault();

		$reserved = array( 'id', 'status', 'billing_email', 'customer_id', 'meta' );
		foreach ( $props as $method => $value ) {
			if ( in_array( $method, $reserved, true ) ) {
				continue;
			}
			if ( is_callable( $value ) ) {
				$order->shouldReceive( $method )->andReturnUsing( $value )->byDefault();
			} else {
				$order->shouldReceive( $method )->andReturn( $value )->byDefault();
			}
		}

		$this->wc_orders[ $id ] = $order;
		$this->stubWcGetOrder();

		return $order;
	}

	/**
	 * (Re)stub wc_get_order() to return orders registered with mockOrder(), or false.
	 * Accepts an order ID or an order object (returned as is).
	 */
	protected function stubWcGetOrder() {
		$orders = &$this->wc_orders;
		Functions\when( 'wc_get_order' )->alias(
			static function ( $order = false ) use ( &$orders ) {
				if ( is_object( $order ) ) {
					return $order;
				}
				$id = (int) $order;
				return isset( $orders[ $id ] ) ? $orders[ $id ] : false;
			}
		);
	}

	/**
	 * Current meta of an order created with mockOrder().
	 *
	 * @param int|object  $order Order ID or mocked order.
	 * @param string|null $key   Meta key, or null for all meta.
	 * @return mixed
	 */
	protected function getOrderMeta( $order, $key = null ) {
		$id   = is_object( $order ) ? (int) $order->get_id() : (int) $order;
		$meta = isset( $this->wc_order_meta[ $id ] ) ? $this->wc_order_meta[ $id ] : array();
		if ( null === $key ) {
			return $meta;
		}
		return array_key_exists( $key, $meta ) ? $meta[ $key ] : null;
	}

	/**
	 * Load a plugin PHP file (relative to the plugin root) for testing without WordPress.
	 *
	 * @param string $relative_path e.g. 'includes/class-track-orders-for-woocommerce-activator.php'.
	 */
	protected function requirePluginFile( $relative_path ) {
		require_once TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . ltrim( $relative_path, '/' );
	}
}
