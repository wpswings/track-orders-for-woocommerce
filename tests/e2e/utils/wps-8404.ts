/**
 * WPS-8404 helpers: mail capture mu-plugin, extra users and seeded shipments.
 */
import { expect, Page } from '@playwright/test';
import { createOrder, CreatedOrder } from './orders';
import { phpValue, wpEval, wpEvalJson } from './wp-cli';

export const MAIL_OPTION = 'wps_e2e_8404_mails';
const MU_PLUGIN = 'wps-e2e-8404-mail-capture.php';

export interface CapturedMail {
	to: string | string[];
	subject: string;
	message: string;
}

/** Install an mu-plugin that stores every wp_mail() call in an option instead of sending it. */
export function installMailCapture(): void {
	const code = `<?php
add_filter( 'pre_wp_mail', function ( $return, $atts ) {
	$mails   = get_option( '${ MAIL_OPTION }', array() );
	$mails   = is_array( $mails ) ? $mails : array();
	$mails[] = array( 'to' => $atts['to'], 'subject' => $atts['subject'], 'message' => $atts['message'] );
	update_option( '${ MAIL_OPTION }', $mails, false );
	return true;
}, 10, 2 );
`;
	wpEval( `
		if ( ! is_dir( WPMU_PLUGIN_DIR ) ) { wp_mkdir_p( WPMU_PLUGIN_DIR ); }
		file_put_contents( trailingslashit( WPMU_PLUGIN_DIR ) . ${ phpValue( MU_PLUGIN ) }, ${ phpValue( code ) } );
		delete_option( '${ MAIL_OPTION }' );
	` );
}

/** Remove the mail capture mu-plugin and the stored mails. */
export function removeMailCapture(): void {
	wpEval( `
		$file = trailingslashit( WPMU_PLUGIN_DIR ) . ${ phpValue( MU_PLUGIN ) };
		if ( file_exists( $file ) ) { unlink( $file ); }
		delete_option( '${ MAIL_OPTION }' );
	` );
}

/** Mails captured so far. */
export function capturedMails(): CapturedMail[] {
	return wpEvalJson< CapturedMail[] >( `$m = get_option( '${ MAIL_OPTION }', array() ); return is_array( $m ) ? array_values( $m ) : array();` );
}

/** Clear captured mails. */
export function clearCapturedMails(): void {
	wpEval( `delete_option( '${ MAIL_OPTION }' );` );
}

/** Run the daily delivery alerts Action Scheduler hook synchronously. */
export function runDailyAlertCheck(): void {
	wpEval( `do_action( 'wps_tofw_delivery_alerts_daily_check' );` );
}

/** Create (or reset the password of) a user with a role. */
export function ensureUser( login: string, role: string, password: string ): number {
	return wpEvalJson< number >( `
		$login = ${ phpValue( login ) };
		$id    = username_exists( $login );
		if ( ! $id ) {
			$id = wp_insert_user( array( 'user_login' => $login, 'user_email' => $login . '@example.com', 'user_pass' => ${ phpValue( password ) }, 'role' => ${ phpValue( role ) } ) );
		} else {
			wp_set_password( ${ phpValue( password ) }, $id );
			( new WP_User( $id ) )->set_role( ${ phpValue( role ) } );
		}
		return (int) $id;
	` );
}

/** Log in through wp-login.php as any user (does not assume a wp-admin redirect). */
export async function loginAs( page: Page, login: string, password: string ): Promise< void > {
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( login );
	await page.locator( '#user_pass' ).fill( password );
	await page.locator( '#wp-submit' ).click();
	await page.waitForURL( ( url ) => ! url.pathname.includes( 'wp-login.php' ) );
	await expect( page.locator( 'body' ) ).not.toHaveClass( /login-action-login/ );
}

/** Set the creation date of an order ("Y-m-d H:i:s", site time). */
export function setOrderCreatedDate( orderId: number, date: string ): void {
	wpEval( `$o = wc_get_order( ${ Number( orderId ) } ); $o->set_date_created( ${ phpValue( date ) } ); $o->save();` );
}

/** Unix timestamp of a "Y-m-d H:i:s" date in the site timezone. */
export function siteTimestamp( date: string ): number {
	return wpEvalJson< number >( `return ( new DateTime( ${ phpValue( date ) }, wp_timezone() ) )->getTimestamp();` );
}

/** Create an order for the shipment insights scenarios. */
export function createShipment( status: string, meta: Record< string, unknown > ): CreatedOrder {
	return createOrder( { status, billing_email: 'insights-8404@example.com', meta } );
}
