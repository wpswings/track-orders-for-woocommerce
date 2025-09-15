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
 * Description:       <code><strong>Track Orders for WooCommerce</strong></code> Keep your customers informed in real-time with simple order tracking, transforming their waiting time into an engaging and interactive journey. <a target="_blank" href="https://wpswings.com/woocommerce-plugins/?utm_source=wpswings-orderbump-shop&utm_medium=orderbump-pro-backend&utm_campaign=shop-page" >Elevate your eCommerce store by exploring more on <strong>WP Swings</strong></a>.
 * Version:           1.1.8
 * Author:            WPSwings
 * Author URI:        https://wpswings.com/
 * Text Domain:       track-orders-for-woocommerce
 * Domain Path:       /languages
 *
 * Requires Plugins:  woocommerce
 * Requires at least:    6.7.0
 * Tested up to:         6.8.2
 * WC requires at least: 6.5.0
 * WC tested up to:      10.1.2
 * Requires PHP:         7.4
 * Stable tag:           1.1.8
 *
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

use Automattic\WooCommerce\Utilities\OrderUtil;
// HPOS Compatibility and cart and checkout block.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

if ( in_array( 'woocommerce/woocommerce.php', get_option( 'active_plugins', array() ), true ) || ( is_multisite() && array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins', array() ) ) ) ) {

	/**
	 * Define plugin constants.
	 *
	 * @since 1.0.0
	 */
	function define_track_orders_for_woocommerce_constants() {
		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION', '1.1.7' );
		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH', plugin_dir_path( __FILE__ ) );
		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL', plugin_dir_url( __FILE__ ) );
		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_SERVER_URL', 'https://wpswings.com' );
		track_orders_for_woocommerce_constants( 'TRACK_ORDERS_FOR_WOOCOMMERCE_ITEM_REFERENCE', 'Track Orders For WooCommerce' );
	}


	add_action( 'init', 'wps_otfw_create_images_folder_inside_uploads' );

	/**
	 * Function for create directory for saving the qr image.
	 *
	 * @return void
	 */
	function wps_otfw_create_images_folder_inside_uploads() {
		// Get the uploads directory path.
		$wp_upload_dir = wp_upload_dir();

		// Define the new folder name.
		$new_folder_name = 'tracking_images';

		// Create the full path for the new folder.
		$new_folder_path = $wp_upload_dir['basedir'] . '/' . $new_folder_name;

		// Check if the folder doesn't exist already.
		if ( ! file_exists( $new_folder_path ) ) {
			// Create the new folder.
			if ( wp_mkdir_p( $new_folder_path ) ) {
				return;
			}
		}
	}

	/**
	 * Define wps-site update feature.
	 *
	 * @since 1.0.0
	 */
	function auto_update_track_orders_for_woocommerce() {
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
	function activate_track_orders_for_woocommerce() {
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
	function deactivate_track_orders_for_woocommerce() {
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

	register_activation_hook( __FILE__, 'activate_track_orders_for_woocommerce' );
	register_deactivation_hook( __FILE__, 'deactivate_track_orders_for_woocommerce' );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path( __FILE__ ) . 'includes/class-track-orders-for-woocommerce.php';
	require plugin_dir_path( __FILE__ ) . 'integration/class-track-orders-for-woocommerce-with-fedex.php';
	if ( 'on' === get_option( 'wps_enable_dhl_tracking' ) ) {
		require plugin_dir_path( __FILE__ ) . 'template/wps-dhl-tracking-template.php';
	}



	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since 1.0.0
	 */
	function run_track_orders_for_woocommerce() {
		define_track_orders_for_woocommerce_constants();
		auto_update_track_orders_for_woocommerce();
		$wps_tofw = new Track_Orders_For_Woocommerce();
		$wps_tofw->tofw_run();
		$GLOBALS['wps_tofw_obj'] = $wps_tofw;
	}
	run_track_orders_for_woocommerce();


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
			$links_array[] = '<a href="https://demo.wpswings.com/track-orders-for-woocommerce-pro/?utm_source=wpswings-ot-demo&utm_medium=ot-pro-page&utm_campaign=live-demo" target="_blank"><img src="' . esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Demo.svg" class="wps-info-img" alt="Demo image">' . __( 'Demo', 'track-orders-for-woocommerce' ) . '</a>';
			$links_array[] = '<a href="https://docs.wpswings.com/track-orders-for-woocommerce/?utm_source=ot-org-page&utm_medium=referral&utm_campaign=ot-doc-free" target="_blank"><img src="' . esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Documentation.svg" class="wps-info-img" alt="documentation image">' . __( 'Documentation', 'track-orders-for-woocommerce' ) . '</a>';
			$links_array[] = '<a href="https://wpswings.com/submit-query/?utm_source=wpswings-ot-pro-support&utm_medium=ot-org-backend&utm_campaign=support" target="_blank"><img src="' . esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Support.svg" class="wps-info-img" alt="support image">' . __( 'Support', 'track-orders-for-woocommerce' ) . '</a>';
		}
		return $links_array;
	}
	add_filter( 'plugin_row_meta', 'track_orders_for_woocommerce_custom_settings_at_plugin_tab', 10, 2 );


	/**
	 * This function checks session is set or not
	 *
	 * @link http://www.wpswings.com/
	 */
	function wps_tofw_set_session() {
		ob_start(); // Start output buffering.
		$value_check = isset( $_POST['track_order_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['track_order_nonce_name'] ) ) : '';
		wp_verify_nonce( $value_check, 'track_order_nonce' );

		if ( isset( $_POST['wps_tofw_order_id_submit'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_order_id_submit'] ) ) : '' ) {
			$order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
			$order = wc_get_order( $order_id );
			if ( ! session_id() ) {
				session_start(); // Start the session.
			}
			if ( ! $order ) {
				$_SESSION['wps_tofw_notification'] = __( 'OrderId is Invalid', 'track-orders-for-woocommerce' );
				ob_end_flush();
				return;
			}
			$tofw_order = new WC_Order( $order_id );
			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				$billing_email = $tofw_order->get_billing_email();
			} else {
				$billing_email = get_post_meta( $order_id, '_billing_email', true );
			}
			$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
			$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
			$track_order_url = get_permalink( $page_id );

			$order = wc_get_order( $order_id );

			if ( ! empty( $order ) ) {

				if ( 'on' != get_option( 'wps_tofw_enable_track_order_using_order_id', 'no' ) ) {

					$req_email = isset( $_POST['order_email'] ) ? sanitize_text_field( wp_unslash( $_POST['order_email'] ) ) : '';
					if ( $req_email == $billing_email ) {
						$_SESSION['wps_tofw_email'] = $billing_email;
						$order = wc_get_order( $order_id );

						$url = $track_order_url . '?' . $order_id;
						wp_redirect( $url );
						exit();
					} else {
						$_SESSION['wps_tofw_notification'] = __( 'OrderId or Email is Invalidss', 'track-orders-for-woocommerce' );
						session_write_close();
						return;
					}
				} else {
					$order = wc_get_order( $order_id );
					$url = $track_order_url . '?' . $order_id;
					wp_redirect( $url );
					exit();
				}
			} else {
				$_SESSION['wps_tofwp_notification'] = __( 'OrderId is Invalid', 'track-orders-for-woocommerce' );
				session_write_close();
				return;
			}
		}
		ob_end_flush();
	}
	add_action( 'init', 'wps_tofw_set_session' );
} else {
	wps_tofw_dependency_checkup();
}

/**
 * Checking dependency for woocommerce plugin.
 *
 * @return void
 */
function wps_tofw_dependency_checkup() {
	add_action( 'admin_init', 'wps_tofw_deactivate_child_plugin' );
	add_action( 'admin_notices', 'wps_tofw_show_admin_notices' );
}

/**
 * Deactivating child plugin.
 *
 * @return void
 */
function wps_tofw_deactivate_child_plugin() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}
/**
 * Showing admin notices.
 *
 * @return void
 */
function wps_tofw_show_admin_notices() {
	$mwb_mbfw_child_plugin  = __( 'Track Orders For Woocommerce', 'track-orders-for-woocommerce' );
	$mwb_mbfw_parent_plugin = __( 'WooCommerce', 'track-orders-for-woocommerce' );
	echo '<div class="notice notice-error is-dismissible"><p>'
		/* translators: %s: dependency checks */
		. sprintf( esc_html__( '%1$s requires %2$s to function correctly. Please activate %2$s before activating %1$s. For now, the plugin has been deactivated.', 'track-orders-for-woocommerce' ), '<strong>' . esc_html( $mwb_mbfw_child_plugin ) . '</strong>', '<strong>' . esc_html( $mwb_mbfw_parent_plugin ) . '</strong>' )
		. '</p></div>';
	if (isset($_GET['activate'])) { // phpcs:ignore
		unset($_GET['activate']); //phpcs:ignore
	}
}

// To Suppress The Notices on text doman.
add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
if ( 'on' == get_option( 'wps_tofw_enable_invoice_tracking_info' ) ) {
	add_filter( 'wps_fetch_tracking_data', 'wps_get_order_data_from_id', 10, 2 );
}

/**
 * Function to fetch order data from order ID.
 *
 * @param string $default Default value.
 * @param int    $order_id Order ID.
 * @return string HTML content with order tracking information.
 */
function wps_get_order_data_from_id( $default, $order_id ) {
	$wps_pgfw_order_id = intval( $order_id );
	$wps_pgfw_order = wc_get_order( $wps_pgfw_order_id );

	if ( ! $wps_pgfw_order ) {
		return '<div style="color:red;">Invalid Order ID.</div>';
	}

	// Order meta data.
	$wps_pgfw_estimated_date  = $wps_pgfw_order->get_meta( 'wps_tofw_estimated_delivery_date' );
	$wps_pgfw_estimated_time  = $wps_pgfw_order->get_meta( 'wps_tofw_estimated_delivery_time' );
	$wps_pgfw_carrier_base    = $wps_pgfw_order->get_meta( 'wps_tofwp_enhanced_order_company' );
	$wps_pgfw_tracking_number = $wps_pgfw_order->get_meta( 'wps_tofwp_enhanced_tracking_no' );
	$wps_pgfw_tracking_link   = $wps_pgfw_carrier_base && $wps_pgfw_tracking_number ? esc_url( $wps_pgfw_carrier_base . urlencode( $wps_pgfw_tracking_number ) ) : '';

	$wps_pgfw_saved_settings  = get_option( 'wps_tofwp_general_settings_saved' );
	$wps_pgfw_saved_providers = isset( $wps_pgfw_saved_settings['providers_data'] ) ? $wps_pgfw_saved_settings['providers_data'] : array();

	$wps_pgfw_status = wc_get_order_status_name( $wps_pgfw_order->get_status() );

	$wps_pgfw_icon_url = '';
	$wps_pgfw_matched_carrier_name = '';

	if ( is_plugin_active( 'track-orders-for-woocommerce-pro/track-orders-for-woocommerce-pro.php' ) ) {
		$wps_pgfw_plugin_url = TRACK_ORDERS_FOR_WOOCOMMERCE_PRO_DIR_URL;
		$wps_pgfw_icon_path  = $wps_pgfw_plugin_url . 'admin/partials/assets/icons/';
		foreach ( $wps_pgfw_saved_providers as $name => $url ) {
			if ( strpos( $wps_pgfw_carrier_base, $url ) !== false ) {
				$wps_pgfw_matched_carrier_name = $name;
				$icon_file = strtolower( str_replace( ' ', '', $name ) ) . '.png';
				$icon_path = TRACK_ORDERS_FOR_WOOCOMMERCE_PRO_DIR_PATH . 'admin/partials/assets/icons/' . $icon_file;
				$wps_pgfw_icon_url = file_exists( $icon_path ) ? $wps_pgfw_icon_path . $icon_file : $wps_pgfw_icon_path . 'default.png';
				break;
			}
		}
	}
	ob_start();
	?>
	<div style="font-family: sans-serif; font-size: 12px; line-height: 1.5; position: absolute; bottom: 20px; left: 20px; width: 95%; max-width: 320px; background: #f8f8f8; border: 1px solid #ccc; border-radius: 6px; padding: 10px; box-sizing: border-box;">
		<div style="font-weight: bold; font-size: 13px; color: #333; margin-bottom: 6px;">Track Order Status : </div>

		<div style="margin-bottom: 4px;"><strong>Order ID:</strong> <?php echo esc_html( $wps_pgfw_order_id ); ?></div>
		<div style="margin-bottom: 4px;"><strong>Status:</strong> <span style="color: green;"><?php echo esc_html( $wps_pgfw_status ); ?></span></div>

		<?php if ( $wps_pgfw_estimated_date || $wps_pgfw_estimated_time ) : ?>
			<div style="margin-bottom: 4px;">
				<strong>ETA:</strong>
				<?php
				if ( $wps_pgfw_estimated_date ) :
					?>
					Date: <?php echo esc_html( $wps_pgfw_estimated_date ); ?> <?php endif; ?>
				<?php
				if ( $wps_pgfw_estimated_time ) :
					?>
					Time: <?php echo esc_html( $wps_pgfw_estimated_time ); ?><?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $wps_pgfw_tracking_link ) : ?>
			<div style="margin-bottom: 4px;">
				<strong>Carrier:</strong>
				<?php if ( $wps_pgfw_icon_url ) : ?>
					<img src="<?php echo esc_url( $wps_pgfw_icon_url ); ?>" alt="<?php echo esc_attr( $wps_pgfw_matched_carrier_name ); ?>" style="height: 18px; vertical-align: middle; margin-right: 4px;">
				<?php endif; ?>
				<?php echo esc_html( $wps_pgfw_matched_carrier_name ); ?>
			</div>
		<?php endif; ?>

		<div style="margin-top: 10px; display: table; width: 100%;">
			<?php if ( $wps_pgfw_tracking_link ) : ?>
				<div style="display: table-cell; width: 50%; padding-right: 5px;">
					<a href="<?php echo esc_url( $wps_pgfw_tracking_link ); ?>" style="display: block; font-size: 11px; background: #0071a1; color: #fff; padding: 6px; text-align: center; text-decoration: none; border-radius: 4px;">Track Carrier</a>
				</div>
			<?php endif; ?>
			<div style="display: table-cell; width: 50%; padding-left: 5px;">
				<a href="<?php echo esc_url( home_url( '/track-your-order/?' . $wps_pgfw_order_id ) ); ?>" style="display: block; font-size: 11px; background: #28a745; color: #fff; padding: 6px; text-align: center; text-decoration: none; border-radius: 4px;">Track Order</a>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'wps_tracking_info', 'wps_tofw_tracking_info_shortcode' );

/**
 * Shortcode: [wps_tracking_info].
 * Description: Displays tracking information for a WooCommerce order.
 *
 * @param array $atts Attributes for the shortcode.
 * - order_id: (int) The ID of the WooCommerce order (required).
 * - align: (string) Text alignment ('left', 'center', 'right', default: 'center').
 *
 * Example usage:
 * [wps_tracking_info order_id="12345" align="left"].
 */
function wps_tofw_tracking_info_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'order_id' => '',
			'align'    => 'center',
		),
		$atts,
		'wps_tracking_info'
	);

	if ( empty( $atts['order_id'] ) ) {
		return '<div style="color:red;">Order ID is missing.</div>';
	}

	$wps_pgfw_order_id = intval( $atts['order_id'] );
	$wps_pgfw_order = wc_get_order( $wps_pgfw_order_id );

	if ( ! $wps_pgfw_order ) {
		return '<div style="color:red;">Invalid Order ID.</div>';
	}

	// Order meta data.
	$wps_pgfw_estimated_date  = $wps_pgfw_order->get_meta( 'wps_tofw_estimated_delivery_date' );
	$wps_pgfw_estimated_time  = $wps_pgfw_order->get_meta( 'wps_tofw_estimated_delivery_time' );
	$wps_pgfw_carrier_base    = $wps_pgfw_order->get_meta( 'wps_tofwp_enhanced_order_company' );
	$wps_pgfw_tracking_number = $wps_pgfw_order->get_meta( 'wps_tofwp_enhanced_tracking_no' );
	$wps_pgfw_tracking_link   = $wps_pgfw_carrier_base && $wps_pgfw_tracking_number ? esc_url( $wps_pgfw_carrier_base . urlencode( $wps_pgfw_tracking_number ) ) : '';

	$wps_pgfw_saved_settings  = get_option( 'wps_tofwp_general_settings_saved' );
	$wps_pgfw_saved_providers = isset( $wps_pgfw_saved_settings['providers_data'] ) ? $wps_pgfw_saved_settings['providers_data'] : array();

	// Order status.
	$wps_pgfw_status = wc_get_order_status_name( $wps_pgfw_order->get_status() );

	// Text alignment logic.
	$wps_pgfw_allowed_alignments = array( 'left', 'center', 'right' );
	$wps_pgfw_align = in_array( strtolower( $atts['align'] ), $wps_pgfw_allowed_alignments ) ? strtolower( $atts['align'] ) : 'center';

	$wps_pgfw_container_style = 'max-width: 500px; padding: 20px; border-radius: 12px; background: #f8f9fa; box-shadow: 0 4px 12px rgba(0,0,0,0.1); font-family: Arial, sans-serif;';
	if ( 'center' === $wps_pgfw_align ) {
		$wps_pgfw_container_style .= ' margin: 20px auto;';
	} elseif ( 'left' === $wps_pgfw_align ) {
		$wps_pgfw_container_style .= ' margin: 20px 0 20px auto; float: left;';
	} else {
		$wps_pgfw_container_style .= ' margin: 20px auto 20px 0; float: right;';
	}

	ob_start();
	if ( is_plugin_active( 'track-orders-for-woocommerce-pro/track-orders-for-woocommerce-pro.php' ) ) {
		$wps_pgfw_plugin_url = TRACK_ORDERS_FOR_WOOCOMMERCE_PRO_DIR_URL;
		$wps_pgfw_icon_path  = $wps_pgfw_plugin_url . 'admin/partials/assets/icons/';
		$wps_pgfw_matched_carrier_name = '';
		$wps_pgfw_icon_url = '';

		// Detect matched carrier and icon.
		foreach ( $wps_pgfw_saved_providers as $wps_pgfw_name => $wps_pgfw_url ) {
			if ( strpos( $wps_pgfw_carrier_base, $wps_pgfw_url ) !== false ) {
				$wps_pgfw_matched_carrier_name = $wps_pgfw_name;
				$wps_pgfw_icon_file = strtolower( str_replace( ' ', '', $wps_pgfw_matched_carrier_name ) ) . '.png';
				$wps_pgfw_icon_full_path = TRACK_ORDERS_FOR_WOOCOMMERCE_PRO_DIR_PATH . 'admin/partials/assets/icons/' . $wps_pgfw_icon_file;
				$wps_pgfw_icon_url = file_exists( $wps_pgfw_icon_full_path ) ? $wps_pgfw_icon_path . $wps_pgfw_icon_file : $wps_pgfw_icon_path . 'default.png';
				break;
			}
		}
	}
	?>
	<div style="<?php echo esc_attr( $wps_pgfw_container_style ); ?>">
		<h2 style="margin-top: 0; color: #333;">Order Tracking Information</h2>
		<p><strong>Order ID:</strong> <?php echo esc_html( $wps_pgfw_order_id ); ?></p>
		<p><strong>Order Status:</strong> <span style="color: green;"><?php echo esc_html( $wps_pgfw_status ); ?></span></p>
		<?php if ( is_plugin_active( 'track-orders-for-woocommerce-pro/track-orders-for-woocommerce-pro.php' ) ) { ?>
			<?php if ( $wps_pgfw_estimated_date || $wps_pgfw_estimated_time ) : ?>
				<p><strong>Estimated Delivery:</strong><br>
					<?php if ( $wps_pgfw_estimated_date ) : ?>
						📅 <?php echo esc_html( $wps_pgfw_estimated_date ); ?><br>
					<?php endif; ?>
					<?php if ( $wps_pgfw_estimated_time ) : ?>
						⏰ <?php echo esc_html( $wps_pgfw_estimated_time ); ?>
					<?php endif; ?>
				</p>
			<?php endif; ?>


			<?php if ( $wps_pgfw_tracking_link ) : ?>
				<div style="margin-top: 15px;">
					<strong>Carrier Tracking:</strong>
					<div style="display: flex; align-items: center; gap: 10px; margin-top: 8px;">
						<?php if ( $wps_pgfw_icon_url ) : ?>
							<img src="<?php echo esc_url( $wps_pgfw_icon_url ); ?>" alt="<?php echo esc_attr( $wps_pgfw_matched_carrier_name ); ?>" style="height: 35px;">
						<?php endif; ?>
						<?php if ( $wps_pgfw_matched_carrier_name ) : ?>
							<span style="font-weight: bold;"><?php echo esc_html( $wps_pgfw_matched_carrier_name ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		<?php } ?>

		<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px;">
			<?php if ( is_plugin_active( 'track-orders-for-woocommerce-pro/track-orders-for-woocommerce-pro.php' ) ) { ?>
				<?php if ( $wps_pgfw_tracking_link ) { ?>
					<a href="<?php echo esc_url( $wps_pgfw_tracking_link ); ?>" class="button wc-forward" target="_blank" style="text-align: center; background-color: #0071a1; color: #fff; padding: 10px 15px; border-radius: 6px; text-decoration: none;">
						Track with Carrier
					</a>
					<?php
				}
			}
			?>
			<a href="<?php echo esc_url( home_url( '/track-your-order/?' . $wps_pgfw_order_id ) ); ?>" class="button wc-forward" style="flex: 1; text-align: center; background-color: #28a745; color: #fff; padding: 10px 15px; border-radius: 6px; text-decoration: none;">
				Track Your Order
			</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

add_action( 'wp_ajax_wps_mult_carrier_data_tracking', 'wps_mult_carrier_data_tracking_callback' );
add_action( 'wp_ajax_nopriv_wps_mult_carrier_data_tracking', 'wps_mult_carrier_data_tracking_callback' );

/**
 * Callback function for tracking multiple carriers.
 */
function wps_mult_carrier_data_tracking_callback() {
	check_ajax_referer( 'wps_muilt_carrier', 'nonce' );
	if ( 'on' == get_option( 'wps_tofwp_enable_multi_carrier_tracking' ) ) {

		// Get and sanitize params.
		$tracking_number = isset( $_POST['tracking_number'] ) ? sanitize_text_field( wp_unslash( $_POST['tracking_number'] ) ) : '';
		$courier_code    = isset( $_POST['courier_code'] ) ? sanitize_key( wp_unslash( $_POST['courier_code'] ) ) : '';

		if ( '' === $tracking_number || '' === $courier_code ) {
			wp_send_json_error( array( 'message' => 'Missing tracking_number or courier_code' ), 400 );
		}

		// Prepare the API URL.
		$api_url = 'https://api.trackingmore.com/v4/trackings/get?tracking_numbers=' . $tracking_number . '&courier_code=' . $courier_code;

		// API Request Headers (API Key).
		$headers = array(
			'Content-Type' => 'application/json',
			'Tracking-Api-Key' => get_option( 'wps_tofwp_multi_carrier_api_key' ),
			'Accept' => 'application/json',
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			return;
		}

		// Decode the API response.
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( '4102' == $data['meta']['code'] ) {
			wps_create_new_shippement_order( $tracking_number, $courier_code );
		} elseif ( '200' == $data['meta']['code'] ) {
			$result = array();
			foreach ( $data['data'] as $key => $value ) {

				if ( isset( $value['origin_info']['trackinfo'] ) ) {

					$result[ $key ] = $value['origin_info']['trackinfo'];
					$result['delivery_status'] = $value['delivery_status'];
				}
			}
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( array( 'message' => 'Tracking information not found' ), 400 );
		}
	}
}

/**
 * Create a new shipment order.
 *
 * @param string $tracking_number The tracking number.
 * @param string $courier_code    The courier code.
 */
function wps_create_new_shippement_order( $tracking_number, $courier_code ) {

	if ( 'on' == get_option( 'wps_tofwp_enable_multi_carrier_tracking' ) ) {

		$wps_mutli_carrier_api_key = get_option( 'wps_tofwp_multi_carrier_api_key' );
		// Tracking data.
		$tracking_data = array(
			'tracking_number' => $tracking_number,
			'courier_code'    => $courier_code,
		);

		// API endpoint.
		$api_url = 'https://api.trackingmore.com/v4/trackings/create';

		// Make POST request.
		$response = wp_remote_post(
			$api_url,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Tracking-Api-Key' => $wps_mutli_carrier_api_key,
				),
				'body'    => wp_json_encode( $tracking_data ),
				'timeout' => 30,
			)
		);

		// Handle errors.
		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => $response->get_error_message(),
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );

		$result = array();
		if ( isset( $body['data'] ) && is_array( $body['data'] ) ) {
			foreach ( $body['data'] as $key => $value ) {

				if ( isset( $value['origin_info']['trackinfo'] ) ) {

					$result[ $key ] = $value['origin_info']['trackinfo'];
					$result['delivery_status'] = $value['delivery_status'];
				}
			}
		}
		wp_send_json_success( $result );
	}
}

