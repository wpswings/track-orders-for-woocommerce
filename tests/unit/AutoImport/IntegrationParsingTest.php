<?php
/**
 * Parsing of tracking data per shipping integration (WPS-8398).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\AutoImport;

use Brain\Monkey\Functions;
use Mockery;
use WPS_TOFW_Integration_Printful;
use WPS_TOFW_Integration_Printify;
use WPS_TOFW_Integration_Sendcloud;
use WPS_TOFW_Integration_Shipment_Tracking;
use WPS_TOFW_Integration_ShipStation;
use WPS_TOFW_Integration_Shippo;
use WPS_TOFW_Integration_WooCommerce_Shipping;
use WPS_TOFW_Integrations_Manager;

/**
 * Meta / note / hook parsing of every integration.
 */
class IntegrationParsingTest extends AutoImportTestCase {

	/**
	 * Real manager (used for the known-carrier check of unsigned notes).
	 *
	 * @var WPS_TOFW_Integrations_Manager
	 */
	private $manager;

	/**
	 * Create the manager.
	 */
	protected function set_up() {
		parent::set_up();
		$this->manager = new WPS_TOFW_Integrations_Manager();
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
	}

	// ShipStation.

	/**
	 * The shipnotify hook of the ShipStation plugin imports the tracking number and carrier.
	 */
	public function test_shipstation_shipnotify_imports_tracking() {
		$order   = $this->mockShopOrder( array( 'id' => 21 ) );
		$manager = Mockery::mock( 'WPS_TOFW_Integrations_Manager' );
		$manager->shouldReceive( 'import_tracking' )->once()->with( $order, '9400111899223397846505', 'USPS', 'shipstation' );

		$integration = new WPS_TOFW_Integration_ShipStation( $manager );
		$integration->handle_shipnotify(
			21,
			array(
				'tracking_number' => '9400111899223397846505',
				'carrier'         => 'USPS',
				'ship_date'       => '2026-11-20',
			)
		);
	}

	/**
	 * Shipnotify without a tracking number, bad args or unknown order is ignored.
	 */
	public function test_shipstation_shipnotify_ignores_incomplete_payloads() {
		$this->mockShopOrder( array( 'id' => 22 ) );
		$manager = Mockery::mock( 'WPS_TOFW_Integrations_Manager' );
		$manager->shouldNotReceive( 'import_tracking' );

		$integration = new WPS_TOFW_Integration_ShipStation( $manager );
		$integration->handle_shipnotify( 22, array( 'carrier' => 'USPS' ) );
		$integration->handle_shipnotify( 22, array( 'tracking_number' => '' ) );
		$integration->handle_shipnotify( 22, 'not-an-array' );
		$integration->handle_shipnotify( 999, array( 'tracking_number' => '9400111899223397846505' ) );

		$this->addToAssertionCount( 1 );
	}

	/**
	 * Shipnotify without a carrier imports with an empty carrier.
	 */
	public function test_shipstation_shipnotify_without_carrier() {
		$order   = $this->mockShopOrder( array( 'id' => 23 ) );
		$manager = Mockery::mock( 'WPS_TOFW_Integrations_Manager' );
		$manager->shouldReceive( 'import_tracking' )->once()->with( $order, 'TRK123456', '', 'shipstation' );

		( new WPS_TOFW_Integration_ShipStation( $manager ) )->handle_shipnotify( $order, array( 'tracking_number' => 'TRK123456' ) );
	}

	/**
	 * ShipStation registers its shipnotify hook.
	 */
	public function test_shipstation_registers_shipnotify_hook() {
		$integration = new WPS_TOFW_Integration_ShipStation( $this->manager );
		$integration->register_hooks();

		$this->assertNotFalse( has_action( 'woocommerce_shipstation_shipnotify', array( $integration, 'handle_shipnotify' ) ) );
	}

	/**
	 * ShipStation's own order note format is parsed.
	 */
	public function test_shipstation_note_is_parsed() {
		$integration = new WPS_TOFW_Integration_ShipStation( $this->manager );

		$shipment = $integration->extract_from_note( 'Order shipped via USPS on 2026-11-20 with tracking number 9400111899223397846505.' );

		$this->assertSame(
			array(
				'tracking_number' => '9400111899223397846505',
				'carrier'         => 'USPS',
			),
			$shipment
		);
	}

	/**
	 * ShipStation is detected through its plugin folder.
	 */
	public function test_shipstation_plugin_detection() {
		$integration = new WPS_TOFW_Integration_ShipStation( $this->manager );
		$this->assertFalse( $integration->is_plugin_active() );

		$this->activatePlugins( 'woocommerce-shipstation-integration/woocommerce-shipstation.php' );
		$this->assertTrue( $integration->is_plugin_active() );
	}

	// WooCommerce Shipping labels.

