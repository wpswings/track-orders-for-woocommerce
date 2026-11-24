/**
 * Helpers for the WPS-8398 shipping plugin auto-import specs.
 */
import { phpValue, setOptions, wpEval, wpEvalJson } from './wp-cli';

/** Integration IDs (option "wps_tofw_auto_import_<id>"). */
export const AUTO_IMPORT_INTEGRATIONS = [
	'shipstation',
	'woocommerce_shipping',
	'shipment_tracking',
	'shippo',
	'sendcloud',
	'printful',
	'printify',
] as const;

export type AutoImportIntegration = ( typeof AUTO_IMPORT_INTEGRATIONS )[ number ];

const MU_PLUGIN_FILE = 'wps-8398-e2e-fake-shipping-plugins.php';

/**
 * Must-use plugin that makes ShipStation and WooCommerce Shipment Tracking look active
 * (the integrations check WC_SHIPSTATION_VERSION / wc_st_add_tracking_number()), so the
 * real plugins are not needed.
 */
const MU_PLUGIN_CODE = `<?php
/**
 * E2E (WPS-8398): pretend ShipStation and Shipment Tracking are active.
 */
if ( ! defined( 'WC_SHIPSTATION_VERSION' ) ) {
	define( 'WC_SHIPSTATION_VERSION', '0.0.0-e2e' );
}
if ( ! function_exists( 'wc_st_add_tracking_number' ) ) {
	function wc_st_add_tracking_number() {}
}
`;

/** Install the fake shipping plugins mu-plugin. */
export function installFakeShippingPlugins(): void {
	wpEvalJson( `
		if ( ! wp_mkdir_p( WPMU_PLUGIN_DIR ) ) {
			throw new Exception( 'Cannot create ' . WPMU_PLUGIN_DIR );
		}
		$file = trailingslashit( WPMU_PLUGIN_DIR ) . ${ phpValue( MU_PLUGIN_FILE ) };
		if ( false === file_put_contents( $file, ${ phpValue( MU_PLUGIN_CODE ) } ) ) {
			throw new Exception( 'Cannot write ' . $file );
		}
		return true;
	` );
}

/** Remove the fake shipping plugins mu-plugin. */
export function removeFakeShippingPlugins(): void {
	wpEval( `wp_delete_file( trailingslashit( WPMU_PLUGIN_DIR ) . ${ phpValue( MU_PLUGIN_FILE ) } );` );
}

/** Turn the given integrations on and every other one off. */
export function setEnabledIntegrations( ...enabled: AutoImportIntegration[] ): void {
	const options: Record< string, string > = {};
	for ( const id of AUTO_IMPORT_INTEGRATIONS ) {
		options[ `wps_tofw_auto_import_${ id }` ] = enabled.includes( id ) ? 'on' : '';
	}
	setOptions( options );
}

/** Fire the ShipStation plugin's shipnotify action for an order. */
export function shipStationShipNotify( orderId: number, trackingNumber: string, carrier: string ): void {
	wpEvalJson( `
		do_action(
			'woocommerce_shipstation_shipnotify',
			wc_get_order( ${ Number( orderId ) } ),
			array(
				'tracking_number' => ${ phpValue( trackingNumber ) },
				'carrier'         => ${ phpValue( carrier ) },
				'ship_date'       => gmdate( 'Y-m-d' ),
			)
		);
		return true;
	` );
}

/** Add an order note (as a shipping app would). */
export function addOrderNote( orderId: number, note: string ): void {
	wpEvalJson( `wc_get_order( ${ Number( orderId ) } )->add_order_note( ${ phpValue( note ) } ); return true;` );
}

/** Order notes written by the auto-import ("... imported automatically from ..."). */
export function autoImportNotes( orderId: number ): string[] {
	return wpEvalJson< string[] >( `
		$notes = wc_get_order_notes( array( 'order_id' => ${ Number( orderId ) } ) );
		$out   = array();
		foreach ( $notes as $note ) {
			if ( false !== strpos( $note->content, 'imported automatically from' ) ) {
				$out[] = $note->content;
			}
		}
		return $out;
	` );
}
