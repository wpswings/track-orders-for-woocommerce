/**
 * WPS-8397 helpers: fake 17TRACK API (mu-plugin) and carrier status sync triggers.
 *
 * The real 17TRACK API is never called: a must-use plugin short-circuits every request to
 * api.17track.net through the `pre_http_request` filter and answers from the option
 * `e2e_wps_8397_17track` ( tracking number => { status, events } ). It also limits the sync
 * order query to orders flagged with the `_e2e_wps_8397` meta, so orders created by other specs
 * are never looked up (or counted against the monthly limit) by these tests.
 */
import { createOrder, CreatedOrder } from './orders';
import { phpValue, wpEval, wpEvalJson } from './wp-cli';

export const SYNC_TAB = 'track-orders-for-woocommerce-carrier-sync';
export const USAGE_OPTION = 'wps_tofw_carrier_sync_usage';
export const FAKE_RESPONSES_OPTION = 'e2e_wps_8397_17track';
export const FAKE_CALLS_OPTION = 'e2e_wps_8397_17track_calls';
export const ORDER_FLAG_META = '_e2e_wps_8397';

const MU_PLUGIN_FILE = 'e2e-wps-8397-fake-17track.php';

const MU_PLUGIN = `<?php
/**
 * Plugin Name: E2E WPS-8397 fake 17TRACK API
 */
add_filter(
	'pre_http_request',
	function ( $pre, $args, $url ) {
		if ( false === strpos( (string) $url, 'api.17track.net' ) ) {
			return $pre;
		}
		$calls   = (array) get_option( '${ FAKE_CALLS_OPTION }', array() );
		$calls[] = array( 'url' => $url, 'body' => isset( $args['body'] ) ? $args['body'] : '', 'token' => isset( $args['headers']['17token'] ) ? $args['headers']['17token'] : '' );
		update_option( '${ FAKE_CALLS_OPTION }', $calls, false );

		$map      = (array) get_option( '${ FAKE_RESPONSES_OPTION }', array() );
		$entries  = json_decode( isset( $args['body'] ) ? $args['body'] : '[]', true );
		$register = '/register' === substr( wp_parse_url( $url, PHP_URL_PATH ), -9 );
		$accepted = array();
		$rejected = array();
		foreach ( (array) $entries as $entry ) {
			$number = isset( $entry['number'] ) ? (string) $entry['number'] : '';
			$fake   = isset( $map[ $number ] ) ? (array) $map[ $number ] : array( 'status' => 'InTransit' );
			if ( $register ) {
				$accepted[] = array( 'number' => $number );
			} elseif ( ! empty( $fake['error'] ) ) {
				$rejected[] = array( 'number' => $number, 'error' => array( 'code' => -18010012, 'message' => $fake['error'] ) );
			} else {
				$accepted[] = array(
					'number'     => $number,
					'track_info' => array(
						'latest_status' => array( 'status' => $fake['status'] ),
						'tracking'      => array( 'providers' => array( array( 'events' => isset( $fake['events'] ) ? $fake['events'] : array() ) ) ),
					),
				);
			}
		}
		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( array( 'code' => 0, 'data' => array( 'accepted' => $accepted, 'rejected' => $rejected ) ) ),
			'response' => array( 'code' => 200, 'message' => 'OK' ),
			'cookies'  => array(),
			'filename' => null,
		);
	},
	1,
	3
);
add_filter(
	'wps_tofw_carrier_sync_order_query_args',
	function ( $args ) {
		$args['meta_query'][] = array( 'key' => '${ ORDER_FLAG_META }', 'value' => '1' );
		return $args;
	}
);
`;

/** Install the fake 17TRACK mu-plugin. */
export function installFake17Track(): void {
	wpEval( `
		wp_mkdir_p( WPMU_PLUGIN_DIR );
		file_put_contents( WPMU_PLUGIN_DIR . '/${ MU_PLUGIN_FILE }', ${ phpValue( MU_PLUGIN ) } );
	` );
}

