<?php
/**
 * Carrier sync of one order: stored status/timeline, auto-complete and provider fallback (WPS-8397).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\CarrierSync;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Mockery;
use Track_Orders_For_Woocommerce_Carrier_Sync as Carrier_Sync;

require_once __DIR__ . '/CarrierSyncTestSupport.php';

/**
 * Tests for Track_Orders_For_Woocommerce_Carrier_Sync::sync_order().
 */
class CarrierSyncOrderTest extends CarrierSyncTestCase {

	/**
	 * Order notes added to the mocked order.
	 *
	 * @var string[]
	 */
	private $notes = array();

	/**
	 * Mock an order with a tracking number that records notes.
	 *
	 * @param array $props Extra mockOrder() props.
	 * @return \Mockery\MockInterface
	 */
	private function trackedOrder( array $props = array() ) {
		$notes = &$this->notes;
		$meta  = isset( $props['meta'] ) ? $props['meta'] : array();
		unset( $props['meta'] );
		return $this->mockOrder(
			array_merge(
				array(
					'id'             => 21,
					'meta'           => array_merge(
						array(
							'wps_tofw_package_tracking_number'   => 'TRK21',
							'wps_tofw_selected_shipping_service' => 'ups',
						),
						$meta
					),
					'add_order_note' => static function ( $note ) use ( &$notes ) {
						$notes[] = $note;
						return count( $notes );
					},
					'has_status'     => false,
				),
				$props
			)
		);
	}

	public function test_in_transit_result_is_stored_with_timeline() {
		$provider = new FakeProvider(
			'fake',
			true,
			null,
			array(
				array(
					'status' => 'in_transit',
					'events' => array(
						array(
							'time'        => 100,
							'description' => 'Picked up',
							'location'    => 'Hub A',
						),
						array(
							'time'        => 300,
							'description' => 'Arrived at <script>x</script>facility',
							'location'    => 'Hub B',
							'status'      => 'in_transit',
						),
						array(
							'time'        => 200,
							'description' => '',
						),
						'not-an-array',
					),
				),
			)
		);
		$this->useProviders( array( 'fake' => $provider ) );
		$this->trackedOrder();
		Actions\expectDone( 'wps_tofw_carrier_status_changed' )->once();
		Actions\expectDone( 'wps_tofw_carrier_status_updated' )->once()->with( 21, 'in_transit' );

		$before = time();
		$result = ( new Carrier_Sync() )->sync_order( 21 );

		$this->assertSame( 'in_transit', $result['status'] );
		$this->assertSame( array( array( 'ups', 'TRK21' ) ), $provider->calls );
		$this->assertSame( 'in_transit', $this->getOrderMeta( 21, Carrier_Sync::META_STATUS ) );
		$this->assertSame( 'fake', $this->getOrderMeta( 21, Carrier_Sync::META_PROVIDER ) );
		$this->assertGreaterThanOrEqual( $before, $this->getOrderMeta( 21, Carrier_Sync::META_LAST_CHECKED ) );
		$this->assertGreaterThanOrEqual( $before, $this->getOrderMeta( 21, Carrier_Sync::META_UPDATED ) );

		$events = $this->getOrderMeta( 21, Carrier_Sync::META_EVENTS );
		$this->assertCount( 2, $events, 'empty / invalid events are dropped' );
		$this->assertSame( 300, $events[0]['time'], 'events are sorted newest first' );
		$this->assertSame( 'Arrived at xfacility', $events[0]['description'], 'event text is sanitized' );
		$this->assertSame( 'Picked up', $events[1]['description'] );

		$this->assertSame( array( 'Carrier status updated via FAKE: In transit' ), $this->notes );
	}

	public function statusProvider() {
		return array(
			array( 'in_transit', 'In transit' ),
			array( 'out_for_delivery', 'Out for delivery' ),
			array( 'delivered', 'Delivered' ),
			array( 'exception', 'Exception' ),
		);
	}

	/**
	 * @dataProvider statusProvider
	 *
	 * @param string $status Normalized status.
	 * @param string $label  Label.
	 */
	public function test_normalized_status_labels( $status, $label ) {
		$this->assertSame( $label, Carrier_Sync::get_status_label( $status ) );
	}

	public function test_unknown_status_from_provider_is_stored_as_pending() {
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake', true, null, array( array( 'status' => 'teleported' ) ) ) ) );
		$this->trackedOrder();

		( new Carrier_Sync() )->sync_order( 21 );

		$this->assertSame( 'pending', $this->getOrderMeta( 21, Carrier_Sync::META_STATUS ) );
	}

	public function test_known_status_is_not_downgraded_to_pending() {
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake', true, null, array( array( 'status' => 'pending' ) ) ) ) );
		$this->trackedOrder(
			array(
				'meta' => array(
					Carrier_Sync::META_STATUS => 'out_for_delivery',
					Carrier_Sync::META_EVENTS => array( array( 'time' => 1, 'description' => 'Old event' ) ),
				),
			)
		);
		Actions\expectDone( 'wps_tofw_carrier_status_changed' )->never();

		( new Carrier_Sync() )->sync_order( 21 );

		$this->assertSame( 'out_for_delivery', $this->getOrderMeta( 21, Carrier_Sync::META_STATUS ) );
		$this->assertSame( 'Old event', $this->getOrderMeta( 21, Carrier_Sync::META_EVENTS )[0]['description'], 'empty reply keeps the timeline' );
		$this->assertSame( array(), $this->notes, 'no note when the status did not change' );
	}

