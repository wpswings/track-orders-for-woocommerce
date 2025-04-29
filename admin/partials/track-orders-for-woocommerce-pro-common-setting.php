<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html field for general tab.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin/partials
 */

$wps_tofw_main_wrapper_class_theme = '';
$wps_tofw_child_wrapper_class = '';
$wps_tofw_custom_css_name = '';
$wps_tofw_custom_js_name = '';

?>
<form action="" method="POST" class="wps-tofw-common-section-form">
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wps_tofwp_wrapper">

		<div class="wps-form-group wps-msp-text wps_tofw_pro_tag">
				 <div class="wps-form-group__label">
							<label for="wps" class="wps-form-label"><?php echo esc_html_e( 'Main Wrapper Class of Theme', 'track-orders-for-woocommerce' ); ?></label>
						</div>
							<div class="wps-form-group__control">
								<label class="mdc-text-field mdc-text-field--outlined">
									<span class="mdc-notched-outline">
										<span class="mdc-notched-outline__leading"></span>
										<span class="mdc-notched-outline__notch">
										</span>
										<span class="mdc-notched-outline__trailing"></span>
									</span>
								  <input class="mdc-text-field__input wps_tofw_pro_feature" name = "wps_tofw_main_wrapper_class_theme" id="" type="text" value = "<?php echo esc_attr( $wps_tofw_main_wrapper_class_theme ); ?>"  placeholder="For e.g. .wps_main_classs .wps_main_class_2">
								</label>

								<div class="mdc-text-field-helper-line">
									<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true">
									<?php echo esc_html_e( 'Write the main wrapper class of your theme if some design issue arises.', 'track-orders-for-woocommerce' ); ?>
									</div>
								</div>
					</div>
		 </div>




		 <div class="wps-form-group wps-msp-text wps_tofw_pro_tag">
				 <div class="wps-form-group__label">
							<label for="wps" class="wps-form-label"><?php echo esc_html_e( 'Child Wrapper Class of Theme', 'track-orders-for-woocommerce' ); ?></label>
						</div>
							<div class="wps-form-group__control">
								<label class="mdc-text-field mdc-text-field--outlined">
									<span class="mdc-notched-outline">
										<span class="mdc-notched-outline__leading"></span>
										<span class="mdc-notched-outline__notch">
										</span>
										<span class="mdc-notched-outline__trailing"></span>
									</span>
								  <input class="mdc-text-field__input wps_tofw_pro_feature" name = "wps_tofw_child_wrapper_class" id="" type="text" value = "<?php echo esc_attr( $wps_tofw_child_wrapper_class ); ?>" placeholder="For e.g, .wps_main_classs .wps_main_class_2" >
								</label>

								<div class="mdc-text-field-helper-line">
									<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true">
									<?php echo esc_html_e( 'Write the child wrapper class of your theme if some design issue arises.', 'track-orders-for-woocommerce' ); ?>
									</div>
								</div>
					</div>
		 </div>


		 <div class="wps-form-group wps_tofw_pro_tag">
							<div class="wps-form-group__label">
								<label class="wps-form-label" ><?php echo esc_html_e( 'Tracking Order Page Global CSS', 'track-orders-for-woocommerce' ); ?></label>
							</div>
							<div class="wps-form-group__control">
								<label class="mdc-text-field mdc-text-field--outlined mdc-text-field--textarea"  for="text-field-hero-input">
									<span class="mdc-notched-outline">
										<span class="mdc-notched-outline__leading"></span>
										<span class="mdc-notched-outline__trailing"></span>
									</span>
									<span class="mdc-text-field__resizer">
										<textarea class="mdc-text-field__input wps_custom_tofw_css wps_tofw_pro_feature" rows="2" cols="25" aria-label="Label" name="wps_tofw_custom_css_name" placeholder="For e.g, .wps_main_classs{color:red}"><?php echo esc_attr( $wps_tofw_custom_css_name ); ?></textarea>
									</span>
								</label>
								<div class="mdc-text-field-helper-line">
									<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true">
									<?php echo esc_html_e( 'Write the custom css for Tracking Order page.', 'track-orders-for-woocommerce' ); ?>
									</div>
								</div>
							</div>
						</div>




		 <div class="wps-form-group wps_tofw_pro_tag">
							<div class="wps-form-group__label">
								<label class="wps-form-label" ><?php echo esc_html_e( 'Tracking Order Page Global JS', 'track-orders-for-woocommerce' ); ?></label>
							</div>
							<div class="wps-form-group__control">
								<label class="mdc-text-field mdc-text-field--outlined mdc-text-field--textarea"  for="text-field-hero-input">
									<span class="mdc-notched-outline">
										<span class="mdc-notched-outline__leading"></span>
										<span class="mdc-notched-outline__trailing"></span>
									</span>
									<span class="mdc-text-field__resizer">
										<textarea class="mdc-text-field__input wps_custom_tofw_js wps_tofw_pro_feature" rows="2" cols="25" aria-label="Label" name="wps_tofw_custom_js_name" placeholder="For e.g, console.log('run')"><?php echo esc_attr( $wps_tofw_custom_js_name ); ?></textarea>
									</span>
								</label>
								<div class="mdc-text-field-helper-line">
									<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true">
									<?php echo esc_html_e( 'Write the custom js for Tracking Order page.', 'track-orders-for-woocommerce' ); ?>
									</div>
								</div>
							</div>
						</div>
</div>

<?php
wp_nonce_field( 'admin_save_data', 'wps_tabs_nonce' );
?>
<!-- Save Button -->
<div class="wps-form-group">
		<div class="wps-form-group__label"></div>
		<div class="wps-form-group__control">
			<span class="mdc-button mdc-button--raised wps_tofw_pro_feature" name= "wps_tofp_enhance_tracking_save" id="wps_tofp_enhance_tracking_save"> <span class="mdc-button__ripple"></span>
				<span class="mdc-button__label "><?php esc_html_e( 'Save Changes', 'track-orders-for-woocommerce' ); ?></span>
			</span>
		</div>
	</div>
</form>

<?php
include_once plugin_dir_path( dirname( __FILE__ ) ) . '/partials/track-order-for-woocommerce-go-pro.php';
