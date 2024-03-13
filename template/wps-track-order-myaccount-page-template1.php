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

/**
	 * Woocommerce_before_main_content hook.
	 *
	 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
	 * @hooked woocommerce_breadcrumb - 20
	 */


$wps_main_wrapper_class = get_option( 'wps_tofw_main_wrapper_class_theme' );
$wps_child_wrapper_class = get_option( 'wps_tofw_child_wrapper_class' );
$wps_track_order_css = get_option( 'wps_tofw_custom_css_name' );
$wps_track_order_js = get_option( 'wps_tofw_custom_js_name' );
?>
<style id="wps-tofw-global-css" type="text/css">
<?php echo wp_kses_post( $wps_track_order_css ); ?>	
</style>
<script id="wps-tofw-global-js" type="text/javascript">
<?php echo wp_kses_post( wp_unslash( $wps_track_order_js ) ); ?>
</script>
<div class="wps-tofw-order-tracking-section <?php echo esc_attr( $wps_main_wrapper_class ); ?>">
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
		foreach ( $get_status_shipping as $key1 => $value1 ) {
			if ( ! empty( $wps_track_order_status ) && in_array( $value1, $wps_track_order_status ) ) {
				$status_shipped = 1;
			}
		}
	}
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

	if ( $allowed ) {
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
			$wps_current_status = $tofw_order->post_status;
			$ordered_by = $tofw_order->post->post_author;
			$ordered_by = get_user_by( 'ID', $ordered_by );
			if ( isset( $ordered_by ) && ! empty( $ordered_by ) ) {
				$ordered_by = $ordered_by->data->display_name;
			}
		} else {
			$get_status = $tofw_order->get_data( $order_id );
			$wps_current_status = $get_status['status'];
			$order_status = 'wc-' . $tofw_order->get_status();
			$order_data = $tofw_order->get_data();
			$ordered_by = $order_data['customer_id'];
			$ordered_by = get_user_by( 'ID', $ordered_by );
			if ( isset( $ordered_by ) && ! empty( $ordered_by ) ) {
				$ordered_by = $ordered_by->data->display_name;
			}
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
			$order_onchange_time = $tofw_order->get_meta( 'wps_track_order_onchange_time', true );
		} else {
			$billing_first_name = get_post_meta( $order_id, '_billing_first_name', true );
			$billing_last_name = get_post_meta( $order_id, '_billing_last_name', true );
			$billing_address = get_post_meta( $order_id, '_billing_address_1', true ) . ' ' . get_post_meta( $order_id, '_billing_address_2', true );
			$billing_city = get_post_meta( $order_id, '_billing_city', true );
			$billing_state = get_post_meta( $order_id, '_billing_state', true );
			$billing_country = get_post_meta( $order_id, '_billing_country', true );
			$billing_postcode = get_post_meta( $order_id, '_billing_postcode', true );
			$wps_track_order_status = get_post_meta( $order_id, 'wps_track_order_status', true );
			$order_onchange_time = get_post_meta( $order_id, 'wps_track_order_onchange_time', true );
		}


		$order_status_key = str_replace( '-', '_', $order_status );
		$order_status_key = 'wps_tofw_' . $order_status_key . '_text';
		$total = 0;
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

					$total = $product->get_price() * $item['qty'];

				}
			}
		} else {
			$total = 0;
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
					$total = $product->get_price() * $item['qty'];
				}
			}
		}
		$completed = 0;
		$onchange_approval_date = '';
		$onchange_processing_date = '';
		$onchange_shipping_date = '';
		$onchange_cancle_date = '';
		$no_shipping = 0;
		$no_processing = 0;
		$order_cancel = 0;
		?>
		<?php
		if ( 'wc-cancelled' == $order_status ) {
			$order_cancel = 1;
			$previous_status = $wps_track_order_status[ count( $wps_track_order_status ) - 2 ];
			if ( ! empty( $get_status_approval ) && is_array( $get_status_approval ) && in_array( $previous_status, $get_status_approval ) ) {
				$no_processing = 1;
				$no_shipping = 1;
			} elseif ( ! empty( $get_status_processing ) && is_array( $get_status_processing ) && in_array( $previous_status, $get_status_processing ) ) {
				$no_processing = 0;
				$no_shipping = 1;
			} else {
				$no_processing = 0;
				$no_shipping = 0;
			}
		}

		$modified_current_status = 'wc-' . $wps_current_status;
		$c = 0;

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS usage is enabled.
			$wps_tofw_enhanced_customer_note = $tofw_order->get_meta( 'wps_tofw_enhanced_cn', true );
		} else {
			$wps_tofw_enhanced_customer_note = get_post_meta( $order_id, 'wps_tofw_enhanced_cn', true );
		}

		if ( ! empty( $wps_tofw_enhanced_customer_note ) ) {
			$wps_tofw_enhanced_customer_note = $wps_tofw_enhanced_customer_note;
		} else {
			$wps_tofw_enhanced_customer_note = '';
		}
		?>
		<div class="wps_tofw_order_tab <?php echo esc_attr( $wps_child_wrapper_class ); ?>">
			<ul class="wps_tofw_order_track_link">
				<li id="wps_order_detail_section" ><a href="javascript:;"><?php esc_html_e( 'Order Details', 'track-orders-for-woocommerce' ); ?></a></li>
				<li id="wps_order_track_section" ><a href="javascript:;"><?php esc_html_e( 'Track Order', 'track-orders-for-woocommerce' ); ?></a></li>
				
			</ul>
		</div>
		
		<section class="wps_tofw_header" id="wps_progress_bar">
			<div class="wps_tofw_header-wrapper_template2">
				
				<div class="wps_progress">
					<div class="circle_tofw_wps wps_done" id="wps_placed">
						<span class="label"></span>
						<span class="title"><?php esc_html_e( 'Placed', 'track-orders-for-woocommerce' ); ?></span>
						
					</div>
					<span class="bar_tofw_wps wps_done"></span>
					<div class="circle_tofw_wps wps_done" id="wps_approved">
						<?php
						if ( ! empty( $get_status_approval ) && is_array( $get_status_approval ) && ! empty( $modified_current_status ) && in_array( $modified_current_status, $get_status_approval ) ) {
							?>
							<input type="hidden" class="hidden_value" value="2">
							<?php
						}
						?>
						<span class="label"></span>
						<span class="title"><?php esc_html_e( 'Approved', 'track-orders-for-woocommerce' ); ?></span>
					</div>
					<?php
					if ( 1 == $no_processing && 1 == $no_shipping ) {
						$c = 1;
						?>
						<span class="bar_tofw_wps"></span>
						<div class="circle_tofw_wps wps_done" id="wps_approved">
							<input type="hidden" class="hidden_value" value="3">
							<span class="label"></span>
							<span class="title"><?php esc_html_e( 'Cancelled', 'track-orders-for-woocommerce' ); ?></span>
						</div>
						<?php
					}
					?>
					<span class="bar_tofw_wps half"></span>
					<div class="circle_tofw_wps active" id="wps_processing">
						<?php
						if ( ! empty( $get_status_processing ) && is_array( $get_status_processing ) && ! empty( $modified_current_status ) && in_array( $modified_current_status, $get_status_processing ) ) {
							?>
							<input type="hidden" class="hidden_value" value="3">
						<?php } ?>
						<span class="label"></span>
						<span class="title"><?php esc_html_e( 'Processed', 'track-orders-for-woocommerce' ); ?></span>
					</div>
					<?php
					if ( 0 == $no_processing && 1 == $no_shipping ) {
						$c = 1;
						?>
						<span class="bar_tofw_wps"></span>
						<div class="circle_tofw_wps wps_done" id="wps_approved">
							<span class="label"></span>
							<span class="title"><?php esc_html_e( 'Cancelled', 'track-orders-for-woocommerce' ); ?></span>
							<input type="hidden" class="hidden_value" value="4">
						</div>
					<?php } ?>
					<span class="bar_tofw_wps"></span>
					<div class="circle_tofw_wps" id="wps_shipping">
						<?php if ( ! empty( $get_status_shipping ) && is_array( $get_status_shipping ) && ! empty( $modified_current_status ) && in_array( $modified_current_status, $get_status_shipping ) && 'wc-completed' != $order_status ) { ?>
							<input type="hidden" class="hidden_value" value="4">
						<?php } ?>
						<span class="label"></span>
						<span class="title"><?php esc_html_e( 'Shipped', 'track-orders-for-woocommerce' ); ?></span>
					</div>
					<?php
					if ( 0 == $no_processing && 0 == $no_shipping && 'wc-cancelled' == $order_status ) {
						$c = 1;
						?>
						<span class="bar_tofw_wps"></span>
						<div class="circle_tofw_wps wps_done" id="wps_cancelled">
							<span class="label"></span>
							<span class="title"><?php esc_html_e( 'Cancelled', 'track-orders-for-woocommerce' ); ?></span>
							<input type="hidden" class="hidden_value" value="5">
						</div>
						<?php
					}
					if ( 'wc-completed' == $order_status ) {
						$completed = 1;
						?>
						<span class="bar_tofw_wps"></span>
						<div class="circle_tofw_wps" id="wps_delivered" >
							<span class="label"></span>
							<span class="title"><?php esc_html_e( 'Delivered', 'track-orders-for-woocommerce' ); ?></span>
							<input type="hidden" class="hidden_value" value="6">
						</div>
						<?php
					} else {
						if ( 0 == $c && 1 != $completed ) {
							?>
								<span class="bar_tofw_wps"></span>
								<div class="circle_tofw_wps wps_done" id="wps_delivered">
									<span class="label"></span>
									<span class="title"><?php esc_html_e( 'Delivery', 'track-orders-for-woocommerce' ); ?></span>
									<input type="hidden" class="hidden_value" value="6">
								</div>
							<?php
						}
					}
					?>
					</div>
					<!-- </div> -->
					<?php
					$shipping = 0;
					$processing = 0;
					$shipping_blk = 0;
					$processing_blk = 0;
					if ( is_array( $get_status_processing ) && ! empty( $get_status_processing ) ) {
						foreach ( $get_status_processing as $key => $value ) {
							if ( ! empty( $wps_track_order_status ) && in_array( $value, $wps_track_order_status ) ) {
								$processing = 1;
								break;
							}
						}
					}
					?>
					<?php
					if ( is_array( $get_status_shipping ) && ! empty( $get_status_shipping ) ) {
						foreach ( $get_status_shipping as $key => $value ) {
							if ( ! empty( $wps_track_order_status ) && is_array( $wps_track_order_status ) && in_array( $value, $wps_track_order_status ) ) {
								$shipping = 1;
								break;
							}
						}
					}
					if ( ! empty( $wps_track_order_status ) && is_array( $wps_track_order_status ) ) {
						$length = count( $wps_track_order_status );
						$current_last_status = $wps_track_order_status[ $length - 1 ];
						$previous_last_status = $wps_track_order_status[ $length - 2 ];
					}

					if ( ! empty( $get_status_approval ) && ! empty( $current_last_status ) && is_array( $get_status_approval ) && in_array( $current_last_status, $get_status_approval ) ) {
						$shipping_blk = 1;
						$processing_blk = 1;
						$processing = 0;
						$shipping = 0;
					} else if ( ! empty( $get_status_processing ) && ! empty( $current_last_status ) && is_array( $get_status_processing ) && in_array( $current_last_status, $get_status_processing ) ) {
						$shipping_blk = 1;
						$processing_blk = 0;
						$processing = 1;
						$shipping = 0;
					}
					if ( 1 == $completed ) {
						$shipping = 1;
						$processing = 1;
						$shipping_blk = 0;
						$processing_blk = 0;
					}
					if ( 'wc-cancelled' == $order_status && 1 == $no_processing ) {
						$shipping_blk = 1;
						$processing_blk = 1;
					} else if ( 'wc-cancelled' == $order_status && 1 == $no_shipping ) {
						$shipping_blk = 1;
						$processing_blk = 0;
					}
					?>
				</div>
			</section>
			<section class="wps_tofw_order_tracker_content approval" id="wps_approval">
				<div class="wps-deliver-msg wps-tofw-wps-msg">
					<h3><?php esc_html_e( 'Approval', 'track-orders-for-woocommerce' ); ?></h3>
					<ul class="wps-tofw-order-info">
						<li><?php esc_html_e( 'placed', 'track-orders-for-woocommerce' ); ?></li>
						<li><?php echo esc_html( $tofw_order->get_date_created()->date( 'F d, Y H:i ' ) ); ?></li>
						<li><?php esc_html_e( 'Your Order is Successfully Placed', 'track-orders-for-woocommerce' ); ?></li></ul>
						<?php


						$class = '';
						$active = 0;
						$f = 0;
						$cancelled = 0;
						if ( is_array( $wps_track_order_status ) && empty( $wps_track_order_status ) && '' != $order_status && in_array( $order_status, $get_status_approval ) ) {

							$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $order_status ] );
							if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
								foreach ( $order_onchange_time as $changekey => $changevalue ) {
									if ( $order_status == $changekey ) {
										$onchange_approval_date = $changevalue;
									}
								}
							}
							?>
							<ul class="wps-tofw-order-info">
								<li><?php echo esc_html( $order_status ); ?></li>
								<li><?php echo esc_html( $onchange_approval_date ); ?></li>
								<li><?php echo esc_html( $current_status ); ?>"</li></ul>
								<?php
						} else if ( is_array( $wps_track_order_status ) && ! empty( $wps_track_order_status ) && is_array( $get_status_approval ) && ! empty( $get_status_approval ) ) {

							$f = 0;
							foreach ( $wps_track_order_status as $key => $value ) {
								if ( in_array( $value, $get_status_approval ) ) {
									$f = 1;
									$value_key = str_replace( '-', '_', $value );
									$value_key = 'wps_tofw_' . $value_key . '_text';
									$message = __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $value ];
									$current_status = get_option( $value_key, '' );
									if ( '' == $current_status ) {
										$current_status = $message;
									}
									if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
										foreach ( $order_onchange_time as $key1 => $value1 ) {

											if ( $value == $key1 ) {
												$onchange_approval_date = $value1;
											}
										}
									}
									?>
										<ul class="wps-tofw-order-info">
											<li><?php echo esc_html( $woo_statuses[ $value ] ); ?></li>
											<li>
											<?php
											if ( isset( $onchange_approval_date ) && '' != $onchange_approval_date ) {
												echo esc_html( $onchange_approval_date );
											} else {
												echo esc_html( $tofw_order->get_date_created()->date( 'd F, Y H:i' ) );
												; }
											?>
											</li>
											<li><?php echo esc_html( $current_status ); ?></li></ul>
										<?php
								}
								if ( isset( $wps_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $wps_track_order_status[ $key + 1 ] && in_array( $value, $get_status_approval ) && 'wc-cancelled' == $order_status ) {
									$cancelled = 1;
									$current_status = get_option( 'wps_tofw_wc_cancelled_text', '' );
									if ( '' == $current_status ) {
										$current_status = __( 'Your Order is Cancelled', 'track-orders-for-woocommerce' );
									}
									if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
										foreach ( $order_onchange_time as $change_key1 => $change_value1 ) {
											if ( $wps_track_order_status[ $key + 1 ] == $change_key1 ) {
												$onchange_approval_date = $change_value1;
											}
										}
									}
									?>
											<ul class="wps-tofw-order-info">
												<li><?php esc_html_e( 'Cancelled', 'track-orders-for-woocommerce' ); ?></li>
												<li><?php echo esc_html( $change_value1 ); ?> </li>
												<li><?php echo esc_html( $current_status ); ?>"</li></ul>
										<?php
								}
							}
						}
						?>
								</div>
							</section>
							<?php

							$status_counter = 0;
							if ( ! empty( $get_status_processing ) && is_array( $get_status_processing ) ) {
								foreach ( $get_status_processing as $preocessing_key => $processing_value ) {
									if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
										foreach ( $order_onchange_time as $order_key => $order_value ) {
											if ( $processing_value == $order_key ) {
												$status_counter = 1;
											}
										}
									}
								}
							}
							if ( 1 == $status_counter ) {
								?>
								<section class="wps_tofw_order_tracker_content processing" id="wps_process">
									<div class="wps-deliver-msg wps-tofw-wps-msg">
										<h3><?php esc_html_e( 'processing', 'track-orders-for-woocommerce' ); ?></h3>
										<?php
										if ( 1 != $cancelled ) {
											if ( is_array( $wps_track_order_status ) && empty( $wps_track_order_status ) && '' != $order_status ) {
												$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $order_status ] );
												if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
													foreach ( $order_onchange_time as $changekey1 => $changevalue1 ) {
														if ( $order_status == $changevalue1 ) {
															$onchange_processing_date = $changevalue1;
														}
													}
												}
												?>
												<ul class="wps-tofw-order-info">
													<li><?php echo esc_html( $woo_statuses[ $order_status ] ); ?></li>
													<li><?php echo esc_html( $onchange_processing_date ); ?></li>
													<li><?php echo esc_html( $current_status ); ?>"</li></ul>
													<?php
											} else if ( is_array( $wps_track_order_status ) && ! empty( $wps_track_order_status ) ) {
												$f = 0;
												foreach ( $wps_track_order_status as $key => $value ) {
													if ( ! empty( $get_status_processing ) && is_array( $get_status_processing ) && in_array( $value, $get_status_processing ) ) {
														$f = 1;
														$value_key = str_replace( '-', '_', $value );
														$value_key = 'wps_tofw_' . $value_key . '_text';
														$message = __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $value ];
														$current_status = get_option( $value_key, '' );
														if ( '' == $current_status ) {
															$current_status = $message;
														}

														if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
															foreach ( $order_onchange_time as $key1 => $value1 ) {

																if ( $value == $key1 ) {
																	$onchange_processing_date = $value1;
																}
															}
														}
														?>
															<ul class="wps-tofw-order-info">
																<li><?php echo esc_html( $woo_statuses[ $value ] ); ?></li>
																<li><?php echo esc_html( $onchange_processing_date ); ?></li>
																<li><?php echo esc_html( $current_status ); ?></li></ul>
																<?php
													}
													if ( ! empty( $get_status_processing ) && is_array( $get_status_processing ) && isset( $wps_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $wps_track_order_status[ $key + 1 ] && in_array( $value, $get_status_processing ) && 'wc-cancelled' == $order_status ) {
														$cancelled = 1;
														$current_status = get_option( 'wps_tofw_wc_cancelled_text', '' );
														if ( '' == $current_status ) {
															$current_status = __( 'Your Order is Cancelled', 'track-orders-for-woocommerce' );
														}
														if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
															foreach ( $order_onchange_time as $key2 => $value2 ) {
																if ( $wps_track_order_status[ $key + 1 ] == $key2 ) {
																	$onchange_processing_date = $value2;
																}
															}
														}
														?>
																<ul class="wps-tofw-order-info">
																	<li><?php esc_html_e( 'Cancelled', 'track-orders-for-woocommerce' ); ?></li>
																	<li><?php echo esc_html( $onchange_processing_date ); ?></li>
																	<li><?php echo esc_html( $current_status ); ?></li></ul>
															<?php
													}
												}
											}
											?>
														<?php
										} else {
											$current_status = get_option( 'wps_tofw_wc_cancelled_text', __( 'Your Order is cancelled', 'track-orders-for-woocommerce' ) );
											if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
												foreach ( $order_onchange_time as $key_change1 => $value_change1 ) {

													if ( ! empty( $order_status ) && $order_status == $key_change1 ) {
														$onchange_processing_date = $value_change1;
													}
												}
											}
											?>
														<ul class="wps-tofw-order-info">
															<li><?php esc_html_e( 'Cancelled', 'track-orders-for-woocommerce' ); ?></li>
															<li><?php echo esc_html( $onchange_processing_date ); ?></li>
															<li><?php echo esc_html( $current_status ); ?></li></ul>
												<?php
										}
										?>
													</div>
												</section>
												<?php
							} else {
								if ( ! empty( $get_status_shipping ) && is_array( $get_status_shipping ) && ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
									foreach ( $get_status_shipping as $ship_key => $ship_value ) {
										foreach ( $order_onchange_time as $order_key => $order_value ) {
											if ( $order_value == $ship_value ) {
												$status_counter = 1;
											}
										}
									}
								}
								if ( 1 == $status_counter ) {
									?>
													<section class="wps_tofw_order_tracker_content processing">
														<div class="wps-deliver-msg wps-tofw-wps-msg">
															<ul class="wps-tofw-order-info">
																<li><?php esc_html_e( 'Your Order is processed', 'track-orders-for-woocommerce' ); ?></li>

															</ul>
														</div></section>
										<?php
								}
							}
							?>
												<?php
												$status_counter = 0;
												if ( ! empty( $get_status_shipping ) && is_array( $get_status_shipping ) && ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
													foreach ( $get_status_shipping as $shiping_key => $shiping_value ) {

														foreach ( $order_onchange_time as $order_key => $order_value ) {
															if ( $shiping_value == $order_key ) {
																$status_counter = 1;
															}
														}
													}
												}
												if ( 1 == $status_counter ) {
													?>
													<section class="wps_tofw_order_tracker_content shipping" id="wps_ship">
														<div class="wps-deliver-msg wps-tofw-wps-msg">
															<h3><?php esc_html_e( 'Shipping', 'track-orders-for-woocommerce' ); ?></h3>
															<?php
															if ( 1 != $cancelled ) {
																if ( is_array( $wps_track_order_status ) && empty( $wps_track_order_status ) && '' != $order_status ) {
																	$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $order_status ] );
																	if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
																		foreach ( $order_onchange_time as $keychange => $valuechange ) {
																			if ( $order_status == $keychange ) {
																				$onchange_shipping_date = $valuechange;
																			}
																		}
																	}
																	?>
																	<ul class="wps-tofw-order-info">
																		<li><?php echo esc_html( $woo_statuses[ $order_status ] ); ?></li>
																		<li><?php echo esc_html( $onchange_shipping_date ); ?></li>
																		<li><?php echo esc_html( $current_status ); ?>"</li></ul>
																		<?php
																} else if ( is_array( $wps_track_order_status ) && ! empty( $wps_track_order_status ) && ! empty( $get_status_shipping ) && is_array( $get_status_shipping ) ) {
																	$f = 0;
																	foreach ( $wps_track_order_status as $key => $value ) {
																		if ( in_array( $value, $get_status_shipping ) ) {
																			$f = 1;
																			$value_key = str_replace( '-', '_', $value );
																			$value_key = 'wps_tofw_' . $value_key . '_text';
																			$message = __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $value ];
																			$current_status = get_option( $value_key, '' );
																			if ( '' == $current_status ) {
																				$current_status = $message;
																			}

																			if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
																				foreach ( $order_onchange_time as $key1 => $value1 ) {

																					if ( $value == $key1 ) {
																						$onchange_shipping_date = $value1;
																					}
																				}
																			}
																			?>
																				<ul class="wps-tofw-order-info">
																					<li><?php echo esc_html( $woo_statuses[ $value ] ); ?></li>
																					<li><?php echo esc_html( $onchange_shipping_date ); ?></li>
																					<li><?php echo esc_html( $current_status ); ?></li></ul>
																					<?php
																		}
																		if ( ! empty( $get_status_shipping ) && isset( $wps_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $wps_track_order_status[ $key + 1 ] && in_array( $value, $get_status_shipping ) && 'wc-cancelled' == $order_status ) {
																			$cancelled = 1;
																			$current_status = get_option( 'wps_tofw_wc_cancelled_text', '' );
																			if ( '' == $current_status ) {
																				$current_status = __( 'Your Order is Cancelled', 'track-orders-for-woocommerce' );
																			}
																			if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
																				foreach ( $order_onchange_time as $change_key => $change_value ) {
																					if ( $wps_track_order_status[ $key + 1 ] == $change_key ) {
																						$onchange_shipping_date = $change_value;
																					}
																				}
																			}
																			?>
																					<ul class="wps-tofw-order-info">
																						<li><?php echo esc_html_e( 'Cancelled', 'track-orders-for-woocommerce' ); ?></li>
																						<li><?php echo esc_html( $onchange_shipping_date ); ?></li>
																						<li><?php echo esc_html( $current_status ); ?></li></ul>
																					<?php
																		}
																	}
																}
																?>
																			<?php
															} else {
																$current_status = get_option( 'wps_tofw_wc_cancelled_text', __( 'Your Order is cancelled', 'track-orders-for-woocommerce' ) );
																if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
																	foreach ( $order_onchange_time as $key_change => $value_change ) {
																		if ( $order_status == $key_change ) {
																			$onchange_shipping_date = $value_change;
																		}
																	}
																}
																?>
																			<ul class="wps-tofw-order-info">
																				<li><?php esc_html_e( 'Cancelled', 'track-orders-for-woocommerce' ); ?></li>
																				<li><?php echo esc_html( $onchange_shipping_date ); ?></li>
																				<li><?php echo esc_html( $current_status ); ?></li></ul>
																	<?php
															}
															?>
																		</div>
																	</section>
																	<?php
												} elseif ( ( '' != $expected_delivery_date ) && ( 'wc-completed' == $order_status ) ) {
													?>
																	<section class="wps_tofw_order_tracker_content shipping">
																		<div class="wps-deliver-msg wps-tofw-wps-msg">
																			<ul class="wps-tofw-order-info">
																				<li><?php esc_html_e( 'Your Order is Shipped', 'track-orders-for-woocommerce' ); ?></li>
																			</ul>
																		</div></section>
														<?php
												}
												?>
																	<section class="wps_tofw_order_tracker_content delivery" id="wps_delivery">
																		<div class="wps-deliver-msg wps-tofw-wps-msg">
																			<?php
																			if ( 1 == $completed ) {
																				?>
																				<h3><?php esc_html_e( 'Delivered', 'track-orders-for-woocommerce' ); ?></h3>
																				<?php
																				if ( '' == $expected_delivery_date || '' == $expected_delivery_time || '' != $order_delivered_date ) {
																					if ( 'wc-cancelled' == $order_status && '' == $order_delivered_date ) {
																						$message = __( 'Order Cancelled', 'track-orders-for-woocommerce' );
																					} else if ( '' != $order_delivered_date && 'wc-cancelled' == $order_status ) {
																						$message = __( 'Not Delivered', 'track-orders-for-woocommerce' );
																					} else {
																						$message = __( 'Your order is completed and delivered on ', 'track-orders-for-woocommerce' ) . date_i18n( 'd F, Y H:i', strtotime( $order_delivered_date ) );
																					}
																					?>
																					<span class="order-delivered-info">
																						<?php echo esc_html( $message ); ?>
																					</span>
																					<?php
																				}
																			} else if ( 1 == $order_cancel ) {
																				$message_cancelled = __( 'Your order is Cancelled', 'track-orders-for-woocommerce' );
																				?>
																				<span class="order-delivered-info">
																					<?php echo esc_html( $message_cancelled ); ?>
																				</span>
																				<?php
																			} else if ( '' != $expected_delivery_date ) {

																				if ( ( '' != $order_delivered_date ) && ( 'wc-cancelled' == $order_status ) ) {
																					$message_expected = __( 'Your order is expected to delivered by ', 'track-orders-for-woocommerce' ) . date_i18n( 'd F, Y H:i', strtotime( $expected_delivery_date ) ) . $expected_delivery_time;

																				} elseif ( 'wc-completed' == $order_status ) {
																					$message_expected = __( 'Your order is Completed on ', 'track-orders-for-woocommerce' ) . date_i18n( 'd F, Y H:i', strtotime( $order_delivered_date ) );
																				} else {
																					$message_expected = __( 'Your order is expected to delivered by ', 'track-orders-for-woocommerce' ) . date_i18n( 'F d, Y ', strtotime( $expected_delivery_date ) ) . $expected_delivery_time;
																				}
																				?>
																				<p class="oreder-info">
																					<?php echo esc_html( $message_expected ); ?>
																				</p>
																				<?php
																			}
																			?>

																		</div>
																	</section>
																	<div class="wps_tofw_header" id="wps_product">
																		<div class="section wps_tofw_product-details-section-template ">
																		
																			<table class="wps_tofw_shop_table order_details wps-product-details-table wps-product-details-table-template">
																				<thead>
																					<tr>
																						<th><?php esc_html_e( 'Product Details', 'track-orders-for-woocommerce' ); ?></th>
																						<th><?php esc_html_e( 'Quantity', 'track-orders-for-woocommerce' ); ?></th>
																						<th><?php esc_html_e( 'Sub Total', 'track-orders-for-woocommerce' ); ?></th>
																					</tr>
																				</thead>
																				<tbody>
																					<?php
																					if ( WC()->version < '3.0.0' ) {
																						foreach ( $tofw_order->get_items() as $item_id => $item ) {
																							if ( $item['qty'] > 0 ) {
																								/**
																								 * Add product.
																								 *
																								 * @since 1.0.0
																								 */
																								$product = apply_filters( 'woocommerce_order_item_product', $item->get_product, $item );

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

																								$total = $product->get_price() * $item['qty'];
																								?>
																								<tr>
																									<td>
																										<div class="wps-product-wrapper wps-product-img">

																											<?php
																											if ( isset( $thumbnail ) && ! empty( $thumbnail ) ) {
																												echo wp_kses_post( $thumbnail );
																											} else {
																												?>
																												<img alt="<?php esc_attr_e( 'Placeholder', 'track-orders-for-woocommerce' ); ?>" class="wps_tofw_attachment-thumbnail size-thumbnail wp-post-image wps-img-responsive" src="<?php echo esc_attr( home_url() ); ?>/wp-content/plugins/woocommerce/assets/images/placeholder.png">
																												<?php
																											}
																											?>
																												
																										</div>
																										<div class="wps-product-wrapper wps-product-desc">
																											<h4><a href=""><?php echo esc_attr( $productdata->post->post_title ); ?></a></h4>
																										</div>
																									</td>
																									<td class="wpstext-center">
																										<?php echo esc_html( $item['qty'] ); ?>

																									</td>
																									<td>
																										<span class="wpstext-center"><span class="wps_rnx_formatted_price"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></span></span>
																									</td>
																								</tr>
																								<?php
																							}
																						}
																					} else {
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
																								$total = $product->get_price() * $item['qty'];
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
																												<img alt="<?php esc_html_e( 'Placeholder', 'track-orders-for-woocommerce' ); ?>" class="wps_tofw_attachment-thumbnail size-thumbnail wp-post-image wps-img-responsive" src="<?php echo esc_attr( home_url() ); ?>/wp-content/plugins/woocommerce/assets/images/placeholder.png">
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
																										<span class="wpstext-center"><span class="wps_rnx_formatted_price"><?php echo wp_kses_post( wc_price( $total ) ); ?></span></span>
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
																								<span><h4><?php esc_html_e( 'Total', 'track-orders-for-woocommerce' ); ?></h4></span>
																							</div>
																						</td>
																						<td id="wps_tofw_total_quantity">
																							<?php echo esc_html( $wps_tofw_total_qty ); ?>
																						</td>
																						<td>
																							<div>
																								<span class="wpstext-center"><span class="wps_rnx_formatted_price"><?php echo wp_kses_post( wc_price( $wps_tofw_grand_total ) ); ?></span></span>
																							</div>
																						</td>
																					</tr>
																				</tbody>
																			</table>
																		</div>
																	</div>
																	<div class="section wps_tofw_product-details-section-template" id="wps_order">
																		<div class="wps_tofw_order-details-wrap">
																			<div class="wps_tofw_oders-detail">
																				<h3><?php esc_html_e( 'Order Details', 'track-orders-for-woocommerce' ); ?></h3>
																				<p><span><?php esc_html_e( 'Order Id', 'track-orders-for-woocommerce' ); ?></span><span><?php echo esc_html( $order_id ); ?><?php echo '(' . count( $tofw_order->get_items() ) . esc_html_e( ' items', 'track-orders-for-woocommerce' ) . ')'; ?></span></p>
																				<p><span><?php esc_html_e( 'Order date', 'track-orders-for-woocommerce' ); ?></span> <span>
																								   <?php
																									if ( WC()->version < '3.0.0' ) {
																										echo esc_html( date_i18n( 'd F, Y H:i', strtotime( $tofw_order->post->post_date ) ) );
																									} else {
																										$wps_date = $tofw_order->get_date_created()->date( 'd F, Y H:i' );
																										echo esc_html( $wps_date );}
																									?>
																				</span></p>
																				<p><span><?php esc_html_e( 'Amount paid', 'track-orders-for-woocommerce' ); ?></span> <span><?php echo wp_kses_post( wc_price( $tofw_order->get_total() ) ); ?></span></p>
																			</div>
																			<div class="wps_tofw_user_address">
																				<?php
																				$wps_billing_phone = OrderUtil::custom_orders_table_usage_is_enabled() ? $tofw_order->get_billing_phone() : get_post_meta( $order_id, '_billing_phone', true );
																				?>
																				<h3> <?php echo esc_html( $billing_first_name ) . ' ' . esc_html( $billing_last_name ) . ' ' . esc_html( $wps_billing_phone ); ?></h3>
																				<p><?php echo esc_html( $billing_address ); ?></p>
																				<p><?php echo esc_html( $billing_city ) . ', ' . esc_html( $billing_state ) . ' -' . esc_html( $billing_postcode ); ?></p>
																				<p><?php echo esc_html( WC()->countries->countries[ $billing_country ] ); ?></p>
																			</div>
																		</div>
																	</div>
																	<?php if ( ! empty( $wps_tofw_enhanced_customer_note ) ) { ?>
																		<div class="wps-tofw-order-tracking-section " id="wps_notice">
																			<section class="wps_tofw_order_tracker_content">
																				<div class=" wps-deliver-msg wps-tofw-wps-msg ">
																					<h3><?php esc_html_e( 'Customer Note :-', 'track-orders-for-woocommerce' ); ?></h3>				
																					<span><?php echo esc_html( $wps_tofw_enhanced_customer_note ); ?></span>
																				</div>
																			</section>
																		</div>
																		<?php
																	}
	} else {
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
																// phpcs:disable
																?>
																<link rel="stylesheet" type="text/css" href="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . '/public/css/track-orders-for-woocommerce-public.css'; ?>" media="screen">
																<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>
																<script type="text/javascript" src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'public/js/track-orders-for-woocommerce-public.js'; ?>"></script>
																<?php
																// phpcs:disable
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
