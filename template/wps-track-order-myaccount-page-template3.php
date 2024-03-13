<?php
/**
 * Template page to track order.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/template
 */

use Automattic\WooCommerce\Utilities\OrderUtil;
/**
 * Exit if accessed directly
 */
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
			// HPOS usage is enabled.
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
				 * Add reason
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
						$order_email = $order_obj->get_meta( '_billing_email', true );
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
					$allowed = false;
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
 * Add action.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_before_main_content' );

	/**
	 *  Woocommerce_before_main_content hook.
	 *
	 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
	 * @hooked woocommerce_breadcrumb - 20
	 */
	$wps_main_wrapper_class = get_option( 'wps_tofw_main_wrapper_class_theme' );
	$wps_child_wrapper_class = get_option( 'wps_tofw_child_wrapper_class' );
	$wps_track_order_css = get_option( 'wps_tofw_custom_css_name' );
	$wps_track_order_js = get_option( 'wps_tofw_custom_js_name' );

if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
	// HPOS usage is enabled.
	$wps_tofw_enhanced_customer_note = $order_obj->get_meta( 'wps_tofw_enhanced_cn', true );
	$wps_order_number = $order_obj->get_meta( '_order_number', true );
} else {
	$wps_tofw_enhanced_customer_note = get_post_meta( $order_id, 'wps_tofw_enhanced_cn', true );
	$wps_order_number = get_post_meta( $order_id, '_order_number', true );
}

	$wps_prefix = '';
	$wps_prefix = get_option( 'woocommerce_order_number_prefix', false );
	$wps_order_number = $wps_prefix . $wps_order_number;
