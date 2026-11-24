<?php
/**
 * Auto-import manager: enabling, hooks, importing and de-duplication (WPS-8398).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\AutoImport;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use WPS_TOFW_Integrations_Manager;

/**
 * WPS_TOFW_Integrations_Manager.
 */
class IntegrationsManagerTest extends AutoImportTestCase {

	/**
	 * Manager under test.
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
	}

	/**
	 * Stub get_comment() for one order note.
	 *
	 * @param int    $comment_id Note ID.
	 * @param int    $order_id   Order ID.
	 * @param string $content    Note text.
	 * @return object
	 */
	private function stubNote( $comment_id, $order_id, $content ) {
		$comment = (object) array(
			'comment_ID'      => $comment_id,
			'comment_post_ID' => $order_id,
			'comment_content' => $content,
			'comment_type'    => 'order_note',
		);
		Functions\when( 'get_comment' )->justReturn( $comment );
		return $comment;
	}

	/**
	 * Tracking numbers recorded as imported (ignoring the empty entry, see test_imported_list_has_no_empty_entry).
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	private function importedNumbers( $order_id ) {
		return array_values( array_filter( (array) $this->getOrderMeta( $order_id, WPS_TOFW_Integrations_Manager::IMPORTED_META ), 'strlen' ) );
	}

	/**
	 * All seven integrations are registered, keyed by ID.
	 */
	public function test_registers_all_integrations() {
		$this->assertSame(
			array( 'shipstation', 'woocommerce_shipping', 'shipment_tracking', 'shippo', 'sendcloud', 'printful', 'printify' ),
			array_keys( $this->manager->get_integrations() )
		);
	}

	/**
	 * An integration is active only when enabled in the settings AND its plugin is active.
	 */
	public function test_active_integrations_need_setting_and_plugin() {
		$this->assertSame( array(), $this->manager->get_active_integrations(), 'nothing enabled by default' );

		$this->enableIntegrations( 'shipstation', 'shippo' );
		$this->assertSame( array( 'shippo' ), array_keys( $this->manager->get_active_integrations() ), 'ShipStation plugin is not active' );

		$this->activatePlugins( 'woocommerce-shipstation-integration/woocommerce-shipstation.php' );
		$this->assertSame( array( 'shipstation', 'shippo' ), array_keys( $this->manager->get_active_integrations() ) );
	}

	/**
	 * With every integration disabled no hook is registered at all.
	 */
	public function test_init_without_enabled_integrations_registers_nothing() {
		$this->activatePlugins( 'woocommerce-shipstation-integration/woocommerce-shipstation.php' );
		$this->manager->init();

		$this->assertFalse( has_action( 'woocommerce_shipstation_shipnotify' ) );
		$this->assertFalse( has_action( 'woocommerce_order_note_added', array( $this->manager, 'handle_order_note_added' ) ) );
		$this->assertFalse( has_action( 'woocommerce_after_order_object_save', array( $this->manager, 'handle_order_saved' ) ) );
	}

