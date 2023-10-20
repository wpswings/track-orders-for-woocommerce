<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link    https://wpswings.com/
 * @since   1.0.0
 * @package Track Orders for WooCommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Track Orders for WooCommerce
 * Plugin URI:        https://wpswings.com/product/track-orders-for-woocommerce/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            WPSwings
 * Author URI:        https://wpswings.com/
 * Text Domain:       track-orders-for-woocommerce
 * Domain Path:       /languages
 *
 * Requires at least:    5.5.0
 * Tested up to:         6.3.1
 * WC requires at least: 5.5.0
 * WC tested up to:      8.0.3
 * Requires PHP:         7.2
 * Stable tag:           1.0.0
 *
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

if ( in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins', array() ), true ) || ( is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins', array() ) ) ) ) {

	/**
	 * Define plugin constants.
	 *
	 * @since 1.0.0
	 */
	function track_orders_for_woocommerce_define_constants() {

		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION', '1.0.0' );
		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH', plugin_dir_path( __FILE__ ) );
		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL', plugin_dir_url( __FILE__ ) );
		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_SERVER_URL', 'https://wpswings.com' );
		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_ITEM_REFERENCE', 'Track Orders For WooCommerce' );
	}

	/**
	 * Define wps-site update feature.
	 *
	 * @since 1.0.0
	 */
	function track_orders_for_woocommerce_auto_update() {

		if ( ! defined( 'TRACK_ORDERS_FOR_WOOCOMMERCE_ITEM_REFERENCE' ) ) {
			define( 'TRACK_ORDERS_FOR_WOOCOMMERCE_ITEM_REFERENCE', 'Track Orders For WooCommerce' );
		}
		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_BASE_FILE', __FILE__ );
		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_SERVER_URL', 'https://wpswings.com' );

	}



	/**
	 * Callable function for defining plugin constants.
	 *
	 * @param String $key   Key for contant.
	 * @param String $value value for contant.
	 * @since 1.0.0
	 */
	function track_orders_for_woocommerce_constants( $key, $value ) {

		if ( ! defined( $key ) ) {

			define( $key, $value );
		}
	}

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-track-orders-for-woocommerce-activator.php
	 */
	function track_orders_for_woocommerce_activate() {

		include_once plugin_dir_path( __FILE__ ) . 'includes/class-track-orders-for-woocommerce-activator.php';
		Track_Orders_For_Woocommerce_Activator::track_orders_for_woocommerce_activate();
		$wps_tofw_active_plugin = get_option( 'wps_all_plugins_active', false );
		if ( is_array( $wps_tofw_active_plugin ) && ! empty( $wps_tofw_active_plugin ) ) {
			$wps_tofw_active_plugin['track-orders-for-woocommerce'] = array(
				'plugin_name' => __( 'Track Orders For Woocommerce', 'track-orders-for-woocommerce' ),
				'active' => '1',
			);
		} else {
			$wps_tofw_active_plugin                        = array();
			$wps_tofw_active_plugin['track-orders-for-woocommerce'] = array(
				'plugin_name' => __( 'Track Orders For Woocommerce', 'track-orders-for-woocommerce' ),
				'active' => '1',
			);
		}
		update_option( 'wps_all_plugins_active', $wps_tofw_active_plugin );
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-track-orders-for-woocommerce-deactivator.php
	 */
	function track_orders_for_woocommerce_deactivate() {
		include_once plugin_dir_path( __FILE__ ) . 'includes/class-track-orders-for-woocommerce-deactivator.php';
		Track_Orders_For_Woocommerce_Deactivator::track_orders_for_woocommerce_deactivate();
		$wps_tofw_deactive_plugin = get_option( 'wps_all_plugins_active', false );
		if ( is_array( $wps_tofw_deactive_plugin ) && ! empty( $wps_tofw_deactive_plugin ) ) {
			foreach ( $wps_tofw_deactive_plugin as $wps_tofw_deactive_key => $wps_tofw_deactive ) {
				if ( 'track-orders-for-woocommerce' === $wps_tofw_deactive_key ) {
					$wps_tofw_deactive_plugin[ $wps_tofw_deactive_key ]['active'] = '0';
				}
			}
		}
		update_option( 'wps_all_plugins_active', $wps_tofw_deactive_plugin );
	}

	register_activation_hook( __FILE__, 'track_orders_for_woocommerce_activate' );
	register_deactivation_hook( __FILE__, 'track_orders_for_woocommerce_deactivate' );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path( __FILE__ ) . 'includes/class-track-orders-for-woocommerce.php';
	require plugin_dir_path( __FILE__ ) . 'integration/class-track-orders-for-woocommerce-with-fedex.php';



	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since 1.0.0
	 */
	function track_orders_for_woocommerce_run() {
		track_orders_for_woocommerce_define_constants();
		track_orders_for_woocommerce_auto_update();
		$wps_tofw = new Track_Orders_For_Woocommerce();
		$wps_tofw->tofw_run();
		$GLOBALS['wps_tofw_obj'] = $wps_tofw;

	}
	track_orders_for_woocommerce_run();


	// Add settings link on plugin page.
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'track_orders_for_woocommerce_settings_link' );

	/**
	 * Settings link.
	 *
	 * @since 1.0.0
	 * @param Array $links Settings link array.
	 */
	function track_orders_for_woocommerce_settings_link( $links ) {

		$my_link = array(
			'<a href="' . admin_url( 'admin.php?page=track_orders_for_woocommerce_menu' ) . '">' . __( 'Settings', 'track-orders-for-woocommerce' ) . '</a>',
		);
		return array_merge( $my_link, $links );
	}


	/**
	 * Adding custom setting links at the plugin activation list.
	 *
	 * @param  array  $links_array      array containing the links to plugin.
	 * @param  string $plugin_file_name plugin file name.
	 * @return array
	 */
	function track_orders_for_woocommerce_custom_settings_at_plugin_tab( $links_array, $plugin_file_name ) {
		if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
			$links_array[] = '<a href="#" target="_blank"><img src="' . esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Demo.svg" class="wps-info-img" alt="Demo image">' . __( 'Demo', 'track-orders-for-woocommerce' ) . '</a>';
			$links_array[] = '<a href="#" target="_blank"><img src="' . esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Documentation.svg" class="wps-info-img" alt="documentation image">' . __( 'Documentation', 'track-orders-for-woocommerce' ) . '</a>';
			$links_array[] = '<a href="#" target="_blank"><img src="' . esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Support.svg" class="wps-info-img" alt="support image">' . __( 'Support', 'track-orders-for-woocommerce' ) . '</a>';
		}
		return $links_array;
	}
	add_filter( 'plugin_row_meta', 'track_orders_for_woocommerce_custom_settings_at_plugin_tab', 10, 2 );

	add_action( 'activated_plugin', 'track_orders_standard_redirect_on_settings' );
	if ( ! function_exists( 'track_orders_standard_redirect_on_settings' ) ) {

		/**
		 * Function to redirect.
		 *
		 * @param string $plugin is string.
		 * @return void
		 */
		function track_orders_standard_redirect_on_settings( $plugin ) {
			if ( plugin_basename( __FILE__ ) === $plugin ) {
				$general_settings_url = admin_url( 'admin.php?page=track_orders_for_woocommerce_menu' );
				wp_redirect( esc_url( $general_settings_url ) );
				   exit();
			}
		}
	}


	/**
	 * This function checks session is set or not
	 *
	 * @link http://www.wpswings.com/
	 */
	function track_orders_set_session() {
		if ( ! session_id() ) {

			session_start();
		}
		$value_check = isset( $_POST['track_order_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['track_order_nonce_name'] ) ) : '';
		wp_verify_nonce( $value_check, 'track_order_nonce' );
		if ( isset( $_POST['wps_tofw_order_id_submit'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_order_id_submit'] ) ) : '' ) {
			$order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
			$billing_email = get_post_meta( $order_id, '_billing_email', true );
			$wps_tofw_pages = get_option( 'track_orders_tracking_page' );
			$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
			$track_order_url = get_permalink( $page_id );
			$order = wc_get_order( $order_id );
			if ( ! empty( $order ) ) {

				if ( 'on' != get_option( 'wps_tofw_enable_track_order_using_order_id', 'no' ) ) {
					$req_email = isset( $_POST['order_email'] ) ? sanitize_text_field( wp_unslash( $_POST['order_email'] ) ) : '';

					if ( ! empty( $req_email ) && ! empty( $billing_email ) && $req_email == $billing_email ) {
						$_SESSION['wps_tofw_email'] = $billing_email;
						$order = wc_get_order( $order_id );
						$url = $track_order_url . '?' . $order_id;
						wp_redirect( $url );
						exit();
					} else {
						$_SESSION['wps_tofw_notification'] = __( 'OrderId or Email is Invalid', 'woocommerce-order-tracker' );
					}
				} else {
					$order = wc_get_order( $order_id );
					$url = $track_order_url . '?' . $order_id;
					wp_redirect( $url );
					exit();
				}
			} else {
				$_SESSION['wps_tofw_notification'] = __( 'OrderId is Invalid', 'woocommerce-order-tracker' );
			}
		}
	}
	add_action( 'init', 'track_orders_set_session' );
} else {
	track_orders_dependency_checkup();
}

/**
 * Checking dependency for woocommerce plugin.
 *
 * @return void
 */
function track_orders_dependency_checkup() {
	add_action( 'admin_init', 'track_orders_deactivate_child_plugin' );
	add_action( 'admin_notices', 'track_orders_show_admin_notices' );
}

/**
 * Deactivating child plugin.
 *
 * @return void
 */
function track_orders_deactivate_child_plugin() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}
/**
 * Showing admin notices.
 *
 * @return void
 */
function track_orders_show_admin_notices() {
	$mwb_mbfw_child_plugin  = __( 'Track Orders For Woocommerce', 'track-orders-for-woocommerce' );
	$mwb_mbfw_parent_plugin = __( 'WooCommerce', 'track-orders-for-woocommerce' );
	echo '<div class="notice notice-error is-dismissible"><p>'
		/* translators: %s: dependency checks */
		. sprintf( esc_html__( '%1$s requires %2$s to function correctly. Please activate %2$s before activating %1$s. For now, the plugin has been deactivated.', 'mwb-bookings-for-woocommerce' ), '<strong>' . esc_html( $mwb_mbfw_child_plugin ) . '</strong>', '<strong>' . esc_html( $mwb_mbfw_parent_plugin ) . '</strong>' )
		. '</p></div>';
	if ( isset( $_GET['activate'] ) ) { // phpcs:ignore
		unset( $_GET['activate'] ); //phpcs:ignore
	}
}