if ( ! empty( $wps_tofw_enhanced_customer_note ) ) {
	$wps_tofw_enhanced_customer_note = $wps_tofw_enhanced_customer_note;
} else {
	$wps_tofw_enhanced_customer_note = '';
}
?>
<style id="wps-tofw-global-css" type="text/css">
<?php echo wp_kses_post( $wps_track_order_css ); ?>	
</style>
<script id="wps-tofw-global-js" type="text/javascript">
<?php echo wp_kses_post( wp_unslash( $wps_track_order_js ) ); ?>
</script>
	
	<div class="wps-tofw-order-tracking-section <?php echo esc_attr( $wps_main_wrapper_class ); ?>">
		<?php


		if ( true == $allowed ) {

			$tofw_order = new WC_Order( $order_id );

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// HPOS usage is enabled.
				$expected_delivery_date = $tofw_order->get_meta( 'wps_tofw_estimated_delivery_date', true );
				$expected_delivery_time = $tofw_order->get_meta( 'wps_tofw_estimated_delivery_time', true );
				$order_delivered_date = $tofw_order->get_meta( '_completed_date', true );
			} else {
				$expected_delivery_date = get_post_meta( $order_id, 'wps_tofw_estimated_delivery_date', true );
				$expected_delivery_time = get_post_meta( $order_id, 'wps_tofw_estimated_delivery_time', true );
				$order_delivered_date = get_post_meta( $order_id, '_completed_date', true );
			}

			if ( WC()->version < '3.0.0' ) {
				$order_status = $tofw_order->post_status;
				$ordered_by = $tofw_order->post->post_author;
				$ordered_by = get_user_by( 'ID', $ordered_by );
				$wps_date_on_order_change = $tofw_order->modified_date;
				$wps_modified_date = date_i18n( 'd F, Y H:i', strtotime( $wps_date_on_order_change ) );

				if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
					// HPOS usage is enabled.
					$wps_status_change_time  = $tofw_order->get_meta( 'wps_track_order_onchange_time', true );
				} else {
					$wps_status_change_time = get_post_meta( $order_id, 'wps_track_order_onchange_time', true );
				}

				$wps_status_change_time[ 'wc-' . $order_status ] = $wps_modified_date;

				$ordered_by = $ordered_by->data->display_name;
			} else {
				$order_status = 'wc-' . $tofw_order->get_status();
				$ordered_by = $tofw_order->get_customer_id();
				$ordered_by = get_user_by( 'ID', $ordered_by );
				if ( ! empty( $ordered_by ) ) {
					$ordered_by = $ordered_by->data->display_name;
				}
				$wps_date_on_order_change = $tofw_order->get_date_modified();
				$wps_modified_date = date_i18n( 'd F, Y H:i', strtotime( $wps_date_on_order_change ) );
				if ( ! empty( $wps_status_change_time ) ) {

					if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
						// HPOS usage is enabled.
						$wps_status_change_time = $tofw_order->get_meta( 'wps_track_order_onchange_time', true );
					} else {
						$wps_status_change_time = get_post_meta( $order_id, 'wps_track_order_onchange_time', true );
					}
				}
				$wps_status_change_time[ $order_status ] = $wps_modified_date;

			}




			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// HPOS usage is enabled.
				$billing_first_name = $tofw_order->get_billing_first_name();
				$billing_last_name = $tofw_order->get_billing_last_name();
				$billing_address = $tofw_order->get_billing_address_1() . ' ' . $tofw_order->get_billing_address_2();
				$billing_city = $tofw_order->get_billing_city();
				$billing_state = $tofw_order->get_billing_state();
				$billing_country = $tofw_order->get_billing_country();
				$billing_postcode = $tofw_order->get_billing_postcode();
				$wps_track_order_status = $tofw_order->get_meta( 'wps_track_order_status', true );
				$wps_phone_number = $tofw_order->get_billing_phone();
			} else {
				$billing_first_name = get_post_meta( $order_id, '_billing_first_name', true );
				$billing_last_name = get_post_meta( $order_id, '_billing_last_name', true );
				$billing_address = get_post_meta( $order_id, '_billing_address_1', true ) . ' ' . get_post_meta( $order_id, '_billing_address_2', true );
				$billing_city = get_post_meta( $order_id, '_billing_city', true );
				$billing_state = get_post_meta( $order_id, '_billing_state', true );
				$billing_country = get_post_meta( $order_id, '_billing_country', true );
				$billing_postcode = get_post_meta( $order_id, '_billing_postcode', true );
				$wps_track_order_status = get_post_meta( $order_id, 'wps_track_order_status', true );
				$wps_phone_number = get_post_meta( $order_id, '_billing_phone', true );
			}


			$order_status_key = str_replace( '-', '_', $order_status );
			$order_status_key = 'wps_tofw_' . $order_status_key . '_text';

			?>
			<section class="wps-order-section about-section details-section <?php echo esc_html( $wps_child_wrapper_class ); ?>">

				<div class="wps-order-details-header">
					<h2><?php esc_html_e( 'Order Details', 'track-orders-for-woocommerce' ); ?></h2>
				</div>
				
				<div class="wps-order-details-div">
					<ul class="wps-order-listing wps_tofw_order_list">
						<li>
							<span><?php esc_html_e( 'Order Id:', 'track-orders-for-woocommerce' ); ?></span>
							<span>
							<?php
							if ( empty( $wps_order_number ) ) {
								echo esc_html( $order_id );
							} else {
								echo esc_html( $wps_order_number );}
							?>
							<?php echo esc_html__( '(', 'track-orders-for-woocommerce' ) . esc_html( count( $tofw_order->get_items() ) ) . esc_html__( ' items)', 'track-orders-for-woocommerce' ); ?></span>
						</li>
						<li>
							<span><?php esc_html_e( 'Order Date:', 'track-orders-for-woocommerce' ); ?></span>
							<span>
							<?php
							if ( WC()->version < '3.0.0' ) {
								echo esc_html( date_i18n( 'd F, Y H:i', strtotime( $tofw_order->post->post_date ) ) );
							} else {
								$wps_date = $tofw_order->get_date_created();
								echo esc_html( $wps_date->date( 'd F, Y H:i' ) );}
							?>
							</span>
						</li>
						<li> 
							<span><?php esc_html_e( 'Amount Paid:', 'track-orders-for-woocommerce' ); ?></span>
							<span><strong class="amt-paid"><?php echo wp_kses_post( wc_price( $tofw_order->get_total() ) ); ?></strong></span>
						</li>
					</ul>
				</div>
				<div class="wps-order-details-div wps_tofw_order_list">
					<h3><?php echo esc_html( $billing_first_name ) . ' ' . esc_html( $billing_last_name ) . ' ' . esc_html( $wps_phone_number ); ?></h3>
					<ul class="wps-tofw-address-block">
						<li><?php echo esc_html( $billing_address ); ?></li>
						<li><?php echo esc_html( $billing_city ) . ', ' . esc_html( $billing_state ) . ' -' . esc_html( $billing_postcode ); ?></li>
						<li><?php echo esc_html( WC()->countries->countries[ $billing_country ] ); ?></li>
					</ul>
				</div>
			</section>

			<section class="section wps_tofw_product-details-section <?php echo esc_html( $wps_child_wrapper_class ); ?>">  
				
				<table class="wps_tofw_shop_table order_details wps-product-details-table wps-tofw-product-detail-table"> 
					<thead>
						<tr>
							<th><?php esc_html_e( 'Product Details', 'track-orders-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Quantity', 'track-orders-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Sub Total', 'track-orders-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php $total = 0; ?>
						<?php
						if ( WC()->version < '3.0.0' ) {
							foreach ( $tofw_order->get_items() as $item_id => $item ) {
								if ( $item['qty'] > 0 ) {
									/**
									 * Add product.
									 *
									 * @since 1.0.0
									 */
									$product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );

									/**
									 * Add product.
									 *
									 * @since 1.0.0
									 */
									$thumbnail     = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';
									$productdata = new WC_Product( $product->id );
									$is_visible        = $product && $product->is_visible();

									/**
									 * Add product.
									 *
									 * @since 1.0.0
									 */
									$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $tofw_order );
									?>
									<?php $total = $product->get_price() * $item['qty']; ?>
									<tr>
										<td>
											<div class="wps-product-wrapper wps-product-img">
												<?php
												if ( isset( $thumbnail ) && ! empty( $thumbnail ) ) {
													echo wp_kses_post( $thumbnail );
												} else {
													?>
													<img alt="<?php esc_html_e( 'Placeholder', 'track-orders-for-woocommerce' ); ?>" class="wps_tofw_attachment-thumbnail size-thumbnail wp-post-image wps-img-responsive" src="<?php echo esc_attr( home_url() ); ?>/wp-content/plugins/woocommerce/assets/images/placeholder.png">
													<?php
												}
												?>
											</div>
											<div class="wps-product-wrapper wps-product-desc">
												<h4><a href=""><?php echo esc_html( $productdata->post->post_title ); ?></a></h4>
											</div>
										</td>
										<td>
											<?php echo esc_html( $item['qty'] ); ?>
										</td>
										<td>
											<span><b><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></b></span>
										</td>
									</tr>
									<?php
								}
							}
						} else {
							$count = 1;
							$total = 0;
							$wps_tofw_grand_total = 0;
							$wps_tofw_total_qty = 0;
							foreach ( $tofw_order->get_items() as $item_id => $item ) {
								if ( $item->get_quantity() > 0 ) {

									/**
									 * Add product.
									 *
									 * @since 1.0.0
									 */
									$product = apply_filters( 'woocommerce_order_item_product', $item->get_product(), $item );

									/**
									 * Add product.
									 *
									 * @since 1.0.0
									 */
									$thumbnail     = $product ? apply_filters( 'woocommerce_admin_order_item_thumbnail', $product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';
									$productdata = wc_get_product( $product->get_id() );
									$is_visible        = $product && $product->is_visible();

									/**
									 * Add product.
									 *
									 * @since 1.0.0
									 */
									$product_permalink = apply_filters( 'woocommerce_order_item_permalink', $is_visible ? $product->get_permalink( $item ) : '', $item, $tofw_order );
									?>
									<?php
									$total = $product->get_price() * $item->get_quantity();
									$wps_tofw_grand_total += $total;
									$wps_tofw_total_qty += $item['qty'];
									?>
									<tr>
										<td>
											<div class="wps-product-wrapper wps-product-img">
												<?php
												if ( isset( $thumbnail ) && ! empty( $thumbnail ) ) {
													echo wp_kses_post( $thumbnail );
												} else {
													?>
													<img alt="<?php esc_html_e( 'Placeholder', 'track-orders-for-woocommerce' ); ?>"  class="wps_tofw_attachment-thumbnail size-thumbnail wp-post-image wps-img-responsive" src="<?php echo esc_attr( home_url() ); ?>/wp-content/plugins/woocommerce/assets/images/placeholder.png">
													<?php
												}
												?>
											</div>
											<div class="wps-product-wrapper wps-product-desc">
												<h4><a href=""><?php echo esc_html( $productdata->get_title() ); ?></a></h4>
											</div>
										</td>
										<td class="wpstext-center">
											<?php echo esc_html( $item->get_quantity() ); ?>
										</td>
										<td>
											<span class="wpstext-center"><b><?php echo wp_kses_post( wc_price( $total ) ); ?></b></span>
										</td>
									</tr>
									<?php
								}
							}
						}
						?>
						<tr>
							<td>
								<div>
									<span><b><?php esc_html_e( 'Total', 'track-orders-for-woocommerce' ); ?></b></span>
								</div>
							</td>
							<td id="wps_tofw_total_item">
								<?php
								echo esc_html( $wps_tofw_total_qty );
								?>
							</td>
							<td>
								<div>
									<span class="wpstext-center"><b><?php echo wp_kses_post( wc_price( $wps_tofw_grand_total ) ); ?></b></span>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</section>
			<?php
			$get_status_approval = get_option( 'wps_tofw_order_status_in_approval', array() );
			$get_status_processing = get_option( 'wps_tofw_order_status_in_processing', array() );
			$get_status_shipping = get_option( 'wps_tofw_order_status_in_shipping', array() );
			$wps_track_order_status = array();

			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// HPOS usage is enabled.
				$wps_track_order_status = $order_obj->get_meta( 'wps_track_order_status', true );
			} else {
				$wps_track_order_status = get_post_meta( $order_id, 'wps_track_order_status', true );
			}


			$woo_statuses = wc_get_order_statuses();
			$status_process = 0;
			$status_shipped = 0;
			if ( is_array( $get_status_processing ) && ! empty( $get_status_processing ) ) {
				foreach ( $get_status_processing as $key => $value ) {
					if ( ! empty( $wps_track_order_status ) && in_array( $value, $wps_track_order_status ) ) {
						$status_process = 1;
					}
				}
			}

			if ( is_array( $get_status_shipping ) && ! empty( $get_status_shipping ) ) {
				foreach ( $get_status_shipping as $key1 => $value ) {
					if ( ! empty( $wps_track_order_status ) && in_array( $value, $wps_track_order_status ) ) {
						$status_shipped = 1;
					}
				}
			}

			?>
			<section class="<?php echo esc_html( $wps_child_wrapper_class ); ?> section wps_tofw_product-details-section">
				<table class="wps_tofw_shop_table order_details wps-product-details-table wps-tofw-track-order-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'APPROVAL', 'track-orders-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'PROCESSING', 'track-orders-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'SHIPPING', 'track-orders-for-woocommerce' ); ?></th>
							<?php if ( '' != $expected_delivery_date || '' != $expected_delivery_time || '' != $order_delivered_date ) { ?>
								<th><?php esc_html_e( 'DELIVERY', 'track-orders-for-woocommerce' ); ?></th>
							<?php } ?>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="3">
								<?php if ( 'on' != $wps_tofw_enable_track_order_popup ) { ?>
									<div class="wps-design-division">
										<?php
								} else {
									?>
										<div class="wps-design-division wps-delivery-division-for-message">
										<?php } ?>
										<div class="wps-controller">
											<span class="track-approval">
												<span class="wps-circle wps-tofw-hover 
												<?php
												if ( empty( $wps_track_order_status ) ) {
													echo 'active'; }
												?>
												" data-status = "<?php esc_html_e( 'Your Order is Successfully Placed', 'track-orders-for-woocommerce' ); ?>"></span> 

												<?php
												$class = '';
												$active = 0;
												$f = 0;
												$cancelled = 0;
												if ( is_array( $wps_track_order_status ) && empty( $wps_track_order_status ) && '' != $order_status && in_array( $order_status, $get_status_approval ) ) {
													?>
														<?php
														$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $order_status ] );

														?>
														<span class="wps-circle active" data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

													<?php
												} else if ( is_array( $wps_track_order_status ) && ! empty( $wps_track_order_status ) && is_array( $get_status_approval ) && ! empty( $get_status_approval ) ) {
													$f = 0;
													foreach ( $wps_track_order_status as $key => $value ) {
														if ( in_array( $value, $get_status_approval ) ) {
															$f = 1;
															$value_key = str_replace( '-', '_', $value );
															$value_key = 'wps_tofw_' . $value_key . '_text';
															$message = __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $value ];
															?>
															<?php
															$current_status = get_option( $value_key, '' );
															$get_status_approval_count = count( $get_status_approval );
															for ( $i = 0; $i < $get_status_approval_count; $i++ ) {
																if ( array_key_exists( $get_status_approval[ $i ], $wps_status_change_time ) ) {
																	$current_status = $current_status . __( ' on ', 'track-orders-for-woocommerce' ) . $wps_status_change_time[ $get_status_approval[ $i ] ];
																}
															}
															?>
															<?php
															if ( '' == $current_status ) {
																$get_status_approval_count = count( $get_status_approval );
																for ( $i = 0; $i < $get_status_approval_count; $i++ ) {
																	if ( array_key_exists( $get_status_approval[ $i ], $wps_status_change_time ) ) {
																		$current_status = $message . __( ' on ', 'track-orders-for-woocommerce' ) . $wps_status_change_time[ $get_status_approval[ $i ] ];
																	}
																}
															}
															?>
																<span class="wps-circle wps-tofw-hover 
																<?php
																if ( ! isset( $wps_track_order_status[ $key + 1 ] ) ) {
																	$active = 1;
																	echo 'active'; }
																?>
																"  data-status = '<?php echo esc_attr( $message ); ?>'></span>	

																<?php
														}
														if ( isset( $wps_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $wps_track_order_status[ $key + 1 ] && in_array( $value, $get_status_approval ) && 'wc-cancelled' == $order_status ) {
															$cancelled = 1;
															$current_status = get_option( 'wps_tofw_wc_cancelled_text', '' );
															if ( '' == $current_status ) {
																$current_status = __( 'Your Order is Cancelled', 'track-orders-for-woocommerce' );
															}
															?>
																<span class="wps-circle order-cancelled"  data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

																<?php
														}
													}
												}
												?>

												</span>
												<span class="track-processing">
													<?php if ( 1 != $cancelled ) { ?>

														<?php
														if ( 1 == $active ) {
															if ( is_array( $get_status_processing ) && ! empty( $get_status_processing ) && ! empty( $wps_track_order_status ) && is_array( $wps_track_order_status ) ) {
																foreach ( $get_status_processing as $key => $value ) {
																	if ( in_array( $value, $wps_track_order_status ) ) {
																		$class = 'revert';
																	}
																}
															}
														}
														?>
														<?php $f = 0; ?>
														<?php
														if ( is_array( $wps_track_order_status ) && empty( $wps_track_order_status ) && '' != $order_status ) {
															?>
															<?php $current_status = get_option( $order_status_key, __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $order_status ] ); ?>
															<span class="wps-circle active" data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

															<?php
														} else if ( ! empty( $get_status_processing ) && is_array( $wps_track_order_status ) && ! empty( $wps_track_order_status ) ) {
															$f = 0;
															foreach ( $wps_track_order_status as $key => $value ) {
																if ( in_array( $value, $get_status_processing ) ) {
																	$f = 1;
																	$value_key = str_replace( '-', '_', $value );
																	$value_key = 'wps_tofw_' . $value_key . '_text';
																	$message = __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $value ];
																	?>
																	<?php
																	$current_status = get_option( $value_key, '' );
																	$get_status_processing_count = count( $get_status_processing );
																	for ( $i = 0; $i < $get_status_processing_count; $i++ ) {

																		if ( array_key_exists( $get_status_processing[ $i ], $wps_status_change_time ) ) {
																			$current_status = $current_status . __( ' on ', 'track-orders-for-woocommerce' ) . $wps_status_change_time[ $get_status_processing[ $i ] ];
																		}
																	}
																	?>
																	<?php
																	if ( '' == $current_status ) {
																		$get_status_processing_count = count( $get_status_processing );
																		for ( $i = 0; $i < $get_status_processing_count; $i++ ) {
																			if ( array_key_exists( $get_status_processing[ $i ], $wps_status_change_time ) ) {
																				$current_status = $message . __( ' on ', 'track-orders-for-woocommerce' ) . $wps_status_change_time[ $get_status_processing[ $i ] ];
																			}
																		}
																	}
																	?>
																	<span class="wps-circle wps-tofw-hover <?php echo esc_attr( $class ); ?> <?php
																	if ( ! isset( $wps_track_order_status[ $key + 1 ] ) ) {
																		$active = 1;
																		echo 'active'; }
																	?>
																	" data-status = '
																	<?php
																	if ( 'revert' == $class ) {
																		esc_attr_e( 'Your Order is Sent back', 'track-orders-for-woocommerce' );
																	} else {
																		echo esc_attr( $message ); }
																	?>
'  ></span>	

																	<?php
																}
																if ( isset( $wps_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $wps_track_order_status[ $key + 1 ] && in_array( $value, $get_status_processing ) && 'wc-cancelled' == $order_status ) {
																	$cancelled = 1;
																	$current_status = get_option( 'wps_tofw_wc_cancelled_text', '' );
																	if ( '' == $current_status ) {
																		$current_status = __( 'Your Order is Cancelled', 'track-orders-for-woocommerce' );
																	}
																	?>
																	<span class="wps-circle order-cancelled"  data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

																	<?php
																}
															}
															if ( 1 != $f && 0 == $status_process && 0 == $status_shipped ) {
																?>
																<span class="wps-circle hollow" data-status=""></span> 
																<?php
															} else if ( 1 != $f && 0 == $status_process && 1 == $status_shipped ) {
																?>
																<span class="wps-circle" data-status="<?php esc_attr_e( 'Your Order Is Processed', 'track-orders-for-woocommerce' ); ?>"></span> 
																<?php
															}
														} else {
															?>
															<span class="wps-circle hollow" data-status=""></span> 
															<?php
														}
														?>
														<?php
													} else {
														$current_status = get_option( 'wps_tofw_wc_cancelled_text', __( 'Your Order is cancelled', 'track-orders-for-woocommerce' ) );
														?>
														<span class="wps-circle red" data-status="<?php echo esc_attr( $current_status ); ?>"></span> 
														<?php
													}
													?>
												</span>
												<span class="track-shipping">
													<?php if ( 1 != $cancelled ) { ?>
														<?php
														if ( 1 == $active ) {
															if ( ! empty( $wps_track_order_status ) && is_array( $get_status_shipping ) && ! empty( $get_status_shipping ) ) {
																foreach ( $get_status_shipping as $key => $value ) {
																	if ( in_array( $value, $wps_track_order_status ) ) {
																		$class = 'revert';
																	}
																}
															}
														}
														?>
														<?php
														$f = 0;

														if ( is_array( $wps_track_order_status ) && empty( $wps_track_order_status ) && '' != $order_status ) {
															?>
																<?php
																$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $order_status ] );

																?>
																<span class="wps-circle active" data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

															<?php
														} else if ( ! empty( $get_status_shipping ) && is_array( $wps_track_order_status ) && ! empty( $wps_track_order_status ) ) {
															$f = 0;
															foreach ( $wps_track_order_status as $key => $value ) {

																if ( in_array( $value, $get_status_shipping ) ) {

																	$f = 1;
																	$value_key = str_replace( '-', '_', $value );
																	$value_key = 'wps_tofw_' . $value_key . '_text';


																	$message = __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $value ];

																	?>
																	<?php
																	$current_status = get_option( $value_key, '' );
																	$get_status_shipping_count = count( $get_status_shipping );
																	for ( $i = 0; $i < $get_status_shipping_count; $i++ ) {

																		if ( array_key_exists( $get_status_shipping[ $i ], $wps_status_change_time ) ) {
																			$current_status = $current_status . __( ' on ', 'track-orders-for-woocommerce' ) . $wps_status_change_time[ $get_status_shipping[ $i ] ];
																		}
																	}
																	?>
																	<?php
																	if ( '' == $current_status ) {
																		$get_status_shipping_count = count( $get_status_shipping );
																		for ( $i = 0; $i < $get_status_shipping_count; $i++ ) {
																			if ( ! empty( $wps_status_change_time ) && is_array( $wps_status_change_time ) ) {
																				if ( in_array( $get_status_shipping[ $i ], $wps_status_change_time ) ) {
																					$current_status = $message . __( ' on ', 'track-orders-for-woocommerce' ) . $wps_status_change_time[ $get_status_shipping[ $i ] ];
																				}
																			}
																		}
																	}
																	?>
																		<span class="wps-circle wps-tofw-hover <?php echo esc_attr( $class ); ?> <?php
																		if ( ! isset( $wps_track_order_status[ $key + 1 ] ) ) {
																			$active = 1;
																			echo 'active'; }
																		?>
																		" data-status = '
																	<?php
																	if ( 'revert' == $class ) {
																			esc_attr_e( 'Your Order is Sent back', 'track-orders-for-woocommerce' );
																	} else {
																		echo esc_attr( $message ); }
																	?>
'  ></span>	

																		<?php
																}
																if ( isset( $wps_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $wps_track_order_status[ $key + 1 ] && in_array( $value, $get_status_shipping ) && 'wc-cancelled' == $order_status ) {
																	$cancelled = 1;
																	$current_status = get_option( 'wps_tofw_wc_cancelled_text', '' );
																	if ( '' == $current_status ) {
																		$current_status = __( 'Your Order is Cancelled', 'track-orders-for-woocommerce' );
																	}
																	?>
																		<span class="wps-circle order-cancelled"  data-status = '<?php echo esc_attr( $current_status ); ?>'></span>	

																		<?php
																}
															}
															if ( 1 != $f ) {
																?>
																	<span class="wps-circle hollow" data-status=""></span> 
																<?php
															}
														} else {
															?>
																<span class="wps-circle hollow" data-status=""></span> 
															<?php
														}
														?>
														<?php
													} else {
														$current_status = get_option( 'wps_tofw_wc_cancelled_text', __( 'Your Order is cancelled', 'track-orders-for-woocommerce' ) );
														?>
															<span class="wps-circle red" data-status="<?php echo esc_attr( $current_status ); ?>"></span> 
														<?php
													}
													?>
													</span>
													<div class="wps-deliver-msg wps-tofw-wps-delivery-msg"></div>
												</div>
											</div>
										</td>
										<?php if ( '' != $expected_delivery_date || '' != $expected_delivery_time || '' != $order_delivered_date ) { ?>
											<td>
												<?php
												if ( 'on' != $wps_tofw_enable_track_order_popup ) {
													?>
													<div class="wps-delivery-div">
													<?php
												} else {
													?>
														<div class="wps-delivery-div wps-after-delivery-div">
													<?php } ?>
														<span>
														<?php
														if ( 'wc-cancelled' == $order_status ) {
															esc_html_e( 'Order Cancelled', 'track-orders-for-woocommerce' );
														} else if ( '' == $order_delivered_date && 'wc-cancelled' != $order_status ) {
															esc_html_e( 'Not Delivered', 'track-orders-for-woocommerce' ); } else {
															echo esc_html__( 'on ', 'track-orders-for-woocommerce' ) . esc_html( date_i18n( 'd F, Y H:i', strtotime( $order_delivered_date ) ) );
															}
															?>
														</span>
														<?php
														if ( '' != $expected_delivery_date ) {
															?>
															<span>
															<?php
															if ( ( '' != $order_delivered_date ) || ( 'wc-cancelled' == $order_status ) ) {
																?>
	<del><?php echo esc_html__( 'by ', 'track-orders-for-woocommerce' ) . esc_html( date_i18n( 'F d, Y', strtotime( $expected_delivery_date ) ) ) . esc_html( $expected_delivery_time ); ?>
														</del>
																<?php
															} else {
																echo esc_html__( 'by ', 'track-orders-for-woocommerce' ) . esc_html( date_i18n( 'F d, Y', strtotime( $expected_delivery_date ) ) ) . esc_html( $expected_delivery_time );}
															?>
</span><?php } ?>
													</div>
												</td>
											<?php } ?>
										</tr>
									</tbody>
								</table>
							</section>
							
							<?php if ( ! empty( $wps_tofw_enhanced_customer_note ) ) { ?>
								<div class="wps-tofw-order-tracking-section ">
									<section class="section wps_tofw_product-details-section">
										<table class=" wps_tofw_shop_table order_details wps-product-details-table wps-tofw-track-order-table ">
											<thead>
												<tr>
													<th><?php esc_html_e( 'Customer Note :-', 'track-orders-for-woocommerce' ); ?></th>	
												</tr>
											</thead>
											<tbody>
												<tr>
													<td><?php echo esc_html( $wps_tofw_enhanced_customer_note ); ?></td>
												</tr>
											</tbody>
														
											
										</table>
									</section>
								</div>
								<?php
							}
		} else {
			?>
							<div>
								<input type="text" name="wps_tofw_track_no" id="wps_tofw_track_no">
								<input type="button" name="track" id="track" class="button alt" value="Track">
								<div id="YQContainer"></div>
							</div>

			<?php
			$return_request_not_send = __( 'Tracking Request can\'t be send. ', 'track-orders-for-woocommerce' );

			/**
			 * Tracking request.
			 *
			 * @since 1.0.0
			 */
			$return_request_not_send = apply_filters( 'wps_tofw_tracking_request_not_send', $return_request_not_send );
			echo wp_kses_post( $return_request_not_send );
			echo wp_kses_post( $reason );
		}
		?>
					</div>
					<?php
					/**
					 * Woocommerce_after_main_content hook.
					 *
					 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
					 */
					if ( 'on' != $wps_tofw_enable_track_order_popup ) {


						/**
						 * Add content.
						 *
						 * @since 1.0.0
						 */
						do_action( 'woocommerce_after_main_content' );
						get_footer( 'shop' );
					} elseif ( 'on' == $wps_tofw_enable_track_order_popup && $current_user_id > 0 && 0 != $order_id && '' != $order_id && null != $order_id ) {

						/**
						 * Add content.
						 *
						 * @since 1.0.0
						 */
						do_action( 'wps_tofw_after_popup' );
					} else {

						/**
						 * Add content.
						 *
						 * @since 1.0.0
						 */
						do_action( 'woocommerce_after_main_content' );
						get_footer( 'shop' );
					}
