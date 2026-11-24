<?php
/**
 * Fixtures for the WPS-8396 bulk tracking import / REST tests.
 *
 * WordPress core classes used by the code under test (WP_Error, WP_REST_Response,
 * WP_REST_Server) are not available without WordPress. The legacy tests/TrackingImportTest.php
 * declares a minimal WP_Error without merge_from()/get_error_messages(), so the test classes
 * using these fixtures run in separate processes, where this complete stand-in is declared first.
 *
 * @package Track_Orders_For_Woocommerce
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, Squiz.Commenting

namespace {

	if ( ! defined( 'MB_IN_BYTES' ) ) {
		define( 'MB_IN_BYTES', 1048576 );
	}

	if ( ! class_exists( 'WP_Error' ) ) {
		/**
		 * WP_Error stand-in with the API used by the tracking service.
		 */
		class WP_Error {
			public $errors     = array();
			public $error_data = array();

			public function __construct( $code = '', $message = '', $data = '' ) {
				if ( '' !== $code ) {
					$this->add( $code, $message, $data );
				}
			}

			public function add( $code, $message, $data = '' ) {
				$this->errors[ $code ][] = $message;
				if ( '' !== $data ) {
					$this->error_data[ $code ] = $data;
				}
			}

			public function get_error_codes() {
				return array_keys( $this->errors );
			}

			public function get_error_code() {
				$codes = $this->get_error_codes();
				return empty( $codes ) ? '' : $codes[0];
			}

			public function get_error_messages( $code = '' ) {
				if ( '' === $code ) {
					$all = array();
					foreach ( $this->errors as $messages ) {
						$all = array_merge( $all, $messages );
					}
					return $all;
				}
				return isset( $this->errors[ $code ] ) ? $this->errors[ $code ] : array();
			}

			public function get_error_message( $code = '' ) {
				if ( '' === $code ) {
					$code = $this->get_error_code();
				}
				$messages = $this->get_error_messages( $code );
				return empty( $messages ) ? '' : $messages[0];
			}

			public function get_error_data( $code = '' ) {
				if ( '' === $code ) {
					$code = $this->get_error_code();
				}
				return isset( $this->error_data[ $code ] ) ? $this->error_data[ $code ] : null;
			}

			public function has_errors() {
				return ! empty( $this->errors );
			}

			public function merge_from( WP_Error $error ) {
				foreach ( $error->errors as $code => $messages ) {
					foreach ( $messages as $message ) {
						$this->add( $code, $message );
					}
				}
				foreach ( $error->error_data as $code => $data ) {
					$this->error_data[ $code ] = $data;
				}
			}
		}
	}

	if ( ! class_exists( 'WP_REST_Response' ) ) {
		/**
		 * WP_REST_Response stand-in.
		 */
		class WP_REST_Response {
			public $data;
			public $status;

			public function __construct( $data = null, $status = 200 ) {
				$this->data   = $data;
				$this->status = $status;
			}

			public function get_data() {
				return $this->data;
			}

			public function get_status() {
				return $this->status;
			}
		}
	}

	if ( ! class_exists( 'WP_REST_Server' ) ) {
		/**
		 * WP_REST_Server constants.
		 */
		class WP_REST_Server {
			const READABLE  = 'GET';
			const CREATABLE = 'POST';
			const EDITABLE  = 'POST, PUT, PATCH';
			const DELETABLE = 'DELETE';
		}
	}
}

namespace Tests\Unit\TrackingImport {

	use Brain\Monkey\Functions;
	use Mockery;

	/**
	 * Thrown by the wp_send_json_* stubs so AJAX handlers stop like they do in WordPress.
	 */
	class JsonResponse extends \Exception {
		public $success;
		public $data;
		public $status;

		public function __construct( $success, $data, $status = null ) {
			parent::__construct( $success ? 'json_success' : 'json_error' );
			$this->success = $success;
			$this->data    = $data;
			$this->status  = $status;
		}
	}

	/**
	 * Thrown by the wp_die stub.
	 */
	class WpDie extends \Exception {
		public $args;

		public function __construct( $message, $args = array() ) {
			parent::__construct( (string) $message );
			$this->args = $args;
		}
	}

	/**
	 * Helpers shared by the WPS-8396 tests.
	 */
	trait TrackingFixtures {

		/**
		 * Mail sent through the stubbed WC()->mailer(): list of [ to, subject, message ].
		 *
		 * @var array
		 */
		protected $sent_mails = array();

		/**
		 * Order notes added to mocked orders: [ order_id => [ note, ... ] ].
		 *
		 * @var array
		 */
		protected $order_notes = array();

		/**
		 * Status changes of mocked orders: [ order_id => [ status, ... ] ].
		 *
		 * @var array
		 */
		protected $status_changes = array();

