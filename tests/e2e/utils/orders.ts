/**
 * WooCommerce order helpers (HPOS-safe: everything goes through the WC CRUD API).
 */
import { phpValue, wpEvalJson } from './wp-cli';
import { E2E_PRODUCT_SKU } from './store';

export interface CreateOrderOptions {
	/** Order status without the "wc-" prefix. Default "processing". */
	status?: string;
	/** Default "guest@example.com". */
	billing_email?: string;
	billing_first_name?: string;
	billing_last_name?: string;
	/** Customer user ID, 0 for a guest order. Default 0. */
	customer_id?: number;
	/** Order meta to save (key => value). */
	meta?: Record< string, unknown >;
	/** SKU of the product to add. Default: the product created by global setup. Pass null for no items. */
	product_sku?: string | null;
	quantity?: number;
}

export interface CreatedOrder {
	id: number;
	order_key: string;
	status: string;
	billing_email: string;
	/** Customer "view order" URL (needs the order owner logged in). */
	view_url: string;
}

/**
 * Create an order with wc_create_order() through `wp eval`.
 *
 * @example
 *   const order = createOrder( { status: 'completed', billing_email: 'a@b.com', meta: { wps_tofw_package_tracking_number: 'TRK1' } } );
 */
export function createOrder( options: CreateOrderOptions = {} ): CreatedOrder {
	const data = {
		status: 'processing',
		billing_email: 'guest@example.com',
		billing_first_name: 'E2E',
		billing_last_name: 'Customer',
		customer_id: 0,
		meta: {},
		product_sku: E2E_PRODUCT_SKU,
		quantity: 1,
		...options,
	};
	return wpEvalJson< CreatedOrder >( `
		$d     = ${ phpValue( data ) };
		$order = wc_create_order( array( 'customer_id' => (int) $d['customer_id'] ) );
		if ( is_wp_error( $order ) ) {
			throw new Exception( $order->get_error_message() );
		}
		if ( ! empty( $d['product_sku'] ) ) {
			$product_id = wc_get_product_id_by_sku( $d['product_sku'] );
			if ( $product_id ) {
				$order->add_product( wc_get_product( $product_id ), (int) $d['quantity'] );
			}
		}
		$order->set_billing_email( $d['billing_email'] );
		$order->set_billing_first_name( $d['billing_first_name'] );
		$order->set_billing_last_name( $d['billing_last_name'] );
		$order->set_billing_country( 'US' );
		foreach ( (array) $d['meta'] as $key => $value ) {
			$order->update_meta_data( $key, $value );
		}
		$order->calculate_totals();
		$order->save();
		$order->update_status( $d['status'] );
		return array(
			'id'            => $order->get_id(),
			'order_key'     => $order->get_order_key(),
			'status'        => $order->get_status(),
			'billing_email' => $order->get_billing_email(),
			'view_url'      => $order->get_view_order_url(),
		);
	` );
}

/** Read one order meta value (HPOS-safe). */
export function getOrderMeta< T = unknown >( orderId: number, key: string ): T {
	return wpEvalJson< T >(
		`$order = wc_get_order( ${ Number( orderId ) } ); return $order ? $order->get_meta( ${ phpValue( key ) }, true ) : null;`
	);
}

/** Update order meta values (HPOS-safe). */
export function updateOrderMeta( orderId: number, meta: Record< string, unknown > ): void {
	wpEvalJson( `
		$order = wc_get_order( ${ Number( orderId ) } );
		foreach ( ${ phpValue( meta ) } as $key => $value ) {
			$order->update_meta_data( $key, $value );
		}
		$order->save();
		return true;
	` );
}

/** Current order status (without "wc-"). */
export function getOrderStatus( orderId: number ): string {
	return wpEvalJson< string >(
		`$order = wc_get_order( ${ Number( orderId ) } ); return $order ? $order->get_status() : '';`
	);
}
