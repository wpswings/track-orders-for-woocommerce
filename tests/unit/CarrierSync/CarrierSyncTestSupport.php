<?php
/**
 * Shared doubles for the carrier status sync unit tests (WPS-8397).
 *
 * Not a test file (no "Test.php" suffix): loaded with require_once by the
 * CarrierSync tests. Declares only what the bootstrap does not provide.
 *
 * @package Track_Orders_For_Woocommerce
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Commenting

namespace {

	if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
		define( 'MINUTE_IN_SECONDS', 60 );
	}
	if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
		define( 'HOUR_IN_SECONDS', 3600 );
	}

	if ( ! class_exists( 'WP_Error' ) ) {
		/**
		 * Minimal WP_Error (same shape as the one in tests/TrackingImportTest.php).
		 */
		class WP_Error {
			public $errors = array();

			public function __construct( $code = '', $message = '' ) {
				if ( $code ) {
					$this->add( $code, $message );
				}
			}

			public function add( $code, $message, $data = '' ) {
				$this->errors[ $code ][] = $message;
			}

			public function has_errors() {
				return ! empty( $this->errors );
			}

			public function get_error_message() {
				$first = reset( $this->errors );
				return $first ? $first[0] : '';
			}
		}
	}

	require_once TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/class-track-orders-for-woocommerce-carrier-sync.php';
}

namespace Tests\Unit\CarrierSync {

	/**
	 * Scripted tracking provider: returns queued results and records every lookup.
	 */
	class FakeProvider implements \Wps_Tofw_Tracking_Provider {

		public $id;
		public $configured;
		public $carriers;
		public $results;
		public $calls = array();

		/**
		 * @param string     $id         Provider id.
		 * @param bool       $configured Whether is_configured() is true.
		 * @param array|null $carriers   Supported carrier slugs, null for all.
		 * @param array      $results    Results returned in order (array or WP_Error); the last one repeats.
		 */
		public function __construct( $id, $configured = true, $carriers = null, $results = array() ) {
			$this->id         = $id;
			$this->configured = $configured;
			$this->carriers   = $carriers;
			$this->results    = $results;
		}

		public function get_id() {
			return $this->id;
		}

		public function get_label() {
			return strtoupper( $this->id );
		}

		public function is_configured() {
			return $this->configured;
		}

		public function supports( $carrier ) {
			return null === $this->carriers || in_array( $carrier, $this->carriers, true );
		}

		public function get_status( $carrier, $tracking_number ) {
			$this->calls[] = array( $carrier, $tracking_number );
			if ( count( $this->results ) > 1 ) {
				return array_shift( $this->results );
			}
			return $this->results ? $this->results[0] : array( 'status' => 'in_transit', 'events' => array() );
		}
	}

	/**
	 * Tiny $wpdb double for the usage counter row lock (INSERT IGNORE / DELETE on wp_options).
	 */
	class FakeWpdb {

		public $options = 'wp_options';
		public $locks   = array();
		public $log     = array();

		public function prepare( $query ) {
			$args = func_get_args();
			array_shift( $args );
			return array(
				'sql'  => $query,
				'args' => $args,
			);
		}

		public function query( $prepared ) {
			$this->log[] = 'query';
			$name        = $prepared['args'][0];
			if ( isset( $this->locks[ $name ] ) ) {
				return 0;
			}
			$this->locks[ $name ] = $prepared['args'][1];
			return 1;
		}

		public function get_var( $prepared ) {
			$name = $prepared['args'][0];
			return isset( $this->locks[ $name ] ) ? $this->locks[ $name ] : null;
		}

		public function delete( $table, $where ) {
			$this->log[] = 'delete';
			unset( $this->locks[ $where['option_name'] ] );
			return 1;
		}
	}

	/**
	 * Base class: WordPress stubs used by the carrier sync code (options, transients, dates, $wpdb).
	 */
	abstract class CarrierSyncTestCase extends \Tests\Unit\TestCase {

		/** @var array In-memory transients. */
		protected $transients = array();

		/** @var FakeWpdb */
		protected $wpdb;

		/** @var mixed Previous global $wpdb. */
		private $previous_wpdb;

		protected function set_up() {
			parent::set_up();
			$this->stubCommonWpFunctions();
			$this->setOptions(
				array(
					'wps_tofw_is_plugin_enable'    => 'on',
					'wps_tofw_carrier_sync_enable' => 'on',
				)
			);

			$this->transients = array();
			$transients       = &$this->transients;
			\Brain\Monkey\Functions\when( 'get_transient' )->alias(
				static function ( $key ) use ( &$transients ) {
					return isset( $transients[ $key ] ) ? $transients[ $key ] : false;
				}
			);
			\Brain\Monkey\Functions\when( 'set_transient' )->alias(
				static function ( $key, $value ) use ( &$transients ) {
					$transients[ $key ] = $value;
					return true;
				}
			);
			\Brain\Monkey\Functions\when( 'delete_transient' )->alias(
				static function ( $key ) use ( &$transients ) {
					unset( $transients[ $key ] );
					return true;
				}
			);
			\Brain\Monkey\Functions\when( 'wp_date' )->alias(
				static function ( $format, $timestamp = null ) {
					return gmdate( $format, null === $timestamp ? time() : $timestamp );
				}
			);
			\Brain\Monkey\Functions\when( 'wp_cache_delete' )->justReturn( true );
			\Brain\Monkey\Functions\when( 'number_format_i18n' )->alias(
				static function ( $number ) {
					return number_format( (float) $number );
				}
			);

			global $wpdb;
			$this->previous_wpdb = isset( $wpdb ) ? $wpdb : null;
			$this->wpdb          = new FakeWpdb();
			$wpdb                = $this->wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		protected function tear_down() {
			global $wpdb;
			$wpdb = $this->previous_wpdb; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			parent::tear_down();
		}

		/**
		 * Usage option value for the current month.
		 *
		 * @param int $count Lookups used.
		 * @return array
		 */
		protected function usage( $count ) {
			return array(
				'period' => gmdate( 'Y-m' ),
				'count'  => $count,
			);
		}

		/**
		 * Replace the registered providers.
		 *
		 * @param array $providers id => provider.
		 */
		protected function useProviders( array $providers ) {
			\Brain\Monkey\Filters\expectApplied( 'wps_tofw_carrier_sync_providers' )->andReturn( $providers );
		}
	}

	/**
	 * Code of a WP_Error (works with both WP_Error doubles).
	 *
	 * @param \WP_Error $error Error.
	 * @return string
	 */
	function error_code( $error ) {
		$codes = array_keys( $error->errors );
		return $codes ? (string) $codes[0] : '';
	}
}