		/**
		 * Stubs used by the tracking service in every test.
		 */
		protected function stubTrackingEnvironment() {
			$this->stubCommonWpFunctions();
			$this->setOptions();
			$this->stubWcGetOrder();
			$this->sent_mails     = array();
			$this->order_notes    = array();
			$this->status_changes = array();

			$counter = 0;
			Functions\stubs(
				array(
					'wp_strip_all_tags'         => static function ( $text ) {
						return trim( strip_tags( (string) $text ) );
					},
					'current_time'              => '2026-11-24',
					'get_current_user_id'       => 1,
					'wc_get_order_statuses'     => array(
						'wc-pending'    => 'Pending payment',
						'wc-processing' => 'Processing',
						'wc-on-hold'    => 'On hold',
						'wc-completed'  => 'Completed',
						'wc-cancelled'  => 'Cancelled',
					),
					'wc_get_order_status_name'  => static function ( $status ) {
						return ucfirst( (string) $status );
					},
					'wp_generate_password'      => static function () use ( &$counter ) {
						++$counter;
						return 'ID' . str_pad( (string) $counter, 8, '0', STR_PAD_LEFT );
					},
					'rest_sanitize_boolean'     => static function ( $value ) {
						if ( is_string( $value ) ) {
							return in_array( strtolower( $value ), array( '1', 'true', 'yes', 'on' ), true );
						}
						return (bool) $value;
					},
					'rest_authorization_required_code' => 401,
				)
			);

			$mails  = &$this->sent_mails;
			$mailer = Mockery::mock( 'WC_Emails' );
			$mailer->shouldReceive( 'send' )->andReturnUsing(
				static function ( $to, $subject, $message ) use ( &$mails ) {
					$mails[] = array( $to, $subject, $message );
					return true;
				}
			);
			$wc = Mockery::mock( 'WooCommerce' );
			$wc->shouldReceive( 'mailer' )->andReturn( $mailer );
			Functions\when( 'WC' )->justReturn( $wc );
		}

		/**
		 * Make wp_send_json_success()/wp_send_json_error()/wp_die() throw instead of exiting.
		 */
		protected function stubJsonResponses() {
			Functions\when( 'wp_send_json_success' )->alias(
				static function ( $data = null, $status = null ) {
					throw new JsonResponse( true, $data, $status );
				}
			);
			Functions\when( 'wp_send_json_error' )->alias(
				static function ( $data = null, $status = null ) {
					throw new JsonResponse( false, $data, $status );
				}
			);
			Functions\when( 'wp_die' )->alias(
				static function ( $message = '', $title = '', $args = array() ) {
					throw new WpDie( $message, $args );
				}
			);
		}

		/**
		 * Mocked order line item.
		 *
		 * @param int    $id   Item id.
		 * @param string $sku  Product SKU ('' for no product).
		 * @param int    $qty  Quantity.
		 * @param string $name Item name.
		 * @return \Mockery\MockInterface
		 */
		protected function mockLineItem( $id, $sku, $qty, $name = '' ) {
			$product = null;
			if ( '' !== $sku ) {
				$product = Mockery::mock( 'WC_Product' );
				$product->shouldReceive( 'get_sku' )->andReturn( $sku );
			}
			$item = Mockery::mock( 'WC_Order_Item_Product' );
			$item->shouldReceive( 'get_id' )->andReturn( $id );
			$item->shouldReceive( 'get_product' )->andReturn( $product );
			$item->shouldReceive( 'get_quantity' )->andReturn( $qty );
			$item->shouldReceive( 'get_name' )->andReturn( '' !== $name ? $name : 'Item ' . $id );
			return $item;
		}

		/**
		 * A mocked WC_Order that supports everything add_tracking() calls.
		 *
		 * @param array $props See WpTestHelpers::mockOrder(), plus 'items' (list of line item mocks).
		 * @return \Mockery\MockInterface|\WC_Order
		 */
		protected function mockTrackableOrder( array $props = array() ) {
			$items = array();
			if ( isset( $props['items'] ) ) {
				foreach ( $props['items'] as $item ) {
					$items[ $item->get_id() ] = $item;
				}
				unset( $props['items'] );
			}

			$id     = isset( $props['id'] ) ? (int) $props['id'] : 1;
			$status = isset( $props['status'] ) ? $props['status'] : 'processing';

			$this->order_notes[ $id ]    = array();
			$this->status_changes[ $id ] = array();
			$notes                       = &$this->order_notes[ $id ];
			$changes                     = &$this->status_changes[ $id ];

			$props = array_merge(
				array(
					'get_type'       => 'shop_order',
					'get_items'      => $items,
					'has_status'     => static function ( $check ) use ( &$status ) {
						return in_array( $status, (array) $check, true );
					},
					'add_order_note' => static function ( $note ) use ( &$notes ) {
						$notes[] = $note;
						return count( $notes );
					},
					'update_status'  => static function ( $new_status ) use ( &$status, &$changes ) {
						$status    = $new_status;
						$changes[] = $new_status;
						return true;
					},
				),
				$props
			);

			$order = $this->mockOrder( $props );
			$order->shouldReceive( 'get_status' )->andReturnUsing(
				static function () use ( &$status ) {
					return $status;
				}
			);
			return $order;
		}
	}
}