/**
 * Delete the carrier logo database table.
 */
function wps_delete_carrier_logo_database() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'wps_tofw_carrier_logos';
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %s', $table_name ) );
	update_option( 'wps_tofwp_enable_multi_carrier_tracking', 'off' );
}

register_deactivation_hook( __FILE__, 'wps_delete_carrier_logo_database' );

/**
 * Fetch and store carrier logos.
 */
add_action( 'admin_init', 'wps_fetch_and_store_carrier_logos' );

/**
 * Fetch and store carrier logos.
 */
function wps_fetch_and_store_carrier_logos() {
	if ( 'on' == get_option( 'wps_tofwp_enable_multi_carrier_tracking' ) ) {

		global $wpdb;
		$table_name = $wpdb->prefix . 'wps_tofw_carrier_logos';

		wps_create_carrier_logo_database();
		$transient_key = 'wps_carrier_logos_data';
		$cached_data = get_transient( $transient_key );

		if ( false == $cached_data ) {
			$api_url = 'https://api.trackingmore.com/v4/couriers/all';
			$api_key = get_option( 'wps_tofwp_multi_carrier_api_key' );

			$args = array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Tracking-Api-Key' => $api_key,
					'Accept' => 'application/json',
				),
			);

			$response = wp_remote_get( $api_url, $args );

			if ( is_wp_error( $response ) ) {
				error_log( 'Error fetching carrier logos from API: ' . $response->get_error_message() );
				return;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( json_last_error() !== JSON_ERROR_NONE || ! isset( $data['data'] ) ) {
				error_log( 'Error decoding API response or missing "data" key.' );
				return;
			}

			$couriers = $data['data'];
			set_transient( $transient_key, $couriers, 12 * HOUR_IN_SECONDS );
			$couriers_to_insert = $couriers;
		} else {
			$couriers_to_insert = $cached_data;
		}

		$insert_data = array();
		foreach ( $couriers_to_insert as $courier ) {
			if ( isset( $courier['courier_name'] ) && isset( $courier['courier_logo'] ) && isset( $courier['courier_code'] ) ) {
				$carrier_name = sanitize_text_field( $courier['courier_name'] );
				$logo_url = esc_url_raw( 'https:' . $courier['courier_logo'] );
				$carrier_code = sanitize_text_field( $courier['courier_code'] );
				$insert_data[] = array(
					'carrier_name' => $carrier_name,
					'logo_url' => $logo_url,
					'carrier_code' => $carrier_code,
				);
			}
		}

		// Clear the existing table data to avoid duplicates.
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wps_tofw_carrier_logos" );
		$rows_inserted = 0;
		foreach ( $insert_data as $row ) {
			$result = $wpdb->insert(
				$table_name,
				$row,
				array( '%s', '%s', '%s' )
			);
			if ( false !== $result ) {
				$rows_inserted++;
			}
		}

		if ( $rows_inserted > 0 ) {
			error_log( "Successfully inserted $rows_inserted carrier logos into the database." );
		} else {
			error_log( 'No carrier logos were inserted into the database.' );
		}
	}
}

