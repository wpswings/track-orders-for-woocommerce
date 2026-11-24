/**
 * WP-CLI helpers for E2E tests.
 *
 * Every command runs inside the wp-env "cli" container:
 *   wp-env run cli wp -- <args>
 * Set E2E_WP_CLI to run WP-CLI some other way, e.g. E2E_WP_CLI="wp --path=/var/www/html".
 */
import { execFileSync } from 'child_process';
import path from 'path';

/** Plugin root (the folder holding .wp-env.json). */
export const PLUGIN_ROOT = path.resolve( __dirname, '..', '..', '..' );

function wpEnvBin(): string {
	// @wordpress/env exports lib/env.js; the CLI lives in ../bin/wp-env.
	const envMain = require.resolve( '@wordpress/env', { paths: [ PLUGIN_ROOT ] } );
	return path.join( path.dirname( path.dirname( envMain ) ), 'bin', 'wp-env' );
}

/** Split a command string into arguments, honouring single and double quotes. */
export function splitArgs( command: string ): string[] {
	const args: string[] = [];
	let current = '';
	let quote: string | null = null;
	let hasToken = false;
	for ( const char of command.trim() ) {
		if ( quote ) {
			if ( char === quote ) {
				quote = null;
			} else {
				current += char;
			}
		} else if ( char === '"' || char === "'" ) {
			quote = char;
			hasToken = true;
		} else if ( /\s/.test( char ) ) {
			if ( hasToken || current ) {
				args.push( current );
			}
			current = '';
			hasToken = false;
		} else {
			current += char;
		}
	}
	if ( hasToken || current ) {
		args.push( current );
	}
	return args;
}

/**
 * Run a WP-CLI command and return its trimmed stdout.
 *
 * @example
 *   wpCli( 'option get blogname' );
 *   wpCli( [ 'post', 'create', '--post_title=Hello world', '--porcelain' ] );
 */
export function wpCli( command: string | string[] ): string {
	const args = Array.isArray( command ) ? command : splitArgs( command );
	const custom = process.env.E2E_WP_CLI;
	let file: string;
	let fileArgs: string[];
	if ( custom ) {
		const [ bin, ...rest ] = splitArgs( custom );
		file = bin;
		fileArgs = [ ...rest, ...args ];
	} else {
		file = process.execPath;
		fileArgs = [ wpEnvBin(), 'run', 'cli', 'wp', '--', ...args ];
	}
	try {
		const out = execFileSync( file, fileArgs, {
			cwd: PLUGIN_ROOT,
			encoding: 'utf8',
			stdio: [ 'ignore', 'pipe', 'pipe' ],
			maxBuffer: 32 * 1024 * 1024,
		} );
		return out
			.split( '\n' )
			// Drop wp-env spinner/status lines if they end up on stdout.
			.filter( ( line ) => ! /^\s*[ℹ✔✖⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏]/.test( line ) )
			.join( '\n' )
			.trim();
	} catch ( error ) {
		const err = error as { stdout?: string; stderr?: string; message: string };
		throw new Error(
			`WP-CLI command failed: wp ${ args.join( ' ' ) }\n${ err.stdout || '' }\n${ err.stderr || err.message }`
		);
	}
}

/** Run PHP code with `wp eval` and return what it echoes. */
export function wpEval( php: string ): string {
	return wpCli( [ 'eval', php ] );
}

/**
 * Run PHP code with `wp eval` and return the JSON-decoded value it `return`s.
 *
 * @example
 *   const id = wpEvalJson< number >( "return wc_get_product_id_by_sku( 'E2E-SIMPLE' );" );
 */
export function wpEvalJson< T = unknown >( php: string ): T {
	const marker = 'E2E_JSON:';
	const out = wpEval(
		`$e2e_result = ( function () { ${ php } } )(); echo "\\n${ marker }" . wp_json_encode( $e2e_result ) . "\\n";`
	);
	const line = out.split( '\n' ).reverse().find( ( l ) => l.startsWith( marker ) );
	if ( ! line ) {
		throw new Error( `wpEvalJson: no result marker in output:\n${ out }` );
	}
	return JSON.parse( line.slice( marker.length ) ) as T;
}

/** Encode a JS value so PHP code can read it with phpValue(). */
export function phpValue( value: unknown ): string {
	const encoded = Buffer.from( JSON.stringify( value ), 'utf8' ).toString( 'base64' );
	return `json_decode( base64_decode( '${ encoded }' ), true )`;
}

/** Set a WordPress option (strings, numbers, booleans, arrays and objects are supported). */
export function setOption( name: string, value: unknown ): void {
	wpEval( `update_option( ${ phpValue( name ) }, ${ phpValue( value ) } );` );
}

/** Set several options at once (one WP-CLI call). */
export function setOptions( options: Record< string, unknown > ): void {
	wpEval(
		`foreach ( ${ phpValue( options ) } as $e2e_name => $e2e_value ) { update_option( $e2e_name, $e2e_value ); }`
	);
}

/** Read a WordPress option (decoded from JSON, so arrays come back as objects/arrays). */
export function getOption< T = unknown >( name: string ): T {
	return wpEvalJson< T >( `return get_option( ${ phpValue( name ) } );` );
}

/** Delete a WordPress option. */
export function deleteOption( name: string ): void {
	wpEval( `delete_option( ${ phpValue( name ) } );` );
}
