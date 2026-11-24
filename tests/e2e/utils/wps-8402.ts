/**
 * WPS-8402 helpers: pages with the Track Order block and customer accounts.
 */
import { expect, Page } from '@playwright/test';
import { phpValue, wpEvalJson } from './wp-cli';

export const TRACK_ORDER_BLOCK = 'track-orders-for-woocommerce/track-order';
export const PLUGIN_FILE = 'track-orders-for-woocommerce/track-orders-for-woocommerce.php';

/** Publish a page whose content is the given block markup and return its ID and permalink. */
export function createBlockPage( title: string, content: string ): { id: number; url: string } {
	return wpEvalJson< { id: number; url: string } >( `
		$id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => ${ phpValue( title ) },
			'post_content' => ${ phpValue( content ) },
		), true );
		if ( is_wp_error( $id ) ) {
			throw new Exception( $id->get_error_message() );
		}
		return array( 'id' => (int) $id, 'url' => get_permalink( $id ) );
	` );
}

/** Create (or reset the password of) a customer account and return its user ID. */
export function ensureCustomer( login: string, email: string, password: string ): number {
	return wpEvalJson< number >( `
		$user = get_user_by( 'login', ${ phpValue( login ) } );
		if ( $user ) {
			wp_set_password( ${ phpValue( password ) }, $user->ID );
			return (int) $user->ID;
		}
		$id = wp_insert_user( array(
			'user_login' => ${ phpValue( login ) },
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

/** Log in through wp-login.php as a non-admin user (customers are redirected away from wp-admin). */
export async function loginAs( page: Page, login: string, password: string ): Promise< void > {
	await page.goto( '/wp-login.php?redirect_to=' + encodeURIComponent( '/' ) );
	await page.locator( '#user_login' ).fill( login );
	await page.locator( '#user_pass' ).fill( password );
	await Promise.all( [
		page.waitForURL( ( url ) => ! url.pathname.includes( 'wp-login.php' ) ),
		page.locator( '#wp-submit' ).click(),
	] );
	const cookies = await page.context().cookies();
	expect( cookies.some( ( c ) => c.name.startsWith( 'wordpress_logged_in_' ) ) ).toBe( true );
}
