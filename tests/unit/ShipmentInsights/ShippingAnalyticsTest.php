<?php
/**
 * WPS-8404: shipping analytics (date range, per-carrier delivery time, statuses, late shipments, counters).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\ShipmentInsights;

use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests for the analytics report and the beacon endpoint.
 */
class ShippingAnalyticsTest extends InsightsTestCase {

	/**
	 * Orders returned by wc_get_orders.
	 *
	 * @var array
	 */
	protected $orders = array();

	/**
	 * Transients.
	 *
	 * @var array
	 */
	protected $transients = array();

	/**
	 * Stub queries and transients.
	 */
	protected function set_up() {
		parent::set_up();
		$this->orders     = array();
		$this->transients = array();
		$orders           = &$this->orders;
		$transients       = &$this->transients;
		Functions\when( 'wc_get_orders' )->alias(
			static function ( $args ) use ( &$orders ) {
				return 1 === (int) $args['paged'] ? $orders : array();
			}
		);
		Functions\when( 'get_transient' )->alias(
			static function ( $key ) use ( &$transients ) {
				return isset( $transients[ $key ] ) ? $transients[ $key ] : false;
			}
		);
		Functions\when( 'set_transient' )->alias(
			static function ( $key, $value ) use ( &$transients ) {
				$transients[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'wp_list_pluck' )->alias(
			static function ( $list, $field ) {
				$out = array();
				foreach ( $list as $k => $row ) {
					$out[ $k ] = is_array( $row ) ? $row[ $field ] : $row->$field;
				}
				return $out;
			}
		);
		$_GET = array();
	}

	/**
	 * Clean globals.
	 */
	protected function tear_down() {
		$_GET = array();
		unset( $GLOBALS['wpdb'] );
		parent::tear_down();
	}

	/**
	 * Range for the report.
	 *
	 * @return array
	 */
	protected function range() {
		return array(
			'from'    => '2024-01-01',
			'to'      => '2024-01-31',
			'from_ts' => 1704067200,
			'to_ts'   => 1706745599,
		);
	}

	/**
	 * Default range: last 30 days, today included.
	 */
	public function test_default_range_is_last_30_days() {
		$range = $this->insights->wps_tofw_get_requested_range();

		$this->assertSame( gmdate( 'Y-m-d' ), $range['to'] );
		$this->assertSame( gmdate( 'Y-m-d', strtotime( '-29 days' ) ), $range['from'] );
		$this->assertSame( strtotime( $range['to'] . ' 23:59:59 UTC' ), $range['to_ts'] );
	}

	/**
	 * Requested range is honoured, swapped when reversed, and invalid values fall back.
	 */
	public function test_requested_range_parsing() {
		$_GET = array(
			'wps_tofw_from' => '2024-02-10',
			'wps_tofw_to'   => '2024-02-01',
		);
		$range = $this->insights->wps_tofw_get_requested_range();
		$this->assertSame( '2024-02-01', $range['from'] );
		$this->assertSame( '2024-02-10', $range['to'] );
		$this->assertSame( strtotime( '2024-02-01 00:00:00 UTC' ), $range['from_ts'] );

		$_GET = array(
			'wps_tofw_from' => "2024-01-01' OR 1=1",
			'wps_tofw_to'   => '<script>',
		);
		$range = $this->insights->wps_tofw_get_requested_range();
		$this->assertSame( gmdate( 'Y-m-d' ), $range['to'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $range['from'] );
	}

	/**
	 * Report: avg delivery days per carrier, statuses, late shipments (incl. unshipped past EDD).
	 */
	public function test_analytics_report() {
		$this->setOptions( array( 'wps_tofw_delivery_alerts_expected_days' => 5 ) );
		$now          = time();
		$this->orders = array(
			// UPS delivered in 2 and 4 days => 3.0 avg.
			$this->order( array( 'id' => 1, 'status' => 'completed', 'meta' => array( 'wps_tofw_selected_shipping_service' => 'ups', 'wps_tofw_shipped_at' => $now - 10 * DAY_IN_SECONDS, 'wps_tofw_delivered_at' => $now - 8 * DAY_IN_SECONDS ) ) ),
			$this->order( array( 'id' => 2, 'status' => 'completed', 'meta' => array( 'wps_tofw_selected_shipping_service' => 'ups', 'wps_tofw_shipped_at' => $now - 10 * DAY_IN_SECONDS, 'wps_tofw_delivered_at' => $now - 6 * DAY_IN_SECONDS ) ) ),
			// FedEx: in transit and late, and an exception.
			$this->order( array( 'id' => 3, 'meta' => array( 'wps_tofw_selected_shipping_service' => 'fedex', 'wps_tofw_shipped_at' => $now - 9 * DAY_IN_SECONDS, 'wps_tofw_package_tracking_number' => 'FX1' ) ) ),
			$this->order( array( 'id' => 4, 'meta' => array( 'wps_tofw_selected_shipping_service' => 'fedex', 'wps_tofw_carrier_status' => 'expired' ) ) ),
			// Not shipped but EDD passed => late without carrier row.
			$this->order( array( 'id' => 5, 'meta' => array( 'wps_tofw_estimated_delivery_date' => gmdate( 'Y-m-d', $now - 3 * DAY_IN_SECONDS ) ) ) ),
			// Not shipped, nothing late.
			$this->order( array( 'id' => 6 ) ),
			// Cancelled.
			$this->order( array( 'id' => 7, 'status' => 'cancelled' ) ),
		);

		$report = $this->insights->wps_tofw_get_analytics( $this->range(), true );

		$this->assertSame( 7, $report['scanned'] );
		$this->assertFalse( $report['truncated'] );
		$this->assertSame(
			array(
				'not_shipped'      => 2,
				'in_transit'       => 1,
				'out_for_delivery' => 0,
				'delivered'        => 2,
				'exception'        => 1,
				'cancelled'        => 1,
			),
			$report['status_counts']
		);
		$this->assertSame( 3.0, $report['carriers']['Ups']['avg_days'] );
		$this->assertSame( 2, $report['carriers']['Ups']['delivered'] );
		$this->assertSame( 0, $report['carriers']['Ups']['late'] );
		$this->assertSame( 2, $report['carriers']['Fedex']['shipments'] );
		$this->assertSame( 2, $report['carriers']['Fedex']['late'] );
		$this->assertNull( $report['carriers']['Fedex']['avg_days'] );
		$this->assertSame( 3.0, $report['avg_days'] );
		$this->assertSame( 3, $report['late_count'] );
		$this->assertEqualsCanonicalizing( array( 3, 4, 5 ), array_column( $report['late'], 'order_id' ) );
		$this->assertArrayNotHasKey( 'Unspecified', $report['carriers'], 'unshipped/cancelled orders are not carrier shipments' );
	}

	/**
	 * Query is limited to orders created in the range.
	 */
	public function test_analytics_queries_orders_created_in_range() {
		$queries = array();
		Functions\when( 'wc_get_orders' )->alias(
			static function ( $args ) use ( &$queries ) {
				$queries[] = $args;
				return array();
			}
		);

		$report = $this->insights->wps_tofw_get_analytics( $this->range(), true );
		$this->assertSame( 0, $report['scanned'] );
		$this->assertNull( $report['avg_days'] );
		$this->assertCount( 1, $queries );
		$this->assertSame( '1704067200...1706745599', $queries[0]['date_created'] );
		$this->assertSame( 'shop_order', $queries[0]['type'] );
	}

	/**
	 * Report is cached for the range; force bypasses the cache; another range is computed separately.
	 */
	public function test_analytics_cache() {
		$this->orders = array( $this->order( array( 'id' => 1 ) ) );
		$first        = $this->insights->wps_tofw_get_analytics( $this->range() );
		$this->assertSame( 1, $first['scanned'] );

		$this->orders[] = $this->order( array( 'id' => 2 ) );
		$this->assertSame( 1, $this->insights->wps_tofw_get_analytics( $this->range() )['scanned'], 'cached' );
		$this->assertSame( 2, $this->insights->wps_tofw_get_analytics( $this->range(), true )['scanned'], 'forced' );

		$other         = $this->range();
		$other['from'] = '2023-12-01';
		$this->assertSame( 2, $this->insights->wps_tofw_get_analytics( $other )['scanned'], 'different range, different cache key' );
	}

	/**
	 * Tracking visit / upsell click counters are summed per range.
	 */
	public function test_counters() {
		$this->setOptions( array( 'wps_tofw_insights_db_version' => '1.0.0' ) );
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->with( Mockery::pattern( '/FROM wp_wps_tofw_daily_stats WHERE stat_date BETWEEN %s AND %s/' ), '2024-01-01', '2024-01-31' )->andReturn( 'SQL' );
		$wpdb->shouldReceive( 'get_results' )->once()->with( 'SQL', ARRAY_A )->andReturn(
			array(
				array( 'stat_date' => '2024-01-02', 'metric' => 'tracking_visit', 'stat_count' => '5' ),
				array( 'stat_date' => '2024-01-02', 'metric' => 'upsell_click', 'stat_count' => '2' ),
				array( 'stat_date' => '2024-01-01', 'metric' => 'tracking_visit', 'stat_count' => '3' ),
			)
		);
		$GLOBALS['wpdb'] = $wpdb;

		$counters = $this->insights->wps_tofw_get_counters( '2024-01-01', '2024-01-31' );

		$this->assertSame( array( 'tracking_visit' => 8, 'upsell_click' => 2 ), $counters['totals'] );
		$this->assertSame( array( 'tracking_visit' => 5, 'upsell_click' => 2 ), $counters['daily']['2024-01-02'] );
	}

	/**
	 * Counters are empty (no query) before the stats table exists.
	 */
	public function test_counters_without_table() {
		$wpdb = Mockery::mock( 'wpdb' );
		$wpdb->shouldNotReceive( 'get_results' );
		$GLOBALS['wpdb'] = $wpdb;

		$counters = $this->insights->wps_tofw_get_counters( '2024-01-01', '2024-01-31' );
		$this->assertSame( array( 'tracking_visit' => 0, 'upsell_click' => 0 ), $counters['totals'] );
	}

	/**
	 * Beacon endpoint rejects requests without a valid nonce.
	 */
	public function test_beacon_requires_nonce() {
		Functions\when( 'check_ajax_referer' )->justReturn( false );
		Functions\expect( 'wp_send_json_error' )->once()->with( null, 403 )->andReturnUsing(
			static function () {
				throw new \RuntimeException( 'json_error' );
			}
		);
		$this->expectExceptionMessage( 'json_error' );
		$this->insights->wps_tofw_ajax_record_upsell_click();
	}

	/**
	 * Beacon counts an upsell click (sanitized metric) for real browsers, not bots.
	 */
	public function test_beacon_counts_upsell_click_but_not_bots() {
		$this->setOptions( array( 'wps_tofw_insights_db_version' => '1.0.0' ) );
		Functions\when( 'check_ajax_referer' )->justReturn( true );
		Functions\when( 'current_time' )->justReturn( '2024-01-02' );
		Functions\when( 'wp_send_json_success' )->alias(
			static function () {
				throw new \RuntimeException( 'json_success' );
			}
		);
		$wpdb         = Mockery::mock( 'wpdb' );
		$wpdb->prefix = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->with( Mockery::type( 'string' ), '2024-01-02', 'upsell_click' )->andReturn( 'SQL' );
		$wpdb->shouldReceive( 'query' )->once()->with( 'SQL' );
		$GLOBALS['wpdb'] = $wpdb;

		$_POST['metric']            = 'evil_metric';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Chrome';
		try {
			$this->insights->wps_tofw_ajax_record_upsell_click();
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'json_success', $e->getMessage() );
		}

		$_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1';
		try {
			$this->insights->wps_tofw_ajax_record_upsell_click();
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'json_success', $e->getMessage() );
		}
		unset( $_POST['metric'], $_SERVER['HTTP_USER_AGENT'] );
	}

	/**
	 * Alerts and analytics tabs are registered on the settings page.
	 */
	public function test_settings_tabs_registered() {
		$tabs = $this->insights->wps_tofw_add_settings_tabs( array( 'existing' => array() ) );

		$this->assertArrayHasKey( 'existing', $tabs );
		$this->assertStringEndsWith( 'admin/partials/track-orders-for-woocommerce-delivery-alerts.php', $tabs['track-orders-for-woocommerce-delivery-alerts']['file_path'] );
		$this->assertStringEndsWith( 'admin/partials/track-orders-for-woocommerce-shipping-analytics.php', $tabs['track-orders-for-woocommerce-shipping-analytics']['file_path'] );
		$this->assertFileExists( $tabs['track-orders-for-woocommerce-shipping-analytics']['file_path'] );
	}

	/**
	 * Alert settings expose enable, recipients, expected days, conditions, exception statuses and dashboard notice.
	 */
	public function test_alert_settings_fields() {
		Functions\when( 'wc_get_order_statuses' )->justReturn( array( 'wc-processing' => 'Processing' ) );
		$ids = array_column( $this->insights->wps_tofw_alert_settings_fields(), 'id' );

		$this->assertSame(
			array(
				'wps_tofw_delivery_alerts_enable',
				'wps_tofw_delivery_alerts_recipients',
				'wps_tofw_delivery_alerts_expected_days',
				'wps_tofw_delivery_alerts_conditions',
				'wps_tofw_delivery_alerts_exception_statuses',
				'wps_tofw_delivery_alerts_dashboard_notice',
				'wps_tofw_delivery_alerts_settings_save',
			),
			$ids
		);
	}
}
