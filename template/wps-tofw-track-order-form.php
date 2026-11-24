<?php
/**
 * Guest track order form (order id + billing email).
 *
 * Shared by the guest track order page template and the "Track Order" block.
 *
 * Available variables:
 *
 * @var bool $wps_tofw_show_title Whether to output the form heading. Optional, defaults to true.
 *
 * @since   1.3.0
 * @package Track_Orders_For_Woocommerce/template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $wps_tofw_show_title ) ) {
	$wps_tofw_show_title = true;
}

$wps_main_wrapper_class = get_option( 'wps_tofw_track_order_class' );
$wps_child_wrapper_class = get_option( 'wps_tofw_track_order_child_class' );
$wps_track_order_css = get_option( 'wps_tofw_tracking_order_custom_css' );
?>
<style>	<?php echo esc_html( $wps_track_order_css ); ?>	</style>
<div class="woocommerce woocommerce-account <?php echo esc_attr( $wps_main_wrapper_class ); ?>">
	<div class="<?php echo esc_attr( $wps_child_wrapper_class ); ?>">
		<div id="wps_tofw_guest_request_form_wrapper">
			<?php if ( $wps_tofw_show_title ) { ?>
			<h2>
			<?php
			$return_product_form = __( 'Track Your Order', 'track-orders-for-woocommerce' );

			/**
			 * Add more setting.
			 *
			 * @since 1.0.0
			 */
			$return_product_form = apply_filters( 'wps_tofw_return_product_form', $return_product_form );
			echo wp_kses_post( $return_product_form );
			?>
			</h2>
			<?php } ?>
			<?php
				$tofw_notification = isset( $_SESSION['wps_tofw_notification'] ) ? sanitize_text_field( wp_unslash( $_SESSION['wps_tofw_notification'] ) ) : '';
				if ( isset( $tofw_notification ) && ! empty( $tofw_notification ) ) {
					$tofw_notification = isset( $_SESSION['wps_tofw_notification'] ) ? sanitize_text_field( wp_unslash( $_SESSION['wps_tofw_notification'] ) ) : '';

					?>
					<ul class="woocommerce-error">
							<li><strong><?php esc_html_e( 'ERROR', 'track-orders-for-woocommerce' ); ?></strong>: <?php echo esc_html( $tofw_notification ); ?></li>
					</ul>
				<?php
				unset( $_SESSION['wps_tofw_notification'] );
			}
			?>
			<?php
			$wps_tofw_enable_track_17track_feature = get_option( 'wps_tofwp_general_settings_saved', false );
			if ( ! empty( $wps_tofw_enable_track_17track_feature['enable_plugin'] ) && 'on' == $wps_tofw_enable_track_17track_feature['enable_plugin'] ) {
				?>
			<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
			<label><?php esc_html_e( 'Enter Your 17TrackingNo', 'track-orders-for-woocommerce' ); ?></label>
				<input type="text"id="wps_tofw_enhanced_trackingid"name="wps_tracking_no" class="wps_tofw_enhanced_trackingid">
			</p>
			<p class="form-row">
				
				<input type="button" class="button wps_tofw_enhanced_17track" id="YQElem2" value="17Tracking">
			</p>
			<?php } ?>
			<form class="login" method="post">
				<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
					<label for="username"><?php esc_html_e( 'Enter Order Id', 'track-orders-for-woocommerce' ); ?><span class="required"> *</span></label>
					<input type="text" id="order_id" name="order_id" class="woocommerce-Input woocommerce-Input--text input-text">
				</p>
				<?php if ( 'on' != get_option( 'wps_tofw_enable_track_order_using_order_id', 'no' ) ) { ?>


					<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
						<label for="username"><?php esc_html_e( 'Enter Order Email', 'track-orders-for-woocommerce' ); ?><span class="required"> *</span></label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="order_email" id="order_email" value="">
					</p> 
					<?php
				}
				?>
				<input type="hidden" name="track_order_nonce_name" value="<?php echo esc_attr( wp_create_nonce( 'track_order_nonce' ) ); ?>">
				<p class="form-row">
					<input type="submit" value="<?php esc_attr_e( 'TRACK ORDER', 'track-orders-for-woocommerce' ); ?>" name="wps_tofw_order_id_submit" class="woocommerce-Button button">
				</p>
			</form>
		</div>
	</div>
</div>
