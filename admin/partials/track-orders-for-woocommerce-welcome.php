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
global $wps_tofw_obj;
$tofw_default_tabs = $wps_tofw_obj->wps_std_plug_default_tabs();
$tofw_tab_key = '';
?>
<header>
	<?php
	// desc - This hook is used for trial.
	do_action( 'wps_tofw_settings_saved_notice' );
	?>
	<div class="wps-header-container wps-bg-white wps-r-8">
		<h1 class="wps-header-title"><?php echo esc_attr( __( 'WPSwings' ) ); ?></h1>
	</div>
</header>
<main class="wps-main wps-bg-white wps-r-8">
	<section class="wps-section">
		<div>
			<?php
				// desc - This hook is used for trial.
			do_action( 'wps_msp_before_common_settings_form' );
				// if submenu is directly clicked on woocommerce.
			$tofw_genaral_settings = apply_filters(
				'tofw_home_settings_array',
				array(
					array(
						'title' => __( 'Enable Tracking', 'track-orders-for-woocommerce' ),
						'type'  => 'radio-switch',
						'id'    => 'tofw_enable_tracking',
						'value' => get_option( 'tofw_enable_tracking' ),
						'class' => 'tofw-radio-switch-class',
						'options' => array(
							'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
							'no' => __( 'NO', 'track-orders-for-woocommerce' ),
						),
					),
					array(
						'type'  => 'button',
						'id'    => 'tofw_button_demo',
						'button_text' => __( 'Save', 'track-orders-for-woocommerce' ),
						'class' => 'tofw-button-class',
					),
				)
			);
			?>
			<form action="" method="POST" class="wps-tofw-gen-section-form">
				<div class="tofw-secion-wrap">
					<?php
					$tofw_general_html = $wps_tofw_obj->wps_std_plug_generate_html( $tofw_genaral_settings );
					echo esc_html( $tofw_general_html );
					wp_nonce_field( 'admin_save_data', 'wps_tabs_nonce' );
					?>
				</div>
			</form>
			<?php
			do_action( 'wps_msp_before_common_settings_form' );
			$all_plugins = get_plugins();
			?>
		</div>
	</section>
	<style type="text/css">
		.cards {
			   display: flex;
			   flex-wrap: wrap;
			   padding: 20px 40px;
		}
		.card {
			flex: 1 0 518px;
			box-sizing: border-box;
			margin: 1rem 3.25em;
			text-align: center;
		}

	</style>
	<div class="centered">
		<section class="cards">
			<?php foreach ( get_plugins() as $key => $value ) : ?>
				<?php if ( 'WPSwings' === $value['Author'] ) : ?>
					<article class="card">
						<div class="container">
							<h4><b><?php echo esc_html( $value['Name'] ); ?></b></h4> 
							<p><?php echo esc_html( $value['Version'] ); ?></p> 
							<p><?php echo esc_html( $value['Description'] ); ?></p>
						</div>
					</article>
				<?php endif; ?>
			<?php endforeach; ?>
		</section>
	</div>
