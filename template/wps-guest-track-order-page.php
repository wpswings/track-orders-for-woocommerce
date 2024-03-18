<?php
/**
 * Guest track order page.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/template
 */

$current_user_id = get_current_user_id();
if ( $current_user_id > 0 ) {
	$myaccount_page = get_option( 'woocommerce_myaccount_page_id' );
	$myaccount_page_url = get_permalink( $myaccount_page );
	wp_redirect( $myaccount_page_url );
	exit;
}

get_header( 'shop' );

/**
 * Woocommerce_before_main_content hook.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @since 1.0.0
*/
do_action( 'woocommerce_before_main_content' );

/**
 * Woocommerce_after_main_content hook.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */

$wps_main_wrapper_class = get_option( 'wps_tofw_track_order_class' );
$wps_child_wrapper_class = get_option( 'wps_tofw_track_order_child_class' );
$wps_track_order_css = get_option( 'wps_tofw_tracking_order_custom_css' );
?>
<style>	<?php echo esc_html( $wps_track_order_css ); ?>	</style>
<div class="woocommerce woocommerce-account <?php echo esc_attr( $wps_main_wrapper_class ); ?>">
	<div class="<?php echo esc_attr( $wps_child_wrapper_class ); ?>">
		<div id="wps_tofw_guest_request_form_wrapper">
			<h2>
			<?php
			$return_product_form = __( 'Track Your Order', 'track-orders-for-woocommerce' );

			/**
			 * Add more setting.
			 *
			 * @since 1.0.0
			 */
			$return_product_form = apply_filters( 'wps_tofw_return_product_form', $return_product_form );
			wp_kses_post( $return_product_form );
			?>
			</h2>
			<?php
			if ( isset( $_SESSION['wps_tofw_notification'] ) && ! empty( $_SESSION['wps_tofw_notification'] ) ) {
				?>
				<ul class="woocommerce-error">
						<li><strong><?php esc_html_e( 'ERROR', 'track-orders-for-woocommerce' ); ?></strong>: <?php echo esc_html( $_SESSION['wps_tofw_notification'] ); ?></li>
				</ul>
				<?php
				unset( $_SESSION['wps_tofw_notification'] );
			}
			?>
			<?php
			// $wps_tofw_enable_track_17track_feature = get_option( 'wps_tofwp_enable_17track_integration', 'no' );
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
				<input type="hidden" name="track_order_nonce_name" value="<?php wp_create_nonce( 'track_order_nonce' ); ?>">
				<p class="form-row">
					<input type="submit" value="<?php esc_attr_e( 'TRACK ORDER', 'track-orders-for-woocommerce' ); ?>" name="wps_tofw_order_id_submit" class="woocommerce-Button button">
				</p>
			</form>
		</div>
	</div>
</div>

<?php
$check = get_option( 'wps_tofw_enable_guest_export' );
if ( 'on' == $check ) {
	?>

	<div>
		<form method="POST">
			<h3><?php esc_html_e( '!------ Export Your All Orders Using Email ------!', 'track-orders-for-woocommerce' ); ?></h3>
			<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
				<label for="wps_wot_export_email"><?php esc_html_e( 'Enter Email', 'track-orders-for-woocommerce' ); ?><span class="required"> *</span></label>
				<input type="email" required  class="woocommerce-Input wps_wot_export_email woocommerce-Input--text input-text">
				<input type="hidden" name="track_order_nonce_name" value="<?php wp_create_nonce( 'track_order_nonce' ); ?>">
				<input type="submit"  value="<?php esc_attr_e( 'Export Orders', 'track-orders-for-woocommerce' ); ?>"  class="woocommerce-Button wps_tofw_guest_user_export_button button">
			</p>
		</form>
	</div>

	<?php
}


/**
 *  Add more conent.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_after_main_content' );

/**
 * Woocommerce_sidebar hook.
 *
 * @hooked woocommerce_get_sidebar - 10
*/

get_footer( 'shop' );
