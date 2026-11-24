/**
 * WPS-8397 Automatic carrier status sync (free).
 *
 * 17TRACK is faked by a mu-plugin (pre_http_request), see ../utils/wps-8397.ts.
 */
import { expect, test } from '@playwright/test';
import { getOrderMeta, getOrderStatus, settingsTabUrl, trackingPageUrl, wpEvalJson } from '../utils';
import {
	createTrackedOrder,
	fakeCalls,
	fakeTrackingResponse,
	getUsage,
	installFake17Track,
	removeFake17Track,
	resetCarrierSync,
	runQueuedSyncActions,
	runSyncBatch,
	setUsage,
	SYNC_TAB,
} from '../utils/wps-8397';

test.describe.configure( { mode: 'serial' } );

test.describe( 'WPS-8397 carrier status sync', () => {
	test.beforeAll( () => {
		installFake17Track();
	} );

	test.afterAll( () => {
		removeFake17Track();
		resetCarrierSync( { wps_tofw_carrier_sync_enable: 'off' } );
	} );

	test.beforeEach( () => {
		resetCarrierSync();
	} );

	test( 'settings tab shows the free monthly usage counter and limit', async ( { page } ) => {
		setUsage( 7 );

		await page.goto( settingsTabUrl( SYNC_TAB ) );

		await expect( page.locator( '.wps-tofw-carrier-sync-usage__count' ) ).toHaveText( /7\s*\/\s*50/ );
		await expect( page.locator( '#wps_tofw_carrier_sync_enable' ) ).toBeAttached();
		await expect( page.locator( '#wps_tofw_carrier_sync_auto_complete' ) ).toBeAttached();
		await expect( page.locator( 'form[action$="admin-post.php"] button[type="submit"]' ) ).toBeEnabled();
	} );

	test( 'sync stores the mapped carrier status and timeline and the tracking page shows it', async ( { page } ) => {
		const number = `E2E8397OFD${ Date.now() }`;
		fakeTrackingResponse( number, 'OutForDelivery', [
			{ time_utc: '2026-01-02T09:30:00Z', description: 'Out for delivery with courier', location: 'Springfield', sub_status: 'OutForDelivery_Other' },
			{ time_utc: '2026-01-01T18:00:00Z', description: 'Arrived at destination hub', location: 'Shelbyville', sub_status: 'InTransit_Arrival' },
		] );
		const order = createTrackedOrder( number );

		runSyncBatch();

		expect( getOrderMeta( order.id, 'wps_tofw_carrier_status' ) ).toBe( 'out_for_delivery' );
		expect( getOrderMeta( order.id, 'wps_tofw_carrier_status_provider' ) ).toBe( '17track' );
		const events = getOrderMeta< { description: string; location: string }[] >( order.id, 'wps_tofw_carrier_status_events' );
		expect( events.map( ( e ) => e.description ) ).toEqual( [ 'Out for delivery with courier', 'Arrived at destination hub' ] );
		expect( getOrderStatus( order.id ) ).toBe( 'processing' );

		// The request went to the fake API with the stored key and the UPS carrier code.
		const calls = fakeCalls();
		expect( calls ).toHaveLength( 1 );
		expect( calls[ 0 ].url ).toContain( '/gettrackinfo' );
		expect( calls[ 0 ].token ).toBe( 'e2e-fake-key' );
		expect( JSON.parse( calls[ 0 ].body ) ).toEqual( [ { number, carrier: 100002 } ] );

		// One lookup counted.
		expect( getUsage() ).toBe( 1 );

		// Synced orders are not looked up again on the next run.
		runSyncBatch();
		expect( fakeCalls() ).toHaveLength( 1 );

		await page.goto( `${ trackingPageUrl( 'wps_track_order_page' ) }?${ order.id }` );
		const timeline = page.locator( '.wps-tofw-carrier-timeline' );
		await expect( timeline ).toBeVisible();
		await expect( timeline ).toHaveClass( /wps-tofw-carrier-timeline--out_for_delivery/ );
		await expect( timeline.locator( '.wps-tofw-carrier-timeline__badge' ) ).toHaveText( 'Out for delivery' );
		const items = timeline.locator( 'li.wps-tofw-carrier-timeline__event' );
		await expect( items ).toHaveCount( 2 );
		await expect( items.first() ).toHaveClass( /is-latest/ );
		await expect( items.first() ).toContainText( 'Out for delivery with courier' );
		await expect( items.first() ).toContainText( 'Springfield' );

		await page.goto( settingsTabUrl( SYNC_TAB ) );
		await expect( page.locator( '.wps-tofw-carrier-sync-usage__count' ) ).toHaveText( /1\s*\/\s*50/ );
	} );

	test( 'in transit, delivered and exception statuses are mapped', () => {
		const stamp = Date.now();
		const cases: [ string, string ][] = [
			[ 'InTransit', 'in_transit' ],
			[ 'Exception', 'exception' ],
			[ 'Delivered', 'delivered' ],
		];
		const orders = cases.map( ( [ raw ], i ) => {
			const number = `E2E8397MAP${ stamp }${ i }`;
			fakeTrackingResponse( number, raw );
			return createTrackedOrder( number );
		} );

		runSyncBatch();

		cases.forEach( ( [ , expected ], i ) => {
			expect( getOrderMeta( orders[ i ].id, 'wps_tofw_carrier_status' ), cases[ i ][ 0 ] ).toBe( expected );
		} );
		expect( getUsage() ).toBe( 3 );
		// Auto-complete is off: the delivered order keeps its status.
		expect( getOrderStatus( orders[ 2 ].id ) ).toBe( 'processing' );
	} );

	test( 'delivered order is completed automatically when auto-complete is on', () => {
		resetCarrierSync( { wps_tofw_carrier_sync_auto_complete: 'on' } );
		const number = `E2E8397DLV${ Date.now() }`;
		fakeTrackingResponse( number, 'Delivered' );
		const order = createTrackedOrder( number );

		runSyncBatch();

		expect( getOrderMeta( order.id, 'wps_tofw_carrier_status' ) ).toBe( 'delivered' );
		expect( getOrderStatus( order.id ) ).toBe( 'completed' );
		const notes = wpEvalJson< string[] >(
			`return array_map( function ( $n ) { return $n->content; }, wc_get_order_notes( array( 'order_id' => ${ order.id } ) ) );`
		);
		expect( notes.some( ( n ) => n.includes( 'carrier reported the shipment as delivered' ) ) ).toBe( true );
	} );

	test( 'rejected tracking number stores the error and does not stop the other orders', () => {
		const stamp = Date.now();
		const bad = `E2E8397BAD${ stamp }`;
		const good = `E2E8397GOOD${ stamp }`;
		wpEvalJson( `
			$map = (array) get_option( 'e2e_wps_8397_17track', array() );
			$map['${ bad }'] = array( 'error' => 'Invalid tracking number' );
			update_option( 'e2e_wps_8397_17track', $map, false );
			return true;
		` );
		fakeTrackingResponse( good, 'InTransit' );
		const goodOrder = createTrackedOrder( good );
		const badOrder = createTrackedOrder( bad );

		runSyncBatch();

		expect( getOrderMeta( badOrder.id, 'wps_tofw_carrier_status_error' ) ).toBe( 'Invalid tracking number' );
		expect( getOrderMeta( badOrder.id, 'wps_tofw_carrier_status' ) ).toBe( '' );
		expect( getOrderMeta( goodOrder.id, 'wps_tofw_carrier_status' ) ).toBe( 'in_transit' );
	} );

	test( 'monthly limit stops lookups and the settings tab says so', async ( { page } ) => {
		setUsage( 50 );
		const number = `E2E8397LIM${ Date.now() }`;
		fakeTrackingResponse( number, 'Delivered' );
		const order = createTrackedOrder( number );

		runSyncBatch();

		expect( fakeCalls() ).toHaveLength( 0 );
		expect( getOrderMeta( order.id, 'wps_tofw_carrier_status' ) ).toBe( '' );
		expect( getUsage() ).toBe( 50 );

		await page.goto( settingsTabUrl( SYNC_TAB ) );
		const usage = page.locator( '.wps-tofw-carrier-sync-usage' );
		await expect( usage.locator( '.wps-tofw-carrier-sync-usage__count' ) ).toHaveText( /50\s*\/\s*50/ );
		await expect( usage ).toContainText( 'The monthly lookup limit has been reached' );
	} );

	test( '"Run Sync Now" queues an Action Scheduler batch that syncs the order', async ( { page } ) => {
		const number = `E2E8397RUN${ Date.now() }`;
		fakeTrackingResponse( number, 'InTransit' );
		const order = createTrackedOrder( number );

		await page.goto( settingsTabUrl( SYNC_TAB ) );
		await page.locator( 'form[action$="admin-post.php"] button[type="submit"]' ).click();
		await page.waitForURL( /wps_tofw_sync_queued=1/ );
		await expect( page.locator( '.notice-success' ).filter( { hasText: 'A sync batch has been queued' } ) ).toBeVisible();

		// Run whatever is still pending (the async runner may already have processed it).
		await expect
			.poll( () => {
				runQueuedSyncActions();
				return getOrderMeta( order.id, 'wps_tofw_carrier_status' );
			} )
			.toBe( 'in_transit' );
	} );
} );
