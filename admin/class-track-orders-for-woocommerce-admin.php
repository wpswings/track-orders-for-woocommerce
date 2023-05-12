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
		
		if ( isset( $screen->id ) && ( 'wpswings_page_home' === $screen->id || 'wpswings_page_track_orders_for_woocommerce_menu' === $screen->id ) ) {
			// multistep form css.
			if ( ! tofw_wps_standard_check_multistep() ) {
				$style_url        = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'build/style-index.css';
				wp_enqueue_style(
					'wps-admin-react-styles',
					$style_url,
					array(),
					time(),
					false
				);
				return;
			}
			
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
		if ( isset( $screen->id ) && ( 'wpswings_page_home' === $screen->id || 'wpswings_page_track_orders_for_woocommerce_menu' === $screen->id ) ) {
			if ( ! tofw_wps_standard_check_multistep() ) {
				// js for the multistep from.
				$script_path      = '../../build/index.js';
				$script_asset_path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'build/index.asset.php';
				$script_asset      = file_exists( $script_asset_path )
					? require $script_asset_path
					: array(
						'dependencies' => array(
							'wp-hooks',
							'wp-element',
							'wp-i18n',
							'wc-components',
						),
						'version'      => filemtime( $script_path ),
					);
				$script_url        = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'build/index.js';
				wp_register_script(
					'react-app-block',
					$script_url,
					$script_asset['dependencies'],
					$script_asset['version'],
					true
				);
				wp_enqueue_script( 'react-app-block' );
				wp_localize_script(
					'react-app-block',
					'frontend_ajax_object',
					array(
						'ajaxurl'            => admin_url( 'admin-ajax.php' ),
						'wps_standard_nonce' => wp_create_nonce( 'ajax-nonce' ),
						'redirect_url' => admin_url( 'admin.php?page=track_orders_for_woocommerce_menu' ),
					)
				);
				return;
			}

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
				)
			);
			wp_enqueue_script( $this->plugin_name . 'admin-js' );
			wp_enqueue_script( 'wps-admin-min-js', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/wps-admin.min.js', array(), time(), false );

		}
	}

	/**
	 * Adding settings menu for Track Orders For Woocommerce.
	 *
	 * @since 1.0.0
	 */
	public function tofw_options_page() {
		global $submenu;
		if ( empty( $GLOBALS['admin_page_hooks']['wps-plugins'] ) ) {
			add_menu_page( 'WPSwings', 'WPSwings', 'manage_options', 'wps-plugins', array( $this, 'wps_plugins_listing_page' ), TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/wps_Grey-01.svg', 15 );
			if ( tofw_wps_standard_check_multistep() ) {
				add_submenu_page( 'wps-plugins', 'Home', 'Home', 'manage_options', 'home', array( $this, 'wpswings_welcome_callback_function' ) );
			}
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
					if ( $value[0] == 'Home') {
						$is_home =true;
					}
				}
				if ( ! $is_home ) {
					if ( tofw_wps_standard_check_multistep() ) {
						add_submenu_page( 'wps-plugins', 'Home', 'Home', 'manage_options', 'home', array( $this, 'wpswings_welcome_callback_function' ), 1 );
					}
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
				'title' => __( 'Enable plugin', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Enable plugin to start the functionality.', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_is_plugin_enable',
				'value' => get_option( 'wps_tofw_is_plugin_enable' ),
				'class' => 'wps_tofw_is_plugin_enable',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable Order tracking using order id only', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'In Default case, guest user can track order using email and order id. Enable this to track order using order id only..', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_enable_track_with_order_id',
				'value' => get_option( 'wps_tofw_enable_track_with_order_id' ),
				'class' => 'wps_tofw_enable_track_with_order_id',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable logged-in user to EXPORT ORDER', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Logged-in user can export order from my-account->order sections', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_enable_login_export',
				'value' => get_option( 'wps_tofw_enable_login_export' ),
				'class' => 'wps_tofw_enable_login_export',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable Guest user to EXPORT ORDER', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Guest user can export order from guest tracking page', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_enable_guest_export',
				'value' => get_option( 'wps_tofw_enable_guest_export' ),
				'class' => 'wps_tofw_enable_guest_export',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable use of icon for order status', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Enable this to show icon instead of text for order status in order table.', 'track-orders-for-woocommerce' ),
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
				'description'  => __( 'Enable to send the e-mail notification to the customer on changing order status', 'track-orders-for-woocommerce' ),
				'id'    => 'wps_tofw_enable_email_notification',
				'value' => get_option( 'wps_tofw_enable_email_notification' ),
				'class' => 'wps_tofw_enable_email_notification',
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
			'button_text' => __( 'Save Settings', 'mwb-bookings-for-woocommerce' ),
			'class'       => 'wps_tofw_general_settings_save',
			'name'        => 'wps_tofw_general_settings_save',
		);
		return $tofw_settings_general;
	}

	/**
	 * Track Orders For Woocommerce admin menu page.
	 *
	 * @since 1.0.0
	 * @param array $tofw_settings_template Settings fields.
	 */
	public function tofw_track_order_settings_page( $tofw_track_order_settings ) {
	
		$custom_order_status = get_option( 'wps_tofw_new_custom_order_status', array() );
		$selected_order_status = get_option( 'tofw_selected_custom_order_status' );
		$new_order_statues = array();
		$order_status = array(
			'wc-packed' => __( 'Order Packed', 'woocommerce-order-tracker' ),
			'wc-dispatched' => __( 'Order Dispatched', 'woocommerce-order-tracker' ),
			'wc-shipped' => __( 'Order Shipped', 'woocommerce-order-tracker' ),
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
				if(key_exists( $value,$order_status  ) ) {
					$new_order_statues[$value] = $order_status[$value];
				}
				
			}
		}
		$wc_get_order_statuses = wc_get_order_statuses();
		unset($wc_get_order_statuses['wc-completed']);
		$order_status = array_merge( $new_order_statues, $wc_get_order_statuses  );

		$tofw_track_order_settings = array(
			
			array(
				'title' => __( 'Enable track orders Feature', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Enable Track Your Order Feature.', 'track-orders-for-woocommerce' ),
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
				'description'  => __( 'Enable this setting to use custom status to enhance order tracking.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_enable_use_custom_status',
				'value' => get_option( 'tofw_enable_use_custom_status' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			

			array(
				'title' => __( 'Approval', 'track-orders-for-woocommerce' ),
				'type'  => 'multiselect',
				'description'  => __( 'Select Custom status to enhance tracking.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_approval_order_status',
				'value' => get_option( 'tofw_approval_order_status' ),
				'class' => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => '',
				'options' => $order_status,
			),
			array(
				'title' => __( 'Processing', 'track-orders-for-woocommerce' ),
				'type'  => 'multiselect',
				'description'  => __( 'Select Custom status to enhance tracking.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_processing_order_status',
				'value' => get_option( 'tofw_processing_order_status' ),
				'class' => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => '',
				'options' => $order_status,
			),
			array(
				'title' => __( 'Shipping', 'track-orders-for-woocommerce' ),
				'type'  => 'multiselect',
				'description'  => __( 'Select Custom status to enhance tracking.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_shipping_order_status',
				'value' => get_option( 'tofw_shipping_order_status' ),
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
		apply_filters( 'tofw_general_settings_array_filter', $tofw_track_order_settings );

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
	 * @param array $tofw_custom_order_status_settings contains array of settings
	 * @return void
	 */
	public function tofw_custom_order_status_setting_page($tofw_custom_order_status_settings){
		$custom_order_status = get_option( 'wps_tofw_new_custom_order_status', array() );
		$order_status = array(
			'wc-packed' => __( 'Order Packed', 'woocommerce-order-tracker' ),
			'wc-dispatched' => __( 'Order Dispatched', 'woocommerce-order-tracker' ),
			'wc-shipped' => __( 'Order Shipped', 'woocommerce-order-tracker' ),
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
				'description'  => __( 'Select Custom status to use in tracking.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_selected_custom_order_status',
				'value' => get_option( 'tofw_selected_custom_order_status' ),
				'class' => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => '',
				'options' => $order_status,
			),
			array(
				'title' =>'' ,
				'type'  => 'custom_status',
				'description'  =>'',
				'id'    => 'custom_staus',
				'value' =>'',
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
	public function tofw_track_order_gmap_settings_callback($tofw_track_order_gmap_settings){
		$tofw_track_order_gmap_settings = array(
				array(
					'title' => __( 'Enable Google Map For Tracking', 'track-orders-for-woocommerce' ),
					'type'  => 'radio-switch',
					'description'  => __( 'Enable Tracking Your Order With Google Map Api.', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_tofw_trackorder_with_google_map',
					'value' => get_option( 'wps_tofw_trackorder_with_google_map' ),
					'class' => 'tofw-radio-switch-class',
					'options' => array(
						'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
						'no' => __( 'NO', 'track-orders-for-woocommerce' ),
					),
				),
				array(
					'title' => __( 'Enter Google Map Api Key', 'track-orders-for-woocommerce' ),
					'type'  => 'text',
					'description'  => __( 'Enter Google Map Api Key.', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_tofw_track_order_google_map_api_key',
					'value' => get_option( 'wps_tofw_track_order_google_map_api_key' ),
					'class' => '',
					'style' => 'width:10em;',
					
				),
				array(
					'title' => __( 'Enter Order Production House Address', 'track-orders-for-woocommerce' ),
					'type'  => 'text',
					'description'  => __( 'Enter your order production house address.', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_tofw_track_order_production_address',
					'value' => get_option( 'wps_tofw_track_order_production_address' ),
					'class' => '',
					'style' => 'width:10em;',
					
				),
				array(
					'title' => __( 'Enter Addresses From Where Your Order Has Gone Through', 'track-orders-for-woocommerce' ),
					'type'  => 'text',
					'description'  => __( 'Enter the addresses one by one from where your order has gone through.', 'track-orders-for-woocommerce' ),
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
					'description'  => __( 'Select Custom status to enhance tracking.', 'track-orders-for-woocommerce' ),
					'id'    => 'wps_tofw_selected_address',
					'value' => get_option( 'wps_tofw_selected_address' ),
					'class' => 'tofw-multiselect-class wps-defaut-multiselect',
					'placeholder' => 'Select Your Hubpoint Addresses',
					'options' => get_option( 'wps_tofw_old_addresses') ,
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
		if (  ! isset( $_POST['wps_tabs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['wps_tabs_nonce'] ), 'admin_save_data' ) )  {
			return;
		}
		
		if ( isset( $_POST['tofw_button_demo'] ) ) {
			
			$screen = get_current_screen();
			if ( isset( $screen->id ) && 'wp-swings_page_home' === $screen->id ) { 

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
		
		if( $wps_settings_save_progress ){
			
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
								update_option( $tofw_genaral_setting['id'], is_array( $_POST[ $tofw_genaral_setting['id'] ] ) ? $this->wps_sanitize_array( $_POST[ $tofw_genaral_setting['id'] ] ) : sanitize_text_field( $_POST[ $tofw_genaral_setting['id'] ] ) );
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
	 * @param $array is the array data.
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
	public function wps_tofw_create_custom_order_status_callback(){
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

		esc_html_e( 'success', 'woocommerce-order-tracker' );
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
				esc_html_e( 'success', 'woocommerce-order-tracker' );
			} else {
				esc_html_e( 'failed', 'woocommerce-order-tracker' );
			}

			wp_die();
		}
	}

	/**
	 * Function for ajax select template.
	 *
	 * @return void
	 */
	public function wps_selected_template_callback(){
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$selected_template_name = isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '';
		update_option( 'wps_tofw_activated_template', $selected_template_name );
		esc_html_e( 'success', 'woocommerce-order-tracker' );
		wp_die();
	}

	/**
	 * Function for ajax insert address.
	 *
	 * @return void
	 */
	public function wps_tofw_insert_address_for_tracking(){
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$wps_tofw_address_collections = isset( $_POST['wps_tofw_addresses'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_addresses'] ) ) : '';
		$wps_tofw_previous_address = get_option( 'wps_tofw_old_addresses', array() );
		if ( is_array( $wps_tofw_previous_address ) ) {

			$wps_tofw_previous_address[ 'wps_address_' . $wps_tofw_address_collections ] = $wps_tofw_address_collections;
			update_option( 'wps_tofw_old_addresses', $wps_tofw_previous_address );
		}

		$wps_tofw_address_array_value = get_option( 'wps_tofw_old_addresses', false );
		echo json_encode( $wps_tofw_address_array_value );
		wp_die();
	}

}
