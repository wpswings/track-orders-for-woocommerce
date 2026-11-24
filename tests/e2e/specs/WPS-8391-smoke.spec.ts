/**
 * Smoke tests: the plugin is active, its settings page and the guest tracking page render.
 */
import { expect, test } from '@playwright/test';
import { GUEST_STORAGE_STATE, SETTINGS_PAGE, trackingPageUrl } from '../utils';

test.describe( 'Smoke', () => {
	test( 'admin can open the plugin settings page', async ( { page } ) => {
		await page.goto( SETTINGS_PAGE );

		await expect( page.locator( '.tofw-admin-shell__title' ) ).toHaveText( 'Track Orders for WooCommerce' );
		await expect( page.locator( 'a#track-orders-for-woocommerce-general' ) ).toHaveClass( /is-active/ );
		await expect( page.locator( '.tofw-admin-shell__content-card' ) ).toBeVisible();
	} );

	test.describe( 'as a guest', () => {
		test.use( { storageState: GUEST_STORAGE_STATE } );

		test( 'guest tracking page renders the track order form', async ( { page } ) => {
			const url = trackingPageUrl( 'wps_guest_track_order_page' );
			expect( url, 'guest tracking page should exist' ).not.toBe( '' );

			await page.goto( url );

			await expect( page.locator( '#wps_tofw_guest_request_form_wrapper' ) ).toBeVisible();
			await expect( page.locator( '#order_id' ) ).toBeVisible();
			await expect( page.locator( '#order_email' ) ).toBeVisible();
			await expect( page.locator( 'input[name="wps_tofw_order_id_submit"]' ) ).toBeVisible();
		} );
	} );
} );
