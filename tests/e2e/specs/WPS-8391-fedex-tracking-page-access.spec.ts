/**
 * WPS-8391 FedEx tracking page ("Shipment Tracking"): only visitors allowed to view an order
 * (wps_tofw_can_view_order) get its shipment details. The order's customer may; another
 * customer or an unverified guest gets a neutral error and no order data; any visitor may
 * when "track using order id only" is enabled.
 *
 * The order gets a shipment and the "usps" shipping service, so an allowed submit shows the
 * tracking number and the "Service Not Available" notice without calling any carrier API.
 */
import { expect, Page, test } from '@playwright/test';
import { createOrder, getOption, GUEST_STORAGE_STATE, phpValue, setOption, trackingPageUrl, updateOrderMeta, wpEvalJson } from '../utils';

const ORDER_ID_ONLY = 'wps_tofw_enable_track_order_using_order_id';
const ERROR = '.wps_tofw_track_package_error';
const DENIED = 'No tracking details were found for this order.';
const OTHER_LOGIN = 'fedex8391other';
const OTHER_PASSWORD = 'Fedex8391-Other!';

let ownerId = 0;
let orderId = 0;
let trackingNumber = '';
let pageUrl = '';
let savedOrderIdOnly: unknown;

function ensureCustomer( login: string, password: string ): number {
	return wpEvalJson< number >( `
		$user = get_user_by( 'login', ${ phpValue( login ) } );
		if ( $user ) {
			wp_set_password( ${ phpValue( password ) }, $user->ID );
			return (int) $user->ID;
		}
		return (int) wp_insert_user( array(
			'user_login' => ${ phpValue( login ) },
			'user_email' => ${ phpValue( `${ login }@example.com` ) },
			'user_pass'  => ${ phpValue( password ) },
			'role'       => 'customer',
		) );
	` );
}

async function loginAsCustomer( page: Page, login: string, password: string ): Promise< void > {
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( login );
	await page.locator( '#user_pass' ).fill( password );
	await Promise.all( [ page.waitForLoadState( 'domcontentloaded' ), page.locator( '#wp-submit' ).click() ] );
	await expect.poll( async () => ( await page.context().cookies() ).some( ( c ) => c.name.startsWith( 'wordpress_logged_in_' ) ) ).toBe( true );
}

async function submit( page: Page, id: number ): Promise< void > {
	await page.goto( pageUrl );
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

async function expectDenied( page: Page ): Promise< void > {
	await expect( page.locator( ERROR ) ).toContainText( DENIED );
	await expect( page.locator( '.wps_tofw_shipment_tracking_warning_msg' ) ).toHaveCount( 0 );
	await expect( page.locator( 'body' ) ).not.toContainText( trackingNumber );
}

test.describe.serial( 'WPS-8391 FedEx tracking page access check', () => {
	test.beforeAll( () => {
		savedOrderIdOnly = getOption( ORDER_ID_ONLY );
		setOption( ORDER_ID_ONLY, 'no' );
		pageUrl = trackingPageUrl( 'wps_fedex_track_order' );
		expect( pageUrl, 'FedEx tracking page should exist' ).not.toBe( '' );

		ownerId = ensureCustomer( 'fedex8391owner', 'Fedex8391-Owner!' );
		ensureCustomer( OTHER_LOGIN, OTHER_PASSWORD );

		const order = createOrder( { status: 'completed', billing_email: 'fedex8391owner@example.com', customer_id: ownerId } );
		orderId = order.id;
		trackingNumber = `9400E2E8391${ orderId }`;
		const stored = wpEvalJson< boolean >( `
			$result = Track_Orders_For_Woocommerce_Tracking_Service::add_tracking(
				${ orderId },
				array( 'carrier' => 'usps', 'tracking_number' => ${ phpValue( trackingNumber ) }, 'notify_customer' => false )
			);
			return ! is_wp_error( $result );
		` );
		expect( stored ).toBe( true );
		// add_tracking() syncs the legacy service meta to the shipment carrier; set it afterwards.
		updateOrderMeta( orderId, { wps_tofw_selected_shipping_service: 'usps' } );
	} );

	test.afterAll( () => {
		setOption( ORDER_ID_ONLY, savedOrderIdOnly === false ? 'no' : savedOrderIdOnly );
	} );

	test( 'the order customer gets the shipment details', async ( { browser } ) => {
		const context = await browser.newContext( { storageState: GUEST_STORAGE_STATE } );
		const page = await context.newPage();
		await loginAsCustomer( page, 'fedex8391owner', 'Fedex8391-Owner!' );
		await submit( page, orderId );
		await expectTracked( page );
		await context.close();
	} );

	test( 'another logged-in customer gets no order data', async ( { browser } ) => {
		const context = await browser.newContext( { storageState: GUEST_STORAGE_STATE } );
		const page = await context.newPage();
		await loginAsCustomer( page, OTHER_LOGIN, OTHER_PASSWORD );
		await submit( page, orderId );
		await expectDenied( page );
		await context.close();
	} );

	test( 'the logged-in admin who is not the customer gets no order data', async ( { page } ) => {
		await submit( page, orderId );
		await expectDenied( page );
	} );

	test.describe( 'as a guest', () => {
		test.use( { storageState: GUEST_STORAGE_STATE } );

		test( 'an unverified guest gets no order data', async ( { page } ) => {
			await submit( page, orderId );
			await expectDenied( page );
		} );

		test( 'an unknown order id gets the same neutral message', async ( { page } ) => {
			await submit( page, 999999999 );
			await expect( page.locator( ERROR ) ).toContainText( DENIED );
		} );

		test( 'any visitor may track when "track using order id only" is enabled', async ( { page } ) => {
			setOption( ORDER_ID_ONLY, 'on' );
			try {
				await submit( page, orderId );
				await expectTracked( page );
			} finally {
				setOption( ORDER_ID_ONLY, 'no' );
			}
		} );
	} );
} );
