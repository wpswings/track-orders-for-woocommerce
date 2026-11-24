<?php
/**
 * Carrier sync Action Scheduler batches, lock, scheduling and tracking page timeline (WPS-8397).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\CarrierSync;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Track_Orders_For_Woocommerce_Carrier_Sync as Carrier_Sync;

require_once __DIR__ . '/CarrierSyncTestSupport.php';

/**
 * Tests for run_batch(), maybe_schedule() and render_timeline().
 */
class CarrierSyncBatchTest extends CarrierSyncTestCase {

	/**
	 * Args passed to wc_get_orders().
	 *
	 * @var array|null
	 */
	private $query;

	/**
	 * Stub wc_get_orders() to return the given IDs and mock those orders with tracking numbers.
	 *
	 * @param int[] $ids Order IDs.
	 */
	private function ordersToSync( array $ids ) {
		$query = &$this->query;
		Functions\when( 'wc_get_order_statuses' )->justReturn(
			array(
				'wc-pending'    => 'Pending',
				'wc-processing' => 'Processing',
				'wc-completed'  => 'Completed',
				'wc-cancelled'  => 'Cancelled',
			)
		);
		Functions\when( 'wc_get_orders' )->alias(
			static function ( $args ) use ( &$query, $ids ) {
				$query = $args;
				return array_map( 'strval', $ids );
			}
		);
		foreach ( $ids as $id ) {
			$this->mockOrder(
				array(
					'id'             => $id,
					'meta'           => array( 'wps_tofw_package_tracking_number' => 'TRK' . $id ),
					'add_order_note' => null,
					'has_status'     => false,
				)
			);
		}
	}

	public function test_batch_syncs_due_orders_and_records_last_run() {
		$provider = new FakeProvider( 'fake' );
		$this->useProviders( array( 'fake' => $provider ) );
		$this->ordersToSync( array( 1, 2 ) );
		Functions\expect( 'as_enqueue_async_action' )->never();

		$processed = ( new Carrier_Sync() )->run_batch( 0 );

		$this->assertSame( 2, $processed );
		$this->assertSame( array( array( '', 'TRK1' ), array( '', 'TRK2' ) ), $provider->calls );
		$this->assertSame( 'in_transit', $this->getOrderMeta( 2, Carrier_Sync::META_STATUS ) );
		$this->assertGreaterThan( 0, $this->getOption( 'wps_tofw_carrier_sync_last_run' ) );
		$this->assertArrayNotHasKey( 'wps_tofw_carrier_sync_lock', $this->transients, 'lock released' );
	}

	public function test_order_query_excludes_final_statuses_and_closed_orders() {
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake' ) ) );
		$this->ordersToSync( array() );

		( new Carrier_Sync() )->run_batch( 0 );

		$this->assertSame( 20, $this->query['limit'] );
		$this->assertSame( 'ids', $this->query['return'] );
		$this->assertSame( array( 'wc-processing', 'wc-completed' ), $this->query['status'] );
		$this->assertSame( array( 'delivered' ), $this->query['meta_query'][1][1]['value'], 'delivered orders are not synced again' );
		$this->assertSame( 'NOT IN', $this->query['meta_query'][1][1]['compare'] );
	}

	public function test_failed_order_does_not_stall_the_batch() {
		$provider = new FakeProvider(
			'fake',
			true,
			null,
			array(
				new \WP_Error( 'boom', 'Carrier API error' ),
				array( 'status' => 'delivered' ),
			)
		);
		$this->useProviders( array( 'fake' => $provider ) );
		$this->ordersToSync( array( 1, 2 ) );

		$processed = ( new Carrier_Sync() )->run_batch( 0 );

		$this->assertSame( 2, $processed );
		$this->assertSame( 'Carrier API error', $this->getOrderMeta( 1, Carrier_Sync::META_ERROR ) );
		$this->assertSame( 'delivered', $this->getOrderMeta( 2, Carrier_Sync::META_STATUS ) );
	}

	public function test_running_batch_holds_lock_so_parallel_run_is_skipped() {
		$this->transients['wps_tofw_carrier_sync_lock'] = 1;
		Functions\expect( 'wc_get_orders' )->never();

		$this->assertSame( 0, ( new Carrier_Sync() )->run_batch( 0 ) );
		$this->assertSame( 1, $this->transients['wps_tofw_carrier_sync_lock'], 'foreign lock is kept' );
	}

