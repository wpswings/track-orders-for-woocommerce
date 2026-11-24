<?php
/**
 * WPS-8404: daily delivery alert job, dedupe, email digest and recipients.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\ShipmentInsights;

use Brain\Monkey\Functions;

/**
 * Tests for the daily check / batch processing and the alert email.
 */
class DeliveryAlertsTest extends InsightsTestCase {

	/**
	 * Emails "sent" through wp_mail.
	 *
	 * @var array
	 */
	protected $mails = array();

	/**
	 * Orders returned by wc_get_orders.
	 *
	 * @var array
	 */
	protected $orders = array();

	/**
	 * Last wc_get_orders() query.
	 *
	 * @var array|null
	 */
	protected $last_query;

	/**
	 * Stub mail, orders query and blog name.
	 */
	protected function set_up() {
		parent::set_up();
		if ( function_exists( 'WC' ) ) {
			$this->markTestSkipped( 'WC() is defined: the digest goes through the WooCommerce mailer.' );
		}
		$this->mails  = array();
		$this->orders = array();
		$mails        = &$this->mails;
		$orders       = &$this->orders;
		$query        = &$this->last_query;

		Functions\when( 'wp_mail' )->alias(
			static function ( $to, $subject, $body ) use ( &$mails ) {
				$mails[] = compact( 'to', 'subject', 'body' );
				return true;
			}
		);
		Functions\when( 'wc_get_orders' )->alias(
			static function ( $args ) use ( &$orders, &$query ) {
				$query = $args;
				return $orders;
			}
		);
		Functions\when( 'get_bloginfo' )->justReturn( 'E2E Shop' );
		Functions\when( 'wp_specialchars_decode' )->returnArg();
		Functions\when( 'admin_url' )->alias(
			static function ( $path = '' ) {
				return 'http://example.org/wp-admin/' . $path;
			}
		);
		$this->stubWcGetOrder();
		$this->setOptions(
			array(
				'wps_tofw_delivery_alerts_enable'        => 'on',
				'wps_tofw_delivery_alerts_expected_days' => 5,
			)
		);
	}

	/**
	 * A late order (shipped 10 days ago) with tracking.
	 *
	 * @param int $id Order id.
	 * @return \Mockery\MockInterface
	 */
	protected function lateOrder( $id ) {
		return $this->order(
			array(
				'id'   => $id,
				'meta' => array(
					'wps_tofw_shipped_at'                => time() - 10 * DAY_IN_SECONDS,
					'wps_tofw_package_tracking_number'   => 'TRK' . $id,
					'wps_tofw_selected_shipping_service' => 'royal_mail',
				),
			)
		);
	}

	/**
	 * Daily check: active alert recorded, digest emailed with order, carrier, tracking and issue.
	 */
	public function test_daily_check_sends_digest_and_records_active_alerts() {
		$this->setOptions( array( 'wps_tofw_delivery_alerts_recipients' => 'ops@example.com, not-an-email; boss@example.com' ) );
		$this->orders = array(
			$this->lateOrder( 101 ),
			$this->order( array( 'id' => 102, 'meta' => array( 'wps_tofw_carrier_status' => 'failed_attempt' ) ) ),
			$this->order( array( 'id' => 103, 'meta' => array( 'wps_tofw_shipped_at' => time() - DAY_IN_SECONDS ) ) ),
		);

		$this->insights->wps_tofw_run_daily_check();

		$active = $this->getOption( 'wps_tofw_delivery_active_alerts' );
		$this->assertSame( array( 101, 102 ), array_keys( $active ) );
		$this->assertSame( array( 'expected_days' ), $active[101]['issues'] );
		$this->assertSame( array( 'exception' ), $active[102]['issues'] );

		$this->assertCount( 1, $this->mails, 'one digest for all orders' );
		$mail = $this->mails[0];
		$this->assertSame( array( 'ops@example.com', 'boss@example.com' ), $mail['to'] );
		$this->assertStringContainsString( 'E2E Shop', $mail['subject'] );
		$this->assertStringContainsString( '#101', $mail['body'] );
		$this->assertStringContainsString( 'TRK101', $mail['body'] );
		$this->assertStringContainsString( 'Royal Mail', $mail['body'] );
		$this->assertStringContainsString( '#102', $mail['body'] );
		$this->assertStringContainsString( 'Delivery exception', $mail['body'] );
		$this->assertStringNotContainsString( '#103', $mail['body'] );
		$this->assertSame( array(), $this->getOption( 'wps_tofw_delivery_alerts_pending_digest' ) );
	}

