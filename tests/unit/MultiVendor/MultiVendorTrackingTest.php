<?php
/**
 * WPS-8401: multi-vendor shipment tracking (ownership, partial quantities, per-vendor
 * storage with legacy fallback, legacy carrier mirroring, admin tracking protection,
 * customer view).
 *
 * The marketplace integrations (Dokan, WCFM, WC Vendors) are the real plugin classes;
 * only the marketplace plugins' own functions are stubbed.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\MultiVendor;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery;
use Tests\Unit\MultiVendor\Support\JsonResponse;
use Tests\Unit\TestCase;
use Track_Orders_For_Woocommerce_Multi_Vendor as Manager;

require_once __DIR__ . '/Support/JsonResponse.php';

/**
 * Tests for Track_Orders_For_Woocommerce_Multi_Vendor and the vendor integrations.
 */
class MultiVendorTrackingTest extends TestCase {

	const VENDOR_A = 11;
	const VENDOR_B = 12;

	/**
	 * Current user id for the stubs.
	 *
	 * @var int
	 */
	private $current_user = 0;

	/**
	 * Whether the posted nonce is valid.
	 *
	 * @var bool
	 */
	private $nonce_valid = true;

	/**
	 * check_ajax_referer() calls.
	 *
	 * @var array
	 */
	private $nonce_calls = array();

	/**
	 * check_ajax_referer() stub: dies (-1, 403) like WordPress on an invalid nonce.
	 *
	 * @param string $action Action.
	 * @param string $field  Field.
	 * @return int
	 */
	public function checkNonce( $action, $field ) {
		$this->nonce_calls[] = array( $action, $field );
		if ( ! $this->nonce_valid ) {
			throw new JsonResponse( false, -1, 403 );
		}
		return 1;
	}

	/**
	 * Notes per order id: list of array( note, is_customer_note ).
	 *
	 * @var array
	 */
	private $notes = array();

	/**
	 * Load the classes and stub WordPress / marketplace functions.
	 */
	protected function set_up() {
		parent::set_up();
		foreach ( array( 'vendor-integration', 'multi-vendor', 'vendor-dokan', 'vendor-wcfm', 'vendor-wcvendors' ) as $file ) {
			$this->requirePluginFile( 'includes/multi-vendor/class-track-orders-for-woocommerce-' . $file . '.php' );
		}
		$this->stubCommonWpFunctions();
		$this->current_user = self::VENDOR_A;
		$this->notes        = array();
		$_POST              = array();

		$test = $this;
		Functions\when( 'absint' )->alias(
			static function ( $value ) {
				return abs( (int) $value );
			}
		);
		Functions\when( 'map_deep' )->alias(
			static function ( $value, $callback ) {
				return is_array( $value ) ? array_map( $callback, $value ) : $callback( $value );
			}
		);
		Functions\when( 'is_user_logged_in' )->alias(
			static function () use ( $test ) {
				return $test->currentUser() > 0;
			}
		);
		Functions\when( 'get_current_user_id' )->alias(
			static function () use ( $test ) {
				return $test->currentUser();
			}
		);
		Functions\when( 'wp_date' )->justReturn( '2026-11-24' );
		Functions\when( 'wp_generate_uuid4' )->alias(
			static function () {
				static $n = 0;
				return 'shipment-' . ( ++$n );
			}
		);
		Functions\when( 'get_userdata' )->justReturn( false );
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
		Functions\when( 'wp_list_pluck' )->alias(
			static function ( $list, $field ) {
				return array_map(
					static function ( $row ) use ( $field ) {
						return $row[ $field ];
					},
					$list
				);
			}
		);
		Functions\when( 'wp_enqueue_style' )->justReturn( true );
		Functions\when( 'wc_date_format' )->justReturn( 'Y-m-d' );
		Functions\when( 'date_i18n' )->alias(
			static function ( $format, $timestamp ) {
				return gmdate( $format, $timestamp );
			}
		);
		$this->nonce_valid = true;
		$this->nonce_calls = array();
		Functions\when( 'check_ajax_referer' )->alias(
			static function ( $action, $field ) use ( $test ) {
				return $test->checkNonce( $action, $field );
			}
		);
		Functions\when( 'wp_send_json_error' )->alias(
			static function ( $data = null, $status = null ) {
				throw new JsonResponse( false, $data, $status );
			}
		);
		Functions\when( 'wp_send_json_success' )->alias(
			static function ( $data = null, $status = null ) {
				throw new JsonResponse( true, $data, $status );
			}
		);

		// WCFM: vendors are users 11 and 12, items carry the vendor in _vendor_id.
		Functions\when( 'wcfm_is_vendor' )->alias(
			static function () use ( $test ) {
				return in_array( $test->currentUser(), array( self::VENDOR_A, self::VENDOR_B ), true );
			}
		);
		Functions\when( 'wcfm_get_vendor_id_by_post' )->justReturn( 0 );
		Functions\when( 'wcfm_get_vendor_store_name' )->alias(
			static function ( $vendor_id ) {
				return array( self::VENDOR_A => 'Alpha <b>Store</b>', self::VENDOR_B => 'Beta Store' )[ $vendor_id ];
			}
		);
	}

	/**
	 * Tear down.
	 */
	protected function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Current user id (read by stubs).
	 *
	 * @return int
	 */
	public function currentUser() {
		return $this->current_user;
	}