	/**
	 * Labels are imported oldest first; refunded and failed labels are skipped.
	 */
	public function test_woocommerce_shipping_labels() {
		$integration = new WPS_TOFW_Integration_WooCommerce_Shipping( $this->manager );
		$labels      = array(
			array(
				'tracking'     => 'NEWEST123',
				'carrier_id'   => 'usps',
				'service_name' => 'USPS - Priority Mail',
			),
			array(
				'tracking'   => 'REFUNDED1',
				'carrier_id' => 'ups',
				'refund'     => array( 'status' => 'complete' ),
			),
			array(
				'tracking'   => 'ERROR123',
				'carrier_id' => 'ups',
				'status'     => 'PURCHASE_ERROR',
			),
			array(
				'tracking'     => '',
				'carrier_id'   => 'dhl',
			),
			array(
				'tracking'     => 'OLDEST123',
				'service_name' => 'FedEx Ground',
			),
		);

		$this->assertSame(
			array(
				array(
					'tracking_number' => 'OLDEST123',
					'carrier'         => 'FedEx Ground',
				),
				array(
					'tracking_number' => 'NEWEST123',
					'carrier'         => 'usps',
				),
			),
			$integration->extract_from_meta( 'wcshipping_labels', $labels )
		);
	}

	/**
	 * Labels stored as JSON (older WooCommerce Shipping / Services) are decoded.
	 */
	public function test_woocommerce_shipping_json_labels() {
		$integration = new WPS_TOFW_Integration_WooCommerce_Shipping( $this->manager );
		$json        = '[{"tracking":"1Z999AA10123456784","carrier_id":"ups"}]';

		$this->assertSame(
			array(
				array(
					'tracking_number' => '1Z999AA10123456784',
					'carrier'         => 'ups',
				),
			),
			$integration->extract_from_meta( 'wc_connect_labels', $json )
		);
		$this->assertSame( array(), $integration->extract_from_meta( 'wc_connect_labels', 'not json' ) );
		$this->assertSame( array(), $integration->extract_from_meta( 'wc_connect_labels', 42 ) );
	}

	/**
	 * Watched label meta keys (filterable).
	 */
	public function test_woocommerce_shipping_watched_meta_keys() {
		$integration = new WPS_TOFW_Integration_WooCommerce_Shipping( $this->manager );

		$this->assertSame( array( 'wc_connect_labels', 'wcshipping_labels', '_wcshipping-labels' ), $integration->get_watched_meta_keys() );
	}

	// WooCommerce Shipment Tracking.

	/**
	 * Shipment Tracking items: custom provider wins over the provider slug, empty items skipped.
	 */
	public function test_shipment_tracking_items() {
		$integration = new WPS_TOFW_Integration_Shipment_Tracking( $this->manager );
		$items       = array(
			array(
				'tracking_provider' => 'ups',
				'tracking_number'   => '1Z999AA10123456784',
			),
			array(
				'tracking_provider'        => '',
				'custom_tracking_provider' => 'Acme Freight',
				'tracking_number'          => 'ACME000123',
			),
			array(
				'tracking_provider' => 'dhl',
				'tracking_number'   => '',
			),
			(object) array(
				'tracking_number' => 'NOCARRIER1',
			),
		);

		$this->assertSame(
			array(
				array(
					'tracking_number' => '1Z999AA10123456784',
					'carrier'         => 'ups',
				),
				array(
					'tracking_number' => 'ACME000123',
					'carrier'         => 'Acme Freight',
				),
				array(
					'tracking_number' => 'NOCARRIER1',
					'carrier'         => '',
				),
			),
			$integration->extract_from_meta( '_wc_shipment_tracking_items', $items )
		);
		$this->assertSame( array(), $integration->extract_from_meta( '_wc_shipment_tracking_items', 'garbage' ) );
		$this->assertSame( array( '_wc_shipment_tracking_items' ), $integration->get_watched_meta_keys() );
	}

	// Order notes: Shippo, Sendcloud, Printful, Printify.

	/**
	 * A Shippo note with "Tracking number: X (Carrier)".
	 */
	public function test_shippo_note() {
		$integration = new WPS_TOFW_Integration_Shippo( $this->manager );

		$this->assertSame(
			array(
				'tracking_number' => '9400111899223397846505',
				'carrier'         => 'USPS',
			),
			$integration->extract_from_note( 'Shippo: your order has shipped. Tracking number: 9400111899223397846505 (USPS)' )
		);
	}

	/**
	 * Carrier and number are read from a tracking link in the note.
	 */
	public function test_shippo_note_with_tracking_link() {
		$integration = new WPS_TOFW_Integration_Shippo( $this->manager );
		$note        = 'Label created with Shippo. <a href="https://tools.usps.com/go/TrackConfirmAction?tLabels=9400111899223397846505">Track package</a>';

		$this->assertSame(
			array(
				'tracking_number' => '9400111899223397846505',
				'carrier'         => 'USPS',
			),
			$integration->extract_from_note( $note )
		);
	}

