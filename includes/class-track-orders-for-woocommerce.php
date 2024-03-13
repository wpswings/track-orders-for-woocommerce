<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link  https://wpswings.com/
 * @since 1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */
class Track_Orders_For_Woocommerce {


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since 1.0.0
	 * @var   Track_Orders_For_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 * @var   string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0.0
	 * @var   string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0.0
	 * @var   string    $msp_onboard    To initializsed the object of class onboard.
	 */
	protected $msp_onboard;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area,
	 * the public-facing side of the site and common side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( defined( 'TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION' ) ) {
			$this->version = TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION;
		} else {
			$this->version = '1.0.1';
		}

		$this->plugin_name = 'track-orders-for-woocommerce';

		$this->track_orders_for_woocommerce_dependencies();
		$this->track_orders_for_woocommerce_locale();
		if ( is_admin() ) {
			$this->track_orders_for_woocommerce_admin_hooks();
		} else {
			$this->track_orders_for_woocommerce_public_hooks();
		}
		$this->track_orders_for_woocommerce_common_hooks();

		$this->track_orders_for_woocommerce_api_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Track_Orders_For_Woocommerce_Loader. Orchestrates the hooks of the plugin.
	 * - Track_Orders_For_Woocommerce_i18n. Defines internationalization functionality.
	 * - Track_Orders_For_Woocommerce_Admin. Defines all hooks for the admin area.
	 * - Track_Orders_For_Woocommerce_Common. Defines all hooks for the common area.
	 * - Track_Orders_For_Woocommerce_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since 1.0.0
	 */
	private function track_orders_for_woocommerce_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-track-orders-for-woocommerce-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-track-orders-for-woocommerce-i18n.php';

		if ( is_admin() ) {

			// The class responsible for defining all actions that occur in the admin area.
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-track-orders-for-woocommerce-admin.php';

			// The class responsible for on-boarding steps for plugin.
			if ( is_dir( plugin_dir_path( dirname( __FILE__ ) ) . 'onboarding' ) && ! class_exists( 'Track_Orders_For_Woocommerce_Onboarding_Steps' ) ) {
				include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-track-orders-for-woocommerce-onboarding-steps.php';
			}

			if ( class_exists( 'Track_Orders_For_Woocommerce_Onboarding_Steps' ) ) {
				$msp_onboard_steps = new Track_Orders_For_Woocommerce_Onboarding_Steps();
			}
		} else {

			// The class responsible for defining all actions that occur in the public-facing side of the site.
			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-track-orders-for-woocommerce-public.php';

		}

		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'package/rest-api/class-track-orders-for-woocommerce-rest-api.php';

