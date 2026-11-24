/**
 * WPS-8396 helpers: capture outgoing e-mail, enable application passwords and Action Scheduler runs.
 */
import { phpValue, wpCli, wpEval, wpEvalJson } from './wp-cli';

const MU_PLUGIN = 'wps-e2e-8396.php';
export const MAIL_LOG_OPTION = 'wps_e2e_8396_mail_log';

/**
 * Install a must-use plugin that short-circuits wp_mail() (logging recipient + subject to an option)
 * and allows application passwords on the http:// test site.
 */
export function installTestMuPlugin(): void {
	const code = `<?php
// E2E helper for WPS-8396 (removed after the spec).
add_filter( 'wp_is_application_passwords_available', '__return_true' );
add_filter( 'pre_wp_mail', function ( $return, $atts ) {
	$log   = get_option( '${ MAIL_LOG_OPTION }', array() );
	$log   = is_array( $log ) ? $log : array();
	$log[] = array( 'to' => is_array( $atts['to'] ) ? implode( ',', $atts['to'] ) : (string) $atts['to'], 'subject' => (string) $atts['subject'] );
	update_option( '${ MAIL_LOG_OPTION }', $log, false );
	return true;
}, 10, 2 );
`;
	wpEval( `
		$dir = WPMU_PLUGIN_DIR;
		if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }
		file_put_contents( trailingslashit( $dir ) . '${ MU_PLUGIN }', ${ phpValue( code ) } );
		delete_option( '${ MAIL_LOG_OPTION }' );
	` );
}

/** Remove the must-use plugin and the mail log. */
export function removeTestMuPlugin(): void {
	wpEval( `
		$file = trailingslashit( WPMU_PLUGIN_DIR ) . '${ MU_PLUGIN }';
		if ( file_exists( $file ) ) { unlink( $file ); }
		delete_option( '${ MAIL_LOG_OPTION }' );
	` );
}

export interface LoggedMail {
	to: string;
	subject: string;
}

/** E-mails captured since installTestMuPlugin(). */
export function capturedMails(): LoggedMail[] {
	return wpEvalJson< LoggedMail[] >( `$log = get_option( '${ MAIL_LOG_OPTION }', array() ); return is_array( $log ) ? array_values( $log ) : array();` );
}

/** Run the due Action Scheduler actions of a group (what WP-Cron / the async runner would do). */
export function runScheduledActions( hook: string ): void {
	// Run pending actions of the hook directly. Filtering by --group fails with
	// "group does not exist" when the async runner already claimed the action,
	// so match on the hook and treat "nothing to run" as a no-op.
	try {
		wpCli( [ 'action-scheduler', 'run', `--hooks=${ hook }`, '--force' ] );
	} catch ( error ) {
		// Nothing pending (or already being processed) – the caller polls the result.
	}
}

/** Create a user if missing and return its ID. */
export function ensureUser( login: string, email: string, role: string ): number {
	return wpEvalJson< number >( `
		$user = get_user_by( 'login', ${ phpValue( login ) } );
		if ( $user ) {
			return (int) $user->ID;
		}
		return (int) wp_insert_user( array( 'user_login' => ${ phpValue( login ) }, 'user_email' => ${ phpValue( email ) }, 'user_pass' => wp_generate_password(), 'role' => ${ phpValue( role ) } ) );
	` );
}

/** Create an application password for a user; returns the plain password. */
export function createApplicationPassword( login: string, name: string ): string {
	return wpEvalJson< string >( `
		$user = get_user_by( 'login', ${ phpValue( login ) } );
		$created = WP_Application_Passwords::create_new_application_password( $user->ID, array( 'name' => ${ phpValue( name ) } ) );
		if ( is_wp_error( $created ) ) {
			throw new Exception( $created->get_error_message() );
		}
		return $created[0];
	` );
}

/** Delete the application passwords of a user created with the given name. */
export function deleteApplicationPasswords( login: string, name: string ): void {
	wpEval( `
		$user = get_user_by( 'login', ${ phpValue( login ) } );
		if ( $user ) {
			foreach ( WP_Application_Passwords::get_user_application_passwords( $user->ID ) as $item ) {
				if ( $item['name'] === ${ phpValue( name ) } ) {
					WP_Application_Passwords::delete_application_password( $user->ID, $item['uuid'] );
				}
			}
		}
	` );
}

/** Basic auth header value. */
export function basicAuth( user: string, password: string ): string {
	return 'Basic ' + Buffer.from( `${ user }:${ password }` ).toString( 'base64' );
}

/** Where the tracking shipments meta of an order is stored (HPOS tables or post meta). */
export function shipmentsStorage( orderId: number ): { hpos: boolean; hposRows: number; postmetaRows: number } {
	return wpEvalJson( `
		global $wpdb;
		$id   = ${ Number( orderId ) };
		$hpos = class_exists( '\\\\Automattic\\\\WooCommerce\\\\Utilities\\\\OrderUtil' ) && \\Automattic\\WooCommerce\\Utilities\\OrderUtil::custom_orders_table_usage_is_enabled();
		$hpos_rows = 0;
		if ( $hpos ) {
			$hpos_rows = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders_meta WHERE order_id = %d AND meta_key = 'wps_tofw_tracking_shipments'", $id ) );
		}
		$post_rows = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'wps_tofw_tracking_shipments'", $id ) );
		return array( 'hpos' => $hpos, 'hposRows' => $hpos_rows, 'postmetaRows' => $post_rows );
	` );
}
