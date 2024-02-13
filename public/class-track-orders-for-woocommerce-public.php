<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 * namespace track_orders_for_woocommerce_public.
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/public
 */
class Track_Orders_For_Woocommerce_Public {

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
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function tofw_public_enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/css/track-orders-for-woocommerce-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function tofw_public_enqueue_scripts() {

		wp_register_script( $this->plugin_name, TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/js/track-orders-for-woocommerce-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'tofw_public_param', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( $this->plugin_name );
		if ( 0 <= strpos( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', '/track-your-order' ) ) {
			$wps_tofw_google_api_key = get_option( 'wps_tofw_google_api_key', '' );
			wp_enqueue_script( 'wps_new_road_map_script', 'https://maps.googleapis.com/maps/api/js?key= ' . $wps_tofw_google_api_key, '', $this->version, true );

			wp_register_script( 'wps-public', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/js/wps-public.js', array( 'jquery' ), $this->version, false );
			wp_localize_script( 'wps-public', 'wps_public_param', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_script( 'wps-public' );
		}

	}


	/**
	 * This function is for rendering track order button
	 *
	 * @link http://www.wpswings.com/
	 * @param object $order is a object.
	 */
	public function wps_tofw_track_order_button( $order ) {
		if ( '3.0.0' > WC()->version ) {
			$order_id = $order->id;
		} else {
			$order_id = $order->get_id();
		}
		$wps_tofw_enable_track_order_feature = get_option( 'tofw_enable_track_order', 'no' );
		if ( 'on' != $wps_tofw_enable_track_order_feature ) {
			return;
		}
		$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
		$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
		$track_order_url = get_permalink( $page_id );
		?>
		<p><label class="wps_enhanced_order_note"><?php esc_html_e( 'Note:', 'track-orders-for-woocommerce' ); ?></label><span class="wps_order_note_text"><?php esc_html_e( 'Click The Below To Track Your Order', 'track-orders-for-woocommerce' ); ?></span></p>
			<a href="<?php echo esc_attr( $track_order_url ) . '?' . esc_attr( $order_id ); ?>" class="button button-primary"><?php esc_html_e( 'TRACK ORDER', 'track-orders-for-woocommerce' ); ?></a>
		<?php

	}

	/**
	 * Function to add track order button.
	 *
	 * @param array  $actions is an array.
	 * @param object $order is the object.
	 * @return array
	 */
	public function wps_tofw_add_track_order_button_on_orderpage( $actions, $order ) {
		$wps_tofw_enable_track_order_feature = get_option( 'tofw_enable_track_order', 'no' );
		if ( 'on' != $wps_tofw_enable_track_order_feature ) {
			return $actions;
		}

		$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
		$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
		if ( '3.0.0' > WC()->version ) {
			$order_id = $order->id;
			$track_order_url = get_permalink( $page_id );
			$actions['wps_track_order']['url']  = $track_order_url . '?' . $order_id;
			$actions['wps_track_order']['name']     = __( 'Track Order', 'track-orders-for-woocommerce' );
		} else {
			$order_id = $order->get_id();
			$track_order_url = get_permalink( $page_id );
			$actions['wps_track_order']['url']  = $track_order_url . '?' . $order_id;
			$actions['wps_track_order']['name']     = __( 'Track Order', 'track-orders-for-woocommerce' );
		}

		return $actions;
	}

	/**
	 * Function to export.
	 *
	 * @return void
	 */
	public function wps_wot_add_export_button_before_order_table() {
		if ( 'on' == get_option( 'wps_tofw_enable_login_export' ) ) {

			?>
				<button class="wps_export woocommerce-button"><?php esc_html_e( 'Export Orders', 'track-orders-for-woocommerce' ); ?></button>
			<?php

		}
	}

	/**
	 * This function is to create template for track order
	 *
	 * @link http://www.wpswings.com/
	 * @param string $template is the contains path.
	 * @return string
	 */
	public function wps_tofw_include_track_order_page( $template ) {
		$selected_template = get_option( 'wps_tofw_activated_template' );
		$wps_tofw_google_map_setting = get_option( 'wps_tofw_trackorder_with_google_map', false );
		$wps_tofw_enable_track_order_feature = get_option( 'tofw_enable_track_order', 'no' );
		if ( 'on' != $wps_tofw_enable_track_order_feature ) {
			return $template;
		}
		$page_id = '';
		if ( 'on' == $wps_tofw_enable_track_order_feature && 'on' == $wps_tofw_google_map_setting ) {
			$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
			$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
			if ( is_page( $page_id ) ) {
				$new_template = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'template/wps-map-new-template.php';
				$template = $new_template;
			}
		} else {

			$wps_tofw_pages = get_option( 'wps_tofw_tracking_page', false );
			if ( is_array( $wps_tofw_pages ) && isset( $wps_tofw_pages['pages'] ) && is_array( $wps_tofw_pages['pages'] ) ) {
				// Access the page_id only if the structure is as expected.
				$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
			}
			if ( is_page( $page_id ) && isset( $page_id ) ) {
				if ( ' ' != $selected_template && null != $selected_template ) {
					$path = '';
					if ( ( 'template4' === $selected_template || 'newtemplate1' === $selected_template || 'newtemplate2' === $selected_template || 'newtemplate3' === $selected_template ) && ( is_plugin_active( 'track-orders-for-woocommerce-pro/track-orders-for-woocommerce-pro.php' ) ) ) {
						$path = TRACK_ORDERS_FOR_WOOCOMMERCE_PRO_DIR_PATH;
					} else {
						$path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH;
					}
					$new_template = $path . 'template/wps-track-order-myaccount-page-' . $selected_template . '.php';
					$template = $new_template;
				} else {
					$new_template = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'template/wps-track-order-myaccount-page-template1.php';
					$template = $new_template;
				}
			}
		}

		return $template;
	}


	/**
	 * This function is to create template for track order
	 *
	 * @link http://www.wpswings.com/
	 * @param string $template is the contains path.
	 * @return string
	 */
	public function wps_tofw_include_guest_track_order_page( $template ) {
		$wps_tofw_enable_track_order_feature = get_option( 'tofw_enable_track_order', 'no' );
		if ( 'on' != $wps_tofw_enable_track_order_feature ) {
			return $template;
		}
		$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
		$page_id = $wps_tofw_pages['pages']['wps_guest_track_order_page'];
		if ( is_page( $page_id ) ) {
			$new_template = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'template/wps-guest-track-order-page.php';
			$template = $new_template;
		}

		return $template;
	}


	/**
	 * This function is to create template for FedEX tracking of Order
	 *
	 * @link http://www.wpswings.com/
	 * @param string $template is the contains path.
	 * @return string
	 */
	public function wps_ordertracking_page( $template ) {
		$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
		$page_id = $wps_tofw_pages['pages']['wps_fedex_track_order'];
		if ( is_page( $page_id ) ) {
			$new_template = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'template/wps-order-tracking-page.php';
			$template = $new_template;
		}

		return $template;
	}

	/**
	 * This function is for rendering track order button
	 *
	 * @link http://www.wpswings.com/
	 * @param object $order is a object.
	 */
	public function wps_tofw_track_order_info( $order ) {
		if ( '3.0.0' > WC()->version ) {
			$order_id = $order->id;
		} else {
			$order_id = $order->get_id();
		}
		$wps_tofw_enable_track_order_feature = get_option( 'tofw_enable_track_order', 'no' );

		if ( 'on' != $wps_tofw_enable_track_order_feature ) {
			return;
		}

		$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
		$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
		$track_order_url = get_permalink( $page_id );
		$wps_tofw_enable_track_order_api = get_option( 'wps_tofw_enable_third_party_tracking_api', 'no' );
		if ( 'on' == $wps_tofw_enable_track_order_api ) {

			$wps_shipping_service = get_post_meta( $order_id, 'wps_tofw_selected_shipping_service', true );
			if ( 'canada_post' === $wps_shipping_service ) {
				$wps_shipping_service = 'Canada Post';
			} else if ( 'fedex' === $wps_shipping_service ) {
				$wps_shipping_service = 'FedEx';
			} else if ( 'usps' === $wps_shipping_service ) {
				$wps_shipping_service = 'USPS';
			}
			$wps_est_delivery_date = get_post_meta( $order_id, 'wps_tofw_estimated_delivery_date', true );
			$wps_est_delivery_time = get_post_meta( $order_id, 'wps_tofw_estimated_delivery_time', true );
			$wps_tyo_tracking_number = get_post_meta( $order_id, 'wps_tofw_package_tracking_number', true );
			?>
			<div style="background-color: rgba(246,246,246,255);padding: 20px;">
			<h3 style="font-weight:500"><?php esc_html_e( 'Tracking Info', 'track-orders-for-woocommerce' ); ?></h3>
			<p>
				<?php esc_html_e( 'Order picked up by ', 'track-orders-for-woocommerce' ); ?><b><?php echo esc_html( $wps_shipping_service ); ?></b><br>
				<?php esc_html_e( 'Estimated Delivery Date : ', 'track-orders-for-woocommerce' ); ?><b>
							  <?php
								echo esc_html( $wps_est_delivery_date );
								echo ' ';
								echo esc_html( $wps_est_delivery_time );
								?>
				</b><br>
				<?php esc_html_e( 'Tracking Code : ', 'track-orders-for-woocommerce' ); ?><b><?php echo esc_html( $wps_tyo_tracking_number ); ?></b>
			</p>
				<a href="<?php echo esc_attr( $track_order_url ) . '?' . esc_attr( $order_id ); ?>" ><?php esc_html_e( 'track your order', 'track-orders-for-woocommerce' ); ?></a>
			</div>
				<?php
		}
	}

}