/**
 * Create the carrier logo database table.
 */
function wps_create_carrier_logo_database() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'wps_tofw_carrier_logos';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        carrier_name varchar(255) NOT NULL,
        logo_url varchar(255) NOT NULL,
        carrier_code varchar(255) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

// Run immediately after order is placed (on checkout)
add_action( 'woocommerce_new_order', 'wps_run_split_on_order', 10, 1 );

function wps_run_split_on_order( $order_id ) {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
    if ( ! $order_id  && 'on' !== $wps_enable_partila_shipement ) {
        return;
    }

    // Call your function
    wps_split_parent_into_children( $order_id );
}


function wps_split_parent_into_children( $parent_order_id ) {
	$parent = wc_get_order( $parent_order_id );
	if ( ! $parent ) {
		return new WP_Error( 'wps_no_parent', 'Parent order not found.' );
	}

	// Prevent dupes.
	if ( $parent->get_meta( '_wps_children_created' ) ) {
		return new WP_Error( 'wps_already_done', 'Children already created for this order.' );
	}

	$child_ids = [];
	foreach ( $parent->get_items( 'line_item' ) as $item_id => $item ) {
		$child = wc_create_order( [
			'status'      => $parent->get_status(),
			'customer_id' => $parent->get_customer_id(),
			'currency'    => $parent->get_currency(),
		] );

		if ( ! $child || ! is_a( $child, 'WC_Order' ) ) {
			continue;
		}

		$child->set_parent_id( $parent_order_id );
		$child->set_address( $parent->get_address( 'billing' ), 'billing' );
		$child->set_address( $parent->get_address( 'shipping' ), 'shipping' );
		$child->set_payment_method( $parent->get_payment_method() );
		$child->set_payment_method_title( $parent->get_payment_method_title() );

		$child_item = new WC_Order_Item_Product();
		$child_item->set_product_id( $item->get_product_id() );
		$child_item->set_variation_id( $item->get_variation_id() );
		$child_item->set_name( $item->get_name() );
		$child_item->set_quantity( $item->get_quantity() );
		$child_item->set_subtotal( $item->get_subtotal() );
		$child_item->set_total( $item->get_total() );
		$child_item->set_taxes( $item->get_taxes() );
		$child_item->set_tax_class( $item->get_tax_class() );

		foreach ( $item->get_meta_data() as $meta ) {
			$child_item->add_meta_data( $meta->key, $meta->value, true );
		}

		$child->add_item( $child_item );
		$child->calculate_totals( false );
		$child->add_order_note( sprintf( 'Created as child order of #%d for item #%d.', $parent_order_id, $item_id ) );
		$child->update_meta_data( '_wps_is_child_order', 'yes' );
		$child->save();

		$child_ids[$item->get_product_id()] = $child->get_id();
	}

	if ( $child_ids ) {
		$parent->update_meta_data( '_wps_child_order_ids', $child_ids );
		$parent->update_meta_data( '_wps_children_created', current_time( 'mysql' ) );
		$parent->add_order_note( 'Child orders created: ' . implode( ', ', array_map( 'wc_clean', $child_ids ) ) );
		$parent->save();
		return $child_ids;
	}

	return new WP_Error( 'wps_no_items', 'No line items to split.' );
}


