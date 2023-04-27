<div class="wps-tofw-wrap">
<h2><?php esc_html_e( 'Your License', 'track-orders-for-woocommerce' ); ?></h2>
<div class="wps_tofw_license_text">
	<p>
	<?php
	esc_html_e( 'This is the License Activation Panel. After purchasing extension from WPSwings you will get the purchase code of this extension. Please verify your purchase below so that you can use feature of this plugin.', 'track-orders-for-woocommerce' );
	?>
	</p>
	<form id="wps_tofw_license_form"> 
		<table class="wps-tofw-form-table">
			<tr>
			<th scope="row"><label for="puchase-code"><?php esc_html_e( 'Purchase Code : ', 'track-orders-for-woocommerce' ); ?></label></th>
			<td>
				<input type="text" id="wps_tofw_license_key" name="purchase-code" required="" size="30" class="wps-tofw-purchase-code" value="" placeholder="<?php esc_attr_e( 'Enter your code here...', 'track-orders-for-woocommerce' ); ?>">
			</td>
			</tr>
		</table>
		<p id="wps_tofw_license_activation_status"></p>
		<p class="submit">
		<button id="wps_tofw_license_activate" required="" class="button-primary woocommerce-save-button" name="wps_tofw_license_settings"><?php esc_html_e( 'Validate', 'track-orders-for-woocommerce' ); ?></button>
		</p>
	</form>
</div>
</div>
