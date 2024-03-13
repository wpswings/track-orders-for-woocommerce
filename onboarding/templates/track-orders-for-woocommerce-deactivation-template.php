<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wpswings.com
 * @since      1.0.0
 *
 * @package    WPSwings_Onboarding
 * @subpackage WPSwings_Onboarding/admin/onboarding
 */

global $pagenow, $wps_tofw_obj;
if ( empty( $pagenow ) || 'plugins.php' != $pagenow ) {
	return false;
}
$wps_plugin_name                = ! empty( explode( '/', plugin_basename( __FILE__ ) ) ) ? explode( '/', plugin_basename( __FILE__ ) )[0] : '';
$wps_plugin_deactivation_id     = $wps_plugin_name . '-no_thanks_deactive';
$wps_plugin_onboarding_popup_id = $wps_plugin_name . '-onboarding_popup';
$msp_onboarding_form_deactivate =
// desc - filter for trial.
apply_filters( 'wps_msp_deactivation_form_fields', array() );

?>
<?php if ( ! empty( $msp_onboarding_form_deactivate ) ) : ?>
		<div id="<?php echo esc_attr( $wps_plugin_onboarding_popup_id ); ?>" class="mdc-dialog mdc-dialog--scrollable <?php
				//desc - filter for trial.
				echo esc_attr( apply_filters('wps_stand_dialog_classes', 'track-orders-for-woocommerce' ) );
		?>">

		<div class="wps-msp-on-boarding-wrapper-background mdc-dialog__container">
			<div class="wps-msp-on-boarding-wrapper mdc-dialog__surface" role="alertdialog" aria-modal="true" aria-labelledby="my-dialog-title" aria-describedby="my-dialog-content">
				<div class="mdc-dialog__content">
					<div class="wps-msp-on-boarding-close-btn">
						<a href="#">
							<span class="msp-close-form material-icons wps-msp-close-icon mdc-dialog__button" data-mdc-dialog-action="close">clear</span>
						</a>
					</div>

					<h3 class="wps-msp-on-boarding-heading mdc-dialog__title"><?php esc_html_e( 'Track Orders For Woocommerce', 'track-orders-for-woocommerce' ); ?></h3>
					<p class="wps-msp-on-boarding-desc"><?php esc_html_e( 'May we have a little info about why you are deactivating?', 'track-orders-for-woocommerce' ); ?></p>
					<form action="#" method="post" class="wps-msp-on-boarding-form">
						<?php
						$tofw_onboarding_deactive_html = $wps_tofw_obj->wps_std_plug_generate_html( $msp_onboarding_form_deactivate );
						echo esc_html( $tofw_onboarding_deactive_html );
						?>
						<div class="wps-msp-on-boarding-form-btn__wrapper mdc-dialog__actions">
							<div class="wps-msp-on-boarding-form-submit wps-msp-on-boarding-form-verify ">
								<input type="submit" class="wps-msp-on-boarding-submit wps-on-boarding-verify mdc-button mdc-button--raised" value="Send Us">
							</div>
							<div class="wps-msp-on-boarding-form-no_thanks">
								<a href="#" id="<?php echo esc_attr( $wps_plugin_deactivation_id ); ?>" class="<?php
									//desc - filter for trial.
									echo esc_attr( apply_filters('wps_stand_no_thank_classes', 'track-orders-for-woocommerce-no_thanks' ) );
								?> mdc-button"><?php esc_html_e( 'Skip and Deactivate Now', 'track-orders-for-woocommerce' ); ?></a>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="mdc-dialog__scrim"></div>
	</div>
<?php endif; ?>
