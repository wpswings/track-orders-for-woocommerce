<?php
/**
 * WPS-8396: CSV reading, header mapping and the formula-injection guard of the error report.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\TrackingImport;

require_once __DIR__ . '/fixtures.php';

use Tests\Unit\TestCase;

/**
 * Tests for Track_Orders_For_Woocommerce_Tracking_Import CSV helpers.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CsvParsingTest extends TestCase {

	/**
	 * Temporary files created by a test.
	 *
	 * @var string[]
	 */
	private $files = array();

	/**
	 * Load the class.
	 */
	protected function set_up() {
		parent::set_up();
		$this->stubCommonWpFunctions();
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-tracking-service.php' );
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-tracking-import.php' );
	}

	/**
	 * Remove temporary files.
	 */
	protected function tear_down() {
		foreach ( $this->files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
		parent::tear_down();
	}

	/**
	 * Write a CSV to a temporary file.
	 *
	 * @param string $contents CSV text.
	 * @return string Path.
	 */
	private function csv( $contents ) {
		$path          = tempnam( sys_get_temp_dir(), 'tofw8396' );
		$this->files[] = $path;
		file_put_contents( $path, $contents );
		return $path;
	}

	/**
	 * All seven documented columns are read; "#" prefixes are stripped from order ids and
	 * cells are sanitized (tags removed).
	 */
	public function test_reads_all_columns() {
		$rows = \Track_Orders_For_Woocommerce_Tracking_Import::parse_csv_file(
			$this->csv(
				"order_id,carrier,tracking_number,ship_date,items,status,notify_customer\n" .
				"#1234,UPS,<b>1Z999</b>,2026-11-20,\"SKU-A:1|#57:2\",completed,no\n"
			),
			10
		);

		$this->assertSame(
			array(
				array(
					'line'   => 2,
					'data'   => array(
						'order_id'        => '1234',
						'carrier'         => 'UPS',
						'tracking_number' => '1Z999',
						'ship_date'       => '2026-11-20',
						'items'           => 'SKU-A:1|#57:2',
						'status'          => 'completed',
						'notify_customer' => 'no',
					),
					'valid'  => null,
					'errors' => array(),
					'result' => '',
				),
			),
			$rows
		);
	}

	/**
	 * Column order does not matter, aliases are accepted, unknown columns are ignored and
	 * optional columns default to an empty string.
	 */
	public function test_header_aliases_and_optional_columns() {
		$rows = \Track_Orders_For_Woocommerce_Tracking_Import::parse_csv_file(
			$this->csv( "Tracking No,Foo,Courier,Order Number,Send Email\r\nABC,bar,DHL,77,yes\r\n" ),
			10
		);
		$this->assertSame( '77', $rows[0]['data']['order_id'] );
		$this->assertSame( 'DHL', $rows[0]['data']['carrier'] );
		$this->assertSame( 'ABC', $rows[0]['data']['tracking_number'] );
		$this->assertSame( 'yes', $rows[0]['data']['notify_customer'] );
		$this->assertSame( '', $rows[0]['data']['ship_date'] );
		$this->assertSame( '', $rows[0]['data']['status'] );
	}

	/**
	 * Missing required columns, empty files and header-only files are rejected with clear errors.
	 */
	public function test_rejects_invalid_files() {
		$error = \Track_Orders_For_Woocommerce_Tracking_Import::parse_csv_file( $this->csv( "order_id,carrier\n1,UPS\n" ), 10 );
		$this->assertInstanceOf( \WP_Error::class, $error );
		$this->assertSame( 'wps_tofw_csv_missing_columns', $error->get_error_code() );
		$this->assertStringContainsString( 'tracking_number', $error->get_error_message() );

		$this->assertSame( 'wps_tofw_csv_empty', \Track_Orders_For_Woocommerce_Tracking_Import::parse_csv_file( $this->csv( '' ), 10 )->get_error_code() );
		$this->assertSame( 'wps_tofw_csv_no_rows', \Track_Orders_For_Woocommerce_Tracking_Import::parse_csv_file( $this->csv( "order_id,carrier,tracking_number\n\n\n" ), 10 )->get_error_code() );
		$this->assertSame(
			'wps_tofw_csv_too_many_rows',
			\Track_Orders_For_Woocommerce_Tracking_Import::parse_csv_file( $this->csv( "order_id,carrier,tracking_number\n1,a,b\n2,a,b\n3,a,b\n" ), 2 )->get_error_code()
		);
	}

	/**
	 * Line numbers point at the file line (header = 1), also after blank lines.
	 */
	public function test_line_numbers_skip_blank_lines() {
		$rows = \Track_Orders_For_Woocommerce_Tracking_Import::parse_csv_file(
			$this->csv( "order_id,carrier,tracking_number\n1,UPS,A\n\n2,UPS,B\n" ),
			10
		);
		$this->assertSame( array( 2, 4 ), array_column( $rows, 'line' ) );
	}

	/**
	 * Every spreadsheet formula trigger is neutralized in the error report; other values are untouched.
	 */
	public function test_csv_safe_neutralizes_formula_triggers() {
		foreach ( array( '=1+1', '+1', '-1', '@SUM(A1)', "\tX", "\rX" ) as $value ) {
			$this->assertSame( "'" . $value, \Track_Orders_For_Woocommerce_Tracking_Import::csv_safe( $value ), $value );
		}
		$this->assertSame( '', \Track_Orders_For_Woocommerce_Tracking_Import::csv_safe( '' ) );
		$this->assertSame( '1234', \Track_Orders_For_Woocommerce_Tracking_Import::csv_safe( 1234 ) );
		$this->assertSame( 'a=b', \Track_Orders_For_Woocommerce_Tracking_Import::csv_safe( 'a=b' ) );
	}

	/**
	 * The documented columns are in the canonical order used by the sample and error report.
	 */
	public function test_columns_constant() {
		$this->assertSame(
			array( 'order_id', 'carrier', 'tracking_number', 'ship_date', 'items', 'status', 'notify_customer' ),
			\Track_Orders_For_Woocommerce_Tracking_Import::COLUMNS
		);
		$this->assertSame( 50, \Track_Orders_For_Woocommerce_Tracking_Import::BATCH_SIZE );
	}
}
