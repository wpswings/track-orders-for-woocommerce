<?php
/**
 * The common functionality of the plugin.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/common
 */

/**
 * The common functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the common stylesheet and JavaScript.
 * namespace track_orders_for_woocommerce_common.
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/common
 */
class Track_Orders_For_Woocommerce_Common {
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the common side of the site.
	 *
	 * @since    1.0.0
	 */
	public function tofw_common_enqueue_styles() {
		wp_enqueue_style( $this->plugin_name . 'common', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'common/css/track-orders-for-woocommerce-common.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the common side of the site.
	 *
	 * @since    1.0.0
	 */
	public function tofw_common_enqueue_scripts() {
		wp_register_script( $this->plugin_name . 'common', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'common/js/track-orders-for-woocommerce-common.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name . 'common', 'tofw_common_param', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( $this->plugin_name . 'common' );
	}

	/**
	 * Validating wpswings license
	 *
	 * @since    1.0.0
	 */
	public function wps_tofw_validate_license_key() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );
		$wps_tofw_purchase_code = ( ! empty( $_POST['purchase_code'] ) ) ? sanitize_text_field( wp_unslash( $_POST['purchase_code'] ) ) : '';
		$wps_tofw_response = self::track_orders_for_woocommerce_license_code_update( $wps_tofw_purchase_code );
		if ( is_wp_error( $wps_tofw_response ) ) {
			echo json_encode(
				array(
					'status' => false,
					'msg' => __(
						'An unexpected error occurred. Please try again.',
						'track-orders-for-woocommerce'
					),
				)
			);
		} else {
			$wps_tofw_license_data = json_decode( wp_remote_retrieve_body( $wps_tofw_response ) );

			if ( isset( $wps_tofw_license_data->result ) && 'success' === $wps_tofw_license_data->result ) {
				update_option( 'wps_tofw_license_key', $wps_tofw_purchase_code );
				update_option( 'wps_tofw_license_check', true );

				echo json_encode(
					array(
						'status' => true,
						'msg' => __(
							'Successfully Verified. Please Wait.',
							'track-orders-for-woocommerce'
						),
					)
				);
			} else {
				echo json_encode(
					array(
						'status' => false,
						'msg' => $wps_tofw_license_data->message,
					)
				);
			}
		}
		wp_die();
	}

	/**
	 * Function is used for the sending the track data.
	 * 
	 * @name tofw_wpswings_tracker_send_event.
	 * @since 1.0.0
	*/
	public function tofw_wpswings_tracker_send_event( $override = false ) {
		require_once WC()->plugin_path() . '/includes/class-wc-tracker.php';

		$last_send = get_option( 'wpswings_tracker_last_send' );
		if ( ! apply_filters( 'wpswings_tracker_send_override', $override ) ) {
			// Send a maximum of once per week by default.
			$last_send = $this->wps_tofw_last_send_time();
			if ( $last_send && $last_send > apply_filters( 'wpswings_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {
				return;
			}
		} else {
			// Make sure there is at least a 1 hour delay between override sends, we don't want duplicate calls due to double clicking links.
			$last_send = $this->wps_tofw_last_send_time();
			if ( $last_send && $last_send > strtotime( '-1 hours' ) ) {
				return;
			}
		}
		$api_route = '';
		$api_route = 'mp';
		$api_route .= 's';
		// Update time first before sending to ensure it is set.
		update_option( 'wpswings_tracker_last_send', time() );
		$params = WC_Tracker::get_tracking_data();
		$params = apply_filters( 'wpswings_tracker_params', $params );
		$api_url = 'https://tracking.wpswings.com/wp-json/' . $api_route . '-route/v1/' . $api_route . '-testing-data/';
		$sucess = wp_safe_remote_post(
			$api_url,
			array(
				'method'      => 'POST',
				'body'        => wp_json_encode( $params ),
			)
		);
	}

	/**
	 * Get the updated time.
	 *
	 * @name wps_tofw_last_send_time
	 *
	 * @since 1.0.0
	 */
	public function wps_tofw_last_send_time() {
		return apply_filters( 'wpswings_tracker_last_send_time', get_option( 'wpswings_tracker_last_send', false ) );
	}

	/**
	 * Update the option for settings from the multistep form.
	 *
	 * @name tofw_wps_standard_save_settings_filter
	 * @since 1.0.0
	 */
	public function tofw_wps_standard_save_settings_filter() {
		check_ajax_referer( 'ajax-nonce', 'nonce' );

		$term_accpted = ! empty( $_POST['consetCheck'] ) ? sanitize_text_field( wp_unslash( $_POST['consetCheck'] ) ) : ' ';
		if ( ! empty( $term_accpted ) && 'yes' == $term_accpted ) {
			update_option( 'tofw_enable_tracking', 'on' );
		}
		// settings fields.
		$first_name = ! empty( $_POST['firstName'] ) ? sanitize_text_field( wp_unslash( $_POST['firstName'] ) ) : '';
		update_option( 'firstname', $first_name );

		$email = ! empty( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
		update_option( 'email', $email );

		$desc = ! empty( $_POST['desc'] ) ? sanitize_text_field( wp_unslash( $_POST['desc'] ) ) : '';
		update_option( 'desc', $desc );

		$age = ! empty( $_POST['age'] ) ? sanitize_text_field( wp_unslash( $_POST['age'] ) ) : '';
		update_option( 'age', $age );

		$first_checkbox = ! empty( $_POST['FirstCheckbox'] ) ? sanitize_text_field( wp_unslash( $_POST['FirstCheckbox'] ) ) : '';
		update_option( 'first_checkbox', $first_checkbox );

		$checked_first_switch = ! empty( $_POST['checkedA'] ) ? sanitize_text_field( wp_unslash( $_POST['checkedA'] ) ) : '';
		if ( ! empty( $checked_first_switch ) && $checked_first_switch ) {
			update_option( 'tofw_radio_switch_demo', 'on' );
		}

		$checked_second_switch = ! empty( $_POST['checkedB'] ) ? sanitize_text_field( wp_unslash( $_POST['checkedB'] ) ) : '';
		if ( ! empty( $checked_second_switch ) && $checked_second_switch ) {
			update_option( 'tofw_radio_reset_license', 'on' );
		}
		update_option( 'wps_track_orders_for_woocommerce_multistep_done', 'yes' );

		$wps_tofw_purchase_code = ! empty( $_POST['licenseCode'] ) ? sanitize_text_field( wp_unslash( $_POST['licenseCode'] ) ) : '';

		$wps_tofw_response = self::track_orders_for_woocommerce_license_code_update( $wps_tofw_purchase_code );
		if ( is_wp_error( $wps_tofw_response ) ) {
			wp_send_json( 'license_could_not_be_verified' );
		} else {
			$wps_tofw_license_data = json_decode( wp_remote_retrieve_body( $wps_tofw_response ) );
			if ( isset( $wps_tofw_license_data->result ) && 'success' === $wps_tofw_license_data->result ) {
				update_option( 'wps_tofw_license_key', $wps_tofw_purchase_code );
				update_option( 'wps_tofw_license_check', true );
			}
		}
		wp_send_json( 'yes' );
	}

	/**
	 * Function is used to verify the license code.
	 * 
	 * @name track_orders_for_woocommerce_license_code_update
	 * @since 1.0.0
	 * @param String $license_code.
	*/
	public static function track_orders_for_woocommerce_license_code_update( $license_code ) {
		$wps_server_name = ( !empty( $_SERVER['SERVER_NAME'] ) ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';
		$api_params = array(
			'slm_action'        => 'slm_activate',
			'secret_key'        => TRACK_ORDERS_FOR_WOOCOMMERCE_SPECIAL_SECRET_KEY,
			'license_key'       => $license_code,
			'_registered_domain' => $wps_server_name,
			'item_reference'    => urlencode( TRACK_ORDERS_FOR_WOOCOMMERCE_ITEM_REFERENCE ),
			'product_reference' => 'wpsPK-2965',
		);

		$query = esc_url_raw( add_query_arg( $api_params, TRACK_ORDERS_FOR_WOOCOMMERCE_LICENSE_SERVER_URL ) );

		$wps_tofw_response = wp_remote_get(
			$query,
			array(
				'timeout' => 20,
				'sslverify' => false,
			)
		);
		return $wps_tofw_response;
	}
}