	/**
	 * The batch only scans open orders (no completed / cancelled / refunded) within the lookback window.
	 */
	public function test_scan_query() {
		$this->setOptions(
			array(
				'wps_tofw_order_status_in_shipping'          => array( 'wc-dispatched', 'wc-completed' ),
				'wps_tofw_delivery_alerts_exception_statuses' => array( 'wc-delivery-failed' ),
			)
		);
		$this->insights->wps_tofw_run_daily_check();

		$this->assertSame( 'shop_order', $this->last_query['type'] );
		$this->assertEqualsCanonicalizing( array( 'processing', 'on-hold', 'dispatched', 'delivery-failed' ), $this->last_query['status'] );
		$this->assertSame( 100, $this->last_query['limit'] );
		$this->assertStringStartsWith( '>', $this->last_query['date_created'] );
	}

	/**
	 * Each issue is emailed once per order: a second run sends nothing, but the alert stays active.
	 */
	public function test_alerts_are_deduplicated() {
		$this->orders = array( $this->lateOrder( 201 ) );

		$this->insights->wps_tofw_run_daily_check();
		$this->insights->wps_tofw_run_daily_check();

		$this->assertCount( 1, $this->mails );
		$this->assertCount( 1, (array) $this->getOrderMeta( 201, 'wps_tofw_delivery_alerts_sent' ) );
		$this->assertArrayHasKey( 201, $this->getOption( 'wps_tofw_delivery_active_alerts' ) );
	}

	/**
	 * A new issue on an already alerted order (new estimated date passed) is emailed again.
	 */
	public function test_new_issue_on_same_order_is_alerted() {
		$order        = $this->lateOrder( 301 );
		$this->orders = array( $order );
		$this->insights->wps_tofw_run_daily_check();

		$order->update_meta_data( 'wps_tofw_estimated_delivery_date', gmdate( 'Y-m-d', time() - 2 * DAY_IN_SECONDS ) );
		$this->insights->wps_tofw_run_daily_check();

		$this->assertCount( 2, $this->mails );
		$this->assertStringContainsString( 'Estimated delivery date passed', $this->mails[1]['body'] );
		$this->assertCount( 2, $this->getOrderMeta( 301, 'wps_tofw_delivery_alerts_sent' ) );
	}

	/**
	 * Resolved orders (delivered) are pruned from the active alerts.
	 */
	public function test_resolved_orders_are_pruned() {
		$order        = $this->lateOrder( 401 );
		$this->orders = array( $order );
		$this->insights->wps_tofw_run_daily_check();
		$this->assertArrayHasKey( 401, $this->getOption( 'wps_tofw_delivery_active_alerts' ) );

		$order->shouldReceive( 'get_status' )->andReturn( 'completed' );
		$this->orders = array();
		$this->insights->wps_tofw_run_daily_check();

		$this->assertSame( array(), $this->getOption( 'wps_tofw_delivery_active_alerts' ) );
		$this->assertCount( 1, $this->mails );
	}

	/**
	 * Deleted orders are pruned too.
	 */
	public function test_deleted_orders_are_pruned() {
		$this->setOptions( array( 'wps_tofw_delivery_active_alerts' => array( 999 => array( 'issues' => array( 'exception' ), 'detected' => 1 ) ) ) );
		$this->assertSame( array(), $this->insights->wps_tofw_prune_active_alerts() );
	}

	/**
	 * Alerts disabled: the daily job does nothing.
	 */
	public function test_disabled_alerts_do_nothing() {
		$this->setOptions( array( 'wps_tofw_delivery_alerts_enable' => '' ) );
		$this->orders = array( $this->lateOrder( 501 ) );
		Functions\expect( 'wc_get_orders' )->never();

		$this->insights->wps_tofw_run_daily_check();
		$this->insights->wps_tofw_process_alert_batch( 1 );

		$this->assertSame( array(), $this->mails );
		$this->assertNull( $this->getOption( 'wps_tofw_delivery_active_alerts' ) );
	}

	/**
	 * A full batch queues the next page via Action Scheduler and defers the email.
	 */
	public function test_full_batch_enqueues_next_page() {
		for ( $i = 1; $i <= 100; $i++ ) {
			$this->orders[] = $this->order( array( 'id' => 1000 + $i ) );
		}
		$this->orders[0] = $this->lateOrder( 1001 );
		Functions\expect( 'as_enqueue_async_action' )->once()->with( 'wps_tofw_delivery_alerts_process_batch', array( 2 ), 'wps-tofw-delivery-alerts' );

		$this->insights->wps_tofw_process_alert_batch( 1 );

		$this->assertSame( array(), $this->mails, 'digest waits for the last batch' );
		$this->assertArrayHasKey( 1001, $this->getOption( 'wps_tofw_delivery_alerts_pending_digest' ) );
	}