	public function test_previous_error_is_cleared_on_success() {
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake' ) ) );
		$this->trackedOrder( array( 'meta' => array( Carrier_Sync::META_ERROR => 'old error' ) ) );

		( new Carrier_Sync() )->sync_order( 21 );

		$this->assertArrayNotHasKey( Carrier_Sync::META_ERROR, $this->getOrderMeta( 21 ) );
	}

	public function test_delivered_completes_order_when_auto_complete_is_on() {
		$this->setOptions( array( 'wps_tofw_carrier_sync_auto_complete' => 'on' ) );
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake', true, null, array( array( 'status' => 'delivered' ) ) ) ) );
		$order = $this->trackedOrder();
		$order->shouldReceive( 'update_status' )->once()->with( 'completed', Mockery::type( 'string' ) );

		( new Carrier_Sync() )->sync_order( 21 );

		$this->assertSame( 'delivered', $this->getOrderMeta( 21, Carrier_Sync::META_STATUS ) );
	}

	public function test_delivered_does_not_complete_order_when_auto_complete_is_off() {
		$this->setOptions( array( 'wps_tofw_carrier_sync_auto_complete' => 'off' ) );
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake', true, null, array( array( 'status' => 'delivered' ) ) ) ) );
		$order = $this->trackedOrder();
		$order->shouldReceive( 'update_status' )->never();

		( new Carrier_Sync() )->sync_order( 21 );

		$this->assertSame( 'delivered', $this->getOrderMeta( 21, Carrier_Sync::META_STATUS ) );
	}

	public function test_auto_complete_skips_cancelled_or_completed_orders() {
		$this->setOptions( array( 'wps_tofw_carrier_sync_auto_complete' => 'on' ) );
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake', true, null, array( array( 'status' => 'delivered' ) ) ) ) );
		$order = $this->trackedOrder( array( 'has_status' => true ) );
		$order->shouldReceive( 'update_status' )->never();

		( new Carrier_Sync() )->sync_order( 21 );
	}

	public function test_non_delivered_status_never_completes_order() {
		$this->setOptions( array( 'wps_tofw_carrier_sync_auto_complete' => 'on' ) );
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake', true, null, array( array( 'status' => 'exception' ) ) ) ) );
		$order = $this->trackedOrder();
		$order->shouldReceive( 'update_status' )->never();

		( new Carrier_Sync() )->sync_order( 21 );

		$this->assertSame( 'exception', $this->getOrderMeta( 21, Carrier_Sync::META_STATUS ) );
	}

	public function test_already_delivered_order_is_not_completed_again() {
		$this->setOptions( array( 'wps_tofw_carrier_sync_auto_complete' => 'on' ) );
		$this->useProviders( array( 'fake' => new FakeProvider( 'fake', true, null, array( array( 'status' => 'delivered' ) ) ) ) );
		$order = $this->trackedOrder( array( 'meta' => array( Carrier_Sync::META_STATUS => 'delivered' ) ) );
		$order->shouldReceive( 'update_status' )->never();

		( new Carrier_Sync() )->sync_order( 21 );
	}

	public function test_falls_back_to_next_provider_on_wp_error() {
		$primary  = new FakeProvider( 'primary', true, array( 'ups' ), array( new \WP_Error( 'down', 'Primary API down' ) ) );
		$fallback = new FakeProvider( 'fallback', true, null, array( array( 'status' => 'out_for_delivery' ) ) );
		$this->useProviders(
			array(
				'primary'  => $primary,
				'fallback' => $fallback,
			)
		);
		$this->trackedOrder();

		$result = ( new Carrier_Sync() )->sync_order( 21 );

		$this->assertSame( 'out_for_delivery', $result['status'] );
		$this->assertCount( 1, $primary->calls );
		$this->assertCount( 1, $fallback->calls );
		$this->assertSame( 'fallback', $this->getOrderMeta( 21, Carrier_Sync::META_PROVIDER ) );
		$this->assertSame( 'out_for_delivery', $this->getOrderMeta( 21, Carrier_Sync::META_STATUS ) );
		$this->assertSame( $this->usage( 2 ), $this->getOption( Carrier_Sync::USAGE_OPTION ), 'each lookup counts' );
	}

	public function test_fallback_is_not_tried_without_quota() {
		$this->setOptions( array( Carrier_Sync::USAGE_OPTION => $this->usage( 49 ) ) );
		$primary  = new FakeProvider( 'primary', true, null, array( new \WP_Error( 'down', 'Primary API down' ) ) );
		$fallback = new FakeProvider( 'fallback' );
		$this->useProviders(
			array(
				'primary'  => $primary,
				'fallback' => $fallback,
			)
		);
		$this->trackedOrder();

		$result = ( new Carrier_Sync() )->sync_order( 21 );

		$this->assertSame( 'down', error_code( $result ) );
		$this->assertSame( array(), $fallback->calls );
	}

