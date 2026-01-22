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
$wps_tofw_child_wrapper_class      = '';
$wps_tofw_custom_css_name          = '';
$wps_tofw_custom_js_name           = '';

// Check dependency plugin (Upsell Order Bump for WooCommerce) availability.
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$wps_tofw_is_upsell_active = (
	is_plugin_active( 'upsell-order-bump-offer-for-woocommerce/upsell-order-bump-offer-for-woocommerce.php' ) ||
	is_plugin_active( 'upsell-order-bump-offer-for-woocommerce-pro/upsell-order-bump-offer-for-woocommerce-pro.php' )
);

$wps_tofw_upsell_plugin_url = 'https://wordpress.org/plugins/upsell-order-bump-offer-for-woocommerce/';

$wps_tofw_enable_upsell_tracking_page = get_option( 'wps_tofw_enable_upsell_tracking_page', '' );

if ( isset( $_POST['wps_tofp_enhance_tracking_save'] ) && check_admin_referer( 'admin_save_data', 'wps_tabs_nonce' ) ) {
	if ( $wps_tofw_is_upsell_active ) {
		$wps_tofw_enable_upsell_tracking_page = isset( $_POST['wps_tofw_enable_upsell_tracking_page'] ) ? 'on' : '';
		update_option( 'wps_tofw_enable_upsell_tracking_page', $wps_tofw_enable_upsell_tracking_page );
	}
}
?>
<form action="" method="POST" class="wps-tofw-common-section-form">
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wps_tofwp_wrapper">

<div class="wps-form-group wps-msp-text">
				 <div class="wps-form-group__label">
							<label for="wps" class="wps-form-label"><?php echo esc_html_e( 'Upsell Tracking Page', 'track-orders-for-woocommerce' ); ?></label>
						</div>
							<div class="wps-form-group__control">
					<div class="mdc-switch">
						<div class="mdc-switch__track"></div>
						<div class="mdc-switch__thumb-underlay">
							<div class="mdc-switch__thumb"></div>
							<input
								name="wps_tofw_enable_upsell_tracking_page"
								type="checkbox"
								id="wps_tofw_enable_upsell_tracking_page"
								value="on"
								class="mdc-switch__native-control"
								role="switch"
								<?php checked( 'on', $wps_tofw_enable_upsell_tracking_page ); ?>
								<?php disabled( ! $wps_tofw_is_upsell_active ); ?>
							>
						</div>
					</div>

								<div class="mdc-text-field-helper-line">
									<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true">
									<?php
									if ( $wps_tofw_is_upsell_active ) {
										esc_html_e( 'Enable this to display the Upsell Tracking Page cards when the Upsell Funnel Builder for WooCommerce plugin is active.', 'track-orders-for-woocommerce' );
									} else {
										echo wp_kses_post(
											sprintf(
												/* translators: %s: plugin url */
												__( 'Activate the "Upsell Funnel Builder for WooCommerce" plugin to use this setting. Download: <a href="%s" target="_blank">Upsell Funnel Builder</a>.', 'track-orders-for-woocommerce' ),
												esc_url( $wps_tofw_upsell_plugin_url )
											)
										);
									}
									?>
									</div>
								</div>
					</div>
		 </div>


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
<div class="wps-form-group wps_tofw_main_class">
		<div class="wps-form-group__label"></div>
		<div class="wps-form-group__control">
			<button class="mdc-button mdc-button--raised" name= "wps_tofp_enhance_tracking_save" id="wps_tofp_enhance_tracking_save"> <span class="mdc-button__ripple"></span>
				<span class="mdc-button__label "><?php esc_html_e( 'Save Changes', 'track-orders-for-woocommerce-pro' ); ?></span>
			</button>
		</div>
	</div>
</form>

<?php
include_once plugin_dir_path( dirname( __FILE__ ) ) . '/partials/track-order-for-woocommerce-go-pro.php';