/**
 * Add order status dropdown to line items in WooCommerce order edit page
 * Add this code to your theme's functions.php or create a custom plugin
 */

// Add status column header
add_action('woocommerce_admin_order_item_headers', 'add_line_item_status_header');
function add_line_item_status_header() {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
	if ('on' !== $wps_enable_partila_shipement) {
		return;
	}
    echo '<th class="line-item-status">Status</th>';
}

// Add status dropdown to each line item
add_action('woocommerce_admin_order_item_values', 'wps_tofw_add_line_item_status_dropdown', 10, 3);
function wps_tofw_add_line_item_status_dropdown( $product, $item, $item_id ) {

	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
    // Always get the order object safely
    $order = wc_get_order( $item->get_order_id() );
    if ( ! $order && 'on' !== $wps_enable_partila_shipement) {
        return; // bail early if no order found
    }

    // Get current item status (stored in meta or default to parent order status)
    $current_status = wc_get_order_item_meta( $item_id, '_line_item_status', true );
    if ( empty( $current_status ) ) {
        $current_status = $order->get_status();
    }

    // Check if child order
    $wps_tofw_is_child_order = $order->get_meta( '_wps_is_child_order' );

    // Only show dropdown if not a child order
    if ( 'yes' !== $wps_tofw_is_child_order ) {
        $order_statuses = wc_get_order_statuses();
        
        echo '<td class="line-item-status" width="150px">';
        echo '<select name="line_item_status[' . esc_attr( $item_id ) . ']" class="line-item-status-select" style="width: 100%;">';
        
        foreach ( $order_statuses as $status_key => $status_name ) {
            $status_key = str_replace( 'wc-', '', $status_key );
            $selected   = selected( $current_status, $status_key, false );
            echo '<option value="' . esc_attr( $status_key ) . '" ' . $selected . '>' . esc_html( $status_name ) . '</option>';
        }
        
        echo '</select>';
        echo '</td>';
    }
}