	public function test_lock_is_released_when_sync_throws() {
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake' ) ) );
		Functions\when( 'wc_get_order_statuses' )->justReturn( array( 'wc-processing' => 'Processing' ) );
		Functions\when( 'wc_get_orders' )->alias(
			static function () {
				throw new \RuntimeException( 'db gone' );
			}
		);

		try {
			( new Carrier_Sync() )->run_batch( 0 );
			$this->fail( 'exception expected' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'db gone', $e->getMessage() );
		}
		$this->assertArrayNotHasKey( 'wps_tofw_carrier_sync_lock', $this->transients );
	}

	public function test_disabled_sync_does_nothing() {
		$this->setOptions( array( 'wps_tofw_carrier_sync_enable' => 'off' ) );
		Functions\expect( 'wc_get_orders' )->never();

		$this->assertSame( 0, ( new Carrier_Sync() )->run_batch( 0 ) );
	}

	public function test_disabled_plugin_does_nothing() {
		$this->setOptions( array( 'wps_tofw_is_plugin_enable' => 'off' ) );

		$this->assertFalse( ( new Carrier_Sync() )->is_enabled() );
		$this->assertSame( 0, ( new Carrier_Sync() )->run_batch( 0 ) );
	}

	public function test_full_batch_chains_next_batch() {
		Filters\expectApplied( 'wps_tofw_carrier_sync_batch_size' )->andReturn( 2 );
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake' ) ) );
		$this->ordersToSync( array( 1, 2 ) );
		Functions\expect( 'as_enqueue_async_action' )
			->once()
			->with( Carrier_Sync::BATCH_HOOK, array( 'chain' => 4 ), Carrier_Sync::GROUP );

		( new Carrier_Sync() )->run_batch( 3 );
	}

	public function test_chain_stops_at_max_batches() {
		Filters\expectApplied( 'wps_tofw_carrier_sync_batch_size' )->andReturn( 2 );
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake' ) ) );
		$this->ordersToSync( array( 1, 2 ) );
		Functions\expect( 'as_enqueue_async_action' )->never();

		( new Carrier_Sync() )->run_batch( 9 );
	}

	public function test_quota_exhausted_mid_batch_stops_and_flags_limit() {
		$this->setOptions( array( Carrier_Sync::USAGE_OPTION => $this->usage( 49 ) ) );
		$provider = new FakeProvider( 'fake' );
		$this->useProviders( array( 'fake' => $provider ) );
		$this->ordersToSync( array( 1, 2, 3 ) );
		Functions\expect( 'as_enqueue_async_action' )->never();

		$processed = ( new Carrier_Sync() )->run_batch( 0 );

		$this->assertSame( 1, $processed );
		$this->assertCount( 1, $provider->calls );
		$this->assertSame( gmdate( 'Y-m' ), $this->getOption( 'wps_tofw_carrier_sync_limit_reached' ) );
		$this->assertSame( $this->usage( 50 ), $this->getOption( Carrier_Sync::USAGE_OPTION ) );
	}

	public function test_run_scheduled_runs_first_batch() {
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake' ) ) );
		$this->ordersToSync( array( 5 ) );

		( new Carrier_Sync() )->run_scheduled();

		$this->assertSame( 'in_transit', $this->getOrderMeta( 5, Carrier_Sync::META_STATUS ) );
	}

	public function test_enabling_schedules_recurring_action_with_interval() {
		$this->setOptions( array( 'wps_tofw_carrier_sync_interval' => 12 ) );
		Functions\when( 'as_next_scheduled_action' )->justReturn( false );
		Functions\expect( 'as_unschedule_all_actions' )->once()->with( Carrier_Sync::RECURRING_HOOK, array(), Carrier_Sync::GROUP );
		Functions\expect( 'as_schedule_recurring_action' )
			->once()
			->with( \Mockery::type( 'int' ), 12 * HOUR_IN_SECONDS, Carrier_Sync::RECURRING_HOOK, array(), Carrier_Sync::GROUP );

		( new Carrier_Sync() )->maybe_schedule();

		$this->assertSame( 'on|43200', $this->getOption( 'wps_tofw_carrier_sync_schedule_signature' ) );
	}

	public function test_invalid_interval_falls_back_to_six_hours() {
		$this->setOptions( array( 'wps_tofw_carrier_sync_interval' => 5 ) );

		$this->assertSame( 6 * HOUR_IN_SECONDS, ( new Carrier_Sync() )->get_interval() );
	}

