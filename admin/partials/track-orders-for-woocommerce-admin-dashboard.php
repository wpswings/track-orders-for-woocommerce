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

global $plugin_standard_obj;
$callname_lic         = Track_Orders_For_Woocommerce::$lic_callback_function;
$callname_lic_initial = Track_Orders_For_Woocommerce::$lic_ini_callback_function;
$day_count            = Track_Orders_For_Woocommerce::$callname_lic_initial();
if ( ! tofw_wps_standard_check_multistep() ) {
	?>
	<div id="react-app"></div>
	<?php
	return;
}
if ( $day_count > 0 ) {
	$tofw_active_tab = isset( $_GET['tofw_tab'] ) ? sanitize_key( $_GET['tofw_tab'] ) : 'track-orders-for-woocommerce-general';
	if ( ! get_option( 'wps_tofw_license_check', 0 ) ) {
		$day_count_warning = floor( $day_count );
		$day_string        = sprintf( _n( '%s day', '%s days', $day_count_warning, 'track-orders-for-woocommerce' ), number_format_i18n( $day_count_warning ) );
		$day_string        = '<span id="wps-tofw-day-count" >' . $day_string . '</span>';
		?>
		<div class="thirty-days-notice wps-header-container wps-bg-white wps-r-8">
			<h1 class="update-message notice">
				<p>
					<strong><a href="?page=track_orders_for_woocommerce_menu&tofw_tab=track-orders-for-woocommerce-license"><?php esc_html_e( 'Activate', 'track-orders-for-woocommerce' ); ?></a><?php printf( __( ' the license key before %s or you may risk losing data and the plugin will also become disfunctional.', 'track-orders-for-woocommerce' ), $day_string ); ?></strong>
				</p>
			</h1>
		</div>
		<?php
	}
} else {
	$tofw_active_tab = isset( $_GET['tofw_tab'] ) ? sanitize_key( $_GET['tofw_tab'] ) : 'track-orders-for-woocommerce-license';
	?>
<div class="thirty-days-notice wps-header-container wps-bg-white wps-r-8">
	<h1 class="wps-header-title">
		<p>
			<strong><?php esc_html_e( ' Your trial period is over please activate license to use the features.', 'track-orders-for-woocommerce' ); ?></strong>
		</p>
	</h1>
</div>
	<?php
}
$tofw_default_tabs = $plugin_standard_obj->wps_std_plug_default_tabs();
?>
<header>
	<?php
		// desc - This hook is used for trial.
		do_action( 'wps_tofw_settings_saved_notice' );
	?>
	<div class="wps-header-container wps-bg-white wps-r-8">
		<h1 class="wps-header-title"><?php echo esc_attr( strtoupper( str_replace( '-', ' ', $plugin_standard_obj->tofw_get_plugin_name() ) ) ); ?></h1>
		<a href="https://docs.wpswings.com/" target="_blank" class="wps-link"><?php esc_html_e( 'Documentation', 'track-orders-for-woocommerce' ); ?></a>
		<span>|</span>
		<a href="https://wpswings.com/contact-us/" target="_blank" class="wps-link"><?php esc_html_e( 'Support', 'track-orders-for-woocommerce' ); ?></a>
	</div>
</header>
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
				$tofw_default_tabs     = $plugin_standard_obj->wps_std_plug_default_tabs();
				$tofw_tab_content_path = $tofw_default_tabs[ $tofw_active_tab ]['file_path'];
				$plugin_standard_obj->wps_std_plug_load_template( $tofw_tab_content_path );
				// desc - This hook is used for trial.
				do_action( 'wps_msp_after_general_settings_form' );
			?>
		</div>
	</section>
