<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link  https://wpswings.com/
 * @since 1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>


<div class="wps_tofw_lite_go_pro_popup_wrap " >
		<!-- Go pro popup main start. -->
		<div class="wps_tofw_popup_shadow"></div>
		<div class="wps_tofw_lite_go_pro_popup">
			<!-- Main heading. -->
			<div class="wps_tofw_lite_go_pro_popup_head">
				<h2><?php esc_html_e( 'Unlock Enhanced Order Tracking with Pro Version!', 'track-orders-for-woocommerce' ); ?></h2>
				<!-- Close button. -->
				<a href="javascript:void(0)" class="wps_tofw_lite_go_pro_popup_close">
					<span>×</span>
				</a>
			</div>  

			<!-- Notice icon. -->
			<div class="wps_tofw_lite_go_pro_popup_head"><img height="200" class="wps_go_pro_images" src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/go-pro.png' ); ?>">
			</div>
			
				
			<!-- Notice. -->
			<div class="wps_tofw_lite_go_pro_popup_content">
				<p class="wps_tofw_lite_go_pro_popup_text">
				<?php
				esc_html_e(
					'Upgrade to the Pro version of our WooCommerce order tracking plugin and unlock a suite of powerful features! ',
					'track-orders-for-woocommerce'
				)
				?>
							</p>
							<p class="wps_tofw_lite_go_pro_popup_text">
				<?php
				esc_html_e(
					'Enjoy the ability to create custom order statuses to perfectly match your fulfillment process, integrate directly with leading carriers like FedEx, USPS, and Canada Post for real-time shipment updates, and keep your customers informed every step of the way with automated email notifications.',
					'track-orders-for-woocommerce'
				)
				?>
							</p>
					
					<p class="wps_tofw_lite_go_pro_popup_text">
					
					<?php esc_html_e( 'Give your customers the peace of mind they deserve and drastically reduce "Where`s my order?" inquiries.', 'track-orders-for-woocommerce' ); ?>			

				</div>

			<!-- Go pro button. -->
			<div class="wps_tofw_lite_go_pro_popup_button">
				<a class="button wps_ubo_lite_overview_go_pro_button" target="_blank" href="https://wpswings.com/product/track-orders-for-woocommerce-pro/?utm_source=ot-org-backend&utm_medium=referral&utm_campaign=ot-pro">	<?php esc_html_e( 'Upgrade To Premium today!', 'track-orders-for-woocommerce' ); ?> </p>
			<span class="dashicons dashicons-arrow-right-alt"></span></a>
			</div>
		</div>
		<!-- Go pro popup main end. -->
	</div>