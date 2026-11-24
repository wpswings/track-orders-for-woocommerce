/**
 * WPS-8391 FedEx tracking page ("Shipment Tracking"): the form carries a real nonce, a submit
 * without a valid nonce is rejected without showing order data, and the submitted order id is
 * never written to the site option wps_tofw_user_order_id.
 *
 * The order gets a shipment (tracking number) and the "usps" shipping service, so a processed
 * submit shows the tracking number and the "Service Not Available" notice without calling
 * any carrier API.
 */
import { BrowserContext, expect, Page, test } from '@playwright/test';
import { createOrder, deleteOption, getOption, GUEST_STORAGE_STATE, phpValue, setOption, trackingPageUrl, updateOrderMeta, wpEval, wpEvalJson } from '../utils';

const BASE_URL = process.env.WP_BASE_URL || 'http://localhost:8888';
const OPTION = 'wps_tofw_user_order_id';
const NONCE_FIELD = 'input[name="wps_track_package_nonce_name"]';
const ERROR = '.wps_tofw_track_package_error';

let orderId = 0;
let trackingNumber = '';
let pageUrl = '';

function createTrackedOrder(): void {
	const order = createOrder( { status: 'completed', billing_email: 'fedex8391@example.com' } );
	orderId = order.id;
	trackingNumber = `1Z8391E2E${ orderId }`;
	const stored = wpEvalJson< boolean >( `
		$result = Track_Orders_For_Woocommerce_Tracking_Service::add_tracking(
			${ orderId },
			array( 'carrier' => 'ups', 'tracking_number' => ${ phpValue( trackingNumber ) }, 'notify_customer' => false )
		);
		return ! is_wp_error( $result );
	` );
	expect( stored ).toBe( true );
	// add_tracking() syncs the legacy service meta to the shipment carrier; set it afterwards.
	updateOrderMeta( orderId, { wps_tofw_selected_shipping_service: 'usps' } );
}

async function openForm( page: Page ): Promise< void > {
	await page.goto( pageUrl );
	await expect( page.locator( '#wps_tofw_order_id' ) ).toBeVisible();
}

async function submit( page: Page, id: number ): Promise< void > {
	await page.locator( '#wps_tofw_order_id' ).fill( String( id ) );
	await Promise.all( [
		page.waitForResponse( ( response ) => response.request().method() === 'POST' && response.url().startsWith( pageUrl ) ),
		page.locator( 'button[name="wps_track_package"]' ).click(),
	] );
	await page.waitForLoadState( 'domcontentloaded' );
}

async function expectTracked( page: Page ): Promise< void > {
	await expect( page.locator( '.wps_tofw_shipment_tracking_warning_msg' ) ).toBeVisible();
	await expect( page.locator( 'body' ) ).toContainText( trackingNumber );
	await expect( page.locator( ERROR ) ).toHaveCount( 0 );
}

async function expectRejected( page: Page ): Promise< void > {
	await expect( page.locator( ERROR ) ).toBeVisible();
	await expect( page.locator( '.wps_tofw_shipment_tracking_warning_msg' ) ).toHaveCount( 0 );
	await expect( page.locator( 'body' ) ).not.toContainText( trackingNumber );
	// The form is shown again with a fresh nonce.
	await expect( page.locator( NONCE_FIELD ) ).toHaveAttribute( 'value', /\S+/ );
}

test.describe.serial( 'WPS-8391 FedEx tracking page nonce', () => {
	let saved: unknown;

	test.beforeAll( () => {
		saved = getOption( OPTION );
		pageUrl = trackingPageUrl( 'wps_fedex_track_order' );
		expect( pageUrl, 'FedEx tracking page should exist' ).not.toBe( '' );
		createTrackedOrder();
	} );

	test.afterAll( () => {
		if ( saved === false ) {
			deleteOption( OPTION );
		} else {
			setOption( OPTION, saved );
		}
	} );

	test( 'the form posts a non-empty nonce and the order owner gets the tracking details', async ( { page } ) => {
		// Default project: logged in as admin, who owns no order; give the order to the admin.
		wpEval( `$o = wc_get_order( ${ orderId } ); $o->set_customer_id( get_user_by( 'login', 'admin' )->ID ); $o->save();` );
		await openForm( page );
		const nonce = await page.locator( NONCE_FIELD ).getAttribute( 'value' );
		expect( nonce ).toMatch( /^[a-f0-9]{10}$/ );

		const posted = page.waitForRequest( ( request ) => request.method() === 'POST' && request.url().startsWith( pageUrl ) );
		await submit( page, orderId );
		const body = new URLSearchParams( ( await posted ).postData() || '' );
		expect( body.get( 'wps_track_package_nonce_name' ) ).toBe( nonce );

		await expectTracked( page );
	} );

	test.describe( 'as a guest', () => {
		test.use( { storageState: GUEST_STORAGE_STATE } );

		test( 'a submit with a forged nonce is rejected and shows no order data', async ( { page } ) => {
			await openForm( page );
			await page.locator( NONCE_FIELD ).evaluate( ( input: HTMLInputElement ) => {
				input.value = 'forged1234';
			} );
			await submit( page, orderId );
			await expectRejected( page );
		} );

		test( 'a submit without a nonce is rejected and shows no order data', async ( { page } ) => {
			await openForm( page );
			await page.locator( NONCE_FIELD ).evaluate( ( input: HTMLInputElement ) => input.remove() );
			await submit( page, orderId );
			await expectRejected( page );
		} );

		test( 'a cross-site style POST without the form nonce is rejected', async ( { browser } ) => {
			const context: BrowserContext = await browser.newContext( { storageState: GUEST_STORAGE_STATE, baseURL: BASE_URL } );
			try {
				const response = await context.request.post( pageUrl, {
					form: { wps_user_order_id: String( orderId ), wps_track_package: '' },
				} );
				expect( response.ok() ).toBe( true );
				const html = await response.text();
				expect( html ).toContain( 'wps_tofw_track_package_error' );
				expect( html ).not.toContain( trackingNumber );
				expect( html ).not.toContain( 'wps_tofw_shipment_tracking_warning_msg' );
			} finally {
				await context.close();
			}
		} );

		test( 'a visitor submit does not create the wps_tofw_user_order_id option', async ( { page } ) => {
			deleteOption( OPTION );
			await openForm( page );
			await submit( page, orderId );
			await expectTracked( page );
			expect( getOption( OPTION ) ).toBe( false );
		} );

		test( 'a visitor submit does not change an existing wps_tofw_user_order_id option', async ( { page } ) => {
			setOption( OPTION, 'untouched-8391' );
			await openForm( page );
			await submit( page, orderId );
			await expectTracked( page );
			expect( getOption( OPTION ) ).toBe( 'untouched-8391' );
		} );
	} );
} );
