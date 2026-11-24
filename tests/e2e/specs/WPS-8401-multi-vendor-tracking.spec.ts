/**
 * WPS-8401 Multi-vendor shipment tracking.
 *
 * Marketplaces are not installed in wp-env: a WCFM stand-in (see utils/wps-8401.ts) activates the
 * plugin's real WCFM integration. Customer-facing rendering is tested with shipments seeded in the
 * order meta (per-vendor key + legacy combined key); the vendor AJAX endpoint is called with real
 * vendor / subscriber sessions.
 */
import { Browser, expect, Page, test } from '@playwright/test';
import { getOption, getOrderMeta, GUEST_STORAGE_STATE, setOptions, trackingPageUrl, updateOrderMeta } from '../utils';
import {
	createMarketplaceOrder,
	deleteUsers,
	ensureUser,
	installFakeWcfm,
	loggedInContext,
	removeFakeWcfm,
	saveVendorShipment,
	vendorNonce,
} from '../utils/wps-8401';

const BASE_URL = process.env.WP_BASE_URL || 'http://localhost:8888';
const USERS = [ 'e2e8401alpha', 'e2e8401beta', 'e2e8401customer', 'e2e8401subscriber' ];

let vendorA = 0;
let vendorB = 0;
let customer = 0;
let savedTemplate: unknown;
let savedMap: unknown;

const ITEMS = ( a: number, b: number ) => [
	{ sku: 'E2E-8401-ALPHA', name: 'Alpha Mug', qty: 2, vendor_id: a },
	{ sku: 'E2E-8401-BETA', name: 'Beta Book', qty: 1, vendor_id: b },
];

/** Shipments: vendor A shipped 1 of 2 mugs (own meta key), vendor B's book is in the legacy combined key. */
function seededMeta( itemA: number, itemB: number ): Record< string, unknown > {
	return {
		[ `wps_tofw_vendor_shipments_${ vendorA }` ]: {
			s1: {
				id: 's1',
				carrier: 'fedex',
				carrier_name: 'FedEx',
				tracking_number: 'FX-ALPHA-1',
				tracking_url: 'https://www.fedex.com/fedextrack/?trknbr=FX-ALPHA-1',
				date: '2026-11-20',
				items: { [ itemA ]: 1 },
				created: 1,
			},
		},
		wps_tofw_vendor_shipments: {
			[ vendorB ]: {
				legacy1: {
					id: 'legacy1',
					carrier: 'ups',
					carrier_name: 'UPS',
					tracking_number: 'UPS-BETA-9',
					tracking_url: '',
					date: '2026-11-21',
					items: { [ itemB ]: 1 },
					created: 2,
				},
			},
		},
	};
}

/** Assert the per-vendor section of a page. */
async function expectVendorSections( page: Page ): Promise< void > {
	const section = page.locator( '.wps-tofw-vt-customer' );
	await expect( section ).toHaveCount( 1 );
	const groups = section.locator( '.wps-tofw-vt-group' );
	await expect( groups ).toHaveCount( 2 );

	const alpha = groups.filter( { has: page.locator( '.wps-tofw-vt-store', { hasText: 'Alpha Store' } ) } );
	await expect( alpha.locator( 'a', { hasText: 'FX-ALPHA-1' } ) ).toHaveAttribute( 'href', 'https://www.fedex.com/fedextrack/?trknbr=FX-ALPHA-1' );
	await expect( alpha.locator( '.wps-tofw-vt-shipments' ) ).toContainText( 'FedEx' );
	await expect( alpha.locator( '.wps-tofw-vt-shipments' ) ).toContainText( 'Alpha Mug × 1' );
	await expect( alpha.locator( '.wps-tofw-vt-pending' ) ).toContainText( 'Alpha Mug × 1' );

	const beta = groups.filter( { has: page.locator( '.wps-tofw-vt-store', { hasText: 'Beta Store' } ) } );
	await expect( beta.locator( '.wps-tofw-vt-shipments' ) ).toContainText( 'UPS-BETA-9' );
	await expect( beta.locator( '.wps-tofw-vt-shipments' ) ).toContainText( 'Beta Book × 1' );
	await expect( beta.locator( '.wps-tofw-vt-pending' ) ).toHaveCount( 0 );

	await expect( section.locator( '.wps-tofw-vt-delete' ) ).toHaveCount( 0 );
}