	/**
	 * Line item double.
	 *
	 * @param int    $product_id Product id.
	 * @param string $name       Name.
	 * @param int    $qty        Quantity.
	 * @param int    $vendor_id  WCFM vendor (_vendor_id meta).
	 * @return Mockery\MockInterface
	 */
	private function item( $product_id, $name, $qty, $vendor_id ) {
		$item = Mockery::mock( 'WC_Order_Item_Product' );
		$item->shouldReceive( 'get_product_id' )->andReturn( $product_id );
		$item->shouldReceive( 'get_name' )->andReturn( $name );
		$item->shouldReceive( 'get_quantity' )->andReturn( $qty );
		$item->shouldReceive( 'get_meta' )->with( '_vendor_id', true )->andReturn( $vendor_id );
		return $item;
	}

	/**
	 * Marketplace order: item 101 (vendor A, qty 3), item 102 (vendor A, qty 1), item 201 (vendor B, qty 2).
	 *
	 * @param int   $id       Order id.
	 * @param array $meta     Meta.
	 * @param array $refunded item_id => refunded qty (negative, as WooCommerce returns it).
	 * @return Mockery\MockInterface
	 */
	private function marketplaceOrder( $id = 500, array $meta = array(), array $refunded = array() ) {
		$items = array(
			101 => $this->item( 1, 'Alpha Mug', 3, self::VENDOR_A ),
			102 => $this->item( 2, 'Alpha Cap', 1, self::VENDOR_A ),
			201 => $this->item( 3, 'Beta Book', 2, self::VENDOR_B ),
		);
		$test  = $this;
		$notes = &$this->notes;
		return $this->mockOrder(
			array(
				'id'                        => $id,
				'meta'                      => $meta,
				'get_items'                 => static function () use ( $items ) {
					return $items;
				},
				'get_item'                  => static function ( $item_id ) use ( $items ) {
					return isset( $items[ $item_id ] ) ? $items[ $item_id ] : false;
				},
				'get_qty_refunded_for_item' => static function ( $item_id ) use ( $refunded ) {
					return isset( $refunded[ $item_id ] ) ? $refunded[ $item_id ] : 0;
				},
				'get_meta_data'             => static function () use ( $test, $id ) {
					$out = array();
					foreach ( (array) $test->liveMeta( $id ) as $key => $value ) {
						$out[] = new MetaRow( $key, $value );
					}
					return $out;
				},
				'add_order_note'            => static function ( $note, $is_customer = 0 ) use ( &$notes, $id ) {
					$notes[ $id ][] = array( $note, $is_customer );
					return 1;
				},
			)
		);
	}

	/**
	 * Live meta of a mocked order.
	 *
	 * @param int $id Order id.
	 * @return array
	 */
	public function liveMeta( $id ) {
		return $this->getOrderMeta( $id );
	}

	/**
	 * Manager with the given integrations active.
	 *
	 * @param array $keys Integration keys.
	 * @return Manager
	 */
	private function manager( array $keys = array( 'wcfm' ) ) {
		$manager = new Manager( '1.3.0' );
		$classes = array(
			'dokan'     => 'Track_Orders_For_Woocommerce_Vendor_Dokan',
			'wcfm'      => 'Track_Orders_For_Woocommerce_Vendor_Wcfm',
			'wcvendors' => 'Track_Orders_For_Woocommerce_Vendor_Wcvendors',
		);
		$active  = array();
		foreach ( $keys as $key ) {
			$active[ $key ] = new $classes[ $key ]( $manager );
		}
		$prop = new \ReflectionProperty( Manager::class, 'integrations' );
		$prop->setAccessible( true );
		$prop->setValue( $manager, $active );
		return $manager;
	}

	/**
	 * Call the save AJAX handler with $post and return the JSON response.
	 *
	 * @param Manager $manager Manager.
	 * @param array   $post    POST data (merged over defaults).
	 * @return JsonResponse
	 */
	private function ajaxSave( $manager, array $post ) {
		$_POST = array_merge(
			array(
				'nonce'           => 'NONCE',
				'marketplace'     => 'wcfm',
				'order_id'        => '500',
				'carrier'         => 'fedex',
				'tracking_number' => 'FX-1',
				'date'            => '2026-11-20',
				'items'           => array( '101' => '1' ),
			),
			$post
		);
		try {
			$manager->wps_tofw_save_vendor_shipment();
		} catch ( JsonResponse $response ) {
			return $response;
		}
		$this->fail( 'The AJAX handler did not send a JSON response.' );
	}

	/**
	 * A stored shipment.
	 *
	 * @param string $id      Id.
	 * @param string $carrier Carrier.
	 * @param string $number  Tracking number.
	 * @param array  $items   Items.
	 * @param int    $created Created.
	 * @return array
	 */
	private function shipment( $id, $carrier, $number, array $items, $created = 1 ) {
		return array(
			'id'              => $id,
			'carrier'         => $carrier,
			'carrier_name'    => strtoupper( $carrier ),
			'tracking_number' => $number,
			'tracking_url'    => '',
			'date'            => '2026-11-20',
			'items'           => $items,
			'created'         => $created,
		);
	}

	/*
	 * Ownership.
	 */

	public function test_wcfm_vendor_owns_only_orders_with_its_items() {
		$order = $this->marketplaceOrder();
		$wcfm  = new \Track_Orders_For_Woocommerce_Vendor_Wcfm( new Manager( '1' ) );

		$this->assertTrue( $wcfm->vendor_owns_order( $order, self::VENDOR_A ) );
		$this->assertSame( array( 101, 102 ), array_keys( $wcfm->get_vendor_items( $order, self::VENDOR_A ) ) );
		$this->assertSame( array( 201 ), array_keys( $wcfm->get_vendor_items( $order, self::VENDOR_B ) ) );
		$this->assertFalse( $wcfm->vendor_owns_order( $order, 99 ) );
		$this->assertFalse( $wcfm->vendor_owns_order( $order, 0 ) );
	}