// Save line item status when order is saved
add_action('woocommerce_saved_order_items', 'save_line_item_status', 10, 2);
function save_line_item_status($order_id, $items) {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
    if (isset($_POST['line_item_status']) && is_array($_POST['line_item_status']) && 'on' === $wps_enable_partila_shipement) {
        foreach ($_POST['line_item_status'] as $item_id => $status) {
            wc_update_order_item_meta($item_id, '_line_item_status', sanitize_text_field($status));

			// $child_order_ids = get_post_meta($order_id, '_wps_child_order_ids', true );
			$parent_order = wc_get_order( $order_id );
			$child_order_ids = $parent_order->get_meta( '_wps_child_order_ids' );
            

			foreach($child_order_ids as $key => $value){
				if(wps_get_product_id_from_item($item_id) == $key){
					$order = wc_get_order($value);
					$order->update_status($status);
				$child_order = wc_get_order( $value );
				$child_order->add_order_note( 'Status updated from parent order');
				}
			}

            // Optional: Create a note when status changes
            $order = wc_get_order($order_id);
            $item = $order->get_item($item_id);
            if ($item) {
                $product_name = $item->get_name();
                $status_name = wc_get_order_status_name($status);
                $order->add_order_note(
                    sprintf('Line item "%s" status changed to: %s', $product_name, $status_name)
                );
            }
        }
    }
}


