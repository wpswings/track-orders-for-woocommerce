<?php
/**
 * Bulk tracking import (CSV) controller.
 *
 * Flow: upload (parse + store job) -> chunked validation (dry-run preview) ->
 * start (Action Scheduler, 50 rows per action, chained) -> progress polling ->
 * error report CSV.
 *
 * @link       https://wpswings.com/
 * @since      1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV tracking import.
 *
 * @since 1.3.0
 */
class Track_Orders_For_Woocommerce_Tracking_Import {

	/**
	 * Settings tab slug.
	 *
	 * @var string
	 */
	const TAB = 'track-orders-for-woocommerce-tracking-import';

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	const NONCE = 'wps_tofw_tracking_import';

	/**
	 * Option prefix for stored jobs.
	 *
	 * @var string
	 */
	const JOB_OPTION_PREFIX = 'wps_tofw_tracking_import_job_';

	/**
	 * Action Scheduler hook for one batch.
	 *
	 * @var string
	 */
	const BATCH_HOOK = 'wps_tofw_tracking_import_batch';

	/**
	 * Action Scheduler hook to remove an old job.
	 *
	 * @var string
	 */
	const CLEANUP_HOOK = 'wps_tofw_tracking_import_cleanup';

	/**
	 * Action Scheduler group.
	 *
	 * @var string
	 */
	const GROUP = 'wps-tofw-tracking-import';

	/**
	 * Rows per Action Scheduler action.
	 *
	 * @var int
	 */
	const BATCH_SIZE = 50;

	/**
	 * Rows validated per AJAX request during the dry run.
	 *
	 * @var int
	 */
	const VALIDATE_CHUNK = 200;

	/**
	 * CSV columns in canonical order.
	 *
	 * @var array
	 */
	const COLUMNS = array( 'order_id', 'carrier', 'tracking_number', 'ship_date', 'items', 'status', 'notify_customer' );

	/**
	 * Days after which a job that is still queued/processing is abandoned by the cleanup.
	 *
	 * @var int
	 */
	const MAX_JOB_AGE_DAYS = 3;

	/**
	 * Plugin version (asset versioning).
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor.
	 *
	 * @param string $version Plugin version.
	 */
	public function __construct( $version = '' ) {
		$this->version = $version;
	}

