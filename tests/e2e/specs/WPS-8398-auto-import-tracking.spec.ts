/**
 * WPS-8398: tracking numbers are imported automatically from shipping plugins
 * (ShipStation hook, Shipment Tracking meta, Shippo order notes), per integration setting.
 */
import { expect, test } from '@playwright/test';
import {
	createOrder,
	getOption,
	getOrderMeta,
	setOption,
	settingsTabUrl,
	updateOrderMeta,
	wpEvalJson,
} from '../utils';
import {
	addOrderNote,
	autoImportNotes,
	installFakeShippingPlugins,
	removeFakeShippingPlugins,
	setEnabledIntegrations,
	shipStationShipNotify,
} from '../utils/wps-8398';

const THIRD_PARTY_API_OPTION = 'wps_tofw_enable_third_party_tracking_api';

/** ID of the logged-in admin, used as the order customer so the "view order" page is accessible. */
function adminUserId(): number {
	return wpEvalJson< number >( `$u = get_user_by( 'login', 'admin' ); return $u ? (int) $u->ID : 1;` );
}

test.describe( 'WPS-8398 auto-import tracking from shipping plugins', () => {
	test.describe.configure( { mode: 'serial' } );

	let previousThirdPartyApi: unknown;
	let customerId: number;

	test.beforeAll( () => {
		installFakeShippingPlugins();
		previousThirdPartyApi = getOption( THIRD_PARTY_API_OPTION );
		// Shows the "Tracking Info" box (carrier + tracking code) on the order details page.
		setOption( THIRD_PARTY_API_OPTION, 'on' );
		setEnabledIntegrations();
		customerId = adminUserId();
	} );

	test.afterAll( () => {
		setEnabledIntegrations();
		setOption( THIRD_PARTY_API_OPTION, previousThirdPartyApi || '' );
		removeFakeShippingPlugins();
	} );

	test( 'admin enables an integration on the Shipping Services tab', async ( { page } ) => {
		await page.goto( settingsTabUrl( 'track-orders-for-woocommerce-shipping-services' ) );

		const shipstation = page.locator( '#wps_tofw_auto_import_shipstation' );
		await expect( page.locator( 'body' ) ).toContainText( 'Auto-import tracking from ShipStation' );
		await expect( page.locator( 'body' ) ).toContainText( 'Auto-import tracking from Shippo' );
		await expect( shipstation ).not.toBeChecked();
		// The fake ShipStation plugin is detected.
		await expect(
			page.locator( '.wps-form-group' ).filter( { has: shipstation } )
		).toContainText( 'plugin detected and active' );

		await shipstation.check( { force: true } );
		await Promise.all( [
			page.waitForResponse(
				( response ) => response.request().method() === 'POST' && response.url().includes( 'track-orders-for-woocommerce-shipping-services' )
			),
			page.locator( '#wps_tofw_shipping_services_settings_save' ).click(),
		] );

		await expect( page.locator( '#wps_tofw_auto_import_shipstation' ) ).toBeChecked();
		await expect( page.locator( '#wps_tofw_auto_import_shippo' ) ).not.toBeChecked();
		expect( getOption( 'wps_tofw_auto_import_shipstation' ) ).toBe( 'on' );
		expect( getOption( 'wps_tofw_auto_import_shippo' ) ).toBe( '' );
	} );

	test( 'ShipStation shipnotify imports the tracking once and the customer sees it', async ( { page } ) => {
		setEnabledIntegrations( 'shipstation' );
		const order = createOrder( { customer_id: customerId, billing_email: 'shipstation@example.com' } );

		shipStationShipNotify( order.id, '9400 1118 9922 3397 8465 05', 'USPS' );

		expect( getOrderMeta( order.id, 'wps_tofw_package_tracking_number' ) ).toBe( '9400111899223397846505' );
		expect( getOrderMeta( order.id, 'wps_tofw_selected_shipping_service' ) ).toBe( 'usps' );
		expect( getOrderMeta( order.id, 'wps_tofw_auto_import_source' ) ).toBe( 'shipstation' );
		expect( autoImportNotes( order.id ) ).toHaveLength( 1 );
		expect( autoImportNotes( order.id )[ 0 ] ).toContain( 'ShipStation' );

		// Same shipment notified again: de-duplicated.
		shipStationShipNotify( order.id, '9400111899223397846505', 'USPS' );
		expect( autoImportNotes( order.id ) ).toHaveLength( 1 );

		await page.goto( order.view_url );
		const details = page.locator( '.woocommerce-order-details' );
		await expect( details ).toContainText( 'Tracking Code' );
		await expect( details ).toContainText( '9400111899223397846505' );
		await expect( details ).toContainText( 'USPS' );
	} );

	test( 'a Shippo order note imports the tracking number and carrier', async () => {
		setEnabledIntegrations( 'shippo' );
		const order = createOrder( { customer_id: customerId } );

		addOrderNote( order.id, 'Shippo: your order has shipped. Tracking number: 1Z999AA10123456784 (UPS)' );

		expect( getOrderMeta( order.id, 'wps_tofw_package_tracking_number' ) ).toBe( '1Z999AA10123456784' );
		expect( getOrderMeta( order.id, 'wps_tofw_selected_shipping_service' ) ).toBe( 'ups' );
		expect( getOrderMeta( order.id, 'wps_tofw_auto_import_source' ) ).toBe( 'shippo' );
		expect( autoImportNotes( order.id ) ).toHaveLength( 1 );
	} );

	test( 'Shipment Tracking meta with an unknown carrier keeps the carrier name', async ( { page } ) => {
		setEnabledIntegrations( 'shipment_tracking' );
		const order = createOrder( { customer_id: customerId } );

		updateOrderMeta( order.id, {
			_wc_shipment_tracking_items: [
				{ tracking_provider: '', custom_tracking_provider: 'Acme Freight', tracking_number: 'ACME000123' },
			],
		} );

		expect( getOrderMeta( order.id, 'wps_tofw_package_tracking_number' ) ).toBe( 'ACME000123' );
		expect( getOrderMeta( order.id, 'wps_tofw_selected_shipping_service' ) ).toBe( 'acme_freight' );
		expect( getOrderMeta( order.id, 'wps_tofw_selected_shipping_service_label' ) ).toBe( 'Acme Freight' );
		expect( autoImportNotes( order.id ) ).toHaveLength( 1 );

		await page.goto( order.view_url );
		const details = page.locator( '.woocommerce-order-details' );
		await expect( details ).toContainText( 'ACME000123' );
		await expect( details ).toContainText( 'Acme Freight' );
	} );

	test( 'disabled integrations import nothing', async () => {
		setEnabledIntegrations();
		const order = createOrder( { customer_id: customerId } );

		shipStationShipNotify( order.id, '9400111899223397846599', 'USPS' );
		addOrderNote( order.id, 'Shippo: your order has shipped. Tracking number: 1Z999AA10123456799 (UPS)' );
		updateOrderMeta( order.id, {
			_wc_shipment_tracking_items: [ { tracking_provider: 'ups', tracking_number: '1Z999AA10123456788' } ],
		} );

		expect( getOrderMeta( order.id, 'wps_tofw_package_tracking_number' ) ).toBe( '' );
		expect( autoImportNotes( order.id ) ).toHaveLength( 0 );
	} );
} );
