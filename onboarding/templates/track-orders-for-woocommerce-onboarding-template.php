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

global $wps_tofw_obj;
$msp_onboarding_form_fields =
// desc - filter for trial.
apply_filters( 'wps_msp_on_boarding_form_fields', array() );
?>

<?php if ( ! empty( $msp_onboarding_form_fields ) ) : ?>
	<div class="mdc-dialog mdc-dialog--scrollable <?php
    //desc - filter for trial.
    echo esc_attr( apply_filters( 'wps_stand_dialog_classes', 'track-orders-for-woocommerce' ) );
?>"> 

		<div class="wps-msp-on-boarding-wrapper-background mdc-dialog__container">
			<div class="wps-msp-on-boarding-wrapper mdc-dialog__surface" role="alertdialog" aria-modal="true" aria-labelledby="my-dialog-title" aria-describedby="my-dialog-content">
				<div class="mdc-dialog__content">
					<div class="wps-msp-on-boarding-close-btn">
						<a href="#"><span class="msp-close-form material-icons wps-msp-close-icon mdc-dialog__button" data-mdc-dialog-action="close">clear</span></a>
					</div>
					<h3 class="wps-msp-on-boarding-heading mdc-dialog__title"><?php esc_html_e( 'Welcome to WPSwings', 'track-orders-for-woocommerce' ); ?> </h3>
					<p class="wps-msp-on-boarding-desc"><?php esc_html_e( 'We love making new friends! Subscribe below and we promise to keep you up-to-date with our latest new plugins, updates, awesome deals and a few special offers.', 'track-orders-for-woocommerce' ); ?></p>

					<form action="#" method="post" class="wps-msp-on-boarding-form">
						<?php
						$tofw_onboarding_html = $wps_tofw_obj->wps_std_plug_generate_html( $msp_onboarding_form_fields );
						echo esc_html( $tofw_onboarding_html );
						?>
						<div class="wps-msp-on-boarding-form-btn__wrapper mdc-dialog__actions">
							<div class="wps-msp-on-boarding-form-submit wps-msp-on-boarding-form-verify ">
								<input type="submit" class="wps-msp-on-boarding-submit wps-on-boarding-verify mdc-button mdc-button--raised" value="Send Us">
							</div>
							<div class="wps-msp-on-boarding-form-no_thanks">
								<a href="#" class="wps-msp-on-boarding-no_thanks mdc-button" data-mdc-dialog-action="discard"><?php esc_html_e( 'Skip For Now', 'track-orders-for-woocommerce' ); ?></a>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="mdc-dialog__scrim"></div>
	</div>
<?php endif; ?>
