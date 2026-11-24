/**
 * WPS-8395 helpers: review request notice state and data.
 */
import { phpValue, wpEval, wpEvalJson } from './wp-cli';

export const REVIEW_NOTICE_OPTION = 'wps_tofw_review_notice_state';
export const REVIEW_COUNT_TRANSIENT = 'wps_tofw_review_tracked_orders_count';
export const TRACKING_META_KEY = 'wps_tofw_package_tracking_number';

export interface ReviewNoticeState {
	status?: 'active' | 'dismissed' | 'rated';
	snooze_until?: number;
	reached_at?: number;
}

/** Forget the notice state and the cached tracked-orders count (fresh install behaviour). */
export function resetReviewNotice(): void {
	wpEval(
		`delete_option( ${ phpValue( REVIEW_NOTICE_OPTION ) } ); delete_transient( ${ phpValue( REVIEW_COUNT_TRANSIENT ) } );`
	);
}

/** Overwrite the notice state (and clear the count cache). */
export function setReviewNoticeState( state: ReviewNoticeState ): void {
	wpEval(
		`update_option( ${ phpValue( REVIEW_NOTICE_OPTION ) }, ${ phpValue( state ) }, false ); delete_transient( ${ phpValue( REVIEW_COUNT_TRANSIENT ) } );`
	);
}

/** Stored notice state (null when the option does not exist). */
export function getReviewNoticeState(): ReviewNoticeState | null {
	return wpEvalJson< ReviewNoticeState | null >(
		`$s = get_option( ${ phpValue( REVIEW_NOTICE_OPTION ) }, null ); return is_array( $s ) ? $s : null;`
	);
}

/** Number of orders (any status) with a non-empty tracking number, uncapped. */
export function countTrackedOrders(): number {
	return wpEvalJson< number >( `
		return count( wc_get_orders( array(
			'limit'      => -1,
			'return'     => 'ids',
			'type'       => 'shop_order',
			'status'     => array_keys( wc_get_order_statuses() ),
			'meta_query' => array( array( 'key' => ${ phpValue( TRACKING_META_KEY ) }, 'value' => '', 'compare' => '!=' ) ),
		) ) );
	` );
}

/** Create `count` orders with a tracking number in one WP-CLI call. Returns their IDs. */
export function createTrackedOrders( count: number, prefix = 'E2E8395' ): number[] {
	return wpEvalJson< number[] >( `
		$ids = array();
		for ( $i = 1; $i <= ${ Number( count ) }; $i++ ) {
			$order = wc_create_order();
			$order->set_billing_email( 'review-notice@example.com' );
			$order->update_meta_data( ${ phpValue( TRACKING_META_KEY ) }, ${ phpValue( prefix ) } . '-' . uniqid() . '-' . $i );
			$order->set_status( 'completed' );
			$order->save();
			$ids[] = $order->get_id();
		}
		return $ids;
	` );
}

/** Current server time (seconds). */
export function serverTime(): number {
	return wpEvalJson< number >( 'return time();' );
}

/** Create (or reset the password of) a user with the given role. */
export function ensureUser( login: string, role: string, password = 'password' ): number {
	return wpEvalJson< number >( `
		$login = ${ phpValue( login ) };
		$user  = get_user_by( 'login', $login );
		$id    = $user ? $user->ID : wp_insert_user( array( 'user_login' => $login, 'user_pass' => ${ phpValue( password ) }, 'user_email' => $login . '@example.com', 'role' => ${ phpValue( role ) } ) );
		if ( is_wp_error( $id ) ) { throw new Exception( $id->get_error_message() ); }
		wp_set_password( ${ phpValue( password ) }, $id );
		( new WP_User( $id ) )->set_role( ${ phpValue( role ) } );
		return $id;
	` );
}