	public function test_wcfm_current_vendor_requires_a_logged_in_vendor() {
		$wcfm = new \Track_Orders_For_Woocommerce_Vendor_Wcfm( new Manager( '1' ) );

		$this->assertSame( self::VENDOR_A, $wcfm->get_current_vendor_id() );
		$this->current_user = 5; // Customer.
		$this->assertSame( 0, $wcfm->get_current_vendor_id() );
		$this->current_user = 0;
		$this->assertSame( 0, $wcfm->get_current_vendor_id() );
	}

	public function test_dokan_vendor_owns_only_its_sub_order() {
		Functions\when( 'dokan_get_seller_id_by_order' )->alias(
			static function ( $order_id ) {
				return array( 601 => self::VENDOR_A, 602 => array( self::VENDOR_A, self::VENDOR_B ) )[ $order_id ];
			}
		);
		$dokan = new \Track_Orders_For_Woocommerce_Vendor_Dokan( new Manager( '1' ) );
		$sub   = $this->mockOrder( array( 'id' => 601 ) );
		$multi = $this->mockOrder( array( 'id' => 602 ) );

		$this->assertTrue( $dokan->vendor_owns_order( $sub, self::VENDOR_A ) );
		$this->assertFalse( $dokan->vendor_owns_order( $sub, self::VENDOR_B ) );
		$this->assertFalse( $dokan->vendor_owns_order( $multi, self::VENDOR_A ), 'Multi-seller parent order is not owned by one vendor.' );
		$this->assertSame( array(), $dokan->get_vendor_items( $sub, self::VENDOR_B ) );
	}

