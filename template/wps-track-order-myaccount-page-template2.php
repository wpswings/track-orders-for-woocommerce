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
				 * Add reason.
				 *
				 * @since 1.0.0
				 */
				$reason = apply_filters( 'wps_tofw_track_choose_order', $reason );
			}
		} else // check order associated to customer account or not for guest user.
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

if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
	// HPOS usage is enabled.
	$wps_tofw_enhanced_customer_note = $order_obj->get_meta( 'wps_tofw_enhanced_cn', true );
} else {
	$wps_tofw_enhanced_customer_note = get_post_meta( $order_id, 'wps_tofw_enhanced_cn', true );
}


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
			$conexpected_delivery_timeverted_ = $tofw_order->get_meta( 'wps_tofw_estimated_delivery_time', true );
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
			if ( isset( $ordered_by ) && ! empty( $ordered_by ) ) {
				$ordered_by = $ordered_by->data->display_name;
			}
		} else {
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
		if ( 'wc-completed' == $order_status ) {
			$completed = 1;
		}
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
			} elseif ( ! empty( $get_status_approval ) && is_array( $get_status_approval ) & in_array( $previous_status, $get_status_processing ) ) {
				$no_processing = 0;
				$no_shipping = 1;
			} else {
				$no_processing = 0;
				$no_shipping = 0;
			}
		}
		?>
		<section class="wps_tofw_header wps_tofw_header--template2 <?php echo esc_attr( $wps_child_wrapper_class ); ?>">
			<div class="wps_tofw_header-wrapper">
				<div class="wps_tofw_oder-tracker_gifimg">
					
				</div>
				<?php
				$shipping = 0;
				$processing = 0;
				$shipping_blk = 0;
				$processing_blk = 0;
				if ( is_array( $get_status_processing ) && ! empty( $get_status_processing ) && ! empty( $wps_track_order_status ) ) {
					foreach ( $get_status_processing as $key => $value ) {
						if ( ! empty( $wps_track_order_status ) && is_array( $wps_track_order_status ) && in_array( $value, $wps_track_order_status ) ) {
							$processing = 1;
							break;
						}
					}
				}
				?>
				<?php
				if ( is_array( $get_status_shipping ) && ! empty( $get_status_shipping ) && ! empty( $wps_track_order_status ) ) {
					foreach ( $get_status_shipping as $key => $value ) {
						if ( ! empty( $wps_track_order_status ) && is_array( $wps_track_order_status ) && in_array( $value, $wps_track_order_status ) ) {
							$shipping = 1;
							break;
						}
					}
				}
				?>
				<?php
				if ( ! empty( $wps_track_order_status ) && is_array( $wps_track_order_status ) ) {

					$length = count( $wps_track_order_status );
					$current_last_status = $wps_track_order_status[ $length - 1 ];
					$previous_last_status = $wps_track_order_status[ $length - 2 ];
				}
				if ( ! empty( $get_status_approval ) && is_array( $get_status_approval ) && in_array( $current_last_status, $get_status_approval ) ) {
					$shipping_blk = 1;
					$processing_blk = 1;
					$processing = 0;
					$shipping = 0;
				} else if ( ! empty( $get_status_processing ) && is_array( $get_status_processing ) && in_array( $current_last_status, $get_status_processing ) ) {
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
				<ul class="wps_tofw_process_steps_wrap">
					<li id="wps_placed_order">
						<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
							<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#E7E7E7"/>
							<path d="M21.5385 24.6154L24 36.3077M21.5385 24.6154L20.0311 17.5809C19.8335 16.6588 19.0186 16 18.0755 16H16M21.5385 24.6154H28.9231M24 36.3077H44.1117C44.5846 36.3077 44.9928 35.9764 45.0902 35.5137L47.1307 25.8214C47.2616 25.1998 46.7873 24.6154 46.1522 24.6154H40.6154M24 36.3077L24.8163 39.1648C25.0616 40.0234 25.8464 40.6154 26.7394 40.6154H44.9231" stroke="black" stroke-linecap="round"/>
							<circle cx="27.6923" cy="44.9231" r="2.57692" stroke="black"/>
							<circle cx="41.2308" cy="44.9231" r="2.57692" stroke="black"/>
							<path d="M35.0769 19.6923V29.5385M35.0769 29.5385L32 26.5299M35.0769 29.5385L38.1538 26.5299" stroke="#36B37E" stroke-linecap="round"/>
						</svg>
						<span><?php esc_html_e( 'Placed', 'track-orders-for-woocommerce' ); ?></span>
					</li>
					<li id="wps_approval_order">
						<?php
						if ( 1 == $shipping || 1 == $processing ) {
							?>
							<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#E7E7E7"/>
<path d="M27.2 32L30.4 35.2L37.6 28" stroke="#36B37E" stroke-linecap="round"/>
<path d="M44.7273 43.6364V17C44.7273 16.4477 44.2796 16 43.7273 16H25.8182M44.7273 48H21C20.4477 48 20 47.5523 20 47V21.8182M25.8182 16L22.9091 18.9091L20 21.8182M25.8182 16V20.8182C25.8182 21.3705 25.3705 21.8182 24.8182 21.8182H20" stroke="black" stroke-linecap="round"/>
</svg>
							<span><?php esc_html_e( 'Approval', 'track-orders-for-woocommerce' ); ?></span>
							<?php
						} else {
							$shipping_blk = 1;
							$processing_blk = 1;
							?>
							<svg class="wps_active_status_svg" width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#36B37E"/>
<path d="M27.2 32L30.4 35.2L37.6 28" stroke="#36B37E" stroke-linecap="round"/>
<path d="M44.7273 43.6364V17C44.7273 16.4477 44.2796 16 43.7273 16H25.8182M44.7273 48H21C20.4477 48 20 47.5523 20 47V21.8182M25.8182 16L22.9091 18.9091L20 21.8182M25.8182 16V20.8182C25.8182 21.3705 25.3705 21.8182 24.8182 21.8182H20" stroke="black" stroke-linecap="round"/>
</svg>
							<span><?php esc_html_e( 'Approval', 'track-orders-for-woocommerce' ); ?></span>
						<?php } ?>
					</li>
					<li id="wps_processing_order">
						<?php
						if ( 1 == $processing_blk ) {
							?>
							<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#E7E7E7"/>
<path d="M23.2 26L32.8 31.2M23.2 26V35.8256C23.2 36.1813 23.3889 36.5102 23.6961 36.6894L32.8 42M23.2 26L28 23.2M32.8 31.2V42M32.8 31.2L37.6 28.4M42.4 25.6L33.2944 20.6678C32.9874 20.5015 32.6159 20.5074 32.3143 20.6833L28 23.2M42.4 25.6V35.8256C42.4 36.1813 42.2111 36.5102 41.9039 36.6894L32.8 42M42.4 25.6L37.6 28.4M37.6 28.4V33.6M37.6 28.4L28 23.2" stroke="black" stroke-linecap="round"/>
<path d="M31.2 15.6L31.5254 15.9796L32.0971 15.4896L31.4236 15.1528L31.2 15.6ZM28.2236 13.5528C27.9766 13.4293 27.6763 13.5294 27.5528 13.7764C27.4293 14.0234 27.5294 14.3237 27.7764 14.4472L28.2236 13.5528ZM28.0746 17.6204C27.8649 17.8001 27.8407 18.1157 28.0204 18.3254C28.2001 18.5351 28.5157 18.5593 28.7254 18.3796L28.0746 17.6204ZM16.5 30.8C16.5 22.6814 23.0814 16.1 31.2 16.1V15.1C22.5291 15.1 15.5 22.1291 15.5 30.8H16.5ZM31.4236 15.1528L28.2236 13.5528L27.7764 14.4472L30.9764 16.0472L31.4236 15.1528ZM30.8746 15.2204L28.0746 17.6204L28.7254 18.3796L31.5254 15.9796L30.8746 15.2204Z" fill="#019FFF"/>
<path d="M32.8 47.6L32.4746 47.2204L31.9029 47.7105L32.5764 48.0472L32.8 47.6ZM35.7764 49.6472C36.0234 49.7707 36.3237 49.6706 36.4472 49.4236C36.5707 49.1766 36.4706 48.8763 36.2236 48.7528L35.7764 49.6472ZM35.9254 45.5796C36.1351 45.3999 36.1593 45.0843 35.9796 44.8746C35.7999 44.665 35.4843 44.6407 35.2746 44.8204L35.9254 45.5796ZM47.5 32.4C47.5 40.5186 40.9186 47.1 32.8 47.1V48.1C41.4709 48.1 48.5 41.0709 48.5 32.4H47.5ZM32.5764 48.0472L35.7764 49.6472L36.2236 48.7528L33.0236 47.1528L32.5764 48.0472ZM33.1254 47.9796L35.9254 45.5796L35.2746 44.8204L32.4746 47.2204L33.1254 47.9796Z" fill="#019FFF"/>
</svg>
							<span><?php esc_html_e( 'Processing', 'track-orders-for-woocommerce' ); ?></span>
																					  <?php
						} else if ( 0 == $shipping && 1 == $processing ) {
							$shipping_blk = 1;
							?>
							<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#36be7e"/>
<path d="M23.2 26L32.8 31.2M23.2 26V35.8256C23.2 36.1813 23.3889 36.5102 23.6961 36.6894L32.8 42M23.2 26L28 23.2M32.8 31.2V42M32.8 31.2L37.6 28.4M42.4 25.6L33.2944 20.6678C32.9874 20.5015 32.6159 20.5074 32.3143 20.6833L28 23.2M42.4 25.6V35.8256C42.4 36.1813 42.2111 36.5102 41.9039 36.6894L32.8 42M42.4 25.6L37.6 28.4M37.6 28.4V33.6M37.6 28.4L28 23.2" stroke="black" stroke-linecap="round"/>
<path d="M31.2 15.6L31.5254 15.9796L32.0971 15.4896L31.4236 15.1528L31.2 15.6ZM28.2236 13.5528C27.9766 13.4293 27.6763 13.5294 27.5528 13.7764C27.4293 14.0234 27.5294 14.3237 27.7764 14.4472L28.2236 13.5528ZM28.0746 17.6204C27.8649 17.8001 27.8407 18.1157 28.0204 18.3254C28.2001 18.5351 28.5157 18.5593 28.7254 18.3796L28.0746 17.6204ZM16.5 30.8C16.5 22.6814 23.0814 16.1 31.2 16.1V15.1C22.5291 15.1 15.5 22.1291 15.5 30.8H16.5ZM31.4236 15.1528L28.2236 13.5528L27.7764 14.4472L30.9764 16.0472L31.4236 15.1528ZM30.8746 15.2204L28.0746 17.6204L28.7254 18.3796L31.5254 15.9796L30.8746 15.2204Z" fill="#019FFF"/>
<path d="M32.8 47.6L32.4746 47.2204L31.9029 47.7105L32.5764 48.0472L32.8 47.6ZM35.7764 49.6472C36.0234 49.7707 36.3237 49.6706 36.4472 49.4236C36.5707 49.1766 36.4706 48.8763 36.2236 48.7528L35.7764 49.6472ZM35.9254 45.5796C36.1351 45.3999 36.1593 45.0843 35.9796 44.8746C35.7999 44.665 35.4843 44.6407 35.2746 44.8204L35.9254 45.5796ZM47.5 32.4C47.5 40.5186 40.9186 47.1 32.8 47.1V48.1C41.4709 48.1 48.5 41.0709 48.5 32.4H47.5ZM32.5764 48.0472L35.7764 49.6472L36.2236 48.7528L33.0236 47.1528L32.5764 48.0472ZM33.1254 47.9796L35.9254 45.5796L35.2746 44.8204L32.4746 47.2204L33.1254 47.9796Z" fill="#019FFF"/>
</svg>
							<span><?php esc_html_e( 'Processing', 'track-orders-for-woocommerce' ); ?></span>
																					  <?php
						} else {
							?>
							<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#E7E7E7"/>
<path d="M23.2 26L32.8 31.2M23.2 26V35.8256C23.2 36.1813 23.3889 36.5102 23.6961 36.6894L32.8 42M23.2 26L28 23.2M32.8 31.2V42M32.8 31.2L37.6 28.4M42.4 25.6L33.2944 20.6678C32.9874 20.5015 32.6159 20.5074 32.3143 20.6833L28 23.2M42.4 25.6V35.8256C42.4 36.1813 42.2111 36.5102 41.9039 36.6894L32.8 42M42.4 25.6L37.6 28.4M37.6 28.4V33.6M37.6 28.4L28 23.2" stroke="black" stroke-linecap="round"/>
<path d="M31.2 15.6L31.5254 15.9796L32.0971 15.4896L31.4236 15.1528L31.2 15.6ZM28.2236 13.5528C27.9766 13.4293 27.6763 13.5294 27.5528 13.7764C27.4293 14.0234 27.5294 14.3237 27.7764 14.4472L28.2236 13.5528ZM28.0746 17.6204C27.8649 17.8001 27.8407 18.1157 28.0204 18.3254C28.2001 18.5351 28.5157 18.5593 28.7254 18.3796L28.0746 17.6204ZM16.5 30.8C16.5 22.6814 23.0814 16.1 31.2 16.1V15.1C22.5291 15.1 15.5 22.1291 15.5 30.8H16.5ZM31.4236 15.1528L28.2236 13.5528L27.7764 14.4472L30.9764 16.0472L31.4236 15.1528ZM30.8746 15.2204L28.0746 17.6204L28.7254 18.3796L31.5254 15.9796L30.8746 15.2204Z" fill="#019FFF"/>
<path d="M32.8 47.6L32.4746 47.2204L31.9029 47.7105L32.5764 48.0472L32.8 47.6ZM35.7764 49.6472C36.0234 49.7707 36.3237 49.6706 36.4472 49.4236C36.5707 49.1766 36.4706 48.8763 36.2236 48.7528L35.7764 49.6472ZM35.9254 45.5796C36.1351 45.3999 36.1593 45.0843 35.9796 44.8746C35.7999 44.665 35.4843 44.6407 35.2746 44.8204L35.9254 45.5796ZM47.5 32.4C47.5 40.5186 40.9186 47.1 32.8 47.1V48.1C41.4709 48.1 48.5 41.0709 48.5 32.4H47.5ZM32.5764 48.0472L35.7764 49.6472L36.2236 48.7528L33.0236 47.1528L32.5764 48.0472ZM33.1254 47.9796L35.9254 45.5796L35.2746 44.8204L32.4746 47.2204L33.1254 47.9796Z" fill="#019FFF"/>
</svg>
							<span><?php esc_html_e( 'Processing', 'track-orders-for-woocommerce' ); ?></span>
																					  <?php
						}
						?>
					</li>
					<li id="wps_shipping_order">
						<?php
						if ( 1 == $shipping_blk ) {
							?>
							<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#E7E7E7"/>
<path d="M27.4286 18H22.7143C22.162 18 21.7143 18.4477 21.7143 19V28.4286C21.7143 28.9809 22.162 29.4286 22.7143 29.4286H32.1429C32.6951 29.4286 33.1429 28.9809 33.1429 28.4286V19C33.1429 18.4477 32.6951 18 32.1428 18H27.4286ZM27.4286 18V23.1429" stroke="#F45C20" stroke-linecap="round"/>
<path d="M38.8571 22.5714V24.8572M38.8571 22.5714V34M38.8571 22.5714H36M38.8571 24.8572H45.0373C45.4462 24.8572 45.8139 25.1061 45.9657 25.4858L48 30.5714M38.8571 24.8572V30.5714M48 30.5714V39.8572C48 40.4094 47.5523 40.8572 47 40.8572H44M48 30.5714H38.8571M38.8571 30.5714V34M38.8571 34H16M38.8571 34V38.5714M16 34V39.8572C16 40.4094 16.4477 40.8572 17 40.8572H20M16 34V23.5714C16 23.0192 16.4477 22.5714 17 22.5714H18.8571M26.8571 40.8572H37.1429" stroke="black"/>
<circle cx="23.4286" cy="41.4286" r="3.5" stroke="black"/>
<circle cx="40.5714" cy="41.4286" r="3.5" stroke="black"/>
</svg>
							<span><?php esc_html_e( 'Shipping', 'track-orders-for-woocommerce' ); ?></span>
							<?php
						} else if ( 1 == $shipping && 'wc-completed' != $order_status ) {
							?>
							<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#36be7e"/>
<path d="M27.4286 18H22.7143C22.162 18 21.7143 18.4477 21.7143 19V28.4286C21.7143 28.9809 22.162 29.4286 22.7143 29.4286H32.1429C32.6951 29.4286 33.1429 28.9809 33.1429 28.4286V19C33.1429 18.4477 32.6951 18 32.1428 18H27.4286ZM27.4286 18V23.1429" stroke="#F45C20" stroke-linecap="round"/>
<path d="M38.8571 22.5714V24.8572M38.8571 22.5714V34M38.8571 22.5714H36M38.8571 24.8572H45.0373C45.4462 24.8572 45.8139 25.1061 45.9657 25.4858L48 30.5714M38.8571 24.8572V30.5714M48 30.5714V39.8572C48 40.4094 47.5523 40.8572 47 40.8572H44M48 30.5714H38.8571M38.8571 30.5714V34M38.8571 34H16M38.8571 34V38.5714M16 34V39.8572C16 40.4094 16.4477 40.8572 17 40.8572H20M16 34V23.5714C16 23.0192 16.4477 22.5714 17 22.5714H18.8571M26.8571 40.8572H37.1429" stroke="black"/>
<circle cx="23.4286" cy="41.4286" r="3.5" stroke="black"/>
<circle cx="40.5714" cy="41.4286" r="3.5" stroke="black"/>
</svg>
							<span><?php esc_html_e( 'Shipping', 'track-orders-for-woocommerce' ); ?></span>
							<?php
						} else {
							?>
							<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#E7E7E7"/>
<path d="M27.4286 18H22.7143C22.162 18 21.7143 18.4477 21.7143 19V28.4286C21.7143 28.9809 22.162 29.4286 22.7143 29.4286H32.1429C32.6951 29.4286 33.1429 28.9809 33.1429 28.4286V19C33.1429 18.4477 32.6951 18 32.1428 18H27.4286ZM27.4286 18V23.1429" stroke="#F45C20" stroke-linecap="round"/>
<path d="M38.8571 22.5714V24.8572M38.8571 22.5714V34M38.8571 22.5714H36M38.8571 24.8572H45.0373C45.4462 24.8572 45.8139 25.1061 45.9657 25.4858L48 30.5714M38.8571 24.8572V30.5714M48 30.5714V39.8572C48 40.4094 47.5523 40.8572 47 40.8572H44M48 30.5714H38.8571M38.8571 30.5714V34M38.8571 34H16M38.8571 34V38.5714M16 34V39.8572C16 40.4094 16.4477 40.8572 17 40.8572H20M16 34V23.5714C16 23.0192 16.4477 22.5714 17 22.5714H18.8571M26.8571 40.8572H37.1429" stroke="black"/>
<circle cx="23.4286" cy="41.4286" r="3.5" stroke="black"/>
<circle cx="40.5714" cy="41.4286" r="3.5" stroke="black"/>
</svg>
							<span><?php esc_html_e( 'Shipping', 'track-orders-for-woocommerce' ); ?></span>
							<?php
						}
						?>
					</li>
					<?php
					if ( 'wc-completed' == $order_status ) {
						?>
						<li class="wps_completed_condition" id="wps_delivered_order">
							<svg class="wps_active_status_svg" width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" data-completed_data='<?php echo esc_attr( $completed ); ?>'>
<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#36B37E"/>
<path d="M25.4286 23H20.7143C20.162 23 19.7143 23.4477 19.7143 24V33.4286C19.7143 33.9809 20.162 34.4286 20.7143 34.4286H30.1429C30.6951 34.4286 31.1429 33.9809 31.1429 33.4286V24C31.1429 23.4477 30.6951 23 30.1428 23H25.4286ZM25.4286 23V28.1429" stroke="#F45C20" stroke-linecap="round"/>
<path d="M36.8571 27.5714V29.8572M36.8571 27.5714V39M36.8571 27.5714H34M36.8571 29.8572H43.0373C43.4462 29.8572 43.8139 30.1061 43.9657 30.4858L46 35.5714M36.8571 29.8572V35.5714M46 35.5714V44.8572C46 45.4094 45.5523 45.8572 45 45.8572H42M46 35.5714H36.8571M36.8571 35.5714V39M36.8571 39H14M36.8571 39V43.5714M14 39V44.8572C14 45.4094 14.4477 45.8572 15 45.8572H18M14 39V28.5714C14 28.0192 14.4477 27.5714 15 27.5714H16.8571M24.8571 45.8572H35.1429" stroke="black"/>
<circle cx="21.4286" cy="46.4286" r="3.5" stroke="black"/>
<circle cx="38.5714" cy="46.4286" r="3.5" stroke="black"/>
<path d="M44.2632 19.117L43.1383 22.5358C42.8541 23.3996 43.7769 24.1636 44.5725 23.7233L51.6826 19.7883C52.3734 19.406 52.3695 18.4116 51.6758 18.0347L44.4798 14.1246C43.6882 13.6945 42.7761 14.4484 43.0495 15.3068L44.2632 19.117ZM44.2632 19.117L48.1523 19.1019" stroke="#36B37E" stroke-linecap="round"/>
</svg>
							<span><?php esc_html_e( 'Delivered', 'track-orders-for-woocommerce' ); ?></span>
						</li> 
						<?php
					} else if ( 1 == $order_cancel ) {
						?>
						<li class="wps_cancelled_condition" id="wps_cancelled_order">
							<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg"data-cancelled_data='<?php echo esc_attr( $order_cancel ); ?>'>
<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#E7E7E7"/>
<path d="M21.6471 24.7843L24.1569 36.7059M21.6471 24.7843L20.1035 17.5809C19.9059 16.6588 19.091 16 18.1479 16H16M21.6471 24.7843H29.1765M24.1569 36.7059H44.6788C45.1517 36.7059 45.5599 36.3746 45.6574 35.9119L47.7461 25.9903C47.877 25.3688 47.4027 24.7843 46.7676 24.7843H41.098M24.1569 36.7059L24.9973 39.6475C25.2426 40.5061 26.0274 41.098 26.9204 41.098H45.4902" stroke="black" stroke-linecap="round"/>
<circle cx="27.9216" cy="45.4902" r="2.63725" stroke="black"/>
<circle cx="41.7255" cy="45.4902" r="2.63725" stroke="black"/>
<path d="M38.3081 22.5686L33.1716 28.1112L35.7398 25.3399M35.7398 25.3399L33.0979 22.6388M35.7398 25.3399L38.3818 28.0411" stroke="#B71D18" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
							<span><?php esc_html_e( 'Cancelled', 'track-orders-for-woocommerce' ); ?></span>
						</li> 
						<?php
					} else {
						?>
						<li class="wps_completed_condition" id="wps_delivered_order">
							<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" data-completed_data='<?php echo esc_attr( $completed ); ?>'>
<rect x="0.5" y="0.5" width="63" height="63" rx="31.5" stroke="#e7e7e7"/>
<path d="M25.4286 23H20.7143C20.162 23 19.7143 23.4477 19.7143 24V33.4286C19.7143 33.9809 20.162 34.4286 20.7143 34.4286H30.1429C30.6951 34.4286 31.1429 33.9809 31.1429 33.4286V24C31.1429 23.4477 30.6951 23 30.1428 23H25.4286ZM25.4286 23V28.1429" stroke="#F45C20" stroke-linecap="round"/>
<path d="M36.8571 27.5714V29.8572M36.8571 27.5714V39M36.8571 27.5714H34M36.8571 29.8572H43.0373C43.4462 29.8572 43.8139 30.1061 43.9657 30.4858L46 35.5714M36.8571 29.8572V35.5714M46 35.5714V44.8572C46 45.4094 45.5523 45.8572 45 45.8572H42M46 35.5714H36.8571M36.8571 35.5714V39M36.8571 39H14M36.8571 39V43.5714M14 39V44.8572C14 45.4094 14.4477 45.8572 15 45.8572H18M14 39V28.5714C14 28.0192 14.4477 27.5714 15 27.5714H16.8571M24.8571 45.8572H35.1429" stroke="black"/>
<circle cx="21.4286" cy="46.4286" r="3.5" stroke="black"/>
<circle cx="38.5714" cy="46.4286" r="3.5" stroke="black"/>
<path d="M44.2632 19.117L43.1383 22.5358C42.8541 23.3996 43.7769 24.1636 44.5725 23.7233L51.6826 19.7883C52.3734 19.406 52.3695 18.4116 51.6758 18.0347L44.4798 14.1246C43.6882 13.6945 42.7761 14.4484 43.0495 15.3068L44.2632 19.117ZM44.2632 19.117L48.1523 19.1019" stroke="#36B37E" stroke-linecap="round"/>
</svg>
							<span><?php esc_html_e( 'Delivery', 'track-orders-for-woocommerce' ); ?></span>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
		</section>
		<section class="wps_tofw_order_tracker_content approval">
			<div class="wps-deliver-msg wps-tofw-wps-msg">
				<h3><?php esc_html_e( 'Approval', 'track-orders-for-woocommerce' ); ?></h3>
				<?php
				if ( ! empty( $wps_track_order_status ) ) {
					?>
						<ul class="wps-tofw-order-info">
							<li><?php esc_html_e( 'placed', 'track-orders-for-woocommerce' ); ?></li>
							<li>
							<?php
							if ( WC()->version < '3.0.0' ) {
								echo esc_html( date_i18n( 'd F, Y H:i', strtotime( $tofw_order->post->post_date ) ) );
							} else {
								$wps_date = $tofw_order->get_date_created()->date( 'd F, Y H:i' );
								echo esc_html( $wps_date );}
							?>
							</li>
							<li><?php esc_html_e( 'Your Order is Successfully Placed', 'track-orders-for-woocommerce' ); ?></li></ul>
							<?php
				}
				?>
						<?php
						$class = '';
						$active = 0;
						$f = 0;
						$cancelled = 0;
						if ( ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) && is_array( $wps_track_order_status ) && empty( $wps_track_order_status ) && '' != $order_status && in_array( $order_status, $get_status_approval ) ) {

							$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $order_status ] );
							foreach ( $order_onchange_time as $changekey => $changevalue ) {
								if ( $order_status == $changekey ) {
									$onchange_approval_date = $changevalue;
								}
							}
							?>
							<ul class="wps-tofw-order-info">
								<li><?php echo esc_html( $order_status ); ?></li>
								<li><?php echo esc_html( $onchange_approval_date ); ?></li>
								<li><?php echo esc_html( $current_status ); ?></li></ul>
								<?php
						} else if ( is_array( $wps_track_order_status ) && ! empty( $wps_track_order_status ) ) {
							$f = 0;
							foreach ( $wps_track_order_status as $key => $value ) {
								if ( ! empty( $get_status_approval ) && is_array( $get_status_approval ) && in_array( $value, $get_status_approval ) ) {
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
												<li><?php echo esc_html( $current_status ); ?></li></ul>
										<?php
								}
							}
						}
						?>
								</div>
							</section>
							<?php

							$status_counter = 0;
							if ( ! empty( $get_status_processing ) && is_array( $get_status_processing ) && ! empty( $order_onchange_time ) && is_array( $order_onchange_time ) ) {
								foreach ( $get_status_processing as $preocessing_key => $processing_value ) {

									foreach ( $order_onchange_time as $order_key => $order_value ) {
										if ( $processing_value == $order_key ) {
											$status_counter = 1;
										}
									}
								}
							}
							if ( 1 == $status_counter ) {
								?>
								<section class="wps_tofw_order_tracker_content processing">

									<div class="wps-deliver-msg wps-tofw-wps-msg">
										<h3><?php esc_html_e( 'processing', 'track-orders-for-woocommerce' ); ?></h3>
										<?php
										if ( 1 != $cancelled ) {
											if ( is_array( $wps_track_order_status ) && empty( $wps_track_order_status ) && '' != $order_status ) {
												$current_status = get_option( $order_status_key, __( 'Your Order status is ', 'track-orders-for-woocommerce' ) . $woo_statuses[ $order_status ] );
												foreach ( $order_onchange_time as $changekey1 => $changevalue1 ) {
													if ( $order_status == $changevalue1 ) {
														$onchange_processing_date = $changevalue1;
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
													if ( in_array( $value, $get_status_processing ) ) {
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
													if ( isset( $wps_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $wps_track_order_status[ $key + 1 ] && in_array( $value, $get_status_processing ) && 'wc-cancelled' == $order_status ) {
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
													if ( $order_status == $key_change1 ) {
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
													<section class="wps_tofw_order_tracker_content shipping">
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
																} else if ( ! empty( $get_status_shipping ) && is_array( $wps_track_order_status ) && ! empty( $wps_track_order_status ) ) {
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
																		if ( ! empty( $get_status_shipping ) && is_array( $get_status_shipping ) && isset( $wps_track_order_status[ $key + 1 ] ) && 'wc-cancelled' == $wps_track_order_status[ $key + 1 ] && in_array( $value, $get_status_shipping ) && 'wc-cancelled' == $order_status ) {
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
																						<li><?php esc_html_e( 'Cancelled', 'track-orders-for-woocommerce' ); ?></li>
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
																	<section class="wps_tofw_order_tracker_content processing">

																		<div class="wps-deliver-msg wps-tofw-wps-msg">
																			<ul class="wps-tofw-order-info">
																				<li><?php esc_html_e( 'Your Order is Shipped', 'track-orders-for-woocommerce' ); ?></li>

																			</ul>
																		</div></section>
														<?php
												}
												?>
																	<section class="wps_tofw_order_tracker_content delivery">
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
																						$message = __( 'Your order is completed and delivered on ', 'track-orders-for-woocommerce' ) . date_i18n( 'F d,Y H:i', strtotime( $order_delivered_date ) );
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
																					$message_expected = __( 'Your order is expected to delivered by ', 'track-orders-for-woocommerce' ) . date_i18n( 'F d, Y ', strtotime( $expected_delivery_date ) ) . $expected_delivery_time;

																				} elseif ( 'wc-completed' == $order_status ) {
																					$message_expected = __( 'Your Order is Completed on ', 'track-orders-for-woocommerce' ) . date_i18n( 'd F, Y H:i', strtotime( $order_delivered_date ) );
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
																	<?php if ( ! empty( $wps_tofw_enhanced_customer_note ) ) { ?>
																		<div class="wps-tofw-order-tracking-section ">
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
															?>
