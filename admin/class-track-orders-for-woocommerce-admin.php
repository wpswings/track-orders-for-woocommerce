<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link  https://wpswings.com/
 * @since 1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin
 */

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Utilities\OrderUtil;
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin
 */
class Track_Orders_For_Woocommerce_Admin {


	/**
	 * The ID of this plugin.
	 *
	 * @since 1.0.0
	 * @var   string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 1.0.0
	 * @var   string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 * @param string $hook The plugin page slug.
	 */
	public function tofw_admin_enqueue_styles( $hook ) {
		$screen = get_current_screen();

		if ( isset( $screen->id ) && ( 'wpswings_page_home' === $screen->id || 'wpswings_page_track_orders_for_woocommerce_menu' === $screen->id || 'wp-swings_page_track_orders_for_woocommerce_menu' === $screen->id ) ) {

			wp_enqueue_style( 'track-orders-for-woocommerce-select2-css', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/select-2/track-orders-for-woocommerce-select2.css', array(), time(), 'all' );

			wp_enqueue_style( 'track-orders-for-woocommerce-meterial-css', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-web.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'track-orders-for-woocommerce-meterial-css2', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-v5.0-web.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'track-orders-for-woocommerce-meterial-lite', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-lite.min.css', array(), time(), 'all' );

			wp_enqueue_style( 'track-orders-for-woocommerce-meterial-icons-css', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/icon.css', array(), time(), 'all' );

			wp_enqueue_style( $this->plugin_name . '-admin-global', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/track-orders-for-woocommerce-admin-global.css', array( 'track-orders-for-woocommerce-meterial-icons-css' ), time(), 'all' );

			wp_enqueue_style( $this->plugin_name, TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/track-orders-for-woocommerce-admin.scss', array(), $this->version, 'all' );
			wp_enqueue_style( 'wps-admin-min-css', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/wps-admin.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'wps-datatable-css', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/datatables/media/css/jquery.dataTables.min.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 * @param string $hook The plugin page slug.
	 */
	public function tofw_admin_enqueue_scripts( $hook ) {

		$screen = get_current_screen();
		if ( isset( $screen->id ) && ( 'wpswings_page_home' === $screen->id || 'wpswings_page_track_orders_for_woocommerce_menu' === $screen->id || 'wp-swings_page_track_orders_for_woocommerce_menu' === $screen->id ) ) {

			wp_enqueue_script( 'track-orders-for-woocommerce-select2', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/select-2/track-orders-for-woocommerce-select2.js', array( 'jquery' ), time(), false );

			wp_enqueue_script( 'track-orders-for-woocommerce-metarial-js', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-web.min.js', array(), time(), false );
			wp_enqueue_script( 'track-orders-for-woocommerce-metarial-js2', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-v5.0-web.min.js', array(), time(), false );
			wp_enqueue_script( 'track-orders-for-woocommerce-metarial-lite', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-lite.min.js', array(), time(), false );
			wp_enqueue_script( 'track-orders-for-woocommerce-datatable', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/datatables.net/js/jquery.dataTables.min.js', array(), time(), false );
			wp_enqueue_script( 'track-orders-for-woocommerce-datatable-btn', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/datatables.net/buttons/dataTables.buttons.min.js', array(), time(), false );
			wp_enqueue_script( 'track-orders-for-woocommerce-datatable-btn-2', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/datatables.net/buttons/buttons.html5.min.js', array(), time(), false );
			wp_register_script( $this->plugin_name . 'admin-js', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/track-orders-for-woocommerce-admin.js', array( 'jquery', 'track-orders-for-woocommerce-select2', 'track-orders-for-woocommerce-metarial-js', 'track-orders-for-woocommerce-metarial-js2', 'track-orders-for-woocommerce-metarial-lite' ), $this->version, false );
			wp_localize_script(
				$this->plugin_name . 'admin-js',
				'tofw_admin_param',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'wps_tofw_nonce' => wp_create_nonce( 'ajax-nonce' ),
					'reloadurl' => admin_url( 'admin.php?page=track_orders_for_woocommerce_menu' ),
					'msp_gen_tab_enable' => get_option( 'tofw_radio_switch_demo' ),
					'tofw_admin_param_location' => ( admin_url( 'admin.php' ) . '?page=track_orders_for_woocommerce_menu&tofw_tab=track-orders-for-woocommerce-general' ),
					'wps_tofw_close_button' => __( 'Close', 'track-orders-for-woocommerce' ),
					'message_success'  => __( 'Order Status successfully saved.', 'track-orders-for-woocommerce' ),
					'message_invalid_input'  => __( 'Please enter a Valid Status Name.', 'track-orders-for-woocommerce' ),
					'message_error_save'  => __( 'Unable to save Order Status.', 'track-orders-for-woocommerce' ),
					'message_empty_data'  => __( 'Please enter the status name .', 'track-orders-for-woocommerce' ),
					'message_template_activated' => __( 'Template Activated sucessfully.', 'track-orders-for-woocommerce' ),
					'address_validation' => __( 'Please Enter Address First', 'track-orders-for-woocommerce' ),
					'address_validation_success' => __( 'Address Successfully Added', 'track-orders-for-woocommerce' ),
					'selec_address_placeholder' => __( 'Select Your Hubpoint Addresses', 'track-orders-for-woocommerce' ),
					'site_url' => site_url(),

				)
			);
			wp_enqueue_script( $this->plugin_name . 'admin-js' );

		}
		add_thickbox();

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-timepicker-js', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/jquery.ui.timepicker.js', array(), time(), false );
		wp_register_script( 'wps-admin-js', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/wps-admin.js', array(), time(), false );
		wp_localize_script(
			'wps-admin-js',
			'wps_admin_param',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'is_enable_status_icon'  => get_option( 'wps_tofw_enable_use_of_icon' ),
				'site_url' => site_url(),
				'custom_order_status_url'  => get_option( 'wps_tofw_new_custom_order_image' ),
				'wps_file_include'  => plugin_dir_url( __FILE__ ),
			),
		);
		wp_enqueue_script( 'wps-admin-js' );

	}

	/**
	 * Adding settings menu for Track Orders For Woocommerce.
	 *
	 * @since 1.0.0
	 */
	public function tofw_options_page() {
		global $submenu;
		if ( empty( $GLOBALS['admin_page_hooks']['wps-plugins'] ) ) {
			add_menu_page( 'WP Swings', 'WP Swings', 'manage_options', 'wps-plugins', array( $this, 'wps_plugins_listing_page' ), TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/wpswings_logo.png', 15 );

			add_submenu_page( 'wps-plugins', 'Home', 'Home', 'manage_options', 'home', array( $this, 'wpswings_welcome_callback_function' ) );

			$tofw_menus =
			// desc - filter for trial.
			apply_filters( 'wps_add_plugins_menus_array', array() );

			if ( is_array( $tofw_menus ) && ! empty( $tofw_menus ) ) {
				foreach ( $tofw_menus as $tofw_key => $tofw_value ) {
					add_submenu_page( 'wps-plugins', $tofw_value['name'], $tofw_value['name'], 'manage_options', $tofw_value['menu_link'], array( $tofw_value['instance'], $tofw_value['function'] ) );
				}
			}
		} else {
			$is_home = false;
			if ( ! empty( $submenu['wps-plugins'] ) ) {
				foreach ( $submenu['wps-plugins'] as $key => $value ) {
					if ( 'Home' === $value[0] ) {
						$is_home = true;
					}
				}
				if ( ! $is_home ) {

						add_submenu_page( 'wps-plugins', 'Home', 'Home', 'manage_options', 'home', array( $this, 'wpswings_welcome_callback_function' ), 1 );

				}
			}
		}
	}

	/**
	 *
	 * Adding the default menu into the wordpress menu
	 *
	 * @name wpswings_callback_function
	 * @since 1.0.0
	 */
	public function wpswings_welcome_callback_function() {
		include TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-welcome.php';
	}

	/**
	 * Removing default submenu of parent menu in backend dashboard
	 *
	 * @since 1.0.0
	 */
	public function wps_tofw_remove_default_submenu() {
		global $submenu;
		if ( is_array( $submenu ) && array_key_exists( 'wps-plugins', $submenu ) ) {
			if ( isset( $submenu['wps-plugins'][0] ) ) {
				unset( $submenu['wps-plugins'][0] );
			}
		}
	}


	/**
	 * Track Orders For Woocommerce tofw_admin_submenu_page.
	 *
	 * @since 1.0.0
	 * @param array $menus Marketplace menus.
	 */
	public function tofw_admin_submenu_page( $menus = array() ) {
		$menus[] = array(
			'name'            => __( 'Track Orders For Woocommerce', 'track-orders-for-woocommerce' ),
			'slug'            => 'track_orders_for_woocommerce_menu',
			'menu_link'       => 'track_orders_for_woocommerce_menu',
			'instance'        => $this,
			'function'        => 'tofw_options_menu_html',
		);
		return $menus;
	}

	/**
	 * Track Orders For Woocommerce wps_plugins_listing_page.
	 *
	 * @since 1.0.0
	 */
	public function wps_plugins_listing_page() {
		$active_marketplaces =
		// desc - filter for trial.
		apply_filters( 'wps_adds_plugins_menus_array', array() );
		if ( is_array( $active_marketplaces ) && ! empty( $active_marketplaces ) ) {
			include TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/welcome.php';
		}
	}

	/**
	 * Track Orders For Woocommerce admin menu page.
	 *
	 * @since 1.0.0
	 */
	public function tofw_options_menu_html() {

		include_once TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-admin-dashboard.php';
	}

	/**
	 * Developer_admin_hooks_listing
	 *
	 * @return array
	 */
	public function wps_developer_admin_hooks_listing() {
		$admin_hooks = array();
		$val         = self::wps_developer_hooks_function( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/' );
		if ( ! empty( $val['hooks'] ) ) {
			$admin_hooks[] = $val['hooks'];
			unset( $val['hooks'] );
		}
		$data = array();
		foreach ( $val['files'] as $v ) {
			if ( 'css' !== $v && 'js' !== $v && 'images' !== $v ) {
				$helo = self::wps_developer_hooks_function( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/' . $v . '/' );
				if ( ! empty( $helo['hooks'] ) ) {
					$admin_hooks[] = $helo['hooks'];
					unset( $helo['hooks'] );
				}
				if ( ! empty( $helo ) ) {
					$data[] = $helo;
				}
			}
		}
		return $admin_hooks;
	}

	/**
	 * Developer_public_hooks_listing
	 */
	public function wps_developer_public_hooks_listing() {

		$public_hooks = array();
		$val          = self::wps_developer_hooks_function( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'public/' );

		if ( ! empty( $val['hooks'] ) ) {
			$public_hooks[] = $val['hooks'];
			unset( $val['hooks'] );
		}
		$data = array();
		foreach ( $val['files'] as $v ) {
			if ( 'css' !== $v && 'js' !== $v && 'images' !== $v ) {
				$helo = self::wps_developer_hooks_function( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'public/' . $v . '/' );
				if ( ! empty( $helo['hooks'] ) ) {
					$public_hooks[] = $helo['hooks'];
					unset( $helo['hooks'] );
				}
				if ( ! empty( $helo ) ) {
					$data[] = $helo;
				}
			}
		}
		return $public_hooks;
	}
	/**
	 * Developer_hooks_function
	 *
	 * @param mixed $path is the path of the file.
	 */
	public function wps_developer_hooks_function( $path ) {
		$all_hooks = array();
		$scan      = scandir( $path );
		$response  = array();
		foreach ( $scan as $file ) {
			if ( strpos( $file, '.php' ) ) {
				$myfile = file( $path . $file );
				foreach ( $myfile as $key => $lines ) {
					if ( preg_match( '/do_action/i', $lines ) && ! strpos( $lines, 'str_replace' ) && ! strpos( $lines, 'preg_match' ) ) {
						$all_hooks[ $key ]['action_hook'] = $lines;
						$all_hooks[ $key ]['desc']        = $myfile[ $key - 1 ];
					}
					if ( preg_match( '/apply_filters/i', $lines ) && ! strpos( $lines, 'str_replace' ) && ! strpos( $lines, 'preg_match' ) ) {
						$all_hooks[ $key ]['filter_hook'] = $lines;
						$all_hooks[ $key ]['desc']        = $myfile[ $key - 1 ];
					}
				}
			} elseif ( strpos( $file, '.' ) == '' && strpos( $file, '.' ) !== 0 ) {
				$response['files'][] = $file;
			}
		}
		if ( ! empty( $all_hooks ) ) {
			$response['hooks'] = $all_hooks;
		}
		return $response;
	}

	/**
	 * Track Orders For Woocommerce admin menu page.
	 *
	 * @since 1.0.0
	 * @param array $tofw_settings_general Settings fields.
	 */
	public function tofw_admin_general_settings_page( $tofw_settings_general ) {
		$tofw_settings_general = array(
			array(
				'title' => __( 'Enable Plugin', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Start the Functionality.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_is_plugin_enable',
				'value' => get_option( 'wps_tofw_is_plugin_enable' ),
				'class' => 'wps_tofw_is_plugin_enable',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable Order Tracking Using Order Id Only', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'In Default Case, Guest User Can Track Order Using Email and Order Id.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_enable_track_order_using_order_id',
				'value' => get_option( 'wps_tofw_enable_track_order_using_order_id' ),
				'class' => 'wps_tofw_enable_track_order_using_order_id',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable Logged-in User to EXPORT ORDER', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Logged-In User Can Export Order From my-account->order Section', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_enable_login_export',
				'value' => get_option( 'wps_tofw_enable_login_export' ),
				'class' => 'wps_tofw_enable_login_export',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable Guest User to EXPORT ORDER', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Guest User Can Export Order From Guest Tracking Page', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_enable_guest_export',
				'value' => get_option( 'wps_tofw_enable_guest_export' ),
				'class' => 'wps_tofw_enable_guest_export',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable Use of Icon for Order Status', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Enable This To Show Icon Instead of Text for Order Status in Order Table.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_enable_use_of_icon',
				'value' => get_option( 'wps_tofw_enable_use_of_icon' ),
				'class' => 'wps_tofw_enable_use_of_icon',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable E-mail Notification Feature', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Send the E-Mail Notification to the Customer on Changing Order Status', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_email_notifier',
				'value' => get_option( 'wps_tofw_email_notifier' ),
				'class' => 'wps_tofw_email_notifier',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),

		);

		$tofw_settings_general =
		/**
		 * Filter is for returning something.
		 *
		 * @since 1.0.0
		 */
		apply_filters( 'tofw_general_settings_array_filter', $tofw_settings_general );
		$tofw_settings_general[] = array(
			'type'        => 'button',
			'id'          => 'wps_tofw_general_settings_save',
			'button_text' => __( 'Save Settings', 'track-orders-for-woocommerce' ),
			'class'       => 'wps_tofw_general_settings_save',
			'name'        => 'wps_tofw_general_settings_save',
		);
		return $tofw_settings_general;
	}

	/**
	 * Track Orders For Woocommerce admin menu page.
	 *
	 * @since 1.0.0
	 * @param array $tofw_track_order_settings fields.
	 */
	public function tofw_track_order_settings_page( $tofw_track_order_settings ) {

		$custom_order_status = get_option( 'wps_tofw_new_custom_order_status', array() );
		$selected_order_status = get_option( 'tofw_selected_custom_order_status' );
		$new_order_statues = array();
		$order_status = array(
			'wc-packed' => __( 'Order Packed', 'track-orders-for-woocommerce' ),
			'wc-dispatched' => __( 'Order Dispatched', 'track-orders-for-woocommerce' ),
			'wc-shipped' => __( 'Order Shipped', 'track-orders-for-woocommerce' ),
		);
		if ( is_array( $custom_order_status ) && ! empty( $custom_order_status ) ) {
			foreach ( $custom_order_status as $key => $value ) {
				foreach ( $value as $status_key => $status_value ) {
					$order_status[ 'wc-' . $status_key ] = $status_value;
				}
			}
		}
		if ( is_array( $selected_order_status ) && ! empty( $selected_order_status ) ) {
			foreach ( $selected_order_status as $key => $value ) {
				if ( key_exists( $value, $order_status ) ) {
					$new_order_statues[ $value ] = $order_status[ $value ];
				}
			}
		}
		$wc_get_order_statuses = wc_get_order_statuses();
		unset( $wc_get_order_statuses['wc-completed'] );
		$order_status = array_merge( $new_order_statues, $wc_get_order_statuses );

		$tofw_track_order_settings = array(

			array(
				'title' => __( 'Enable Track Orders Feature', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'id'    => 'tofw_enable_track_order',
				'value' => get_option( 'tofw_enable_track_order' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),

			array(
				'title' => __( 'Enable Use Of Custom Order Status', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'id'    => 'tofw_enable_use_custom_status',
				'value' => get_option( 'tofw_enable_use_custom_status' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),

			array(
				'title' => __( 'hidden_status', 'track-orders-for-woocommerce' ),
				'type'  => 'multiselect',
				'description'  => __( 'Select Custom status to enhance tracking.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_order_status_in_hidden',
				'value' => '',
				'class' => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => '',
				'options' => $order_status,
			),

			array(
				'title' => __( 'Approval', 'track-orders-for-woocommerce' ),
				'type'  => 'multiselect',
				'description'  => __( 'Select Custom Order Status for the Approval Stage.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_order_status_in_approval',
				'value' => get_option( 'wps_tofw_order_status_in_approval' ),
				'class' => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => '',
				'options' => $order_status,
			),
			array(
				'title' => __( 'Processing', 'track-orders-for-woocommerce' ),
				'type'  => 'multiselect',
				'description'  => __( 'Select Custom Order Status for the Processing Stage.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_order_status_in_processing',
				'value' => get_option( 'wps_tofw_order_status_in_processing' ),
				'class' => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => '',
				'options' => $order_status,
			),
			array(
				'title' => __( 'Shipping', 'track-orders-for-woocommerce' ),
				'type'  => 'multiselect',
				'description'  => __( 'Select Custom Order Status for the Shipping Stage.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_order_status_in_shipping',
				'value' => get_option( 'wps_tofw_order_status_in_shipping' ),
				'class' => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => '',
				'options' => $order_status,
			),

		);

		$tofw_track_order_settings =
		/**
		 * Filter is for returning something.
		 *
		 * @since 1.0.0
		 */
		apply_filters( 'tofw_track_order_settings_array_filter', $tofw_track_order_settings );

		$tofw_track_order_settings[] = array(
			'type'  => 'button',
			'id'    => 'wps_tofw_track-order_setting_save',
			'button_text' => __( 'Save Settings', 'track-orders-for-woocommerce' ),
			'class' => 'tofw-button-class',
		);
		return $tofw_track_order_settings;
	}

	/**
	 * Function for custom order status setting.
	 *
	 * @param array $tofw_custom_order_status_settings contains array of settings.
	 * @return array
	 */
	public function tofw_custom_order_status_setting_page( $tofw_custom_order_status_settings ) {
		$custom_order_status = get_option( 'wps_tofw_new_custom_order_status', array() );
		$order_status = array(
			'wc-packed' => __( 'Order Packed', 'track-orders-for-woocommerce' ),
			'wc-dispatched' => __( 'Order Dispatched', 'track-orders-for-woocommerce' ),
			'wc-shipped' => __( 'Order Shipped', 'track-orders-for-woocommerce' ),
		);
		if ( is_array( $custom_order_status ) && ! empty( $custom_order_status ) ) {
			foreach ( $custom_order_status as $key => $value ) {
				foreach ( $value as $status_key => $status_value ) {
					$order_status[ 'wc-' . $status_key ] = $status_value;
				}
			}
		}

		$tofw_custom_order_status_settings = array(
			array(
				'title' => __( 'Custom Order Statuses', 'track-orders-for-woocommerce' ),
				'type'  => 'multiselect',
				'description'  => __( 'Select Custom Statuses to Use in Tracking.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_selected_custom_order_status',
				'value' => get_option( 'tofw_selected_custom_order_status' ),
				'class' => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => '',
				'options' => $order_status,
			),
			array(
				'title' => '',
				'type'  => 'custom_status',
				'description'  => '',
				'id'    => 'custom_staus',
				'value' => '',
				'class' => '',
				'placeholder' => '',
				'options' => '',
			),

		);

		$tofw_custom_order_status_settings =
		/**
		 * Filter is for returning something.
		 *
		 * @since 1.0.0
		 */
		apply_filters( 'tofw_custom_order_status_array_filter', $tofw_custom_order_status_settings );

		$tofw_custom_order_status_settings[] = array(
			'type'  => 'button',
			'id'    => 'wps_tofw_custom_order_status_setting_save',
			'button_text' => __( 'Save Settings', 'track-orders-for-woocommerce' ),
			'class' => 'tofw-button-class',
		);
		return $tofw_custom_order_status_settings;

	}

	/**
	 * Function for track order settings.
	 *
	 * @param array $tofw_track_order_gmap_settings contains array.
	 * @return array
	 */
	public function tofw_track_order_gmap_settings_callback( $tofw_track_order_gmap_settings ) {
		$tofw_track_order_gmap_settings = array(
			array(
				'title' => __( 'Enable Google Map For Tracking', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Track Your Order With Google Map ApI.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_trackorder_with_google_map',
				'value' => get_option( 'wps_tofw_trackorder_with_google_map' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enter Google Map API Key', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => '',
				'id'    => 'wps_tofw_google_api_key',
				'value' => get_option( 'wps_tofw_google_api_key' ),
				'class' => '',
				'style' => 'width:10em;',

			),
			array(
				'title' => __( 'Enter Order Production House Address', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => '',
				'id'    => 'wps_tofw_order_production_address',
				'value' => get_option( 'wps_tofw_order_production_address' ),
				'class' => '',
				'style' => 'width:10em;',

			),
			array(
				'title' => __( 'Enter Addresses From Where Your Order Has Gone Through', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => '',
				'id'    => 'wps_tofw_track_order_addresses',
				'value' => '',
				'class' => '',
				'style' => 'width:10em;',

			),
			array(
				'type'  => 'button',
				'id'    => 'wps_tofw_add_address',
				'button_text' => __( 'Add Address', 'track-orders-for-woocommerce' ),
				'class' => 'tofw-button-class',
			),

			array(
				'title' => __( 'Selected Addresses', 'track-orders-for-woocommerce' ),
				'type'  => 'multiselect',
				'description'  => __( 'Select Your Hub Point Addresses From Where Your Order Has Gone Through.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_selected_address',
				'value' => get_option( 'wps_tofw_selected_address' ),
				'class' => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => 'Select Your Hubpoint Addresses',
				'options' => get_option( 'wps_tofw_old_addresses' ),
			),
		);

		$tofw_track_order_gmap_settings =
		/**
		 * Filter is for returning something.
		 *
		 * @since 1.0.0
		 */
		apply_filters( 'tofw_track_order_gmap_settings_array_filter', $tofw_track_order_gmap_settings );

		$tofw_track_order_gmap_settings[] = array(
			'type'  => 'button',
			'id'    => 'wps_tofw_track_order_gmap_settings_save',
			'button_text' => __( 'Save Settings', 'track-orders-for-woocommerce' ),
			'class' => 'tofw-button-class',
		);
		return $tofw_track_order_gmap_settings;
	}

	/**
	 * Function for shipping services settings.
	 *
	 * @param [type] $tofw_shipping_services_settings is an array.
	 * @return array
	 */
	public function tofw_shipping_services_settings_callback( $tofw_shipping_services_settings ) {

		$tofw_shipping_services_settings = array(
			array(
				'title' => __( 'Enable Third Party Tracking API', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => '',
				'id'    => 'wps_tofw_enable_third_party_tracking_api',
				'value' => get_option( 'wps_tofw_enable_third_party_tracking_api' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enter The Shop Address ', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => '',
				'id'    => 'wps_tofw_shop_address',
				'value' => get_option( 'wps_tofw_shop_address' ),
				'class' => '',
				'style' => 'width:10em;',

			),
			array(
				'title' => __( 'Enable FedEx Shipment Tracking', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => '',
				'id'    => 'wps_tofw_enable_track_order_using_api',
				'value' => get_option( 'wps_tofw_enable_track_order_using_api' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enter Your FedEx User Key  ', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => '',
				'id'    => 'wps_fedex_userkey',
				'value' => get_option( 'wps_fedex_userkey' ),
				'class' => '',
				'style' => 'width:10em;',

			),
			array(
				'title' => __( 'Enter Your FedEx User Password   ', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => '',
				'id'    => 'wps_fedex_userpassword',
				'value' => get_option( 'wps_fedex_userpassword' ),
				'class' => '',
				'style' => 'width:10em;',

			),
			array(
				'title' => __( 'Enter Your FedEx Account Number', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => '',
				'id'    => 'wps_fedex_account_number',
				'value' => get_option( 'wps_fedex_account_number' ),
				'class' => '',
				'style' => 'width:10em;',

			),
			array(
				'title' => __( 'Enter Your FedEx Meter Number    ', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => '',
				'id'    => 'wps_fedex_meter_number',
				'value' => get_option( 'wps_fedex_meter_number' ),
				'class' => '',
				'style' => 'width:10em;',

			),
		);

		$tofw_shipping_services_settings =
		/**
		 * Filter is for returning something.
		 *
		 * @since 1.0.0
		 */
		apply_filters( 'tofw_shipping_services_settings_array_filter', $tofw_shipping_services_settings );

		$tofw_shipping_services_settings[] = array(
			'type'  => 'button',
			'id'    => 'wps_tofw_shipping_services_settings_save',
			'button_text' => __( 'Save Settings', 'track-orders-for-woocommerce' ),
			'class' => 'tofw-button-class',
		);
		return $tofw_shipping_services_settings;
	}


	/**
	 * Track Orders For Woocommerce save tab settings.
	 *
	 * @since 1.0.0
	 */
	public function tofw_admin_save_tab_settings() {
		global $wps_tofw_obj;
		$wps_settings_save_progress = false;
		if ( wp_doing_ajax() ) {
			return;
		}
		if ( ! isset( $_POST['wps_tabs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wps_tabs_nonce'] ) ), 'admin_save_data' ) ) {
			return;
		}

		if ( isset( $_POST['tofw_button_demo'] ) ) {

			$screen = get_current_screen();

			if ( isset( $screen->id ) && 'wp-swings_page_home' === $screen->id || 'wpswings_page_home' === $screen->id ) {

				$enable_tracking = ! empty( $_POST['tofw_enable_tracking'] ) ? sanitize_text_field( wp_unslash( $_POST['tofw_enable_tracking'] ) ) : '';
				update_option( 'tofw_enable_tracking', $enable_tracking );
			}
		}
		if ( isset( $_POST['wps_tofw_general_settings_save'] ) ) {
			$wps_msp_gen_flag     = false;
			$tofw_genaral_settings =
			// desc - filter for trial.
			apply_filters( 'tofw_general_settings_array', array() );
			$wps_settings_save_progress = true;
		}
		if ( isset( $_POST['wps_tofw_track-order_setting_save'] ) ) {
			$wps_msp_gen_flag     = false;
			$tofw_genaral_settings =
			// desc - filter for trial.
			apply_filters( 'tofw_track_order_array', array() );
			$wps_settings_save_progress = true;
		}
		if ( isset( $_POST['wps_tofw_custom_order_status_setting_save'] ) ) {
			$wps_msp_gen_flag     = false;
			$tofw_genaral_settings =
			// desc - filter for trial.
			apply_filters( 'tofw_custom_order_status_array', array() );
			$wps_settings_save_progress = true;
		}
		if ( isset( $_POST['wps_tofw_track_order_gmap_settings_save'] ) ) {
			$wps_msp_gen_flag     = false;
			$tofw_genaral_settings =
			// desc - filter for trial.
			apply_filters( 'tofw_track_order_gmap_settings_array', array() );
			$wps_settings_save_progress = true;
		}
		if ( isset( $_POST['wps_tofw_shipping_services_settings_save'] ) ) {
			$wps_msp_gen_flag     = false;
			$tofw_genaral_settings =
			// desc - filter for trial.
			apply_filters( 'tofw_shipping_services_settings_array', array() );
			$wps_settings_save_progress = true;
		}
		if ( isset( $_POST['wps_tofp_enhance_tracking_save'] ) ) {
			$wps_msp_gen_flag     = false;
			$tofw_genaral_settings =
			// desc - filter for trial.
			apply_filters( 'wps_tofp_enhance_tracking__array', array() );
			$wps_settings_save_progress = true;
		}

		if ( $wps_settings_save_progress ) {

			$tofw_button_index = array_search( 'submit', array_column( $tofw_genaral_settings, 'type' ) );
			if ( isset( $tofw_button_index ) && ( null == $tofw_button_index || '' == $tofw_button_index ) ) {
				$tofw_button_index = array_search( 'button', array_column( $tofw_genaral_settings, 'type' ) );
			}
			if ( isset( $tofw_button_index ) && '' !== $tofw_button_index ) {
				unset( $tofw_genaral_settings[ $tofw_button_index ] );
				if ( is_array( $tofw_genaral_settings ) && ! empty( $tofw_genaral_settings ) ) {
					foreach ( $tofw_genaral_settings as $tofw_genaral_setting ) {
						if ( isset( $tofw_genaral_setting['id'] ) && '' !== $tofw_genaral_setting['id'] ) {
							if ( isset( $_POST[ $tofw_genaral_setting['id'] ] ) ) {
								update_option( $tofw_genaral_setting['id'], is_array( $_POST[ $tofw_genaral_setting['id'] ] ) ? map_deep( wp_unslash( $_POST[ $tofw_genaral_setting['id'] ] ), 'sanitize_text_field' ) : sanitize_text_field( wp_unslash( $_POST[ $tofw_genaral_setting['id'] ] ) ) );
							} else {
								update_option( $tofw_genaral_setting['id'], '' );
							}
						} else {
							$wps_msp_gen_flag = true;
						}
					}
				}
				if ( $wps_msp_gen_flag ) {
					$wps_msp_error_text = esc_html__( 'Id of some field is missing', 'track-orders-for-woocommerce' );
					$wps_tofw_obj->wps_std_plug_admin_notice( $wps_msp_error_text, 'error' );
				} else {
					$wps_msp_error_text = esc_html__( 'Settings saved !', 'track-orders-for-woocommerce' );
					$wps_tofw_obj->wps_std_plug_admin_notice( $wps_msp_error_text, 'success' );
				}
			}
		}
	}

	/**
	 * Sanitation for an array
	 *
	 * @param $array $wps_input_array is the array data.
	 *
	 * @return array
	 */
	public function wps_sanitize_array( $wps_input_array ) {
		foreach ( $wps_input_array as $key => $value ) {
			$key   = sanitize_text_field( $key );
			$value = sanitize_text_field( $value );
		}
		return $wps_input_array;
	}



	/**
	 * Function for ajax callback.
	 *
	 * @return void
	 */
	public function wps_tofw_create_custom_order_status_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$create_custom_order_status = array();
		$value = array();
		$custom_order_image_url = array();
		$wps_image_url = array();
		$value = get_option( 'wps_tofw_new_custom_order_status', false );
		$custom_order_image_url = get_option( 'wps_tofw_new_custom_order_image', false );
		if ( is_array( $value ) && ! empty( $value ) ) {
			$create_custom_order_status = isset( $_POST['wps_tofw_new_role_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_new_role_name'] ) ) : '';
			$create_custom_order_image_url = isset( $_POST['wps_custom_order_image_url'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_custom_order_image_url'] ) ) : '';
			$key_custom_order_status = str_replace( ' ', '', $create_custom_order_status );
			$key_custom_order_status = strtolower( $key_custom_order_status );
			$value[] = array( $key_custom_order_status => $create_custom_order_status );
			$custom_order_image_url[ $key_custom_order_status ] = $create_custom_order_image_url;

			update_option( 'wps_tofw_new_custom_order_status', $value );
			update_option( 'wps_tofw_new_custom_order_image', $custom_order_image_url );

		} else {

			$create_custom_order_status = isset( $_POST['wps_tofw_new_role_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_new_role_name'] ) ) : '';
			$create_custom_order_image_url = isset( $_POST['wps_custom_order_image_url'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_custom_order_image_url'] ) ) : '';
			$key_custom_order_status = str_replace( ' ', '', $create_custom_order_status );
			$key_custom_order_status = strtolower( $key_custom_order_status );
			$value[] = array( $key_custom_order_status => $create_custom_order_status );
			$custom_order_image_url[ $key_custom_order_status ] = $create_custom_order_image_url;

			update_option( 'wps_tofw_new_custom_order_status', $value );
			update_option( 'wps_tofw_new_custom_order_image', $custom_order_image_url );
		}

		esc_html_e( 'success', 'track-orders-for-woocommerce' );
		wp_die();
	}

	/**
	 * This function delete the Custom order status on the backend
	 *
	 * @link http://www.wpswings.com/
	 */
	public function wps_tofw_delete_custom_order_status_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$wps_tofw_old_selected_statuses = get_option( 'wps_tofw_new_settings_custom_statuses_for_order_tracking', false );
		$wps_custom_action = isset( $_POST['wps_custom_action'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_custom_action'] ) ) : '';
		$wps_custom_key = isset( $_POST['wps_custom_key'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_custom_key'] ) ) : '';
		if ( isset( $wps_custom_key ) && ! empty( $wps_custom_key ) ) {
			$custom_order_status_exist = get_option( 'wps_tofw_new_custom_order_status', array() );
			if ( is_array( $custom_order_status_exist ) && ! empty( $custom_order_status_exist ) ) {
				foreach ( $custom_order_status_exist as $key => $value ) {
					foreach ( $value as $wps_order_key => $wps_order_status ) {
						if ( $wps_order_key === $wps_custom_key ) {
							unset( $custom_order_status_exist[ $key ] );

						}
					}
				}
				update_option( 'wps_tofw_new_custom_order_status', $custom_order_status_exist );

				if ( is_array( $wps_tofw_old_selected_statuses ) && ! empty( $wps_tofw_old_selected_statuses ) ) {
					foreach ( $wps_tofw_old_selected_statuses as $old_key => $old_value ) {
						if ( substr( $old_value, 3 ) == $wps_custom_key ) {
							unset( $wps_tofw_old_selected_statuses[ $old_key ] );
							update_option( 'wps_tofw_new_settings_custom_statuses_for_order_tracking', $wps_tofw_old_selected_statuses );
						}
					}
				}
				esc_html_e( 'success', 'track-orders-for-woocommerce' );
			} else {
				esc_html_e( 'failed', 'track-orders-for-woocommerce' );
			}

			wp_die();
		}
	}

	/**
	 * Function for ajax select template.
	 *
	 * @return void
	 */
	public function wps_selected_template_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$selected_template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '';
		update_option( 'wps_tofw_activated_template', $selected_template_name );
		esc_html_e( 'success', 'track-orders-for-woocommerce' );
		wp_die();
	}

	/**
	 * Function for ajax insert address.
	 *
	 * @return void
	 */
	public function wps_tofw_insert_address_for_tracking() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$wps_tofw_address_collections = isset( $_POST['wps_tofw_addresses'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_addresses'] ) ) : '';
		$wps_tofw_previous_address = get_option( 'wps_tofw_old_addresses', array() );
		if ( is_array( $wps_tofw_previous_address ) ) {

			$wps_tofw_previous_address[ 'wps_address_' . $wps_tofw_address_collections ] = $wps_tofw_address_collections;
			update_option( 'wps_tofw_old_addresses', $wps_tofw_previous_address );
		}

		$wps_tofw_address_array_value = get_option( 'wps_tofw_old_addresses', false );
		echo wp_json_encode( $wps_tofw_address_array_value );
		wp_die();
	}

	/**
	 * Function to add meta box.
	 *
	 * @return void
	 */
	public function wps_tofw_tracking_order_meta_box() {
		$wps_tofw_enable_track_order_feature = get_option( 'tofw_enable_track_order', 'no' );
		$wps_tofwp_enable_track_17track_feature = get_option( 'wps_tofwp_enable_17track_integration', 'no' );
		if ( 'on' != $wps_tofw_enable_track_order_feature ) {
			return;
		}

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$screen = wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';

			add_meta_box( 'wps_tofw_track_order', __( 'Enter Estimated Delivery Date', 'track-orders-for-woocommerce' ), array( $this, 'wps_tofw_track_order_metabox' ), $screen, 'side', 'high' );

			$wps_tofw_enable_track_order_api = get_option( 'wps_tofw_enable_third_party_tracking_api', 'no' );
			if ( 'on' == $wps_tofw_enable_track_order_api || 'on' == $wps_tofwp_enable_track_17track_feature ) {
				add_meta_box( 'wps_tofw_tracking_services', __( 'Select Service For Tracking Your Package', 'track-orders-for-woocommerce' ), array( $this, 'wps_tofw_track_order_services_metabox' ), $screen, 'side', 'high' );
			}
			$wps_tofw_google_map_setting = get_option( 'wps_tofw_trackorder_with_google_map', false );

			if ( 'on' == $wps_tofw_google_map_setting ) {
				add_meta_box( 'wps_tofw_custom_tracking_services', __( 'Select City When Your Package Reaches The Desire City', 'track-orders-for-woocommerce' ), array( $this, 'wps_tofw_track_order_custom_services_metabox' ), $screen, 'side', 'high' );
			}
		} else {
			add_meta_box( 'wps_tofw_track_order', __( 'Enter Estimated Delivery Date', 'track-orders-for-woocommerce' ), array( $this, 'wps_tofw_track_order_metabox' ), 'shop_order', 'side', 'high' );

			$wps_tofw_enable_track_order_api = get_option( 'wps_tofw_enable_third_party_tracking_api', 'no' );
			if ( 'on' == $wps_tofw_enable_track_order_api || 'on' == $wps_tofwp_enable_track_17track_feature ) {
				add_meta_box( 'wps_tofw_tracking_services', __( 'Select Service For Tracking Your Package', 'track-orders-for-woocommerce' ), array( $this, 'wps_tofw_track_order_services_metabox' ), 'shop_order', 'side' );
			}
			$wps_tofw_google_map_setting = get_option( 'wps_tofw_trackorder_with_google_map', false );

			if ( 'on' == $wps_tofw_google_map_setting ) {
				add_meta_box( 'wps_tofw_custom_tracking_services', __( 'Select City When Your Package Reaches The Desire City', 'track-orders-for-woocommerce' ), array( $this, 'wps_tofw_track_order_custom_services_metabox' ), 'shop_order', 'side' );
			}
		}
	}

	/**
	 * Callback function of service_metabox.
	 *
	 * @param object $post_or_order_object is the array data.
	 * @return void
	 */
	public function wps_tofw_track_order_custom_services_metabox( $post_or_order_object ) {
		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		if ( null != $order->get_id() ) {
			$order_id = $order->get_id();

			$wps_tofw_all_selected_cities = get_option( 'wps_tofw_selected_address', false );

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// HPOS usage is enabled.
				$wps_tofw_saved_selected_cities = $order->get_meta( 'wps_tofw_save_selected_city', true );
			} else {
				$wps_tofw_saved_selected_cities = get_post_meta( $order_id, 'wps_tofw_save_selected_city', true );
			}

			?>
			<div class="wps_tofw_shipping_service_wrapper">
				<select name="wps_tofw_custom_shipping_cities" id="wps_tofw_custom_shipping_cities">
					<option value="<?php esc_attr_e( 'none', 'track-orders-for-woocommerce' ); ?>"><?php esc_attr_e( 'Select shipping Cities', 'track-orders-for-woocommerce' ); ?></option>
					<?php
					if ( is_array( $wps_tofw_all_selected_cities ) && ! empty( $wps_tofw_all_selected_cities ) ) {
						foreach ( $wps_tofw_all_selected_cities as $custom_key => $custom_value ) {
							?>
							<option value="<?php echo esc_attr( $custom_value ); ?>" 
													  <?php
														if ( isset( $wps_tofw_saved_selected_cities ) && '' != $wps_tofw_saved_selected_cities && $custom_value == $wps_tofw_saved_selected_cities ) {
															echo 'selected';}
														?>
								><?php echo esc_html( str_replace( 'wps_address_', '', $custom_value ) ); ?></option>
							<?php
						}
					}
					?>
				</select>
				<input type="hidden" name="wps_tofw_custom_shipping_cities_nonce_name" value="<?php wp_create_nonce( 'wps_tofw_custom_shipping_cities_nonce' ); ?>">
			</div>
			<?php
		}
	}

	/**
	 * Function to add order meta.
	 *
	 * @param object $post_or_order_object is the array data.
	 * @return void
	 */
	public function wps_tofw_track_order_metabox( $post_or_order_object ) {
		$wps_tofw_enable_track_order_feature = get_option( 'tofw_enable_track_order', 'no' );
		if ( 'on' != $wps_tofw_enable_track_order_feature ) {
			return;
		}

		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		if ( null != $order->get_id() ) {
			$order_id = $order->get_id();

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// HPOS usage is enabled.
				$expected_delivery_date = $order->get_meta( 'wps_tofw_estimated_delivery_date', true );
				$expected_delivery_time = $order->get_meta( 'wps_tofw_estimated_delivery_time', true );
			} else {
				$expected_delivery_date = get_post_meta( $order_id, 'wps_tofw_estimated_delivery_date', true );
				$expected_delivery_time = get_post_meta( $order_id, 'wps_tofw_estimated_delivery_time', true );
			}

			?>
		<div class="wps_tofw_estimated_delivery_datails_wrapper">
			<input type="hidden" name="wps_tofw_delivery_nonce_name" value="<?php wp_create_nonce( 'wps_tofw_delivery_nonce' ); ?>">
			<label for="wps_tofw_est_delivery_date"><?php esc_html_e( 'Delivery Date', 'track-orders-for-woocommerce' ); ?></label>
			<input type="text" class="wps_tofw_est_delivery_date" id="wps_tofw_est_delivery_date" name="wps_tofw_est_delivery_date" value="<?php echo esc_attr( $expected_delivery_date ); ?>" placeholder="<?php esc_attr_e( 'Enter Delivery Date', 'track-orders-for-woocommerce' ); ?>"></input>
			<label for="wps_tofw_est_delivery_time"><?php esc_html_e( 'Delivery Time', 'track-orders-for-woocommerce' ); ?></label>				
			<input type="text" class="wps_tofw_est_delivery_time" name="wps_tofw_est_delivery_time" id="wps_tofw_est_delivery_time" value="<?php echo esc_attr( $expected_delivery_time ); ?>" placeholder="<?php esc_attr_e( 'Enter Delivery time', 'track-orders-for-woocommerce' ); ?>"></input>
		</div>
			<?php
		}
	}

	/**
	 * Function to add metabox.
	 *
	 * @param object $post_or_order_object is the array data.
	 * @return void
	 */
	public function wps_tofw_track_order_services_metabox( $post_or_order_object ) {
		$wps_tofw_enable_track_order_feature = get_option( 'wps_tofw_enable_third_party_tracking_api', 'no' );
		$wps_tofwp_enable_track_17track_feature = get_option( 'wps_tofwp_enable_17track_integration', 'no' );

		$order = ( $post_or_order_object instanceof WP_Post ) ? wc_get_order( $post_or_order_object->ID ) : $post_or_order_object;
		if ( null != $order->get_id() ) {
			$order_id = $order->get_id();

			$wps_tofw_fedex_tracking_enable = get_option( 'wps_tofw_enable_track_order_using_api', 'no' );
			if ( 'on' === $wps_tofw_fedex_tracking_enable ) {

				$wps_diffrent_shipping_services = array( 'fedex' => 'FedEx' );
			} else {
				$wps_diffrent_shipping_services = array();
			}

			/**
			 * Add different shipping services.
			 *
			 * @since 1.0.0
			 */
			$wps_diffrent_shipping_services = apply_filters( 'wps_tofw_add_diffrent_shipping_services', $wps_diffrent_shipping_services );

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// HPOS usage is enabled.
				$wps_tofw_track_id = $order->get_meta( 'wps_tofw_package_tracking_number', true );
				$selected_method = $order->get_meta( 'wps_tofw_selected_shipping_service', true );
			} else {
				$wps_tofw_track_id = get_post_meta( $order_id, 'wps_tofw_package_tracking_number', true );
				$selected_method = get_post_meta( $order_id, 'wps_tofw_selected_shipping_service', true );
			}

			if ( 'on' == $wps_tofw_enable_track_order_feature ) {
				?>
					
				<div class="wps_tofw_shipping_service_wrapper">
					<select name="wps_tofw_selected_shipping_services">
						<option><?php esc_html_e( '---Select shipping Services---', 'track-orders-for-woocommerce' ); ?></option>
					<?php
					if ( isset( $wps_diffrent_shipping_services ) && ! empty( $wps_diffrent_shipping_services ) ) {
						foreach ( $wps_diffrent_shipping_services as $key => $value ) {
							?>
							<option value="<?php echo esc_attr( $key ); ?>"
												  <?php
													if ( $key == $selected_method ) {
														echo 'selected'; }
													?>
								><?php echo esc_html( $value ); ?></option>
								<?php
						}
					}
					?>
				</select>
				<input type="hidden" name="wps_tofw_selected_shipping_services_nonce_name" value="<?php wp_create_nonce( 'wps_tofw_selected_shipping_services_nonce' ); ?>">
			</div>
			<div class="wps_tofw_ship_tracking_wrapper">
				<label for="wps_tofw_user_tracking_number"><?php esc_html_e( 'Tracking Number', 'track-orders-for-woocommerce' ); ?></label>
				<input type="text" name="wps_tofw_tracking_number" id="wps_tofw_tracking_number" value="<?php echo esc_attr( $wps_tofw_track_id ); ?>" placeholder="<?php esc_attr_e( 'Enter Tracking Number', 'track-orders-for-woocommerce' ); ?>"></input>
			</div>
					<?php
			} elseif ( 'on' == $wps_tofwp_enable_track_17track_feature ) {
				?>
				<div class="wps_tyo_ship_tracking_wrapper">
					<label for="wps_tyo_user_tracking_number"><?php esc_html_e( '17Track Number', 'track-orders-for-woocommerce' ); ?></label>
					<input type="text" name="wps_tofw_tracking_number" id="wps_tofw_tracking_number" value="<?php echo esc_attr( $wps_tofw_track_id ); ?>" placeholder="<?php esc_attr_e( 'Enter 17 Tracking Number', 'woocommerce-order-tracker' ); ?>"></input>
				</div>
				<?php
			}
		}

	}

	/**
	 * Function to save data.
	 *
	 * @param int    $order_id order id.
	 * @param object $order is the order object.
	 * @return void
	 */
	public function wps_tofw_save_delivery_date_meta( $order_id, $order ) {
		if ( isset( $order ) ) {
			$post_id = $order->id;
			$order_obj = wc_get_order( $order->id );
			$value_check = isset( $_POST['wps_tofw_delivery_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_delivery_nonce_name'] ) ) : '';
			wp_verify_nonce( $value_check, 'wps_tofw_delivery_nonce' );

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				if ( isset( $_POST['wps_tofw_est_delivery_date'] ) && sanitize_text_field( wp_unslash( $_POST['wps_tofw_est_delivery_date'] ) ) != '' ) {
					$order_obj->update_meta_data( 'wps_tofw_estimated_delivery_date', sanitize_text_field( wp_unslash( $_POST['wps_tofw_est_delivery_date'] ) ) );
					$order_obj->save();
				} else {
					$order_obj->update_meta_data( 'wps_tofw_estimated_delivery_date', false );
					$order_obj->save();
				}

				if ( isset( $_POST['wps_tofw_est_delivery_time'] ) && sanitize_text_field( wp_unslash( $_POST['wps_tofw_est_delivery_time'] ) ) != '' ) {

					$order_obj->update_meta_data( 'wps_tofw_estimated_delivery_time', sanitize_text_field( wp_unslash( $_POST['wps_tofw_est_delivery_time'] ) ) );
					$order_obj->save();
				} else {
					$order_obj->update_meta_data( 'wps_tofw_estimated_delivery_time', false );
					$order_obj->save();}
			} else {

				if ( isset( $_POST['wps_tofw_est_delivery_date'] ) && sanitize_text_field( wp_unslash( $_POST['wps_tofw_est_delivery_date'] ) ) != '' ) {
					update_post_meta( $post_id, 'wps_tofw_estimated_delivery_date', sanitize_text_field( wp_unslash( $_POST['wps_tofw_est_delivery_date'] ) ) );
				} else {
					update_post_meta( $post_id, 'wps_tofw_estimated_delivery_date', false );
				}

				if ( isset( $_POST['wps_tofw_est_delivery_time'] ) && sanitize_text_field( wp_unslash( $_POST['wps_tofw_est_delivery_time'] ) ) != '' ) {
					update_post_meta( $post_id, 'wps_tofw_estimated_delivery_time', sanitize_text_field( wp_unslash( $_POST['wps_tofw_est_delivery_time'] ) ) );
				} else {
					update_post_meta( $post_id, 'wps_tofw_estimated_delivery_time', false );
				}
			}
		}
	}

	/**
	 * Function to save service meta.
	 *
	 * @param int    $order_id order id.
	 * @param object $order is the order object.
	 * @return void
	 */
	public function wps_tofw_save_shipping_services_meta( $order_id, $order ) {
		if ( isset( $order ) ) {
			$post_id = $order->id;
			$order_obj = wc_get_order( $order->id );
			$value_check = isset( $_POST['wps_tofw_selected_shipping_services_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_selected_shipping_services_nonce_name'] ) ) : '';
			wp_verify_nonce( $value_check, 'wps_tofw_selected_shipping_services_nonce' );
			if ( isset( $_POST['wps_tofw_selected_shipping_services'] ) && isset( $_POST['wps_tofw_tracking_number'] ) && sanitize_text_field( wp_unslash( $_POST['wps_tofw_tracking_number'] ) ) != '' && ! empty( sanitize_text_field( wp_unslash( $_POST['wps_tofw_selected_shipping_services'] ) ) ) ) {

				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$order_obj->update_meta_data( 'wps_tofw_selected_shipping_service', sanitize_text_field( wp_unslash( $_POST['wps_tofw_selected_shipping_services'] ) ) );
					$order_obj->update_meta_data( 'wps_tofw_package_tracking_number', sanitize_text_field( wp_unslash( $_POST['wps_tofw_tracking_number'] ) ) );
					$order_obj->save();
				} else {
					update_post_meta( $post_id, 'wps_tofw_selected_shipping_service', sanitize_text_field( wp_unslash( $_POST['wps_tofw_selected_shipping_services'] ) ) );
					update_post_meta( $post_id, 'wps_tofw_package_tracking_number', sanitize_text_field( wp_unslash( $_POST['wps_tofw_tracking_number'] ) ) );
				}

				$headers = array();
				$order = wc_get_order( $post_id );
				$headers[] = 'Content-Type: text/html; charset=UTF-8';
				$wps_tracking_url = get_post_meta( $order_id, 'wps_tofw_enhanced_order_company', true );
				$wps_tracking_number = get_post_meta( $order_id, 'wps_tofw_enhanced_tracking_no', true );
				if ( '3.0.0' > WC()->version ) {
					$fname = get_post_meta( $post_id, '_billing_first_name', true );
					$lname = get_post_meta( $post_id, '_billing_last_name', true );
					$to = get_post_meta( $post_id, '_billing_email', true );
				} else {
					$wps_all_data = $order->get_data();
					$billing_address = $wps_all_data['billing'];
					$shipping_address = $wps_all_data['shipping'];
					$to = $billing_address['email'];
				}
				$subject = __( 'Your Order Status for Order #', 'track-orders-for-woocommerce' ) . $post_id;
				$message = __( 'Your Order Status is ', 'track-orders-for-woocommerce' ) . $statuses[ $new_status ];
				$mail_header = __( 'Current Order Status is 1', 'track-orders-for-woocommerce' ) . $statuses[ $new_status ];
				$mail_footer = '';
				$message = '<html>
				<body>
					<style>
						body {
							box-shadow: 2px 2px 10px #ccc;
							color: #767676;
							font-family: Arial,sans-serif;
							margin: 80px auto;
							max-width: 700px;
							padding-bottom: 30px;
							width: 100%;
						}

						h2 {
							font-size: 30px;
							margin-top: 0;
							color: #fff;
							padding: 40px;
							background-color: #557da1;
						}

						h4 {
							color: #557da1;
							font-size: 20px;
							margin-bottom: 10px;
						}

						.content {
							padding: 0 40px;
						}

						.Customer-detail ul li p {
							margin: 0;
						}

						.details .Shipping-detail {
							width: 40%;
							float: right;
						}

						.details .Billing-detail {
							width: 60%;
							float: left;
						}

						.details .Shipping-detail ul li,.details .Billing-detail ul li {
							list-style-type: none;
							margin: 0;
						}

						.details .Billing-detail ul,.details .Shipping-detail ul {
							margin: 0;
							padding: 0;
						}

						.clear {
							clear: both;
						}

						table,td,th {
							border: 2px solid #ccc;
							padding: 15px;
							text-align: left;
						}

						table {
							border-collapse: collapse;
							width: 100%;
						}

						.info {
							display: inline-block;
						}

						.bold {
							font-weight: bold;
						}

						.footer {
							margin-top: 30px;
							text-align: center;
							color: #99B1D8;
							font-size: 12px;
						}
						dl.variation dd {
							font-size: 12px;
							margin: 0;
						}
					</style>

					<div style="padding: 36px 48px; background-color:#557DA1;color: #fff; font-size: 30px; font-weight: 300; font-family:helvetica;" class="header">
						' . $mail_header . '
					</div>		

					<div class="content">

						<div class="Order">
							<h4>Order #' . $post_id . '</h4>
							<table>
								<tbody>
									<tr>
										<th>' . __( 'Order Id', 'track-orders-for-woocommerce' ) . '</th>
										<th>' . __( 'Tracking Number ', 'track-orders-for-woocommerce' ) . '</th>
									</tr>';
									$wps_user_tracking_number = get_post_meta( $post_id, 'wps_tofw_package_tracking_number', true );
									$message .= '<tr>
									<td>' . $wps_user_tracking_number . '</td>
								</tr>';
								$message .= '</tbody>
							</table>
							<div>
								<a href=' . $wps_tracking_url . '>' . $wps_tracking_number . '</a>
							</div>
						</div>
					</div>
					<div style="text-align: center; padding: 10px;" class="footer">
						' . $mail_footer . '
					</div>
				</body>
				</html>';

				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$wps_mail_already_send = $order_obj->get_meta( 'wps_tofw_tracking_id_sent', true );
				} else {
					$wps_mail_already_send = get_post_meta( $post_id, 'wps_tofw_tracking_id_sent', true );
				}

				if ( 1 != $wps_mail_already_send ) {
					wc_mail( $to, $subject, $message, $headers );

					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$order_obj->update_meta_data( 'wps_tofw_tracking_id_sent', 1 );
					} else {
						update_post_meta( $post_id, 'wps_tofw_tracking_id_sent', 1 );
					}
				}
			} elseif ( isset( $_POST['wps_tofw_tracking_number'] ) && '' != $_POST['wps_tofw_tracking_number'] ) {

				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$order_obj->update_meta_data( 'wps_tofw_package_tracking_number', sanitize_text_field( wp_unslash( $_POST['wps_tofw_tracking_number'] ) ) );
					$order_obj->save();
				} else {
					update_post_meta( $post_id, 'wps_tofw_package_tracking_number', sanitize_text_field( wp_unslash( $_POST['wps_tofw_tracking_number'] ) ) );
				}
			} else {

				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					$order_obj->update_meta_data( 'wps_tofw_selected_shipping_service', sanitize_text_field( wp_unslash( $_POST['wps_tofw_selected_shipping_services'] ) ) );
					$order_obj->update_meta_data( 'wps_tofw_package_tracking_number', sanitize_text_field( wp_unslash( $_POST['wps_tofw_tracking_number'] ) ) );
					$order_obj->save();
				} else {
					update_post_meta( $post_id, 'wps_tofw_selected_shipping_service', '' );
					update_post_meta( $post_id, 'wps_tofw_package_tracking_number', '' );
				}
			}
		}

	}

	/**
	 * Function to save.
	 *
	 *  @param int    $order_id order id.
	 *  @param object $order is the order object.
	 * @return void
	 */
	public function wps_tofw_save_custom_shipping_cities_meta( $order_id, $order ) {
		if ( isset( $order ) ) {
			$order_obj = wc_get_order( $order->id );
			if ( isset( $order ) && ! empty( $order ) ) {

				$orderdata = $order_obj->get_data();
				$order_modified_date = $orderdata['date_modified'];
				$converted_order_modified_date = date_i18n( 'F d, Y g:i a', strtotime( $order_modified_date ) );
				$current_order_status = $order->get_status();

				$wps_tofw_all_selected_cities = get_option( 'wps_tofw_old_addresses', false );

				if ( is_array( get_post_meta( $order->id, 'wps_tofw_track_custom_cities', true ) ) ) {
					$wps_tofw_previous_saved_cities = get_post_meta( $order->id, 'wps_tofw_track_custom_cities', true );
				} else {
					$wps_tofw_previous_saved_cities = array();
				}

				if ( is_array( get_post_meta( $order->id, 'wps_tofw_custom_change_time', true ) ) ) {
					$wps_tofw_previous_saved_changed_time = get_post_meta( $order->id, 'wps_tofw_custom_change_time', true );
				} else {
					$wps_tofw_previous_saved_changed_time = array();
				}

				$value_check = isset( $_POST['wps_tofw_custom_shipping_cities_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_custom_shipping_cities_nonce_name'] ) ) : '';
				wp_verify_nonce( $value_check, 'wps_tofw_custom_shipping_cities_nonce' );
				if ( isset( $_POST['wps_tofw_custom_shipping_cities'] ) && sanitize_text_field( wp_unslash( $_POST['wps_tofw_custom_shipping_cities'] ) ) != '' ) {
					if ( isset( $wps_tofw_previous_saved_cities ) && '' == $wps_tofw_previous_saved_cities ) {
						if ( array_key_exists( isset( $_POST['wps_tofw_custom_shipping_cities'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_custom_shipping_cities'] ) ) : '', $wps_tofw_all_selected_cities ) ) {

							$wps_tofw_previous_saved_cities[ $current_order_status ][] = $wps_tofw_all_selected_cities[ sanitize_text_field( wp_unslash( $_POST['wps_tofw_custom_shipping_cities'] ) ) ];
							$wps_tofw_previous_saved_changed_time[ $current_order_status ][] = $converted_order_modified_date;

							if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
								$order_obj->update_meta_data( 'wps_tofw_track_custom_cities', $wps_tofw_previous_saved_cities );
								$order_obj->update_meta_data( 'wps_tofw_custom_change_time', $wps_tofw_previous_saved_changed_time );
								$order_obj->save();
							} else {
								update_post_meta( $order->id, 'wps_tofw_track_custom_cities', $wps_tofw_previous_saved_cities );
								update_post_meta( $order->id, 'wps_tofw_custom_change_time', $wps_tofw_previous_saved_changed_time );
							}
						}
					} else {
						if ( array_key_exists( isset( $_POST['wps_tofw_custom_shipping_cities'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_custom_shipping_cities'] ) ) : '', $wps_tofw_all_selected_cities ) ) {

							$wps_tofw_previous_saved_cities[ $current_order_status ][] = $wps_tofw_all_selected_cities[ sanitize_text_field( wp_unslash( $_POST['wps_tofw_custom_shipping_cities'] ) ) ];
							$wps_tofw_previous_saved_changed_time[ $current_order_status ][] = $converted_order_modified_date;

							if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
								$order_obj->update_meta_data( 'wps_tofw_track_custom_cities', $wps_tofw_previous_saved_cities );
								$order_obj->update_meta_data( 'wps_tofw_custom_change_time', $wps_tofw_previous_saved_changed_time );
								$order_obj->save();
							} else {

								update_post_meta( $order->id, 'wps_tofw_track_custom_cities', $wps_tofw_previous_saved_cities );
								update_post_meta( $order->id, 'wps_tofw_custom_change_time', $wps_tofw_previous_saved_changed_time );

							}
						}
					}
					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						$order_obj->update_meta_data( 'wps_tofw_save_selected_city', isset( $_POST['wps_tofw_custom_shipping_cities'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_custom_shipping_cities'] ) ) : '' );
						$order_obj->save();
					} else {
						update_post_meta( $order->id, 'wps_tofw_save_selected_city', isset( $_POST['wps_tofw_custom_shipping_cities'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_custom_shipping_cities'] ) ) : '' );
					}
				}
			}
		}

	}

}
