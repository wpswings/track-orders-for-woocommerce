/**
 * Store fixtures and plugin page lookups.
 */
import { phpValue, wpEvalJson } from './wp-cli';

/** SKU of the simple product created by global setup. */
export const E2E_PRODUCT_SKU = 'E2E-SIMPLE';

/** Create (once) a published simple product and return its ID. */
export function ensureSimpleProduct( sku = E2E_PRODUCT_SKU, name = 'E2E Simple Product', price = '10.00' ): number {
	return wpEvalJson< number >( `
		$id = wc_get_product_id_by_sku( ${ phpValue( sku ) } );
		if ( $id ) {
			return (int) $id;
		}
		$product = new WC_Product_Simple();
		$product->set_name( ${ phpValue( name ) } );
		$product->set_sku( ${ phpValue( sku ) } );
		$product->set_regular_price( ${ phpValue( price ) } );
		$product->set_status( 'publish' );
		return (int) $product->save();
	` );
}

/** ID of the product created by global setup. */
export function getProductId( sku = E2E_PRODUCT_SKU ): number {
	return wpEvalJson< number >( `return (int) wc_get_product_id_by_sku( ${ phpValue( sku ) } );` );
}

/**
 * Pages the plugin creates on activation (option "wps_tofw_tracking_page").
 * Keys: wps_track_order_page, wps_guest_track_order_page, wps_fedex_track_order.
 */
export type TrackingPageKey = 'wps_track_order_page' | 'wps_guest_track_order_page' | 'wps_fedex_track_order';

/** Permalink of a plugin tracking page, e.g. trackingPageUrl( 'wps_guest_track_order_page' ). */
export function trackingPageUrl( key: TrackingPageKey ): string {
	return wpEvalJson< string >( `
		$pages = get_option( 'wps_tofw_tracking_page', array() );
		$key   = ${ phpValue( key ) };
		return empty( $pages['pages'][ $key ] ) ? '' : get_permalink( $pages['pages'][ $key ] );
	` );
}