function wps_get_product_id_from_item($item_id, $return_type = 'product_id') {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
    if (!$item_id && 'on' !== $wps_enable_partila_shipement) {
        return false;
    }
    
    try {
        $item = new WC_Order_Item_Product($item_id);
        $product_id = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        
        // Return based on requested type
        switch ($return_type) {
            case 'product_id':
                return $product_id ? intval($product_id) : false;
                
            case 'variation_id':
                return $variation_id ? intval($variation_id) : false;
                
            case 'both':
                // Return variation ID if exists, otherwise product ID
                return $variation_id ? intval($variation_id) : intval($product_id);
                
            case 'array':
                return array(
                    'product_id' => $product_id ? intval($product_id) : 0,
                    'variation_id' => $variation_id ? intval($variation_id) : 0,
                    'is_variation' => !empty($variation_id)
                );
                
            default:
                return $product_id ? intval($product_id) : false;
        }
        
    } catch (Exception $e) {
        error_log('Error getting product ID from item: ' . $e->getMessage());
        return false;
    }
}

// Add CSS styling for better appearance
add_action('admin_head', 'line_item_status_admin_css');
function line_item_status_admin_css() {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
    $screen = get_current_screen();
    if ($screen && $screen->id === 'shop_order' && 'on' == $wps_enable_partila_shipement) {
        ?>
        <style>
        .line-item-status {
            text-align: center !important;
        }
        .line-item-status-select {
            font-size: 12px;
            padding: 2px 4px;
        }
        .woocommerce_order_items .line-item-status {
            border-left: 1px solid #dfdfdf;
        }
        </style>
        <?php
    }
}

