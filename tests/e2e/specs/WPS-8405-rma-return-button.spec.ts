/**
 * WPS-8405: "Return / Exchange" button on the tracking pages.
 *
 * The button shows for delivered (completed) orders only when an RMA plugin is active
 * (forced through the `wps_tofw_is_rma_active` filter by an mu-plugin), the setting
 * `wps_tofw_enable_rma_return_button` is on and the visitor is the order owner or a guest
 * who verified the billing email. Its link never carries the order key.
 */
import { Browser, expect, Page, test } from '@playwright/test';
import {
	createOrder,
	CreatedOrder,
	GUEST_STORAGE_STATE,
	setOptions,
	trackingPageUrl,
} from '../utils';
import {
	ensureCustomer,
	installRmaMuPlugin,
	loginAsCustomer,
	removeRmaMuPlugin,
	RMA_ACTIVE_OPTION,
} from '../utils/wps-8405';

const BUTTON = 'a.wps-tofw-rma-return-button';
const OWNER = { login: 'wps8405_owner', email: 'wps8405-owner@example.com', password: 'Owner-pass-8405!' };
const OTHER = { login: 'wps8405_other', email: 'wps8405-other@example.com', password: 'Other-pass-8405!' };

const BASE_OPTIONS = {
	wps_tofw_enable_rma_return_button: 'on',
	wps_tofw_rma_return_button_label: '',
	wps_tofw_activated_template: 'template1',
	wps_tofw_trackorder_with_google_map: 'no',
	wps_tofw_enable_track_order_using_order_id: 'no',
	[ RMA_ACTIVE_OPTION ]: 'yes',
};

let ownerId = 0;

function trackUrl( orderId: number ): string {
	return `${ trackingPageUrl( 'wps_track_order_page' ) }?${ orderId }`;
}

function ownerOrder( status: string ): CreatedOrder {
	return createOrder( { status, customer_id: ownerId, billing_email: OWNER.email } );
}

/** The href must not reveal the order key in any form. */
function expectNoOrderKey( href: string | null, order: CreatedOrder ): void {
	expect( href ).toBeTruthy();
	expect( href ).not.toContain( order.order_key );
	expect( href ).not.toMatch( /[?&](key|order_key)=/ );
	expect( href ).not.toContain( 'wc_order_' );
}

async function pageAs( browser: Browser, user: { login: string; password: string } | null ): Promise< Page > {
	const context = await browser.newContext( { storageState: GUEST_STORAGE_STATE } );
	const page = await context.newPage();
	// The map template loads Google Maps in the browser: never hit it for real.
	await page.route( /maps\.google(apis)?\.com/, ( route ) => route.abort() );
	if ( user ) {
		await loginAsCustomer( page, user.login, user.password );
	}
	return page;
}

