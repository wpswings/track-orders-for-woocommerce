<?php
/**
 * WPS-8396: "add tracking to order" service used by the CSV import and the REST API.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\TrackingImport;

require_once __DIR__ . '/fixtures.php';

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Tests\Unit\TestCase;

/**
 * Tests for Track_Orders_For_Woocommerce_Tracking_Service.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TrackingServiceTest extends TestCase {

	use TrackingFixtures;

	/**
	 * Service class.
	 *
	 * @var string
	 */
	const SERVICE = '\Track_Orders_For_Woocommerce_Tracking_Service';

	/**
	 * Load the service and stub WordPress.
	 */
	protected function set_up() {
		parent::set_up();
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-tracking-service.php' );
		$this->stubTrackingEnvironment();
	}

	/**
	 * Fixture sanity: the complete WP_Error stand-in is the one in use.
	 */
	public function test_fixture_wp_error_supports_merge() {
		$this->assertTrue( method_exists( 'WP_Error', 'merge_from' ) );
	}

	/**
	 * A valid request is normalized: carrier matched to a known service, date and status normalized.
	 */
	public function test_prepare_normalizes_valid_input() {
		Filters\expectApplied( 'wps_tofw_add_diffrent_shipping_services' )->andReturn( array( 'ups' => 'UPS', 'dhl' => 'DHL Express' ) );
		$order = $this->mockTrackableOrder( array( 'id' => 10 ) );

		$data = \Track_Orders_For_Woocommerce_Tracking_Service::prepare(
			10,
			array(
				'carrier'         => 'dhl express',
				'tracking_number' => ' JD0146 ',
				'ship_date'       => '2026/11/20',
				'status'          => 'wc-completed',
			)
		);

		$this->assertIsArray( $data );
		$this->assertSame( $order, $data['order'] );
		$this->assertSame( 'dhl', $data['carrier'] );
		$this->assertSame( 'DHL Express', $data['carrier_label'] );
		$this->assertSame( 'JD0146', $data['tracking_number'] );
		$this->assertSame( '2026-11-20', $data['ship_date'] );
		$this->assertSame( 'completed', $data['status'] );
		$this->assertSame( array(), $data['items'] );
	}

	/**
	 * Unknown carriers are kept as typed (custom carrier name).
	 */
	public function test_unknown_carrier_is_kept_as_is() {
		$this->assertSame(
			array( 'key' => 'Royal Mail', 'label' => 'Royal Mail' ),
			\Track_Orders_For_Woocommerce_Tracking_Service::normalize_carrier( 'Royal Mail' )
		);
	}

	/**
	 * FedEx is a known carrier only when the FedEx API option is on.
	 */
	public function test_fedex_known_only_when_api_enabled() {
		$this->assertSame( array(), \Track_Orders_For_Woocommerce_Tracking_Service::get_known_carriers() );
		$this->setOptions( array( 'wps_tofw_enable_track_order_using_api' => 'on' ) );
		$this->assertSame( array( 'fedex' => 'FedEx' ), \Track_Orders_For_Woocommerce_Tracking_Service::get_known_carriers() );
		$this->assertSame( 'fedex', \Track_Orders_For_Woocommerce_Tracking_Service::normalize_carrier( 'FEDEX' )['key'] );
	}

	/**
	 * Unknown / refund orders are rejected with a 404 error.
	 */
	public function test_prepare_rejects_missing_order_and_refunds() {
		$error = \Track_Orders_For_Woocommerce_Tracking_Service::prepare( 999, array( 'carrier' => 'UPS', 'tracking_number' => 'X' ) );
		$this->assertInstanceOf( \WP_Error::class, $error );
		$this->assertSame( 'wps_tofw_invalid_order', $error->get_error_code() );
		$this->assertSame( array( 'status' => 404 ), $error->get_error_data() );

		$this->mockTrackableOrder( array( 'id' => 11, 'get_type' => 'shop_order_refund' ) );
		$error = \Track_Orders_For_Woocommerce_Tracking_Service::prepare( 11, array( 'carrier' => 'UPS', 'tracking_number' => 'X' ) );
		$this->assertSame( 'wps_tofw_invalid_order', $error->get_error_code() );

		$this->assertSame( 'wps_tofw_invalid_order', \Track_Orders_For_Woocommerce_Tracking_Service::prepare( 'abc', array() )->get_error_code() );
	}

	/**
	 * Every validation problem of a row is reported at once.
	 */
	public function test_prepare_collects_all_validation_errors() {
		$this->mockTrackableOrder( array( 'id' => 12 ) );

		$error = \Track_Orders_For_Woocommerce_Tracking_Service::prepare(
			12,
			array(
				'carrier'         => '',
				'tracking_number' => '',
				'ship_date'       => '23/11/2026',
				'status'          => 'shipped-to-mars',
				'items'           => 'SKU:0',
			)
		);

		$this->assertInstanceOf( \WP_Error::class, $error );
		$this->assertEqualsCanonicalizing(
			array(
				'wps_tofw_missing_tracking_number',
				'wps_tofw_missing_carrier',
				'wps_tofw_invalid_ship_date',
				'wps_tofw_invalid_status',
				'wps_tofw_invalid_items',
			),
			$error->get_error_codes()
		);
	}

	/**
	 * Tracking numbers are limited to 100 characters.
	 */
	public function test_prepare_rejects_too_long_tracking_number() {
		$this->mockTrackableOrder( array( 'id' => 13 ) );
		$error = \Track_Orders_For_Woocommerce_Tracking_Service::prepare( 13, array( 'carrier' => 'UPS', 'tracking_number' => str_repeat( 'A', 101 ) ) );
		$this->assertSame( array( 'wps_tofw_invalid_tracking_number' ), $error->get_error_codes() );

		$ok = \Track_Orders_For_Woocommerce_Tracking_Service::prepare( 13, array( 'carrier' => 'UPS', 'tracking_number' => str_repeat( 'A', 100 ) ) );
		$this->assertIsArray( $ok );
	}

	/**
	 * A tracking number already on the order (shipments list or legacy single meta) is a 409 duplicate,
	 * compared case-insensitively.
	 */
	public function test_prepare_rejects_duplicate_tracking_numbers() {
		$this->mockTrackableOrder(
			array(
				'id'   => 14,
				'meta' => array(
					'wps_tofw_tracking_shipments'      => array( array( 'id' => 's1', 'carrier' => 'ups', 'tracking_number' => '1ZABC' ) ),
					'wps_tofw_package_tracking_number' => 'LEGACY-1',
				),
			)
		);

		$error = \Track_Orders_For_Woocommerce_Tracking_Service::prepare( 14, array( 'carrier' => 'UPS', 'tracking_number' => '1zabc' ) );
		$this->assertSame( 'wps_tofw_duplicate_tracking', $error->get_error_code() );
		$this->assertSame( array( 'status' => 409 ), $error->get_error_data() );

		$error = \Track_Orders_For_Woocommerce_Tracking_Service::prepare( 14, array( 'carrier' => 'UPS', 'tracking_number' => 'legacy-1' ) );
		$this->assertSame( 'wps_tofw_duplicate_tracking', $error->get_error_code() );
	}

	/**
	 * Partial shipment items are resolved by SKU / item id against the remaining quantity.
	 */
	public function test_prepare_resolves_partial_items() {
		$this->mockTrackableOrder(
			array(
				'id'    => 15,
				'items' => array(
					$this->mockLineItem( 101, 'TSHIRT', 3, 'T-shirt' ),
					$this->mockLineItem( 102, 'MUG', 1, 'Mug' ),
				),
				'meta'  => array(
					'wps_tofw_tracking_shipments' => array(
						array( 'id' => 's1', 'carrier' => 'ups', 'tracking_number' => 'T1', 'items' => array( array( 'item_id' => 101, 'qty' => 1 ) ) ),
					),
				),
			)
		);

		$data = \Track_Orders_For_Woocommerce_Tracking_Service::prepare( 15, array( 'carrier' => 'UPS', 'tracking_number' => 'T2', 'items' => 'tshirt:2|#102' ) );
		$this->assertSame(
			array(
				array( 'item_id' => 101, 'sku' => 'TSHIRT', 'name' => 'T-shirt', 'qty' => 2 ),
				array( 'item_id' => 102, 'sku' => 'MUG', 'name' => 'Mug', 'qty' => 1 ),
			),
			$data['items']
		);

		// 1 of 3 shipped already, 2 reserved by an earlier row of the same file: nothing left.
		$error = \Track_Orders_For_Woocommerce_Tracking_Service::prepare( 15, array( 'carrier' => 'UPS', 'tracking_number' => 'T3', 'items' => 'TSHIRT:1' ), array( 101 => 2 ) );
		$this->assertSame( array( 'wps_tofw_item_already_shipped' ), $error->get_error_codes() );

		$error = \Track_Orders_For_Woocommerce_Tracking_Service::prepare( 15, array( 'carrier' => 'UPS', 'tracking_number' => 'T3', 'items' => 'TSHIRT:5' ) );
		$this->assertSame( array( 'wps_tofw_item_qty_exceeded' ), $error->get_error_codes() );

		$error = \Track_Orders_For_Woocommerce_Tracking_Service::prepare( 15, array( 'carrier' => 'UPS', 'tracking_number' => 'T3', 'items' => 'NOPE:1|#999' ) );
		$this->assertSame( array( 'wps_tofw_item_not_found' ), $error->get_error_codes() );
		$this->assertCount( 2, $error->get_error_messages() );
	}

	/**
	 * add_tracking() stores the shipment (HPOS-safe CRUD meta), syncs the legacy keys, adds a note,
	 * sends the e-mail once, changes the status and fires the hooks.
	 */
	public function test_add_tracking_stores_shipment_and_notifies_once() {
		$order = $this->mockTrackableOrder( array( 'id' => 20, 'billing_email' => 'buyer@example.com' ) );
		$order->shouldReceive( 'save' )->atLeast()->once()->andReturn( 20 );
		Actions\expectDone( 'wps_tofw_tracking_added' )->once()->with( $order, \Mockery::type( 'array' ), 'csv' );
		Actions\expectDone( 'wps_tofw_tracking_saved' )->once()->with( $order, 'UPS', '1Z1', \Mockery::on( static function ( $ctx ) {
			return 'csv' === $ctx['source'] && '1Z1' === $ctx['shipment']['tracking_number'];
		} ) );

		$shipment = \Track_Orders_For_Woocommerce_Tracking_Service::add_tracking(
			20,
			array(
				'carrier'         => 'UPS',
				'tracking_number' => '1Z1',
				'ship_date'       => '2026-11-20',
				'status'          => 'completed',
				'source'          => 'csv',
			)
		);

		$this->assertIsArray( $shipment );
		$this->assertSame( 'csv', $shipment['source'] );
		$this->assertSame( '2026-11-20', $shipment['ship_date'] );
		$this->assertSame( 1, $shipment['added_by'] );

		$stored = $this->getOrderMeta( 20, 'wps_tofw_tracking_shipments' );
		$this->assertCount( 1, $stored );
		$this->assertSame( '1Z1', $stored[0]['tracking_number'] );
		$this->assertSame( 'UPS', $this->getOrderMeta( 20, 'wps_tofw_selected_shipping_service' ) );
		$this->assertSame( '1Z1', $this->getOrderMeta( 20, 'wps_tofw_package_tracking_number' ) );
		$this->assertSame( 1, $this->getOrderMeta( 20, 'wps_tofw_tracking_id_sent' ) );

		$this->assertCount( 1, $this->sent_mails );
		$this->assertSame( 'buyer@example.com', $this->sent_mails[0][0] );
		$this->assertStringContainsString( '1Z1', $this->sent_mails[0][2] );
		$this->assertStringContainsString( 'Tracking added: UPS 1Z1 (shipped 2026-11-20).', $this->order_notes[20][0] );
		$this->assertSame( array( 'completed' ), $this->status_changes[20] );

		// Second shipment with the default notify rule: e-mail already sent once, not sent again.
		\Track_Orders_For_Woocommerce_Tracking_Service::add_tracking( 20, array( 'carrier' => 'UPS', 'tracking_number' => '1Z2', 'status' => 'completed' ) );
		$this->assertCount( 1, $this->sent_mails );
		$this->assertCount( 2, $this->getOrderMeta( 20, 'wps_tofw_tracking_shipments' ) );
		$this->assertSame( array( 'completed' ), $this->status_changes[20], 'status is not changed again when already set' );
	}

	/**
	 * notify_customer=false never sends; true always sends.
	 */
	public function test_notify_customer_override() {
		$this->mockTrackableOrder( array( 'id' => 21 ) );

		\Track_Orders_For_Woocommerce_Tracking_Service::add_tracking( 21, array( 'carrier' => 'UPS', 'tracking_number' => 'A1', 'notify_customer' => false ) );
		$this->assertCount( 0, $this->sent_mails );
		$this->assertNull( $this->getOrderMeta( 21, 'wps_tofw_tracking_id_sent' ) );

		\Track_Orders_For_Woocommerce_Tracking_Service::add_tracking( 21, array( 'carrier' => 'UPS', 'tracking_number' => 'A2' ) );
		$this->assertCount( 1, $this->sent_mails );

		\Track_Orders_For_Woocommerce_Tracking_Service::add_tracking( 21, array( 'carrier' => 'UPS', 'tracking_number' => 'A3', 'notify_customer' => true ) );
		$this->assertCount( 2, $this->sent_mails );
	}

	/**
	 * should_send_email() rule and its filter.
	 */
	public function test_should_send_email_rule() {
		$fresh = $this->mockTrackableOrder( array( 'id' => 22 ) );
		$sent  = $this->mockTrackableOrder( array( 'id' => 23, 'meta' => array( 'wps_tofw_tracking_id_sent' => '1' ) ) );

		$this->assertTrue( \Track_Orders_For_Woocommerce_Tracking_Service::should_send_email( $fresh ) );
		$this->assertFalse( \Track_Orders_For_Woocommerce_Tracking_Service::should_send_email( $sent ) );
		$this->assertFalse( \Track_Orders_For_Woocommerce_Tracking_Service::should_send_email( $fresh, false ) );
		$this->assertTrue( \Track_Orders_For_Woocommerce_Tracking_Service::should_send_email( $sent, true ) );

		Filters\expectApplied( 'wps_tofw_send_tracking_added_email' )->once()->andReturn( false );
		$this->assertFalse( \Track_Orders_For_Woocommerce_Tracking_Service::should_send_email( $fresh ) );
	}

	/**
	 * Tracking saved through the order edit screen (legacy single meta) is kept in the shipments list
	 * when a new shipment is added.
	 */
	public function test_legacy_tracking_is_kept_in_shipments() {
		$this->mockTrackableOrder(
			array(
				'id'   => 24,
				'meta' => array(
					'wps_tofw_package_tracking_number'   => 'OLD-123',
					'wps_tofw_selected_shipping_service' => 'Royal Mail',
				),
			)
		);

		$order     = wc_get_order( 24 );
		$shipments = \Track_Orders_For_Woocommerce_Tracking_Service::get_shipments( $order );
		$this->assertCount( 1, $shipments );
		$this->assertSame( 'manual', $shipments[0]['source'] );
		$this->assertSame( 'OLD-123', $shipments[0]['tracking_number'] );

		\Track_Orders_For_Woocommerce_Tracking_Service::add_tracking( 24, array( 'carrier' => 'UPS', 'tracking_number' => 'NEW-1', 'source' => 'rest' ) );

		$stored = $this->getOrderMeta( 24, 'wps_tofw_tracking_shipments' );
		$this->assertSame( array( 'OLD-123', 'NEW-1' ), array_column( $stored, 'tracking_number' ) );
		$this->assertSame( 'Royal Mail', $stored[0]['carrier'] );
		$this->assertSame( 'NEW-1', $this->getOrderMeta( 24, 'wps_tofw_package_tracking_number' ) );

		$this->assertSame( array( 'OLD-123', 'NEW-1' ), array_column( \Track_Orders_For_Woocommerce_Tracking_Service::get_shipments( $order ), 'tracking_number' ) );
	}

	/**
	 * Invalid input does not write anything.
	 */
	public function test_add_tracking_with_invalid_input_saves_nothing() {
		$order = $this->mockTrackableOrder( array( 'id' => 25 ) );
		$order->shouldNotReceive( 'save' );
		Actions\expectDone( 'wps_tofw_tracking_added' )->never();

		$result = \Track_Orders_For_Woocommerce_Tracking_Service::add_tracking( 25, array( 'carrier' => 'UPS', 'tracking_number' => '' ) );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( array(), $this->getOrderMeta( 25 ) );
		$this->assertCount( 0, $this->sent_mails );
	}

	/**
	 * Tracking saved by other flows (metabox, shipping plugins) is appended once to the shipments list.
	 */
	public function test_on_tracking_saved_appends_and_dedupes() {
		$this->mockTrackableOrder( array( 'id' => 26 ) );

		\Track_Orders_For_Woocommerce_Tracking_Service::on_tracking_saved( 26, 'USPS', ' 9400 ', array( 'source' => 'shipstation' ) );
		\Track_Orders_For_Woocommerce_Tracking_Service::on_tracking_saved( 26, 'usps', '9400', array( 'source' => 'shipstation' ) );
		\Track_Orders_For_Woocommerce_Tracking_Service::on_tracking_saved( 26, 'USPS', '', array() );
		// Saves made by add_tracking() itself carry the shipment and are ignored.
		\Track_Orders_For_Woocommerce_Tracking_Service::on_tracking_saved( 26, 'UPS', '1Z', array( 'shipment' => array() ) );

		$stored = $this->getOrderMeta( 26, 'wps_tofw_tracking_shipments' );
		$this->assertCount( 1, $stored );
		$this->assertSame( '9400', $stored[0]['tracking_number'] );
		$this->assertSame( 'shipstation', $stored[0]['source'] );
	}

	/**
	 * API output shape.
	 */
	public function test_format_for_response() {
		$this->assertSame(
			array(
				'id'              => 's1',
				'carrier'         => 'ups',
				'carrier_label'   => 'ups',
				'tracking_number' => '1Z',
				'ship_date'       => '2026-11-20',
				'items'           => array(),
				'source'          => '',
				'date_added'      => '',
			),
			\Track_Orders_For_Woocommerce_Tracking_Service::format_for_response(
				array( 'id' => 's1', 'carrier' => 'ups', 'tracking_number' => '1Z', 'ship_date' => '2026-11-20', 'added_by' => 3 )
			)
		);
	}

	/**
	 * Status normalization accepts slugs, wc- slugs and labels.
	 */
	public function test_normalize_status() {
		$this->assertSame( 'completed', \Track_Orders_For_Woocommerce_Tracking_Service::normalize_status( 'Completed' ) );
		$this->assertSame( 'on-hold', \Track_Orders_For_Woocommerce_Tracking_Service::normalize_status( 'wc-on-hold' ) );
		$this->assertSame( '', \Track_Orders_For_Woocommerce_Tracking_Service::normalize_status( ' ' ) );
		$this->assertFalse( \Track_Orders_For_Woocommerce_Tracking_Service::normalize_status( 'shipped' ) );
	}
}
