<?php
/**
 * WPS-8396: CSV import job flow (dry-run validation, Action Scheduler batches, cleanup).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\TrackingImport;

require_once __DIR__ . '/fixtures.php';

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Tests\Unit\TestCase;

/**
 * Tests for Track_Orders_For_Woocommerce_Tracking_Import (job processing).
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ImportJobTest extends TestCase {

	use TrackingFixtures;

	/**
	 * Action Scheduler actions enqueued: list of [ hook, args, group ].
	 *
	 * @var array
	 */
	private $enqueued = array();

	/**
	 * Load the classes and stub WordPress.
	 */
	protected function set_up() {
		parent::set_up();
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-tracking-service.php' );
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-tracking-import.php' );
		$this->stubTrackingEnvironment();
		$this->stubJsonResponses();

		$_REQUEST = array();
		$_POST    = array();
		$_GET     = array();

		Functions\when( 'check_ajax_referer' )->justReturn( 1 );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_set_current_user' )->justReturn( null );

		$this->enqueued = array();
		$enqueued       = &$this->enqueued;
		Functions\when( 'as_enqueue_async_action' )->alias(
			static function ( $hook, $args = array(), $group = '' ) use ( &$enqueued ) {
				$enqueued[] = array( $hook, $args, $group );
				return count( $enqueued );
			}
		);
	}

	/**
	 * Clean request globals.
	 */
	protected function tear_down() {
		$_REQUEST = array();
		$_POST    = array();
		$_GET     = array();
		parent::tear_down();
	}

	/**
	 * Build a stored job from CSV-like rows.
	 *
	 * @param array $rows  List of row data arrays (order_id, carrier, tracking_number, ...).
	 * @param array $extra Job overrides.
	 * @return array
	 */
	private function storeJob( array $rows, array $extra = array() ) {
		$job_rows = array();
		foreach ( $rows as $i => $data ) {
			$job_rows[] = array(
				'line'   => $i + 2,
				'data'   => array_merge( array_fill_keys( \Track_Orders_For_Woocommerce_Tracking_Import::COLUMNS, '' ), $data ),
				'valid'  => null,
				'errors' => array(),
				'result' => '',
			);
		}
		$job = array_merge(
			array(
				'id'          => 'job1',
				'user_id'     => 7,
				'created'     => time(),
				'file_name'   => 'tracking.csv',
				'status'      => 'validating',
				'notify'      => 'settings',
				'rows'        => $job_rows,
				'validated'   => 0,
				'pending_qty' => array(),
				'seen'        => array(),
				'queue'       => array(),
				'next_batch'  => 0,
				'processed'   => 0,
				'succeeded'   => 0,
				'failed'      => 0,
			),
			$extra
		);
		$this->setOptions( array( 'wps_tofw_tracking_import_job_' . $job['id'] => $job ) );
		return $job;
	}

	/**
	 * Current stored job.
	 *
	 * @param string $id Job id.
	 * @return array
	 */
	private function job( $id = 'job1' ) {
		return $this->getOption( 'wps_tofw_tracking_import_job_' . $id );
	}

	/**
	 * Call an AJAX handler and return the JSON response it sent.
	 *
	 * @param string $method Handler method.
	 * @return JsonResponse
	 */
	private function ajax( $method ) {
		$import = new \Track_Orders_For_Woocommerce_Tracking_Import( '1.0.0' );
		try {
			$import->$method();
		} catch ( JsonResponse $response ) {
			return $response;
		}
		$this->fail( $method . '() did not send a JSON response' );
	}

	/**
	 * AJAX endpoints reject a bad nonce and users without manage_woocommerce (403).
	 */
	public function test_ajax_endpoints_require_nonce_and_capability() {
		Functions\when( 'check_ajax_referer' )->justReturn( false );
		foreach ( array( 'ajax_upload', 'ajax_validate', 'ajax_start', 'ajax_progress' ) as $method ) {
			$response = $this->ajax( $method );
			$this->assertFalse( $response->success, $method );
			$this->assertSame( 403, $response->status, $method );
		}

		Functions\when( 'check_ajax_referer' )->justReturn( 1 );
		$checked = array();
		Functions\when( 'current_user_can' )->alias(
			static function ( $cap ) use ( &$checked ) {
				$checked[] = $cap;
				return false;
			}
		);
		$response = $this->ajax( 'ajax_validate' );
		$this->assertFalse( $response->success );
		$this->assertSame( 403, $response->status );
		$this->assertSame( array( 'manage_woocommerce' ), $checked );
	}

	/**
	 * Upload without a file is rejected.
	 */
	public function test_upload_requires_a_file() {
		$_FILES   = array();
		$response = $this->ajax( 'ajax_upload' );
		$this->assertFalse( $response->success );
		$this->assertSame( 'Please choose a CSV file.', $response->data['message'] );
	}

	/**
	 * Dry run: rows are validated without writing to orders; invalid rows (bad id, unknown order,
	 * duplicates inside the file, validation errors) are reported, the job moves to preview.
	 */
	public function test_validate_is_a_dry_run_with_row_errors() {
		$order = $this->mockTrackableOrder( array( 'id' => 100 ) );
		$order->shouldNotReceive( 'save' );
		$this->mockTrackableOrder( array( 'id' => 101 ) );

		$this->storeJob(
			array(
				array( 'order_id' => '100', 'carrier' => 'UPS', 'tracking_number' => '1Z100', 'ship_date' => '2026-11-20' ),
				array( 'order_id' => '=1+1', 'carrier' => 'UPS', 'tracking_number' => 'X' ),
				array( 'order_id' => '999', 'carrier' => 'UPS', 'tracking_number' => 'X' ),
				array( 'order_id' => '100', 'carrier' => 'ups', 'tracking_number' => '1z100' ),
				array( 'order_id' => '101', 'carrier' => '', 'tracking_number' => 'T', 'ship_date' => 'tomorrow' ),
				array( 'order_id' => '101', 'carrier' => 'DHL', 'tracking_number' => 'JD1', 'status' => 'completed' ),
			)
		);
		$_REQUEST['job_id'] = 'JOB1';

		$response = $this->ajax( 'ajax_validate' );

		$this->assertTrue( $response->success );
		$this->assertTrue( $response->data['done'] );
		$this->assertSame( array( 'total' => 6, 'valid' => 2, 'invalid' => 4 ), $response->data['summary'] );

		// Invalid rows are listed first in the preview.
		$lines = array_column( $response->data['rows'], 'line' );
		$this->assertSame( array( 3, 4, 5, 6, 2, 7 ), $lines );

		$by_line = array_column( $response->data['rows'], null, 'line' );
		$this->assertSame( array( 'Order ID must be a number.' ), $by_line[3]['errors'] );
		$this->assertSame( array( 'Order not found.' ), $by_line[4]['errors'] );
		$this->assertStringContainsString( 'Duplicate of line 2', implode( ' ', $by_line[5]['errors'] ) );
		$this->assertContains( 'Carrier is required.', $by_line[6]['errors'] );
		$this->assertContains( 'Ship date must use the YYYY-MM-DD format.', $by_line[6]['errors'] );

		$job = $this->job();
		$this->assertSame( 'preview', $job['status'] );
		$this->assertSame( array(), $job['seen'] );
		$this->assertSame( array(), $this->getOrderMeta( 100 ), 'dry run must not write order meta' );
		$this->assertCount( 0, $this->sent_mails );
		$this->assertSame( array(), $this->enqueued );
	}

	/**
	 * Large files are validated in chunks of 200 rows per request.
	 */
	public function test_validate_runs_in_chunks() {
		$this->mockTrackableOrder( array( 'id' => 100 ) );
		$rows = array();
		for ( $i = 0; $i < 250; $i++ ) {
			$rows[] = array( 'order_id' => '100', 'carrier' => 'UPS', 'tracking_number' => 'T' . $i );
		}
		$this->storeJob( $rows );
		$_REQUEST['job_id'] = 'job1';

		$first = $this->ajax( 'ajax_validate' );
		$this->assertFalse( $first->data['done'] );
		$this->assertSame( 200, $first->data['validated'] );
		$this->assertSame( 'validating', $this->job()['status'] );

		$second = $this->ajax( 'ajax_validate' );
		$this->assertTrue( $second->data['done'] );
		$this->assertSame( 250, $second->data['summary']['valid'] );
	}

	/**
	 * Partial quantities reserved by earlier rows of the same file are taken into account.
	 */
	public function test_validate_tracks_pending_quantities_across_rows() {
		$this->mockTrackableOrder( array( 'id' => 100, 'items' => array( $this->mockLineItem( 5, 'MUG', 2, 'Mug' ) ) ) );
		$this->storeJob(
			array(
				array( 'order_id' => '100', 'carrier' => 'UPS', 'tracking_number' => 'A', 'items' => 'MUG:1' ),
				array( 'order_id' => '100', 'carrier' => 'UPS', 'tracking_number' => 'B', 'items' => 'MUG:1' ),
				array( 'order_id' => '100', 'carrier' => 'UPS', 'tracking_number' => 'C', 'items' => 'MUG:1' ),
			)
		);
		$_REQUEST['job_id'] = 'job1';

		$response = $this->ajax( 'ajax_validate' );
		$this->assertSame( array( 'total' => 3, 'valid' => 2, 'invalid' => 1 ), $response->data['summary'] );
		$this->assertSame( 4, $response->data['rows'][0]['line'] );
	}

	/**
	 * Validating an unknown or already validated job fails.
	 */
	public function test_validate_unknown_job() {
		$_REQUEST['job_id'] = 'nope';
		$this->setOptions();
		$this->assertFalse( $this->ajax( 'ajax_validate' )->success );
	}

	/**
	 * Start queues valid rows in batches of 50 and enqueues only the first batch (chained).
	 */
	public function test_start_queues_batches_with_action_scheduler() {
		$rows = array();
		for ( $i = 0; $i < 120; $i++ ) {
			$rows[] = array( 'order_id' => '100', 'carrier' => 'UPS', 'tracking_number' => 'T' . $i );
		}
		$job = $this->storeJob( $rows, array( 'status' => 'preview' ) );
		foreach ( $job['rows'] as $i => $row ) {
			$job['rows'][ $i ]['valid'] = 7 !== $i;
		}
		$this->setOptions( array( 'wps_tofw_tracking_import_job_job1' => $job ) );
		$_REQUEST['job_id'] = 'job1';
		$_POST['notify']    = 'something-else';

		$response = $this->ajax( 'ajax_start' );

		$this->assertTrue( $response->success );
		$this->assertSame( 'queued', $response->data['status'] );
		$this->assertSame( 119, $response->data['total'] );
		$this->assertSame( 1, $response->data['invalid'] );

		$stored = $this->job();
		$this->assertSame( 'settings', $stored['notify'], 'unknown notify value falls back to settings' );
		$this->assertSame( array( 50, 50, 19 ), array_map( 'count', $stored['queue'] ) );
		$this->assertNotContains( 7, $stored['queue'][0] );
		$this->assertSame(
			array( array( 'wps_tofw_tracking_import_batch', array( 'job_id' => 'job1', 'batch' => 0 ), 'wps-tofw-tracking-import' ) ),
			$this->enqueued
		);

		// Starting twice is refused.
		$this->assertFalse( $this->ajax( 'ajax_start' )->success );
	}

	/**
	 * Start refuses a job without valid rows.
	 */
	public function test_start_without_valid_rows() {
		$job                     = $this->storeJob( array( array( 'order_id' => 'x' ) ), array( 'status' => 'preview' ) );
		$job['rows'][0]['valid'] = false;
		$this->setOptions( array( 'wps_tofw_tracking_import_job_job1' => $job ) );
		$_REQUEST['job_id'] = 'job1';

		$response = $this->ajax( 'ajax_start' );
		$this->assertFalse( $response->success );
		$this->assertSame( array(), $this->enqueued );
	}

	/**
	 * Build a queued job for process_batch().
	 *
	 * @param array $rows   Row data.
	 * @param array $queue  Batches of row indexes.
	 * @param array $extra  Job overrides.
	 */
	private function queuedJob( array $rows, array $queue, array $extra = array() ) {
		$job = $this->storeJob( $rows, array_merge( array( 'status' => 'queued', 'queue' => $queue ), $extra ) );
		foreach ( $job['rows'] as $i => $row ) {
			$job['rows'][ $i ]['valid'] = true;
		}
		$this->setOptions( array( 'wps_tofw_tracking_import_job_job1' => $job ) );
	}

	/**
	 * A failing row (order deleted since the preview, exception in a hook) is recorded and the batch
	 * goes on; the next batch is chained.
	 */
	public function test_failed_rows_do_not_stall_the_batch() {
		$this->mockTrackableOrder( array( 'id' => 100 ) );
		$this->mockTrackableOrder(
			array(
				'id'             => 102,
				'add_order_note' => static function () {
					throw new \RuntimeException( 'Note storage exploded' );
				},
			)
		);
		$this->mockTrackableOrder( array( 'id' => 103 ) );

		$this->queuedJob(
			array(
				array( 'order_id' => '100', 'carrier' => 'UPS', 'tracking_number' => 'A1' ),
				array( 'order_id' => '101', 'carrier' => 'UPS', 'tracking_number' => 'B1' ),
				array( 'order_id' => '102', 'carrier' => 'UPS', 'tracking_number' => 'C1' ),
				array( 'order_id' => '103', 'carrier' => 'UPS', 'tracking_number' => 'D1' ),
				array( 'order_id' => '103', 'carrier' => 'UPS', 'tracking_number' => 'D2' ),
			),
			array( array( 0, 1, 2, 3 ), array( 4 ) )
		);
		$users = array();
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'wp_set_current_user' )->alias(
			static function ( $id ) use ( &$users ) {
				$users[] = $id;
			}
		);

		$import = new \Track_Orders_For_Woocommerce_Tracking_Import( '1.0.0' );
		$import->process_batch( 'job1', 0 );

		$this->assertSame( array( 7, 1 ), $users, 'runs as the importing user, then restores the previous user' );

		$job = $this->job();
		$this->assertSame( 'processing', $job['status'] );
		$this->assertSame( 1, $job['next_batch'] );
		$this->assertSame( 4, $job['processed'] );
		$this->assertSame( 2, $job['succeeded'] );
		$this->assertSame( 2, $job['failed'] );
		$this->assertSame( array( 'success', 'failed', 'failed', 'success', '' ), array_column( $job['rows'], 'result' ) );
		$this->assertSame( array( 'Order not found.' ), $job['rows'][1]['errors'] );
		$this->assertSame( array( 'Note storage exploded' ), $job['rows'][2]['errors'] );
		$this->assertSame( 'csv', $this->getOrderMeta( 100, 'wps_tofw_tracking_shipments' )[0]['source'] );
		$this->assertSame(
			array( array( 'wps_tofw_tracking_import_batch', array( 'job_id' => 'job1', 'batch' => 1 ), 'wps-tofw-tracking-import' ) ),
			$this->enqueued
		);

		// Last batch completes the job and fires the completed action, no further batch.
		Actions\expectDone( 'wps_tofw_tracking_import_completed' )->once();
		$import->process_batch( 'job1', 1 );
		$job = $this->job();
		$this->assertSame( 'completed', $job['status'] );
		$this->assertSame( 3, $job['succeeded'] );
		$this->assertCount( 1, $this->enqueued );
		$this->assertSame( array( 'A1', 'D1', 'D2' ), array_merge(
			array_column( $this->getOrderMeta( 100, 'wps_tofw_tracking_shipments' ), 'tracking_number' ),
			array_column( $this->getOrderMeta( 103, 'wps_tofw_tracking_shipments' ), 'tracking_number' )
		) );
	}

	/**
	 * Stale, duplicated or out-of-order batch actions are ignored; rows already handled are skipped.
	 */
	public function test_stale_and_repeated_batches_are_ignored() {
		$this->mockTrackableOrder( array( 'id' => 100 ) );
		$this->queuedJob(
			array(
				array( 'order_id' => '100', 'carrier' => 'UPS', 'tracking_number' => 'A1' ),
				array( 'order_id' => '100', 'carrier' => 'UPS', 'tracking_number' => 'A2' ),
			),
			array( array( 0, 1 ) )
		);
		$job                      = $this->job();
		$job['rows'][0]['result'] = 'success';
		$this->setOptions( array( 'wps_tofw_tracking_import_job_job1' => $job ) );

		$import = new \Track_Orders_For_Woocommerce_Tracking_Import( '1.0.0' );
		$import->process_batch( 'job1', 3 );
		$import->process_batch( 'unknown', 0 );
		$this->assertSame( 0, $this->job()['processed'] );

		$import->process_batch( 'job1', 0 );
		$this->assertSame( array( 'A2' ), array_column( $this->getOrderMeta( 100, 'wps_tofw_tracking_shipments' ), 'tracking_number' ) );
		$this->assertSame( 'completed', $this->job()['status'] );

		// Running the same batch again after completion does nothing.
		$import->process_batch( 'job1', 0 );
		$this->assertSame( 1, $this->job()['processed'] );
	}

	/**
	 * notify_customer column: "no" skips the e-mail, "yes" forces it, empty = once per order;
	 * "Do not send" in the import screen overrides every row.
	 */
	public function test_notify_customer_per_row_and_global_override() {
		$this->mockTrackableOrder( array( 'id' => 100 ) );
		$this->mockTrackableOrder( array( 'id' => 101 ) );
		$this->mockTrackableOrder( array( 'id' => 102, 'meta' => array( 'wps_tofw_tracking_id_sent' => 1 ) ) );
		$this->mockTrackableOrder( array( 'id' => 103, 'meta' => array( 'wps_tofw_tracking_id_sent' => 1 ) ) );

		$this->queuedJob(
			array(
				array( 'order_id' => '100', 'carrier' => 'UPS', 'tracking_number' => 'A', 'notify_customer' => 'no' ),
				array( 'order_id' => '101', 'carrier' => 'UPS', 'tracking_number' => 'B', 'notify_customer' => '' ),
				array( 'order_id' => '101', 'carrier' => 'UPS', 'tracking_number' => 'B2', 'notify_customer' => '' ),
				array( 'order_id' => '102', 'carrier' => 'UPS', 'tracking_number' => 'C', 'notify_customer' => 'Yes' ),
				array( 'order_id' => '103', 'carrier' => 'UPS', 'tracking_number' => 'D', 'notify_customer' => '' ),
			),
			array( array( 0, 1, 2, 3, 4 ) )
		);

		( new \Track_Orders_For_Woocommerce_Tracking_Import( '1.0.0' ) )->process_batch( 'job1', 0 );

		$this->assertSame( array( 'B', 'C' ), array_map(
			static function ( $mail ) {
				preg_match( '#<td>\s*\d+\s*</td>\s*<td>([^<]+)</td>#', $mail[2], $m );
				return isset( $m[1] ) ? $m[1] : '';
			},
			$this->sent_mails
		) );
		$this->assertNull( $this->getOrderMeta( 100, 'wps_tofw_tracking_id_sent' ) );

		// Global "none": nothing is sent even with notify_customer=yes.
		$this->mockTrackableOrder( array( 'id' => 200 ) );
		$this->sent_mails = array();
		$this->queuedJob(
			array( array( 'order_id' => '200', 'carrier' => 'UPS', 'tracking_number' => 'Z', 'notify_customer' => 'yes' ) ),
			array( array( 0 ) ),
			array( 'notify' => 'none' )
		);
		( new \Track_Orders_For_Woocommerce_Tracking_Import( '1.0.0' ) )->process_batch( 'job1', 0 );
		$this->assertCount( 0, $this->sent_mails );
		$this->assertSame( 'Z', $this->getOrderMeta( 200, 'wps_tofw_package_tracking_number' ) );
	}

	/**
	 * Progress endpoint reports counters.
	 */
	public function test_progress_payload() {
		$this->queuedJob(
			array(
				array( 'order_id' => '1' ),
				array( 'order_id' => '2' ),
			),
			array( array( 0, 1 ) ),
			array( 'processed' => 1, 'succeeded' => 1 )
		);
		$_REQUEST['job_id'] = 'job1';

		$response = $this->ajax( 'ajax_progress' );
		$this->assertSame(
			array(
				'status'    => 'queued',
				'total'     => 2,
				'processed' => 1,
				'succeeded' => 1,
				'failed'    => 0,
				'invalid'   => 0,
				'done'      => false,
			),
			$response->data
		);
	}

	/**
	 * Cleanup: running jobs are rescheduled, stuck ones fail, finished ones are deleted.
	 */
	public function test_cleanup_job() {
		$scheduled = array();
		Functions\when( 'as_schedule_single_action' )->alias(
			static function ( $time, $hook, $args ) use ( &$scheduled ) {
				$scheduled[] = array( $hook, $args );
			}
		);
		$import = new \Track_Orders_For_Woocommerce_Tracking_Import( '1.0.0' );

		$this->queuedJob( array( array( 'order_id' => '1' ) ), array( array( 0 ) ) );
		$import->cleanup_job( 'job1' );
		$this->assertIsArray( $this->job(), 'a running job is kept' );
		$this->assertSame( array( array( 'wps_tofw_tracking_import_cleanup', array( 'job_id' => 'job1' ) ) ), $scheduled );

		$this->queuedJob( array( array( 'order_id' => '1' ) ), array( array( 0 ) ), array( 'created' => time() - 4 * DAY_IN_SECONDS ) );
		Actions\expectDone( 'wps_tofw_tracking_import_failed' )->once();
		$import->cleanup_job( 'job1' );
		$this->assertNull( $this->job(), 'stuck job is abandoned and deleted' );

		$this->storeJob( array( array( 'order_id' => '1' ) ), array( 'status' => 'completed' ) );
		$import->cleanup_job( 'job1' );
		$this->assertNull( $this->job() );
		$this->assertCount( 1, $scheduled );
	}

	/**
	 * Error report and sample downloads require manage_woocommerce and a valid nonce.
	 */
	public function test_downloads_require_permission() {
		$import = new \Track_Orders_For_Woocommerce_Tracking_Import( '1.0.0' );

		Functions\when( 'current_user_can' )->justReturn( false );
		$_GET['_wpnonce'] = 'n';
		foreach ( array( 'download_report', 'download_sample' ) as $method ) {
			try {
				$import->$method();
				$this->fail( $method . ' should die' );
			} catch ( WpDie $die ) {
				$this->assertSame( 403, $die->args['response'] );
			}
		}

		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'wp_verify_nonce' )->justReturn( false );
		try {
			$import->download_report();
			$this->fail( 'download_report should die on a bad nonce' );
		} catch ( WpDie $die ) {
			$this->assertSame( 403, $die->args['response'] );
		}
	}

	/**
	 * The import tab is registered.
	 */
	public function test_settings_tab_is_registered() {
		$tabs = ( new \Track_Orders_For_Woocommerce_Tracking_Import() )->add_settings_tab( array( 'other' => array() ) );
		$this->assertArrayHasKey( 'track-orders-for-woocommerce-tracking-import', $tabs );
		$this->assertStringEndsWith( 'admin/partials/track-orders-for-woocommerce-tracking-import.php', $tabs['track-orders-for-woocommerce-tracking-import']['file_path'] );
		$this->assertFileExists( $tabs['track-orders-for-woocommerce-tracking-import']['file_path'] );
	}
}
