<?php
/**
 * Tests for the bulk tracking import helpers (pure functions only).
 *
 * @package Track_Orders_For_Woocommerce
 * @since   1.3.0
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Commenting
namespace {
	if ( ! defined( 'MB_IN_BYTES' ) ) {
		define( 'MB_IN_BYTES', 1048576 );
	}

	if ( ! function_exists( 'absint' ) ) {
		function absint( $maybeint ) {
			return abs( (int) $maybeint );
		}
	}

	if ( ! function_exists( 'is_wp_error' ) ) {
		function is_wp_error( $thing ) {
			return $thing instanceof WP_Error;
		}
	}

	if ( ! class_exists( 'WP_Error' ) ) {
		/**
		 * Minimal WP_Error stand-in for unit tests.
		 */
		class WP_Error {
			/**
			 * Errors.
			 *
			 * @var array
			 */
			public $errors = array();

			/**
			 * Constructor.
			 *
			 * @param string $code Code.
			 * @param string $message Message.
			 */
			public function __construct( $code = '', $message = '' ) {
				if ( $code ) {
					$this->add( $code, $message );
				}
			}

			/**
			 * Add error.
			 *
			 * @param string $code Code.
			 * @param string $message Message.
			 * @param mixed  $data Data.
			 */
			public function add( $code, $message, $data = '' ) {
				$this->errors[ $code ][] = $message;
			}

			/**
			 * Has errors.
			 *
			 * @return bool
			 */
			public function has_errors() {
				return ! empty( $this->errors );
			}

			/**
			 * First message.
			 *
			 * @return string
			 */
			public function get_error_message() {
				$first = reset( $this->errors );
				return $first ? $first[0] : '';
			}
		}
	}

	require_once TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/class-track-orders-for-woocommerce-tracking-service.php';
	require_once TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/class-track-orders-for-woocommerce-tracking-import.php';
}

namespace Tests {

	use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

	/**
	 * Tracking import / service helper tests.
	 */
	class TrackingImportTest extends PolyfillTestCase {

		/**
		 * Items string parsing.
		 */
		public function testParseItemsString() {
			$items = \Track_Orders_For_Woocommerce_Tracking_Service::parse_items( 'TSHIRT-RED-M:2| MUG-01:1 |#57:1|BAG' );
			$this->assertSame(
				array(
					array( 'type' => 'sku', 'ref' => 'TSHIRT-RED-M', 'qty' => 2 ),
					array( 'type' => 'sku', 'ref' => 'MUG-01', 'qty' => 1 ),
					array( 'type' => 'item_id', 'ref' => 57, 'qty' => 1 ),
					array( 'type' => 'sku', 'ref' => 'BAG', 'qty' => null ),
				),
				$items
			);
			$this->assertSame( array(), \Track_Orders_For_Woocommerce_Tracking_Service::parse_items( '' ) );
		}

		/**
		 * Invalid item strings.
		 */
		public function testParseItemsInvalid() {
			$this->assertInstanceOf( \WP_Error::class, \Track_Orders_For_Woocommerce_Tracking_Service::parse_items( 'SKU:0' ) );
			$this->assertInstanceOf( \WP_Error::class, \Track_Orders_For_Woocommerce_Tracking_Service::parse_items( 'SKU:abc' ) );
			$this->assertInstanceOf( \WP_Error::class, \Track_Orders_For_Woocommerce_Tracking_Service::parse_items( '#x:1' ) );
			$this->assertInstanceOf( \WP_Error::class, \Track_Orders_For_Woocommerce_Tracking_Service::parse_items( ':2' ) );
		}

		/**
		 * Items as array (REST).
		 */
		public function testParseItemsArray() {
			$items = \Track_Orders_For_Woocommerce_Tracking_Service::parse_items(
				array(
					array( 'sku' => 'A', 'qty' => 3 ),
					array( 'item_id' => '12' ),
				)
			);
			$this->assertSame(
				array(
					array( 'type' => 'sku', 'ref' => 'A', 'qty' => 3 ),
					array( 'type' => 'item_id', 'ref' => 12, 'qty' => null ),
				),
				$items
			);
			$this->assertInstanceOf( \WP_Error::class, \Track_Orders_For_Woocommerce_Tracking_Service::parse_items( array( array( 'qty' => 1 ) ) ) );
			$this->assertInstanceOf( \WP_Error::class, \Track_Orders_For_Woocommerce_Tracking_Service::parse_items( array( array( 'sku' => 'A', 'qty' => 1.5 ) ) ) );
		}

		/**
		 * Date normalization.
		 */
		public function testNormalizeDate() {
			$this->assertSame( '2026-11-23', \Track_Orders_For_Woocommerce_Tracking_Service::normalize_date( '2026-11-23' ) );
			$this->assertSame( '2026-11-23', \Track_Orders_For_Woocommerce_Tracking_Service::normalize_date( '2026/11/23' ) );
			$this->assertSame( '2026-11-23', \Track_Orders_For_Woocommerce_Tracking_Service::normalize_date( '2026-11-23 10:15' ) );
			$this->assertFalse( \Track_Orders_For_Woocommerce_Tracking_Service::normalize_date( '2026-02-30' ) );
			$this->assertFalse( \Track_Orders_For_Woocommerce_Tracking_Service::normalize_date( '23/11/2026' ) );
			$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', \Track_Orders_For_Woocommerce_Tracking_Service::normalize_date( '' ) );
		}

		/**
		 * Header mapping with aliases and missing columns.
		 */
		public function testMapHeader() {
			$map = \Track_Orders_For_Woocommerce_Tracking_Import::map_header( array( "\xEF\xBB\xBFOrder ID", 'Tracking Number', 'Carrier', 'items' ) );
			$this->assertSame( array( 'order_id' => 0, 'tracking_number' => 1, 'carrier' => 2, 'items' => 3 ), $map );

			$this->assertInstanceOf( \WP_Error::class, \Track_Orders_For_Woocommerce_Tracking_Import::map_header( array( 'order_id', 'carrier' ) ) );
		}

		/**
		 * CSV parsing of a 1,000 row file (semicolon delimited, blank lines skipped).
		 */
		public function testParseCsvFile() {
			$path  = tempnam( sys_get_temp_dir(), 'tofw' );
			$lines = array( 'order_id;carrier;tracking_number;ship_date;items;status' );
			for ( $i = 1; $i <= 1000; $i++ ) {
				$lines[] = '#' . ( 1000 + $i ) . ';UPS;1Z' . $i . ';2026-11-23;"SKU-A:1|SKU-B:2";completed';
			}
			$lines[] = '';
			file_put_contents( $path, implode( "\n", $lines ) );

			$rows = \Track_Orders_For_Woocommerce_Tracking_Import::parse_csv_file( $path, 5000 );
			$this->assertCount( 1000, $rows );
			$this->assertSame( '1001', $rows[0]['data']['order_id'] );
			$this->assertSame( 'SKU-A:1|SKU-B:2', $rows[0]['data']['items'] );
			$this->assertSame( 2, $rows[0]['line'] );

			$this->assertInstanceOf( \WP_Error::class, \Track_Orders_For_Woocommerce_Tracking_Import::parse_csv_file( $path, 10 ) );
			unlink( $path );
		}

		/**
		 * CSV formula injection guard.
		 */
		public function testCsvSafe() {
			$this->assertSame( "'=SUM(A1)", \Track_Orders_For_Woocommerce_Tracking_Import::csv_safe( '=SUM(A1)' ) );
			$this->assertSame( 'ABC', \Track_Orders_For_Woocommerce_Tracking_Import::csv_safe( 'ABC' ) );
		}
	}
}
