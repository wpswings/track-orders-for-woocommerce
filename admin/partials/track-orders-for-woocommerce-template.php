<?php
/**
 * Track order template.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$activated_template = get_option( 'wps_tofw_activated_template', '' );
$template1 = __( 'Activate', 'track-orders-for-woocommerce' );
$template2 = __( 'Activate', 'track-orders-for-woocommerce' );
$template3 = __( 'Activate', 'track-orders-for-woocommerce' );
$template4 = __( 'Activate', 'track-orders-for-woocommerce' );
$new_template1 = __( 'Activate', 'track-orders-for-woocommerce' );
$new_template2 = __( 'Activate', 'track-orders-for-woocommerce' );
$new_template3 = __( 'Activate', 'track-orders-for-woocommerce' );

if ( 'template1' == $activated_template ) {
	$template1 = __( 'Activated', 'track-orders-for-woocommerce' );
} else if ( 'template2' == $activated_template ) {
	$template2 = __( 'Activated', 'track-orders-for-woocommerce' );
} else if ( 'template3' == $activated_template ) {
	$template3 = __( 'Activated', 'track-orders-for-woocommerce' );
} else if ( 'template4' == $activated_template ) {
	$template4 = __( 'Activated', 'track-orders-for-woocommerce' );
} else if ( 'newtemplate1' == $activated_template ) {
	$new_template1 = __( 'Activated', 'track-orders-for-woocommerce' );
} else if ( 'newtemplate2' == $activated_template ) {
	$new_template2 = __( 'Activated', 'track-orders-for-woocommerce' );
} else if ( 'newtemplate3' == $activated_template ) {
	$new_template3 = __( 'Activated', 'track-orders-for-woocommerce' );
}
?>

<div class="wps_notices_templates_order_tracker">
</div>
<div class="wps_tofw_template">
	<div id="wps_tofw_default_template">
		<div class="wps_tofw_template_img_wrap">
			<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot02.png'; ?>" class="wps_tofw_template_view">
		</div>
		<div class="wps_tofw_temlate_main_wrapper">
			<div class="wps_tofw_temlate_name">
				<h4><?php esc_html_e( 'Template-1', 'track-orders-for-woocommerce' ); ?></h4>
			</div>
			<div class="wps_tofw_temlate_wrapper">
				<input type="button" class="activate_button" id="wps_tofw_activate_third" value=<?php echo esc_attr( $template1 ); ?> data-id='template1'>
				<input type="button" class="preview_button" id="wps_tofw_preview_third" value=<?php esc_attr_e( 'Preview', 'track-orders-for-woocommerce' ); ?> >
			</div>
		</div>
	</div>
	<div id="wps_tofw_first_template">
		<div class="wps_tofw_template_img_wrap">
			
			<img  src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot03.png'; ?>" class="wps_tofw_template_view">
		</div>
		<div class="wps_tofw_temlate_main_wrapper">
			<div class="wps_tofw_temlate_name">
				<h4><?php esc_html_e( 'Template-2', 'track-orders-for-woocommerce' ); ?></h4>
			</div>
			<div class="wps_tofw_temlate_wrapper">
				<input type="button" class="activate_button" id="wps_tofw_activate_first" value=<?php echo esc_attr( $template2 ); ?> data-id='template2'>
				<input type="button" class="preview_button" id="wps_tofw_preview_first" value=<?php esc_attr_e( 'Preview', 'track-orders-for-woocommerce' ); ?> >
			</div>
		</div>
	</div>
	<div id="wps_tofw_second_template">
		<div class="wps_tofw_template_img_wrap">
			<img  src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot01.jpg'; ?>" class="wps_tofw_template_view">
		</div>
		<div class="wps_tofw_temlate_main_wrapper">
			<div class="wps_tofw_temlate_name">
				<h4><?php esc_html_e( 'Template-3', 'track-orders-for-woocommerce' ); ?></h4>
			</div>
			<div class="wps_tofw_temlate_wrapper">
				<input type="button" class="activate_button" id="wps_tofw_activate_second" value=<?php echo esc_attr( $template3 ); ?> data-id='template3'>
				<input type="button" class="preview_button" id="wps_tofw_preview_second" value=<?php esc_attr_e( 'Preview', 'track-orders-for-woocommerce' ); ?> >
			</div>
		</div>
	</div>
	<?php do_action( 'wps_tofw_template_tab', $template4, $new_template1, $new_template2, $new_template3 ); ?>
</div>
	
<div class="hidden_wrapper">
	<div id="wps_template_1" class="wps_hide_template" >
		<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot02.png'; ?>">
	</div>
	<div id="wps_template_2" class="wps_hide_template">
		<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot03.png'; ?>">
	</div>
	<div id="wps_template_3" class="wps_hide_template">
		<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot01.jpg'; ?>">
	</div>
	<?php do_action( 'wps_tofw_preview_template' ); ?>
	
</div>