	public function test_ajax_rejects_a_vendor_that_does_not_own_the_order() {
		$this->marketplaceOrder( 500 );
		$this->current_user = 13; // Another WCFM vendor with no item in the order.
		Functions\when( 'wcfm_is_vendor' )->justReturn( true );

		$response = $this->ajaxSave( $this->manager(), array() );

		$this->assertFalse( $response->success );
		$this->assertSame( 403, $response->status );
		$this->assertSame( 'You are not allowed to update this order.', $response->data['message'] );
		$this->assertNull( $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_13' ) );
	}

	public function test_ajax_rejects_customers_and_guests() {
		$this->marketplaceOrder( 500 );
		$manager = $this->manager();

		foreach ( array( 5, 0 ) as $user ) {
			$this->current_user = $user;
			$response           = $this->ajaxSave( $manager, array() );
			$this->assertSame( 403, $response->status );
		}
		$this->assertSame( array(), $this->getOrderMeta( 500 ) );
	}

	public function test_ajax_checks_the_nonce_first() {
		$this->marketplaceOrder( 500 );
		$this->nonce_valid = false;

		$response = $this->ajaxSave( $this->manager(), array( 'nonce' => 'forged' ) );

		$this->assertSame( -1, $response->data );
		$this->assertSame( array( array( Manager::NONCE_ACTION, 'nonce' ) ), $this->nonce_calls );
		$this->assertSame( array(), $this->getOrderMeta( 500 ) );
	}

	public function test_ajax_rejects_unknown_order_or_marketplace() {
		$this->marketplaceOrder( 500 );
		$manager = $this->manager();

		$this->assertSame( 400, $this->ajaxSave( $manager, array( 'order_id' => '999' ) )->status );
		$this->assertSame( 400, $this->ajaxSave( $manager, array( 'marketplace' => 'dokan' ) )->status, 'Inactive marketplace.' );
	}

	public function test_vendor_cannot_ship_items_of_another_vendor() {
		$this->marketplaceOrder( 500 );

		$response = $this->ajaxSave( $this->manager(), array( 'items' => array( '201' => '1' ) ) );

		$this->assertSame( 400, $response->status );
		$this->assertSame( 'The shipped quantity exceeds the quantity left to ship.', $response->data['message'] );
		$this->assertNull( $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_' . self::VENDOR_A ) );
	}

	/*
	 * Quantities.
	 */

	public function test_remaining_quantities_account_for_shipments_and_refunds() {
		$this->marketplaceOrder(
			500,
			array( 'wps_tofw_vendor_shipments_' . self::VENDOR_A => array( 's1' => $this->shipment( 's1', 'fedex', 'FX-0', array( 101 => 1 ) ) ) ),
			array( 101 => -1 )
		);
		$manager = $this->manager();
		$wcfm    = $manager->get_integration( 'wcfm' );

		$rows = $manager->get_vendor_item_quantities( $wcfm, wc_get_order( 500 ), self::VENDOR_A );

		$this->assertSame( array( 'name' => 'Alpha Mug', 'ordered' => 2, 'shipped' => 1, 'remaining' => 1 ), $rows[101], '3 ordered, 1 refunded, 1 shipped.' );
		$this->assertSame( array( 'name' => 'Alpha Cap', 'ordered' => 1, 'shipped' => 0, 'remaining' => 1 ), $rows[102] );
		$this->assertArrayNotHasKey( 201, $rows );
	}

	public function test_partial_shipments_cannot_exceed_the_remaining_quantity() {
		$this->marketplaceOrder( 500 );
		$manager = $this->manager();

		$this->assertTrue( $this->ajaxSave( $manager, array( 'items' => array( '101' => '2' ) ) )->success );

		$response = $this->ajaxSave( $manager, array( 'tracking_number' => 'FX-2', 'items' => array( '101' => '2' ) ) );
		$this->assertSame( 400, $response->status, 'Only 1 of 3 left.' );

		$this->assertTrue( $this->ajaxSave( $manager, array( 'tracking_number' => 'FX-3', 'items' => array( '101' => '1', '102' => '1' ) ) )->success );
		$this->assertCount( 2, $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_' . self::VENDOR_A ) );

		$html = $this->ajaxSave( $manager, array( 'tracking_number' => 'FX-4', 'items' => array( '101' => '1' ) ) );
		$this->assertSame( 400, $html->status, 'Everything shipped.' );
	}

	/**
	 * Invalid input is rejected with a message.
	 *
	 * @dataProvider invalid_posts
	 *
	 * @param array  $post    POST overrides.
	 * @param string $message Expected message.
	 */
	public function test_invalid_input_is_rejected( array $post, $message ) {
		$this->marketplaceOrder( 500 );

		$response = $this->ajaxSave( $this->manager(), $post );

		$this->assertSame( 400, $response->status );
		$this->assertSame( $message, $response->data['message'] );
		$this->assertNull( $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_' . self::VENDOR_A ) );
	}

	/**
	 * Invalid POST data.
	 *
	 * @return array
	 */
	public function invalid_posts() {
		return array(
			'unknown carrier'      => array( array( 'carrier' => 'pigeon' ), 'Please choose a carrier.' ),
			'empty tracking'       => array( array( 'tracking_number' => '  ' ), 'Enter a tracking number.' ),
			'other without name'   => array( array( 'carrier' => 'other' ), 'Enter the carrier name.' ),
			'no items'             => array( array( 'items' => array() ), 'Select at least one item to ship.' ),
			'zero quantity'        => array( array( 'items' => array( '101' => '0' ) ), 'Select at least one item to ship.' ),
			'more than ordered'    => array( array( 'items' => array( '101' => '4' ) ), 'The shipped quantity exceeds the quantity left to ship.' ),
			'unknown item'         => array( array( 'items' => array( '999' => '1' ) ), 'The shipped quantity exceeds the quantity left to ship.' ),
		);
	}

	/*
	 * Storage.
	 */

	public function test_shipment_is_stored_under_the_vendor_key() {
		$this->marketplaceOrder( 500 );

		$response = $this->ajaxSave( $this->manager(), array( 'tracking_number' => 'FX 1/2', 'date' => 'not-a-date', 'items' => array( '101' => '2' ) ) );

		$this->assertTrue( $response->success );
		$this->assertSame( 'Shipment saved.', $response->data['message'] );
		$shipments = $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_' . self::VENDOR_A );
		$this->assertCount( 1, $shipments );
		$shipment = reset( $shipments );
		$this->assertSame( key( $shipments ), $shipment['id'] );
		$this->assertSame( 'fedex', $shipment['carrier'] );
		$this->assertSame( 'FedEx', $shipment['carrier_name'] );
		$this->assertSame( 'FX 1/2', $shipment['tracking_number'] );
		$this->assertSame( 'https://www.fedex.com/fedextrack/?trknbr=FX%201%2F2', $shipment['tracking_url'] );
		$this->assertSame( '2026-11-24', $shipment['date'], 'Invalid date falls back to today.' );
		$this->assertSame( array( 101 => 2 ), $shipment['items'] );
		$this->assertNull( $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments' ), 'Legacy combined key is not written.' );
	}

	public function test_other_carrier_uses_the_custom_name_and_url() {
		$this->marketplaceOrder( 500 );

		$this->ajaxSave(
			$this->manager(),
			array( 'carrier' => 'other', 'carrier_name' => 'Speedy', 'tracking_url' => 'https://speedy.example/t/9' )
		);

		$shipment = current( $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_' . self::VENDOR_A ) );
		$this->assertSame( 'Speedy', $shipment['carrier_name'] );
		$this->assertSame( 'https://speedy.example/t/9', $shipment['tracking_url'] );
	}

	public function test_vendors_of_the_same_order_do_not_overwrite_each_other() {
		$this->marketplaceOrder( 500 );
		$manager = $this->manager();

		$this->ajaxSave( $manager, array( 'tracking_number' => 'A-1' ) );
		$this->current_user = self::VENDOR_B;
		$this->ajaxSave( $manager, array( 'tracking_number' => 'B-1', 'carrier' => 'ups', 'items' => array( '201' => '2' ) ) );

		$a = $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_' . self::VENDOR_A );
		$b = $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_' . self::VENDOR_B );
		$this->assertSame( 'A-1', current( $a )['tracking_number'] );
		$this->assertSame( 'B-1', current( $b )['tracking_number'] );
		$all = $manager->get_all_shipments( wc_get_order( 500 ) );
		$this->assertSame( array( self::VENDOR_A, self::VENDOR_B ), array_keys( $all ) );
	}

	public function test_legacy_combined_meta_is_still_read_and_own_key_wins() {
		$order = $this->marketplaceOrder(
			500,
			array(
				'wps_tofw_vendor_shipments'                      => array(
					self::VENDOR_A => array( 'old' => $this->shipment( 'old', 'usps', 'OLD-A', array( 101 => 1 ) ) ),
					self::VENDOR_B => array( 'b' => $this->shipment( 'b', 'ups', 'LEGACY-B', array( 201 => 1 ) ) ),
					99             => array(),
				),
				'wps_tofw_vendor_shipments_' . self::VENDOR_A => array( 'new' => $this->shipment( 'new', 'fedex', 'NEW-A', array( 101 => 1 ) ) ),
			)
		);
		$manager = $this->manager();

		$all = $manager->get_all_shipments( $order );

		$this->assertSame( array( self::VENDOR_A, self::VENDOR_B ), array_keys( $all ), 'Empty vendors dropped.' );
		$this->assertSame( array( 'new' ), array_keys( $all[ self::VENDOR_A ] ) );
		$this->assertSame( 'LEGACY-B', $manager->get_vendor_shipments( $order, self::VENDOR_B )['b']['tracking_number'] );
	}

	public function test_saving_moves_the_vendor_out_of_the_legacy_meta() {
		$this->marketplaceOrder(
			500,
			array(
				'wps_tofw_vendor_shipments' => array(
					self::VENDOR_A => array( 'old' => $this->shipment( 'old', 'usps', 'OLD-A', array( 101 => 1 ) ) ),
					self::VENDOR_B => array( 'b' => $this->shipment( 'b', 'ups', 'LEGACY-B', array( 201 => 1 ) ) ),
				),
			)
		);
		$manager = $this->manager();

		$this->ajaxSave( $manager, array( 'tracking_number' => 'NEW-A' ) );

		$own = $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_' . self::VENDOR_A );
		$this->assertSame( array( 'OLD-A', 'NEW-A' ), array_values( wp_list_pluck_values( $own, 'tracking_number' ) ), 'Legacy shipments kept, moved to the own key.' );
		$this->assertSame( array( self::VENDOR_B ), array_keys( $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments' ) ) );

		$this->current_user = self::VENDOR_B;
		$this->ajaxSave( $manager, array( 'tracking_number' => 'NEW-B', 'carrier' => 'ups', 'items' => array( '201' => '1' ) ) );
		$this->assertNull( $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments' ), 'Empty legacy meta is deleted.' );
	}

	public function test_delete_shipment() {
		$this->marketplaceOrder(
			500,
			array( 'wps_tofw_vendor_shipments_' . self::VENDOR_A => array( 's1' => $this->shipment( 's1', 'dhl', 'D-1', array( 101 => 1 ) ) ) )
		);
		$manager = $this->manager();
		$_POST   = array( 'nonce' => 'N', 'marketplace' => 'wcfm', 'order_id' => '500', 'shipment_id' => 'nope' );

		try {
			$manager->wps_tofw_delete_vendor_shipment();
		} catch ( JsonResponse $response ) {
			$this->assertSame( 404, $response->status );
		}

		$_POST['shipment_id'] = 's1';
		try {
			$manager->wps_tofw_delete_vendor_shipment();
		} catch ( JsonResponse $response ) {
			$this->assertTrue( $response->success );
		}
		$this->assertNull( $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_' . self::VENDOR_A ) );
	}

	public function test_another_vendor_cannot_delete_a_shipment() {
		$this->marketplaceOrder(
			500,
			array( 'wps_tofw_vendor_shipments_' . self::VENDOR_A => array( 's1' => $this->shipment( 's1', 'dhl', 'D-1', array( 101 => 1 ) ) ) )
		);
		$this->current_user = self::VENDOR_B;
		$_POST              = array( 'nonce' => 'N', 'marketplace' => 'wcfm', 'order_id' => '500', 'shipment_id' => 's1' );

		try {
			$this->manager()->wps_tofw_delete_vendor_shipment();
			$this->fail( 'No response.' );
		} catch ( JsonResponse $response ) {
			$this->assertSame( 404, $response->status, 'Vendor B only sees its own shipments.' );
		}
		$this->assertCount( 1, $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_' . self::VENDOR_A ) );
	}

	/*
	 * Legacy carrier mirroring.
	 */

	/**
	 * Only carriers the base plugin understands are mirrored.
	 *
	 * @dataProvider mirror_carriers
	 *
	 * @param string $carrier  Carrier.
	 * @param bool   $mirrored Expected.
	 */
	public function test_single_wcfm_shipment_is_mirrored_only_for_supported_carriers( $carrier, $mirrored ) {
		$this->marketplaceOrder( 500 );

		$this->ajaxSave( $this->manager(), array( 'carrier' => $carrier, 'tracking_number' => 'T-1' ) );

		if ( $mirrored ) {
			$this->assertSame( $carrier, $this->getOrderMeta( 500, 'wps_tofw_selected_shipping_service' ) );
			$this->assertSame( 'T-1', $this->getOrderMeta( 500, 'wps_tofw_package_tracking_number' ) );
			$this->assertSame( 'yes', $this->getOrderMeta( 500, Manager::MIRRORED_META_KEY ) );
		} else {
			$this->assertNull( $this->getOrderMeta( 500, 'wps_tofw_package_tracking_number' ) );
			$this->assertNull( $this->getOrderMeta( 500, Manager::MIRRORED_META_KEY ) );
		}
	}

	/**
	 * Carriers.
	 *
	 * @return array
	 */
	public function mirror_carriers() {
		return array(
			'fedex'       => array( 'fedex', true ),
			'usps'        => array( 'usps', true ),
			'canada_post' => array( 'canada_post', true ),
			'ups'         => array( 'ups', false ),
			'dhl'         => array( 'dhl', false ),
		);
	}

	public function test_mirrored_carriers_are_filterable() {
		Filters\expectApplied( 'wps_tofw_vendor_legacy_carrier_keys' )->andReturn( array( 'dhl' ) );
		$this->marketplaceOrder( 500 );

		$this->ajaxSave( $this->manager(), array( 'carrier' => 'dhl', 'tracking_number' => 'D-1' ) );

		$this->assertSame( 'D-1', $this->getOrderMeta( 500, 'wps_tofw_package_tracking_number' ) );
	}

	public function test_admin_tracking_is_never_overwritten() {
		$this->marketplaceOrder(
			500,
			array(
				'wps_tofw_selected_shipping_service' => 'usps',
				'wps_tofw_package_tracking_number'   => 'ADMIN-1',
			)
		);

		$this->ajaxSave( $this->manager(), array( 'carrier' => 'fedex', 'tracking_number' => 'V-1' ) );

		$this->assertSame( 'ADMIN-1', $this->getOrderMeta( 500, 'wps_tofw_package_tracking_number' ) );
		$this->assertSame( 'usps', $this->getOrderMeta( 500, 'wps_tofw_selected_shipping_service' ) );
		$this->assertNull( $this->getOrderMeta( 500, Manager::MIRRORED_META_KEY ) );
	}

	public function test_wcfm_parent_order_with_several_shipments_clears_the_mirror() {
		$this->marketplaceOrder( 500 );
		$manager = $this->manager();

		$this->ajaxSave( $manager, array( 'carrier' => 'fedex', 'tracking_number' => 'A-1' ) );
		$this->assertSame( 'A-1', $this->getOrderMeta( 500, 'wps_tofw_package_tracking_number' ) );

		$this->current_user = self::VENDOR_B;
		$this->ajaxSave( $manager, array( 'carrier' => 'usps', 'tracking_number' => 'B-1', 'items' => array( '201' => '1' ) ) );

		$this->assertSame( '', $this->getOrderMeta( 500, 'wps_tofw_package_tracking_number' ), 'Two vendors: no single tracking number.' );
		$this->assertNull( $this->getOrderMeta( 500, Manager::MIRRORED_META_KEY ) );
	}

	public function test_dokan_sub_order_mirrors_the_latest_shipment_and_clears_unsupported() {
		Functions\when( 'dokan_get_seller_id_by_order' )->justReturn( self::VENDOR_A );
		Functions\when( 'dokan_get_current_user_id' )->justReturn( self::VENDOR_A );
		Functions\when( 'dokan_is_user_seller' )->justReturn( true );
		$this->marketplaceOrder( 500 );
		$manager = $this->manager( array( 'dokan' ) );

		$this->ajaxSave( $manager, array( 'marketplace' => 'dokan', 'carrier' => 'fedex', 'tracking_number' => 'D-1' ) );
		$this->ajaxSave( $manager, array( 'marketplace' => 'dokan', 'carrier' => 'usps', 'tracking_number' => 'D-2', 'items' => array( '102' => '1' ) ) );
		$this->assertSame( 'D-2', $this->getOrderMeta( 500, 'wps_tofw_package_tracking_number' ), 'Latest shipment mirrored.' );
		$this->assertSame( 'usps', $this->getOrderMeta( 500, 'wps_tofw_selected_shipping_service' ) );

		$this->ajaxSave( $manager, array( 'marketplace' => 'dokan', 'carrier' => 'dhl', 'tracking_number' => 'D-3', 'items' => array( '201' => '1' ) ) );
		$this->assertSame( '', $this->getOrderMeta( 500, 'wps_tofw_package_tracking_number' ), 'Latest carrier unsupported: mirror cleared.' );
		$this->assertNull( $this->getOrderMeta( 500, Manager::MIRRORED_META_KEY ) );
	}

	public function test_wc_vendors_never_mirrors() {
		if ( ! class_exists( 'WCV_Vendors' ) ) {
			eval( 'class WCV_Vendors { public static function is_vendor( $id ) { return in_array( (int) $id, array( 11, 12 ), true ); } public static function get_vendor_from_product( $id ) { return in_array( (int) $id, array( 1, 2 ), true ) ? 11 : 12; } }' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
		}
		$this->marketplaceOrder( 500 );

		$response = $this->ajaxSave( $this->manager( array( 'wcvendors' ) ), array( 'marketplace' => 'wcvendors', 'carrier' => 'fedex' ) );

		$this->assertTrue( $response->success );
		$this->assertCount( 1, $this->getOrderMeta( 500, 'wps_tofw_vendor_shipments_' . self::VENDOR_A ) );
		$this->assertNull( $this->getOrderMeta( 500, 'wps_tofw_package_tracking_number' ) );
	}

	/*
	 * Admin save releases the mirror flag.
	 */

	/**
	 * Admin save of the order meta box.
	 *
	 * @param string $tracking Tracking number posted.
	 * @param bool   $nonce_ok Nonce valid.
	 * @param bool   $can      Capability.
	 */
	private function adminSave( $tracking, $nonce_ok = true, $can = true ) {
		Functions\when( 'wp_verify_nonce' )->justReturn( $nonce_ok ? 1 : false );
		Functions\when( 'current_user_can' )->justReturn( $can );
		$_POST = array(
			'wps_tofw_selected_shipping_services_nonce_name' => 'N',
			'wps_tofw_tracking_number'                       => $tracking,
		);
		$this->manager()->wps_tofw_release_mirrored_tracking( 500 );
		$_POST = array();
	}

	/**
	 * Order with a mirrored vendor tracking number.
	 */
	private function mirroredOrder() {
		return $this->marketplaceOrder(
			500,
			array(
				'wps_tofw_vendor_shipments_' . self::VENDOR_A => array( 's1' => $this->shipment( 's1', 'fedex', 'V-1', array( 101 => 1 ) ) ),
				'wps_tofw_package_tracking_number'              => 'V-1',
				Manager::MIRRORED_META_KEY                      => 'yes',
			)
		);
	}

	public function test_admin_entering_own_tracking_releases_the_mirror_flag() {
		$this->mirroredOrder();

		$this->adminSave( 'ADMIN-9' );

		$this->assertNull( $this->getOrderMeta( 500, Manager::MIRRORED_META_KEY ) );

		// The free plugin saved the admin number; a later vendor save no longer touches it.
		wc_get_order( 500 )->update_meta_data( 'wps_tofw_package_tracking_number', 'ADMIN-9' );
		$this->ajaxSave( $this->manager(), array( 'carrier' => 'usps', 'tracking_number' => 'V-2', 'items' => array( '102' => '1' ) ) );
		$this->assertSame( 'ADMIN-9', $this->getOrderMeta( 500, 'wps_tofw_package_tracking_number' ) );
	}

	public function test_admin_save_keeping_the_vendor_tracking_keeps_the_flag() {
		$this->mirroredOrder();

		$this->adminSave( 'V-1' );

		$this->assertSame( 'yes', $this->getOrderMeta( 500, Manager::MIRRORED_META_KEY ) );
	}

	public function test_release_needs_nonce_capability_and_a_tracking_number() {
		$this->mirroredOrder();

		$this->adminSave( 'ADMIN-9', false, true );
		$this->assertSame( 'yes', $this->getOrderMeta( 500, Manager::MIRRORED_META_KEY ), 'Invalid nonce.' );

		$this->adminSave( 'ADMIN-9', true, false );
		$this->assertSame( 'yes', $this->getOrderMeta( 500, Manager::MIRRORED_META_KEY ), 'No capability.' );

		$this->adminSave( '' );
		$this->assertSame( 'yes', $this->getOrderMeta( 500, Manager::MIRRORED_META_KEY ), 'Empty tracking.' );
	}

	/*
	 * Vendor dashboards and customer groups per marketplace.
	 */

	public function test_dashboard_form_is_rendered_only_for_the_owning_vendor_once() {
		$this->marketplaceOrder( 520 );
		$manager = Mockery::mock( Manager::class );
		$wcfm    = new \Track_Orders_For_Woocommerce_Vendor_Wcfm( $manager );
		$manager->shouldReceive( 'render_vendor_form' )->once()->with( $wcfm, Mockery::type( 'WC_Order' ), self::VENDOR_A );

		$this->current_user = 13;
		Functions\when( 'wcfm_is_vendor' )->justReturn( true );
		$wcfm->render_dashboard_form( 520 ); // Not the owner: nothing.

		$this->current_user = self::VENDOR_A;
		$wcfm->render_dashboard_form( 520 );
		$wcfm->render_dashboard_form( wc_get_order( 520 ) ); // Second marketplace hook: not rendered twice.
		$wcfm->render_dashboard_form( 999 ); // Unknown order.
	}

	public function test_wcfm_customer_groups_and_store_name_fallback() {
		$order = $this->marketplaceOrder( 521 );
		$wcfm  = new \Track_Orders_For_Woocommerce_Vendor_Wcfm( new Manager( '1' ) );

		$groups = $wcfm->get_customer_groups( $order );
		$this->assertSame( array( self::VENDOR_A, self::VENDOR_B ), wp_list_pluck_values( $groups, 'vendor_id' ) );
		$this->assertSame( $order, $groups[0]['order'], 'WCFM keeps all vendors on the parent order.' );

		Functions\when( 'wcfm_get_vendor_store_name' )->justReturn( '' );
		Functions\when( 'get_userdata' )->justReturn( (object) array( 'display_name' => 'Alpha Owner' ) );
		$this->assertSame( 'Alpha Owner', $wcfm->get_store_name( self::VENDOR_A ) );
	}

	public function test_dokan_customer_groups_use_sub_orders_and_skip_partial_shipment_children() {
		Functions\when( 'dokan_get_seller_id_by_order' )->alias(
			static function ( $order_id ) {
				return array( 530 => array( self::VENDOR_A, self::VENDOR_B ), 531 => self::VENDOR_A, 532 => self::VENDOR_B, 533 => self::VENDOR_A )[ $order_id ];
			}
		);
		$parent = $this->mockOrder( array( 'id' => 530 ) );
		$sub_a  = $this->mockOrder( array( 'id' => 531 ) );
		$sub_b  = $this->mockOrder( array( 'id' => 532 ) );
		$child  = $this->mockOrder( array( 'id' => 533, 'meta' => array( '_wps_is_child_order' => 'yes' ) ) );
		Functions\expect( 'wc_get_orders' )->once()->with( Mockery::subset( array( 'parent' => 530 ) ) )->andReturn( array( $sub_a, $sub_b, $child ) );
		$dokan = new \Track_Orders_For_Woocommerce_Vendor_Dokan( new Manager( '1' ) );

		$groups = $dokan->get_customer_groups( $parent );

		$this->assertSame( array( self::VENDOR_A, self::VENDOR_B ), wp_list_pluck_values( $groups, 'vendor_id' ) );
		$this->assertSame( $sub_a, $groups[0]['order'], 'Dokan shipments live on the vendor sub-order.' );
	}

	public function test_dokan_single_vendor_order_without_sub_orders_is_its_own_group() {
		Functions\when( 'dokan_get_seller_id_by_order' )->justReturn( self::VENDOR_A );
		Functions\when( 'wc_get_orders' )->justReturn( array() );
		$order = $this->mockOrder( array( 'id' => 534 ) );

		$groups = ( new \Track_Orders_For_Woocommerce_Vendor_Dokan( new Manager( '1' ) ) )->get_customer_groups( $order );

		$this->assertSame( array( array( 'vendor_id' => self::VENDOR_A, 'order' => $order ) ), $groups );
	}

	public function test_dokan_current_vendor_must_be_a_seller() {
		Functions\when( 'dokan_get_current_user_id' )->justReturn( self::VENDOR_A );
		Functions\when( 'dokan_is_user_seller' )->justReturn( false );

		$this->assertSame( 0, ( new \Track_Orders_For_Woocommerce_Vendor_Dokan( new Manager( '1' ) ) )->get_current_vendor_id() );
	}

	public function test_wc_vendors_order_action_and_groups() {
		if ( ! class_exists( 'WCV_Vendors' ) ) {
			eval( 'class WCV_Vendors { public static function is_vendor( $id ) { return in_array( (int) $id, array( 11, 12 ), true ); } public static function get_vendor_from_product( $id ) { return in_array( (int) $id, array( 1, 2 ), true ) ? 11 : 12; } }' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
		}
		Functions\when( 'add_query_arg' )->alias(
			static function ( $key, $value ) {
				return '/dashboard/?' . $key . '=' . $value;
			}
		);
		$wcv   = new \Track_Orders_For_Woocommerce_Vendor_Wcvendors( new Manager( '1' ) );
		$order = $this->marketplaceOrder( 540 );

		$actions = $wcv->add_order_action( array( 'view' => array( 'label' => 'View', 'url' => '#' ) ), 540 );
		$this->assertSame( '/dashboard/?wps_tofw_vendor_order=540', $actions['wps_tofw_tracking']['url'] );
		$this->assertSame( array( 'legacy' ), $wcv->add_order_action( array( 'legacy' ), 540 ), 'Unknown action format left alone.' );

		$this->assertSame( array( self::VENDOR_A, self::VENDOR_B ), wp_list_pluck_values( $wcv->get_customer_groups( $order ), 'vendor_id' ) );
		$this->current_user = 5;
		$this->assertSame( 0, $wcv->get_current_vendor_id(), 'Customers are not vendors.' );
	}

	/*
	 * Notes and customer view.
	 */

	public function test_shipment_adds_a_customer_note_with_store_and_tracking() {
		$this->marketplaceOrder( 500 );

		$this->ajaxSave( $this->manager(), array( 'carrier' => 'fedex', 'tracking_number' => 'FX-9' ) );

		$this->assertSame(
			array( array( 'Items from Alpha Store have been shipped with FedEx. Tracking number: FX-9 https://www.fedex.com/fedextrack/?trknbr=FX-9', 1 ) ),
			$this->notes[500]
		);
	}

	public function test_customer_note_can_be_private() {
		Filters\expectApplied( 'wps_tofw_vendor_shipment_notify_customer' )->andReturn( false );
		$this->marketplaceOrder( 500 );

		$this->ajaxSave( $this->manager(), array() );

		$this->assertSame( 0, $this->notes[500][0][1] );
	}

	public function test_customer_sees_shipments_grouped_by_vendor() {
		$order = $this->marketplaceOrder(
			510,
			array(
				'wps_tofw_vendor_shipments_' . self::VENDOR_A => array(
					's1' => array_merge( $this->shipment( 's1', 'fedex', 'FX-77', array( 101 => 2 ) ), array( 'carrier_name' => 'FedEx', 'tracking_url' => 'https://www.fedex.com/fedextrack/?trknbr=FX-77' ) ),
				),
				'wps_tofw_vendor_shipments'                      => array(
					self::VENDOR_B => array( 'b' => $this->shipment( 'b', 'ups', 'UPS-55', array( 201 => 2 ) ) ),
				),
			)
		);

		ob_start();
		$this->manager()->wps_tofw_render_customer_vendor_shipments( 510 );
		$html = ob_get_clean();

		$this->assertStringContainsString( '<section class="wps-tofw-vt-customer">', $html );
		$this->assertSame( 2, substr_count( $html, 'class="wps-tofw-vt-group"' ) );
		$this->assertStringContainsString( '<h3 class="wps-tofw-vt-store">Alpha Store</h3>', $html, 'Store name is stripped of tags.' );
		$this->assertStringContainsString( '<h3 class="wps-tofw-vt-store">Beta Store</h3>', $html );
		$this->assertStringContainsString( '<a href="https://www.fedex.com/fedextrack/?trknbr=FX-77" target="_blank" rel="noopener noreferrer">FX-77</a>', $html );
		$this->assertStringContainsString( 'UPS-55', $html, 'Legacy combined meta is shown.' );
		$this->assertStringContainsString( 'Alpha Mug × 2', $html );
		$this->assertMatchesRegularExpression( '/Awaiting shipment:<\/strong>\s*Alpha Mug × 1, Alpha Cap × 1/', $html );
		$this->assertStringNotContainsString( 'wps-tofw-vt-delete', $html, 'Customers cannot delete.' );

		ob_start();
		$this->manager()->wps_tofw_render_customer_vendor_shipments( $order );
		$this->assertSame( '', ob_get_clean(), 'Rendered once per order and request (template + order details hooks).' );
	}

	public function test_nothing_is_rendered_without_an_active_marketplace() {
		$this->marketplaceOrder( 500, array( 'wps_tofw_vendor_shipments_' . self::VENDOR_A => array( 's1' => $this->shipment( 's1', 'fedex', 'FX', array( 101 => 1 ) ) ) ) );

		ob_start();
		$this->manager( array() )->wps_tofw_render_customer_vendor_shipments( 500 );

		$this->assertSame( '', ob_get_clean() );
	}

	public function test_carriers_include_plugin_services_and_other_last() {
		Filters\expectApplied( 'wps_tofw_add_diffrent_shipping_services' )->andReturn( array( 'Aramex' => 'Aramex', 'fedex' => 'Should not replace' ) );

		$carriers = $this->manager()->get_carriers();

		$this->assertSame( 'FedEx', $carriers['fedex']['name'] );
		$this->assertSame( array( 'name' => 'Aramex', 'url' => '' ), $carriers['aramex'] );
		$this->assertSame( 'other', array_key_last_compat( $carriers ) );
	}
}

/**
 * WC_Meta_Data double.
 */
class MetaRow { // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound

	/**
	 * Data.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Constructor.
	 *
	 * @param string $key   Key.
	 * @param mixed  $value Value.
	 */
	public function __construct( $key, $value ) {
		$this->data = array( 'key' => $key, 'value' => $value );
	}

	/**
	 * Data.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}
}

/**
 * Values of a field in a list of arrays.
 *
 * @param array  $rows  Rows.
 * @param string $field Field.
 * @return array
 */
function wp_list_pluck_values( array $rows, $field ) {
	return array_map(
		static function ( $row ) use ( $field ) {
			return $row[ $field ];
		},
		$rows
	);
}

/**
 * array_key_last() for PHP 7.2.
 *
 * @param array $array Array.
 * @return mixed
 */
function array_key_last_compat( array $array ) {
	$keys = array_keys( $array );
	return end( $keys );
}
