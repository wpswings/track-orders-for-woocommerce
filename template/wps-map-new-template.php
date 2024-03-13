<?php
/**
 * Guest track order page.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/template
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$allowed = true;

$current_user_id = get_current_user_id();
$wps_tofw_enable_track_order_popup = get_option( 'wps_tofwp_enable_track_order_popup' );
if ( true == $allowed ) {

	$check_value = isset( $_POST['woocommerce-process-checkout-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) ) : '';
	wp_verify_nonce( $check_value, 'woocommerce-process_checkout' );
	if ( isset( $_POST['order_id'] ) ) {
		$order_id = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
	} else {
		$link_array = explode( '?', isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' );
		if ( empty( $link_array[ count( $link_array ) - 1 ] ) ) {
			$order_id = $link_array[ count( $link_array ) - 2 ];
		} else {
			$order_id = $link_array[ count( $link_array ) - 1 ];
		}
	}

	// check order id is valid.
	$order_obj = new WC_Order( $order_id );
	if ( ! is_numeric( $order_id ) ) {

		if ( get_current_user_id() > 0 ) {
			$myaccount_page = get_option( 'woocommerce_myaccount_page_id' );
			$myaccount_page_url = get_permalink( $myaccount_page );
		} else {
			$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
			$page_id = $wps_tofw_pages['pages']['wps_guest_track_order_page'];
			$myaccount_page_url = get_permalink( $page_id );
		}
		$allowed = false;
		$reason = __( 'Please choose an Order.', 'track-orders-for-woocommerce' ) . '<a href="' . $myaccount_page_url . '">' . __( 'Click Here', 'track-orders-for-woocommerce' ) . '</a>';

		/**
		 * Add reason.
		 *
		 * @since 1.0.0
		 */
		$reason = apply_filters( 'wps_tofw_track_choose_order', $reason );
	} else {

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$order_customer_id = get_post_field( 'post_author', $order_id );
		} else {
			$order_customer_id = get_post_field( 'post_author', $order_id );
		}

		if ( $current_user_id > 0 ) {
			if ( $order_customer_id != $current_user_id ) {
				$myaccount_page = get_option( 'woocommerce_myaccount_page_id' );
				$myaccount_page_url = get_permalink( $myaccount_page );
				$allowed = false;
				$reason = __( 'This order #', 'track-orders-for-woocommerce' ) . $order_id . __( 'is not associated to your account.', 'track-orders-for-woocommerce' ) . "<a href='$myaccount_page_url'>" . __( 'Click Here ', 'track-orders-for-woocommerce' ) . '</a>';

				/**
				 * Add reason.
				 *
				 * @since 1.0.0
				 */
				$reason = apply_filters( 'wps_tofw_track_choose_order', $reason );
			}
		} else // Check order associated to customer account or not for guest user.
		{
			if ( 'on' != get_option( 'wps_tofw_enable_track_order_using_order_id', 'no' ) ) {

				if ( isset( $_SESSION['wps_tofw_email'] ) ) {
					$tofw_user_email = $_SESSION['wps_tofw_email'];

					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						// HPOS usage is enabled.
						$order_email = $order_obj->get_billing_email();
					} else {
						$order_email = get_post_meta( $order_id, '_billing_email', true );
					}

					if ( $tofw_user_email != $order_email ) {
						$allowed = false;
						$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
						$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
						$myaccount_page_url = get_permalink( $page_id );
						$reason = __( 'This order #', 'track-orders-for-woocommerce' ) . $order_id . __( 'is not associated to your account.', 'track-orders-for-woocommerce' ) . "<a href='$myaccount_page_url'>" . __( 'Click Here ', 'track-orders-for-woocommerce' ) . '</a>';

						/**
						 * Add reason.
						 *
						 * @since 1.0.0
						 */
						$reason = apply_filters( 'wps_tofw_track_choose_order', $reason );
					}
				} else {

					$myaccount_page = get_option( 'woocommerce_myaccount_page_id' );
					$myaccount_page_url = get_permalink( $myaccount_page );
					$allowed = false;
					$reason = __( 'This order #', 'track-orders-for-woocommerce' ) . $order_id . __( ' is not associated to your account.', 'track-orders-for-woocommerce' ) . "<a href='$myaccount_page_url'>" . __( 'Click Here ', 'track-orders-for-woocommerce' ) . '</a>';

					/**
					 * Add reason.
					 *
					 * @since 1.0.0
					 */
					$reason = apply_filters( 'wps_tofw_track_choose_order', $reason );
				}
			}
		}
	}
} else {
	$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
	$page_id = $wps_tofw_pages['pages']['wps_guest_track_order_page'];
	$track_order_url = get_permalink( $page_id );
	header( 'Location: ' . $track_order_url );
}