// Optional: Add bulk status update functionality
add_action( 'woocommerce_admin_order_items_after_line_items', 'wps_tofw_add_bulk_status_update' );

function wps_tofw_add_bulk_status_update( $order_id ) {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
	$order = wc_get_order( $order_id );
    if ( ! $order instanceof WC_Order && 'on' != $wps_enable_partila_shipement) {
        return;
    }

    $wps_tofw_is_child_order = $order->get_meta( '_wps_is_child_order' );


    if ( 'yes' !== $wps_tofw_is_child_order ) {
        $order_statuses = wc_get_order_statuses();
        ?>
        <tr class="bulk-status-update">
            <td colspan="2">
                <strong><?php esc_html_e( 'Bulk Status Update:', 'text-domain' ); ?></strong>
            </td>
            <td colspan="4">
                <select id="bulk_line_item_status" style="width: 200px;">
                    <option value=""><?php esc_html_e( 'Select status…', 'text-domain' ); ?></option>
                    <?php foreach ( $order_statuses as $status_key => $status_name ) : 
                        $status_key = str_replace( 'wc-', '', $status_key );
                    ?>
                        <option value="<?php echo esc_attr( $status_key ); ?>">
                            <?php echo esc_html( $status_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="apply_bulk_status" class="button">
                    <?php esc_html_e( 'Apply to All', 'text-domain' ); ?>
                </button>
            </td>
        </tr>

        <script>
        jQuery(document).ready(function($) {
            $('#apply_bulk_status').on('click', function() {
                var selectedStatus = $('#bulk_line_item_status').val();
                if (selectedStatus) {
                    $('.line-item-status-select').val(selectedStatus).trigger('change');
                }
            });

            // Visual feedback when a status changes
            $(document).on('change', '.line-item-status-select', function() {
                $(this).closest('tr').addClass('status-changed');
            });
        });
        </script>

        <style>
        .status-changed {
            background-color: #fff2cd !important;
        }
        .bulk-status-update td {
            padding: 10px;
            background-color: #f9f9f9;
            border-top: 2px solid #ddd;
        }
        </style>
        <?php
    }
}

// --- HPOS / New Orders Table ---
add_filter( 'woocommerce_shop_order_list_table_columns', function( $columns ) {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
	if ('on' !== $wps_enable_partila_shipement) {
		return $columns;
	}
	// place after order_status
	$new = [];
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'order_status' === $key ) {
			$new['wps_split_shipments'] = __( 'Partial Shipments', 'wps-track-order' );
		}
	}
	return $new;
}, 20 );

/**
 * Render for HPOS table.
 * @param string   $column Column id.
 * @param WC_Order $order  Order object.
 */
add_action( 'woocommerce_shop_order_list_table_custom_column', function( $column, $order ) {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
	if ( 'on' !== $wps_enable_partila_shipement ) {
		return;
	}
	if ( 'wps_split_shipments' !== $column || ! $order instanceof WC_Order ) {
		return;
	}
	echo wps_render_split_shipments_cell( $order->get_id(), $order );
}, 10, 2 );

// --- Legacy Posts Table (pre-HPOS UI) ---
add_filter( 'manage_edit-shop_order_columns', function( $columns ) {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
	if ('on' !== $wps_enable_partila_shipement) {
		return $columns;
	}
	$new = [];
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'order_status' === $key ) {
			$new['wps_split_shipments'] = __( 'Partial Shipments', 'wps-track-order' );
		}
	}
	return $new;
}, 20 );

/**
 * Render for legacy posts table.
 */
add_action( 'manage_shop_order_posts_custom_column', function( $column, $post_id ) {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
	if ('on' !== $wps_enable_partila_shipement) {
		return;
	}

	if ( 'wps_split_shipments' !== $column ) {
		return;
	}
	$order = wc_get_order( $post_id );
	echo wps_render_split_shipments_cell( $post_id, $order );
}, 10, 2 );


/**
 * Shared renderer for the cell content.
 * Shows child order count and a compact status summary.
 */
function wps_render_split_shipments_cell( $parent_order_id, $parent_order = null ) {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
	if ('on' !== $wps_enable_partila_shipement) {
		return;
	}
	$parent_order = $parent_order ?: wc_get_order( $parent_order_id );
	if ( ! $parent_order ) {
		echo '&mdash;';
		return;
	}

	$children = wps_get_child_orders_ids( $parent_order );
	if ( empty( $children ) ) {
		echo '<span style="opacity:.7;">&mdash;</span>';
		return;
	}


	// Count by status
	$map = [];
	foreach ( $children as $cid ) {
		$c = wc_get_order( $cid );
		if ( ! $c ) { continue; }
		$st = $c->get_status(); // raw (e.g., 'processing', 'partially-shipped')
		if ( ! isset( $map[ $st ] ) ) $map[ $st ] = 0;
		$map[ $st ]++;
	}

// Quick links to all child orders
if ( ! empty( $children ) ) {
    echo '<div style="margin-top:4px;">';
    foreach ( $children as $cid ) {
        printf(
            '<div><a href="%s" target="_blank" style="text-decoration:none;">%s #%d</a></div>',
            esc_url( get_edit_post_link( $cid ) ),
            esc_html__( 'Open child order ↗', 'wps-track-order' ),
            (int) $cid
        );
    }
    echo '</div>';
}

}

/**
 * Get child orders for a parent order.
 * Strategy A: true parent/child via post_parent
 * Strategy B: meta list on parent (_child_order_ids)
 */
function wps_get_child_orders_ids( WC_Order $parent_order ) : array {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
	if ('on' !== $wps_enable_partila_shipement) {
		return '';
	}
	$parent_id = $parent_order->get_id();
	$ids = [];

	// A) True sub-orders
	$sub = wc_get_orders( [
		'limit'  => -1,
		'parent' => $parent_id,
		'return' => 'ids',
		'type'   => [ 'shop_order' ], // include other custom types if needed
	] );
	if ( ! empty( $sub ) ) {
		$ids = array_merge( $ids, $sub );
	}

	// B) Meta list on parent (HPOS-safe via $order->get_meta())
	$meta_key   = apply_filters( 'wps_to_child_orders_meta_key', '_child_order_ids' );
	$child_list = (array) $parent_order->get_meta( $meta_key, true );
	if ( ! empty( $child_list ) ) {
		$ids = array_merge( $ids, array_map( 'absint', $child_list ) );
	}

	$ids = array_values( array_unique( array_filter( $ids ) ) );
	return $ids;
}