	/**
	 * Register the settings tab.
	 *
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public function add_settings_tab( $tabs ) {
		$tabs[ self::TAB ] = array(
			'title'     => esc_html__( 'Bulk Tracking Import', 'track-orders-for-woocommerce' ),
			'name'      => self::TAB,
			'file_path' => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-tracking-import.php',
		);
		return $tabs;
	}

	/**
	 * Enqueue the import screen script.
	 *
	 * @param string $hook Admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		$tab = isset( $_GET['tofw_tab'] ) ? sanitize_key( wp_unslash( $_GET['tofw_tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::TAB !== $tab || false === strpos( (string) $hook, 'track_orders_for_woocommerce_menu' ) ) {
			return;
		}

		wp_enqueue_script(
			'wps-tofw-tracking-import',
			TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/track-orders-for-woocommerce-tracking-import.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_localize_script(
			'wps-tofw-tracking-import',
			'wps_tofw_import',
			array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( self::NONCE ),
				'report_url'   => wp_nonce_url( admin_url( 'admin-post.php?action=wps_tofw_tracking_import_report' ), self::NONCE ),
				'poll_ms'      => 3000,
				'i18n'         => array(
					'choose_file'   => __( 'Please choose a CSV file.', 'track-orders-for-woocommerce' ),
					'uploading'     => __( 'Uploading and reading file...', 'track-orders-for-woocommerce' ),
					'validating'    => __( 'Validating rows', 'track-orders-for-woocommerce' ),
					'importing'     => __( 'Importing', 'track-orders-for-woocommerce' ),
					'queued'        => __( 'Queued, waiting for the background processor...', 'track-orders-for-woocommerce' ),
					'completed'     => __( 'Import completed.', 'track-orders-for-woocommerce' ),
					'rows'          => __( 'Rows', 'track-orders-for-woocommerce' ),
					'valid'         => __( 'Valid', 'track-orders-for-woocommerce' ),
					'invalid'       => __( 'Invalid', 'track-orders-for-woocommerce' ),
					'imported'      => __( 'Imported', 'track-orders-for-woocommerce' ),
					'failed'        => __( 'Failed', 'track-orders-for-woocommerce' ),
					'no_valid'      => __( 'There are no valid rows to import.', 'track-orders-for-woocommerce' ),
					'request_error' => __( 'Request failed. Please try again.', 'track-orders-for-woocommerce' ),
					'confirm'       => __( 'Start importing the valid rows now?', 'track-orders-for-woocommerce' ),
					'truncated'     => __( 'Only the first rows are displayed. Download the error report for the full list of invalid rows.', 'track-orders-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Common permission + nonce check for AJAX endpoints.
	 */
	private function verify_ajax_request() {
		if ( ! check_ajax_referer( self::NONCE, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed. Reload the page and try again.', 'track-orders-for-woocommerce' ) ), 403 );
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to import tracking.', 'track-orders-for-woocommerce' ) ), 403 );
		}
	}

	/**
	 * Get the job id from the request.
	 *
	 * @return string
	 */
	private function get_request_job_id() {
		// Nonce verified by the caller.
		return isset( $_REQUEST['job_id'] ) ? preg_replace( '/[^a-z0-9]/', '', strtolower( sanitize_text_field( wp_unslash( $_REQUEST['job_id'] ) ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Load a job.
	 *
	 * @param string $job_id Job id.
	 * @return array|false
	 */
	public static function get_job( $job_id ) {
		if ( '' === (string) $job_id ) {
			return false;
		}
		$job = get_option( self::JOB_OPTION_PREFIX . $job_id, false );
		return is_array( $job ) ? $job : false;
	}

	/**
	 * Save a job (never autoloaded).
	 *
	 * @param array $job Job.
	 */
	public static function save_job( $job ) {
		update_option( self::JOB_OPTION_PREFIX . $job['id'], $job, false );
	}

	/**
	 * Map a CSV header row to column indexes.
	 *
	 * @param array $header Header cells.
	 * @return array|WP_Error column => index.
	 */
	public static function map_header( $header ) {
		$aliases = array(
			'order_id'        => array( 'order_id', 'order id', 'orderid', 'order', 'order_number', 'order number' ),
			'carrier'         => array( 'carrier', 'shipping_service', 'shipping service', 'courier', 'provider' ),
			'tracking_number' => array( 'tracking_number', 'tracking number', 'tracking', 'tracking_no', 'tracking no', 'tracking_id' ),
			'ship_date'       => array( 'ship_date', 'ship date', 'shipped_date', 'date_shipped', 'shipping_date' ),
			'items'           => array( 'items', 'item', 'products' ),
			'status'          => array( 'status', 'order_status', 'order status' ),
			'notify_customer' => array( 'notify_customer', 'notify customer', 'notify', 'send_email', 'send email' ),
		);

		$map = array();
		foreach ( $header as $index => $cell ) {
			$cell = strtolower( trim( (string) $cell ) );
			$cell = preg_replace( '/^\xEF\xBB\xBF/', '', $cell );
			foreach ( $aliases as $column => $names ) {
				if ( ! isset( $map[ $column ] ) && in_array( $cell, $names, true ) ) {
					$map[ $column ] = $index;
				}
			}
		}

		$missing = array_diff( array( 'order_id', 'carrier', 'tracking_number' ), array_keys( $map ) );
		if ( ! empty( $missing ) ) {
			return new WP_Error(
				'wps_tofw_csv_missing_columns',
				/* translators: %s: missing column names */
				sprintf( __( 'Missing required column(s): %s.', 'track-orders-for-woocommerce' ), implode( ', ', $missing ) )
			);
		}

		return $map;
	}

	/**
	 * Read and normalize a CSV file into rows.
	 *
	 * @param string $path File path.
	 * @param int    $max_rows Maximum data rows.
	 * @return array|WP_Error
	 */
	public static function parse_csv_file( $path, $max_rows ) {
		$handle = fopen( $path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			return new WP_Error( 'wps_tofw_csv_unreadable', __( 'The uploaded file could not be read.', 'track-orders-for-woocommerce' ) );
		}

		$first_line = (string) fgets( $handle );
		$delimiter  = substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ? ';' : ',';
		rewind( $handle );

		$header = fgetcsv( $handle, 0, $delimiter, '"', '\\' );
		if ( ! is_array( $header ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return new WP_Error( 'wps_tofw_csv_empty', __( 'The CSV file is empty.', 'track-orders-for-woocommerce' ) );
		}

		$map = self::map_header( $header );
		if ( is_wp_error( $map ) ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return $map;
		}

		$rows = array();
		$line = 1;
		while ( false !== ( $cells = fgetcsv( $handle, 0, $delimiter, '"', '\\' ) ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			++$line;
			if ( ! is_array( $cells ) || ( 1 === count( $cells ) && ( null === $cells[0] || '' === trim( (string) $cells[0] ) ) ) ) {
				continue; // Blank line.
			}
			if ( count( $rows ) >= $max_rows ) {
				fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				return new WP_Error(
					'wps_tofw_csv_too_many_rows',
					/* translators: %d: max rows */
					sprintf( __( 'The file has more than %d rows. Split it into smaller files.', 'track-orders-for-woocommerce' ), $max_rows )
				);
			}

			$data = array();
			foreach ( self::COLUMNS as $column ) {
				$data[ $column ] = isset( $map[ $column ], $cells[ $map[ $column ] ] ) ? sanitize_text_field( trim( (string) $cells[ $map[ $column ] ] ) ) : '';
			}
			$data['order_id'] = ltrim( $data['order_id'], '#' );

			$rows[] = array(
				'line'   => $line,
				'data'   => $data,
				'valid'  => null,
				'errors' => array(),
				'result' => '',
			);
		}
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( empty( $rows ) ) {
			return new WP_Error( 'wps_tofw_csv_no_rows', __( 'The CSV file has no data rows.', 'track-orders-for-woocommerce' ) );
		}

		return $rows;
	}

	/**
	 * AJAX: upload + parse the CSV, create a job.
	 */
	public function ajax_upload() {
		$this->verify_ajax_request();

		// Nonce and capability verified in verify_ajax_request().
		if ( empty( $_FILES['wps_tofw_import_file'] ) || ! isset( $_FILES['wps_tofw_import_file']['tmp_name'], $_FILES['wps_tofw_import_file']['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error( array( 'message' => __( 'Please choose a CSV file.', 'track-orders-for-woocommerce' ) ) );
		}

		$file = $_FILES['wps_tofw_import_file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp file path validated below.
		$name = sanitize_file_name( wp_unslash( $file['name'] ) );
		$tmp  = (string) $file['tmp_name'];

		if ( ! empty( $file['error'] ) || ! is_uploaded_file( $tmp ) ) {
			wp_send_json_error( array( 'message' => __( 'Upload failed.', 'track-orders-for-woocommerce' ) ) );
		}
		if ( 'csv' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Only .csv files are allowed.', 'track-orders-for-woocommerce' ) ) );
		}
		if ( (int) $file['size'] > 5 * MB_IN_BYTES ) {
			wp_send_json_error( array( 'message' => __( 'The file is too large (max 5 MB).', 'track-orders-for-woocommerce' ) ) );
		}

		/**
		 * Maximum rows accepted in one CSV import.
		 *
		 * @since 1.3.0
		 */
		$max_rows = (int) apply_filters( 'wps_tofw_tracking_import_max_rows', 5000 );
		$rows     = self::parse_csv_file( $tmp, $max_rows );
		if ( is_wp_error( $rows ) ) {
			wp_send_json_error( array( 'message' => $rows->get_error_message() ) );
		}

		$job = array(
			'id'          => strtolower( wp_generate_password( 16, false, false ) ),
			'user_id'     => get_current_user_id(),
			'created'     => time(),
			'file_name'   => $name,
			'status'      => 'validating',
			'notify'      => 'settings',
			'rows'        => $rows,
			'validated'   => 0,
			'pending_qty' => array(),
			'seen'        => array(),
			'queue'       => array(),
			'next_batch'  => 0,
			'processed'   => 0,
			'succeeded'   => 0,
			'failed'      => 0,
		);
		self::save_job( $job );

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + 2 * DAY_IN_SECONDS, self::CLEANUP_HOOK, array( 'job_id' => $job['id'] ), self::GROUP );
		}

		wp_send_json_success(
			array(
				'job_id' => $job['id'],
				'total'  => count( $rows ),
			)
		);
	}

	/**
	 * AJAX: validate the next chunk of rows (dry run).
	 */
	public function ajax_validate() {
		$this->verify_ajax_request();

		$job = self::get_job( $this->get_request_job_id() );
		if ( ! $job || 'validating' !== $job['status'] ) {
			wp_send_json_error( array( 'message' => __( 'Import job not found or already validated.', 'track-orders-for-woocommerce' ) ) );
		}

		$total = count( $job['rows'] );
		$end   = min( $total, $job['validated'] + self::VALIDATE_CHUNK );

		for ( $i = $job['validated']; $i < $end; $i++ ) {
			$row    = $job['rows'][ $i ];
			$data   = $row['data'];
			$errors = array();

			if ( '' === $data['order_id'] || ! ctype_digit( $data['order_id'] ) ) {
				$errors[] = __( 'Order ID must be a number.', 'track-orders-for-woocommerce' );
			} else {
				$seen_key = $data['order_id'] . '|' . strtolower( $data['tracking_number'] );
				if ( '' !== $data['tracking_number'] && isset( $job['seen'][ $seen_key ] ) ) {
					/* translators: %d: line number */
					$errors[] = sprintf( __( 'Duplicate of line %d in this file.', 'track-orders-for-woocommerce' ), $job['seen'][ $seen_key ] );
				}

				$pending  = isset( $job['pending_qty'][ $data['order_id'] ] ) ? $job['pending_qty'][ $data['order_id'] ] : array();
				$prepared = Track_Orders_For_Woocommerce_Tracking_Service::prepare( (int) $data['order_id'], $this->row_to_args( $data ), $pending );
				if ( is_wp_error( $prepared ) ) {
					$errors = array_merge( $errors, $prepared->get_error_messages() );
				} elseif ( empty( $errors ) ) {
					$job['seen'][ $seen_key ] = $row['line'];
					foreach ( $prepared['items'] as $item ) {
						$pending[ $item['item_id'] ] = ( isset( $pending[ $item['item_id'] ] ) ? $pending[ $item['item_id'] ] : 0 ) + $item['qty'];
					}
					$job['pending_qty'][ $data['order_id'] ] = $pending;
				}
			}

			$job['rows'][ $i ]['valid']  = empty( $errors );
			$job['rows'][ $i ]['errors'] = array_values( array_unique( $errors ) );
		}

		$job['validated'] = $end;
		$done             = $end >= $total;
		if ( $done ) {
			$job['status']      = 'preview';
			$job['pending_qty'] = array();
			$job['seen']        = array();
		}
		self::save_job( $job );

		$response = array(
			'validated' => $end,
			'total'     => $total,
			'done'      => $done,
		);

		if ( $done ) {
			$response['summary'] = $this->get_summary( $job );
			$response['rows']    = $this->get_preview_rows( $job );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Convert a CSV row to service arguments.
	 *
	 * @param array $data Row data.
	 * @return array
	 */
	private function row_to_args( $data ) {
		return array(
			'carrier'         => $data['carrier'],
			'tracking_number' => $data['tracking_number'],
			'ship_date'       => $data['ship_date'],
			'items'           => $data['items'],
			'status'          => $data['status'],
		);
	}

	/**
	 * Read the optional notify_customer CSV cell.
	 *
	 * @param array $data Row data.
	 * @return bool|null Null when the cell is empty or missing.
	 */
	private function row_notify( $data ) {
		$value = isset( $data['notify_customer'] ) ? strtolower( trim( (string) $data['notify_customer'] ) ) : '';
		if ( '' === $value ) {
			return null;
		}
		return in_array( $value, array( '1', 'yes', 'y', 'true', 'on' ), true );
	}

	/**
	 * Count summary of a job.
	 *
	 * @param array $job Job.
	 * @return array
	 */
	private function get_summary( $job ) {
		$valid = 0;
		foreach ( $job['rows'] as $row ) {
			if ( true === $row['valid'] ) {
				++$valid;
			}
		}
		return array(
			'total'   => count( $job['rows'] ),
			'valid'   => $valid,
			'invalid' => count( $job['rows'] ) - $valid,
		);
	}

	/**
	 * Rows for the preview table (invalid rows first so problems are visible).
	 *
	 * @param array $job Job.
	 * @return array
	 */
	private function get_preview_rows( $job ) {
		$invalid = array();
		$valid   = array();
		foreach ( $job['rows'] as $row ) {
			$out = array_merge(
				array( 'line' => $row['line'] ),
				$row['data'],
				array(
					'valid'  => (bool) $row['valid'],
					'errors' => $row['errors'],
				)
			);
			if ( $row['valid'] ) {
				$valid[] = $out;
			} else {
				$invalid[] = $out;
			}
		}
		return array_slice( array_merge( $invalid, $valid ), 0, 1000 );
	}

	/**
	 * AJAX: queue the import of valid rows.
	 */
	public function ajax_start() {
		$this->verify_ajax_request();

		$job = self::get_job( $this->get_request_job_id() );
		if ( ! $job || 'preview' !== $job['status'] ) {
			wp_send_json_error( array( 'message' => __( 'Import job not found or already started.', 'track-orders-for-woocommerce' ) ) );
		}

		$notify        = isset( $_POST['notify'] ) ? sanitize_key( wp_unslash( $_POST['notify'] ) ) : 'settings'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in verify_ajax_request().
		$job['notify'] = in_array( $notify, array( 'settings', 'none' ), true ) ? $notify : 'settings';

		$queue = array();
		foreach ( $job['rows'] as $index => $row ) {
			if ( true === $row['valid'] ) {
				$queue[] = $index;
			}
		}
		if ( empty( $queue ) ) {
			wp_send_json_error( array( 'message' => __( 'There are no valid rows to import.', 'track-orders-for-woocommerce' ) ) );
		}

		$job['queue']      = array_chunk( $queue, self::BATCH_SIZE );
		$job['next_batch'] = 0;
		$job['status']     = 'queued';
		self::save_job( $job );

		$this->enqueue_batch( $job['id'], 0 );

		wp_send_json_success( $this->get_progress( $job ) );
	}

	/**
	 * Enqueue one batch. Batches are chained (each batch enqueues the next one) so rows of the
	 * same order are applied in file order and the job option is never written concurrently.
	 *
	 * @param string $job_id Job id.
	 * @param int    $batch Batch index.
	 */
	private function enqueue_batch( $job_id, $batch ) {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action(
				self::BATCH_HOOK,
				array(
					'job_id' => $job_id,
					'batch'  => (int) $batch,
				),
				self::GROUP
			);
		}
	}

	/**
	 * Action Scheduler callback: process one batch.
	 *
	 * @param string $job_id Job id.
	 * @param int    $batch Batch index.
	 */
	public function process_batch( $job_id, $batch = 0 ) {
		$job = self::get_job( $job_id );
		if ( ! $job || ! in_array( $job['status'], array( 'queued', 'processing' ), true ) || (int) $batch !== (int) $job['next_batch'] || ! isset( $job['queue'][ $batch ] ) ) {
			return; // Unknown, finished, or stale/duplicated action.
		}

		$job['status'] = 'processing';

		if ( function_exists( 'wc_set_time_limit' ) ) {
			wc_set_time_limit( 0 );
		}

		// Run as the user who started the import (order notes, capabilities in third-party hooks).
		$previous_user = get_current_user_id();
		if ( ! empty( $job['user_id'] ) ) {
			wp_set_current_user( (int) $job['user_id'] );
		}

		foreach ( $job['queue'][ $batch ] as $index ) {
			$row = $job['rows'][ $index ];
			if ( '' !== (string) $row['result'] ) {
				continue; // Already handled by an earlier, interrupted run of this batch.
			}

			// "Do not send" in the import screen wins, otherwise the row's notify_customer cell (empty = default).
			$notify = 'none' === $job['notify'] ? false : $this->row_notify( $row['data'] );
			$args   = array_merge(
				$this->row_to_args( $row['data'] ),
				array(
					'source'          => 'csv',
					'notify_customer' => $notify,
				)
			);

			try {
				$result = Track_Orders_For_Woocommerce_Tracking_Service::add_tracking( (int) $row['data']['order_id'], $args );
			} catch ( \Throwable $e ) {
				$result = new WP_Error( 'wps_tofw_import_exception', $e->getMessage() );
			}

			++$job['processed'];
			if ( is_wp_error( $result ) ) {
				++$job['failed'];
				$job['rows'][ $index ]['result'] = 'failed';
				$job['rows'][ $index ]['errors'] = $result->get_error_messages();
			} else {
				++$job['succeeded'];
				$job['rows'][ $index ]['result'] = 'success';
			}

			// Persist after each row so a fatal error / timeout does not lose or repeat finished rows.
			self::save_job( $job );
		}

		wp_set_current_user( $previous_user );

		$job['next_batch'] = (int) $batch + 1;
		if ( $job['next_batch'] >= count( $job['queue'] ) ) {
			$job['status']   = 'completed';
			$job['finished'] = time();
		}
		self::save_job( $job );

		if ( 'completed' !== $job['status'] ) {
			$this->enqueue_batch( $job['id'], $job['next_batch'] );
		} else {
			/**
			 * Fires when a CSV tracking import has finished.
			 *
			 * @since 1.3.0
			 * @param array $job Job data.
			 */
			do_action( 'wps_tofw_tracking_import_completed', $job );
		}
	}

	/**
	 * Progress payload.
	 *
	 * @param array $job Job.
	 * @return array
	 */
	private function get_progress( $job ) {
		$total = 0;
		foreach ( $job['queue'] as $chunk ) {
			$total += count( $chunk );
		}
		return array(
			'status'    => $job['status'],
			'total'     => $total,
			'processed' => (int) $job['processed'],
			'succeeded' => (int) $job['succeeded'],
			'failed'    => (int) $job['failed'],
			'invalid'   => $this->get_summary( $job )['invalid'],
			'done'      => 'completed' === $job['status'],
		);
	}

	/**
	 * AJAX: import progress (polled by the screen).
	 */
	public function ajax_progress() {
		$this->verify_ajax_request();

		$job = self::get_job( $this->get_request_job_id() );
		if ( ! $job ) {
			wp_send_json_error( array( 'message' => __( 'Import job not found.', 'track-orders-for-woocommerce' ) ) );
		}

		// Fallback when Action Scheduler is not available: process one batch per poll.
		if ( ! function_exists( 'as_enqueue_async_action' ) && in_array( $job['status'], array( 'queued', 'processing' ), true ) ) {
			$this->process_batch( $job['id'], $job['next_batch'] );
			$job = self::get_job( $job['id'] );
		}

		wp_send_json_success( $this->get_progress( $job ) );
	}

	/**
	 * Action Scheduler callback: delete an old job.
	 *
	 * @param string $job_id Job id.
	 */
	public function cleanup_job( $job_id ) {
		$job = self::get_job( $job_id );
		if ( $job && in_array( $job['status'], array( 'queued', 'processing' ), true ) ) {
			$created = isset( $job['created'] ) ? (int) $job['created'] : 0;
			if ( $created > time() - self::MAX_JOB_AGE_DAYS * DAY_IN_SECONDS ) {
				// Still running: try again later.
				if ( function_exists( 'as_schedule_single_action' ) ) {
					as_schedule_single_action( time() + DAY_IN_SECONDS, self::CLEANUP_HOOK, array( 'job_id' => $job_id ), self::GROUP );
				}
				return;
			}

			// Stuck (e.g. a batch died with a fatal error): give up instead of rescheduling forever.
			$job['status']   = 'failed';
			$job['finished'] = time();
			self::save_job( $job );

			/**
			 * Fires when a CSV tracking import is abandoned because it did not finish in time.
			 *
			 * @since 1.3.0
			 * @param array $job Job data.
			 */
			do_action( 'wps_tofw_tracking_import_failed', $job );
		}
		delete_option( self::JOB_OPTION_PREFIX . preg_replace( '/[^a-z0-9]/', '', (string) $job_id ) );
	}

	/**
	 * Escape a CSV cell against spreadsheet formula injection.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function csv_safe( $value ) {
		$value = (string) $value;
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $value;
		}
		return $value;
	}

	/**
	 * Admin-post: download the sample CSV.
	 */
	public function download_sample() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::NONCE ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'track-orders-for-woocommerce' ), '', array( 'response' => 403 ) );
		}

		$rows = array(
			self::COLUMNS,
			array( '1234', 'fedex', '794612345678', gmdate( 'Y-m-d' ), '', 'completed', '' ),
			array( '1235', 'UPS', '1Z999AA10123456784', gmdate( 'Y-m-d' ), 'TSHIRT-RED-M:2|MUG-01:1', '', 'yes' ),
			array( '1235', 'UPS', '1Z999AA10123456785', gmdate( 'Y-m-d' ), 'TSHIRT-RED-M:1', 'completed', 'yes' ),
			array( '1236', 'DHL', 'JD014600006281230704', '', '#57:1', '', 'no' ),
		);

		$this->output_csv( 'tracking-import-sample.csv', $rows );
	}

	/**
	 * Admin-post: download the error report for a job.
	 */
	public function download_report() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), self::NONCE ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'track-orders-for-woocommerce' ), '', array( 'response' => 403 ) );
		}

		$job = self::get_job( $this->get_request_job_id() );
		if ( ! $job ) {
			wp_die( esc_html__( 'Import job not found or expired.', 'track-orders-for-woocommerce' ) );
		}

		$rows = array( array_merge( array( 'line' ), self::COLUMNS, array( 'error' ) ) );
		foreach ( $job['rows'] as $row ) {
			if ( true === $row['valid'] && 'failed' !== $row['result'] ) {
				continue;
			}
			$out = array( $row['line'] );
			foreach ( self::COLUMNS as $column ) {
				$out[] = isset( $row['data'][ $column ] ) ? $row['data'][ $column ] : '';
			}
			$out[]  = implode( ' ', $row['errors'] );
			$rows[] = $out;
		}

		$this->output_csv( 'tracking-import-errors-' . gmdate( 'Ymd-His' ) . '.csv', $rows );
	}

	/**
	 * Stream a CSV download and exit.
	 *
	 * @param string $filename File name.
	 * @param array  $rows Rows.
	 */
	private function output_csv( $filename, $rows ) {
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		foreach ( $rows as $row ) {
			fputcsv( $out, array_map( array( __CLASS__, 'csv_safe' ), $row ), ',', '"', '\\' );
		}
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}
}
