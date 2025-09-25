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
		 $selected_template = get_option( 'wps_tofw_activated_template' );
		wp_register_script( $this->plugin_name, TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/js/track-orders-for-woocommerce-public.js', array( 'jquery' ), time(), false );
		wp_localize_script(
			$this->plugin_name,
			'tofw_public_param',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'wps_activated_template' => $selected_template,
				'carrier_auth_nonce' => wp_create_nonce( 'wps_muilt_carrier' ),
				'mutlple_carrer_image' => array(
					'wps_packed' => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/image/packed.svg',
					'wps_in_way' => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/image/in-way.svg',
					'wps_delivered' => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/image/delivered.svg',
				),
			)
		);
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
		if ( version_compare( WC()->version, '3.0.0', '<' ) ) {
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

		$tofw_enable_track_order_below = get_option( 'tofw_enable_track_order_below' );
		$tofw_enable_track_order_below_text = get_option( 'tofw_enable_track_order_below_text', __( 'Track Order', 'track-orders-for-woocommerce' ) );
		$tofw_enable_track_order_below_textarea = get_option( 'tofw_enable_track_order_below_textarea', __( 'Click The Below To Track Your Order', 'track-orders-for-woocommerce' ) );
		$wps_tofwp_enable_track_order_popup = get_option( 'wps_tofwp_enable_track_order_popup', 'no' );
		if ( 'on' == $tofw_enable_track_order_below ) {

			?>
			<p><label class="wps_enhanced_order_note"><?php esc_html_e( 'Note: ', 'track-orders-for-woocommerce' ); ?></label><span class="wps_order_note_text"><?php echo esc_html( $tofw_enable_track_order_below_textarea ); ?></span></p>

			<?php if ( 'on' == $wps_tofwp_enable_track_order_popup ) { ?>
				<a href="<?php echo esc_attr( $track_order_url ) . '?' . esc_attr( $order_id ) . '&TB_iframe=true&popup_type=track_order'; ?>" class="woocommerce-button button thickbox order-actions-button "><?php echo esc_html( $tofw_enable_track_order_below_text ); ?></a>
			<?php } else { ?>
				<a href="<?php echo esc_attr( $track_order_url ) . '?' . esc_attr( $order_id ); ?>" class="button button-primary"><?php echo esc_html( $tofw_enable_track_order_below_text ); ?></a>
			<?php } ?>


			<?php
		}
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
		$tofw_enable_track_order_below_action = get_option( 'tofw_enable_track_order_below_action' );
		$tofw_enable_track_order_below_action_text = get_option( 'tofw_enable_track_order_below_action_text', __( 'Track Order', 'track-orders-for-woocommerce' ) );

		if ( 'on' != $tofw_enable_track_order_below_action ) {
			return $actions;
		}
		$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
		$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
		if ( version_compare( WC()->version, '3.0.0', '<' ) ) {
			$order_id = $order->id;
			$track_order_url = get_permalink( $page_id );
			$actions['wps_track_order']['url']  = $track_order_url . '?' . $order_id;
			$actions['wps_track_order']['name']     = $tofw_enable_track_order_below_action_text;
		} else {
			$order_id = $order->get_id();
			$track_order_url = get_permalink( $page_id );
			$actions['wps_track_order']['url']  = $track_order_url . '?' . $order_id;
			$actions['wps_track_order']['name']     = $tofw_enable_track_order_below_action_text;
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
		$status_template_mapping = get_option( 'wps_tofw_new_custom_template', array() );
		$status_name = '';
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
					$link_array = explode( '?', isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
					if ( empty( $link_array[ count( $link_array ) - 1 ] ) ) {
						$order_id = $link_array[ count( $link_array ) - 2 ];
					} else {
						$order_id = $link_array[ count( $link_array ) - 1 ];
					}
					$order = wc_get_order( $order_id );
					if ( ! $order ) {
						return $template;
					}
					// Retrieve the order status.
					$status_slug = $order->get_status();
					$order_statuses = wc_get_order_statuses();
					if ( isset( $order_statuses[ 'wc-' . $status_slug ] ) ) {
						$status_name = $order_statuses[ 'wc-' . $status_slug ];
					}

					// Retrieve the mapping from the options table.
					$status_template_mapping = get_option( 'wps_tofw_new_custom_template', array() );

					// Check if the retrieved data is valid.
					if ( is_array( $status_template_mapping ) ) {
						$current_order_status = $status_name; // Replace this with your dynamic order status.
						$template1 = false; // Initialize the template variable.

						// Loop through the mapping to find the matching template.
						foreach ( $status_template_mapping as $mapping ) {
							if ( isset( $mapping[ $current_order_status ] ) ) {
								$template1 = $mapping[ $current_order_status ]; // Assign the matched template.
								break; // Exit the loop after finding the match.
							}
						}
					}

					$found = false;
					foreach ( $status_template_mapping as $sub_array ) {
						if ( array_key_exists( $status_name, $sub_array ) ) {
							$found = true;
							break; // Exit loop once the key is found.
						}
					}

					if ( $found ) {
						// Determine the path based on the selected template.
						if ( ( 'template8' === $selected_template || 'template4' === $selected_template || 'newtemplate1' === $selected_template || 'newtemplate2' === $selected_template || 'newtemplate3' === $selected_template ) && is_plugin_active( 'track-orders-for-woocommerce-pro/track-orders-for-woocommerce-pro.php' ) ) {
							$path = TRACK_ORDERS_FOR_WOOCOMMERCE_PRO_DIR_PATH;
						} else {
							$path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH;
						}
						// Construct the template path.
						$new_template = $path . 'template/wps-track-order-myaccount-page-' . $selected_template . '.php';
						$template = $new_template;
					} else {
						if ( ( 'template8' === $selected_template || 'template4' === $selected_template || 'newtemplate1' === $selected_template || 'newtemplate2' === $selected_template || 'newtemplate3' === $selected_template ) && ( is_plugin_active( 'track-orders-for-woocommerce-pro/track-orders-for-woocommerce-pro.php' ) ) ) {
							$path = TRACK_ORDERS_FOR_WOOCOMMERCE_PRO_DIR_PATH;
						} else {
							$path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH;
						}
						$new_template = $path . 'template/wps-track-order-myaccount-page-' . $selected_template . '.php';
						$template = $new_template;
					}
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
		if (
			isset( $wps_tofw_pages['pages'] ) &&
			is_array( $wps_tofw_pages['pages'] ) &&
			isset( $wps_tofw_pages['pages']['wps_guest_track_order_page'] )
		) {
			$page_id = $wps_tofw_pages['pages']['wps_guest_track_order_page'];
		}
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
		$page_id = isset( $wps_tofw_pages['pages']['wps_fedex_track_order'] )
			? $wps_tofw_pages['pages']['wps_fedex_track_order']
			: null;

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
		if ( version_compare( WC()->version, '3.0.0', '<' ) ) {
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
				<a href="<?php echo esc_attr( $track_order_url ) . '?' . esc_attr( $order_id ); ?>"><?php esc_html_e( 'track your order', 'track-orders-for-woocommerce' ); ?></a>
			</div>
			<?php
		}
	}


	/**
	 * Register shortcodes for tracking order.
	 */
	public function wps_track_order_shortcodes_multiple_carrier() {
		add_shortcode( 'WPS_MUTIPLE_CARRIER_TRACKING_FORM', array( $this, 'wps_fetch_multiple_tracking_carrier_data' ) );
	}


	/**
	 * Fetch multiple tracking carrier data.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function wps_fetch_multiple_tracking_carrier_data( $atts ) {
		$atts = shortcode_atts(
			array(
				'position' => '',
			),
			array_change_key_case( (array) $atts, CASE_LOWER ),
			'WPS_MUTIPLE_CARRIER_TRACKING_FORM'
		);

		// Map position to class.
		$pos = trim( strtolower( $atts['position'] ) );
		switch ( $pos ) {
			case 'center':
				$align_class = 'wps-tofw-carrier-center';
				break;
			case 'right':
				$align_class = 'wps-tofw-carrier-right';
				break;
			default:
				$align_class = 'wps-tofw-carrier-left';
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wps_tofw_carrier_logos';
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT carrier_name, carrier_code, logo_url FROM {$wpdb->prefix}wps_tofw_carrier_logos ORDER BY carrier_name ASC"
				),
				ARRAY_A
			);
		ob_start();
		?>
	<div class="wps-tofw_track <?php echo esc_attr( $align_class ); ?>">
		<div class="wps-tofw_t-head">
			<input type="text" placeholder="<?php echo esc_attr__( 'Enter tracking id', 'track-orders-for-woocommerce' ); ?>" id="wps-tofw_th-search" class="wps-tofw_th-in" />

			<select name="tracking-method" id="wps-tofw_th-method">
				<option value=""><?php echo esc_html__( '-----Select Carrier------', 'track-orders-for-woocommerce' ); ?></option>
				<?php
				if ( ! empty( $results ) ) {
					foreach ( $results as $carrier ) {
						$name = isset( $carrier['carrier_name'] ) ? esc_html( $carrier['carrier_name'] ) : '';
						$code = isset( $carrier['carrier_code'] ) ? esc_attr( $carrier['carrier_code'] ) : '';
						$logo = isset( $carrier['logo_url'] ) ? esc_url( $carrier['logo_url'] ) : '';

						printf(
							'<option value="%1$s" data-logo="%2$s">%3$s</option>',
							esc_html( $code ),
							esc_html( $logo ),
							esc_html( $name )
						);
					}
				} else {
					echo '<option value="">' . esc_html__( 'No carriers found', 'track-orders-for-woocommerce' ) . '</option>';
				}
				?>
			</select>

			<button type="button" id="wps-tofw_th-search-btn"><?php echo esc_html__( 'Track', 'track-orders-for-woocommerce' ); ?></button>
		</div>

		<div class="wps-tofw_loader" aria-hidden="true"></div>
		<div class="wps-tofw_t-main" id="wps-tofw_t-main"></div>
	</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Add tracking header in My Account order view if partial shipment is enabled and order has multiple items.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 */
	public function wps_tofw_add_tracking_header_myaccount( $order ) {
		if ( ! ( is_account_page() && is_wc_endpoint_url( 'view-order' ) ) ) {
			return;
		}
		if ( ! $order ) {
			return;
		}

		// Count products in the order.
		$items = $order->get_items();
		if ( count( $items ) > 1 ) {
			$this->wps_tofw_render_partial_tracking( $order );
		}
	}

	/**
	 * Add tracking header on the Thank You page if partial shipment is enabled and order has multiple items.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 */
	public function wps_tofw_add_tracking_header_thankyou( $order ) {
		if ( ! is_checkout() || ! is_order_received_page() ) {
			return;
		}
		if ( ! $order ) {
			return;
		}

		// Count products in the order.
		$items = $order->get_items();
		if ( count( $items ) > 1 ) {
			$this->wps_tofw_render_partial_tracking( $order );
		}
	}

	/**
	 * Render partial tracking links for each item in the order.
	 *
	 * @param WC_Order $order The WooCommerce order object.
	 */
	public function wps_tofw_render_partial_tracking( $order ) {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
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

		$wps_tofw_products = array();
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
				wps_tofw_td.innerHTML = '<a href="<?php echo esc_url( home_url( '/track-your-order/?' ) ); ?>' 
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
	 * Hook to run order splitting logic when an order is created or updated.
	 *
	 * @param int $order_id The ID of the order being processed.
	 */
	public function wps_run_split_on_order( $order_id ) {
		$wps_enable_partila_shipement = get_option( 'tofw_enable_partial_shipment' );
		if ( ! $order_id || 'on' !== $wps_enable_partila_shipement ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Count products in the order.
		$items = $order->get_items();
		if ( count( $items ) > 1 ) {
			$this->wps_split_parent_into_children( $order_id );
		}
	}

	/**
	 * Split a parent order into multiple child orders, one per line item.
	 *
	 * @param int $parent_order_id The ID of the parent order to split.
	 * @return array|WP_Error Array of child order IDs on success, WP_Error on failure.
	 */
	public function wps_split_parent_into_children( $parent_order_id ) {
		$parent = wc_get_order( $parent_order_id );
		if ( ! $parent ) {
			return new WP_Error( 'wps_no_parent', 'Parent order not found.' );
		}

		// Prevent dupes.
		if ( $parent->get_meta( '_wps_children_created' ) ) {
			return new WP_Error( 'wps_already_done', 'Children already created for this order.' );
		}

		$child_ids = array();
		foreach ( $parent->get_items( 'line_item' ) as $item_id => $item ) {
			$child = wc_create_order(
				array(
					'status'      => $parent->get_status(),
					'customer_id' => $parent->get_customer_id(),
					'currency'    => $parent->get_currency(),
				)
			);

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

			$child_ids[ $item->get_product_id() ] = $child->get_id();
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
}
