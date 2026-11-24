/**
 * WPS-8392: the plugin listing metadata (name, version) shown on the admin Plugins screen
 * matches the plugin header / readme Stable tag.
 */
import { expect, test } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { PLUGIN_ROOT } from '../utils';

function headerField( file: string, field: string ): string {
	const source = fs.readFileSync( path.join( PLUGIN_ROOT, file ), 'utf8' );
	const match = source.match( new RegExp( `^[\\s*]*${ field }:\\s*(.+?)\\s*$`, 'm' ) );
	if ( ! match ) {
		throw new Error( `${ field } not found in ${ file }` );
	}
	return match[ 1 ];
}

test.describe( 'WPS-8392 plugin listing', () => {
	test( 'Plugins screen shows the plugin name, version and active state', async ( { page } ) => {
		const version = headerField( 'track-orders-for-woocommerce.php', 'Version' );
		const stableTag = headerField( 'README.txt', 'Stable tag' );
		expect( stableTag, 'README.txt Stable tag matches the plugin Version' ).toBe( version );

		await page.goto( '/wp-admin/plugins.php?plugin_status=all' );

		const row = page.locator( 'tr[data-slug="track-orders-for-woocommerce"]' );
		await expect( row ).toHaveCount( 1 );
		await expect( row ).toHaveClass( /\bactive\b/ );
		await expect( row.locator( '.plugin-title strong' ) ).toHaveText( headerField( 'track-orders-for-woocommerce.php', 'Plugin Name' ) );
		await expect( row.locator( '.plugin-version-author-uri' ) ).toContainText( `Version ${ version }` );
		await expect( row.locator( '.plugin-version-author-uri' ) ).toContainText( 'WPSwings' );
	} );

	test( 'plugin is registered under its canonical basename with its description', async ( { page } ) => {
		await page.goto( '/wp-admin/plugins.php' );
		const row = page.locator( 'tr[data-plugin="track-orders-for-woocommerce/track-orders-for-woocommerce.php"]' );
		await expect( row ).toBeVisible();
		await expect( row.locator( '.plugin-description' ) ).toContainText( 'Track Orders for WooCommerce' );
	} );
} );
