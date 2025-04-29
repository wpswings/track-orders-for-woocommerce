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
$new_template8 = __( 'Activate', 'track-orders-for-woocommerce' );

$template1_class1 = '';
$template1_class2 = '';
$template1_class3 = '';
$template1_class4 = '';
$template1_class_new1 = '';
$template1_class_new2 = '';
$template1_class_new3 = '';
$template1_class_new8 = '';



if ( 'template1' == $activated_template ) {
	$template1 = __( 'Activated', 'track-orders-for-woocommerce' );
	$template1_class1 = 'wps_tyo_activated';
} else if ( 'template2' == $activated_template ) {
	$template2 = __( 'Activated', 'track-orders-for-woocommerce' );
	$template1_class2 = 'wps_tyo_activated';
} else if ( 'template3' == $activated_template ) {
	$template3 = __( 'Activated', 'track-orders-for-woocommerce' );
	$template1_class3 = 'wps_tyo_activated';
} else if ( 'template4' == $activated_template ) {
	$template4 = __( 'Activated', 'track-orders-for-woocommerce' );
	$template1_class4 = 'wps_tyo_activated';
} else if ( 'newtemplate1' == $activated_template ) {
	$new_template1 = __( 'Activated', 'track-orders-for-woocommerce' );
	$template1_class_new1 = 'wps_tyo_activated';
} else if ( 'newtemplate2' == $activated_template ) {
	$new_template2 = __( 'Activated', 'track-orders-for-woocommerce' );
	$template1_class_new2 = 'wps_tyo_activated';
} else if ( 'newtemplate3' == $activated_template ) {
	$new_template3 = __( 'Activated', 'track-orders-for-woocommerce' );
	$template1_class_new3 = 'wps_tyo_activated';
} else if ( 'template8' == $activated_template ) {
	$new_template8 = __( 'Activated', 'track-orders-for-woocommerce' );
	$template1_class_new8 = 'wps_tyo_activated';
}


?>

