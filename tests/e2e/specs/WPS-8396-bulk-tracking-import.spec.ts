/**
 * WPS-8396: bulk tracking import (CSV, dry-run preview, error report, Action Scheduler batches)
 * and the REST API tofw-route/v1/orders/{id}/tracking.
 */
import { APIRequestContext, expect, request as playwrightRequest, test } from '@playwright/test';
import {
	createOrder,
	getOrderMeta,
	getOrderStatus,
	GUEST_STORAGE_STATE,
	settingsTabUrl,
	updateOrderMeta,
} from '../utils';
import {
	basicAuth,
	capturedMails,
	createApplicationPassword,
	deleteApplicationPasswords,
	ensureUser,
	installTestMuPlugin,
	removeTestMuPlugin,
	runScheduledActions,
	shipmentsStorage,
} from '../utils/wps-8396';

const IMPORT_TAB = 'track-orders-for-woocommerce-tracking-import';
const TRACKING_EMAIL_SUBJECT = 'Your Order Status for Order #';

interface Shipment {
	id: string;
	carrier: string;
	carrier_label: string;
	tracking_number: string;
	ship_date: string;
	items: Array< { item_id: number; sku: string; name: string; qty: number } >;
	source: string;
}

const trackingRoute = ( orderId: number ) => `/?rest_route=/tofw-route/v1/orders/${ orderId }/tracking`;

const trackingMailsTo = ( email: string ) =>
	capturedMails().filter( ( mail ) => mail.to === email && mail.subject.startsWith( TRACKING_EMAIL_SUBJECT ) );

test.describe.configure( { mode: 'serial' } );

