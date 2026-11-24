/**
 * WPS-8395: admin review request notice after tracking was added to 10 orders.
 */
import { Browser, expect, Page, test } from '@playwright/test';
import { GUEST_STORAGE_STATE, loginAsAdmin, SETTINGS_PAGE } from '../utils';
import {
	countTrackedOrders,
	createTrackedOrders,
	ensureUser,
	getReviewNoticeState,
	resetReviewNotice,
	serverTime,
	setReviewNoticeState,
} from '../utils/wps-8395';

const THRESHOLD = 10;
const SNOOZE_SECONDS = 14 * 24 * 60 * 60;
const NOTICE = '#wps-tofw-review-notice';

/** Wait for the notice AJAX request with the given action. */
function waitForNoticeAjax( page: Page, action: string ) {
	return page.waitForResponse(
		( response ) =>
			response.url().includes( 'admin-ajax.php' ) &&
			( response.request().postData() || '' ).includes( `review_action=${ action }` )
	);
}

/** Make sure at least THRESHOLD orders carry a tracking number. */
function ensureThresholdReached(): void {
	const missing = THRESHOLD - countTrackedOrders();
	if ( missing > 0 ) {
		createTrackedOrders( missing );
	}
}

async function loginAs( browser: Browser, login: string, role: string ) {
	ensureUser( login, role );
	const context = await browser.newContext( { storageState: GUEST_STORAGE_STATE } );
	const page = await context.newPage();
	await loginAsAdmin( page, login, 'password' );
	return { context, page };
}

test.describe.configure( { mode: 'serial' } );

