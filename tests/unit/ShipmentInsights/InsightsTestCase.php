<?php
/**
 * Shared setup for the WPS-8404 delivery alerts / shipping analytics tests.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\ShipmentInsights;

use Brain\Monkey\Functions;
use Tests\Unit\TestCase;

/**
 * Loads Track_Orders_For_Woocommerce_Shipment_Insights and stubs the WordPress
 * functions it needs (timezone, translations, escaping).
 */
abstract class InsightsTestCase extends TestCase {

	/**
	 * Class under test.
	 *
	 * @var \Track_Orders_For_Woocommerce_Shipment_Insights
	 */
	protected $insights;

	/**
	 * Load the class and stub common functions.
	 */
	protected function set_up() {
		parent::set_up();
		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}
		if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
			define( 'MINUTE_IN_SECONDS', 60 );
		}
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-shipment-insights.php' );
		$this->stubCommonWpFunctions();
		Functions\when( 'wp_timezone' )->justReturn( new \DateTimeZone( 'UTC' ) );
		Functions\when( 'sanitize_email' )->returnArg();
		Functions\when( 'is_email' )->alias(
			static function ( $email ) {
				return false !== filter_var( $email, FILTER_VALIDATE_EMAIL );
			}
		);
		$this->setOptions( array( 'admin_email' => 'admin@example.com' ) );
		$this->insights = new \Track_Orders_For_Woocommerce_Shipment_Insights( 'track-orders-for-woocommerce', '1.0.0' );
	}

	/**
	 * Mocked order with the extra methods the insights code calls.
	 *
	 * @param array $props mockOrder() props.
	 * @return \Mockery\MockInterface|\WC_Order
	 */
	protected function order( array $props = array() ) {
		$id    = isset( $props['id'] ) ? (int) $props['id'] : 1;
		$props = array_merge(
			array(
				'get_date_completed'  => null,
				'get_edit_order_url'  => 'http://example.org/wp-admin/post.php?post=' . $id . '&action=edit',
				'save_meta_data'      => null,
			),
			$props
		);
		return $this->mockOrder( $props );
	}

	/**
	 * "d F, Y H:i" string used by the wps_track_order_onchange_time meta.
	 *
	 * @param int $ts Timestamp.
	 * @return string
	 */
	protected function changeTime( $ts ) {
		return gmdate( 'd F, Y H:i', $ts );
	}
}