	public function test_all_providers_failing_stores_error_on_order() {
		$primary  = new FakeProvider( 'primary', true, null, array( new \WP_Error( 'down', 'Primary <b>down</b>' ) ) );
		$fallback = new FakeProvider( 'fallback', true, null, array( new \WP_Error( 'down2', 'Fallback down' ) ) );
		$this->useProviders(
			array(
				'primary'  => $primary,
				'fallback' => $fallback,
			)
		);
		$this->trackedOrder();
		Actions\expectDone( 'wps_tofw_carrier_status_changed' )->never();

		$result = ( new Carrier_Sync() )->sync_order( 21 );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'Primary down', $this->getOrderMeta( 21, Carrier_Sync::META_ERROR ) );
		$this->assertSame( 'primary', $this->getOrderMeta( 21, Carrier_Sync::META_PROVIDER ) );
		$this->assertNotNull( $this->getOrderMeta( 21, Carrier_Sync::META_LAST_CHECKED ), 'failed order is not retried immediately' );
		$this->assertNull( $this->getOrderMeta( 21, Carrier_Sync::META_STATUS ) );
	}

	public function test_unconfigured_or_unsupporting_providers_are_skipped() {
		$fedex   = new FakeProvider( 'fedex', true, array( 'fedex' ) );
		$offline = new FakeProvider( 'offline', false );
		$generic = new FakeProvider( 'generic' );
		$this->useProviders(
			array(
				'fedex'   => $fedex,
				'offline' => $offline,
				'generic' => $generic,
				'junk'    => new \stdClass(),
			)
		);
		$this->trackedOrder();

		( new Carrier_Sync() )->sync_order( 21 );

		$this->assertSame( array(), $fedex->calls );
		$this->assertSame( array(), $offline->calls );
		$this->assertCount( 1, $generic->calls );
	}

	public function test_carrier_specific_provider_is_preferred() {
		$fedex   = new FakeProvider( 'fedex', true, array( 'fedex' ) );
		$generic = new FakeProvider( 'generic' );
		$this->useProviders(
			array(
				'fedex'   => $fedex,
				'generic' => $generic,
			)
		);
		$this->trackedOrder( array( 'meta' => array( 'wps_tofw_selected_shipping_service' => 'fedex' ) ) );

		( new Carrier_Sync() )->sync_order( 21 );

		$this->assertCount( 1, $fedex->calls );
		$this->assertSame( array(), $generic->calls );
	}

	public function test_no_configured_provider_stores_error() {
		$this->useProviders( array( 'offline' => new FakeProvider( 'offline', false ) ) );
		$this->trackedOrder();

		$result = ( new Carrier_Sync() )->sync_order( 21 );

		$this->assertSame( 'wps_tofw_no_provider', error_code( $result ) );
		$this->assertNotEmpty( $this->getOrderMeta( 21, Carrier_Sync::META_ERROR ) );
		$this->assertNull( $this->getOption( Carrier_Sync::USAGE_OPTION ), 'no lookup, no usage' );
	}

	public function test_order_without_tracking_number_is_skipped() {
		$provider = new FakeProvider( 'fake' );
		$this->useProviders( array( 'fake' => $provider ) );
		$this->mockOrder( array( 'id' => 22 ) );

		$result = ( new Carrier_Sync() )->sync_order( 22 );

		$this->assertSame( 'wps_tofw_no_tracking', error_code( $result ) );
		$this->assertSame( array(), $provider->calls );
	}

	public function test_missing_order_returns_error() {
		$this->stubWcGetOrder();

		$this->assertSame( 'wps_tofw_no_order', error_code( ( new Carrier_Sync() )->sync_order( 999 ) ) );
	}

	public function test_pro_enhanced_tracking_number_is_used_with_auto_detected_carrier() {
		$provider = new FakeProvider( 'fake' );
		$this->useProviders( array( 'fake' => $provider ) );
		$this->trackedOrder(
			array(
				'meta' => array(
					'wps_tofw_package_tracking_number' => '',
					'wps_tofwp_enhanced_tracking_no'   => ' ENH-1 ',
				),
			)
		);

		( new Carrier_Sync() )->sync_order( 21 );

		$this->assertSame( array( array( '', 'ENH-1' ) ), $provider->calls );
	}

	public function test_handle_sync_order_requires_capability() {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'wp_die' )->once()->andThrow( new \RuntimeException( 'died' ) );
		Functions\expect( 'check_admin_referer' )->never();

		$this->expectException( \RuntimeException::class );
		( new Carrier_Sync() )->handle_sync_order();
	}

	public function test_handle_run_now_requires_capability() {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'wp_die' )->once()->andThrow( new \RuntimeException( 'died' ) );
		Functions\expect( 'as_enqueue_async_action' )->never();

		$this->expectException( \RuntimeException::class );
		( new Carrier_Sync() )->handle_run_now();
	}
}