	public function test_disabling_unschedules_without_rescheduling() {
		$this->setOptions(
			array(
				'wps_tofw_carrier_sync_enable'             => 'off',
				'wps_tofw_carrier_sync_schedule_signature' => 'on|21600',
			)
		);
		Functions\when( 'as_next_scheduled_action' )->justReturn( 123 );
		Functions\expect( 'as_unschedule_all_actions' )->once();
		Functions\expect( 'as_schedule_recurring_action' )->never();

		( new Carrier_Sync() )->maybe_schedule();

		$this->assertSame( 'off', $this->getOption( 'wps_tofw_carrier_sync_schedule_signature' ) );
	}

	public function test_unchanged_settings_with_existing_action_do_not_reschedule() {
		$this->setOptions( array( 'wps_tofw_carrier_sync_schedule_signature' => 'on|21600' ) );
		Functions\when( 'as_next_scheduled_action' )->justReturn( time() + 100 );
		Functions\expect( 'as_unschedule_all_actions' )->never();
		Functions\expect( 'as_schedule_recurring_action' )->never();

		( new Carrier_Sync() )->maybe_schedule();
	}

	public function test_missing_recurring_action_is_recreated() {
		$this->setOptions( array( 'wps_tofw_carrier_sync_schedule_signature' => 'on|21600' ) );
		Functions\when( 'as_next_scheduled_action' )->justReturn( false );
		Functions\expect( 'as_unschedule_all_actions' )->once();
		Functions\expect( 'as_schedule_recurring_action' )->once();

		( new Carrier_Sync() )->maybe_schedule();
	}

	/**
	 * Render the timeline for a mocked order and return the HTML.
	 *
	 * @param array $meta Order meta.
	 * @return string
	 */
	private function renderTimeline( array $meta ) {
		$this->setOptions(
			array(
				'date_format' => 'Y-m-d',
				'time_format' => 'H:i',
			)
		);
		Functions\when( 'wp_enqueue_style' )->justReturn( null );
		Functions\when( 'esc_html_e' )->alias(
			static function ( $text ) {
				echo htmlspecialchars( $text, ENT_QUOTES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		);
		$this->mockOrder(
			array(
				'id'   => 31,
				'meta' => $meta,
			)
		);
		ob_start();
		( new Carrier_Sync() )->render_timeline( '31' );
		return (string) ob_get_clean();
	}

	public function test_timeline_renders_status_and_events_on_tracking_page() {
		$html = $this->renderTimeline(
			array(
				Carrier_Sync::META_STATUS       => 'out_for_delivery',
				Carrier_Sync::META_LAST_CHECKED => 1767225600,
				Carrier_Sync::META_EVENTS       => array(
					array(
						'time'        => 1767225600,
						'description' => 'With courier <img src=x onerror=alert(1)>',
						'location'    => 'Berlin',
					),
					array(
						'time'        => 1767139200,
						'description' => 'Arrived at hub',
						'location'    => '',
					),
				),
			)
		);

		$this->assertStringContainsString( 'wps-tofw-carrier-timeline--out_for_delivery', $html );
		$this->assertStringContainsString( '<span class="wps-tofw-carrier-timeline__badge">Out for delivery</span>', $html );
		$this->assertSame( 2, substr_count( $html, '<li class="wps-tofw-carrier-timeline__event' ) );
		$this->assertStringContainsString( 'wps-tofw-carrier-timeline__event is-latest', $html );
		$this->assertStringContainsString( 'Berlin', $html );
		$this->assertStringContainsString( 'Arrived at hub', $html );
		$this->assertStringNotContainsString( '<img', $html, 'event text is escaped' );
		$this->assertStringContainsString( 'Last updated: 2026-01-01 00:00', $html );
	}

	public function test_timeline_shows_empty_state_without_events() {
		$html = $this->renderTimeline( array( Carrier_Sync::META_STATUS => 'pending' ) );

		$this->assertStringContainsString( 'Awaiting carrier update', $html );
		$this->assertStringContainsString( 'wps-tofw-carrier-timeline__empty', $html );
	}

	public function test_timeline_hidden_for_order_never_synced() {
		$this->assertSame( '', $this->renderTimeline( array() ) );
	}

	public function test_timeline_hidden_by_filter() {
		Filters\expectApplied( 'wps_tofw_show_carrier_timeline' )->andReturn( false );

		$this->assertSame( '', $this->renderTimeline( array( Carrier_Sync::META_STATUS => 'delivered' ) ) );
	}

	public function test_timeline_for_unknown_order_renders_nothing() {
		$this->stubWcGetOrder();
		ob_start();
		( new Carrier_Sync() )->render_timeline( 404 );
		$this->assertSame( '', ob_get_clean() );
	}
}
