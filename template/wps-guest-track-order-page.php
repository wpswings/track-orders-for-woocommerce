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
	wp_safe_redirect( $myaccount_page_url );
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

include TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'template/wps-tofw-track-order-form.php';
?>


<?php
$check = get_option( 'wps_tofw_enable_guest_export' );
if ( 'on' == $check ) {
	?>

	<div>
		<form method="POST">
			<h3><?php esc_html_e( '!------ Export Your Order Using Order Id & Email ------!', 'track-orders-for-woocommerce' ); ?></h3>
			<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
				<label for="wps_wot_export_order_id"><?php esc_html_e( 'Enter Order Id', 'track-orders-for-woocommerce' ); ?><span class="required"> *</span></label>
				<input type="text" required id="wps_wot_export_order_id" name="wps_wot_export_order_id" class="woocommerce-Input wps_wot_export_order_id woocommerce-Input--text input-text">
			</p>
			<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
				<label for="wps_wot_export_email"><?php esc_html_e( 'Enter Email', 'track-orders-for-woocommerce' ); ?><span class="required"> *</span></label>
				<input type="email" required id="wps_wot_export_email" name="wps_wot_export_email" class="woocommerce-Input wps_wot_export_email woocommerce-Input--text input-text">
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