get_header( 'shop' );


/**
 * Add content.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_before_main_content' );


if ( $allowed ) {

	if ( isset( $order_id ) && ! empty( $order_id ) ) {
		$tofw_order = wc_get_order( $order_id );

		$order_data = $tofw_order->get_data();


		$wps_tofw_all_saved_cities = get_option( 'wps_tofw_save_selected_city', false );

		if ( is_array( $wps_tofw_all_saved_cities ) && ! empty( $wps_tofw_all_saved_cities ) ) {
			foreach ( $wps_tofw_all_saved_cities as $saved_key => $saved_value ) {

				$wps_tofw_modified_all_saved_cities[] = str_replace( 'wps_address_', '', $saved_value );
			}
		}

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS usage is enabled.
			$wps_tofw_previous_saved_cities = $tofw_order->get_meta( 'wps_tofw_track_custom_cities', true );
			$wps_tofw_previous_saved_changed_time = $tofw_order->get_meta( 'wps_tofw_custom_change_time', true );
		} else {
			$wps_tofw_previous_saved_cities = get_post_meta( $order_id, 'wps_tofw_track_custom_cities', true );
			$wps_tofw_previous_saved_changed_time = get_post_meta( $order_id, 'wps_tofw_custom_change_time', true );
		}




		$billing_addresses = $tofw_order->get_formatted_billing_address();
		$created_time = $order_data['date_created'];
		$converted_created_date = date_i18n( 'F d, Y g:i a', strtotime( $created_time ) );

	}

	$wps_tofw_google_api_key = get_option( 'wps_tofw_google_api_key', false );
	$wps_admin_shop_location = get_option( 'wps_tofw_order_production_address', false );



	if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
		// HPOS usage is enabled.
		$wps_order_delivery_date = $tofw_order->get_meta( 'wps_tofw_estimated_delivery_date', true );
		$wps_order_delivery_time = $tofw_order->get_meta( 'wps_tofw_estimated_delivery_time', true );
	} else {
		$wps_order_delivery_date = get_post_meta( $order_id, 'wps_tofw_estimated_delivery_date', true );
		$wps_order_delivery_time = get_post_meta( $order_id, 'wps_tofw_estimated_delivery_time', true );
	}
	?>
	<div class="wps-track-order-main-wrapper">
		<div class="wps-track-order-content">
			<div class="wps-track-order-tracking-section">
				<div class="wps-track-order-tooltip">
					<div class="wps-track-order-tooltip-wrap">
						<span><?php esc_html_e( 'Order Placed Successfully', 'track-orders-for-woocommerce' ); ?></span>
						<span><?php esc_html_e( ' On ', 'track-orders-for-woocommerce' ); ?><?php echo esc_html( $converted_created_date ); ?></span>
						<span>
						<?php
						if ( isset( $wps_admin_shop_location ) && '' != $wps_admin_shop_location ) {
							echo esc_html( $wps_admin_shop_location );
						} else {
							esc_html_e( 'United States', 'track-orders-for-woocommerce' ); }
						?>
						</span>
					</div>
					<span class="wps-track-order-inner-circle"></span>
				</div>
				<?php

				if ( is_array( $wps_tofw_previous_saved_cities ) && ! empty( $wps_tofw_previous_saved_cities ) ) {
					foreach ( $wps_tofw_previous_saved_cities as $city_key => $city_value ) {
						$wps_tofw_same_city_occurence = array_count_values( $city_value );

						?>
						<div class="wps-track-order-tooltip">
							<div class="wps-track-order-tooltip-wrap">
								<span><?php echo esc_html( $city_key ); ?></span>
								<div class="wps-tofw-new-order-time">
									<span>
										<?php

										if ( is_array( $wps_tofw_previous_saved_changed_time ) && array_key_exists( $city_key, $wps_tofw_previous_saved_changed_time ) ) {
											foreach ( $city_value as $key => $value ) {
												if ( $wps_tofw_same_city_occurence[ $value ] <= 1 ) {
													?>
													<span><?php echo esc_html( $value ); ?><?php esc_html_e( ' On ', 'track-orders-for-woocommerce' ); ?><?php echo esc_html( $wps_tofw_previous_saved_changed_time[ $city_key ][ $key ] ); ?></span>
													<?php
												} else {
													$wps_tofw_occurence_key = end( $wps_tofw_previous_saved_changed_time[ $city_key ] );
													?>
													<span><?php echo esc_html( $value ); ?><?php esc_html_e( ' On ', 'track-orders-for-woocommerce' ); ?><?php echo esc_html( $wps_tofw_occurence_key ); ?></span>
													<?php
													break;
												}
											}
										}
										?>
									</span>
								</div>
							</div>
							<span class="wps-track-order-inner-circle"></span>
						</div>
						<?php
					}
				}
				if ( isset( $wps_order_delivery_time ) && '' != $wps_order_delivery_time ) {
					$wps_order_delivery_time = date_i18n( 'g:i a', strtotime( $wps_order_delivery_time ) );
				}

				?>
				<div class="wps-track-order-last-tooltip">
					<div class="wps-track-order-last-tooltip-wrap">
						<span><?php esc_html_e( 'Product Delivered', 'track-orders-for-woocommerce' ); ?></span>
						<?php
						if ( '' != $wps_order_delivery_date ) {
							if ( '' != $wps_order_delivery_time ) {
								?>
								<span><?php esc_html_e( ' On ', 'track-orders-for-woocommerce' ); ?><?php echo esc_html( $wps_order_delivery_date ) . ' ' . esc_html( $wps_order_delivery_time ); ?></span>
								<?php
							} else {
								?>
								<span><?php esc_html_e( ' On ', 'track-orders-for-woocommerce' ); ?><?php echo esc_html( $wps_order_delivery_date ); ?></span>
								<?php
							}
						} else {
							?>
							<span><?php esc_html_e( ' -- ', 'track-orders-for-woocommerce' ); ?></span>
							<?php
						}
						?>
						<span><?php echo esc_html( $order_data['billing']['address_1'] ) . ', ' . esc_html( $order_data['billing']['address_2'] ) . '</br>' . esc_html( $order_data['billing']['city'] ) . ', ' . esc_html( $order_data['billing']['state'] ) . '</br>' . esc_html( $order_data['billing']['email'] ); ?></span>
					</div>
					<span class="wps-track-order-last-inner-circle"></span>
					<span class="wps-track-order-outer-inner-circle"></span>
				</div>
			</div>
		</div>
		<div class="wps-tofw-new-order-details">
			<?php
			if ( WC()->version < '3.0.0' ) {
				foreach ( $tofw_order->get_items() as $orderkey => $ordervalue ) {
					?>
					<div class="wps-tofw-new-order-details-inner">
						<?php
						if ( $ordervalue['qty'] > 0 ) {

							/**
							 * Add products.
							 *
							 * @since 1.0.0
							 */
							$product = apply_filters( 'woocommerce_order_item_product', $ordervalue->get_product(), $ordervalue );

							/**
							 * Get permalink.
							 *
							 * @since 1.0.0
							 */
							$thumbnail     = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $orderkey, $ordervalue ) : '';
							$productdata = new WC_Product( $product->id );
							$is_visible        = $product && $product->is_visible();

							/**
							 * Get permalink.
							 *
							 * @since 1.0.0
							 */
							$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $ordervalue ) : '', $ordervalue, $tofw_order );
							$total += $product->get_price() * $ordervalue['qty'];
							?>
							<div class="order-id"><p><?php esc_html_e( 'order id : ', 'track-orders-for-woocommerce' ); ?><strong><?php echo esc_html( $order_id ); ?></strong></p></div>
							<div class="wps-tofw-order-img-detail">
								<div class="wps-tofw-order">
									<p class="wps-tofw-order-name-bold"><?php echo esc_html( $productdata->post->post_title ); ?></p>
									<p class="wps-tofw-seller-name"><?php esc_html_e( 'seller : ', 'track-orders-for-woocommerce' ); ?><?php echo esc_html( get_bloginfo() ); ?></p>
								</div>
								<div class="wps-tofw-product-name">
									<?php
									if ( isset( $thumbnail ) && ! empty( $thumbnail ) ) {
										echo wp_kses_post( $thumbnail );
									} else {
										?>
										<p><img alt="<?php esc_html_e( 'Placeholder', 'track-orders-for-woocommerce' ); ?>"  class="wps_tofw_attachment-thumbnail size-thumbnail wp-post-image wps-img-responsive" src="<?php echo esc_attr( home_url() ); ?>/wp-content/plugins/woocommerce/assets/images/placeholder.png"></p>
										<?php
									}
									?>
								</div>
							</div>
							<div class="price-product">
								<p class="price-bold"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></p>
								<div class="wps-tofw-quantity">
									<label><?php esc_html_e( 'Quantity : ', 'track-orders-for-woocommerce' ); ?><?php echo esc_html( $ordervalue['qty'] ); ?></label> 
								</div>
							</div>       
							<?php
						}
						?>
					</div>
					<?php
				}
			} else {

				$total = 0;
				$wps_tofw_grand_total = 0;
				$wps_tofw_total_qty = 0;
				foreach ( $tofw_order->get_items() as $orderkey => $ordervalue ) {
					?>
					<div class="wps-tofw-new-order-details-inner">
						<?php
						if ( $ordervalue->get_quantity() > 0 ) {

							/**
							 * Add Products.
							 *
							 * @since 1.0.0
							 */
							$product = apply_filters( 'woocommerce_order_item_product', $ordervalue->get_product(), $ordervalue );

							/**
							 * Add Products.
							 *
							 * @since 1.0.0
							 */
							$thumbnail     = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $orderkey, $ordervalue ) : '';
							$productdata = wc_get_product( $product->get_id() );
							$is_visible        = $product && $product->is_visible();

							/**
							 * Product permalink.
							 *
							 * @since 1.0.0
							 */
							$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $ordervalue ) : '', $ordervalue, $tofw_order );
							$total += $product->get_price() * $ordervalue['qty'];
							$wps_tofw_grand_total += $total;
							$wps_tofw_total_qty += $ordervalue['qty'];
							?>
							<div class="order-id"><p><?php esc_html_e( 'order id : ', 'track-orders-for-woocommerce' ); ?><strong><?php echo esc_html( $order_id ); ?></strong></p></div>
							<div class="wps-tofw-order-img-detail">
								<div class="wps-tofw-order">
									<p class="wps-tofw-order-name-bold"><?php echo esc_html( $productdata->get_title() ); ?></p>
									<p class="wps-tofw-seller-name"><?php esc_html_e( 'seller : ', 'track-orders-for-woocommerce' ); ?><?php echo esc_html( get_bloginfo() ); ?></p>
								</div>
								<div class="wps-tofw-product-name">
									<?php
									if ( isset( $thumbnail ) && ! empty( $thumbnail ) ) {
										echo wp_kses_post( $thumbnail );
									} else {
										?>
										<p><img alt="<?php esc_attr_e( 'Placeholder', 'track-orders-for-woocommerce' ); ?>"  class="wps_tofw_attachment-thumbnail size-thumbnail wp-post-image wps-img-responsive" src="<?php echo esc_attr( home_url() ); ?>/wp-content/plugins/woocommerce/assets/images/placeholder.png"></p>
										<?php
									}
									?>
								</div>
							</div>
							<div class="price-product">
								<p class="price-bold"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></p>
								<div class="wps-tofw-quantity">
									<label><?php esc_html_e( 'Quantity : ', 'track-orders-for-woocommerce' ); ?><?php echo esc_html( $ordervalue['qty'] ); ?></label> 
								</div>
							</div>        
							<?php
						}
						?>
					</div>
					<?php
				}
			}
			?>
		</div>
	</div>	
	<?php

	if ( is_array( $wps_tofw_previous_saved_cities ) && ! empty( $wps_tofw_previous_saved_cities ) ) {
		foreach ( $wps_tofw_previous_saved_cities as $mapkey => $mapvalue ) {
			if ( is_array( $mapvalue ) && ! empty( $mapvalue ) ) {
				foreach ( $mapvalue as $keymap => $valuemap ) {
					$wps_tofw_order_send_to_cities[] = $valuemap;
					$wps_tofw_order_send_to_cities = array_unique( $wps_tofw_order_send_to_cities );
					$wps_tofw_order_send_to_cities = array_values( $wps_tofw_order_send_to_cities );
					$wps_tofw_order_sent_cities = wp_json_encode( $wps_tofw_order_send_to_cities );
				}
			}
		}
	}

	if ( isset( $wps_tofw_order_sent_cities ) && ( '' != $wps_tofw_order_sent_cities || null != $wps_tofw_order_sent_cities ) ) {
		echo '<input type="hidden" name="wps_tofw_google_distance_map" id="wps_tofw_google_distance_map" value="' . esc_attr( htmlspecialchars( $wps_tofw_order_sent_cities ) ) . '">';
	} else {
		$wps_tofw_order_production_add = get_option( 'wps_tofw_order_production_address', false );
		$address[] = $wps_tofw_order_production_add;
		$wps_tofw_order_sent_cities = $address;
		echo '<input type="hidden" name="wps_tofw_google_distance_map" id="wps_tofw_google_distance_map" value="' . esc_attr( wp_json_encode( $wps_tofw_order_sent_cities ) ) . '">';

	}

	$wps_tofw_order_production_add = get_option( 'wps_tofw_order_production_address', false );
	$address = $wps_tofw_order_production_add;

	$wps_tofw_billing_add = $order_data['billing']['city'] . '+' . $order_data['billing']['state'];

	$wps_tofw_origin_location = get_option( 'wps_tofw_address_get_correct', false );


	if ( isset( $wps_tofw_origin_location ) && ( '' != $wps_tofw_origin_location || null != $wps_tofw_origin_location ) ) {
		$lat = get_option( 'wps_tofw_address_latitude', false );
		$long = get_option( 'wps_tofw_address_longitude', false );

		?>
		<input type="hidden" id="start_hidden" value="<?php echo esc_attr( $lat ); ?>">
		<input type="hidden" id="end_hidden" value="<?php echo esc_attr( $long ); ?>">
		<input type="hidden" id="billing_hidden" value="<?php echo esc_attr( $wps_tofw_billing_add ); ?>">
		<?php

	} else {
		if ( ! empty( $address ) ) {

			$response = wp_remote_get( 'https://maps.google.com/maps/api/geocode/json?address=' . urlencode( $address ) . '&key=' . $wps_tofw_google_api_key );

			if ( ! is_wp_error( $response ) ) {
				$geocode = wp_remote_retrieve_body( $response );
			}
			
			$output = json_decode( $geocode );

			if ( isset( $output->results[0] ) && ! empty( $output->results[0] ) ) {
				$lat = $output->results[0]->geometry->location->lat;
				$long = $output->results[0]->geometry->location->lng;
				?>
				<input type="hidden" id="start_hidden" value="<?php echo esc_attr( $lat ); ?>">
				<input type="hidden" id="end_hidden" value="<?php echo esc_attr( $long ); ?>">
				<input type="hidden" id="billing_hidden" value="<?php echo esc_attr( $wps_tofw_billing_add ); ?>">
				<?php
				update_option( 'wps_tofw_address_get_correct', 'on' );
				update_option( 'wps_tofw_address_latitude', $lat );
				update_option( 'wps_tofw_address_longitude', $long );

			}
		}
	}
	?>
	<h3><?php esc_html_e( 'Places Where Your Package Travel', 'track-orders-for-woocommerce' ); ?></h3>
	<div id="map"></div>
	<div id="directions-panel"></div>
	<?php
} else {
	$return_request_not_send = __( 'Tracking Request can\'t be send. ', 'track-orders-for-woocommerce' );

	/**
	 * Request not sent.
	 *
	 * @since 1.0.0
	 */
	$return_request_not_send = apply_filters( 'wps_tofw_tracking_request_not_send', $return_request_not_send );
	echo wp_kses_post( $return_request_not_send );
	echo wp_kses_post( $reason );
}
get_footer( 'shop' );
?>