		/**
		 * This class responsible for defining common functionality
		 * of the plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'common/class-track-orders-for-woocommerce-common.php';

		$this->loader = new Track_Orders_For_Woocommerce_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Track_Orders_For_Woocommerce_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since 1.0.0
	 */
	private function track_orders_for_woocommerce_locale() {

		$plugin_i18n = new Track_Orders_For_Woocommerce_I18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Define the name of the hook to save admin notices for this plugin.
	 *
	 * @since 1.0.0
	 */
	private function wps_saved_notice_hook_name() {
		$wps_plugin_name                            = ! empty( explode( '/', plugin_basename( __FILE__ ) ) ) ? explode( '/', plugin_basename( __FILE__ ) )[0] : '';
		$wps_plugin_settings_saved_notice_hook_name = $wps_plugin_name . '_settings_saved_notice';
		return $wps_plugin_settings_saved_notice_hook_name;
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 */
	private function track_orders_for_woocommerce_admin_hooks() {
		$tofw_plugin_admin = new Track_Orders_For_Woocommerce_Admin( $this->tofw_get_plugin_name(), $this->tofw_get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $tofw_plugin_admin, 'tofw_admin_enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $tofw_plugin_admin, 'tofw_admin_enqueue_scripts' );

		// Add settings menu for Track Orders For Woocommerce.
		$this->loader->add_action( 'admin_menu', $tofw_plugin_admin, 'tofw_options_page' );
		$this->loader->add_action( 'admin_menu', $tofw_plugin_admin, 'wps_tofw_remove_default_submenu', 50 );

		// All admin actions and filters after License Validation goes here.
		$this->loader->add_filter( 'wps_add_plugins_menus_array', $tofw_plugin_admin, 'tofw_admin_submenu_page', 15 );
		$this->loader->add_filter( 'tofw_track_order_array', $tofw_plugin_admin, 'tofw_track_order_settings_page', 10 );
		$this->loader->add_filter( 'tofw_general_settings_array', $tofw_plugin_admin, 'tofw_admin_general_settings_page', 10 );
		$this->loader->add_filter( 'tofw_custom_order_status_array', $tofw_plugin_admin, 'tofw_custom_order_status_setting_page', 10 );
		$this->loader->add_action( 'tofw_track_order_gmap_settings_array', $tofw_plugin_admin, 'tofw_track_order_gmap_settings_callback' );
		$this->loader->add_action( 'tofw_shipping_services_settings_array', $tofw_plugin_admin, 'tofw_shipping_services_settings_callback' );

		// Saving tab settings.
		$this->loader->add_action( 'wps_tofw_settings_saved_notice', $tofw_plugin_admin, 'tofw_admin_save_tab_settings' );

		// Developer's Hook Listing.
		$this->loader->add_action( 'tofw_developer_admin_hooks_array', $tofw_plugin_admin, 'wps_developer_admin_hooks_listing' );
		$this->loader->add_action( 'tofw_developer_public_hooks_array', $tofw_plugin_admin, 'wps_developer_public_hooks_listing' );

		$this->loader->add_action( 'wp_ajax_wps_tofw_create_custom_order_status', $tofw_plugin_admin, 'wps_tofw_create_custom_order_status_callback' );
		$this->loader->add_action( 'wp_ajax_wps_tofw_delete_custom_order_status', $tofw_plugin_admin, 'wps_tofw_delete_custom_order_status_callback' );

		$this->loader->add_action( 'wp_ajax_wps_selected_template', $tofw_plugin_admin, 'wps_selected_template_callback' );

		$this->loader->add_action( 'wp_ajax_wps_tofw_insert_address_for_tracking', $tofw_plugin_admin, 'wps_tofw_insert_address_for_tracking' );
		$this->loader->add_action( 'admin_menu', $tofw_plugin_admin, 'wps_tofw_tracking_order_meta_box' );

		$this->loader->add_action( 'woocommerce_process_shop_order_meta', $tofw_plugin_admin, 'wps_tofw_save_delivery_date_meta', 10, 2 );
		$this->loader->add_action( 'woocommerce_process_shop_order_meta', $tofw_plugin_admin, 'wps_tofw_save_shipping_services_meta', 10, 2 );
		$this->loader->add_action( 'woocommerce_process_shop_order_meta', $tofw_plugin_admin, 'wps_tofw_save_custom_shipping_cities_meta', 10, 2 );

	}

	/**
	 * Register all of the hooks related to the common functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 */
	private function track_orders_for_woocommerce_common_hooks() {

		$tofw_plugin_common = new Track_Orders_For_Woocommerce_Common( $this->tofw_get_plugin_name(), $this->tofw_get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $tofw_plugin_common, 'tofw_common_enqueue_styles' );

		$this->loader->add_action( 'wp_enqueue_scripts', $tofw_plugin_common, 'tofw_common_enqueue_scripts' );
		if ( 'on' == get_option( 'wps_tofw_is_plugin_enable' ) ) {
			// Save ajax request for the plugin's multistep.
			$this->loader->add_action( 'wp_ajax_tofw_wps_standard_save_settings_filter', $tofw_plugin_common, 'tofw_wps_standard_save_settings_filter' );
			$this->loader->add_action( 'wp_ajax_nopriv_tofw_wps_standard_save_settings_filter', $tofw_plugin_common, 'tofw_wps_standard_save_settings_filter' );
			if ( self::is_enbale_usage_tracking() ) {
				$this->loader->add_action( 'wpswings_tracker_send_event', $tofw_plugin_common, 'tofw_wpswings_tracker_send_event' );
			}
			// license validation.

			$this->loader->add_filter( 'template_include', $tofw_plugin_common, 'wps_tofw_include_track_order_page', 10, 1 );
			$this->loader->add_action( 'woocommerce_order_status_changed', $tofw_plugin_common, 'wps_tofw_track_order_status', 10, 3 );

			$this->loader->add_action( 'wp_ajax_wps_wot_export_my_orders', $tofw_plugin_common, 'wps_tofw_export_my_orders_callback' );
			$this->loader->add_action( 'wp_ajax_nopriv_wps_tofw_export_my_orders_guest_user', $tofw_plugin_common, 'wps_tofw_export_my_orders_guest_user_callback' );
			$this->loader->add_action( 'init', $tofw_plugin_common, 'wps_tofw_register_custom_order_status' );
			$this->loader->add_action( 'wc_order_statuses', $tofw_plugin_common, 'wps_tofw_add_custom_order_status', 10, 1 );
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 */
	private function track_orders_for_woocommerce_public_hooks() {

		$tofw_plugin_public = new Track_Orders_For_Woocommerce_Public( $this->tofw_get_plugin_name(), $this->tofw_get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $tofw_plugin_public, 'tofw_public_enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $tofw_plugin_public, 'tofw_public_enqueue_scripts' );
		$this->loader->add_action( 'wp_enqueue_scripts', $tofw_plugin_public, 'tofw_public_enqueue_scripts' );

		if ( 'on' == get_option( 'wps_tofw_is_plugin_enable' ) ) {
			$this->loader->add_action( 'woocommerce_order_details_after_order_table', $tofw_plugin_public, 'wps_tofw_track_order_button' );
			$this->loader->add_action( 'woocommerce_order_details_before_order_table_items', $tofw_plugin_public, 'wps_tofw_track_order_info', 10, 1 );
			if ( ! in_array( 'track-orders-for-woocommerce-pro/track-orders-for-woocommerce-pro.php', get_option( 'active_plugins', array() ), true ) ) {
				$this->loader->add_action( 'woocommerce_my_account_my_orders_actions', $tofw_plugin_public, 'wps_tofw_add_track_order_button_on_orderpage', 10, 2 );

			}
			$this->loader->add_action( 'woocommerce_before_account_orders', $tofw_plugin_public, 'wps_wot_add_export_button_before_order_table', 10, 1 );
			$this->loader->add_action( 'template_include', $tofw_plugin_public, 'wps_tofw_include_track_order_page', 10, 1 );
			$this->loader->add_action( 'template_include', $tofw_plugin_public, 'wps_tofw_include_guest_track_order_page', 10, 1 );
			$this->loader->add_action( 'template_include', $tofw_plugin_public, 'wps_ordertracking_page', 10, 1 );
		}
	}

	/**
	 * Register all of the hooks related to the api functionality
	 * of the plugin.
	 *
	 * @since 1.0.0
	 */
	private function track_orders_for_woocommerce_api_hooks() {

		$tofw_plugin_api = new Track_Orders_For_Woocommerce_Rest_Api( $this->tofw_get_plugin_name(), $this->tofw_get_version() );

		$this->loader->add_action( 'rest_api_init', $tofw_plugin_api, 'wps_tofw_add_endpoint' );

	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function tofw_run() {
		$this->loader->tofw_run();
	}

	/**
	 * Check is usage tracking is enable
	 *
	 * @version 1.0.0
	 * @name is_enbale_usage_tracking
	 */
	public static function is_enbale_usage_tracking() {
		$check_is_enable = get_option( 'tofw_enable_tracking', false );
		return ! empty( $check_is_enable ) ? true : false;
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since  1.0.0
	 * @return string    The name of the plugin.
	 */
	public function tofw_get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Track_Orders_For_Woocommerce_Loader    Orchestrates the hooks of the plugin.
	 */
	public function tofw_get_loader() {
		return $this->loader;
	}


	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Track_Orders_For_Woocommerce_Onboard    Orchestrates the hooks of the plugin.
	 */
	public function tofw_get_onboard() {
		return $this->msp_onboard;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string    The version number of the plugin.
	 */
	public function tofw_get_version() {
		return $this->version;
	}

	/**
	 * Predefined default wps_std_plug tabs.
	 *
	 * @return Array       An key=>value pair of Track Orders For Woocommerce tabs.
	 */
	public function wps_std_plug_default_tabs() {
		$tofw_default_tabs     = array();

		$tofw_default_tabs['track-orders-for-woocommerce-general']       = array(
			'title'       => esc_html__( 'General Setting', 'track-orders-for-woocommerce' ),
			'name'        => 'track-orders-for-woocommerce-general',
			'file_path'   => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-general.php',
		);
		$tofw_default_tabs['track-orders-for-woocommerce-track-order'] = array(
			'title'       => esc_html__( 'Track Order', 'track-orders-for-woocommerce' ),
			'name'        => 'track-orders-for-woocommerce-track-order.php',
			'file_path'   => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-track-order.php',
		);
		$tofw_default_tabs['track-orders-for-woocommerce-custom-orders-status']      = array(
			'title'       => esc_html__( 'Custom Order Status', 'track-orders-for-woocommerce' ),
			'name'        => 'track-orders-for-woocommerce-custom-orders-status',
			'file_path'   => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-custom-order-status.php',
		);
		$tofw_default_tabs['track-orders-for-woocommerce-template']      = array(
			'title'       => esc_html__( 'Template', 'track-orders-for-woocommerce' ),
			'name'        => 'track-orders-for-woocommerce-template',
			'file_path'   => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-template.php',
		);
		$tofw_default_tabs['track-orders-for-woocommerce-track-your-your-order-with-google-map']      = array(
			'title'       => esc_html__( 'Track Order with Google Maps', 'track-orders-for-woocommerce' ),
			'name'        => 'track-orders-for-woocommerce-track-your-your-order-with-google-map',
			'file_path'   => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-track-your-order-with-google-map.php',
		);
		$tofw_default_tabs['track-orders-for-woocommerce-shipping-services']      = array(
			'title'       => esc_html__( 'Shipping Services', 'track-orders-for-woocommerce' ),
			'name'        => 'track-orders-for-woocommerce-shipping-services',
			'file_path'   => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-shipping-services.php',
		);
		$tofw_default_tabs =
		// desc - filter for trial.
		apply_filters( 'track_orders_for_woocmmerce_admin_settings_tabs', $tofw_default_tabs );
		$tofw_default_tabs['track-orders-for-woocommerce-overview']      = array(
			'title'       => esc_html__( 'Overview', 'track-orders-for-woocommerce' ),
			'name'        => 'track-orders-for-woocommerce-overview',
			'file_path'   => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-overview.php',
		);
		$tofw_default_tabs['track-orders-for-woocommerce-developer']     = array(
			'title'       => esc_html__( 'Developer', 'track-orders-for-woocommerce' ),
			'name'        => 'track-orders-for-woocommerce-developer',
			'file_path'   => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-developer.php',
		);

		return $tofw_default_tabs;
	}

	/**
	 * Locate and load appropriate tempate.
	 *
	 * @since 1.0.0
	 * @param string $path   path file for inclusion.
	 * @param array  $file_name parameters to pass to the file for access.
	 */
	public function wps_tofw_plug_load_template( $path, $file_name ) {

		// $tofw_file_path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . $path;
		$tofw_file_path = apply_filters( 'wps_tofw_pro_tab_template_html', $path, $file_name );

		if ( file_exists( $path ) ) {

			include( $path );

		} else {
			/* translators: %s: file path */
			$etmfw_notice = sprintf( esc_html__( 'Unable to locate file at location "%s". Some features may not work properly in this plugin. Please contact us!', 'event-tickets-manager-for-woocommerce' ), $path );
			$this->wps_std_plug_admin_notice( $etmfw_notice, 'error' );
		}
	}

	/**
	 * Show admin notices.
	 *
	 * @param string $tofw_message Message to display.
	 * @param string $type        notice type, accepted values - error/update/update-nag.
	 * @since 1.0.0
	 */
	public static function wps_std_plug_admin_notice( $tofw_message, $type = 'error' ) {

		$tofw_classes = 'notice ';

		switch ( $type ) {

			case 'update':
				$tofw_classes .= 'updated is-dismissible';
				break;

			case 'update-nag':
				$tofw_classes .= 'update-nag is-dismissible';
				break;

			case 'success':
				$tofw_classes .= 'notice-success is-dismissible';
				break;

			default:
				$tofw_classes .= 'notice-error is-dismissible';
		}

		$tofw_notice  = '<div class="' . esc_attr( $tofw_classes ) . '">';
		$tofw_notice .= '<p>' . esc_html( $tofw_message ) . '</p>';
		$tofw_notice .= '</div>';

		echo wp_kses_post( $tofw_notice );
	}


	/**
	 * Show WordPress and server info.
	 *
	 * @return Array $tofw_system_data       returns array of all WordPress and server related information.
	 * @since  1.0.0
	 */
	public function wps_std_plug_system_status() {
		global $wpdb;
		$tofw_system_status    = array();
		$tofw_wordpress_status = array();
		$tofw_system_data      = array();

		// Get the web server.
		$tofw_system_status['web_server'] = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';

		// Get PHP version.
		$tofw_system_status['php_version'] = function_exists( 'phpversion' ) ? phpversion() : __( 'N/A (phpversion function does not exist)', 'track-orders-for-woocommerce' );

		// Get the server's IP address.
		$tofw_system_status['server_ip'] = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '';

		// Get the server's port.
		$tofw_system_status['server_port'] = isset( $_SERVER['SERVER_PORT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_PORT'] ) ) : '';

		// Get the uptime.
		$tofw_system_status['uptime'] = function_exists( 'exec' ) ? @exec( 'uptime -p' ) : __( 'N/A (make sure exec function is enabled)', 'track-orders-for-woocommerce' );

		// Get the server path.
		$tofw_system_status['server_path'] = defined( 'ABSPATH' ) ? ABSPATH : __( 'N/A (ABSPATH constant not defined)', 'track-orders-for-woocommerce' );

		// Get the OS.
		$tofw_system_status['os'] = function_exists( 'php_uname' ) ? php_uname( 's' ) : __( 'N/A (php_uname function does not exist)', 'track-orders-for-woocommerce' );

		// Get WordPress version.
		$tofw_wordpress_status['wp_version'] = function_exists( 'get_bloginfo' ) ? get_bloginfo( 'version' ) : __( 'N/A (get_bloginfo function does not exist)', 'track-orders-for-woocommerce' );

		// Get and count active WordPress plugins.
		$tofw_wordpress_status['wp_active_plugins'] = function_exists( 'get_option' ) ? count( get_option( 'active_plugins' ) ) : __( 'N/A (get_option function does not exist)', 'track-orders-for-woocommerce' );

		// See if this site is multisite or not.
		$tofw_wordpress_status['wp_multisite'] = function_exists( 'is_multisite' ) && is_multisite() ? __( 'Yes', 'track-orders-for-woocommerce' ) : __( 'No', 'track-orders-for-woocommerce' );

		// See if WP Debug is enabled.
		$tofw_wordpress_status['wp_debug_enabled'] = defined( 'WP_DEBUG' ) ? __( 'Yes', 'track-orders-for-woocommerce' ) : __( 'No', 'track-orders-for-woocommerce' );

		// See if WP Cache is enabled.
		$tofw_wordpress_status['wp_cache_enabled'] = defined( 'WP_CACHE' ) ? __( 'Yes', 'track-orders-for-woocommerce' ) : __( 'No', 'track-orders-for-woocommerce' );

		// Get the total number of WordPress users on the site.
		$tofw_wordpress_status['wp_users'] = function_exists( 'count_users' ) ? count_users() : __( 'N/A (count_users function does not exist)', 'track-orders-for-woocommerce' );

		// Get the number of published WordPress posts.
		$tofw_wordpress_status['wp_posts'] = wp_count_posts()->publish >= 1 ? wp_count_posts()->publish : __( '0', 'track-orders-for-woocommerce' );

		// Get PHP memory limit.
		$tofw_system_status['php_memory_limit'] = function_exists( 'ini_get' ) ? (int) ini_get( 'memory_limit' ) : __( 'N/A (ini_get function does not exist)', 'track-orders-for-woocommerce' );

		// Get the PHP error log path.
		$tofw_system_status['php_error_log_path'] = ! ini_get( 'error_log' ) ? __( 'N/A', 'track-orders-for-woocommerce' ) : ini_get( 'error_log' );

		// Get PHP max upload size.
		$tofw_system_status['php_max_upload'] = function_exists( 'ini_get' ) ? (int) ini_get( 'upload_max_filesize' ) : __( 'N/A (ini_get function does not exist)', 'track-orders-for-woocommerce' );

		// Get PHP max post size.
		$tofw_system_status['php_max_post'] = function_exists( 'ini_get' ) ? (int) ini_get( 'post_max_size' ) : __( 'N/A (ini_get function does not exist)', 'track-orders-for-woocommerce' );

		// Get the PHP architecture.
		if ( PHP_INT_SIZE == 4 ) {
			$tofw_system_status['php_architecture'] = '32-bit';
		} elseif ( PHP_INT_SIZE == 8 ) {
			$tofw_system_status['php_architecture'] = '64-bit';
		} else {
			$tofw_system_status['php_architecture'] = 'N/A';
		}

		// Get server host name.
		$tofw_system_status['server_hostname'] = function_exists( 'gethostname' ) ? gethostname() : __( 'N/A (gethostname function does not exist)', 'track-orders-for-woocommerce' );

		// Show the number of processes currently running on the server.
		$tofw_system_status['processes'] = function_exists( 'exec' ) ? @exec( 'ps aux | wc -l' ) : __( 'N/A (make sure exec is enabled)', 'track-orders-for-woocommerce' );

		// Get the memory usage.
		$tofw_system_status['memory_usage'] = function_exists( 'memory_get_peak_usage' ) ? round( memory_get_peak_usage( true ) / 1024 / 1024, 2 ) : 0;

		// Get CPU usage.
		// Check to see if system is Windows, if so then use an alternative since sys_getloadavg() won't work.
		if ( stristr( PHP_OS, 'win' ) ) {
			$tofw_system_status['is_windows']        = true;
			$tofw_system_status['windows_cpu_usage'] = function_exists( 'exec' ) ? @exec( 'wmic cpu get loadpercentage /all' ) : __( 'N/A (make sure exec is enabled)', 'track-orders-for-woocommerce' );
		}

		// Get the memory limit.
		$tofw_system_status['memory_limit'] = function_exists( 'ini_get' ) ? (int) ini_get( 'memory_limit' ) : __( 'N/A (ini_get function does not exist)', 'track-orders-for-woocommerce' );

		// Get the PHP maximum execution time.
		$tofw_system_status['php_max_execution_time'] = function_exists( 'ini_get' ) ? ini_get( 'max_execution_time' ) : __( 'N/A (ini_get function does not exist)', 'track-orders-for-woocommerce' );

		// Get outgoing IP address.
		$tofw_system_status['outgoing_ip'] = function_exists( 'wp_remote_get' ) ? wp_remote_retrieve_body( wp_remote_get( 'http://ipecho.net/plain' ) ) : __( 'N/A (wp_remote_get function does not exist)', 'track-orders-for-woocommerce' );


		$tofw_system_data['php'] = $tofw_system_status;
		$tofw_system_data['wp']  = $tofw_wordpress_status;

		return $tofw_system_data;
	}

	/**
	 * Generate html components.
	 *
	 * @param string $tofw_components html to display.
	 * @since 1.0.0
	 */
	public function wps_std_plug_generate_html( $tofw_components = array() ) {
		if ( is_array( $tofw_components ) && ! empty( $tofw_components ) ) {
			foreach ( $tofw_components as $tofw_component ) {
				if ( ! empty( $tofw_component['type'] ) && ! empty( $tofw_component['id'] ) ) {
					switch ( $tofw_component['type'] ) {

						case 'hidden':
						case 'number':
						case 'email':
						case 'text':
							?>
						<div class="wps-form-group wps-msp-<?php echo esc_attr( $tofw_component['type'] ); ?>">
							<div class="wps-form-group__label">
								<label for="<?php echo esc_attr( $tofw_component['id'] ); ?>" class="wps-form-label"><?php echo ( isset( $tofw_component['title'] ) ? esc_html( $tofw_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="wps-form-group__control">
								<label class="mdc-text-field mdc-text-field--outlined">
									<span class="mdc-notched-outline">
										<span class="mdc-notched-outline__leading"></span>
										<span class="mdc-notched-outline__notch">
							<?php if ( 'number' != $tofw_component['type'] ) { ?>
												<span class="mdc-floating-label" id="my-label-id" style=""><?php echo ( isset( $tofw_component['placeholder'] ) ? esc_attr( $tofw_component['placeholder'] ) : '' ); ?></span>
						<?php } ?>
										</span>
										<span class="mdc-notched-outline__trailing"></span>
									</span>
									<input
									class="mdc-text-field__input <?php echo ( isset( $tofw_component['class'] ) ? esc_attr( $tofw_component['class'] ) : '' ); ?>" 
									name="<?php echo ( isset( $tofw_component['name'] ) ? esc_html( $tofw_component['name'] ) : esc_html( $tofw_component['id'] ) ); ?>"
									id="<?php echo esc_attr( $tofw_component['id'] ); ?>"
									type="<?php echo esc_attr( $tofw_component['type'] ); ?>"
									value="<?php echo ( isset( $tofw_component['value'] ) ? esc_attr( $tofw_component['value'] ) : '' ); ?>"
									placeholder="<?php echo ( isset( $tofw_component['placeholder'] ) ? esc_attr( $tofw_component['placeholder'] ) : '' ); ?>"
									>
								</label>
								<div class="mdc-text-field-helper-line">
									<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true"><?php echo ( isset( $tofw_component['description'] ) ? esc_attr( $tofw_component['description'] ) : '' ); ?></div>
								</div>
							</div>
						</div>
							<?php
							break;
						case 'custom_status':
							require_once TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/class-wps-custom-order-status.php';
							break;
						case 'password':
							?>
						<div class="wps-form-group">
							<div class="wps-form-group__label">
								<label for="<?php echo esc_attr( $tofw_component['id'] ); ?>" class="wps-form-label"><?php echo ( isset( $tofw_component['title'] ) ? esc_html( $tofw_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="wps-form-group__control">
								<label class="mdc-text-field mdc-text-field--outlined mdc-text-field--with-trailing-icon">
									<span class="mdc-notched-outline">
										<span class="mdc-notched-outline__leading"></span>
										<span class="mdc-notched-outline__notch">
										</span>
										<span class="mdc-notched-outline__trailing"></span>
									</span>
									<input 
									class="mdc-text-field__input <?php echo ( isset( $tofw_component['class'] ) ? esc_attr( $tofw_component['class'] ) : '' ); ?> wps-form__password" 
									name="<?php echo ( isset( $tofw_component['name'] ) ? esc_html( $tofw_component['name'] ) : esc_html( $tofw_component['id'] ) ); ?>"
									id="<?php echo esc_attr( $tofw_component['id'] ); ?>"
									type="<?php echo esc_attr( $tofw_component['type'] ); ?>"
									value="<?php echo ( isset( $tofw_component['value'] ) ? esc_attr( $tofw_component['value'] ) : '' ); ?>"
									placeholder="<?php echo ( isset( $tofw_component['placeholder'] ) ? esc_attr( $tofw_component['placeholder'] ) : '' ); ?>"
									>
									<i class="material-icons mdc-text-field__icon mdc-text-field__icon--trailing wps-password-hidden" tabindex="0" role="button">visibility</i>
								</label>
								<div class="mdc-text-field-helper-line">
									<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true"><?php echo ( isset( $tofw_component['description'] ) ? esc_attr( $tofw_component['description'] ) : '' ); ?></div>
								</div>
							</div>
						</div>
							<?php
							break;

						case 'textarea':
							?>
						<div class="wps-form-group">
							<div class="wps-form-group__label">
								<label class="wps-form-label" for="<?php echo esc_attr( $tofw_component['id'] ); ?>"><?php echo ( isset( $tofw_component['title'] ) ? esc_html( $tofw_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="wps-form-group__control">
								<label class="mdc-text-field mdc-text-field--outlined mdc-text-field--textarea"      for="text-field-hero-input">
									<span class="mdc-notched-outline">
										<span class="mdc-notched-outline__leading"></span>
										<span class="mdc-notched-outline__notch">
											<span class="mdc-floating-label"><?php echo ( isset( $tofw_component['placeholder'] ) ? esc_attr( $tofw_component['placeholder'] ) : '' ); ?></span>
										</span>
										<span class="mdc-notched-outline__trailing"></span>
									</span>
									<span class="mdc-text-field__resizer">
										<textarea class="mdc-text-field__input <?php echo ( isset( $tofw_component['class'] ) ? esc_attr( $tofw_component['class'] ) : '' ); ?>" rows="2" cols="25" aria-label="Label" name="<?php echo ( isset( $tofw_component['name'] ) ? esc_html( $tofw_component['name'] ) : esc_html( $tofw_component['id'] ) ); ?>" id="<?php echo esc_attr( $tofw_component['id'] ); ?>" placeholder="<?php echo ( isset( $tofw_component['placeholder'] ) ? esc_attr( $tofw_component['placeholder'] ) : '' ); ?>"><?php echo ( isset( $tofw_component['value'] ) ? esc_textarea( $tofw_component['value'] ) : '' ); // WPCS: XSS ok. ?></textarea>
									</span>
								</label>
							</div>
						</div>
							<?php
							break;

						case 'select':
						case 'multiselect':
							?>
						<div class="wps-form-group">
							<div class="wps-form-group__label">
								<label class="wps-form-label" for="<?php echo esc_attr( $tofw_component['id'] ); ?>"><?php echo ( isset( $tofw_component['title'] ) ? esc_html( $tofw_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="wps-form-group__control">
								<div class="wps-form-select">
									<select id="<?php echo esc_attr( $tofw_component['id'] ); ?>" name="<?php echo ( isset( $tofw_component['name'] ) ? esc_html( $tofw_component['name'] ) : esc_html( $tofw_component['id'] ) ); ?><?php echo ( 'multiselect' === $tofw_component['type'] ) ? '[]' : ''; ?>" id="<?php echo esc_attr( $tofw_component['id'] ); ?>" class="mdl-textfield__input <?php echo ( isset( $tofw_component['class'] ) ? esc_attr( $tofw_component['class'] ) : '' ); ?>" <?php echo 'multiselect' === $tofw_component['type'] ? 'multiple="multiple"' : ''; ?> >
							<?php
							foreach ( $tofw_component['options'] as $tofw_key => $tofw_val ) {
								?>
											<option value="<?php echo esc_attr( $tofw_key ); ?>"
												<?php
												if ( is_array( $tofw_component['value'] ) ) {
													selected( in_array( (string) $tofw_key, $tofw_component['value'], true ), true );
												} else {
														   selected( $tofw_component['value'], (string) $tofw_key );
												}
												?>
												>
												<?php echo esc_html( $tofw_val ); ?>
											</option>
										<?php
							}
							?>
									</select>
									<label class="mdl-textfield__label" for="<?php echo esc_attr( $tofw_component['id'] ); ?>"><?php echo ( isset( $tofw_component['description'] ) ? esc_attr( $tofw_component['description'] ) : '' ); ?></label>
								</div>
							</div>
						</div>

							<?php
							break;

						case 'checkbox':
							?>
						<div class="wps-form-group">
							<div class="wps-form-group__label">
								<label for="<?php echo esc_attr( $tofw_component['id'] ); ?>" class="wps-form-label"><?php echo ( isset( $tofw_component['title'] ) ? esc_html( $tofw_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="wps-form-group__control wps-pl-4">
								<div class="mdc-form-field">
									<div class="mdc-checkbox">
										<input 
										name="<?php echo ( isset( $tofw_component['name'] ) ? esc_html( $tofw_component['name'] ) : esc_html( $tofw_component['id'] ) ); ?>"
										id="<?php echo esc_attr( $tofw_component['id'] ); ?>"
										type="checkbox"
										class="mdc-checkbox__native-control <?php echo ( isset( $tofw_component['class'] ) ? esc_attr( $tofw_component['class'] ) : '' ); ?>"
										value="<?php echo ( isset( $tofw_component['value'] ) ? esc_attr( $tofw_component['value'] ) : '' ); ?>"
							<?php checked( $tofw_component['value'], '1' ); ?>
										/>
										<div class="mdc-checkbox__background">
											<svg class="mdc-checkbox__checkmark" viewBox="0 0 24 24">
												<path class="mdc-checkbox__checkmark-path" fill="none" d="M1.73,12.91 8.1,19.28 22.79,4.59"/>
											</svg>
											<div class="mdc-checkbox__mixedmark"></div>
										</div>
										<div class="mdc-checkbox__ripple"></div>
									</div>
									<label for="checkbox-1"><?php echo ( isset( $tofw_component['description'] ) ? esc_attr( $tofw_component['description'] ) : '' ); ?></label>
								</div>
							</div>
						</div>
							<?php
							break;

						case 'radio':
							?>
						<div class="wps-form-group">
							<div class="wps-form-group__label">
								<label for="<?php echo esc_attr( $tofw_component['id'] ); ?>" class="wps-form-label"><?php echo ( isset( $tofw_component['title'] ) ? esc_html( $tofw_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="wps-form-group__control wps-pl-4">
								<div class="wps-flex-col">
							<?php
							foreach ( $tofw_component['options'] as $tofw_radio_key => $tofw_radio_val ) {
								?>
										<div class="mdc-form-field">
											<div class="mdc-radio">
												<input
												name="<?php echo ( isset( $tofw_component['name'] ) ? esc_html( $tofw_component['name'] ) : esc_html( $tofw_component['id'] ) ); ?>"
												value="<?php echo esc_attr( $tofw_radio_key ); ?>"
												type="radio"
												class="mdc-radio__native-control <?php echo ( isset( $tofw_component['class'] ) ? esc_attr( $tofw_component['class'] ) : '' ); ?>"
								<?php checked( $tofw_radio_key, $tofw_component['value'] ); ?>
												>
												<div class="mdc-radio__background">
													<div class="mdc-radio__outer-circle"></div>
													<div class="mdc-radio__inner-circle"></div>
												</div>
												<div class="mdc-radio__ripple"></div>
											</div>
											<label for="radio-1"><?php echo esc_html( $tofw_radio_val ); ?></label>
										</div>    
								<?php
							}
							?>
								</div>
							</div>
						</div>
							<?php
							break;

						case 'radio-switch':
							?>

						<div class="wps-form-group">
							<div class="wps-form-group__label">
								<label for="" class="wps-form-label"><?php echo ( isset( $tofw_component['title'] ) ? esc_html( $tofw_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
							</div>
							<div class="wps-form-group__control">
								<div>
									<div class="mdc-switch">
										<div class="mdc-switch__track"></div>
										<div class="mdc-switch__thumb-underlay">
											<div class="mdc-switch__thumb"></div>
											<input name="<?php echo ( isset( $tofw_component['name'] ) ? esc_html( $tofw_component['name'] ) : esc_html( $tofw_component['id'] ) ); ?>" type="checkbox" id="<?php echo esc_html( $tofw_component['id'] ); ?>" value="on" class="mdc-switch__native-control <?php echo ( isset( $tofw_component['class'] ) ? esc_attr( $tofw_component['class'] ) : '' ); ?>" role="switch" aria-checked="
																	<?php
																	if ( 'on' == $tofw_component['value'] ) {
																		echo 'true';
																	} else {
																		echo 'false';
																	}
																	?>
							"
											<?php checked( $tofw_component['value'], 'on' ); ?>
											>
										</div>
									</div>
								</div>
								<div class="mdc-text-field-helper-line">
									<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true"><?php echo ( isset( $tofw_component['description'] ) ? esc_attr( $tofw_component['description'] ) : '' ); ?></div>
								</div>
							</div>
						</div>
							<?php
							break;

						case 'button':
							?>
						<div class="wps-form-group">
							<div class="wps-form-group__label"></div>
							<div class="wps-form-group__control">
								<button class="mdc-button mdc-button--raised" name= "<?php echo ( isset( $tofw_component['name'] ) ? esc_html( $tofw_component['name'] ) : esc_html( $tofw_component['id'] ) ); ?>"
									id="<?php echo esc_attr( $tofw_component['id'] ); ?>"> <span class="mdc-button__ripple"></span>
									<span class="mdc-button__label <?php echo ( isset( $tofw_component['class'] ) ? esc_attr( $tofw_component['class'] ) : '' ); ?>"><?php echo ( isset( $tofw_component['button_text'] ) ? esc_html( $tofw_component['button_text'] ) : '' ); ?></span>
								</button>
							</div>
						</div>

							<?php
							break;

						case 'multi':
							?>
							<div class="wps-form-group wps-msp-<?php echo esc_attr( $tofw_component['type'] ); ?>">
								<div class="wps-form-group__label">
									<label for="<?php echo esc_attr( $tofw_component['id'] ); ?>" class="wps-form-label"><?php echo ( isset( $tofw_component['title'] ) ? esc_html( $tofw_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
									</div>
									<div class="wps-form-group__control">
							<?php
							foreach ( $tofw_component['value'] as $component ) {
								?>
											<label class="mdc-text-field mdc-text-field--outlined">
												<span class="mdc-notched-outline">
													<span class="mdc-notched-outline__leading"></span>
													<span class="mdc-notched-outline__notch">
								<?php if ( 'number' != $component['type'] ) { ?>
															<span class="mdc-floating-label" id="my-label-id" style=""><?php echo ( isset( $tofw_component['placeholder'] ) ? esc_attr( $tofw_component['placeholder'] ) : '' ); ?></span>
							<?php } ?>
													</span>
													<span class="mdc-notched-outline__trailing"></span>
												</span>
												<input 
												class="mdc-text-field__input <?php echo ( isset( $tofw_component['class'] ) ? esc_attr( $tofw_component['class'] ) : '' ); ?>" 
												name="<?php echo ( isset( $tofw_component['name'] ) ? esc_html( $tofw_component['name'] ) : esc_html( $tofw_component['id'] ) ); ?>"
												id="<?php echo esc_attr( $component['id'] ); ?>"
												type="<?php echo esc_attr( $component['type'] ); ?>"
												value="<?php echo ( isset( $tofw_component['value'] ) ? esc_attr( $tofw_component['value'] ) : '' ); ?>"
												placeholder="<?php echo ( isset( $tofw_component['placeholder'] ) ? esc_attr( $tofw_component['placeholder'] ) : '' ); ?>"
								<?php echo esc_attr( ( 'number' === $component['type'] ) ? 'max=10 min=0' : '' ); ?>
												>
											</label>
							<?php } ?>
									<div class="mdc-text-field-helper-line">
										<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true"><?php echo ( isset( $tofw_component['description'] ) ? esc_attr( $tofw_component['description'] ) : '' ); ?></div>
									</div>
								</div>
							</div>
								<?php
							break;
						case 'color':
						case 'date':
						case 'file':
							?>
							<div class="wps-form-group wps-msp-<?php echo esc_attr( $tofw_component['type'] ); ?>">
								<div class="wps-form-group__label">
									<label for="<?php echo esc_attr( $tofw_component['id'] ); ?>" class="wps-form-label"><?php echo ( isset( $tofw_component['title'] ) ? esc_html( $tofw_component['title'] ) : '' ); // WPCS: XSS ok. ?></label>
								</div>
								<div class="wps-form-group__control">
									<label>
										<input 
										class="<?php echo ( isset( $tofw_component['class'] ) ? esc_attr( $tofw_component['class'] ) : '' ); ?>" 
										name="<?php echo ( isset( $tofw_component['name'] ) ? esc_html( $tofw_component['name'] ) : esc_html( $tofw_component['id'] ) ); ?>"
										id="<?php echo esc_attr( $tofw_component['id'] ); ?>"
										type="<?php echo esc_attr( $tofw_component['type'] ); ?>"
										value="<?php echo ( isset( $tofw_component['value'] ) ? esc_attr( $tofw_component['value'] ) : '' ); ?>"
									<?php echo esc_html( ( 'date' === $tofw_component['type'] ) ? 'max=' . gmdate( 'Y-m-d', strtotime( gmdate( 'Y-m-d', mktime() ) . ' + 365 day' ) ) . 'min=' . gmdate( 'Y-m-d' ) . '' : '' ); ?>
										>
									</label>
									<div class="mdc-text-field-helper-line">
										<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true"><?php echo ( isset( $tofw_component['description'] ) ? esc_attr( $tofw_component['description'] ) : '' ); ?></div>
									</div>
								</div>
							</div>
							<?php
							break;

						case 'submit':
							?>
						<tr valign="top">
							<td scope="row">
								<input type="submit" class="button button-primary" 
								name="<?php echo ( isset( $tofw_component['name'] ) ? esc_html( $tofw_component['name'] ) : esc_html( $tofw_component['id'] ) ); ?>"
								id="<?php echo esc_attr( $tofw_component['id'] ); ?>"
								class="<?php echo ( isset( $tofw_component['class'] ) ? esc_attr( $tofw_component['class'] ) : '' ); ?>"
								value="<?php echo esc_attr( $tofw_component['button_text'] ); ?>"
								/>
							</td>
						</tr>
							<?php
							break;

						default:
							break;
					}
				}
			}
		}
	}

	/**
	 * Public static variable to be accessed in this plugin.
	 *
	 * @var string
	 */
	public static $lic_callback_function = 'check_lcns_validity';

	// public static variable to be accessed in this plugin.
	/**
	 * Public static variable to be accessed in this plugin.
	 *
	 * @var string
	 */
	public static $lic_ini_callback_function = 'check_lcns_initial_days';

	/**
	 * Validate the use of features of this plugin.
	 *
	 * @since 1.0.0
	 */
	public static function check_lcns_validity() {

		$wps_tofw_lcns_key = get_option( 'wps_tofw_license_key', '' );

		$wps_tofw_lcns_status = get_option( 'wps_tofw_license_check', '' );

		if ( $wps_tofw_lcns_key && true == $wps_tofw_lcns_status ) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Validate the use of features of this plugin for initial days.
	 *
	 * @since 1.0.0
	 */
	public static function check_lcns_initial_days() {

		$thirty_days = get_option( 'wps_tofw_activated_timestamp', 0 );

		$current_time = current_time( 'timestamp' );
		$day_count = ( $thirty_days - $current_time ) / ( 24 * 60 * 60 );

		return $day_count;
	}
}
