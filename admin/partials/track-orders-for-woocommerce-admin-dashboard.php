<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link  https://wpswings.com/
 * @since 1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit(); // Exit if accessed directly.
}
$secure_nonce      = wp_create_nonce( 'wps-upsell-auth-nonce' );
$id_nonce_verified = wp_verify_nonce( $secure_nonce, 'wps-upsell-auth-nonce' );
if ( ! $id_nonce_verified ) {
wp_die( esc_html__( 'Nonce Not verified', 'upsell-order-bump-offer-for-woocommerce' ) );
}
global $wps_tofw_obj, $error_notice;
$tofw_active_tab = isset( $_GET['tofw_tab'] ) ? sanitize_key( $_GET['tofw_tab'] ) : 'track-orders-for-woocommerce-general';
$tofw_default_tabs = $wps_tofw_obj->wps_std_plug_default_tabs();
?>
<header>
	<?php
	// desc - This hook is used for trial.
	do_action( 'wps_tofw_settings_saved_notice' );
	?>
	<div class="wps-header-container wps-bg-white wps-r-8">
		<h1 class="wps-header-title"><?php echo esc_attr( strtoupper( str_replace( '-', ' ', $wps_tofw_obj->tofw_get_plugin_name() ) ) ); ?></h1>
		<a href="https://docs.wpswings.com/" target="_blank" class="wps-link"><?php esc_html_e( 'Documentation', 'track-orders-for-woocommerce' ); ?></a>
		<span>|</span>
		<a href="https://wpswings.com/contact-us/" target="_blank" class="wps-link"><?php esc_html_e( 'Support', 'track-orders-for-woocommerce' ); ?></a>
	</div>
</header>
<?php
do_action( 'wps_tofw_licensed_tab_section' );
if ( ! $error_notice ) {
	$wps_tofw_error_text = esc_html__( 'Settings saved !', 'track-orders-for-woocommerce' );
}
?>
<main class="wps-main wps-bg-white wps-r-8">
	<nav class="wps-navbar">
		<ul class="wps-navbar__items">
			<?php
			if ( is_array( $tofw_default_tabs ) && ! empty( $tofw_default_tabs ) ) {
				foreach ( $tofw_default_tabs as $tofw_tab_key => $tofw_default_tabs ) {

					$tofw_tab_classes = 'wps-link ';
					if ( ! empty( $tofw_active_tab ) && $tofw_active_tab === $tofw_tab_key ) {
						$tofw_tab_classes .= 'active';
					}
					?>
					<li>
						<a id="<?php echo esc_attr( $tofw_tab_key ); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=track_orders_for_woocommerce_menu' ) . '&tofw_tab=' . esc_attr( $tofw_tab_key ) ); ?>" class="<?php echo esc_attr( $tofw_tab_classes ); ?>"><?php echo esc_html( $tofw_default_tabs['title'] ); ?></a>
					</li>
					<?php
				}
			}
			?>
		</ul>
	</nav>
	<section class="wps-section">
		<div>
			<?php
			// desc - This hook is used for trial.
			do_action( 'wps_msp_before_general_settings_form' );
			// if submenu is directly clicked on woocommerce.
			if ( empty( $tofw_active_tab ) ) {
				$tofw_active_tab = 'wps_std_plug_general';
			}
			// look for the path based on the tab id in the admin templates.
			$tofw_default_tabs     = $wps_tofw_obj->wps_std_plug_default_tabs();
			// look for the path based on the tab id in the admin templates.

			$tofw_tab_content_path = $tofw_default_tabs[ $tofw_active_tab ]['file_path'];
			$wps_tofw_obj->wps_tofw_plug_load_template( $tofw_tab_content_path, $tofw_active_tab );
			// desc - This hook is used for trial.
			do_action( 'wps_msp_after_general_settings_form' );
			?>
		</div>
	</section>
