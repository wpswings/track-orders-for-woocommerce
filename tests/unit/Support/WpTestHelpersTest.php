<?php
/**
 * Self-tests for the shared unit test helpers.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Support;

use Brain\Monkey\Filters;
use Tests\Unit\TestCase;

/**
 * Proves the helpers behave as documented in tests/README.md.
 */
class WpTestHelpersTest extends TestCase {

	/**
	 * Options set per test are returned, unknown options fall back to the default.
	 */
	public function test_set_options_backs_option_functions() {
		$this->setOptions( array( 'wps_tofw_is_plugin_enable' => 'on' ) );

		$this->assertSame( 'on', get_option( 'wps_tofw_is_plugin_enable' ) );
		$this->assertSame( 'no', get_option( 'tofw_enable_track_order', 'no' ) );

		update_option( 'tofw_enable_track_order', 'on' );
		$this->assertSame( 'on', get_option( 'tofw_enable_track_order' ) );
		$this->assertSame( 'on', $this->getOption( 'tofw_enable_track_order' ) );

		delete_option( 'tofw_enable_track_order' );
		$this->assertNull( $this->getOption( 'tofw_enable_track_order' ) );
	}

	/**
	 * Options do not leak between tests.
	 */
	public function test_options_are_reset_between_tests() {
		$this->setOptions();
		$this->assertFalse( get_option( 'wps_tofw_is_plugin_enable' ) );
	}

	/**
	 * Mocked orders are returned by wc_get_order and keep live meta.
	 */
	public function test_mock_order_and_wc_get_order() {
		$order = $this->mockOrder(
			array(
				'id'            => 42,
				'status'        => 'completed',
				'billing_email' => 'guest@example.com',
				'customer_id'   => 7,
				'meta'          => array( 'wps_tofw_package_tracking_number' => 'TRK123' ),
				'get_total'     => '19.99',
			)
		);

		$this->assertSame( $order, wc_get_order( 42 ) );
		$this->assertFalse( wc_get_order( 999 ) );
		$this->assertSame( 42, $order->get_id() );
		$this->assertSame( 'completed', $order->get_status() );
		$this->assertSame( 'guest@example.com', $order->get_billing_email() );
		$this->assertSame( 7, $order->get_customer_id() );
		$this->assertSame( '19.99', $order->get_total() );
		$this->assertSame( 'TRK123', $order->get_meta( 'wps_tofw_package_tracking_number', true ) );
		$this->assertSame( '', $order->get_meta( 'missing_key', true ) );

		$order->update_meta_data( 'wps_tofw_selected_shipping_service', 'fedex' );
		$order->save();

		$this->assertSame( 'fedex', $order->get_meta( 'wps_tofw_selected_shipping_service' ) );
		$this->assertSame( 'fedex', $this->getOrderMeta( 42, 'wps_tofw_selected_shipping_service' ) );
	}

	/**
	 * Translation/escape stubs and apply_filters pass values through.
	 */
	public function test_common_wp_function_stubs_and_filters() {
		$this->stubCommonWpFunctions();

		$this->assertSame( 'Track Order', __( 'Track Order', 'track-orders-for-woocommerce' ) );
		$this->assertSame( '&lt;b&gt;', esc_html__( '<b>', 'track-orders-for-woocommerce' ) );
		$this->assertSame( 'value', apply_filters( 'wps_tofw_any_filter', 'value', 'extra' ) );

		Filters\expectApplied( 'wps_tofw_any_filter' )->once()->andReturn( 'filtered' );
		$this->assertSame( 'filtered', apply_filters( 'wps_tofw_any_filter', 'value' ) );
	}
}