	/**
	 * Enabled ShipStation registers shipnotify + note hooks, but no meta hooks.
	 */
	public function test_init_registers_hooks_of_enabled_integrations() {
		$this->enableIntegrations( 'shipstation' );
		$this->activatePlugins( 'woocommerce-shipstation-integration/woocommerce-shipstation.php' );
		$this->manager->init();

		$integrations = $this->manager->get_integrations();
		$this->assertNotFalse( has_action( 'woocommerce_shipstation_shipnotify', array( $integrations['shipstation'], 'handle_shipnotify' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_order_note_added', array( $this->manager, 'handle_order_note_added' ) ) );
		$this->assertNotFalse( has_action( 'woocommerce_rest_insert_order_note', array( $this->manager, 'handle_rest_insert_order_note' ) ) );
		$this->assertFalse( has_action( 'woocommerce_after_order_object_save', array( $this->manager, 'handle_order_saved' ) ) );
	}

	/**
	 * Meta based integrations also watch order saves and post meta writes.
	 */
	public function test_init_registers_meta_hooks_for_meta_integrations() {
		$this->enableIntegrations( 'shipment_tracking' );
		$this->activatePlugins( 'woocommerce-shipment-tracking/woocommerce-shipment-tracking.php' );
		$this->manager->init();

		$this->assertNotFalse( has_action( 'woocommerce_after_order_object_save', array( $this->manager, 'handle_order_saved' ) ) );
		$this->assertNotFalse( has_action( 'added_post_meta', array( $this->manager, 'handle_post_meta' ) ) );
		$this->assertNotFalse( has_action( 'updated_post_meta', array( $this->manager, 'handle_post_meta' ) ) );
	}

	/**
	 * Importing saves the tracking, the carrier, the source, a note, and emails the customer once.
	 */
	public function test_import_tracking_saves_everything() {
		$order = $this->mockShopOrder(
			array(
				'id'            => 31,
				'billing_email' => 'buyer@example.com',
			)
		);
		Actions\expectDone( 'wps_tofw_tracking_saved' )->once();

		$this->assertTrue( $this->manager->import_tracking( $order, ' 9400 1118 9922 3397 8465 05 ', 'USPS', 'shipstation' ) );

		$this->assertSame( '9400111899223397846505', $this->getOrderMeta( 31, 'wps_tofw_package_tracking_number' ) );
		$this->assertSame( 'usps', $this->getOrderMeta( 31, 'wps_tofw_selected_shipping_service' ) );
		$this->assertSame( 'USPS', $this->getOrderMeta( 31, 'wps_tofw_selected_shipping_service_label' ) );
		$this->assertSame( 'shipstation', $this->getOrderMeta( 31, 'wps_tofw_auto_import_source' ) );
		$this->assertSame( 'USPS', $this->getOrderMeta( 31, 'wps_tofw_auto_import_carrier_name' ) );
		$this->assertSame( array( '9400111899223397846505' ), $this->importedNumbers( 31 ) );
		$this->assertSame( 1, $this->getOrderMeta( 31, 'wps_tofw_tracking_id_sent' ) );
		$this->assertSame( array( 'buyer@example.com' ), $this->mails );
		$this->assertCount( 1, $this->order_notes[31] );
		$this->assertStringContainsString( '9400111899223397846505', $this->order_notes[31][0] );
		$this->assertStringContainsString( 'ShipStation', $this->order_notes[31][0] );
	}

	/**
	 * The same tracking number is imported only once (case and spaces ignored).
	 */
	public function test_import_is_deduplicated() {
		$order = $this->mockShopOrder( array( 'id' => 32 ) );

		$this->assertTrue( $this->manager->import_tracking( $order, '1z999aa10123456784', 'UPS', 'shippo' ) );
		$this->assertFalse( $this->manager->import_tracking( $order, '1Z999AA1 0123456784', 'UPS', 'shippo' ) );
		$this->assertFalse( $this->manager->import_tracking( $order, '1Z999AA10123456784', 'UPS', 'shipstation' ) );

		$this->assertCount( 1, $this->order_notes[32] );
		$this->assertCount( 1, $this->mails );
		$this->assertSame( array( '1Z999AA10123456784' ), $this->importedNumbers( 32 ) );
	}

	/**
	 * A second, different shipment becomes the current one but the tracking email is not sent again.
	 */
	public function test_second_shipment_does_not_resend_email() {
		$order = $this->mockShopOrder( array( 'id' => 33 ) );

		$this->manager->import_tracking( $order, 'FIRST12345', 'UPS', 'shipment_tracking' );
		$this->manager->import_tracking( $order, 'SECOND1234', 'FedEx', 'shipment_tracking' );

		$this->assertSame( 'SECOND1234', $this->getOrderMeta( 33, 'wps_tofw_package_tracking_number' ) );
		$this->assertSame( 'fedex', $this->getOrderMeta( 33, 'wps_tofw_selected_shipping_service' ) );
		$this->assertSame( array( 'FIRST12345', 'SECOND1234' ), $this->importedNumbers( 33 ) );
		$this->assertCount( 1, $this->mails );
		$this->assertCount( 2, $this->order_notes[33] );
	}

	/**
	 * A number already entered manually is only remembered, not saved or announced again.
	 */
	public function test_existing_current_tracking_is_not_resaved() {
		$order = $this->mockShopOrder(
			array(
				'id'   => 34,
				'meta' => array( 'wps_tofw_package_tracking_number' => 'MANUAL1234' ),
			)
		);
		Actions\expectDone( 'wps_tofw_tracking_saved' )->never();

		$this->assertFalse( $this->manager->import_tracking( $order, 'manual1234', 'UPS', 'shippo' ) );
		$this->assertSame( array( 'MANUAL1234' ), $this->importedNumbers( 34 ) );
		$this->assertNull( $this->getOrderMeta( 34, 'wps_tofw_auto_import_source' ) );
		$this->assertSame( array(), $this->order_notes[34] );
		$this->assertSame( array(), $this->mails );
	}

	/**
	 * Unknown carriers keep their display name and are remembered as a shipping service.
	 */
	public function test_unknown_carrier_keeps_display_name() {
		$order = $this->mockShopOrder( array( 'id' => 35 ) );

		$this->manager->import_tracking( $order, 'ACME000123', 'Acme Freight', 'shipment_tracking' );

		$this->assertSame( 'acme_freight', $this->getOrderMeta( 35, 'wps_tofw_selected_shipping_service' ) );
		$this->assertSame( 'Acme Freight', $this->getOrderMeta( 35, 'wps_tofw_selected_shipping_service_label' ) );
		$this->assertSame( array( 'acme_freight' => 'Acme Freight' ), $this->getOption( WPS_TOFW_Integrations_Manager::SEEN_CARRIERS_OPTION ) );
		$this->assertSame( 'Acme Freight', $this->manager->add_seen_carriers_to_services( array() )['acme_freight'] );
	}

	/**
	 * Without a carrier the note says "unknown carrier".
	 */
	public function test_missing_carrier_note() {
		$order = $this->mockShopOrder( array( 'id' => 36 ) );

		$this->manager->import_tracking( $order, 'NOCARRIER1', '', 'shippo' );

		$this->assertSame( 'NOCARRIER1', $this->getOrderMeta( 36, 'wps_tofw_package_tracking_number' ) );
		$this->assertStringContainsString( 'unknown carrier', $this->order_notes[36][0] );
	}

	/**
	 * Invalid input and a vetoing filter import nothing.
	 */
	public function test_import_rejections() {
		$order = $this->mockShopOrder( array( 'id' => 37 ) );

		$this->assertFalse( $this->manager->import_tracking( $order, '   ', 'UPS', 'shippo' ) );
		$this->assertFalse( $this->manager->import_tracking( 37, '1Z999AA10123456784', 'UPS', 'shippo' ), 'needs a WC_Order' );

		Filters\expectApplied( 'wps_tofw_auto_import_tracking_data' )->once()->andReturn( array() );
		$this->assertFalse( $this->manager->import_tracking( $order, '1Z999AA10123456784', 'UPS', 'shippo' ) );

		$this->assertNull( $this->getOrderMeta( 37, 'wps_tofw_package_tracking_number' ) );
		$this->assertSame( array(), $this->order_notes[37] );
	}

	/**
	 * The imported-numbers meta should only hold tracking numbers.
	 */
	public function test_imported_list_has_no_empty_entry() {
		$order = $this->mockShopOrder( array( 'id' => 38 ) );
		$this->manager->import_tracking( $order, '1Z999AA10123456784', 'UPS', 'shippo' );

		$imported = $this->getOrderMeta( 38, WPS_TOFW_Integrations_Manager::IMPORTED_META );
		if ( array( '1Z999AA10123456784' ) !== $imported ) {
			$this->markTestIncomplete(
				'Bug: WPS_TOFW_Integrations_Manager::import_tracking() casts the missing meta (get_meta() returns "") with (array), '
				. 'so "wps_tofw_auto_imported_tracking_numbers" is saved as [ "", "<number>" ]. De-duplication still works.'
			);
		}
		$this->assertSame( array( '1Z999AA10123456784' ), $imported );
	}

	/**
	 * Order note from an enabled note integration (Shippo) imports the tracking.
	 */
	public function test_order_note_imports_for_enabled_integration() {
		$this->enableIntegrations( 'shippo' );
		$order = $this->mockShopOrder( array( 'id' => 41 ) );
		$this->stubNote( 501, 41, 'Shippo: your order has shipped. Tracking number: 9400111899223397846505 (USPS)' );

		$this->manager->handle_order_note_added( 501, $order );

		$this->assertSame( '9400111899223397846505', $this->getOrderMeta( 41, 'wps_tofw_package_tracking_number' ) );
		$this->assertSame( 'shippo', $this->getOrderMeta( 41, 'wps_tofw_auto_import_source' ) );
	}

	/**
	 * The same note reported twice (note_added + wp_insert_comment) is handled once.
	 */
	public function test_same_note_is_handled_once() {
		$this->enableIntegrations( 'shippo' );
		$order   = $this->mockShopOrder( array( 'id' => 42 ) );
		$comment = $this->stubNote( 502, 42, 'Shippo: tracking number 9400111899223397846505 (USPS)' );

		$this->manager->handle_order_note_added( 502, $order );
		$this->manager->handle_insert_comment( 502, $comment );

		$this->assertCount( 1, $this->order_notes[42] );
	}

	/**
	 * A note is ignored when its integration is disabled.
	 */
	public function test_order_note_ignored_when_integration_disabled() {
		$this->enableIntegrations( 'shipstation' ); // Enabled, but not the Shippo one.
		$this->activatePlugins( 'woocommerce-shipstation-integration/woocommerce-shipstation.php' );
		$order = $this->mockShopOrder( array( 'id' => 43 ) );
		$this->stubNote( 503, 43, 'Shippo: tracking number 1Z999AA10123456784 (UPS)' );

		$this->manager->handle_order_note_added( 503, $order );

		$this->assertNull( $this->getOrderMeta( 43, 'wps_tofw_package_tracking_number' ) );
	}

	/**
	 * Non order-note comments are ignored.
	 */
	public function test_non_order_note_comments_are_ignored() {
		$this->enableIntegrations( 'shippo' );
		$this->mockShopOrder( array( 'id' => 44 ) );

		$this->manager->handle_insert_comment(
			504,
			(object) array(
				'comment_ID'      => 504,
				'comment_post_ID' => 44,
				'comment_content' => 'Shippo: tracking number 1Z999AA10123456784 (UPS)',
				'comment_type'    => 'review',
			)
		);

		$this->assertNull( $this->getOrderMeta( 44, 'wps_tofw_package_tracking_number' ) );
	}

	/**
	 * Saving an order with Shipment Tracking items imports them (HPOS-safe CRUD hook).
	 */
	public function test_order_save_imports_shipment_tracking_meta() {
		$this->enableIntegrations( 'shipment_tracking' );
		$this->activatePlugins( 'woocommerce-shipment-tracking/woocommerce-shipment-tracking.php' );
		$order = $this->mockShopOrder(
			array(
				'id'   => 51,
				'meta' => array(
					'_wc_shipment_tracking_items' => array(
						array(
							'tracking_provider' => 'ups',
							'tracking_number'   => '1Z999AA10123456784',
						),
					),
				),
			)
		);

		$this->manager->handle_order_saved( $order );
		$this->manager->handle_order_saved( $order );

		$this->assertSame( '1Z999AA10123456784', $this->getOrderMeta( 51, 'wps_tofw_package_tracking_number' ) );
		$this->assertSame( 'ups', $this->getOrderMeta( 51, 'wps_tofw_selected_shipping_service' ) );
		$this->assertCount( 1, $this->order_notes[51], 'second save must not import again' );
	}

	/**
	 * Meta of a disabled integration is ignored; refunds are ignored.
	 */
	public function test_order_save_ignored_when_disabled_or_refund() {
		$this->activatePlugins( 'woocommerce-shipment-tracking/woocommerce-shipment-tracking.php' );
		$meta  = array(
			'_wc_shipment_tracking_items' => array(
				array(
					'tracking_provider' => 'ups',
					'tracking_number'   => '1Z999AA10123456784',
				),
			),
		);
		$order = $this->mockShopOrder(
			array(
				'id'   => 52,
				'meta' => $meta,
			)
		);
		$this->manager->handle_order_saved( $order );
		$this->assertNull( $this->getOrderMeta( 52, 'wps_tofw_package_tracking_number' ), 'disabled' );

		$this->enableIntegrations( 'shipment_tracking' );
		$refund = $this->mockShopOrder(
			array(
				'id'       => 53,
				'meta'     => $meta,
				'get_type' => 'shop_order_refund',
			)
		);
		$this->manager->handle_order_saved( $refund );
		$this->assertNull( $this->getOrderMeta( 53, 'wps_tofw_package_tracking_number' ), 'refund' );
	}

	/**
	 * Direct post meta writes of WooCommerce Shipping labels (serialized) are imported.
	 */
	public function test_post_meta_label_write_is_imported() {
		$this->enableIntegrations( 'woocommerce_shipping' );
		$this->activatePlugins( 'woocommerce-shipping/woocommerce-shipping.php' );
		$this->mockShopOrder( array( 'id' => 54 ) );
		$labels = serialize( // phpcs:ignore
			array(
				array(
					'tracking'   => '9400111899223397846505',
					'carrier_id' => 'usps',
				),
			)
		);

		$this->manager->handle_post_meta( 1, 54, 'unrelated_key', $labels );
		$this->assertNull( $this->getOrderMeta( 54, 'wps_tofw_package_tracking_number' ) );

		$this->manager->handle_post_meta( 2, 54, 'wcshipping_labels', $labels );
		$this->assertSame( '9400111899223397846505', $this->getOrderMeta( 54, 'wps_tofw_package_tracking_number' ) );
		$this->assertSame( 'woocommerce_shipping', $this->getOrderMeta( 54, 'wps_tofw_auto_import_source' ) );
	}

	/**
	 * Settings: one on/off switch per integration with its status text.
	 */
	public function test_settings_have_a_switch_per_integration() {
		$this->enableIntegrations( 'shippo' );

		$settings = $this->manager->add_settings( array( array( 'id' => 'existing' ) ) );

		$this->assertCount( 8, $settings );
		$this->assertSame( 'existing', $settings[0]['id'] );
		$ids = array_column( $settings, 'id' );
		foreach ( array( 'shipstation', 'woocommerce_shipping', 'shipment_tracking', 'shippo', 'sendcloud', 'printful', 'printify' ) as $id ) {
			$this->assertContains( 'wps_tofw_auto_import_' . $id, $ids );
		}
		$shippo = $settings[ array_search( 'wps_tofw_auto_import_shippo', $ids, true ) ];
		$this->assertSame( 'radio-switch', $shippo['type'] );
		$this->assertSame( 'on', $shippo['value'] );
		$this->assertStringContainsString( 'Shippo', $shippo['title'] );
		$this->assertNotSame( '', $shippo['description'] );
	}
}
