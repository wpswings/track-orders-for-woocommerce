/**
 * Plugin specific constants and setup used by global setup and specs.
 */
import { setOptions, wpCli, wpEval } from './wp-cli';
import { ensureSimpleProduct } from './store';

/** Plugin folder/slug inside wp-content/plugins (see "mappings" in .wp-env.json). */
export const PLUGIN_SLUG = 'track-orders-for-woocommerce';

/** Admin settings page (WP Swings > Track Orders For Woocommerce). */
export const SETTINGS_PAGE = '/wp-admin/admin.php?page=track_orders_for_woocommerce_menu';

/** URL of a settings tab, e.g. settingsTabUrl( 'track-orders-for-woocommerce-track-order' ). */
export function settingsTabUrl( tab: string ): string {
	return `${ SETTINGS_PAGE }&tofw_tab=${ encodeURIComponent( tab ) }`;
}

/** Options that turn the plugin features on for the E2E site. */
export const DEFAULT_PLUGIN_OPTIONS: Record< string, unknown > = {
	// General Setting > Enable plugin.
	wps_tofw_is_plugin_enable: 'on',
	// Track Order > Enable track order feature (needed for the track order / guest pages).
	tofw_enable_track_order: 'on',
	// Hide the WP Swings onboarding popup on the settings page.
	wps_msp_onboarding_data_sent: 'sent',
};

/** Activate plugins (idempotent; already active plugins are skipped). */
export function activatePlugins( ...slugs: string[] ): void {
	for ( const slug of slugs ) {
		wpCli( [ 'plugin', 'activate', slug ] );
	}
}

/** WooCommerce store basics: skip onboarding, currency, address, pages, permalinks and a flat-rate zone. */
export function setupStore(): void {
	setOptions( {
		woocommerce_onboarding_profile: { skipped: true },
		woocommerce_task_list_hidden: 'yes',
		woocommerce_extended_task_list_hidden: 'yes',
		woocommerce_task_list_complete: 'yes',
		woocommerce_show_marketplace_suggestions: 'no',
		woocommerce_allow_tracking: 'no',
		woocommerce_coming_soon: 'no',
		woocommerce_store_pages_only: 'no',
		woocommerce_currency: 'USD',
		woocommerce_default_country: 'US:CA',
		woocommerce_store_address: '1 E2E Street',
		woocommerce_store_city: 'San Francisco',
		woocommerce_store_postcode: '94103',
		woocommerce_calc_taxes: 'no',
		woocommerce_enable_guest_checkout: 'yes',
	} );

	wpEval( `
		delete_transient( '_wc_activation_redirect' );
		WC_Install::create_pages();
		$zone_name = 'E2E Everywhere';
		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			if ( $zone['zone_name'] === $zone_name ) {
				return;
			}
		}
		$zone = new WC_Shipping_Zone();
		$zone->set_zone_name( $zone_name );
		$zone->set_zone_order( 1 );
		$zone->save();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $instance_id . '_settings',
			array( 'title' => 'Flat rate', 'tax_status' => 'none', 'cost' => '5' )
		);
	` );

	wpCli( [ 'rewrite', 'structure', '/%postname%/' ] );
	wpCli( [ 'rewrite', 'flush' ] );

	ensureSimpleProduct();
}

/** Enable the plugin and make sure its tracking pages exist. */
export function setupPlugin( extraOptions: Record< string, unknown > = {} ): void {
	setOptions( { ...DEFAULT_PLUGIN_OPTIONS, ...extraOptions } );
	// Pages are created on activation; re-run the activator if they are missing (it reuses existing pages).
	wpEval( `
		$pages = get_option( 'wps_tofw_tracking_page', array() );
		if ( empty( $pages['pages']['wps_guest_track_order_page'] ) && function_exists( 'activate_track_orders_for_woocommerce' ) ) {
			activate_track_orders_for_woocommerce();
		}
	` );
}
