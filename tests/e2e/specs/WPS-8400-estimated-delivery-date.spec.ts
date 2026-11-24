/**
 * WPS-8400 Estimated delivery date (EDD).
 *
 * A US shipping zone with a flat-rate method gets its own EDD rule (0 processing days,
 * 2-4 transit days); every day is a working day except a holiday tomorrow and there is
 * no cut-off time, so the expected range is independent of the weekday the test runs on:
 * today + 3 .. today + 5 (the holiday pushes both ends by one day). The default rule
 * (10-12 days) is deliberately different to prove the per-method rule is used.
 */
import { expect, Page, test } from '@playwright/test';
import {
	getOrderMeta,
	getProductId,
	GUEST_STORAGE_STATE,
	phpValue,
	setOptions,
	trackingPageUrl,
	updateOrderMeta,
	wpEvalJson,
} from '../utils';
import { addDays, deleteEddZone, EDD_OPTION, ensureEddZone, prepareBlockCart, readOption, restoreOptions, siteToday } from '../utils/wps-8400';

const TOUCHED_OPTIONS = [ EDD_OPTION, 'woocommerce_cheque_settings', 'woocommerce_default_customer_address', 'wps_tofw_activated_template' ];

let saved: Record< string, unknown > = {};
let from = '';
let to = '';
/** Range as rendered everywhere ("Y-m-d – Y-m-d"). */
let range = '';
let productUrl = '';

/** Open the guest tracking page and look up an order. */
async function trackAsGuest( page: Page, orderId: number, email: string ): Promise< void > {
	await page.goto( trackingPageUrl( 'wps_guest_track_order_page' ) );
	await page.locator( '#order_id' ).fill( String( orderId ) );
	await page.locator( '#order_email' ).fill( email );
	await page.locator( 'input[name="wps_tofw_order_id_submit"]' ).click();
	await expect( page.locator( '#wps_delivery' ) ).toBeAttached();
}

test.describe.serial( 'WPS-8400 estimated delivery date', () => {
	test.use( { storageState: GUEST_STORAGE_STATE } );

	test.beforeAll( () => {
		for ( const name of TOUCHED_OPTIONS ) {
			saved[ name ] = readOption( name );
		}
		const instanceId = ensureEddZone();
		const today = siteToday();
		from = addDays( today, 3 );
		to = addDays( today, 5 );
		range = `${ from } – ${ to }`;

		setOptions( {
			[ EDD_OPTION ]: {
				enable: 'on',
				show_product: 'on',
				show_cart: 'on',
				show_email: 'on',
				show_tracking: 'on',
				label: 'Estimated delivery',
				date_format: 'Y-m-d',
				processing_days: 5,
				transit_min: 10,
				transit_max: 12,
				cutoff_time: '',
				non_working_days: [],
				holidays: [ addDays( today, 1 ) ],
				methods: { [ instanceId ]: { processing: 0, min: 2, max: 4, hide: 0 } },
			},
			woocommerce_cheque_settings: { enabled: 'yes', title: 'Check payments' },
			woocommerce_default_customer_address: 'base',
			wps_tofw_activated_template: 'template1',
		} );
		productUrl = wpEvalJson< string >( `return get_permalink( ${ getProductId() } );` );
	} );

	test.afterAll( () => {
		deleteEddZone();
		restoreOptions( saved );
	} );

	test( 'the rule for the flat-rate method skips the holiday', () => {
		const calculated = wpEvalJson< { from: string; to: string } >(
			`return Track_Orders_For_Woocommerce_Estimated_Delivery::calculate( ${ phpValue( ensureEddZone() ) } );`
		);
		expect( calculated ).toEqual( { from, to } );
	} );

	test( 'product page shows the estimate', async ( { page } ) => {
		await page.goto( productUrl );

		const edd = page.locator( '.wps-tofw-edd--product' );
		await expect( edd ).toHaveCount( 1 );
		await expect( edd ).toHaveText( `Estimated delivery: ${ range }` );
	} );

	test( 'block checkout shows the estimate on the shipping rate and the order keeps the same range', async ( { page } ) => {
		const email = `edd-${ Date.now() }@example.com`;
		await page.goto( '/' );
		await prepareBlockCart( page.request, getProductId(), email );

		// Store API exposes it as the rate delivery_time (WooCommerce 9.2+).
		const cart = await ( await page.request.get( '/wp-json/wc/store/v1/cart' ) ).json();
		const rates = cart.shipping_rates[ 0 ].shipping_rates as Array< { name: string; delivery_time?: string; meta_data: Array< { key: string; value: string } > } >;
		const rate = rates.find( ( r ) => r.name === 'EDD Flat rate' );
		expect( rate, 'flat rate of the E2E zone' ).toBeTruthy();
		expect( rate?.delivery_time ).toBe( `Estimated delivery: ${ range }` );

		await page.goto( '/checkout/' );
		const checkout = page.locator( '.wp-block-woocommerce-checkout' );
		await expect( checkout ).toBeVisible();
		await expect( checkout.getByText( `Estimated delivery: ${ range }` ).first() ).toBeVisible();

		const email_input = page.locator( '#email' );
		if ( ( await email_input.inputValue() ) === '' ) {
			await email_input.fill( email );
		}
		await page.locator( '.wc-block-components-checkout-place-order-button' ).click();
		await page.waitForURL( /order-received/, { timeout: 30_000 } );

		// Thank-you page (order details).
		await expect( page.locator( '.wps-tofw-edd--order' ) ).toHaveText( `Estimated delivery: ${ range }` );

		const orderId = Number( new URL( page.url() ).pathname.match( /order-received\/(\d+)/ )?.[ 1 ] );
		expect( orderId ).toBeGreaterThan( 0 );
		expect( getOrderMeta( orderId, 'wps_tofw_edd_from' ) ).toBe( from );
		expect( getOrderMeta( orderId, 'wps_tofw_edd_to' ) ).toBe( to );

		// Order email (customer on-hold email, rendered without sending).
		const emailHtml = wpEvalJson< string >( `
			$order  = wc_get_order( ${ orderId } );
			$emails = WC()->mailer()->get_emails();
			$email  = $emails['WC_Email_Customer_On_Hold_Order'];
			$email->object = $order;
			return $email->get_content_html();
		` );
		expect( emailHtml ).toContain( `Estimated delivery: ${ range }` );

		// Tracking page (guest form) shows the same range.
		await trackAsGuest( page, orderId, email );
		await expect( page.locator( '#wps_delivery .order-info' ) ).toHaveText( `Your order is expected to delivered by ${ range }` );

		// A manual date entered by the admin overrides the calculated range.
		updateOrderMeta( orderId, { wps_tofw_estimated_delivery_date: addDays( to, 10 ) } );
		await trackAsGuest( page, orderId, email );
		const manual = wpEvalJson< string >( `return date_i18n( 'F d, Y ', strtotime( ${ phpValue( addDays( to, 10 ) ) } ) );` );
		await expect( page.locator( '#wps_delivery .order-info' ) ).toHaveText( `Your order is expected to delivered by ${ manual.trim() }` );
		await expect( page.locator( '#wps_delivery .order-info' ) ).not.toContainText( range );
	} );
} );