<div class="wps_notices_templates_order_tracker">
</div>
<div class="wps_tofw_template">
	<div id="wps_tofw_default_template">
		<div class="wps_tofw_template_img_wrap <?php echo esc_attr( $template1_class1 ); ?> ">
			<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot02.png'; ?>" class="wps_tofw_template_view">
		</div>
		<div class="wps_tofw_temlate_main_wrapper">
			<div class="wps_tofw_temlate_name">
				<h4><?php esc_html_e( 'Classic Tracker', 'track-orders-for-woocommerce' ); ?></h4>
			</div>
			<div class="wps_tofw_temlate_wrapper">
				<input type="button" class="activate_button" id="wps_tofw_activate_third" value=<?php echo esc_attr( $template1 ); ?> data-id='template1'>
				<input type="button" class="preview_button" id="wps_tofw_preview_third" value=<?php esc_attr_e( 'Preview', 'track-orders-for-woocommerce' ); ?> >
			</div>
		</div>
	</div>
	<div id="wps_tofw_first_template">
		<div class="wps_tofw_template_img_wrap <?php echo esc_attr( $template1_class2 ); ?>">
			
			<img  src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot03.png'; ?>" class="wps_tofw_template_view">
		</div>
		<div class="wps_tofw_temlate_main_wrapper">
			<div class="wps_tofw_temlate_name">
				<h4><?php esc_html_e( 'Compact Status', 'track-orders-for-woocommerce' ); ?></h4>
			</div>
			<div class="wps_tofw_temlate_wrapper">
				<input type="button" class="activate_button" id="wps_tofw_activate_first" value=<?php echo esc_attr( $template2 ); ?> data-id='template2'>
				<input type="button" class="preview_button" id="wps_tofw_preview_first" value=<?php esc_attr_e( 'Preview', 'track-orders-for-woocommerce' ); ?> >
			</div>
		</div>
	</div>
	<div id="wps_tofw_second_template">
		<div class="wps_tofw_template_img_wrap <?php echo esc_attr( $template1_class3 ); ?>">
			<img  src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot01.jpg'; ?>" class="wps_tofw_template_view">
		</div>
		<div class="wps_tofw_temlate_main_wrapper">
			<div class="wps_tofw_temlate_name">
				<h4><?php esc_html_e( 'Elegant Flow', 'track-orders-for-woocommerce' ); ?></h4>
			</div>
			<div class="wps_tofw_temlate_wrapper">
				<input type="button" class="activate_button" id="wps_tofw_activate_second" value=<?php echo esc_attr( $template3 ); ?> data-id='template3'>
				<input type="button" class="preview_button" id="wps_tofw_preview_second" value=<?php esc_attr_e( 'Preview', 'track-orders-for-woocommerce' ); ?> >
			</div>
		</div>
	</div>
	<?php
	do_action( 'wps_tofw_template_tab', $template4, $new_template1, $new_template2, $new_template3, $new_template8 );
	$is_pro_activated = false;
	$is_pro_activated = apply_filters( 'track_orders_for_woocmmerce_pro_plugin_activated', $is_pro_activated );

	if ( ! $is_pro_activated ) {

		?>

		<div id="wps_tofw_fourth_template">
				<div class="wps_tofw_template_img_wrap wps_tofw_pro_tag">
					<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot04.jpg'; ?>" class="wps_tofw_template_view">
				</div>
				<div class="wps_tofw_temlate_main_wrapper">
					<div class="wps_tofw_temlate_name">
						<h4><?php esc_html_e( 'Visual Voyage', 'track-orders-for-woocommerce' ); ?></h4>
					</div>
					<div class="wps_tofw_temlate_wrapper">
						<input type="button" class="activate_button_ wps_tofw_pro_feature" id="wps_tofw_activate_fourth" value=<?php echo esc_attr( $template4 ); ?> data-id='template4'>
						<input type="button" class="preview_button" id="wps_tofw_preview_fourth" value=<?php esc_attr_e( 'Preview', 'track-orders-for-woocommerce' ); ?>>
					</div>
				</div>
			</div>
			<div id="wps_tofw_new_template_1">
				<div class="wps_tofw_template_img_wrap wps_tofw_pro_tag">
					<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/NOt01.jpg'; ?>" class="wps_tofw_template_view">
				</div>
				<div class="wps_tofw_temlate_main_wrapper">
					<div class="wps_tofw_temlate_name">
						<h4><?php esc_html_e( 'Timeline Tracker', 'track-orders-for-woocommerce' ); ?></h4>
					</div>
					<div class="wps_tofw_temlate_wrapper">
						<input type="button" class="activate_button_ wps_tofw_pro_feature" id="wps_tofw_activate_new_template_1" value=<?php echo esc_html( $new_template1 ); ?> data-id='newtemplate1'>
						<input type="button" class="preview_button" id="wps_tofw_preview_new_template_1" value=<?php esc_attr_e( 'Preview', 'track-orders-for-woocommerce' ); ?>>
					</div>
				</div>
			</div>
			<div id="wps_tofw_new_template_2">
				<div class="wps_tofw_template_img_wrap wps_tofw_pro_tag">
					<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Ot02.jpg'; ?>" class="wps_tofw_template_view">
				</div>
				<div class="wps_tofw_temlate_main_wrapper">
					<div class="wps_tofw_temlate_name">
						<h4><?php esc_html_e( 'Visual Tracker', 'track-orders-for-woocommerce' ); ?></h4>
					</div>
					<div class="wps_tofw_temlate_wrapper">
						<input type="button" class="activate_button_ wps_tofw_pro_feature" id="wps_tofw_activate_new_template_2" value=<?php echo esc_attr( $new_template2 ); ?> data-id='newtemplate2'>
						<input type="button" class="preview_button" id="wps_tofw_preview_new_template_2" value=<?php esc_attr_e( 'Preview', 'track-orders-for-woocommerce' ); ?>>
					</div>
				</div>
			</div>
			<div id="wps_tofw_new_template_3">
				<div class="wps_tofw_template_img_wrap wps_tofw_pro_tag">
					<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Ot03.jpg'; ?>" class="wps_tofw_template_view">
				</div>
				<div class="wps_tofw_temlate_main_wrapper">
					<div class="wps_tofw_temlate_name">
						<h4><?php esc_html_e( 'Status Chain', 'track-orders-for-woocommerce' ); ?></h4>
					</div>
					<div class="wps_tofw_temlate_wrapper">
						<input type="button" class="activate_button_ wps_tofw_pro_feature" id="wps_tofw_activate_new_template_3" value= <?php echo esc_html( $new_template3 ); ?> data-id='newtemplate3'>
						<input type="button" class="preview_button" id="wps_tofw_preview_new_template_3" value=<?php esc_attr_e( 'Preview', 'track-orders-for-woocommerce' ); ?>>
					</div>
				</div>
			</div>
			<div id="wps_tofw_new_template_3">
				<div class="wps_tofw_template_img_wrap wps_tofw_pro_tag">
					<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Screenshot_13.png'; ?>" class="wps_tofw_template_view">
				</div>
				<div class="wps_tofw_temlate_main_wrapper">
					<div class="wps_tofw_temlate_name">
						<h4><?php esc_html_e( 'Flowline Tracker', 'track-orders-for-woocommerce' ); ?></h4>
					</div>
					<div class="wps_tofw_temlate_wrapper">
						<input type="button" class="activate_button_ wps_tofw_pro_feature" id="wps_tofw_activate_new_template_8" value= "<?php echo esc_html( $new_template8 ); ?>" data-id="template8">
						<input type="button" class="preview_button" id="wps_tofw_preview_new_template_8" value=<?php esc_attr_e( 'Preview', 'track-orders-for-woocommerce' ); ?>>
					</div>
				</div>
			</div>
		<?php

	}

	?>





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
	<?php
	do_action( 'wps_tofw_preview_template' );

	if ( ! $is_pro_activated ) {

		?>



	<div id="wps_template_4" class="wps_hide_template">
		<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/ot04.jpg'; ?>">
	</div>
	<div id="wps_new_template_1" class="wps_hide_template">
		<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/NOt01.jpg'; ?>">
	</div>
	<div id="wps_new_template_2" class="wps_hide_template">
		<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Ot02.jpg'; ?>">
	</div>
	<div id="wps_new_template_3" class="wps_hide_template">
		<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Ot03.jpg'; ?>">
	</div>
	<div id="wps_new_template_8" class="wps_hide_template">
		<img src="<?php echo esc_attr( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ) . 'admin/image/Screenshot_13.png'; ?>">
	</div>

		<?php
	}
	?>
	
</div>
<?php

include_once plugin_dir_path( dirname( __FILE__ ) ) . '/partials/track-order-for-woocommerce-go-pro.php';
