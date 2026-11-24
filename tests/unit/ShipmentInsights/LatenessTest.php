<?php
/**
 * WPS-8404: late shipment detection (expected days, estimated delivery date incl. EDD fallback).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\ShipmentInsights;

/**
 * Tests for wps_tofw_evaluate_order() and the timestamps it relies on.
 */
class LatenessTest extends InsightsTestCase {

	/**
	 * Shipped longer ago than the expected days => expected_days issue with a stable fingerprint.
	 */
	public function test_late_after_expected_days() {
		$this->setOptions( array( 'wps_tofw_delivery_alerts_expected_days' => 5 ) );
		$shipped = time() - ( 6 * DAY_IN_SECONDS );
		$order   = $this->order( array( 'meta' => array( 'wps_tofw_shipped_at' => $shipped ) ) );

		$issues = $this->insights->wps_tofw_evaluate_order( $order );

		$this->assertSame( array( 'expected_days' ), array_keys( $issues ) );
		$deadline = $shipped + 5 * DAY_IN_SECONDS;
		$this->assertSame( $deadline, $issues['expected_days']['deadline'] );
		$this->assertSame( 'expected_days:' . $deadline, $issues['expected_days']['fingerprint'] );
		$this->assertStringContainsString( '5', $issues['expected_days']['label'] );
	}

	/**
	 * Within the expected days => no issue.
	 */
	public function test_not_late_within_expected_days() {
		$this->setOptions( array( 'wps_tofw_delivery_alerts_expected_days' => 5 ) );
		$order = $this->order( array( 'meta' => array( 'wps_tofw_shipped_at' => time() - ( 4 * DAY_IN_SECONDS ) ) ) );

		$this->assertSame( array(), $this->insights->wps_tofw_evaluate_order( $order ) );
	}

	/**
	 * Invalid expected days falls back to 7.
	 */
	public function test_expected_days_default() {
		$this->setOptions( array( 'wps_tofw_delivery_alerts_expected_days' => 0 ) );
		$this->assertSame( 7, $this->insights->wps_tofw_get_expected_days() );
		$this->setOptions( array( 'wps_tofw_delivery_alerts_expected_days' => 'abc' ) );
		$this->assertSame( 7, $this->insights->wps_tofw_get_expected_days() );
		$this->setOptions( array( 'wps_tofw_delivery_alerts_expected_days' => '3' ) );
		$this->assertSame( 3, $this->insights->wps_tofw_get_expected_days() );
	}

	/**
	 * Without the shipped meta, the earliest shipping status change time is used.
	 */
	public function test_shipped_timestamp_falls_back_to_status_change_time() {
		$this->setOptions( array( 'wps_tofw_order_status_in_shipping' => array( 'wc-dispatched', 'wc-packed' ) ) );
		$early = strtotime( '2024-01-02 10:00:00 UTC' );
		$late  = strtotime( '2024-01-05 10:00:00 UTC' );
		$order = $this->order(
			array(
				'meta' => array(
					'wps_track_order_onchange_time' => array(
						'wc-processing' => $this->changeTime( strtotime( '2024-01-01 09:00:00 UTC' ) ),
						'wc-dispatched' => $this->changeTime( $late ),
						'wc-packed'     => $this->changeTime( $early ),
					),
				),
			)
		);

		$this->assertSame( $early, $this->insights->wps_tofw_get_shipped_timestamp( $order ) );
		$this->assertArrayHasKey( 'expected_days', $this->insights->wps_tofw_evaluate_order( $order ) );
	}

	/**
	 * Never shipped => expected_days cannot fire.
	 */
	public function test_unshipped_order_is_not_late_by_days() {
		$order = $this->order( array( 'status' => 'processing' ) );
		$this->assertSame( 0, $this->insights->wps_tofw_get_shipped_timestamp( $order ) );
		$this->assertSame( array(), $this->insights->wps_tofw_evaluate_order( $order, array( 'expected_days' ) ) );
	}

	/**
	 * A passed manual estimated delivery date => estimated_date issue; a future one => none.
	 */
	public function test_manual_estimated_delivery_date() {
		$past   = $this->order( array( 'id' => 1, 'meta' => array( 'wps_tofw_estimated_delivery_date' => gmdate( 'Y-m-d', time() - 3 * DAY_IN_SECONDS ) ) ) );
		$future = $this->order( array( 'id' => 2, 'meta' => array( 'wps_tofw_estimated_delivery_date' => gmdate( 'Y-m-d', time() + 3 * DAY_IN_SECONDS ) ) ) );

		$issues = $this->insights->wps_tofw_evaluate_order( $past, array( 'estimated_date' ) );
		$this->assertArrayHasKey( 'estimated_date', $issues );
		$this->assertStringStartsWith( 'estimated_date:', $issues['estimated_date']['fingerprint'] );
		$this->assertSame( array(), $this->insights->wps_tofw_evaluate_order( $future, array( 'estimated_date' ) ) );
	}

