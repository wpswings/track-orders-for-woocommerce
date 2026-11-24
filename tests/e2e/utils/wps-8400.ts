/**
 * WPS-8400 helpers: a US shipping zone with a flat-rate method for the estimated delivery
 * date (EDD) rules, date maths and Store API cart helpers for block checkout.
 */
import { APIRequestContext, expect } from '@playwright/test';
import { phpValue, wpEvalJson } from './wp-cli';

export const EDD_OPTION = 'wps_tofw_edd_settings';
export const EDD_ZONE_NAME = 'E2E WPS-8400 US';

/** Create (or reuse) a shipping zone for the US with one flat-rate method; returns the method instance id. */
export function ensureEddZone( title = 'EDD Flat rate', cost = '7' ): number {
	return wpEvalJson< number >( `
		$name = ${ phpValue( EDD_ZONE_NAME ) };
		foreach ( WC_Shipping_Zones::get_zones() as $data ) {
			if ( $data['zone_name'] === $name ) {
				foreach ( $data['shipping_methods'] as $method ) {
					return (int) $method->get_instance_id();
				}
			}
		}
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( $name );
		$zone->set_zone_order( 0 );
		$zone->add_location( 'US', 'country' );
		$zone->save();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $instance_id . '_settings',
			array( 'title' => ${ phpValue( title ) }, 'tax_status' => 'none', 'cost' => ${ phpValue( cost ) } )
		);
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		return (int) $instance_id;
	` );
}

/** Delete the zone created by ensureEddZone(). */
export function deleteEddZone(): void {
	wpEvalJson( `
		foreach ( WC_Shipping_Zones::get_zones() as $data ) {
			if ( $data['zone_name'] === ${ phpValue( EDD_ZONE_NAME ) } ) {
				WC_Shipping_Zones::delete_zone( $data['id'] );
			}
		}
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		return true;
	` );
}

/** Today in the site timezone (Y-m-d). */
export function siteToday(): string {
	return wpEvalJson< string >( `return wp_date( 'Y-m-d' );` );
}

/** Add calendar days to a Y-m-d date. */
export function addDays( ymd: string, days: number ): string {
	const date = new Date( `${ ymd }T12:00:00Z` );
	date.setUTCDate( date.getUTCDate() + days );
	return date.toISOString().slice( 0, 10 );
}

/** Read an option as raw PHP value (false when missing), JSON-decoded. */
export function readOption( name: string ): unknown {
	return wpEvalJson( `return get_option( ${ phpValue( name ) }, null );` );
}

/** Restore options saved with readOption() (null = delete). */
export function restoreOptions( saved: Record< string, unknown > ): void {
	wpEvalJson( `
		foreach ( ${ phpValue( saved ) } as $name => $value ) {
			if ( null === $value ) {
				delete_option( $name );
			} else {
				update_option( $name, $value );
			}
		}
		return true;
	` );
}

/** Store API nonce for the current browser session. */
async function storeNonce( request: APIRequestContext ): Promise< string > {
	const response = await request.get( '/wp-json/wc/store/v1/cart' );
	expect( response.ok() ).toBeTruthy();
	const nonce = response.headers().nonce;
	expect( nonce, 'Store API nonce header' ).toBeTruthy();
	return nonce;
}

/** Empty the cart, add a product and set a US shipping / billing address through the Store API. */
export async function prepareBlockCart( request: APIRequestContext, productId: number, email: string ): Promise< void > {
	const nonce = await storeNonce( request );
	const headers = { Nonce: nonce };

	expect( ( await request.delete( '/wp-json/wc/store/v1/cart/items', { headers } ) ).ok() ).toBeTruthy();
	expect( ( await request.post( '/wp-json/wc/store/v1/cart/add-item', { headers, data: { id: productId, quantity: 1 } } ) ).ok() ).toBeTruthy();

	const address = {
		first_name: 'Edd',
		last_name: 'Buyer',
		address_1: '1 Delivery Way',
		city: 'San Francisco',
		state: 'CA',
		postcode: '94103',
		country: 'US',
	};
	const update = await request.post( '/wp-json/wc/store/v1/cart/update-customer', {
		headers,
		data: { shipping_address: address, billing_address: { ...address, email } },
	} );
	expect( update.ok(), await update.text() ).toBeTruthy();
}