test.describe( 'WPS-8396 bulk tracking import and REST', () => {
	test.beforeAll( () => {
		installTestMuPlugin();
	} );

	test.afterAll( () => {
		removeTestMuPlugin();
	} );

	test( 'admin imports a CSV: dry-run preview, safe error report, background import', async ( { page } ) => {
		const run = Date.now();
		const orderA = createOrder( { status: 'processing', billing_email: `a-${ run }@example.com` } );
		const orderB = createOrder( { status: 'processing', billing_email: `b-${ run }@example.com`, quantity: 2 } );
		// Tracking saved earlier through the order edit screen (legacy single tracking number).
		updateOrderMeta( orderB.id, {
			wps_tofw_package_tracking_number: `LEGACY-${ run }`,
			wps_tofw_selected_shipping_service: 'USPS',
		} );

		const trackA = `E2E8396A${ run }`;
		const trackB = `E2E8396B${ run }`;
		const csv = [
			'order_id,carrier,tracking_number,ship_date,items,status,notify_customer',
			`${ orderA.id },UPS,${ trackA },2026-11-20,,completed,no`,
			`#${ orderB.id },DHL,${ trackB },,E2E-SIMPLE:1,,yes`,
			'=1+1,UPS,FORMULA1,,,,',
			'99999999,UPS,NOORDER1,,,,',
			`${ orderA.id },UPS,${ trackA.toLowerCase() },,,,`,
		].join( '\n' );

		await page.goto( settingsTabUrl( IMPORT_TAB ) );
		await expect( page.locator( '#wps-tofw-import' ) ).toBeVisible();

		await page.locator( '#wps-tofw-import-file' ).setInputFiles( {
			name: 'tracking.csv',
			mimeType: 'text/csv',
			buffer: Buffer.from( csv, 'utf8' ),
		} );
		await page.locator( '#wps-tofw-import-upload' ).click();

		// Dry-run preview.
		const preview = page.locator( '#wps-tofw-import-preview' );
		await expect( preview ).toBeVisible();
		const summary = preview.locator( '.wps-tofw-import__summary' );
		await expect( summary ).toContainText( 'Rows: 5' );
		await expect( summary.locator( '.is-valid' ) ).toHaveText( 'Valid: 2' );
		await expect( summary.locator( '.is-invalid' ) ).toHaveText( 'Invalid: 3' );

		const rows = preview.locator( 'tbody tr' );
		await expect( rows ).toHaveCount( 5 );
		// hasText is case-insensitive, so rows are picked by their CSV line number (first cell).
		const rowAtLine = ( line: number ) =>
			rows.filter( { has: page.locator( 'td:first-child', { hasText: new RegExp( `^${ line }$` ) } ) } );
		await expect( rowAtLine( 2 ).locator( 'td.is-valid' ) ).toBeVisible();
		await expect( rowAtLine( 3 ).locator( 'td.is-valid' ) ).toBeVisible();
		await expect( rowAtLine( 4 ) ).toContainText( 'Order ID must be a number.' );
		await expect( rowAtLine( 5 ) ).toContainText( 'Order not found.' );
		await expect( rowAtLine( 6 ) ).toContainText( 'Duplicate of line 2' );

		// The preview is a dry run: nothing is written yet.
		expect( getOrderMeta( orderA.id, 'wps_tofw_package_tracking_number' ) ).toBe( '' );

		// Error report: invalid rows only, spreadsheet formulas neutralized.
		const reportLink = page.locator( '#wps-tofw-import-report' );
		await expect( reportLink ).toBeVisible();
		const report = await page.request.get( ( await reportLink.getAttribute( 'href' ) ) as string );
		expect( report.status() ).toBe( 200 );
		expect( report.headers()[ 'content-type' ] ).toContain( 'text/csv' );
		const reportText = await report.text();
		const reportLines = reportText.trim().split( /\r?\n/ );
		expect( reportLines[ 0 ] ).toBe( 'line,order_id,carrier,tracking_number,ship_date,items,status,notify_customer,error' );
		expect( reportLines ).toHaveLength( 4 );
		expect( reportText ).toContain( "'=1+1" );
		expect( reportLines.some( ( line ) => /(^|,)"?=/.test( line ) ) ).toBe( false );
		expect( reportText ).toContain( 'Order not found.' );
		expect( reportText ).not.toContain( trackB );

		// Report download is protected by the nonce.
		const forged = await page.request.get( '/wp-admin/admin-post.php?action=wps_tofw_tracking_import_report&_wpnonce=bad&job_id=x' );
		expect( forged.status() ).toBe( 403 );

		// Import the valid rows in the background (Action Scheduler).
		page.once( 'dialog', ( dialog ) => dialog.accept() );
		const started = page.waitForResponse(
			( response ) => response.url().includes( 'admin-ajax.php' ) && ( response.request().postData() || '' ).includes( 'wps_tofw_tracking_import_start' )
		);
		await page.locator( '#wps-tofw-import-start' ).click();
		expect( ( await ( await started ).json() ).success ).toBe( true );

		const progress = page.locator( '#wps-tofw-import-progress' );
		await expect( progress ).toBeVisible();
		// Drive the chained batches until the screen reports completion.
		await expect
			.poll(
				async () => {
					runScheduledActions( 'wps_tofw_tracking_import_batch' );
					return ( ( await progress.locator( '.wps-tofw-import__status' ).textContent() ) || '' ).trim();
				},
				{ timeout: 60_000, intervals: [ 1_000, 2_000, 3_000 ] }
			)
			.toBe( 'Import completed.' );
		await expect( progress.locator( '.wps-tofw-import__summary' ) ).toContainText( 'Imported: 2' );
		await expect( progress.locator( '.wps-tofw-import__summary' ) ).toContainText( 'Failed: 0' );
		await expect( page.locator( '#wps-tofw-import-final-report' ) ).toBeVisible();

		// Order A: tracking + status from the CSV, no tracking e-mail (notify_customer=no).
		expect( getOrderMeta( orderA.id, 'wps_tofw_package_tracking_number' ) ).toBe( trackA );
		expect( getOrderMeta( orderA.id, 'wps_tofw_selected_shipping_service' ) ).toBe( 'UPS' );
		expect( getOrderStatus( orderA.id ) ).toBe( 'completed' );
		const shipmentsA = getOrderMeta< Shipment[] >( orderA.id, 'wps_tofw_tracking_shipments' );
		expect( shipmentsA ).toHaveLength( 1 );
		expect( shipmentsA[ 0 ] ).toMatchObject( { tracking_number: trackA, ship_date: '2026-11-20', source: 'csv' } );
		expect( trackingMailsTo( orderA.billing_email ) ).toHaveLength( 0 );

		// Order B: legacy tracking kept in the shipments list, partial item shipment, e-mail sent once.
		const shipmentsB = getOrderMeta< Shipment[] >( orderB.id, 'wps_tofw_tracking_shipments' );
		expect( shipmentsB.map( ( shipment ) => shipment.tracking_number ) ).toEqual( [ `LEGACY-${ run }`, trackB ] );
		expect( shipmentsB[ 1 ].items ).toHaveLength( 1 );
		expect( shipmentsB[ 1 ].items[ 0 ] ).toMatchObject( { sku: 'E2E-SIMPLE', qty: 1 } );
		expect( getOrderStatus( orderB.id ) ).toBe( 'processing' );
		expect( Number( getOrderMeta( orderB.id, 'wps_tofw_tracking_id_sent' ) ) ).toBe( 1 );
		expect( trackingMailsTo( orderB.billing_email ) ).toHaveLength( 1 );

		// HPOS-safe: stored through the WC CRUD API in the active order storage.
		const storage = shipmentsStorage( orderA.id );
		if ( storage.hpos ) {
			expect( storage.hposRows ).toBe( 1 );
		} else {
			expect( storage.postmetaRows ).toBe( 1 );
		}
	} );

	test( 'REST: logged-in admin (cookie + nonce) adds and reads tracking', async ( { page } ) => {
		const order = createOrder( { status: 'processing', billing_email: `rest-${ Date.now() }@example.com` } );
		await page.goto( '/wp-admin/' );
		const nonce = ( await ( await page.request.get( '/wp-admin/admin-ajax.php?action=rest-nonce' ) ).text() ).trim();
		expect( nonce ).toMatch( /^[a-f0-9]{10}$/ );

		const tracking = `E2E8396REST${ Date.now() }`;
		const created = await page.request.post( trackingRoute( order.id ), {
			headers: { 'X-WP-Nonce': nonce },
			data: { carrier: 'UPS', tracking_number: tracking, ship_date: '2026-11-22', status: 'completed', notify_customer: false },
		} );
		expect( created.status() ).toBe( 201 );
		const body = await created.json();
		expect( body.success ).toBe( true );
		expect( body.status ).toBe( 'completed' );
		expect( body.shipment ).toMatchObject( { carrier: 'UPS', tracking_number: tracking, ship_date: '2026-11-22', source: 'rest' } );

		expect( getOrderMeta( order.id, 'wps_tofw_package_tracking_number' ) ).toBe( tracking );
		expect( getOrderStatus( order.id ) ).toBe( 'completed' );
		expect( trackingMailsTo( order.billing_email ) ).toHaveLength( 0 );

		// Duplicate tracking number -> 409, invalid input -> 400, unknown order -> 404.
		const duplicate = await page.request.post( trackingRoute( order.id ), {
			headers: { 'X-WP-Nonce': nonce },
			data: { carrier: 'UPS', tracking_number: tracking },
		} );
		expect( duplicate.status() ).toBe( 409 );
		const invalid = await page.request.post( trackingRoute( order.id ), {
			headers: { 'X-WP-Nonce': nonce },
			data: { carrier: 'UPS', tracking_number: `${ tracking }-2`, ship_date: '22/11/2026' },
		} );
		expect( invalid.status() ).toBe( 400 );
		const missing = await page.request.post( trackingRoute( 99999999 ), {
			headers: { 'X-WP-Nonce': nonce },
			data: { carrier: 'UPS', tracking_number: 'X' },
		} );
		expect( missing.status() ).toBe( 404 );

		const list = await page.request.get( trackingRoute( order.id ), { headers: { 'X-WP-Nonce': nonce } } );
		expect( list.status() ).toBe( 200 );
		const listed = await list.json();
		expect( listed.tracking.map( ( shipment: Shipment ) => shipment.tracking_number ) ).toEqual( [ tracking ] );

		// Without the nonce the cookie is ignored by the REST API: treated as unauthenticated.
		const noNonce = await page.request.post( trackingRoute( order.id ), { data: { carrier: 'UPS', tracking_number: 'NONONCE1' } } );
		expect( [ 401, 403 ] ).toContain( noNonce.status() );
	} );

	test.describe( 'without a browser session', () => {
		test.use( { storageState: GUEST_STORAGE_STATE } );

		const APP_PASSWORD_NAME = 'wps-e2e-8396';
		let api: APIRequestContext | undefined;

		test.afterEach( async () => {
			await api?.dispose();
			api = undefined;
		} );

		test.afterAll( () => {
			deleteApplicationPasswords( 'admin', APP_PASSWORD_NAME );
			deleteApplicationPasswords( 'e2e8396customer', APP_PASSWORD_NAME );
		} );

		test( 'REST: application password of an admin adds tracking and sends the e-mail once', async ( { baseURL } ) => {
			const order = createOrder( { status: 'processing', billing_email: `app-${ Date.now() }@example.com` } );
			const password = createApplicationPassword( 'admin', APP_PASSWORD_NAME );
			api = await playwrightRequest.newContext( { baseURL, extraHTTPHeaders: { Authorization: basicAuth( 'admin', password ) } } );

			const first = await api.post( trackingRoute( order.id ), { data: { carrier: 'FedEx', tracking_number: 'APP1' } } );
			expect( first.status() ).toBe( 201 );
			const second = await api.post( trackingRoute( order.id ), { data: { carrier: 'FedEx', tracking_number: 'APP2' } } );
			expect( second.status() ).toBe( 201 );
			expect( ( await second.json() ).tracking.map( ( shipment: Shipment ) => shipment.tracking_number ) ).toEqual( [ 'APP1', 'APP2' ] );

			// Default rule: the tracking e-mail goes out once per order.
			expect( trackingMailsTo( order.billing_email ) ).toHaveLength( 1 );
		} );

		test( 'REST: unauthenticated requests and customers are rejected', async ( { baseURL } ) => {
			const order = createOrder( { status: 'processing' } );

			api = await playwrightRequest.newContext( { baseURL } );
			const anonymousPost = await api.post( trackingRoute( order.id ), { data: { carrier: 'UPS', tracking_number: 'ANON1' } } );
			expect( anonymousPost.status() ).toBe( 401 );
			expect( ( await anonymousPost.json() ).code ).toBe( 'tofw_rest_forbidden' );
			const anonymousGet = await api.get( trackingRoute( order.id ) );
			expect( anonymousGet.status() ).toBe( 401 );
			await api.dispose();

			ensureUser( 'e2e8396customer', 'e2e8396customer@example.com', 'customer' );
			const password = createApplicationPassword( 'e2e8396customer', APP_PASSWORD_NAME );
			api = await playwrightRequest.newContext( { baseURL, extraHTTPHeaders: { Authorization: basicAuth( 'e2e8396customer', password ) } } );
			const customerPost = await api.post( trackingRoute( order.id ), { data: { carrier: 'UPS', tracking_number: 'CUST1' } } );
			expect( customerPost.status() ).toBe( 403 );
			const customerGet = await api.get( trackingRoute( order.id ) );
			expect( customerGet.status() ).toBe( 403 );

			expect( getOrderMeta( order.id, 'wps_tofw_package_tracking_number' ) ).toBe( '' );
			expect( getOrderMeta( order.id, 'wps_tofw_tracking_shipments' ) ).toBe( '' );
		} );
	} );
} );
