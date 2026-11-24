/**
 * WPS-8401 helpers: a minimal WCFM stand-in, vendors, marketplace orders and vendor sessions.
 *
 * No marketplace plugin is installed in wp-env. The mu-plugin below defines the three WCFM
 * functions the plugin's real WCFM integration uses (wcfm_is_vendor, wcfm_get_vendor_id_by_post,
 * wcfm_get_vendor_store_name), backed by user meta, so the integration activates and the
 * plugin code under test runs unchanged. It also exposes the vendor tracking nonce of the
 * logged-in user (the vendor dashboard that normally prints it is part of WCFM) and stops
 * Google geocoding requests from the map template.
 */
import { APIRequestContext, Browser, BrowserContext, expect } from '@playwright/test';
import { ensureSimpleProduct } from './store';
import { phpValue, wpCli, wpEval, wpEvalJson } from './wp-cli';

const MU_PLUGIN_FILE = 'wps-e2e-8401-fake-wcfm.php';

const MU_PLUGIN = `<?php
/**
 * Plugin Name: E2E WCFM stand-in (WPS-8401)
 */
if ( ! function_exists( 'wcfm_is_vendor' ) ) {
	function wcfm_is_vendor( $user_id = false ) {
		$user_id = $user_id ? $user_id : get_current_user_id();
		return $user_id > 0 && '1' === (string) get_user_meta( $user_id, '_wps_e2e_wcfm_vendor', true );
	}
}
if ( ! function_exists( 'wcfm_get_vendor_id_by_post' ) ) {
	function wcfm_get_vendor_id_by_post( $product_id ) {
		return (int) get_post_meta( $product_id, '_wps_e2e_wcfm_vendor_id', true );
	}
}
if ( ! function_exists( 'wcfm_get_vendor_store_name' ) ) {
	function wcfm_get_vendor_store_name( $vendor_id ) {
		return (string) get_user_meta( $vendor_id, '_wps_e2e_wcfm_store', true );
	}
}
add_action(
	'wp_ajax_wps_e2e_8401_nonce',
	function () {
		wp_send_json_success( wp_create_nonce( 'wps_tofw_vendor_shipment' ) );
	}
);
add_filter(
	'pre_http_request',
	function ( $pre, $args, $url ) {
		if ( false !== strpos( (string) wp_parse_url( $url, PHP_URL_HOST ), 'google' ) ) {
			return array( 'headers' => array(), 'body' => '{"results":[]}', 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array(), 'filename' => null );
		}
		return $pre;
	},
	10,
	3
);
`;

export const VENDOR_PASSWORD = 'E2e-vendor-pass-8401';

export function installFakeWcfm(): void {
	wpEval( `
		wp_mkdir_p( WPMU_PLUGIN_DIR );
		file_put_contents( trailingslashit( WPMU_PLUGIN_DIR ) . ${ phpValue( MU_PLUGIN_FILE ) }, ${ phpValue( MU_PLUGIN ) } );
	` );
}

export function removeFakeWcfm(): void {
	wpEval( `@unlink( trailingslashit( WPMU_PLUGIN_DIR ) . ${ phpValue( MU_PLUGIN_FILE ) } );` );
}

/** Create (or reuse) a user; vendors get the WCFM stand-in meta and a store name. Returns the user id. */
export function ensureUser( login: string, role: string, store: string | null = null ): number {
	return wpEvalJson< number >( `
		$login = ${ phpValue( login ) };
		$user  = get_user_by( 'login', $login );
		$id    = $user ? $user->ID : wp_insert_user(
			array(
				'user_login'   => $login,
				'user_pass'    => ${ phpValue( VENDOR_PASSWORD ) },
				'user_email'   => $login . '@example.com',
				'role'         => ${ phpValue( role ) },
				'display_name' => $login,
			)
		);
		if ( is_wp_error( $id ) ) {
			throw new Exception( $id->get_error_message() );
		}
		wp_set_password( ${ phpValue( VENDOR_PASSWORD ) }, $id );
		$store = ${ phpValue( store ) };
		if ( null !== $store ) {
			update_user_meta( $id, '_wps_e2e_wcfm_vendor', '1' );
			update_user_meta( $id, '_wps_e2e_wcfm_store', $store );
		}
		return (int) $id;
	` );
}