// Mark as sortable (legacy)
add_filter( 'manage_edit-shop_order_sortable_columns', function( $cols ) {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
	if ('on' !== $wps_enable_partila_shipement) {
		return $cols;
	}
	$cols['wps_split_shipments'] = 'wps_split_shipments';
	return $cols;
} );

// Handle sort query (legacy)
add_action( 'pre_get_posts', function( $q ) {
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
	if ('on' !== $wps_enable_partila_shipement) {
		return;
	}
	if ( is_admin() && 'shop_order' === $q->get('post_type') && $q->get('orderby') === 'wps_split_shipments' ) {
		$q->set( 'meta_key', '_wps_child_count' );
		$q->set( 'orderby', 'meta_value_num' );
	}
} );


// Show tracking links in My Account > View Order
add_action( 'woocommerce_order_details_before_order_table', 'wps_tofw_add_tracking_header_myaccount' );

// Show tracking links in Thank You page
add_action( 'woocommerce_order_details_after_order_table', 'wps_tofw_add_tracking_header_thankyou' );

function wps_tofw_add_tracking_header_myaccount( $order ) {
    if ( ! ( is_account_page() && is_wc_endpoint_url( 'view-order' ) ) ) {
        return;
    }
    wps_tofw_render_partial_tracking( $order );
}

function wps_tofw_add_tracking_header_thankyou( $order ) {
    if ( ! is_checkout() || ! is_order_received_page() ) {
        return;
    }
    wps_tofw_render_partial_tracking( $order );
}

/**
 * Shared renderer for tracking column
 */
function wps_tofw_render_partial_tracking( $order ) {
    $wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
    if ( 'on' !== $wps_enable_partila_shipement ) {
        return;
    }

    if ( ! $order instanceof WC_Order ) {
        return;
    }

    $wps_tofw_tracking_numbers = (array) $order->get_meta( '_wps_child_order_ids' );
    if ( empty( $wps_tofw_tracking_numbers ) ) {
        return;
    }

    // Build map of product name → product ID
    $wps_tofw_products = [];
    foreach ( $order->get_items() as $wps_tofw_item ) {
        $wps_tofw_products[ $wps_tofw_item->get_name() ] = $wps_tofw_item->get_product_id();
    }
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function(){
        const wps_tofw_table = document.querySelector(".woocommerce-table--order-details");
        if (!wps_tofw_table) return;

        const wps_tofw_theadRow = wps_tofw_table.querySelector("thead tr");
        if (wps_tofw_theadRow && !wps_tofw_theadRow.querySelector(".tracking-link-col")) {
            const wps_tofw_th = document.createElement("th");
            wps_tofw_th.className = "tracking-link-col";
            wps_tofw_th.innerText = "Partial Tracking Link";
            wps_tofw_theadRow.appendChild(wps_tofw_th);
        }

        const wps_tofw_trackingLinks = <?php echo wp_json_encode( $wps_tofw_tracking_numbers ); ?>;
        const wps_tofw_productMap    = <?php echo wp_json_encode( $wps_tofw_products ); ?>;

        wps_tofw_table.querySelectorAll("tbody tr.woocommerce-table__line-item").forEach(function(wps_tofw_row){
            const wps_tofw_productName = wps_tofw_row.querySelector(".woocommerce-table__product-name a")?.innerText.trim();
            const wps_tofw_productId   = wps_tofw_productMap[wps_tofw_productName] || null;

            const wps_tofw_td = document.createElement("td");
            wps_tofw_td.className = "tracking-link-col";

            if (wps_tofw_productId && wps_tofw_trackingLinks[wps_tofw_productId]) {
                wps_tofw_td.innerHTML = '<a href="<?php echo esc_url( home_url("/track-your-order/?") ); ?>' 
                    + wps_tofw_trackingLinks[wps_tofw_productId] 
                    + '" target="_blank" class="wps-tofw-track-btn">Track Order</a>';
            } else {
                wps_tofw_td.textContent = '-';
            }

            wps_tofw_row.appendChild(wps_tofw_td);
        });
    });
    </script>
    <?php
}











/**
 * Auto-complete child orders when parent order is completed
 */
add_action( 'woocommerce_order_status_completed', 'wps_tofw_auto_complete_child_orders' );

function wps_tofw_auto_complete_child_orders( $order_id ) {
	$wps_tofw_auto_comple = get_option('tofw_aut_comp_part_order');
	$wps_enable_partila_shipement = get_option('tofw_enable_partial_shipment');
	if ('on' !== $wps_enable_partila_shipement && 'on' != $wps_tofw_auto_comple) {
		return;
	}
    $order = wc_get_order( $order_id );
    if ( ! $order instanceof WC_Order ) {
        return;
    }

    // Get child orders from parent order meta
    $child_order_ids = (array) $order->get_meta( '_wps_child_order_ids' );

    if ( ! empty( $child_order_ids ) ) {
        foreach ( $child_order_ids as $child_order_id ) {
            $child_order = wc_get_order( $child_order_id );

            if ( $child_order instanceof WC_Order ) {
                // Only update if not already completed
                if ( $child_order->get_status() !== 'completed' ) {
                    
                    // ✅ Update line item meta for each product in child order
                    foreach ( $child_order->get_items() as $item_id => $item ) {
                        wc_update_order_item_meta(
                            $item_id,
                            '_line_item_status',
                            'completed'
                        );
                    }

                    // ✅ Complete the child order
                    $child_order->update_status(
                        'completed',
                        __( 'Auto-completed because parent order was completed.', 'track-orders-for-woocommerce' )
                    );
                }
            }
        }

        // ✅ ALSO update the parent order’s own line items to completed
        foreach ( $order->get_items() as $parent_item_id => $parent_item ) {
            wc_update_order_item_meta(
                $parent_item_id,
                '_line_item_status',
                'completed'
            );
        }

        // Save parent order changes
        $order->save();
    }
}
