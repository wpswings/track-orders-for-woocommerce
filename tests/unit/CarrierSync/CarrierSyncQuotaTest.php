<?php
/**
 * Carrier sync monthly limit and usage counter (WPS-8397).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\CarrierSync;

use Brain\Monkey\Filters;
use Track_Orders_For_Woocommerce_Carrier_Sync as Carrier_Sync;

require_once __DIR__ . '/CarrierSyncTestSupport.php';

/**
 * Tests for the free monthly lookup limit.
 */
class CarrierSyncQuotaTest extends CarrierSyncTestCase {

	public function test_default_free_limit_is_50() {
		$sync = new Carrier_Sync();

		$this->assertSame( 50, Carrier_Sync::DEFAULT_MONTHLY_LIMIT );
		$this->assertSame( 50, $sync->get_monthly_limit() );
		$this->assertFalse( $sync->is_unlimited() );
		$this->assertSame( '50', $sync->get_limit_label() );
	}

	public function test_limit_can_be_changed_with_filter() {
		Filters\expectApplied( 'wps_tofw_carrier_sync_monthly_limit' )->with( 50 )->andReturn( 5 );
		$this->setOptions( array( Carrier_Sync::USAGE_OPTION => $this->usage( 5 ) ) );
		$sync = new Carrier_Sync();

		$this->assertSame( 5, $sync->get_monthly_limit() );
		$this->assertFalse( $sync->has_quota() );
	}

	public function test_negative_limit_means_unlimited() {
		Filters\expectApplied( 'wps_tofw_carrier_sync_monthly_limit' )->andReturn( -1 );
		$this->setOptions( array( Carrier_Sync::USAGE_OPTION => $this->usage( 100000 ) ) );
		$sync = new Carrier_Sync();

		$this->assertTrue( $sync->is_unlimited() );
		$this->assertTrue( $sync->has_quota() );
		$this->assertSame( 'Unlimited', $sync->get_limit_label() );
	}

	public function test_php_int_max_limit_means_unlimited() {
		Filters\expectApplied( 'wps_tofw_carrier_sync_monthly_limit' )->andReturn( PHP_INT_MAX );

		$this->assertTrue( ( new Carrier_Sync() )->is_unlimited() );
	}

	public function test_has_quota_until_limit_is_used() {
		$sync = new Carrier_Sync();

		$this->setOptions( array( Carrier_Sync::USAGE_OPTION => $this->usage( 49 ) ) );
		$this->assertTrue( $sync->has_quota() );

		$this->setOptions( array( Carrier_Sync::USAGE_OPTION => $this->usage( 50 ) ) );
		$this->assertFalse( $sync->has_quota() );
	}

	public function test_usage_from_previous_month_is_ignored() {
		$this->setOptions(
			array(
				Carrier_Sync::USAGE_OPTION => array(
					'period' => '2000-01',
					'count'  => 50,
				),
			)
		);
		$sync = new Carrier_Sync();

		$this->assertSame( 0, $sync->get_usage_count() );
		$this->assertTrue( $sync->has_quota() );
	}

	public function test_malformed_usage_option_counts_as_zero() {
		$this->setOptions( array( Carrier_Sync::USAGE_OPTION => 'garbage' ) );

		$this->assertSame( 0, ( new Carrier_Sync() )->get_usage_count() );
	}

	public function test_lookup_increments_usage_counter_and_releases_lock() {
		$this->setOptions( array( Carrier_Sync::USAGE_OPTION => $this->usage( 3 ) ) );
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake' ) ) );
		$this->mockOrder(
			array(
				'id'             => 7,
				'meta'           => array( 'wps_tofw_package_tracking_number' => 'TRK7' ),
				'add_order_note' => null,
			)
		);

		( new Carrier_Sync() )->sync_order( 7 );

		$this->assertSame( $this->usage( 4 ), $this->getOption( Carrier_Sync::USAGE_OPTION ) );
		$this->assertSame( array(), $this->wpdb->locks, 'usage lock row must be removed' );
		$this->assertSame( array( 'query', 'delete' ), $this->wpdb->log );
	}

	public function test_counter_restarts_in_a_new_month() {
		$this->setOptions(
			array(
				Carrier_Sync::USAGE_OPTION => array(
					'period' => '2000-01',
					'count'  => 50,
				),
			)
		);
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake' ) ) );
		$this->mockOrder(
			array(
				'id'             => 7,
				'meta'           => array( 'wps_tofw_package_tracking_number' => 'TRK7' ),
				'add_order_note' => null,
			)
		);

		( new Carrier_Sync() )->sync_order( 7 );

		$this->assertSame( $this->usage( 1 ), $this->getOption( Carrier_Sync::USAGE_OPTION ) );
	}

	public function test_stale_usage_lock_is_cleared() {
		$this->wpdb->locks[ Carrier_Sync::USAGE_OPTION . '_lock' ] = (string) ( time() - 120 );
		$this->setOptions( array( Carrier_Sync::USAGE_OPTION => $this->usage( 1 ) ) );
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake' ) ) );
		$this->mockOrder(
			array(
				'id'             => 7,
				'meta'           => array( 'wps_tofw_package_tracking_number' => 'TRK7' ),
				'add_order_note' => null,
			)
		);

		( new Carrier_Sync() )->sync_order( 7 );

		$this->assertSame( $this->usage( 2 ), $this->getOption( Carrier_Sync::USAGE_OPTION ) );
		$this->assertSame( array(), $this->wpdb->locks );
	}

	public function test_limit_reached_blocks_lookup_without_calling_provider() {
		$this->setOptions( array( Carrier_Sync::USAGE_OPTION => $this->usage( 50 ) ) );
		$provider = new FakeProvider( 'fake' );
		$this->useProviders( array( 'fake' => $provider ) );
		$this->mockOrder(
			array(
				'id'   => 7,
				'meta' => array( 'wps_tofw_package_tracking_number' => 'TRK7' ),
			)
		);

		$result = ( new Carrier_Sync() )->sync_order( 7 );

		$this->assertSame( 'wps_tofw_limit_reached', error_code( $result ) );
		$this->assertSame( array(), $provider->calls );
		$this->assertSame( gmdate( 'Y-m' ), $this->getOption( 'wps_tofw_carrier_sync_limit_reached' ) );
		$this->assertSame( $this->usage( 50 ), $this->getOption( Carrier_Sync::USAGE_OPTION ) );
	}

	public function test_usage_summary_for_settings_tab() {
		$this->setOptions(
			array(
				Carrier_Sync::USAGE_OPTION        => $this->usage( 12 ),
				'wps_tofw_carrier_sync_last_run'  => 1700000000,
				'date_format'                     => 'Y-m-d',
			)
		);
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake', false ) ) );
		\Brain\Monkey\Functions\when( 'current_time' )->justReturn( time() );
		\Brain\Monkey\Functions\when( 'as_next_scheduled_action' )->justReturn( 1800000000 );

		$summary = ( new Carrier_Sync() )->get_usage_summary();

		$this->assertSame( 12, $summary['used'] );
		$this->assertSame( 50, $summary['limit'] );
		$this->assertSame( '50', $summary['limit_label'] );
		$this->assertFalse( $summary['unlimited'] );
		$this->assertFalse( $summary['limit_reached'] );
		$this->assertSame( 1700000000, $summary['last_run'] );
		$this->assertSame( 1800000000, $summary['next_run'] );
		$this->assertSame( array( array( 'label' => 'FAKE', 'configured' => false ) ), $summary['providers'] );
	}
}