	/**
	 * No issues => no email.
	 */
	public function test_no_issues_no_email() {
		$this->orders = array( $this->order( array( 'id' => 601 ) ) );
		$this->insights->wps_tofw_run_daily_check();
		$this->assertSame( array(), $this->mails );
	}

	/**
	 * Email content is escaped (carrier label from order meta).
	 */
	public function test_email_escapes_order_data() {
		Functions\when( 'esc_html' )->alias(
			static function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES );
			}
		);
		$order = $this->order(
			array(
				'id'   => 701,
				'meta' => array(
					'wps_tofw_carrier_status'          => 'exception',
					'wps_tofw_package_tracking_number' => '<img src=x onerror=alert(1)>',
				),
			)
		);
		$this->assertTrue( $this->insights->wps_tofw_send_alert_email( array( 701 => array( 'exception' => 'Delivery exception' ) ) ) );
		$this->assertStringNotContainsString( '<img', $this->mails[0]['body'] );
		$this->assertStringContainsString( '&lt;img', $this->mails[0]['body'] );
		unset( $order );
	}

	/**
	 * Recipients fall back to the admin email; the filter can change them.
	 */
	public function test_recipients_fallback_and_filter() {
		$this->assertSame( array( 'admin@example.com' ), $this->insights->wps_tofw_get_recipients() );

		\Brain\Monkey\Filters\expectApplied( 'wps_tofw_delivery_alert_recipients' )->once()->andReturn( array( 'x@example.com' ) );
		$this->assertSame( array( 'x@example.com' ), $this->insights->wps_tofw_get_recipients() );
	}

	/**
	 * Unknown order ids in the digest produce no email.
	 */
	public function test_email_with_only_missing_orders_is_not_sent() {
		$this->assertFalse( $this->insights->wps_tofw_send_alert_email( array( 12345 => array( 'x' ) ) ) );
		$this->assertSame( array(), $this->mails );
	}

	/**
	 * Status change / tracking / carrier hooks record shipped and delivered timestamps once.
	 */
	public function test_timestamps_recorded_once() {
		$this->setOptions( array( 'wps_tofw_order_status_in_shipping' => array( 'wc-dispatched' ) ) );
		$order = $this->order( array( 'id' => 801 ) );
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$this->insights->wps_tofw_record_status_timestamps( 801, 'processing', 'dispatched' );
		$shipped = $this->getOrderMeta( 801, 'wps_tofw_shipped_at' );
		$this->assertGreaterThan( 0, $shipped );

		$order->update_meta_data( 'wps_tofw_shipped_at', 123 );
		$this->insights->wps_tofw_record_status_timestamps( 801, 'dispatched', 'dispatched' );
		$this->assertSame( 123, $this->getOrderMeta( 801, 'wps_tofw_shipped_at' ), 'not overwritten' );

		$this->insights->wps_tofw_record_carrier_status( 801, 'delivered' );
		$this->assertGreaterThan( 0, $this->getOrderMeta( 801, 'wps_tofw_delivered_at' ) );

		$fresh = $this->order( array( 'id' => 802, 'meta' => array( 'wps_tofw_package_tracking_number' => 'T1' ) ) );
		Functions\when( 'wc_get_order' )->justReturn( $fresh );
		$this->insights->wps_tofw_record_tracking_added( 802 );
		$this->assertGreaterThan( 0, $this->getOrderMeta( 802, 'wps_tofw_shipped_at' ) );
	}

	/**
	 * Daily action is scheduled when enabled and unscheduled when disabled.
	 */
	public function test_schedule_sync() {
		Functions\when( 'as_next_scheduled_action' )->justReturn( false );
		Functions\expect( 'as_schedule_recurring_action' )->once()->with( \Mockery::type( 'int' ), DAY_IN_SECONDS, 'wps_tofw_delivery_alerts_daily_check', array(), 'wps-tofw-delivery-alerts' );
		$this->insights->wps_tofw_sync_schedule();

		$this->setOptions( array( 'wps_tofw_delivery_alerts_enable' => 'off' ) );
		Functions\when( 'as_next_scheduled_action' )->justReturn( 12345 );
		Functions\expect( 'as_unschedule_all_actions' )->once()->with( 'wps_tofw_delivery_alerts_daily_check', array(), 'wps-tofw-delivery-alerts' );
		$this->insights->wps_tofw_sync_schedule();
	}
}