/** Remove the fake 17TRACK mu-plugin. */
export function removeFake17Track(): void {
	wpEval( `@unlink( WPMU_PLUGIN_DIR . '/${ MU_PLUGIN_FILE }' );` );
}

export interface FakeEvent {
	time_utc: string;
	description: string;
	location?: string;
	sub_status?: string;
}

/** What the fake API answers for a tracking number. */
export function fakeTrackingResponse( number: string, status: string, events: FakeEvent[] = [] ): void {
	wpEval( `
		$map = (array) get_option( '${ FAKE_RESPONSES_OPTION }', array() );
		$map[ ${ phpValue( number ) } ] = array( 'status' => ${ phpValue( status ) }, 'events' => ${ phpValue( events ) } );
		update_option( '${ FAKE_RESPONSES_OPTION }', $map, false );
	` );
}

/** Requests the fake API received (url, body, token). */
export function fakeCalls(): { url: string; body: string; token: string }[] {
	return wpEvalJson( `return array_values( (array) get_option( '${ FAKE_CALLS_OPTION }', array() ) );` );
}

/** Set this month's lookup counter. */
export function setUsage( count: number ): void {
	wpEval( `update_option( '${ USAGE_OPTION }', array( 'period' => wp_date( 'Y-m' ), 'count' => ${ Number( count ) } ), false );` );
}

/** Lookups used this month (as the plugin computes it). */
export function getUsage(): number {
	return wpEvalJson< number >( `return ( new Track_Orders_For_Woocommerce_Carrier_Sync() )->get_usage_count();` );
}

/** Create an order (owned by the admin, user 1) with a tracking number, flagged for these tests. */
export function createTrackedOrder( trackingNumber: string, carrier = 'ups' ): CreatedOrder {
	return createOrder( {
		status: 'processing',
		customer_id: 1,
		billing_email: 'carrier-sync@example.com',
		meta: {
			wps_tofw_package_tracking_number: trackingNumber,
			wps_tofw_selected_shipping_service: carrier,
			[ ORDER_FLAG_META ]: '1',
		},
	} );
}

/**
 * Run one sync batch exactly as Action Scheduler does: fire the batch hook the plugin
 * registers ( wps_tofw_carrier_sync_batch, chain 0 ).
 */
export function runSyncBatch(): void {
	wpEval( `do_action( 'wps_tofw_carrier_sync_batch', 0 );` );
}

/** Run the pending carrier sync actions queued in Action Scheduler (e.g. by "Run Sync Now"). Returns how many ran. */
export function runQueuedSyncActions(): number {
	return wpEvalJson< number >( `
		$ids = as_get_scheduled_actions(
			array( 'hook' => 'wps_tofw_carrier_sync_batch', 'group' => 'wps-tofw-carrier-sync', 'status' => ActionScheduler_Store::STATUS_PENDING, 'per_page' => -1 ),
			'ids'
		);
		foreach ( $ids as $id ) {
			ActionScheduler::runner()->process_action( $id, 'E2E' );
		}
		return count( $ids );
	` );
}

/** Reset carrier sync state used by the specs. */
export function resetCarrierSync( extra: Record< string, unknown > = {} ): void {
	wpEval( `
		delete_option( '${ USAGE_OPTION }' );
		delete_option( '${ FAKE_CALLS_OPTION }' );
		delete_option( 'wps_tofw_carrier_sync_limit_reached' );
		delete_transient( 'wps_tofw_carrier_sync_lock' );
		update_option( 'wps_tofw_carrier_sync_enable', 'on' );
		update_option( 'wps_tofw_carrier_sync_17track_api_key', 'e2e-fake-key' );
		update_option( 'wps_tofw_carrier_sync_auto_complete', 'off' );
		foreach ( ${ phpValue( extra ) } as $name => $value ) {
			update_option( $name, $value );
		}
	` );
}
