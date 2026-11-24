/**
 * WPS-8405 helpers: fake an active RMA plugin and log in as a customer.
 */
import { expect, Page } from '@playwright/test';
import { phpValue, wpEval, wpEvalJson } from './wp-cli';

const MU_PLUGIN = 'e2e-wps-8405-rma.php';

/** Option read by the mu-plugin: 'no' makes the RMA plugin look inactive. */
export const RMA_ACTIVE_OPTION = 'e2e_wps_8405_rma_active';

/**
 * Install an mu-plugin that
 * - forces `wps_tofw_is_rma_active` to true (unless option RMA_ACTIVE_OPTION is 'no'), so no real RMA plugin is needed;
 * - short-circuits Google Maps geocoding calls made by the map template (no external API calls).
 */
export function installRmaMuPlugin(): void {
	const code = `<?php
add_filter( 'wps_tofw_is_rma_active', function ( $active ) {
	return 'no' === get_option( '${ RMA_ACTIVE_OPTION }', 'yes' ) ? false : true;
}, 999 );
add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
	if ( false !== strpos( (string) $url, 'maps.google' ) || false !== strpos( (string) $url, 'googleapis.com' ) ) {
		return array( 'headers' => array(), 'body' => '{"results":[],"status":"ZERO_RESULTS"}', 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array(), 'filename' => null );
	}
	return $pre;
}, 10, 3 );
`;
	wpEval( `
		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) {
			wp_mkdir_p( WPMU_PLUGIN_DIR );
		}
		file_put_contents( WPMU_PLUGIN_DIR . '/${ MU_PLUGIN }', ${ phpValue( code ) } );
	` );
}

/** Remove the mu-plugin installed by installRmaMuPlugin(). */
export function removeRmaMuPlugin(): void {
	wpEval( `@unlink( WPMU_PLUGIN_DIR . '/${ MU_PLUGIN }' ); delete_option( '${ RMA_ACTIVE_OPTION }' );` );
}

/** Create (or reset the password of) a customer and return its user ID. */
export function ensureCustomer( login: string, email: string, password: string ): number {
	return wpEvalJson< number >( `
		$login = ${ phpValue( login ) };
		$user  = get_user_by( 'login', $login );
		if ( $user ) {
			wp_set_password( ${ phpValue( password ) }, $user->ID );
			return (int) $user->ID;
		}
		$id = wp_insert_user( array(
			'user_login' => $login,
			'user_email' => ${ phpValue( email ) },
			'user_pass'  => ${ phpValue( password ) },
			'role'       => 'customer',
		) );
		if ( is_wp_error( $id ) ) {
			throw new Exception( $id->get_error_message() );
		}
		return (int) $id;
	` );
}

/** Log in through wp-login.php as a non-admin user (customers end up on My Account, not wp-admin). */
export async function loginAsCustomer( page: Page, login: string, password: string ): Promise< void > {
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( login );
	await page.locator( '#user_pass' ).fill( password );
	await Promise.all( [
		page.waitForURL( ( url ) => ! url.pathname.includes( 'wp-login.php' ) ),
		page.locator( '#wp-submit' ).click(),
	] );
	const cookies = await page.context().cookies();
	expect( cookies.some( ( c ) => c.name.startsWith( 'wordpress_logged_in_' ) ) ).toBe( true );
}
