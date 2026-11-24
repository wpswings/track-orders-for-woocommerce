/**
 * WPS-8403 helpers: "Where is my order?" assistant fixtures.
 */
import { expect, Locator, Page } from '@playwright/test';
import { phpValue, setOptions, wpCli, wpEval, wpEvalJson } from './wp-cli';

/** Fake API key: must never reach the browser. */
export const WISMO_API_KEY = 'sk-e2e-WISMO-DO-NOT-LEAK-8403';
/** Host of the fake LLM endpoint (answered by the mu-plugin, never called for real). */
export const WISMO_LLM_ENDPOINT = 'https://llm.e2e.test/v1/chat/completions';
/** Canned LLM reply. */
export const WISMO_LLM_REPLY = 'E2E fake LLM says your parcel is on its way.';
export const WISMO_SUPPORT_EMAIL = 'help-8403@example.com';
export const WISMO_SUPPORT_URL = 'https://example.com/contact-8403';

const MU_FILE = 'e2e-wps-8403-wismo.php';

/**
 * mu-plugin that fakes the LLM API (stores the last request in an option) and blocks
 * every other outgoing HTTP request to known LLM providers. Also lets a spec lower
 * the verification rate limit through the option e2e_wismo_verify_limit.
 */
const MU_PLUGIN = `<?php
add_filter( 'pre_http_request', function ( $pre, $args, $url ) {
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	if ( 'llm.e2e.test' === $host ) {
		update_option( 'e2e_wismo_llm_last', array( 'url' => $url, 'body' => isset( $args['body'] ) ? (string) $args['body'] : '' ), false );
		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( array( 'choices' => array( array( 'message' => array( 'content' => ${ JSON.stringify( WISMO_LLM_REPLY ) } ) ) ) ) ),
			'response' => array( 'code' => 200, 'message' => 'OK' ),
			'cookies'  => array(),
			'filename' => null,
		);
	}
	if ( preg_match( '/(openai\\.com|anthropic\\.com|googleapis\\.com)$/', $host ) ) {
		return new WP_Error( 'e2e_blocked', 'External LLM calls are blocked in E2E.' );
	}
	return $pre;
}, 10, 3 );
add_filter( 'wps_tofw_wismo_verify_limit', function ( $limit ) {
	$override = (int) get_option( 'e2e_wismo_verify_limit', 0 );
	return $override > 0 ? $override : $limit;
} );
`;

/** Install the fake LLM mu-plugin. */
export function installWismoMuPlugin(): void {
	wpEval( `
		wp_mkdir_p( WPMU_PLUGIN_DIR );
		file_put_contents( trailingslashit( WPMU_PLUGIN_DIR ) . ${ phpValue( MU_FILE ) }, ${ phpValue( MU_PLUGIN ) } );
	` );
}

/** Remove the mu-plugin. */
export function removeWismoMuPlugin(): void {
	wpEval( `
		$file = trailingslashit( WPMU_PLUGIN_DIR ) . ${ phpValue( MU_FILE ) };
		if ( file_exists( $file ) ) {
			unlink( $file );
		}
	` );
}

/** Turn the widget on (with LLM mode pointing at the fake endpoint). */
export function enableWismo( extra: Record< string, unknown > = {} ): void {
	setOptions( {
		wps_tofw_wismo_enable: 'on',
		wps_tofw_wismo_title: 'Where is my order?',
		wps_tofw_wismo_support_email: WISMO_SUPPORT_EMAIL,
		wps_tofw_wismo_support_url: WISMO_SUPPORT_URL,
		wps_tofw_wismo_llm_enable: 'on',
		wps_tofw_wismo_llm_endpoint: WISMO_LLM_ENDPOINT,
		wps_tofw_wismo_llm_api_key: WISMO_API_KEY,
		wps_tofw_wismo_llm_model: 'e2e-model',
		e2e_wismo_verify_limit: 0,
		...extra,
	} );
}

/** Turn the widget and LLM mode off and forget the key. */
export function disableWismo(): void {
	wpEval( `
		foreach ( array( 'wps_tofw_wismo_enable', 'wps_tofw_wismo_llm_enable', 'wps_tofw_wismo_llm_api_key', 'wps_tofw_wismo_llm_endpoint', 'e2e_wismo_verify_limit', 'e2e_wismo_llm_last' ) as $name ) {
			delete_option( $name );
		}
	` );
}

/** Reset the WISMO rate limit counters (all specs share one client IP). */
export function clearWismoRateLimits(): void {
	wpEval( `
		global $wpdb;
		$like = '%' . $wpdb->esc_like( 'wps_tofw_wismo_rl_' ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
		wp_cache_flush();
	` );
}

/** Last request the fake LLM received: { url, body } or false. */
export function lastLlmRequest(): { url: string; body: string } | false {
	return wpEvalJson( `return get_option( 'e2e_wismo_llm_last', false );` );
}

export interface E2ECustomer {
	id: number;
	login: string;
	password: string;
	email: string;
}

/** Create a WooCommerce customer user. */
export function createCustomer( prefix: string ): E2ECustomer {
	const suffix = `${ Date.now() }${ Math.floor( Math.random() * 1000 ) }`;
	const login = `${ prefix }${ suffix }`;
	const email = `${ login }@example.com`;
	const password = 'E2e-pass-8403!';
	const id = Number(
		wpCli( [ 'user', 'create', login, email, '--role=customer', `--user_pass=${ password }`, '--porcelain' ] )
	);
	return { id, login, password, email };
}

/** Log in through wp-login.php as any user (customers are not sent to wp-admin). */
export async function loginAs( page: Page, user: E2ECustomer ): Promise< void > {
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( user.login );
	await page.locator( '#user_pass' ).fill( user.password );
	await page.locator( '#wp-submit' ).click();
	await page.waitForURL( ( url ) => ! url.pathname.includes( 'wp-login.php' ) );
}

/** Widget locators. */
export function wismo( page: Page ) {
	const root = page.locator( '#wps-tofw-wismo' );
	return {
		root,
		launcher: root.locator( '.wps-tofw-wismo__launcher' ),
		panel: root.locator( '#wps-tofw-wismo-panel' ),
		orderInput: root.locator( '.wps-tofw-wismo__form input[type="text"]' ),
		emailInput: root.locator( '.wps-tofw-wismo__form input[type="email"]' ),
		verifyButton: root.locator( '.wps-tofw-wismo__form button[type="submit"]' ),
		botMessages: root.locator( '.wps-tofw-wismo__msg--bot' ),
		lastBot: root.locator( '.wps-tofw-wismo__msg--bot' ).last(),
		askInput: root.locator( '.wps-tofw-wismo__ask input' ),
		askButton: root.locator( '.wps-tofw-wismo__ask button[type="submit"]' ),
		chips: root.locator( '.wps-tofw-wismo__chip' ),
	};
}

/** Open the widget on the current page and wait for its first step. */
export async function openWismo( page: Page ): Promise< ReturnType< typeof wismo > > {
	const w = wismo( page );
	await w.launcher.click();
	await expect( w.panel ).toBeVisible();
	return w;
}

/** Fill and submit the order number + e-mail form, waiting for the verify response. */
export async function submitVerify( page: Page, orderId: number | string, email: string ): Promise< Locator > {
	const w = wismo( page );
	await w.orderInput.fill( String( orderId ) );
	await w.emailInput.fill( email );
	const response = page.waitForResponse(
		( r ) => r.url().includes( 'admin-ajax.php' ) && ( r.request().postData() || '' ).includes( 'action=wps_tofw_wismo_verify' )
	);
	await w.verifyButton.click();
	await response;
	return w.lastBot;
}