	/**
	 * Sendcloud forward links carry carrier and code.
	 */
	public function test_sendcloud_forward_link() {
		$integration = new WPS_TOFW_Integration_Sendcloud( $this->manager );
		$note        = 'Sendcloud label created. <a href="https://tracking.sendcloud.sc/forward?carrier=postnl&amp;code=3SABCD1234567&amp;destination=NL">Track</a>';

		$this->assertSame(
			array(
				'tracking_number' => '3SABCD1234567',
				'carrier'         => 'postnl',
			),
			$integration->extract_from_note( $note )
		);
	}

	/**
	 * Printful "Carrier: X, Tracking number: Y" style notes.
	 */
	public function test_printful_note() {
		$integration = new WPS_TOFW_Integration_Printful( $this->manager );

		$this->assertSame(
			array(
				'tracking_number' => '1Z999AA10123456784',
				'carrier'         => 'UPS',
			),
			$integration->extract_from_note( "Printful order shipped.\nCarrier: UPS\nTracking number: 1Z999AA10123456784" )
		);
	}

	/**
	 * Printify notes are recognised by name.
	 */
	public function test_printify_note() {
		$integration = new WPS_TOFW_Integration_Printify( $this->manager );

		$this->assertSame(
			array(
				'tracking_number' => 'LX123456789CN',
				'carrier'         => 'DHL',
			),
			$integration->extract_from_note( 'Printify: item shipped, tracking number LX123456789CN via DHL' )
		);
	}

	/**
	 * Notes that do not name the app are ignored unless the app is the only note
	 * integration and the carrier is a known one.
	 */
	public function test_unsigned_notes() {
		$printful = new WPS_TOFW_Integration_Printful( $this->manager );
		$note     = 'Your order is on its way. Tracking number: 1Z999AA10123456784 (UPS)';

		$this->assertNull( $printful->extract_from_note( $note ), 'unsigned note, several note integrations' );
		$this->assertSame(
			array(
				'tracking_number' => '1Z999AA10123456784',
				'carrier'         => 'UPS',
			),
			$printful->extract_from_note( $note, array( 'single' => true ) )
		);
		$this->assertNull(
			$printful->extract_from_note( 'Your order is on its way. Tracking number: ZZ1234567 (Some Local Courier)', array( 'single' => true ) ),
			'unsigned note with an unknown carrier'
		);

		// Sendcloud never trusts unsigned notes.
		$sendcloud = new WPS_TOFW_Integration_Sendcloud( $this->manager );
		$this->assertNull( $sendcloud->extract_from_note( $note, array( 'single' => true ) ) );
	}

	/**
	 * Notes added through the app's REST API key ("attributed") are trusted even without the name.
	 */
	public function test_attributed_note_with_unknown_carrier() {
		$shippo = new WPS_TOFW_Integration_Shippo( $this->manager );

		$this->assertSame(
			array(
				'tracking_number' => 'ZZ1234567',
				'carrier'         => 'Some Local Courier',
			),
			$shippo->extract_from_note( 'Tracking number: ZZ1234567 (Some Local Courier)', array( 'attributed' => true ) )
		);
	}

	/**
	 * Values without a digit are not tracking numbers; empty / non-string notes are ignored.
	 */
	public function test_invalid_notes() {
		$shippo = new WPS_TOFW_Integration_Shippo( $this->manager );

		$this->assertNull( $shippo->extract_from_note( 'Shippo: tracking number ABCDEFGHIJ' ) );
		$this->assertNull( $shippo->extract_from_note( 'Shippo: label purchased' ) );
		$this->assertNull( $shippo->extract_from_note( '' ) );
		$this->assertNull( $shippo->extract_from_note( array( 'Shippo' ) ) );
	}

	/**
	 * Meta-only integrations never parse notes.
	 */
	public function test_meta_integrations_ignore_notes() {
		$integration = new WPS_TOFW_Integration_Shipment_Tracking( $this->manager );

		$this->assertFalse( $integration->uses_order_notes() );
		$this->assertNull( $integration->extract_from_note( 'Shipment Tracking: tracking number 1Z999AA10123456784 (UPS)', array( 'single' => true ) ) );
	}

	// Settings.

	/**
	 * Every integration has its own on/off option, off by default.
	 */
	public function test_per_integration_enable_option() {
		$integration = new WPS_TOFW_Integration_Shippo( $this->manager );

		$this->assertSame( 'wps_tofw_auto_import_shippo', $integration->get_option_name() );
		$this->assertFalse( $integration->is_enabled() );

		$this->enableIntegrations( 'shippo' );
		$this->assertTrue( $integration->is_enabled() );

		$this->setOptions( array( 'wps_tofw_auto_import_shippo' => 'yes' ) );
		$this->assertFalse( $integration->is_enabled(), 'only "on" enables an integration' );
	}
}