test.describe( 'WPS-8395 review request notice', () => {
	test.beforeEach( () => {
		resetReviewNotice();
	} );

	test.afterAll( () => {
		// Leave the notice out of the way of other specs.
		setReviewNoticeState( { status: 'dismissed', snooze_until: 0, reached_at: 1 } );
	} );

	test( 'is not shown before 10 orders have tracking', async ( { page } ) => {
		const existing = countTrackedOrders();
		test.skip( existing >= THRESHOLD - 1, `site already has ${ existing } tracked orders` );

		createTrackedOrders( THRESHOLD - 1 - existing );
		await page.goto( '/wp-admin/index.php' );

		await expect( page.locator( '#wpbody-content h1' ) ).toBeVisible();
		await expect( page.locator( NOTICE ) ).toHaveCount( 0 );
		expect( getReviewNoticeState(), 'threshold not recorded yet' ).toBeNull();
	} );

	test( 'appears on the dashboard once 10 orders have tracking, with all buttons', async ( { page } ) => {
		ensureThresholdReached();
		await page.goto( '/wp-admin/index.php' );

		const notice = page.locator( NOTICE );
		await expect( notice ).toBeVisible();
		await expect( notice ).toHaveClass( /is-dismissible/ );
		await expect( notice ).toContainText( `${ THRESHOLD }+ orders` );
		await expect( notice.locator( '[data-review-action="rated"]' ) ).toHaveText( 'Rate us' );
		await expect( notice.locator( '[data-review-action="rated"]' ) ).toHaveAttribute( 'href', /wordpress\.org\/support\/plugin\/track-orders-for-woocommerce\/reviews/ );
		await expect( notice.locator( '.wps-tofw-review-help' ) ).toHaveText( 'Need help' );
		await expect( notice.locator( '[data-review-action="later"]' ) ).toHaveText( 'Later' );
		await expect( notice.locator( '[data-review-action="dismiss"]' ) ).toHaveText( "Don't ask again" );

		const state = getReviewNoticeState();
		expect( state?.reached_at, 'threshold time recorded' ).toBeGreaterThan( 0 );
	} );

	test( 'is shown on the order screens and plugin page only', async ( { page } ) => {
		ensureThresholdReached();

		for ( const url of [ '/wp-admin/admin.php?page=wc-orders', SETTINGS_PAGE ] ) {
			await page.goto( url );
			await expect( page.locator( NOTICE ), `visible on ${ url }` ).toBeVisible();
		}

		for ( const url of [ '/wp-admin/options-general.php', '/wp-admin/admin.php?page=wc-settings', '/wp-admin/plugins.php', '/wp-admin/edit.php' ] ) {
			await page.goto( url );
			await expect( page.locator( '#wpbody-content' ) ).toBeVisible();
			await expect( page.locator( NOTICE ), `hidden on ${ url }` ).toHaveCount( 0 );
		}
	} );

	test( '"Later" hides the notice and snoozes it for 14 days', async ( { page } ) => {
		ensureThresholdReached();
		await page.goto( '/wp-admin/index.php' );
		await expect( page.locator( NOTICE ) ).toBeVisible();

		const before = serverTime();
		const ajax = waitForNoticeAjax( page, 'later' );
		await page.locator( `${ NOTICE } [data-review-action="later"]` ).click();
		const response = await ajax;
		expect( ( await response.json() ).success ).toBe( true );
		await expect( page.locator( NOTICE ) ).toHaveCount( 0 );

		const state = getReviewNoticeState();
		expect( state?.status ).toBe( 'active' );
		expect( state?.snooze_until ).toBeGreaterThanOrEqual( before + SNOOZE_SECONDS );
		expect( state?.snooze_until ).toBeLessThanOrEqual( serverTime() + SNOOZE_SECONDS );

		await page.reload();
		await expect( page.locator( '#wpbody-content h1' ) ).toBeVisible();
		await expect( page.locator( NOTICE ) ).toHaveCount( 0 );

		// Once the snooze is over the notice comes back.
		setReviewNoticeState( { ...state, snooze_until: serverTime() - 60 } );
		await page.reload();
		await expect( page.locator( NOTICE ) ).toBeVisible();
	} );

	test( 'the core dismiss (X) button snoozes like "Later"', async ( { page } ) => {
		ensureThresholdReached();
		await page.goto( '/wp-admin/index.php' );
		await expect( page.locator( NOTICE ) ).toBeVisible();

		const ajax = waitForNoticeAjax( page, 'later' );
		await page.locator( `${ NOTICE } .notice-dismiss` ).click();
		await ajax;

		const state = getReviewNoticeState();
		expect( state?.status ).toBe( 'active' );
		expect( state?.snooze_until ).toBeGreaterThan( serverTime() + SNOOZE_SECONDS - 3600 );
	} );

	test( '"Don\'t ask again" hides the notice for good', async ( { page } ) => {
		ensureThresholdReached();
		await page.goto( '/wp-admin/index.php' );
		await expect( page.locator( NOTICE ) ).toBeVisible();

		const ajax = waitForNoticeAjax( page, 'dismiss' );
		await page.locator( `${ NOTICE } [data-review-action="dismiss"]` ).click();
		await ajax;
		await expect( page.locator( NOTICE ) ).toHaveCount( 0 );

		const state = getReviewNoticeState();
		expect( state?.status ).toBe( 'dismissed' );

		// More tracked orders and no snooze: still never shown again.
		createTrackedOrders( 1 );
		for ( const url of [ '/wp-admin/index.php', '/wp-admin/admin.php?page=wc-orders', SETTINGS_PAGE ] ) {
			await page.goto( url );
			await expect( page.locator( '#wpbody-content' ) ).toBeVisible();
			await expect( page.locator( NOTICE ), `hidden on ${ url }` ).toHaveCount( 0 );
		}
	} );

	test( '"Rate us" opens the review page and stops the notice', async ( { page, context } ) => {
		ensureThresholdReached();
		// Keep the test offline: answer the wordpress.org request locally.
		await context.route( 'https://wordpress.org/**', ( route ) => route.fulfill( { status: 200, contentType: 'text/html', body: '<p>review</p>' } ) );
		await page.goto( '/wp-admin/index.php' );
		await expect( page.locator( NOTICE ) ).toBeVisible();

		const ajax = waitForNoticeAjax( page, 'rated' );
		const popup = context.waitForEvent( 'page' );
		await page.locator( `${ NOTICE } [data-review-action="rated"]` ).click();
		const reviewPage = await popup;
		await ajax;

		expect( reviewPage.url() ).toContain( 'wordpress.org/support/plugin/track-orders-for-woocommerce/reviews' );
		await reviewPage.close();
		await expect( page.locator( NOTICE ) ).toHaveCount( 0 );
		expect( getReviewNoticeState()?.status ).toBe( 'rated' );
	} );

	test( 'shop managers see the notice, editors do not', async ( { browser } ) => {
		ensureThresholdReached();

		const manager = await loginAs( browser, 'e2e_8395_manager', 'shop_manager' );
		await manager.page.goto( '/wp-admin/index.php' );
		await expect( manager.page.locator( NOTICE ) ).toBeVisible();
		await manager.context.close();

		const editor = await loginAs( browser, 'e2e_8395_editor', 'editor' );
		await editor.page.goto( '/wp-admin/index.php' );
		await expect( editor.page.locator( '#wpbody-content h1' ) ).toBeVisible();
		await expect( editor.page.locator( NOTICE ) ).toHaveCount( 0 );
		await editor.context.close();
	} );

	test( 'the AJAX endpoint rejects a forged nonce', async ( { page } ) => {
		setReviewNoticeState( { status: 'active', snooze_until: 0, reached_at: 1 } );

		const forged = await page.request.post( '/wp-admin/admin-ajax.php', {
			form: { action: 'wps_tofw_review_notice_action', nonce: 'forged', review_action: 'dismiss' },
		} );

		expect( forged.status() ).toBe( 403 );
		expect( getReviewNoticeState()?.status ).toBe( 'active' );
	} );
} );
