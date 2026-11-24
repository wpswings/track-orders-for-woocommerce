/**
 * WPS-8403: "Where is my order?" assistant widget.
 *
 * The widget verifies the order (order number + billing e-mail, or the logged-in owner)
 * before answering, returns one generic error with support links on any failure,
 * rate limits verification, and never exposes the LLM API key. The LLM API is faked
 * by an mu-plugin (pre_http_request); no external request is made.
 */
import { expect, test } from '@playwright/test';
import { createOrder, GUEST_STORAGE_STATE, setOption, settingsTabUrl } from '../utils';
import {
	clearWismoRateLimits,
	createCustomer,
	disableWismo,
	enableWismo,
	installWismoMuPlugin,
	lastLlmRequest,
	loginAs,
	openWismo,
	removeWismoMuPlugin,
	submitVerify,
	wismo,
	WISMO_API_KEY,
	WISMO_LLM_REPLY,
	WISMO_SUPPORT_EMAIL,
	WISMO_SUPPORT_URL,
} from '../utils/wps-8403';

const GENERIC_ERROR = 'We could not verify that order. Please check the order number and billing e-mail.';

test.describe.configure( { mode: 'serial' } );

test.describe( 'WPS-8403 Where is my order? assistant', () => {
	test.beforeAll( () => {
		installWismoMuPlugin();
		enableWismo();
	} );

	test.afterAll( () => {
		disableWismo();
		removeWismoMuPlugin();
	} );

	test.beforeEach( () => {
		clearWismoRateLimits();
		setOption( 'e2e_wismo_verify_limit', 0 );
	} );

	test.describe( 'as a guest', () => {
		test.use( { storageState: GUEST_STORAGE_STATE } );

		test( 'wrong e-mail gets the generic error with support links, the right e-mail gets the status', async ( { page } ) => {
			const order = createOrder( { status: 'processing', billing_email: 'wismo-guest@example.com' } );

			await page.goto( '/' );
			const w = await openWismo( page );
			await expect( w.orderInput ).toBeVisible();

			// Wrong e-mail: generic message + support links, no order data.
			const failure = await submitVerify( page, order.id, 'someone-else@example.com' );
			await expect( failure ).toContainText( GENERIC_ERROR );
			await expect( failure.locator( `a[href="mailto:${ WISMO_SUPPORT_EMAIL }"]` ) ).toBeVisible();
			await expect( failure.locator( `a[href="${ WISMO_SUPPORT_URL }"]` ) ).toBeVisible();
			await expect( w.root ).not.toContainText( 'being processed' );

			// An order that does not exist gives exactly the same message.
			const missing = await submitVerify( page, 99999999, 'someone-else@example.com' );
			await expect( missing ).toContainText( GENERIC_ERROR );

			// Correct e-mail (different case): verified, status answer, chat opens.
			const success = await submitVerify( page, order.id, 'WISMO-Guest@Example.com' );
			await expect( success ).toContainText( `Order #${ order.id } is being processed` );
			await expect( w.askInput ).toBeVisible();
		} );

		test( 'questions are answered through the (faked) LLM with minimal order data only', async ( { page } ) => {
			const order = createOrder( {
				status: 'processing',
				billing_email: 'wismo-llm@example.com',
				billing_first_name: 'Privatefirst',
				billing_last_name: 'Privatelast',
				meta: {
					wps_tofw_package_tracking_number: 'TRK8403',
					wps_tofw_selected_shipping_service: 'usps',
				},
			} );

			await page.goto( '/' );
			await openWismo( page );
			await expect( await submitVerify( page, order.id, order.billing_email ) ).toContainText( `Order #${ order.id }` );

			const w = wismo( page );
			await w.askInput.fill( 'What is my tracking number?' );
			const answered = page.waitForResponse( ( r ) => ( r.request().postData() || '' ).includes( 'action=wps_tofw_wismo_ask' ) );
			await w.askButton.click();
			const body = await ( await answered ).json();

			expect( body.success ).toBe( true );
			expect( body.data.source ).toBe( 'llm' );
			await expect( w.lastBot ).toContainText( WISMO_LLM_REPLY );

			const sent = lastLlmRequest();
			expect( sent, 'the fake LLM endpoint received the request' ).not.toBe( false );
			if ( sent ) {
				expect( sent.body ).toContain( 'TRK8403' );
				expect( sent.body ).toContain( 'What is my tracking number?' );
				expect( sent.body ).not.toContain( order.billing_email );
				expect( sent.body ).not.toContain( 'Privatefirst' );
				expect( sent.body ).not.toContain( 'Privatelast' );
				expect( sent.body ).not.toContain( WISMO_API_KEY );
			}
		} );

		test( 'verification is rate limited per IP with the support links', async ( { page } ) => {
			setOption( 'e2e_wismo_verify_limit', 2 );
			const order = createOrder( { billing_email: 'wismo-rl@example.com' } );

			await page.goto( '/' );
			await openWismo( page );
			await expect( await submitVerify( page, order.id, 'bad1@example.com' ) ).toContainText( GENERIC_ERROR );
			await expect( await submitVerify( page, order.id, 'bad2@example.com' ) ).toContainText( GENERIC_ERROR );

			// Third attempt is blocked even with the right e-mail.
			const blocked = await submitVerify( page, order.id, order.billing_email );
			await expect( blocked ).toContainText( 'Too many attempts' );
			await expect( blocked.locator( `a[href="mailto:${ WISMO_SUPPORT_EMAIL }"]` ) ).toBeVisible();
			await expect( wismo( page ).askInput ).toHaveCount( 0 );
		} );

		test( 'nonce endpoint returns a fresh nonce and a forged session token is rejected', async ( { page } ) => {
			await page.goto( '/' );
			const nonce = await page.request.post( '/wp-admin/admin-ajax.php', {
				form: { action: 'wps_tofw_wismo_nonce' },
			} );
			expect( nonce.headers()[ 'cache-control' ] || '' ).toContain( 'no-cache' );
			const nonceBody = await nonce.json();
			expect( nonceBody.success ).toBe( true );
			expect( nonceBody.data.nonce ).toMatch( /^[a-f0-9]{10}$/ );

			const ask = await page.request.post( '/wp-admin/admin-ajax.php', {
				form: { action: 'wps_tofw_wismo_ask', nonce: nonceBody.data.nonce, token: 'MXwwfDk5OTk5OTk5OTl8eA.forged', question: 'status' },
			} );
			expect( ask.status() ).toBe( 401 );
			expect( ( await ask.json() ).data.code ).toBe( 'session_expired' );

			const badNonce = await page.request.post( '/wp-admin/admin-ajax.php', {
				form: { action: 'wps_tofw_wismo_verify', nonce: 'bad', order_id: '1', email: 'a@example.com' },
			} );
			expect( badNonce.status() ).toBe( 403 );
			expect( ( await badNonce.json() ).data.code ).toBe( 'bad_nonce' );
		} );

		test( 'page source never contains the API key', async ( { page } ) => {
			const response = await page.goto( '/' );
			const html = ( await response?.text() ) || '';

			expect( html ).toContain( 'wps_tofw_wismo' );
			expect( html ).not.toContain( WISMO_API_KEY );
			expect( html ).not.toContain( 'llm.e2e.test' );
			await expect( wismo( page ).launcher ).toBeVisible();
			expect( await page.content() ).not.toContain( WISMO_API_KEY );
		} );
	} );

	test.describe( 'as a logged-in customer', () => {
		test.use( { storageState: GUEST_STORAGE_STATE } );

		test( 'owner can pick an own order; another customer\'s order is rejected even with its e-mail', async ( { page } ) => {
			const owner = createCustomer( 'wismoowner' );
			const other = createCustomer( 'wismoother' );
			const ownerOrder = createOrder( { status: 'completed', customer_id: owner.id, billing_email: owner.email } );

			// Another customer: no own orders -> e-mail form; the owner's order is refused.
			await loginAs( page, other );
			await page.goto( '/' );
			const w = await openWismo( page );
			await expect( w.orderInput ).toBeVisible();
			const rejected = await submitVerify( page, ownerOrder.id, owner.email );
			await expect( rejected ).toContainText( GENERIC_ERROR );
			await expect( w.askInput ).toHaveCount( 0 );

			// The account endpoint does not accept someone else's order either.
			const nonce = ( await ( await page.request.post( '/wp-admin/admin-ajax.php', { form: { action: 'wps_tofw_wismo_nonce' } } ) ).json() ).data.nonce;
			const account = await page.request.post( '/wp-admin/admin-ajax.php', {
				form: { action: 'wps_tofw_wismo_verify', nonce, mode: 'account', order_id: String( ownerOrder.id ) },
			} );
			const accountBody = await account.json();
			expect( accountBody.success ).toBe( false );
			expect( accountBody.data.code ).toBe( 'not_verified' );

			// The owner sees the order in the picker and gets an answer without typing the e-mail.
			await page.context().clearCookies();
			await loginAs( page, owner );
			await page.goto( '/' );
			const ow = await openWismo( page );
			const chip = ow.chips.filter( { hasText: `#${ ownerOrder.id }` } );
			await expect( chip ).toBeVisible();
			await chip.click();
			await expect( ow.lastBot ).toContainText( `Order #${ ownerOrder.id } has been completed` );
			await expect( ow.askInput ).toBeVisible();
		} );
	} );

	test( 'admin settings page never prints the saved API key', async ( { page } ) => {
		const response = await page.goto( settingsTabUrl( 'track-orders-for-woocommerce-wismo-assistant' ) );
		const html = ( await response?.text() ) || '';

		await expect( page.locator( '#wps_tofw_wismo_llm_api_key' ) ).toHaveValue( '' );
		expect( html ).not.toContain( WISMO_API_KEY );
	} );

	test.describe( 'when disabled', () => {
		test.use( { storageState: GUEST_STORAGE_STATE } );

		test( 'no widget is rendered and the nonce endpoint is closed', async ( { page } ) => {
			setOption( 'wps_tofw_wismo_enable', '' );
			try {
				const response = await page.goto( '/' );
				expect( ( await response?.text() ) || '' ).not.toContain( 'wps-tofw-wismo-widget.js' );
				await expect( page.locator( '#wps-tofw-wismo' ) ).toHaveCount( 0 );

				const nonce = await page.request.post( '/wp-admin/admin-ajax.php', { form: { action: 'wps_tofw_wismo_nonce' } } );
				expect( nonce.status() ).toBe( 403 );
				expect( ( await nonce.json() ).data.code ).toBe( 'disabled' );
			} finally {
				setOption( 'wps_tofw_wismo_enable', 'on' );
			}
		} );
	} );
} );