	/**
	 * The manual date ends at 23:59 of that day unless a time is set.
	 */
	public function test_estimated_timestamp_uses_end_of_day_or_time() {
		$order = $this->order( array( 'id' => 1, 'meta' => array( 'wps_tofw_estimated_delivery_date' => '2024-03-10' ) ) );
		$this->assertSame( strtotime( '2024-03-10 23:59:00 UTC' ), $this->insights->wps_tofw_get_estimated_timestamp( $order ) );

		$order = $this->order(
			array(
				'id'   => 2,
				'meta' => array(
					'wps_tofw_estimated_delivery_date' => '2024-03-10',
					'wps_tofw_estimated_delivery_time' => '14:30',
				),
			)
		);
		$this->assertSame( strtotime( '2024-03-10 14:30:00 UTC' ), $this->insights->wps_tofw_get_estimated_timestamp( $order ) );
	}

	/**
	 * Without a manual date the computed EDD range ("to" date) of the estimated delivery feature is used.
	 */
	public function test_estimated_timestamp_falls_back_to_computed_edd() {
		$to    = gmdate( 'Y-m-d', time() - 2 * DAY_IN_SECONDS );
		$order = $this->order(
			array(
				'meta' => array(
					'wps_tofw_edd_from' => gmdate( 'Y-m-d', time() - 5 * DAY_IN_SECONDS ),
					'wps_tofw_edd_to'   => $to,
				),
			)
		);
		\Brain\Monkey\Functions\when( 'date_i18n' )->alias( 'gmdate' );
		\Brain\Monkey\Functions\when( 'wp_date' )->alias(
			static function ( $format, $ts = null ) {
				return gmdate( $format, null === $ts ? time() : $ts );
			}
		);
		\Brain\Monkey\Functions\when( 'wp_parse_args' )->alias(
			static function ( $args, $defaults = array() ) {
				return array_merge( (array) $defaults, (array) $args );
			}
		);

		$this->assertSame( strtotime( $to . ' 23:59:00 UTC' ), $this->insights->wps_tofw_get_estimated_timestamp( $order ) );
		$this->assertArrayHasKey( 'estimated_date', $this->insights->wps_tofw_evaluate_order( $order, array( 'estimated_date' ) ) );
	}

	/**
	 * No date at all => 0, invalid date => 0.
	 */
	public function test_estimated_timestamp_missing_or_invalid() {
		$this->assertSame( 0, $this->insights->wps_tofw_get_estimated_timestamp( $this->order( array( 'id' => 1 ) ) ) );
		$this->assertSame( 0, $this->insights->wps_tofw_get_estimated_timestamp( $this->order( array( 'id' => 2, 'meta' => array( 'wps_tofw_estimated_delivery_date' => 'not a date' ) ) ) ) );
	}

	/**
	 * Carrier exception => exception issue (only when that condition is enabled).
	 */
	public function test_exception_condition() {
		$order = $this->order( array( 'meta' => array( 'wps_tofw_carrier_status' => 'failed_attempt' ) ) );

		$this->assertSame( 'exception', $this->insights->wps_tofw_evaluate_order( $order )['exception']['fingerprint'] );

		$this->setOptions( array( 'wps_tofw_delivery_alerts_conditions' => array( 'expected_days' ) ) );
		$this->assertSame( array(), $this->insights->wps_tofw_evaluate_order( $order ) );
	}

	/**
	 * Delivered and cancelled orders are never late, whatever their dates.
	 */
	public function test_delivered_and_cancelled_orders_are_never_late() {
		$meta = array(
			'wps_tofw_shipped_at'              => time() - 30 * DAY_IN_SECONDS,
			'wps_tofw_estimated_delivery_date' => gmdate( 'Y-m-d', time() - 20 * DAY_IN_SECONDS ),
		);
		$this->assertSame( array(), $this->insights->wps_tofw_evaluate_order( $this->order( array( 'id' => 1, 'status' => 'completed', 'meta' => $meta ) ) ) );
		$this->assertSame( array(), $this->insights->wps_tofw_evaluate_order( $this->order( array( 'id' => 2, 'status' => 'refunded', 'meta' => $meta ) ) ) );
	}

	/**
	 * Unknown conditions in the option are dropped; non-array falls back to none.
	 */
	public function test_alert_conditions_are_sanitized() {
		$this->setOptions( array( 'wps_tofw_delivery_alerts_conditions' => array( 'exception', 'evil', 'estimated_date' ) ) );
		$this->assertSame( array( 'exception', 'estimated_date' ), $this->insights->wps_tofw_get_alert_conditions() );

		$this->setOptions( array( 'wps_tofw_delivery_alerts_conditions' => 'exception' ) );
		$this->assertSame( array(), $this->insights->wps_tofw_get_alert_conditions() );
	}

	/**
	 * Delivered timestamp: delivered meta, else completion date for completed orders.
	 */
	public function test_delivered_timestamp() {
		$this->assertSame( 1700000000, $this->insights->wps_tofw_get_delivered_timestamp( $this->order( array( 'id' => 1, 'meta' => array( 'wps_tofw_delivered_at' => 1700000000 ) ) ) ) );

		$completed = new \DateTime( '@1700100000' );
		$order     = $this->order( array( 'id' => 2, 'status' => 'completed', 'get_date_completed' => $completed ) );
		$this->assertSame( 1700100000, $this->insights->wps_tofw_get_delivered_timestamp( $order ) );

		$this->assertSame( 0, $this->insights->wps_tofw_get_delivered_timestamp( $this->order( array( 'id' => 3 ) ) ) );
	}
}