export interface MarketplaceOrder {
	id: number;
	/** Line item ids in the order given. */
	items: number[];
	view_url: string;
	billing_email: string;
}

/**
 * Create an order whose line items belong to vendors (WCFM stores the vendor in the item meta `_vendor_id`).
 */
export function createMarketplaceOrder( options: {
	customer_id?: number;
	billing_email: string;
	items: Array< { sku: string; name: string; qty: number; vendor_id: number } >;
	meta?: Record< string, unknown >;
} ): MarketplaceOrder {
	for ( const item of options.items ) {
		ensureSimpleProduct( item.sku, item.name, '10.00' );
	}
	return wpEvalJson< MarketplaceOrder >( `
		$d     = ${ phpValue( options ) };
		$order = wc_create_order( array( 'customer_id' => isset( $d['customer_id'] ) ? (int) $d['customer_id'] : 0 ) );
		$ids   = array();
		foreach ( $d['items'] as $row ) {
			$item_id = $order->add_product( wc_get_product( wc_get_product_id_by_sku( $row['sku'] ) ), (int) $row['qty'] );
			$item    = $order->get_item( $item_id );
			$item->update_meta_data( '_vendor_id', (int) $row['vendor_id'] );
			$item->save();
			$ids[] = (int) $item_id;
		}
		$order->set_billing_email( $d['billing_email'] );
		$order->set_billing_first_name( 'Market' );
		$order->set_billing_last_name( 'Buyer' );
		$order->set_billing_country( 'US' );
		foreach ( isset( $d['meta'] ) ? (array) $d['meta'] : array() as $key => $value ) {
			$order->update_meta_data( $key, $value );
		}
		$order->calculate_totals();
		$order->save();
		$order->update_status( 'processing' );
		return array(
			'id'            => $order->get_id(),
			'items'         => $ids,
			'view_url'      => $order->get_view_order_url(),
			'billing_email' => $order->get_billing_email(),
		);
	` );
}

/** A browser context logged in as the given user (front end login, works for non-admin roles). */
export async function loggedInContext( browser: Browser, login: string, baseURL: string ): Promise< BrowserContext > {
	const context = await browser.newContext( { storageState: { cookies: [], origins: [] }, baseURL } );
	const page = await context.newPage();
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( login );
	await page.locator( '#user_pass' ).fill( VENDOR_PASSWORD );
	await page.locator( '#wp-submit' ).click();
	await page.waitForURL( ( url ) => ! url.pathname.endsWith( '/wp-login.php' ) );
	await page.close();
	return context;
}

/** Vendor tracking nonce of the session (what the vendor dashboard form would print). */
export async function vendorNonce( request: APIRequestContext ): Promise< string > {
	const response = await request.post( '/wp-admin/admin-ajax.php', { form: { action: 'wps_e2e_8401_nonce' } } );
	expect( response.ok() ).toBeTruthy();
	const json = await response.json();
	expect( json.success ).toBe( true );
	return json.data as string;
}

/** POST the vendor "save shipment" AJAX action. */
export async function saveVendorShipment(
	request: APIRequestContext,
	data: { nonce: string; order_id: number; carrier: string; tracking_number: string; items: Record< number, number >; date?: string }
): Promise< { status: number; body: string; json: { success?: boolean; data?: { message?: string } } | number | null } > {
	const form: Record< string, string > = {
		action: 'wps_tofw_save_vendor_shipment',
		nonce: data.nonce,
		marketplace: 'wcfm',
		order_id: String( data.order_id ),
		carrier: data.carrier,
		tracking_number: data.tracking_number,
		date: data.date || '2026-11-20',
	};
	for ( const [ itemId, qty ] of Object.entries( data.items ) ) {
		form[ `items[${ itemId }]` ] = String( qty );
	}
	const response = await request.post( '/wp-admin/admin-ajax.php', { form } );
	const body = await response.text();
	let json = null;
	try {
		json = JSON.parse( body );
	} catch ( e ) {
		json = null;
	}
	return { status: response.status(), body, json };
}

/** Delete E2E users created by ensureUser(). */
export function deleteUsers( logins: string[] ): void {
	for ( const login of logins ) {
		const id = wpEvalJson< number >( `$u = get_user_by( 'login', ${ phpValue( login ) } ); return $u ? $u->ID : 0;` );
		if ( id ) {
			wpCli( [ 'user', 'delete', String( id ), '--yes' ] );
		}
	}
}