async function customerPage( browser: Browser, login: string ) {
	const context = await loggedInContext( browser, login, BASE_URL );
	return { context, page: await context.newPage() };
}

test.describe.serial( 'WPS-8401 multi-vendor tracking', () => {
	test.beforeAll( () => {
		installFakeWcfm();
		vendorA = ensureUser( 'e2e8401alpha', 'subscriber', 'Alpha Store' );
		vendorB = ensureUser( 'e2e8401beta', 'subscriber', 'Beta Store' );
		customer = ensureUser( 'e2e8401customer', 'customer' );
		ensureUser( 'e2e8401subscriber', 'subscriber' );
		savedTemplate = getOption( 'wps_tofw_activated_template' );
		savedMap = getOption( 'wps_tofw_trackorder_with_google_map' );
		setOptions( { wps_tofw_activated_template: 'template1', wps_tofw_trackorder_with_google_map: 'off' } );
	} );

	test.afterAll( () => {
		setOptions( { wps_tofw_activated_template: savedTemplate || '', wps_tofw_trackorder_with_google_map: savedMap || '' } );
		removeFakeWcfm();
		deleteUsers( USERS );
	} );

	test.describe( 'customer view', () => {
		test.use( { storageState: GUEST_STORAGE_STATE } );

		test( 'tracking page shows shipments per vendor (own key and legacy key)', async ( { page } ) => {
			const email = `mv-guest-${ Date.now() }@example.com`;
			const order = createMarketplaceOrder( { billing_email: email, items: ITEMS( vendorA, vendorB ) } );
			updateOrderMeta( order.id, seededMeta( order.items[ 0 ], order.items[ 1 ] ) );

			await page.goto( trackingPageUrl( 'wps_guest_track_order_page' ) );
			await page.locator( '#order_id' ).fill( String( order.id ) );
			await page.locator( '#order_email' ).fill( email );
			await page.locator( 'input[name="wps_tofw_order_id_submit"]' ).click();

			await expectVendorSections( page );
		} );
	} );

	test( 'order details (My Account) and the map template show shipments per vendor', async ( { browser } ) => {
		const order = createMarketplaceOrder( { customer_id: customer, billing_email: 'e2e8401customer@example.com', items: ITEMS( vendorA, vendorB ) } );
		updateOrderMeta( order.id, seededMeta( order.items[ 0 ], order.items[ 1 ] ) );

		const { context, page } = await customerPage( browser, 'e2e8401customer' );
		try {
			await page.goto( order.view_url );
			await expect( page.locator( '.woocommerce-order-details' ) ).toBeVisible();
			await expectVendorSections( page );

			setOptions( { wps_tofw_trackorder_with_google_map: 'on' } );
			await page.goto( `${ trackingPageUrl( 'wps_track_order_page' ) }?${ order.id }` );
			await expect( page.locator( '#map' ) ).toBeAttached();
			await expectVendorSections( page );
		} finally {
			setOptions( { wps_tofw_trackorder_with_google_map: 'off' } );
			await context.close();
		}
	} );

	test( 'vendor AJAX: ownership, nonce and quantities are enforced', async ( { browser } ) => {
		// Order with items of vendor A only.
		const order = createMarketplaceOrder( {
			billing_email: 'mv-ajax@example.com',
			items: [ { sku: 'E2E-8401-ALPHA', name: 'Alpha Mug', qty: 2, vendor_id: vendorA } ],
		} );
		const itemA = order.items[ 0 ];
		const metaKey = `wps_tofw_vendor_shipments_${ vendorA }`;

		const beta = await loggedInContext( browser, 'e2e8401beta', BASE_URL );
		const subscriber = await loggedInContext( browser, 'e2e8401subscriber', BASE_URL );
		const alpha = await loggedInContext( browser, 'e2e8401alpha', BASE_URL );
		try {
			// Another vendor cannot add tracking to this order.
			const betaResponse = await saveVendorShipment( beta.request, {
				nonce: await vendorNonce( beta.request ),
				order_id: order.id,
				carrier: 'fedex',
				tracking_number: 'HIJACK-1',
				items: { [ itemA ]: 1 },
			} );
			expect( betaResponse.status ).toBe( 403 );
			expect( betaResponse.json ).toMatchObject( { success: false, data: { message: 'You are not allowed to update this order.' } } );

			// A logged-in non-vendor neither.
			const subResponse = await saveVendorShipment( subscriber.request, {
				nonce: await vendorNonce( subscriber.request ),
				order_id: order.id,
				carrier: 'fedex',
				tracking_number: 'HIJACK-2',
				items: { [ itemA ]: 1 },
			} );
			expect( subResponse.status ).toBe( 403 );

			// The owner with another user's nonce is rejected by the nonce check.
			const forged = await saveVendorShipment( alpha.request, {
				nonce: await vendorNonce( beta.request ),
				order_id: order.id,
				carrier: 'fedex',
				tracking_number: 'FORGED-1',
				items: { [ itemA ]: 1 },
			} );
			expect( forged.status ).toBe( 403 );
			expect( forged.body.trim() ).toBe( '-1' );

			expect( getOrderMeta( order.id, metaKey ) ).toBe( '' );
			expect( getOrderMeta( order.id, 'wps_tofw_package_tracking_number' ) ).toBe( '' );

			// The owner ships 1 of 2 (partial shipment).
			const nonce = await vendorNonce( alpha.request );
			const ok = await saveVendorShipment( alpha.request, { nonce, order_id: order.id, carrier: 'fedex', tracking_number: 'FX-OK-1', items: { [ itemA ]: 1 } } );
			expect( ok.status ).toBe( 200 );
			expect( ok.json ).toMatchObject( { success: true, data: { message: 'Shipment saved.' } } );

			const stored = getOrderMeta< Record< string, { carrier: string; tracking_number: string; items: Record< string, number > } > >( order.id, metaKey );
			const shipments = Object.values( stored );
			expect( shipments ).toHaveLength( 1 );
			expect( shipments[ 0 ] ).toMatchObject( { carrier: 'fedex', tracking_number: 'FX-OK-1', items: { [ itemA ]: 1 } } );
			// FedEx is a carrier the base plugin understands: mirrored to the order tracking meta.
			expect( getOrderMeta( order.id, 'wps_tofw_package_tracking_number' ) ).toBe( 'FX-OK-1' );
			expect( getOrderMeta( order.id, '_wps_tofw_vendor_tracking_mirrored' ) ).toBe( 'yes' );

			// Only 1 left: shipping 2 more is rejected.
			const tooMany = await saveVendorShipment( alpha.request, { nonce, order_id: order.id, carrier: 'ups', tracking_number: 'UPS-X', items: { [ itemA ]: 2 } } );
			expect( tooMany.status ).toBe( 400 );
			expect( tooMany.json ).toMatchObject( { success: false, data: { message: 'The shipped quantity exceeds the quantity left to ship.' } } );
			expect( Object.values( getOrderMeta< Record< string, unknown > >( order.id, metaKey ) ) ).toHaveLength( 1 );
		} finally {
			await Promise.all( [ beta.close(), subscriber.close(), alpha.close() ] );
		}
	} );

	test( 'admin-entered tracking is never overwritten by a vendor', async ( { browser } ) => {
		const order = createMarketplaceOrder( {
			billing_email: 'mv-admin@example.com',
			items: [ { sku: 'E2E-8401-ALPHA', name: 'Alpha Mug', qty: 1, vendor_id: vendorA } ],
			meta: { wps_tofw_selected_shipping_service: 'usps', wps_tofw_package_tracking_number: 'ADMIN-USPS-1' },
		} );

		const alpha = await loggedInContext( browser, 'e2e8401alpha', BASE_URL );
		try {
			const response = await saveVendorShipment( alpha.request, {
				nonce: await vendorNonce( alpha.request ),
				order_id: order.id,
				carrier: 'fedex',
				tracking_number: 'FX-VENDOR-2',
				items: { [ order.items[ 0 ] ]: 1 },
			} );
			expect( response.status ).toBe( 200 );
		} finally {
			await alpha.close();
		}

		expect( getOrderMeta( order.id, 'wps_tofw_package_tracking_number' ) ).toBe( 'ADMIN-USPS-1' );
		expect( getOrderMeta( order.id, 'wps_tofw_selected_shipping_service' ) ).toBe( 'usps' );
		expect( getOrderMeta( order.id, '_wps_tofw_vendor_tracking_mirrored' ) ).toBe( '' );
	} );
} );
