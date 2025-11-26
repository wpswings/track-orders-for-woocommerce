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

		if ( isset( $screen->id ) && ( 'wp-swings_page_home' === $screen->id || 'pluginhhhs' === $screen->id || 'wpswings_page_track_orders_for_woocommerce_menu' === $screen->id || 'wp-swings_page_track_orders_for_woocommerce_menu' === $screen->id ) ) {

			wp_enqueue_style( 'track-orders-for-woocommerce-select2-css', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/select-2/track-orders-for-woocommerce-select2.css', array(), time(), 'all' );

			wp_enqueue_style( 'track-orders-for-woocommerce-meterial-css', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-web.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'track-orders-for-woocommerce-meterial-css2', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-components-v5.0-web.min.css', array(), time(), 'all' );
			wp_enqueue_style( 'track-orders-for-woocommerce-meterial-lite', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/material-lite.min.css', array(), time(), 'all' );

			wp_enqueue_style( $this->plugin_name, TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/track-orders-for-woocommerce-admin.scss', array(), $this->version, 'all' );
			wp_enqueue_style( 'wps-admin-min-css', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/wps-admin.min.css', array(), $this->version, 'all' );
			wp_enqueue_style( 'wps-datatable-css', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/datatables/media/css/jquery.dataTables.min.css', array(), $this->version, 'all' );
		}
		wp_enqueue_style( 'track-orders-for-woocommerce-meterial-icons-css', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'package/lib/material-design/icon.css', array(), time(), 'all' );

		wp_enqueue_style( $this->plugin_name . '-admin-global', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/track-orders-for-woocommerce-admin-global.css', array( 'track-orders-for-woocommerce-meterial-icons-css' ), time(), 'all' );
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
			wp_register_script( $this->plugin_name . 'admin-js', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/track-orders-for-woocommerce-admin.js', array( 'jquery', 'track-orders-for-woocommerce-select2', 'track-orders-for-woocommerce-metarial-js', 'track-orders-for-woocommerce-metarial-js2', 'track-orders-for-woocommerce-metarial-lite' ), time(), false );
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

		wp_register_script( 'wps-admin-js', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/wps-admin.js', array(), time(), true );

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
				'title' => __( 'Enable Order Tracking on Invoice', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Show shipping tracking details directly on the invoice page.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_enable_invoice_tracking_info',
				'value' => get_option( 'wps_tofw_enable_invoice_tracking_info' ),
				'class' => 'wps_tofw_enable_invoice_tracking_info',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable Order Delay Notification', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Notify customers about order delays via email.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_enable_order_delay_notification',
				'value' => get_option( 'wps_tofw_enable_order_delay_notification' ),
				'class' => 'wps_tofw_enable_order_delay_notification',
				'configure' => 'yes',
				'configure-class' => 'wps-open-email-popup',
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

		$is_pro_activated = false;
		$is_pro_activated = apply_filters( 'track_orders_for_woocmmerce_pro_plugin_activated', $is_pro_activated );

		$template_options = array(
			array(
				'title' => __( 'Template1', 'track-orders-for-woocommerce' ),
				'type'  => 'radio',
				'id'    => 'wpg_invoice_template_one',
				'class' => 'wpg_invoice_template_one wpg_invoice_preview',
				'name'  => 'tofw_invoice_template',
				'value' => 'template_1',
				'src'   => esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot_1.3.png',
			),
			array(
				'title' => __( 'Template2', 'track-orders-for-woocommerce' ),
				'type'  => 'radio',
				'id'    => 'wpg_invoice_template_two',
				'class' => 'wpg_invoice_template_two wpg_invoice_preview',
				'src'   => esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot_2.png',
				'name'  => 'tofw_invoice_template',
				'value' => 'template_2',
			),
			array(
				'title' => __( 'Template3', 'track-orders-for-woocommerce' ),
				'type'  => 'radio',
				'id'    => 'wpg_invoice_template_three',
				'class' => 'wpg_invoice_template_three wpg_invoice_preview',
				'src'   => esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot_1.png',
				'name'  => 'tofw_invoice_template',
				'value' => 'template_3',
			),
			array(
				'title' => __( 'Template4', 'track-orders-for-woocommerce' ),
				'type'  => 'radio',
				'id'    => 'wpg_invoice_template_four',
				'class' => 'wpg_invoice_template_four wpg_invoice_preview',
				'src'   => esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/track_4.png',
				'name'  => 'tofw_invoice_template',
				'value' => 'template_4',
			),
		);

		// ✅ Conditionally add Template5
		if ( is_plugin_active( 'track-orders-for-woocommerce-pro/track-orders-for-woocommerce-pro.php' ) ) {
			$template_options[] = array(
				'title' => __( 'Template5', 'track-orders-for-woocommerce' ),
				'type'  => 'radio',
				'id'    => 'wpg_invoice_template_five',
				'class' => 'wpg_invoice_template_five wpg_invoice_preview custom_prince',
				'src'   => esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot_5_pro.png',
				'name'  => 'tofw_invoice_template',
				'link' => 'https://mail.google.com/mail/u/0/?tab=rm&ogbl#inbox',
				'value' => 'template_5',
			);
		} else {
			$template_options[] = array(
				'title' => __( 'Template5', 'track-orders-for-woocommerce' ),
				'type'  => 'radio',
				'id'    => 'wpg_invoice_template_five',
				'class' => 'wpg_invoice_template_five wpg_invoice_preview wps_tofw_pro_feature',
				'src'   => esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot_5.png',
				'name'  => '',
				'value' => '',
			);
		}

		$tofw_settings_general[] = array(
			'title'       => __( 'Choose Template', 'track-orders-for-woocommerce' ),
			'type'        => 'temp-select',
			'id'          => 'tofw_invoice_template',
			'description' => __( 'This template will be used as in email notification', 'track-orders-for-woocommerce' ),
			'selected'    => get_option( 'tofw_invoice_template' ),
			'value'       => $template_options,
		);

		$tofw_settings_general =
			/**
			 * Filter is for returning something.
			 *
			 * @since 1.0.0
			 */
			apply_filters( 'tofw_general_settings_array_filter', $tofw_settings_general );
		if ( ! $is_pro_activated ) {
			$tofw_settings_general[] =
				array(
					'title' => __( 'Enable QR Redirection Feature', 'track-orders-for-woocommerce' ),
					'type'  => 'radio-switch',
					'description'  => __( 'Send the QR in Email Notification on Changing Order Status', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_tofw_qr_redirect',
					'value' => '',
					'class' => 'wps_tofw_qr_redirect wps_tofw_pro_feature',
					'options' => array(
						'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
						'no' => __( 'NO', 'track-orders-for-woocommerce' ),
					),
				);

			$tofw_settings_general[] =
				array(
					'title' => __( 'Enable DHL Tracking', 'track-orders-for-woocommerce' ),
					'type'  => 'radio-switch',
					'description'  => __( 'Allow users to track DHL shipments directly using the tracking number without redirecting to carriers page', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_enable_dhl_tracking',
					'value' => '',
					'class' => 'wps_tofw_qr_redirect wps_tofw_pro_feature',
					'options' => array(
						'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
						'no' => __( 'NO', 'track-orders-for-woocommerce' ),
					),
				);
		}

		$tofw_settings_general[] = array(
			'type'        => 'button',
			'id'          => 'wps_tofw_general_settings_save',
			'main-class'  => 'wps_tofw_main_class',
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
	 * @param array $tofw_partial_shipement_settings fields.
	 */
	public function tofw_track_order_partial_shipement_array_callbck( $tofw_partial_shipement_settings ) {

		$tofw_partial_shipement_settings = array(
			array(
				'title' => __( 'Enable Partial Shipment Feature', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'id'    => 'tofw_enable_partial_shipment',
				'value' => get_option( 'tofw_enable_partial_shipment' ),
				'class' => 'tofw-radio-switch-class',
				'description'  => __( 'Enable functionality for partial shipment.', 'track-orders-for-woocommerce' ),
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Auto Complete Partial Order ', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'id'    => 'tofw_aut_comp_part_order',
				'description'  => __( 'Auto complete the partial order once the parent order completed.', 'track-orders-for-woocommerce' ),
				'value' => get_option( 'tofw_aut_comp_part_order' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Create the “Partially Shipped” order status ', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'id'    => 'tofw_part_order_status',
				'description'  => __( 'Register a new order status titled ‘Partially Shipped’.', 'track-orders-for-woocommerce' ),
				'value' => get_option( 'tofw_part_order_status' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
		);

		// save button.
		$tofw_partial_shipement_settings[] = array(
			'type'  => 'button',
			'id'    => 'wps_tofw_save_partial_shipment',
			'main-class'  => 'wps_tofw_main_class',
			'button_text' => __( 'Save Settings', 'track-orders-for-woocommerce' ),
			'class' => 'tofw-button-class',
		);

		return $tofw_partial_shipement_settings;
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
				'description'  => __( 'Enable functionality for tracking order.', 'track-orders-for-woocommerce' ),
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable Track Orders Button Below Order Details ', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'id'    => 'tofw_enable_track_order_below',
				'description'  => __( 'Enable functionality for tracking order below order deatils at my account page.', 'track-orders-for-woocommerce' ),
				'value' => get_option( 'tofw_enable_track_order_below' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enter Track Order Text Below Order Details', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Enter Text For Track Order Below Order details in My Account Section.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_enable_track_order_below_text',
				'placeholder' => 'Text For Track Order',
				'value' => get_option( 'tofw_enable_track_order_below_text', __( 'Track Order', 'track-orders-for-woocommerce' ) ),
				'class' => 'tofw-radio-switch-class',
			),
			array(
				'title' => __( 'Enter Track Order Note Below Order Details', 'track-orders-for-woocommerce' ),
				'type'  => 'textarea',
				'description'  => __( 'Enter Note For Track Order Below Order details in My Account Section.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_enable_track_order_below_textarea',
				'placeholder' => 'Text For Track Order',
				'value' => get_option( 'tofw_enable_track_order_below_textarea', __( 'Click The Below To Track Your Order', 'track-orders-for-woocommerce' ) ),
				'class' => 'tofw-radio-switch-class',
			),

			array(
				'title' => __( 'Enable Track Orders Button Action in Order Details ', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'id'    => 'tofw_enable_track_order_below_action',
				'description'  => 'Enable functionality for tracking order as Action on order deatils at my account page.',
				'value' => get_option( 'tofw_enable_track_order_below_action' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enter Track Order Action Text in Account Section', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Enter Text For Track Order as Action on Order details in My Account Section.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_enable_track_order_below_action_text',
				'placeholder' => 'Text For Order Action',
				'value' => get_option( 'tofw_enable_track_order_below_action_text', __( 'Track Order', 'track-orders-for-woocommerce' ) ),
				'class' => 'tofw-radio-switch-class',
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

			array(
				'title' => __( 'Enable WhatsApp Feature', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Activate this option to allow sharing tracking information URLs with customers via WhatsApp.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_enable_whatsapp_share_track_order',
				'value' => get_option( 'tofw_enable_whatsapp_share_track_order' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable Multi-Carrier Tracking', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch-copy',
				'description'  => __( '--> it will show multiple carrier tracking form on page.', 'track-orders-for-woocommerce' ),
				'shortcode' => '[WPS_MUTIPLE_CARRIER_TRACKING_FORM]',
				'id'    => 'wps_tofwp_enable_multi_carrier_tracking',
				'value' => get_option( 'wps_tofwp_enable_multi_carrier_tracking' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enter API Key', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description' => __( 'Enter your API Key to enable courier services. To get your API Key, <a href="https://www.trackingmore.com/docs/trackingmore/d5ac362fc3cda-api-quick-start-guide" target="_blank">visit the TrackingMore API dashboard</a>.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofwp_multi_carrier_api_key',
				'value' => get_option( 'wps_tofwp_multi_carrier_api_key' ),
				'class' => 'tofw-radio-switch-class',
			),

		);

		$tofw_track_order_settings =
			/**
			 * Filter is for returning something.
			 *
			 * @since 1.0.0
			 */
			apply_filters( 'tofw_track_order_settings_array_filter', $tofw_track_order_settings );

		$is_pro_activated = false;
		$is_pro_activated = apply_filters( 'track_orders_for_woocmmerce_pro_plugin_activated', $is_pro_activated );

		if ( ! $is_pro_activated ) {

			$tofw_track_order_settings_pro = array(
				array(
					'title' => __( 'Enable Popup Order Tracking', 'track-orders-for-woocommerce' ),
					'type'  => 'radio-switch',
					'id'    => 'wps_tofwp_enable_track_order_popup',
					'value' => '',
					'description'  => __( 'Pop-up will open on Order Action Track Order button', 'track-orders-for-woocommerce' ),

					'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
					'options' => array(
						'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
						'no' => __( 'NO', 'track-orders-for-woocommerce' ),
					),
				),
				array(
					'title' => __( 'Enable Shortcode to create Order Tracking page', 'track-orders-for-woocommerce' ),
					'type'  => 'radio-switch-copy',
					'description'  => __( '-->it will show my-account-page for logged in user and it will show tracking form for guest user.', 'track-orders-for-woocommerce' ),

					'shortcode' => '[wps_create_tracking_page]',
					'id'    => 'wps_tofwp_create_tracking_page',
					'value' => '',
					'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
					'options' => array(
						'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
						'no' => __( 'NO', 'track-orders-for-woocommerce' ),
					),
				),
				array(
					'title' => __( 'Enable Shortcode to show track order form', 'track-orders-for-woocommerce' ),
					'type'  => 'radio-switch-copy',
					'description'  => __( '--> it will show tracking form for logged in user as well as guest user.', 'track-orders-for-woocommerce' ),
					'shortcode' => '[wps_track_order_form]',
					'id'    => 'wps_tofwp_track_order_form',
					'value' => '',
					'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
					'options' => array(
						'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
						'no' => __( 'NO', 'track-orders-for-woocommerce' ),
					),
				),
				array(
					'title' => __( 'Enable to send pay link on pending status', 'track-orders-for-woocommerce' ),
					'type'  => 'radio-switch',
					'description'  => __( 'Send Mail Notification contains Pay link on pending payment order status', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_tofwp_send_pay_link',
					'value' => '',
					'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
					'options' => array(
						'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
						'no' => __( 'NO', 'track-orders-for-woocommerce' ),
					),
				),
				array(
					'title' => __( 'Enable to send message text on changing order status', 'track-orders-for-woocommerce' ),
					'type'  => 'radio-switch',
					'description'  => __( 'Send Message Text  Notification on every order status change', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_tofwp_enable_send_msg_text',
					'value' => '',
					'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
					'options' => array(
						'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
						'no' => __( 'NO', 'track-orders-for-woocommerce' ),
					),
				),

				array(
					'title' => __( 'Enter Twilio API Sid', 'track-orders-for-woocommerce' ),
					'type'  => 'text',
					'description'  => __( 'Enter twilio API sid here', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_tofwp_twillio_sid',
					'value' => '',
					'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
				),
				array(
					'title' => __( 'Enter Twilio API Token', 'track-orders-for-woocommerce' ),
					'type'  => 'text',
					'description'  => __( 'Enable twilio API token here.', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_tofwp_twillio_api_token',
					'value' => '',
					'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
				),
				array(
					'title' => __( 'Enter Twilio Sending Number', 'track-orders-for-woocommerce' ),
					'type'  => 'text',
					'description'  => __( 'Enable twilio sending number here.', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_tofwp_twillio_send_number',
					'value' => '',
					'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
				),
				array(
					'title' => __( 'Enter Content to send in Sms with ticket', 'track-orders-for-woocommerce' ),
					'type'  => 'text',
					'description'  => __( 'Use Placeholders  {customer} for Customer-Name, {order-id} for Order ID and {tracking-url} for Tracking URL.', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_tofwp_twillio_content_here',
					'value' => '',
					'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
				),

			);

			$tofw_track_order_settings = array_merge( $tofw_track_order_settings, $tofw_track_order_settings_pro );
		}

		$tofw_track_order_settings[] = array(
			'type'  => 'button',
			'id'    => 'wps_tofw_track-order_setting_save',
			'button_text' => __( 'Save Settings', 'track-orders-for-woocommerce' ),
			'class' => 'tofw-button-class',
			'main-class'  => 'wps_tofw_main_class',
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
				'title' => __( 'Enable Use Of Custom Order Status', 'track-orders-for-woocommerce' ),
				'description'  => __( 'Enable to use Custom Statuses to Use in Tracking.', 'track-orders-for-woocommerce' ),
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
			'main-class'  => 'wps_tofw_main_class',
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
			'main-class'  => 'wps_tofw_main_class',
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
				'title' => __( 'Enable FedEx Shipment Tracking', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Enable real-time FedEx tracking information on customer orders.', 'track-orders-for-woocommerce' ),
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
				'description'  => __( 'Enable integration with FedEx by entering your unique user key.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_fedex_userkey',
				'value' => get_option( 'wps_fedex_userkey' ),
				'class' => '',
				'style' => 'width:10em;',
				'classname' => 'wps_fedex_field',

			),
			array(
				'title' => __( 'Enter Your FedEx User Password   ', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Enable FedEx integration by providing your account password securely.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_fedex_userpassword',
				'value' => get_option( 'wps_fedex_userpassword' ),
				'class' => '',
				'style' => 'width:10em;',
				'classname' => 'wps_fedex_field',

			),
			array(
				'title' => __( 'Enter Your FedEx Account Number', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Enable FedEx services by providing your account number.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_fedex_account_number',
				'value' => get_option( 'wps_fedex_account_number' ),
				'class' => '',
				'style' => 'width:10em;',
				'classname' => 'wps_fedex_field',

			),
			array(
				'title' => __( 'Enter Your FedEx Meter Number    ', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => '',
				'id'    => 'wps_fedex_meter_number',
				'value' => get_option( 'wps_fedex_meter_number' ),
				'class' => '',
				'style' => 'width:10em;',
				'classname' => 'wps_fedex_field',

			),
		);

		$tofw_shipping_services_settings =
			/**
			 * Filter is for returning something.
			 *
			 * @since 1.0.0
			 */
			apply_filters( 'tofw_shipping_services_settings_array_filter', $tofw_shipping_services_settings );

		$is_pro_activated = false;
		$is_pro_activated = apply_filters( 'track_orders_for_woocmmerce_pro_plugin_activated', $is_pro_activated );

		if ( ! $is_pro_activated ) {

			$tofw_shipping_services_settings[] = array(
				'title' => __( 'Enable USPS Shipment Tracking API', 'track-orders-for-woocommerce' ),
				'description'  => __( ' Enable real-time USPS tracking for your shipments via API integration.', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'id'    => 'wps_tofwp_enable_usps_tracking',
				'value' => '',
				'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			);
			$tofw_shipping_services_settings[] = array(
				'title' => __( 'USPS Username', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Enter Your USPS Username Here', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofwp_usps_tracking_user_key',
				'value' => '',
				'class' => 'wps_tofw_pro_feature',

			);
			$tofw_shipping_services_settings[] = array(
				'title' => __( 'USPS User Password', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Enter Your USPS Password Here', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofwp_usps_tracking_user_password',
				'value' => '',
				'class' => 'wps_tofw_pro_feature',

			);
			$tofw_shipping_services_settings[] = array(
				'title' => __( 'Enable Canada Post Shipment Tracking API', 'track-orders-for-woocommerce' ),
				'description'  => __( 'Enable real-time Canada Post tracking for your shipments via API integration.', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'id'    => 'wps_tofwp_enable_canadapost_tracking',
				'value' => '',
				'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			);
			$tofw_shipping_services_settings[] = array(
				'title' => __( 'Canada Post Username', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Enter Your Post Username Here', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofwp_canadapost_tracking_user_key',
				'value' => '',
				'class' => 'wps_tofw_pro_feature',

			);
			$tofw_shipping_services_settings[] = array(
				'title' => __( 'Canada Post User Password', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'Enter Your Canada Post Password Here', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofwp_canadapost_tracking_user_password',
				'value' => '',
				'class' => 'wps_tofw_pro_feature',

			);
			$tofw_shipping_services_settings[] = array(
				'title' => __( 'Enable 17Track.net Tracking Feature', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'id'    => 'wps_tofwp_enable_17track_integration',
				'value' => '',
				'description'  => __( 'Note :- To use this feature please disable **Enable Third Party Tracking API** option.', 'track-orders-for-woocommerce' ),
				'class' => 'tofw-radio-switch-class wps_tofw_pro_feature',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			);
		}

		$tofw_shipping_services_settings[] = array(
			'type'  => 'button',
			'id'    => 'wps_tofw_shipping_services_settings_save',
			'button_text' => __( 'Save Settings', 'track-orders-for-woocommerce' ),
			'class' => 'tofw-button-class',
			'main-class'  => 'wps_tofw_main_class',
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

			if ( isset( $screen->id ) && ( 'wp-swings_page_home' === $screen->id || 'wpswings_page_home' === $screen->id ) ) {

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
			update_option(
				'wps_enable_dhl_track_icon',
				isset( $_POST['wps_tofw_other_setting_upload_DHL_ICON'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_other_setting_upload_DHL_ICON'] ) ) : ''
			);
		}
		if ( isset( $_POST['wps_tofw_track-order_setting_save'] ) ) {
			$wps_msp_gen_flag     = false;
			$tofw_genaral_settings =
				// desc - filter for trial.
				apply_filters( 'tofw_track_order_array', array() );
			$wps_settings_save_progress = true;
		}
		if ( isset( $_POST['wps_tofw_save_partial_shipment'] ) ) {
			$wps_msp_gen_flag     = false;
			$tofw_genaral_settings =
				// desc - filter for trial.
				apply_filters( 'tofw_track_order_partial_shipement_array', array() );
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
	 * @param array $wps_input_array is the array data.
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
		$create_custom_order_status1 = array();
		$tem = array();
		$value = array();
		$custom_order_image_url = array();
		$wps_image_url = array();
		$set_value_temp = array();
		$value = get_option( 'wps_tofw_new_custom_order_status', false );

		$set_value_temp = get_option( 'wps_tofw_new_custom_template', false );
		$custom_order_image_url = get_option( 'wps_tofw_new_custom_order_image', false );
		if ( is_array( $value ) && ! empty( $value ) ) {
			$create_custom_order_status = isset( $_POST['wps_tofw_new_role_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_new_role_name'] ) ) : '';
			$create_custom_order_image_url = isset( $_POST['wps_custom_order_image_url'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_custom_order_image_url'] ) ) : '';
			$key_custom_order_status = str_replace( ' ', '', $create_custom_order_status );
			$key_custom_order_status = strtolower( $key_custom_order_status );
			$value[] = array( $key_custom_order_status => $create_custom_order_status );
			$custom_order_image_url[ $key_custom_order_status ] = $create_custom_order_image_url;

			$create_custom_order_status1 = isset( $_POST['wps_tofw_new_role_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_new_role_name'] ) ) : '';
			$tem = isset( $_POST['wps_template_select'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_template_select'] ) ) : '';
			$set_value_temp[] = array( $create_custom_order_status1 => $tem );

			update_option( 'wps_tofw_new_custom_order_status', $value );
			update_option( 'wps_tofw_new_custom_order_image', $custom_order_image_url );
			update_option( 'wps_tofw_new_custom_template', $set_value_temp );
		} else {

			$create_custom_order_status = isset( $_POST['wps_tofw_new_role_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_new_role_name'] ) ) : '';
			$create_custom_order_image_url = isset( $_POST['wps_custom_order_image_url'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_custom_order_image_url'] ) ) : '';
			$key_custom_order_status = str_replace( ' ', '', $create_custom_order_status );
			$key_custom_order_status = strtolower( $key_custom_order_status );

			// Ensure it's an array.
			if ( ! is_array( $value ) ) {
				$value = array();
			}

			$value[] = array( $key_custom_order_status => $create_custom_order_status );
			$custom_order_image_url[ $key_custom_order_status ] = $create_custom_order_image_url;

			$create_custom_order_status1 = isset( $_POST['wps_tofw_new_role_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_new_role_name'] ) ) : '';
			$tem = isset( $_POST['wps_template_select'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_template_select'] ) ) : '';
			// Ensure it's an array.
			if ( ! is_array( $set_value_temp ) ) {
				$set_value_temp = array();
			}
			$set_value_temp[] = array( $create_custom_order_status1 => $tem );

			update_option( 'wps_tofw_new_custom_order_status', $value );
			update_option( 'wps_tofw_new_custom_order_image', $custom_order_image_url );
			update_option( 'wps_tofw_new_custom_template', $set_value_temp );
		}

		esc_html_e( 'success', 'track-orders-for-woocommerce' );
		wp_die();
	}

	/**
	 * This function delete the Custom order status on the backend
	 *
	 * @link http://www.wpswings.com/
	 */
	public function wps_tofw_edit_custom_order_status_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$wps_response = array();
		$wps_tofw_old_selected_statuses = get_option( 'wps_tofw_new_settings_custom_statuses_for_order_tracking', false );
		$wps_key_name_space = isset( $_POST['wps_key_name_space'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_key_name_space'] ) ) : '';
		$wps_custom_key = isset( $_POST['wps_custom_key'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_custom_key'] ) ) : '';

		$template_array = get_option( 'wps_tofw_new_custom_template', array() );

		$search_key_template = $wps_key_name_space;

		foreach ( $template_array as $item ) {

			if ( array_key_exists( $search_key_template, $item ) ) {
				$value = $item[ $search_key_template ];
				$wps_response['wps_template_name'] = $value;
				break;
			}
		}

		$template_array_image = get_option( 'wps_tofw_new_custom_order_image', array() );

		if ( isset( $template_array_image[ $wps_custom_key ] ) ) {
			$value = $template_array_image[ $wps_custom_key ];
			$wps_response['wps_custom_order_image'] = $value;
		}

		$wps_response['wps_order_status_name'] = $wps_key_name_space;

		echo wp_json_encode( $wps_response );
		wp_die();
	}



	/**
	 * This function delete the Custom order status on the backend  wps_tofw_edit_custom_order_status_callback
	 *
	 * @link http://www.wpswings.com/
	 */
	public function wps_tofw_save_edit_custom_order_status_callback() {
		 check_ajax_referer( 'ajax-nonce', 'nonce' );
		$wps_response = array();

		$wps_tofw_edit_order_status = isset( $_POST['wps_tofw_edit_order_status'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_edit_order_status'] ) ) : '';
		$wps_edit_order_image_url = isset( $_POST['wps_edit_order_image_url'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_edit_order_image_url'] ) ) : '';
		$wps_edit_template_select = isset( $_POST['wps_edit_template_select'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_edit_template_select'] ) ) : '';
		$wps_tofw_edit_order_status_space = isset( $_POST['wps_tofw_edit_order_status_space'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_edit_order_status_space'] ) ) : '';

		$wps_without_space = str_replace( ' ', '', $wps_tofw_edit_order_status_space );

		$template_array_image = get_option( 'wps_tofw_new_custom_order_image', array() );
		$wps_template_name_array = get_option( 'wps_tofw_new_custom_template', array() );
		$wps_all_custom_order_array = get_option( 'wps_tofw_new_custom_order_status', array() );

		// Check if the key exists in the array before updating image URL.
		if ( array_key_exists( $wps_without_space, $template_array_image ) ) {
			$template_array_image[ $wps_without_space ] = $wps_edit_order_image_url; // Update the value.
		}

		foreach ( $wps_template_name_array as $key => $value ) {
			if ( isset( $value[ $wps_tofw_edit_order_status_space ] ) ) {
				$wps_template_name_array[ $key ][ $wps_tofw_edit_order_status_space ] = $wps_edit_template_select; // Update the value.
			}
		}

		if ( array_key_exists( $wps_tofw_edit_order_status, $wps_all_custom_order_array ) ) {
			$wps_all_custom_order_array[ $wps_tofw_edit_order_status ] = $wps_edit_template_select; // Update the value.
		}

		// Update the options with the modified arrays.
		update_option( 'wps_tofw_new_custom_order_image', $template_array_image );
		update_option( 'wps_tofw_new_custom_template', $wps_template_name_array );
		update_option( 'wps_tofw_new_custom_order_status', $wps_all_custom_order_array );

		echo wp_json_encode( $wps_response );

		wp_die();
	}


	/**
	 * This function delete the Custom order status on the backend.
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

				$custom_order_status_temp = get_option( 'wps_tofw_new_custom_template', array() );

				foreach ( $custom_order_status_temp as $index => $sub_array ) {
					if ( isset( $sub_array[ $wps_custom_key ] ) ) {
						// Unset the element if the key matches.
						unset( $custom_order_status_temp[ $index ] );
					}
				}
				if ( class_exists( 'WC_Logger' ) ) {
					$logger = wc_get_logger();
					$logger->info( json_encode( $custom_order_status_temp, JSON_PRETTY_PRINT ), array( 'source' => 'wps-order-tracker-plugin' ) );
				}
				update_option( 'wps_tofw_new_custom_template', $custom_order_status_temp );

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
									echo 'selected';
								}
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
										echo 'selected';
									}
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
					<input type="text" name="wps_tofw_tracking_number" id="wps_tofw_tracking_number" value="<?php echo esc_attr( $wps_tofw_track_id ); ?>" placeholder="<?php esc_attr_e( 'Enter 17 Tracking Number', 'track-orders-for-woocommerce' ); ?>"></input>
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
					$order_obj->save();
				}
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
				if ( ! $order ) {
					return;
				}
				$headers[] = 'Content-Type: text/html; charset=UTF-8';
				$wps_tracking_url = get_post_meta( $order_id, 'wps_tofw_enhanced_order_company', true );
				$wps_tracking_number = get_post_meta( $order_id, 'wps_tofw_enhanced_tracking_no', true );
				if ( version_compare( WC()->version, '3.0.0', '<' ) ) {
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
				$mail_header = __( 'Current Order Status is ', 'track-orders-for-woocommerce' ) . $statuses[ $new_status ];
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
					isset( $_POST['wps_tofw_selected_shipping_services'] ) && $order_obj->update_meta_data( 'wps_tofw_selected_shipping_service', sanitize_text_field( wp_unslash( $_POST['wps_tofw_selected_shipping_services'] ) ) );

					isset( $_POST['wps_tofw_tracking_number'] ) && $order_obj->update_meta_data( 'wps_tofw_package_tracking_number', sanitize_text_field( wp_unslash( $_POST['wps_tofw_tracking_number'] ) ) );
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
			if ( isset( $order ) && ! empty( $order ) && isset( $order_obj ) && is_object( $order ) ) {

				if ( is_object( $order_obj ) && method_exists( $order_obj, 'get_data' ) ) {

					if ( empty( $order_obj ) ) {
						return;
					}
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
				} else {
					if ( class_exists( 'WC_Logger' ) ) {
						$logger = wc_get_logger();
						$logger->error( 'Error: $order_obj is not an object or does not have get_data method.', array( 'source' => 'wps-order-tracker-plugin' ) );
					}
				}
			}
		}
	}

	/**
	 * Function to add new column.
	 *
	 *  @param int    $column_name column name.
	 *  @param object $order is the order object.
	 * @return void
	 */
	public function tofw_track_order_col_column( $column_name, $order ) {
		// WC_Order object is available as $order variable here.
		$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
		$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
		$track_order_url = get_permalink( $page_id );

		// Parse the URL.
		$url_parts = wp_parse_url( $track_order_url );
		$path = $url_parts['path'];
		$path = trim( $path, '/' );
		$path_parts = explode( '/', $path );
		$last_part = end( $path_parts );

		if ( 'tofw_track_order_col' === $column_name ) {
			;
			if ( $order ) {
				$site_url = get_site_url() . '/' . $last_part . '/?' . esc_html( $order->get_order_number() ) . '';
				echo '<a href="' . esc_url( $site_url ) . '" target="_blank">
        <img src="' . esc_url( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/track_icon.png' ) . '" alt="Icon" style="width: 40px; height: 40px;">
      </a>';
			}
		}
	}

	/**
	 * Function to register new column.
	 *
	 *  @param array $columns column array.
	 * @return array
	 */
	public function wps_new_column_track_order_column( $columns ) {
		// just add a new column here.
		$columns['tofw_track_order_col'] = 'Tracking Order';
		// return the modified array.
		return $columns;
	}


	/**
	 * Register google embed block.
	 *
	 * @return void
	 */
	public function register_google_embed_blocks() {
		wp_register_script(
			'tofw-embed-block',
			plugins_url( 'src/js/tofw-embed-block.js', __FILE__ ),
			array( 'wp-blocks', 'wp-editor', 'wp-element', 'wp-components' ),
			filemtime( plugin_dir_path( __FILE__ ) . '/src/js/tofw-embed-block.js' )
		);

		register_block_type(
			'wpswings/tofw-embed-block',
			array(
				'editor_script' => 'tofw-embed-block',
			)
		);

		wp_localize_script(
			'tofw-embed-block',
			'embed_block_param',
			array(
				'ajaxurl'             => admin_url( 'admin-ajax.php' ),
				'reloadurl'           => admin_url( 'admin.php?page=pdf_generator_for_wp_menu' ),
			)
		);
	}


	/**
	 * Add line item status header to the order edit screen.
	 */
	public function add_line_item_status_header() {
		 $wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		if ( 'on' !== $wps_enable_partila_shipement ) {
			return;
		}
		echo '<th class="line-item-status">Status</th>';
	}

	/**
	 * Add line item status dropdown to each line item in the order edit screen.
	 *
	 * @param WC_Product    $product The product object.
	 * @param WC_Order_Item $item The order item object.
	 * @param int           $item_id The order item ID.
	 */
	public function wps_tofw_add_line_item_status_dropdown( $product, $item, $item_id ) {

		if ( $item->get_type() !== 'line_item' ) {
			return;
		}

		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		$order = wc_get_order( $item->get_order_id() );
		if ( ! $order || 'on' !== $wps_enable_partila_shipement ) {
			return;
		}

		// Count products in the order.
		$items = $order->get_items();
		if ( count( $items ) > 1 ) {
			$current_status = wc_get_order_item_meta( $item_id, '_line_item_status', true );
			if ( empty( $current_status ) ) {
				$current_status = $order->get_status();
			}

			// Check if child order.
			$wps_tofw_is_child_order = $order->get_meta( '_wps_child_order_ids' );
			if ( ! empty( $wps_tofw_is_child_order ) ) {
				$order_statuses = wc_get_order_statuses();

				echo '<td class="line-item-status" width="20%">';
				echo '<select name="line_item_status[' . esc_attr( $item_id ) . ']" class="line-item-status-select" style="width: 100%;">';

				foreach ( $order_statuses as $status_key => $status_name ) {
					$status_key = str_replace( 'wc-', '', $status_key );
					$selected   = selected( $current_status, $status_key, false );
					echo '<option value="' . esc_attr( $status_key ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $status_name ) . '</option>';
				}

				echo '</select>';
				echo '</td>';
			}
		}
	}

	/**
	 * Save line item status when order is saved.
	 *
	 * @param int   $order_id Order ID.
	 * @param array $items Array of order items.
	 */
	public function save_line_item_status( $order_id, $items ) {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		check_admin_referer( 'update-order_' . $order_id );
		if ( isset( $_POST['line_item_status'] ) && is_array( $_POST['line_item_status'] ) && 'on' === $wps_enable_partila_shipement ) {
			if ( isset( $_POST['line_item_status'] ) && is_array( $_POST['line_item_status'] ) ) {
				$line_item_statuses = array_map( 'sanitize_text_field', wp_unslash( $_POST['line_item_status'] ) );
			}

			foreach ( $line_item_statuses as $item_id => $status ) {
				$item_id = absint( $item_id );
				$status  = sanitize_text_field( $status );
				wc_update_order_item_meta( $item_id, '_line_item_status', sanitize_text_field( $status ) );
				$parent_order = wc_get_order( $order_id );
				$child_order_ids = $parent_order->get_meta( '_wps_child_order_ids' );

				foreach ( $child_order_ids as $key => $value ) {
					if ( $this->wps_get_product_id_from_item( $item_id ) == $key ) {
						$order = wc_get_order( $value );
						$order->update_status( $status );
						$child_order = wc_get_order( $value );
						$child_order->add_order_note( 'Status updated from parent order' );
					}
				}

				// Optional: Create a note when status changes.
				$order = wc_get_order( $order_id );
				$item = $order->get_item( $item_id );
				if ( $item ) {
					$product_name = $item->get_name();
					$status_name = wc_get_order_status_name( $status );
					$order->add_order_note(
						sprintf( 'Line item "%s" status changed to: %s', $product_name, $status_name )
					);
				}
			}
		}
	}

	/**
	 * Get product ID from order item ID.
	 *
	 * @param int    $item_id Order item ID.
	 * @param string $return_type Type of ID to return: 'product_id', 'variation_id', 'both', or 'array'.
	 * @return int|array|false Product ID, variation ID, both, or array of IDs; false on failure.
	 */
	public function wps_get_product_id_from_item( $item_id, $return_type = 'product_id' ) {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		if ( ! $item_id && 'on' !== $wps_enable_partila_shipement ) {
			return false;
		}

		try {
			$item = new WC_Order_Item_Product( $item_id );
			$product_id = $item->get_product_id();
			$variation_id = $item->get_variation_id();

			switch ( $return_type ) {
				case 'product_id':
					return $product_id ? intval( $product_id ) : false;
				case 'variation_id':
					return $variation_id ? intval( $variation_id ) : false;
				case 'both':
					return $variation_id ? intval( $variation_id ) : intval( $product_id );
				case 'array':
					return array(
						'product_id' => $product_id ? intval( $product_id ) : 0,
						'variation_id' => $variation_id ? intval( $variation_id ) : 0,
						'is_variation' => ! empty( $variation_id ),
					);

				default:
					return $product_id ? intval( $product_id ) : false;
			}
		} catch ( Exception $e ) {
			error_log( 'Error getting product ID from item: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Add custom CSS for the line item status column.
	 */
	public function line_item_status_admin_css() {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		$screen = get_current_screen();
		if ( $screen && 'shop_order' === $screen->id && 'on' == $wps_enable_partila_shipement ) {
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

	/**
	 * Add bulk status update dropdown above line items table.
	 *
	 * @param int $order_id Order ID.
	 */
	public function wps_tofw_add_bulk_status_update( $order_id ) {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || 'on' != $wps_enable_partila_shipement ) {
			return;
		}

		$wps_tofw_is_child_order = $order->get_meta( '_wps_child_order_ids' );
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Count products in the order.
		$items = $order->get_items();
		if ( count( $items ) > 1 ) {

			if ( ! empty( $wps_tofw_is_child_order ) ) {
				$order_statuses = wc_get_order_statuses();
				?>
				<tr class="bulk-status-update">
					<td colspan="2">
						<strong><?php esc_html_e( 'Bulk Status Update:', 'track-orders-for-woocommerce' ); ?></strong>
					</td>
					<td colspan="4">
						<select id="bulk_line_item_status" style="width: 200px;">
							<option value=""><?php esc_html_e( 'Select status…', 'track-orders-for-woocommerce' ); ?></option>
							<?php
							foreach ( $order_statuses as $status_key => $status_name ) :
								$status_key = str_replace( 'wc-', '', $status_key );
								?>
								<option value="<?php echo esc_attr( $status_key ); ?>">
									<?php echo esc_html( $status_name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<button type="button" id="apply_bulk_status" class="button">
							<?php esc_html_e( 'Apply to All', 'track-orders-for-woocommerce' ); ?>
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

						// Visual feedback when a status changes.
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
	}


	/**
	 * Add the "Partial Shipments" column to the orders list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function wps_tofw_order_list_table_columns( $columns ) {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		if ( 'on' !== $wps_enable_partila_shipement ) {
			return $columns;
		}
		// place after order_status.
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'order_status' === $key ) {
				$new['wps_split_shipments'] = __( 'Partial Shipments', 'track-orders-for-woocommerce' );
			}
		}
		return $new;
	}

	/**
	 * Render the column content.
	 *
	 * @param string   $column The column key.
	 * @param WC_Order $order The order object.
	 */
	public function wps_tofw_shop_order_list_table_custom_column_callback( $column, $order ) {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		if ( 'on' !== $wps_enable_partila_shipement ) {
			return;
		}

		$wps_tofw_is_child_order = $order->get_meta( '_wps_child_order_ids' );

		if ( ! isset( $wps_tofw_is_child_order ) ) {
			return;
		}

		if ( 'wps_split_shipments' !== $column || ! $order instanceof WC_Order ) {
			return;
		}
		echo wp_kses_post( (string) $this->wps_render_split_shipments_cell( $order->get_id(), $order ) );
	}

	/**
	 * Shared renderer for the cell content.
	 * Shows child order count and a compact status summary.
	 *
	 * @param int      $parent_order_id The parent order ID.
	 * @param WC_Order $parent_order The parent order object (optional, will be loaded.
	 */
	public function wps_render_split_shipments_cell( $parent_order_id, $parent_order = null ) {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		if ( 'on' !== $wps_enable_partila_shipement ) {
			return;
		}
		if ( ! $parent_order ) {
			$parent_order = wc_get_order( $parent_order_id );
		}
		if ( ! $parent_order ) {
			echo '&mdash;';
			return;
		}

		$children = $this->wps_get_child_orders_ids( $parent_order );
		if ( empty( $children ) ) {
			echo '<span style="opacity:.7;">&mdash;</span>';
			return;
		}

		$map = array();
		foreach ( $children as $cid ) {
			$c = wc_get_order( $cid );
			if ( ! $c ) {
				continue;
			}
			$st = $c->get_status();
			if ( ! isset( $map[ $st ] ) ) {
				$map[ $st ] = 0;
			}
			$map[ $st ]++;
		}

		if ( ! empty( $children ) ) {
			echo '<div style="margin-top:4px;">';
			foreach ( $children as $cid ) {
				printf(
					'<div><a href="%s" target="_blank" style="text-decoration:none;">%s #%d</a></div>',
					esc_url( get_edit_post_link( $cid ) ),
					esc_html__( 'Open child order ↗', 'track-orders-for-woocommerce' ),
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
	 *
	 * @param WC_Order $parent_order The parent order.
	 */
	public function wps_get_child_orders_ids( WC_Order $parent_order ): array {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		if ( 'on' !== $wps_enable_partila_shipement ) {
			return '';
		}
		$parent_id = $parent_order->get_id();
		$ids = array();

		$sub = wc_get_orders(
			array(
				'limit'  => -1,
				'parent' => $parent_id,
				'return' => 'ids',
				'type'   => array( 'shop_order' ),
			)
		);
		if ( ! empty( $sub ) ) {
			$ids = array_merge( $ids, $sub );
		}

		$meta_key   = apply_filters( 'wps_to_child_orders_meta_key', '_child_order_ids' );
		$child_list = (array) $parent_order->get_meta( $meta_key, true );
		if ( ! empty( $child_list ) ) {
			$ids = array_merge( $ids, array_map( 'absint', $child_list ) );
		}

		$ids = array_values( array_unique( array_filter( $ids ) ) );
		return $ids;
	}

	/**
	 * Make the column sortable.
	 *
	 * @param array $cols Existing columns.
	 * @return array Modified columns.
	 */
	public function wps_tofw_shop_order_sortable_columns_callback( $cols ) {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		if ( 'on' !== $wps_enable_partila_shipement ) {
			return $cols;
		}
		$cols['wps_split_shipments'] = 'wps_split_shipments';
		return $cols;
	}

	/**
	 * Adjust the query to sort by child order count.
	 *
	 * @param WP_Query $q The current query object.
	 */
	public function wps_tofw_pre_get_posts_cllbck( $q ) {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		if ( 'on' !== $wps_enable_partila_shipement ) {
			return;
		}
		if ( is_admin() && 'shop_order' === $q->get( 'post_type' ) && $q->get( 'orderby' ) === 'wps_split_shipments' ) {
			$q->set( 'meta_key', '_wps_child_count' );
			$q->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * When a parent order is completed, auto-complete all its child orders.
	 *
	 * @param int $order_id The ID of the order being completed.
	 */
	public function wps_tofw_auto_complete_child_orders( $order_id ) {
		$wps_tofw_auto_comple = get_option( 'tofw_aut_comp_part_order' );
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		if ( 'on' !== $wps_enable_partila_shipement || 'on' != $wps_tofw_auto_comple ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		// Get child orders from parent order meta.
		$child_order_ids = (array) $order->get_meta( '_wps_child_order_ids' );

		if ( ! empty( $child_order_ids ) ) {
			foreach ( $child_order_ids as $child_order_id ) {
				$child_order = wc_get_order( $child_order_id );

				if ( $child_order instanceof WC_Order ) {
					// Only update if not already completed.
					if ( $child_order->get_status() !== 'completed' ) {

						foreach ( $child_order->get_items() as $item_id => $item ) {
							wc_update_order_item_meta(
								$item_id,
								'_line_item_status',
								'completed'
							);
						}

						$child_order->update_status(
							'completed',
							__( 'Auto-completed because parent order was completed.', 'track-orders-for-woocommerce' )
						);
					}
				}
			}

			foreach ( $order->get_items() as $parent_item_id => $parent_item ) {
				wc_update_order_item_meta(
					$parent_item_id,
					'_line_item_status',
					'completed'
				);
			}

			// Save parent order changes.
			$order->save();
		}
	}

	/**
	 * Update a parent order's line item meta when a child order status changes.
	 *
	 * @param int      $order_id   The ID of the order whose status changed.
	 * @param string   $old_status The previous status of the order.
	 * @param string   $new_status The new status of the order.
	 * @param WC_Order $order      The order object.
	 */
	public function wps_update_another_order_on_status_change_hpos( $order_id, $old_status, $new_status, $order ) {

		// Validate order object.
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		// Retrieve linked order metadata (HPOS safe).
		$is_child_order_id = $order->get_meta( '_wps_is_child_order' );
		$parent_order_id   = $order->get_meta( '_wps_parent_order_id' );
		$cart_line_item_id = $order->get_meta( '_wps_parent_item_id' );

		// Validate required metadata before proceeding.
		if ( 'yes' !== $is_child_order_id || empty( $parent_order_id ) || empty( $cart_line_item_id ) ) {
			return;
		}

		// Get parent order object.
		$target_order = wc_get_order( $parent_order_id );
		if ( ! $target_order ) {
			return;
		}

		// Loop through parent order items and update the matching one.
		foreach ( $target_order->get_items() as $item_id => $item ) {
			if ( (int) $item_id === (int) $cart_line_item_id ) {
				$item->update_meta_data( '_line_item_status', $new_status );
				$item->save();
				$target_order->add_order_note( "Line item #{$item_id} updated to status '{$new_status}' due to child order #{$order_id} status change." );
				break;
			}
		}

		// Save changes to the parent order.
		$target_order->save();
	}

	/**
	 * Render the email template popup in admin footer.
	 */
	public function wps_tofw_admin_footer() {
		if ( 'wpswings_page_track_orders_for_woocommerce_menu' === get_current_screen()->id ) {
			$customer_subject = get_option( 'wps_delay_email_customer_subject', 'Delivery Delay Notification - Order {order_id}' );
			$customer_body    = get_option( 'wps_delay_email_customer_body', '<p>Your delivery has been delayed.</p>' );

			$admin_subject = get_option( 'wps_delay_email_admin_subject', 'Order Delay - {order_id}' );
			$admin_body    = get_option( 'wps_delay_email_admin_body', '<p>An order has been delayed.</p>' );

			$notify_admin = get_option( 'wps_tofw_notify_admin_delay', 'no' );
			?>
	<!-- MAIN POPUP -->
	<div id="wps-email-popup" class="wps-popup">
		<div class="wps-popup-dialog">
			<div class="wps-popup-header">
				<strong><h4><?php echo esc_html__( 'Delay Notification', 'track-orders-for-woocommerce' ); ?></h4></strong>
				<span class="wps-popup-close">&times;</span>
			</div>
			<div class="wps-popup-body">
				<label class="wps-toggle">
					<input type="checkbox" id="wps_send_admin_mail" <?php checked( $notify_admin, 'yes' ); ?>>
					<span>Send Delay Email to Admin</span>
				</label>
				<hr>

				<strong><h5><?php echo esc_html__( 'Customer Email Template', 'track-orders-for-woocommerce' ); ?></h5></strong>

				<label><?php echo esc_html__( 'Subject', 'track-orders-for-woocommerce' ); ?></label>
				<input type="text" id="wps_customer_subject" class="wps-input"
					   value="<?php echo esc_attr( $customer_subject ); ?>">

				<label><?php echo esc_html__( 'Email Body (HTML Allowed)', 'track-orders-for-woocommerce' ); ?></label>
				<textarea id="wps_customer_body" class="wps-textarea"><?php echo esc_textarea( $customer_body ); ?></textarea>

				<p class="wps-placeholders">
					<strong><?php echo esc_html__( 'Placeholders:', 'track-orders-for-woocommerce' ); ?></strong><br>
					{order_id}, {customer_name}, {expected_date}, {expected_time},<br>
					{expected_datetime}, {order_url}
				</p>

				<!-- PREVIEW BUTTON -->
				<button type="button"
						class="button button-secondary wps-preview-email"
						data-type="customer"
						style="margin-bottom:10px;">
					<?php echo esc_html__( 'Preview Customer Email', 'track-orders-for-woocommerce' ); ?>
				</button>

				<!-- PREVIEW BOX -->
				<div id="wps-preview-customer" class="wps-email-preview" style="display:none;">
					<div class="wps-email-preview-inner"></div>
				</div>

				<hr>

				<!-- ADMIN EMAIL SETTINGS -->
					<div id="wps_admin_section" style="<?php echo ( 'yes' === $notify_admin ) ? '' : 'display:none;'; ?>">

					<strong><h4><?php echo esc_html__( 'Admin Email Template', 'track-orders-for-woocommerce' ); ?></h4></strong>

					<label><?php echo esc_html__( 'Subject', 'track-orders-for-woocommerce' ); ?></label>
					<input type="text" id="wps_admin_subject" class="wps-input"
						   value="<?php echo esc_attr( $admin_subject ); ?>">

					<label><?php echo esc_html__( 'Email Body (HTML Allowed)', 'track-orders-for-woocommerce' ); ?></label>
					<textarea id="wps_admin_body" class="wps-textarea"><?php echo esc_textarea( $admin_body ); ?></textarea>

					<p class="wps-placeholders">
						<strong><?php echo esc_html__( 'Placeholders:', 'track-orders-for-woocommerce' ); ?></strong><br>
						{order_id}, {customer_name}, {expected_date}, {expected_time},<br>
						{expected_datetime}, {order_url}
					</p>

					<!-- PREVIEW BUTTON -->
					<button type="button"
							class="button button-secondary wps-preview-email"
							data-type="admin"
							style="margin-bottom:10px;">
						<?php echo esc_html__( 'Preview Admin Email', 'track-orders-for-woocommerce' ); ?>
					</button>

					<!-- PREVIEW BOX -->
					<div id="wps-preview-admin" class="wps-email-preview" style="display:none;">
						<div class="wps-email-preview-inner"></div>
					</div>

				</div>

			</div>

			<!-- FOOTER -->
			<div class="wps-popup-footer">
				<button class="button button-primary wps-save-email-template"><?php echo esc_html__( 'Save Settings', 'track-orders-for-woocommerce' ); ?></button>
				<button class="button wps-close-popup"><?php echo esc_html__( 'Close', 'track-orders-for-woocommerce' ); ?></button>
			</div>

		</div>
	</div>

			<?php
		}
	}


		/**
		 * Handle preview email AJAX request.
		 *
		 * @return void
		 */
	public function wps_preview_wc_email_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		$body    = isset( $_POST['body'] ) ? wp_kses_post( wp_unslash( $_POST['body'] ) ) : '';

		$sample = array(
			'{order_id}'        => '1234',
			'{customer_name}'   => 'John Doe',
			'{expected_date}'   => 'November 28, 2025',
			'{expected_time}'   => '11:15 AM',
			'{expected_datetime}' => 'November 28, 2025 11:15 AM',
			'{order_url}'       => site_url( '/my-account/view-order/1234/' ),
		);

		foreach ( $sample as $tag => $value ) {
			$subject = str_replace( $tag, $value, $subject );
			$body    = str_replace( $tag, $value, $body );
		}

		$mailer = WC()->mailer();
		$final_html = $mailer->wrap_message( $subject, wpautop( $body ) );

		wp_send_json_success( $final_html );
	}

		/**
		 * Save delay email settings.
		 *
		 * @return void
		 */
	public function wps_save_delay_email_settings_callback() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		$customer_subject = isset( $_POST['customer_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_subject'] ) ) : '';
		$customer_body    = isset( $_POST['customer_body'] ) ? wp_kses_post( wp_unslash( $_POST['customer_body'] ) ) : '';
		$admin_subject    = isset( $_POST['admin_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['admin_subject'] ) ) : '';
		$admin_body       = isset( $_POST['admin_body'] ) ? wp_kses_post( wp_unslash( $_POST['admin_body'] ) ) : '';
		$notify_admin     = isset( $_POST['notify_admin'] ) ? sanitize_text_field( wp_unslash( $_POST['notify_admin'] ) ) : 'no';

		update_option( 'wps_delay_email_customer_subject', $customer_subject );
		update_option( 'wps_delay_email_customer_body', $customer_body );

		update_option( 'wps_delay_email_admin_subject', $admin_subject );
		update_option( 'wps_delay_email_admin_body', $admin_body );

		update_option( 'wps_tofw_notify_admin_delay', ( 'yes' === $notify_admin ) ? 'yes' : 'no' );

		wp_send_json_success( 'Saved' );
	}

		/**
		 * Decide which delay cron handler to execute based on HPOS availability.
		 *
		 * @return void
		 */
	public function wps_run_delay_cron_master() {

		// Detect HPOS.
		$is_hpos = (
		class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) &&
		\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
		);

		if ( $is_hpos ) {
			$this->wps_check_orders_for_delay_hpos();
		} else {
			$this->wps_check_orders_for_delay_legacy();
		}
	}



		/**
		 * 3. LEGACY MODE — Using wp_posts / wp_postmeta.
		 *
		 * Check legacy (posts/postmeta) orders for delayed deliveries.
		 *
		 * @return void
		 */
	public function wps_check_orders_for_delay_legacy() {
		global $wpdb;

		$batch_limit = 800;
		$wps_tofw_posts      = esc_sql( $wpdb->prefix . 'posts' );
		$wps_tofw_meta       = esc_sql( $wpdb->prefix . 'postmeta' );

			// NOTE: we do not filter by wps_tofw_delay_notified here.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- table names are escaped above.
			$wps_tofw_sql = $wpdb->prepare(
				"
			SELECT p.ID AS order_id
			FROM {$wps_tofw_posts} p
			INNER JOIN {$wps_tofw_meta} m1 ON m1.post_id = p.ID AND m1.meta_key = 'wps_tofw_estimated_delivery_date'
			INNER JOIN {$wps_tofw_meta} m2 ON m2.post_id = p.ID AND m2.meta_key = 'wps_tofw_estimated_delivery_time'
			WHERE p.post_type = 'shop_order'
			  AND p.post_status IN ('wc-pending','wc-processing','wc-on-hold')
			LIMIT %d
			",
				$batch_limit
			);

		$orders = $wpdb->get_results( $wps_tofw_sql );

		if ( empty( $orders ) ) {
			return;
		}

		foreach ( $orders as $row ) {
			$order = wc_get_order( $row->order_id );

			if ( $order ) {
				$this->wps_process_single_order_delay( $order );
			} else {
				error_log( 'Failed to load order object for ID: ' . $row->order_id );
			}
		}
	}



	/**
	 * Check HPOS (wc_orders/wc_orders_meta) orders for delayed deliveries.
	 *
	 * @return void
	 */
	public function wps_check_orders_for_delay_hpos() {
		global $wpdb;

		$batch_limit  = 800;
		$orders_table = esc_sql( $wpdb->prefix . 'wc_orders' );
		$hpos_meta    = esc_sql( $wpdb->prefix . 'wc_orders_meta' );
		$legacy_meta  = esc_sql( $wpdb->prefix . 'postmeta' );

		$valid_statuses       = array( 'wc-pending', 'wc-processing', 'wc-on-hold' );
		$status_placeholders  = implode( ',', array_fill( 0, count( $valid_statuses ), '%s' ) );

		// Hybrid query: check BOTH HPOS meta and legacy postmeta.
		// NOTE: we do not filter by wps_tofw_delay_notified here.
		$hpos_sql = "
			SELECT DISTINCT wco.id AS order_id
			FROM {$orders_table} wco

			/* HPOS meta joins */
			LEFT JOIN {$hpos_meta} h1 ON h1.order_id = wco.id AND h1.meta_key = 'wps_tofw_estimated_delivery_date'
			LEFT JOIN {$hpos_meta} h2 ON h2.order_id = wco.id AND h2.meta_key = 'wps_tofw_estimated_delivery_time'

			/* Legacy meta joins */
			LEFT JOIN {$legacy_meta} m1 ON m1.post_id = wco.id AND m1.meta_key = 'wps_tofw_estimated_delivery_date'
			LEFT JOIN {$legacy_meta} m2 ON m2.post_id = wco.id AND m2.meta_key = 'wps_tofw_estimated_delivery_time'

			WHERE wco.status IN ( {$status_placeholders} )

			  /* DATE must exist in either table */
			  AND ( h1.meta_value IS NOT NULL OR m1.meta_value IS NOT NULL )

			  /* TIME must exist in either table */
			  AND ( h2.meta_value IS NOT NULL OR m2.meta_value IS NOT NULL )

			LIMIT %d
		";

		$params = array_merge( $valid_statuses, array( $batch_limit ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- table names are escaped above.
		$wps_tofw_sql = call_user_func_array(
			array( $wpdb, 'prepare' ),
			array_merge(
				array( $hpos_sql ),
				$params
			)
		);

		$orders = $wpdb->get_results( $wps_tofw_sql );

		if ( empty( $orders ) ) {
			return;
		}

		foreach ( $orders as $row ) {
			$order = wc_get_order( $row->order_id );

			if ( $order ) {
				$this->wps_process_single_order_delay( $order );
			} else {
				error_log( 'Failed to load order object: ' . $row->order_id );
			}
		}
	}



		/**
		 * 5. DELAY CHECK — COMMON FOR BOTH HPOS & LEGACY.
		 * Handles:
		 *  - detecting if date/time changed.
		 *  - resetting notified flag.
		 *  - sending emails.
		 *
		 * @param WC_Order $order Order object being inspected.
		 * @return void
		 */
	public function wps_process_single_order_delay( $order ) {

		$order_id = $order->get_id();

		$date = $order->get_meta( 'wps_tofw_estimated_delivery_date' );
		$time = $order->get_meta( 'wps_tofw_estimated_delivery_time' );

		if ( ! $date || ! $time ) {
			return;
		}

		$expected_timestamp = strtotime( $date . ' ' . $time );
		$current_timestamp  = current_time( 'timestamp' );

		// Load meta.
		$last_expected_ts = $order->get_meta( 'wps_tofw_last_expected_ts' );
		$delay_sent       = $order->get_meta( 'wps_tofw_delay_notified' );

		// If expected date/time changed → reset notified flag.
		if ( $last_expected_ts && ( (int) $last_expected_ts !== (int) $expected_timestamp ) ) {
			error_log( "Order $order_id: Expected date/time changed. Resetting delay_notified." );
			$order->update_meta_data( 'wps_tofw_delay_notified', '' );
			$delay_sent = '';
		}

		// If already notified for this exact expected timestamp → stop.
		if ( '1' === $delay_sent ) {
			error_log( "Order $order_id already notified for this expected date. Skipping." );
			return;
		}

		// Delay condition.
		if ( $current_timestamp > $expected_timestamp ) {

			error_log( "Order $order_id is delayed. Sending notifications..." );

			$notify_admin = get_option( 'wps_tofw_notify_admin_delay', 'no' );

			$this->wps_send_delay_email_to_customer( $order, $date, $time );

			if ( 'yes' === $notify_admin ) {
				$this->wps_send_delay_email_to_admin( $order, $date, $time );
			}

			// Save meta.
			$order->update_meta_data( 'wps_tofw_delay_notified', '1' );
			$order->update_meta_data( 'wps_tofw_last_expected_ts', $expected_timestamp );
			$order->save();

			error_log( "Order $order_id delay flag updated." );
		} else {

			// Save latest expected timestamp so we detect changes next time.
			$order->update_meta_data( 'wps_tofw_last_expected_ts', $expected_timestamp );
			$order->save();

			error_log( "Order $order_id future delivery timestamp stored." );
		}
	}

		/**
		 * 6. CUSTOMER EMAIL.
		 *
		 * @param WC_Order $order Order object.
		 * @param string   $date  Expected date.
		 * @param string   $time  Expected time.
		 * @return void
		 */
	public function wps_send_delay_email_to_customer( $order, $date, $time ) {

		$mailer = WC()->mailer();

		$subject = get_option( 'wps_delay_email_customer_subject', 'Delivery Delay - Order {order_id}' );
		$body    = get_option( 'wps_delay_email_customer_body', '<p>Your delivery is delayed.</p>' );

		// Placeholder replacement.
		$placeholders = array(
			'{order_id}'        => $order->get_id(),
			'{customer_name}'   => $order->get_billing_first_name(),
			'{expected_date}'   => $date,
			'{expected_time}'   => $time,
			'{expected_datetime}' => $date . ' ' . $time,
			'{order_url}'       => $order->get_view_order_url(),
		);

		$subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $subject );
		$body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $body );

		// Wrap inside WooCommerce template.
		$heading = 'Delivery Delay Notification';
		$wrapped = $mailer->wrap_message( $heading, $body );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$mailer->send( $order->get_billing_email(), $subject, $wrapped, $headers );
	}



		/**
		 * 7. ADMIN EMAIL.
		 *
		 * @param WC_Order $order Order object.
		 * @param string   $date  Expected date.
		 * @param string   $time  Expected time.
		 * @return void
		 */
	public function wps_send_delay_email_to_admin( $order, $date, $time ) {

		if ( 'yes' !== get_option( 'wps_tofw_notify_admin_delay', 'no' ) ) {
			return;
		}

		$mailer = WC()->mailer();

		$subject = get_option( 'wps_delay_email_admin_subject', 'Order Delay - {order_id}' );
		$body    = get_option( 'wps_delay_email_admin_body', '<p>An order is delayed.</p>' );

		// Placeholder replacement.
		$placeholders = array(
			'{order_id}'        => $order->get_id(),
			'{customer_name}'   => $order->get_billing_first_name(),
			'{expected_date}'   => $date,
			'{expected_time}'   => $time,
			'{expected_datetime}' => $date . ' ' . $time,
			'{order_url}'       => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
		);

		$subject = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $subject );
		$body    = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $body );

		$heading = 'Order Delay Notice';
		$wrapped = $mailer->wrap_message( $heading, $body );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$mailer->send( get_option( 'admin_email' ), $subject, $wrapped, $headers );
	}

}