test.describe( 'WPS-8405 Return / Exchange button (free)', () => {
	test.describe.configure( { mode: 'serial' } );

	test.beforeAll( () => {
		installRmaMuPlugin();
		ownerId = ensureCustomer( OWNER.login, OWNER.email, OWNER.password );
		ensureCustomer( OTHER.login, OTHER.email, OTHER.password );
	} );

	test.beforeEach( () => {
		setOptions( BASE_OPTIONS );
	} );

	test.afterAll( () => {
		removeRmaMuPlugin();
		setOptions( {
			wps_tofw_enable_rma_return_button: 'on',
			wps_tofw_trackorder_with_google_map: 'no',
			wps_tofw_enable_track_order_using_order_id: 'no',
		} );
	} );

	test( 'owner sees the button on a completed order and the link has no order key', async ( { browser } ) => {
		const order = ownerOrder( 'completed' );
		const page = await pageAs( browser, OWNER );

		await page.goto( trackUrl( order.id ) );

		const button = page.locator( BUTTON );
		await expect( button ).toHaveCount( 1 );
		await expect( button ).toBeVisible();
		await expect( button ).toHaveText( 'Return / Exchange' );
		const href = await button.getAttribute( 'href' );
		expectNoOrderKey( href, order );
		// Without an RMA request page the owner is sent to the My Account order view.
		expect( href ).toBe( order.view_url );
		await page.context().close();
	} );

	test( 'custom label from settings is used', async ( { browser } ) => {
		setOptions( { wps_tofw_rma_return_button_label: 'Start a return' } );
		const order = ownerOrder( 'completed' );
		const page = await pageAs( browser, OWNER );

		await page.goto( trackUrl( order.id ) );

		await expect( page.locator( BUTTON ) ).toHaveText( 'Start a return' );
		await page.context().close();
	} );

	test( 'no button on a processing order', async ( { browser } ) => {
		const order = ownerOrder( 'processing' );
		const page = await pageAs( browser, OWNER );

		await page.goto( trackUrl( order.id ) );

		await expect( page.locator( '.wps-tofw-order-tracking-section' ) ).toBeVisible();
		await expect( page.locator( BUTTON ) ).toHaveCount( 0 );
		await page.context().close();
	} );

	test( 'no button when the setting is off', async ( { browser } ) => {
		setOptions( { wps_tofw_enable_rma_return_button: 'off' } );
		const order = ownerOrder( 'completed' );
		const page = await pageAs( browser, OWNER );

		await page.goto( trackUrl( order.id ) );

		await expect( page.locator( '.wps-tofw-order-tracking-section' ) ).toBeVisible();
		await expect( page.locator( BUTTON ) ).toHaveCount( 0 );
		await page.context().close();
	} );

	test( 'no button when no RMA plugin is active', async ( { browser } ) => {
		setOptions( { [ RMA_ACTIVE_OPTION ]: 'no' } );
		const order = ownerOrder( 'completed' );
		const page = await pageAs( browser, OWNER );

		await page.goto( trackUrl( order.id ) );

		await expect( page.locator( '.wps-tofw-order-tracking-section' ) ).toBeVisible();
		await expect( page.locator( BUTTON ) ).toHaveCount( 0 );
		await page.context().close();
	} );

	test( 'another logged-in customer gets no button', async ( { browser } ) => {
		const order = ownerOrder( 'completed' );
		const page = await pageAs( browser, OTHER );

		await page.goto( trackUrl( order.id ) );

		await expect( page.locator( 'body' ) ).toContainText( 'is not associated to your account' );
		await expect( page.locator( BUTTON ) ).toHaveCount( 0 );
		await page.context().close();
	} );

	test( 'guest who verified the billing email sees the button without order key', async ( { browser } ) => {
		const order = createOrder( { status: 'completed', billing_email: 'wps8405-guest@example.com' } );
		const page = await pageAs( browser, null );

		await page.goto( trackingPageUrl( 'wps_guest_track_order_page' ) );
		await page.locator( '#order_id' ).fill( String( order.id ) );
		await page.locator( '#order_email' ).fill( order.billing_email );
		await Promise.all( [
			page.waitForURL( ( url ) => url.search === `?${ order.id }` ),
			page.locator( 'input[name="wps_tofw_order_id_submit"]' ).click(),
		] );

		const button = page.locator( BUTTON );
		await expect( button ).toBeVisible();
		const href = await button.getAttribute( 'href' );
		expectNoOrderKey( href, order );
		expect( href ).not.toContain( String( order.id ) + '/?' );
		await page.context().close();
	} );

	test( 'stranger tracking by order id only gets no button', async ( { browser } ) => {
		setOptions( { wps_tofw_enable_track_order_using_order_id: 'on' } );
		const order = createOrder( { status: 'completed', billing_email: 'wps8405-victim@example.com' } );
		const page = await pageAs( browser, null );

		await page.goto( trackUrl( order.id ) );

		// The tracking details are shown (order-id-only mode) but not the return button.
		await expect( page.locator( '.wps-tofw-order-tracking-section' ) ).toBeVisible();
		await expect( page.locator( 'body' ) ).not.toContainText( 'is not associated to your account' );
		await expect( page.locator( BUTTON ) ).toHaveCount( 0 );
		await expect( page.locator( 'body' ) ).not.toContainText( order.order_key );
		await page.context().close();
	} );

	test( 'map template shows the button to the owner', async ( { browser } ) => {
		setOptions( { wps_tofw_trackorder_with_google_map: 'on' } );
		const order = ownerOrder( 'completed' );
		const page = await pageAs( browser, OWNER );

		await page.goto( trackUrl( order.id ) );

		await expect( page.locator( '#map' ) ).toBeAttached();
		const button = page.locator( BUTTON );
		await expect( button ).toBeVisible();
		expectNoOrderKey( await button.getAttribute( 'href' ), order );
		await page.context().close();
	} );

	test( 'map template: no button on a processing order', async ( { browser } ) => {
		setOptions( { wps_tofw_trackorder_with_google_map: 'on' } );
		const order = ownerOrder( 'processing' );
		const page = await pageAs( browser, OWNER );

		await page.goto( trackUrl( order.id ) );

		await expect( page.locator( '#map' ) ).toBeAttached();
		await expect( page.locator( BUTTON ) ).toHaveCount( 0 );
		await page.context().close();
	} );

	test( 'FedEx tracking page shows the button to the owner only', async ( { browser } ) => {
		const order = ownerOrder( 'completed' );
		const fedexUrl = trackingPageUrl( 'wps_fedex_track_order' );

		const owner = await pageAs( browser, OWNER );
		await owner.goto( fedexUrl );
		await owner.locator( '#wps_tofw_order_id' ).fill( String( order.id ) );
		await Promise.all( [
			owner.waitForLoadState( 'load' ),
			owner.locator( 'button[name="wps_track_package"]' ).click(),
		] );
		const button = owner.locator( BUTTON );
		await expect( button ).toBeVisible();
		expectNoOrderKey( await button.getAttribute( 'href' ), order );
		await owner.context().close();

		// A guest who only knows the order ID gets nothing.
		const stranger = await pageAs( browser, null );
		await stranger.goto( fedexUrl );
		await stranger.locator( '#wps_tofw_order_id' ).fill( String( order.id ) );
		await Promise.all( [
			stranger.waitForResponse( ( r ) => r.request().method() === 'POST' && r.url().startsWith( fedexUrl ) ),
			stranger.locator( 'button[name="wps_track_package"]' ).click(),
		] );
		await expect( stranger.locator( '#wps_tofw_order_id' ) ).toBeVisible();
		await expect( stranger.locator( BUTTON ) ).toHaveCount( 0 );
		await stranger.context().close();
	} );
} );
