<?php
/**
 * This is file for enhance tracking.
 *
 * @package Woocommerce_Order_Tracker.
 */

if ( isset( $_POST['wps_tofp_enhance_tracking_save'] ) && check_admin_referer( 'admin_save_data', 'wps_tabs_nonce' ) ) {

	$wps_tofwp_general_settings = array();
	$wps_tofwp_enable_plugin = isset( $_POST['wps_tofwp_tracking_enable'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofwp_tracking_enable'] ) ) : '';
	$wps_tofwp_shipment_tracking_providers_url = isset( $_POST['wps_tofwp_courier_url'] ) ? map_deep( wp_unslash( $_POST['wps_tofwp_courier_url'] ), 'sanitize_text_field' ) : array();
	$wps_tofwp_general_settings = array(
		'enable_plugin' => $wps_tofwp_enable_plugin,
		'providers_data' => $wps_tofwp_shipment_tracking_providers_url,
	);

	update_option( 'wps_tofwp_general_settings_saved', $wps_tofwp_general_settings );
	?>

	<?php
}
?>
<form action="" method="POST" class="wps-tofw-gen-section-form">
	<div class="tofw-secion-wrap">
<?php

// Creation of shipment tracking courier list array.
$wps_tofwp_courier_companies = array();
$wps_tofwp_courier_companies = array(
	'DHL'                   => 'http://www.dhl.com/en/express/tracking.shtml?AWB=',
	'UPS'                   => 'https://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=',
	'USPS'                  => 'https://tools.usps.com/go/TrackConfirmAction?tRef=fullpage&tLc=5&text28777=&tLabels=',
	'FedEx'                 => 'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=',
	'RoyalMail'             => 'https://www.royalmail.com/track-your-item#/',
	'AustraliaPost'         => 'https://auspost.com.au/mypost/track/#/search',
	'IMEX '                 => 'http://dm.mytracking.net/IMEX/track/TrackDetails.aspx?t=',
	'OnTrac'                => 'https://www.ontrac.com/tracking.asp?trackingres=submit&tracking_number=',
	'parcelForce'           => 'https://www.parcelforce.com/track',
	'Dpd'                   => 'https://www.dpd.co.uk/apps/tracking/?reference=',
	'CollectPlus'           => 'https://www.collectplus.co.uk/track/',
	'TforceLogistics'       => 'http://www.tforcelogistics.com/track-a-shipment/',
	'ApcPostalLogostics'    => 'https://us.mytracking.net/APC/track/TrackDetails.aspx?t=',
	'EStes'                 => 'http://www.estes-express.com/WebApp/ShipmentTracking/',
);

if ( empty( get_option( 'wps_tofwp_courier_companies', false ) ) ) {
	update_option( 'wps_tofwp_courier_companies', $wps_tofwp_courier_companies );

}
	update_option( 'wps_tofwp_courier_default_company', $wps_tofwp_courier_companies );
	$wps_tofwp_courier_companies = get_option( 'wps_tofwp_courier_companies', false );
   $wps_tofwp_general_settings_data = get_option( 'wps_tofwp_general_settings_saved', false );
   $wps_tofwp_courier_default_company = get_option( 'wps_tofwp_courier_default_company', false );
?>
<?php
   $wps_tofwp_general_settings_data = get_option( 'wps_tofwp_general_settings_saved', false );
   $wps_tofwp_courier_default_company = get_option( 'wps_tofwp_courier_default_company', false );


$saved_settings = get_option('wps_tofwp_general_settings_saved');
$saved_providers = isset($saved_settings['providers_data']) ? $saved_settings['providers_data'] : array();
?>
<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wps_tofwp_wrapper">

		<div class="wps_tofwp_section">
			<div class="wps_tofwp_enable_plugin">
				<div class="wps-form-group wps_tofw_pro_tag">
							<div class="wps-form-group__label">
								<label for="wps_tofwp_tracking_enable" class="wps-form-label"><?php esc_html_e( 'Enable Enhance Tracking', 'track-orders-for-woocommerce' ); ?></label>
							</div>
							<div class="wps-form-group__control">
								<div>
									<div class="mdc-switch">
										<div class="mdc-switch__track"></div>
										<div class="mdc-switch__thumb-underlay">
											<div class="mdc-switch__thumb"></div>
											<input name="wps_tofwp_tracking_enable" type="checkbox" id="wps_tofwp_tracking_enable" value="on" class="mdc-switch__native-control " role="switch" aria-checked="
																	
							"
											<?php isset( $wps_tofwp_general_settings_data['enable_plugin'] ) ? checked( 'on', $wps_tofwp_general_settings_data['enable_plugin'] ) : ''; ?>
											>
										</div>
									</div>
								</div>
								<div class="mdc-text-field-helper-line">
									<div class="mdc-text-field-helper-text--persistent wps-helper-text" id="" aria-hidden="true"></div>
								</div>
							</div>
						</div>
			</div>
		</div>
		<style>
	.wps-select-wrapper {
		margin-bottom: 25px;
	}

	.wps-select-wrapper label {
		display: block;
		font-weight: 600;
		margin-bottom: 8px;
		font-size: 1rem;
		color: #333;
	}

	select[name="wps_tofwp_courier_url_keys[]"] {
		width: 100%;
		height: 220px;
		padding: 10px;
		border: 1px solid #ccc;
		border-radius: 6px;
		font-size: 0.95rem;
		background-color: #fafafa;
		transition: border-color 0.3s ease;
	}

	select[name="wps_tofwp_courier_url_keys[]"]:focus {
		border-color: #0073aa;
		outline: none;
		background-color: #fff;
	}

	.wps-provider-grid {
		display: flex;
		flex-wrap: wrap;
		gap: 16px;
	}

	.wps-provider-card {
		flex: 1 1 160px;
		max-width: 180px;
		min-height: 170px;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		background: #fff;
		border: 1px solid #e0e0e0;
		border-radius: 12px;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
		padding: 15px;
		text-align: center;
		transition: transform 0.2s ease, box-shadow 0.2s ease;
	}

	.wps-provider-card:hover {
		transform: translateY(-4px);
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
	}

	.wps-provider-card img {
		width: 55px;
		height: 55px;
		object-fit: contain;
		margin-bottom: 12px;
	}

	.wps-provider-card span {
		font-weight: 600;
		font-size: 0.95rem;
		color: #222;
	}
</style>

		<!-- Panel For Adding The Shipment Tracking Couries-->
		<div class="wps_tofwp_tracking_section wps_tofw_pro_tag">
		<!-- Select Dropdown -->
		<div class="wps-select-wrapper">
			<label for="wps_tofwp_courier_url_keys">Choose Courier Providers</label>
			<select name="wps_tofwp_courier_url_keys[]" id="wps_tofwp_courier_url_keys" multiple>
				<?php foreach ( $wps_tofwp_courier_companies as $key => $url ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( array_key_exists( $key, $saved_providers ), true ); ?>>
						<?php echo esc_html( $key ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		</div>


<div class="wps_enhanced_tofwp_table_wrapper wps_tofw_pro_tag">
<table class="form-table">
  <tr>
	   <th><?php esc_html_e( 'Add Shipment Tracking Provide Name', 'track-orders-for-woocommerce' ); ?></th>
		<td>
			
			
			<div class="wps_enhanced_tofwp_provider">

				<div class="wps_enhanced_tofwp_add-wrap">
					<?php
					$tip_description = __( 'Enter Providers name you are going to use like PostNl Shipping, Express Shipping etc.', 'track-orders-for-woocommerce' );
					echo wp_kses_post( wc_help_tip( $tip_description ) );
					?>
					<label><?php esc_html_e( 'Provider Name', 'track-orders-for-woocommerce' ); ?></label>

					<input type="text" name="wps_enhanced_tofwp_add_prodvider" class="wps_toy_enhanced_provider" value="">
				</div>

				<div class="wps_enhanced_tofwp_add-wrap">
					<?php
					$tip_descriptions = __( 'Enter Providers Tracking Page Url from where your customer can track thier packages.', 'track-orders-for-woocommerce' );
					echo wp_kses_post( wc_help_tip( $tip_descriptions ) );
					?>
					<label><?php esc_html_e( 'Provider tracking Page Url', 'track-orders-for-woocommerce' ); ?></label>

					<div class="wps_enhanced_tofwp_add-inner-wrap">
						<input type="text" name="wps_enhanced_tofwp_add_prodvider" class='wps_toy_enhanced_provider_url' value="">
						<input type="button" id='wps_tofwp_enhanced_woocommerce_shipment_tracking_add_providers' value="<?php esc_attr_e( 'Add', 'track-orders-for-woocommerce' ); ?>" class="button">
					</div>
				</div>
			</div>
	   </td>
   </tr>
	   <tr>
			<td>
			 <div>
				
				</div>
			
			</td>
			</tr>
</table>
</div> 
</div> 
<?php
		wp_nonce_field( 'admin_save_data', 'wps_tabs_nonce' );
?>
		<div class="wps-form-group">
		<div class="wps-form-group__label"></div>
		<div class="wps-form-group__control">
			<span class="mdc-button mdc-button--raised wps_tofw_pro_feature" name= "wps_tofp_enhance_tracking_save" id="wps_tofp_enhance_tracking_save"> <span class="mdc-button__ripple"></span>
				<span class="mdc-button__label wps_tofw_pro_feature"><?php esc_html_e( 'Save Changes', 'track-orders-for-woocommerce' ); ?></span>
				</sapn>
		</div>
	</div>
</form>
<?php

include_once plugin_dir_path( dirname( __FILE__ ) ) . '/partials/track-order-for-woocommerce-go-pro.php';
?>
<script>
	jQuery(document).ready(function($) {
	$('select[name="wps_tofwp_courier_url_keys[]"]').select2({
		placeholder: 'Select courier providers'
	});
});
</script>