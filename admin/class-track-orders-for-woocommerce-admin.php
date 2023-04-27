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
		
		if ( isset( $screen->id ) && ( 'wpswings_page_home' === $screen->id || 'wp-swings_page_track_orders_for_woocommerce_menu' === $screen->id ) ) {
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
					'wps_standard_nonce' => wp_create_nonce( 'ajax-nonce' ),
					'reloadurl' => admin_url( 'admin.php?page=track_orders_for_woocommerce_menu' ),
					'msp_gen_tab_enable' => get_option( 'tofw_radio_switch_demo' ),
					'tofw_admin_param_location' => ( admin_url( 'admin.php' ) . '?page=track_orders_for_woocommerce_menu&tofw_tab=track-orders-for-woocommerce-general' ),
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
				'id'    => 'tofw_radio_switch_demo',
				'value' => get_option( 'tofw_radio_switch_demo' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Reset License', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'Enable to reset the license on deactivation of the plugin.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_radio_reset_license',
				'value' => get_option( 'tofw_radio_reset_license' ),
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'type'  => 'button',
				'id'    => 'tofw_button_demo',
				'button_text' => __( 'Button Demo', 'track-orders-for-woocommerce' ),
				'class' => 'tofw-button-class',
			),
		);
		return $tofw_settings_general;
	}

	/**
	 * Track Orders For Woocommerce admin menu page.
	 *
	 * @since 1.0.0
	 * @param array $tofw_settings_template Settings fields.
	 */
	public function tofw_admin_template_settings_page( $tofw_settings_template ) {
		$tofw_settings_template = array(
			array(
				'title' => __( 'Text Field Demo', 'track-orders-for-woocommerce' ),
				'type'  => 'text',
				'description'  => __( 'This is text field demo follow same structure for further use.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_text_demo',
				'value' => '',
				'class' => 'tofw-text-class',
				'placeholder' => __( 'Text Demo', 'track-orders-for-woocommerce' ),
			),
			array(
				'title' => __( 'Number Field Demo', 'track-orders-for-woocommerce' ),
				'type'  => 'number',
				'description'  => __( 'This is number field demo follow same structure for further use.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_number_demo',
				'value' => '',
				'class' => 'tofw-number-class',
				'placeholder' => '',
			),
			array(
				'title' => __( 'Password Field Demo', 'track-orders-for-woocommerce' ),
				'type'  => 'password',
				'description'  => __( 'This is password field demo follow same structure for further use.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_password_demo',
				'value' => '',
				'class' => 'tofw-password-class',
				'placeholder' => '',
			),
			array(
				'title' => __( 'Textarea Field Demo', 'track-orders-for-woocommerce' ),
				'type'  => 'textarea',
				'description'  => __( 'This is textarea field demo follow same structure for further use.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_textarea_demo',
				'value' => '',
				'class' => 'tofw-textarea-class',
				'rows' => '5',
				'cols' => '10',
				'placeholder' => __( 'Textarea Demo', 'track-orders-for-woocommerce' ),
			),
			array(
				'title' => __( 'Select Field Demo', 'track-orders-for-woocommerce' ),
				'type'  => 'select',
				'description'  => __( 'This is select field demo follow same structure for further use.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_select_demo',
				'value' => '',
				'class' => 'tofw-select-class',
				'placeholder' => __( 'Select Demo', 'track-orders-for-woocommerce' ),
				'options' => array(
					'' => __( 'Select option', 'track-orders-for-woocommerce' ),
					'INR' => __( 'Rs.', 'track-orders-for-woocommerce' ),
					'USD' => __( '$', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Multiselect Field Demo', 'track-orders-for-woocommerce' ),
				'type'  => 'multiselect',
				'description'  => __( 'This is multiselect field demo follow same structure for further use.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_multiselect_demo',
				'value' => '',
				'class' => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => '',
				'options' => array(
					'default' => __( 'Select currency code from options', 'track-orders-for-woocommerce' ),
					'INR' => __( 'Rs.', 'track-orders-for-woocommerce' ),
					'USD' => __( '$', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Checkbox Field Demo', 'track-orders-for-woocommerce' ),
				'type'  => 'checkbox',
				'description'  => __( 'This is checkbox field demo follow same structure for further use.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_checkbox_demo',
				'value' => '',
				'class' => 'tofw-checkbox-class',
				'placeholder' => __( 'Checkbox Demo', 'track-orders-for-woocommerce' ),
			),

			array(
				'title' => __( 'Radio Field Demo', 'track-orders-for-woocommerce' ),
				'type'  => 'radio',
				'description'  => __( 'This is radio field demo follow same structure for further use.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_radio_demo',
				'value' => '',
				'class' => 'tofw-radio-class',
				'placeholder' => __( 'Radio Demo', 'track-orders-for-woocommerce' ),
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title' => __( 'Enable', 'track-orders-for-woocommerce' ),
				'type'  => 'radio-switch',
				'description'  => __( 'This is switch field demo follow same structure for further use.', 'track-orders-for-woocommerce' ),
				'id'    => 'tofw_radio_switch_demo',
				'value' => '',
				'class' => 'tofw-radio-switch-class',
				'options' => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no' => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),

			array(
				'type'  => 'button',
				'id'    => 'tofw_button_demo',
				'button_text' => __( 'Button Demo', 'track-orders-for-woocommerce' ),
				'class' => 'tofw-button-class',
			),
		);
		return $tofw_settings_template;
	}

	/**
	 * Track Orders For Woocommerce save tab settings.
	 *
	 * @since 1.0.0
	 */
	public function tofw_admin_save_tab_settings() {
		global $wps_tofw_obj;

		if ( isset( $_POST['tofw_button_demo'] )
			&& ( ! empty( $_POST['wps_tabs_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( $_POST['wps_tabs_nonce'] ), 'admin_save_data' ) )
		) {

			$enable_tracking = ! empty( $_POST['tofw_enable_tracking'] ) ? sanitize_text_field( wp_unslash( $_POST['tofw_enable_tracking'] ) ) : '';
			update_option( 'tofw_enable_tracking', $enable_tracking );

			$wps_msp_gen_flag     = false;
			$tofw_genaral_settings =
			// desc - filter for trial.
			apply_filters( 'tofw_general_settings_array', array() );
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
	 * Checking wpswings license on daily basis
	 *
	 * @since 1.0.0
	 */

	public function wps_tofw_check_license() {

		$user_license_key = get_option( 'wps_tofw_license_key', '' );

		$api_params = array(
			'slm_action'        => 'slm_check',
			'secret_key'        => TRACK_ORDERS_FOR_WOOCOMMERCE_SPECIAL_SECRET_KEY,
			'license_key'       => $user_license_key,
			'_registered_domain' => $_SERVER['SERVER_NAME'],
			'item_reference'    => urlencode( TRACK_ORDERS_FOR_WOOCOMMERCE_ITEM_REFERENCE ),
			'product_reference' => 'wpsPK-2965',
		);

		$query = esc_url_raw( add_query_arg( $api_params, TRACK_ORDERS_FOR_WOOCOMMERCE_LICENSE_SERVER_URL ) );
		$wps_response = wp_remote_get(
			$query,
			array(
				'timeout' => 20,
				'sslverify' => false,
			)
		);
		$license_data = json_decode( wp_remote_retrieve_body( $wps_response ) );

		if ( isset( $license_data->result ) && 'success' === $license_data->result && isset( $license_data->status ) && 'active' === $license_data->status ) {

			update_option( 'wps_tofw_license_check', true );

		} else {

			delete_option( 'wps_tofw_license_check' );
		}
	}
}
