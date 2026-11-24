/**
 * WPS-8404: delivery exception / late shipment alerts and shipping analytics.
 */
import { expect, test } from '@playwright/test';
import { CreatedOrder, GUEST_STORAGE_STATE, phpValue, setOptions, settingsTabUrl, wpEval, wpEvalJson } from '../utils';
import {
	capturedMails,
	clearCapturedMails,
	createShipment,
	ensureUser,
	installMailCapture,
	loginAs,
	removeMailCapture,
	runDailyAlertCheck,
	setOrderCreatedDate,
	siteTimestamp,
} from '../utils/wps-8404';

const DAY = 86400;
const ALERT_OPTIONS = {
	wps_tofw_delivery_alerts_expected_days: 3,
	wps_tofw_delivery_alerts_recipients: 'alerts-8404@example.com',
	wps_tofw_delivery_alerts_conditions: [ 'exception', 'expected_days', 'estimated_date' ],
	wps_tofw_delivery_alerts_dashboard_notice: 'on',
	wps_tofw_delivery_active_alerts: {},
};
const ANALYTICS_TAB = 'track-orders-for-woocommerce-shipping-analytics';

test.describe.serial( 'WPS-8404 delivery alerts & shipping analytics', () => {
	let late: CreatedOrder;
	let exception: CreatedOrder;
	let onTime: CreatedOrder;

	test.beforeAll( () => {
		installMailCapture();
		// Toggle so the update_option hook (re)schedules the daily Action Scheduler job.
		setOptions( { ...ALERT_OPTIONS, wps_tofw_delivery_alerts_enable: 'off' } );
		setOptions( { wps_tofw_delivery_alerts_enable: 'on' } );

		const now = Math.floor( Date.now() / 1000 );
		late = createShipment( 'processing', {
			wps_tofw_shipped_at: now - 10 * DAY,
			wps_tofw_package_tracking_number: 'LATE8404',
			wps_tofw_selected_shipping_service: 'e2e_post',
		} );
		exception = createShipment( 'processing', {
			wps_tofw_carrier_status: 'failed_attempt',
			wps_tofw_shipped_at: now - DAY,
			wps_tofw_package_tracking_number: 'EXC8404',
		} );
		onTime = createShipment( 'processing', {
			wps_tofw_shipped_at: now - DAY,
			wps_tofw_package_tracking_number: 'ONTIME8404',
		} );
	} );

	test.afterAll( () => {
		removeMailCapture();
		setOptions( { wps_tofw_delivery_alerts_enable: 'off', wps_tofw_delivery_active_alerts: {} } );
	} );

	test( 'enabling alerts schedules the daily Action Scheduler job', () => {
		const next = wpEvalJson< number | boolean >(
			`return as_next_scheduled_action( 'wps_tofw_delivery_alerts_daily_check', array(), 'wps-tofw-delivery-alerts' );`
		);
		expect( next ).toBeTruthy();
	} );

	test( 'daily check emails one digest with late and exception shipments only', () => {
		clearCapturedMails();
		runDailyAlertCheck();

		const mails = capturedMails().filter( ( m ) => /Shipments need attention/.test( m.subject ) );
		expect( mails ).toHaveLength( 1 );
		const mail = mails[ 0 ];
		expect( String( mail.to ) ).toContain( 'alerts-8404@example.com' );
		expect( mail.message ).toContain( `#${ late.id }` );
		expect( mail.message ).toContain( 'LATE8404' );
		expect( mail.message ).toContain( 'E2e Post' );
		expect( mail.message ).toContain( `#${ exception.id }` );
		expect( mail.message ).toContain( 'Delivery exception' );
		expect( mail.message ).not.toContain( 'ONTIME8404' );

		const active = wpEvalJson< Record< string, { issues: string[] } > >( `return get_option( 'wps_tofw_delivery_active_alerts' );` );
		expect( active[ String( late.id ) ].issues ).toEqual( [ 'expected_days' ] );
		expect( active[ String( exception.id ) ].issues ).toContain( 'exception' );
		expect( active[ String( onTime.id ) ] ).toBeUndefined();
	} );

	test( 'running the daily check again does not email the same issues twice', () => {
		clearCapturedMails();
		runDailyAlertCheck();

		const digest = capturedMails().filter( ( m ) => /Shipments need attention/.test( m.subject ) );
		for ( const mail of digest ) {
			expect( mail.message ).not.toContain( 'LATE8404' );
			expect( mail.message ).not.toContain( 'EXC8404' );
		}
	} );

	test( 'admin sees the alert notice and dashboard widget', async ( { page } ) => {
		await page.goto( '/wp-admin/index.php' );

		const notice = page.locator( '.notice-warning' ).filter( { hasText: 'Track Orders:' } );
		await expect( notice ).toBeVisible();
		await expect( notice ).toContainText( /delivery exception or (is|are) running late/ );
		await expect( notice.locator( `a[href*="tofw_tab=${ ANALYTICS_TAB }"]` ) ).toBeVisible();

		const widget = page.locator( '#wps_tofw_delivery_alerts_widget' );
		await expect( widget ).toBeVisible();
		await expect( widget ).toContainText( `#${ late.id }` );
		await expect( widget ).toContainText( `#${ exception.id }` );
		await expect( widget.getByRole( 'link', { name: `#${ onTime.id }`, exact: true } ) ).toHaveCount( 0 );
	} );

	test( 'delivery alerts tab shows the active alert count', async ( { page } ) => {
		await page.goto( settingsTabUrl( 'track-orders-for-woocommerce-delivery-alerts' ) );
		await expect( page.locator( '.wps-tofw-insights-meta' ) ).toContainText( /Shipments currently needing attention: [1-9]/ );
		await expect( page.locator( '.wps-tofw-insights-meta' ) ).toContainText( 'Next scheduled check' );
	} );

	test.describe( 'users without manage_options', () => {
		test.use( { storageState: GUEST_STORAGE_STATE } );

		test( 'shop manager sees no alert notice or widget', async ( { page } ) => {
			ensureUser( 'e2e_shopmgr_8404', 'shop_manager', 'ShopMgr-8404-pass' );
			await loginAs( page, 'e2e_shopmgr_8404', 'ShopMgr-8404-pass' );
			await page.goto( '/wp-admin/index.php' );

			await expect( page.locator( '#wpadminbar' ) ).toBeVisible();
			await expect( page.locator( '#dashboard-widgets-wrap' ) ).toBeVisible();
			await expect( page.locator( '#wps_tofw_delivery_alerts_widget' ) ).toHaveCount( 0 );
			await expect( page.locator( '.notice' ).filter( { hasText: 'running late' } ) ).toHaveCount( 0 );
		} );

		test( 'subscriber sees no alert notice or widget', async ( { page } ) => {
			ensureUser( 'e2e_subscriber_8404', 'subscriber', 'Subscriber-8404-pass' );
			await loginAs( page, 'e2e_subscriber_8404', 'Subscriber-8404-pass' );
			await page.goto( '/wp-admin/index.php' );

			await expect( page.locator( 'body' ) ).toBeVisible();
			await expect( page.locator( '#wps_tofw_delivery_alerts_widget' ) ).toHaveCount( 0 );
			await expect( page.locator( 'body' ) ).not.toContainText( 'running late' );
		} );
	} );

	test( 'analytics date range filter changes the figures', async ( { page } ) => {
		// A random past day nobody else uses, so retries and other specs do not change the numbers.
		const year = 2005 + Math.floor( Math.random() * 10 );
		const month = String( 1 + Math.floor( Math.random() * 12 ) ).padStart( 2, '0' );
		const day = String( 1 + Math.floor( Math.random() * 27 ) ).padStart( 2, '0' );
		const date = `${ year }-${ month }-${ day }`;
		const nextDay = `${ year }-${ month }-${ String( Number( day ) + 1 ).padStart( 2, '0' ) }`;
		const shipped = siteTimestamp( `${ date } 10:00:00` );
		const carrier = `e2e_carrier_${ year }${ month }${ day }`;
		const carrierLabel = `E2e Carrier ${ year }${ month }${ day }`;

		const delivered = createShipment( 'completed', {
			wps_tofw_selected_shipping_service: carrier,
			wps_tofw_shipped_at: shipped,
			wps_tofw_delivered_at: shipped + 2 * DAY,
		} );
		const lateShipment = createShipment( 'processing', {
			wps_tofw_selected_shipping_service: carrier,
			wps_tofw_shipped_at: shipped,
			wps_tofw_package_tracking_number: `ANL${ year }${ month }${ day }`,
		} );
		setOrderCreatedDate( delivered.id, `${ date } 09:00:00` );
		setOrderCreatedDate( lateShipment.id, `${ date } 09:30:00` );

		// Tracking page visits / upsell clicks counters for that day.
		wpEval( `
			( new Track_Orders_For_Woocommerce_Shipment_Insights( 'track-orders-for-woocommerce', '1' ) )->wps_tofw_maybe_upgrade_db();
			global $wpdb;
			$table = $wpdb->prefix . 'wps_tofw_daily_stats';
			$wpdb->replace( $table, array( 'stat_date' => ${ phpValue( date ) }, 'metric' => 'tracking_visit', 'stat_count' => 8 ) );
			$wpdb->replace( $table, array( 'stat_date' => ${ phpValue( date ) }, 'metric' => 'upsell_click', 'stat_count' => 2 ) );
		` );

		await page.goto( `${ settingsTabUrl( ANALYTICS_TAB ) }&wps_tofw_from=${ date }&wps_tofw_to=${ date }&wps_tofw_refresh=1` );

		const card = ( label: string ) =>
			page.locator( '.wps-tofw-insights__card' ).filter( { hasText: label } ).locator( '.wps-tofw-insights__card-value' );

		await expect( page.locator( '.wps-tofw-insights__note' ).first() ).toContainText( '2 orders analysed' );
		await expect( card( 'Shipments' ) ).toHaveText( '2' );
		await expect( card( 'Avg. delivery time' ) ).toContainText( '2.0 days' );
		await expect( card( 'Late / exception' ) ).toHaveText( '1' );
		await expect( card( 'Tracking page visits' ) ).toHaveText( '8' );
		await expect( card( 'Upsell clicks' ) ).toContainText( '2' );
		await expect( card( 'Upsell clicks' ) ).toContainText( '25.0%' );

		const carrierRow = page.locator( '.wps-tofw-insights__table tr' ).filter( { hasText: carrierLabel } );
		await expect( carrierRow ).toHaveCount( 1 );
		await expect( carrierRow.locator( 'td' ) ).toHaveText( [ carrierLabel, '2', '1', '1', /2\.0/ ] );

		const statusRow = ( label: string ) => page.locator( '.wps-tofw-insights__table tr' ).filter( { has: page.locator( 'td', { hasText: new RegExp( `^${ label }$` ) } ) } );
		await expect( statusRow( 'Delivered' ).locator( '.wps-tofw-insights__bar-value' ) ).toHaveText( '1' );
		await expect( statusRow( 'In transit' ).locator( '.wps-tofw-insights__bar-value' ) ).toHaveText( '1' );

		await expect( page.locator( `a[href*="${ lateShipment.id }"]` ).filter( { hasText: `#${ lateShipment.id }` } ) ).toBeVisible();

		// Change the range with the form: the next day has no orders and no activity.
		const filters = page.locator( 'form.wps-tofw-insights__filters' );
		await filters.locator( 'input[name="wps_tofw_from"]' ).fill( nextDay );
		await filters.locator( 'input[name="wps_tofw_to"]' ).fill( nextDay );
		await Promise.all( [
			page.waitForURL( new RegExp( `wps_tofw_from=${ nextDay }` ) ),
			filters.locator( 'button[type="submit"]' ).click(),
		] );

		await expect( page.locator( '.wps-tofw-insights__note' ).first() ).toContainText( '0 orders analysed' );
		await expect( card( 'Shipments' ) ).toHaveText( '0' );
		await expect( card( 'Late / exception' ) ).toHaveText( '0' );
		await expect( card( 'Tracking page visits' ) ).toHaveText( '0' );
		await expect( page.getByText( 'No shipments in this period.' ) ).toBeVisible();
		await expect( page.getByText( 'No late shipments in this period.' ) ).toBeVisible();
	} );
} );
