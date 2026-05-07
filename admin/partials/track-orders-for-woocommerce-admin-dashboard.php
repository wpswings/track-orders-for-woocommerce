<?php
/**
 * Provide an admin area view for the plugin dashboard shell.
 *
 * @link  https://wpswings.com/
 * @since 1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

$secure_nonce      = wp_create_nonce( 'wps-upsell-auth-nonce' );
$id_nonce_verified = wp_verify_nonce( $secure_nonce, 'wps-upsell-auth-nonce' );

if ( ! $id_nonce_verified ) {
	wp_die( esc_html__( 'Nonce Not verified', 'track-orders-for-woocommerce' ) );
}

global $wps_tofw_obj;

$tofw_active_tab   = isset( $_GET['tofw_tab'] ) ? sanitize_key( $_GET['tofw_tab'] ) : 'track-orders-for-woocommerce-general';
$tofw_default_tabs = $wps_tofw_obj->wps_std_plug_default_tabs();
$tofw_primary_tabs = array();
$tofw_overflow_tabs = array();
$tofw_use_overflow = false;

if ( empty( $tofw_active_tab ) || ! isset( $tofw_default_tabs[ $tofw_active_tab ] ) ) {
	$tofw_active_tab = 'track-orders-for-woocommerce-general';
}

foreach ( $tofw_default_tabs as $tofw_tab_key => $tofw_tab_config ) {
	if ( $tofw_use_overflow ) {
		$tofw_overflow_tabs[ $tofw_tab_key ] = $tofw_tab_config;
		continue;
	}

	$tofw_primary_tabs[ $tofw_tab_key ] = $tofw_tab_config;

	if ( 'track-orders-for-woocommerce-order-api' === $tofw_tab_key ) {
		$tofw_use_overflow = true;
	}
}

$tofw_badge_text   = $wps_tofw_obj->tofw_get_admin_badge_text();
$tofw_version_text = $wps_tofw_obj->tofw_get_admin_version_label();
$tofw_is_pro       = $wps_tofw_obj->tofw_is_pro_active();
$tofw_more_active  = isset( $tofw_overflow_tabs[ $tofw_active_tab ] );
?>
<div class="tofw-admin-shell <?php echo $tofw_is_pro ? 'is-pro-active' : 'is-free-active'; ?>">
	
	<div class="tofw-admin-shell__frame">
		<?php
		// desc - This hook is used for trial.
		do_action( 'wps_tofw_settings_saved_notice' );
		?>
		<header class="tofw-admin-shell__masthead">
			<div class="tofw-admin-shell__brand">
				<span class="tofw-admin-shell__badge"><?php echo esc_html( $tofw_badge_text ); ?></span>
				<div class="tofw-admin-shell__brand-copy">
					<h1 class="tofw-admin-shell__title"><?php esc_html_e( 'Track Orders for WooCommerce', 'track-orders-for-woocommerce' ); ?></h1>
					<span class="tofw-admin-shell__version"><?php echo esc_html( $tofw_version_text ); ?></span>
				</div>
			</div>
		</header>

		<nav class="tofw-admin-shell__tabs" aria-label="<?php esc_attr_e( 'Plugin settings tabs', 'track-orders-for-woocommerce' ); ?>">
			<span class="tofw-admin-shell__tabs-version"><?php echo esc_html( $tofw_version_text ); ?></span>
			<ul class="tofw-admin-shell__tab-list">
				<?php foreach ( $tofw_primary_tabs as $tofw_tab_key => $tofw_tab_config ) : ?>
					<?php $tofw_tab_classes = 'tofw-admin-shell__tab-link' . ( $tofw_active_tab === $tofw_tab_key ? ' is-active' : '' ); ?>
					<li class="tofw-admin-shell__tab-item">
						<a id="<?php echo esc_attr( $tofw_tab_key ); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=track_orders_for_woocommerce_menu&tofw_tab=' . $tofw_tab_key ) ); ?>" class="<?php echo esc_attr( $tofw_tab_classes ); ?>">
							<?php echo esc_html( $tofw_tab_config['title'] ); ?>
						</a>
					</li>
				<?php endforeach; ?>
				<?php if ( ! empty( $tofw_overflow_tabs ) ) : ?>
					<li class="tofw-admin-shell__tab-item tofw-admin-shell__tab-item--more">
						<details class="tofw-admin-shell__more<?php echo $tofw_more_active ? ' is-active' : ''; ?>">
							<summary class="tofw-admin-shell__more-trigger">
								<span><?php esc_html_e( 'More', 'track-orders-for-woocommerce' ); ?></span>
								<span class="tofw-admin-shell__more-icon" aria-hidden="true"></span>
							</summary>
							<ul class="tofw-admin-shell__more-menu">
								<?php foreach ( $tofw_overflow_tabs as $tofw_tab_key => $tofw_tab_config ) : ?>
									<?php $tofw_tab_classes = 'tofw-admin-shell__more-link' . ( $tofw_active_tab === $tofw_tab_key ? ' is-active' : '' ); ?>
									<li class="tofw-admin-shell__more-item">
										<a id="<?php echo esc_attr( $tofw_tab_key ); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=track_orders_for_woocommerce_menu&tofw_tab=' . $tofw_tab_key ) ); ?>" class="<?php echo esc_attr( $tofw_tab_classes ); ?>">
											<?php echo esc_html( $tofw_tab_config['title'] ); ?>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</details>
					</li>
				<?php endif; ?>
			</ul>
		</nav>

		<div class="tofw-admin-shell__content-grid">
			<section class="tofw-admin-shell__main">
				<div class="tofw-admin-shell__content-card">
					<?php
					$tofw_tab_content_path = $tofw_default_tabs[ $tofw_active_tab ]['file_path'];
					$wps_tofw_obj->wps_tofw_plug_load_template( $tofw_tab_content_path, $tofw_active_tab );
					?>
				</div>
			</section>

			<aside class="tofw-admin-shell__sidebar">
				<div class="tofw-admin-sidecard">
					<h2 class="tofw-admin-sidecard__title"><?php esc_html_e( 'Need help with this plugin?', 'track-orders-for-woocommerce' ); ?></h2>
					<p class="tofw-admin-sidecard__text"><?php esc_html_e( 'Guides, documentation, and support links in one place.', 'track-orders-for-woocommerce' ); ?></p>
					<div class="tofw-admin-sidecard__actions">
						<a class="tofw-admin-sidecard__button is-secondary" target="_blank" rel="noopener noreferrer" href="https://wpswings.com/docs/"><?php esc_html_e( 'Watch Video', 'track-orders-for-woocommerce' ); ?></a>
						<a class="tofw-admin-sidecard__button is-secondary" target="_blank" rel="noopener noreferrer" href="https://docs.wpswings.com/track-orders-for-woocommerce/?utm_source=ot-org-page&utm_medium=referral&utm_campaign=ot-doc-free"><?php esc_html_e( 'Documentation', 'track-orders-for-woocommerce' ); ?></a>
						<a class="tofw-admin-sidecard__button is-secondary" target="_blank" rel="noopener noreferrer" href="https://wpswings.com/contact-us/"><?php esc_html_e( 'Support', 'track-orders-for-woocommerce' ); ?></a>
					</div>
				</div>

				<div class="tofw-admin-sidecard">
					<div class="tofw-admin-sidecard__title-wrap">
						<h2 class="tofw-admin-sidecard__title"><?php esc_html_e( 'Grow Your Store with WP Swings', 'track-orders-for-woocommerce' ); ?></h2>
						<span class="tofw-admin-sidecard__star">+</span>
					</div>
					<p class="tofw-admin-sidecard__text"><?php esc_html_e( 'Expert solutions to boost your store performance.', 'track-orders-for-woocommerce' ); ?></p>
					<div class="tofw-admin-sidecard__services">
						<a class="tofw-admin-sidecard__service" target="_blank" rel="noopener noreferrer" href="https://wpswings.com/seo-services/"><?php esc_html_e( 'SEO Services', 'track-orders-for-woocommerce' ); ?></a>
						<a class="tofw-admin-sidecard__service" target="_blank" rel="noopener noreferrer" href="https://wpswings.com/google-ads-setup-and-g4-setup/"><?php esc_html_e( 'Google Ads Setup And G4 Setup', 'track-orders-for-woocommerce' ); ?></a>
						<a class="tofw-admin-sidecard__service" target="_blank" rel="noopener noreferrer" href="https://wpswings.com/speed-optimization-service/"><?php esc_html_e( 'Speed Optimization', 'track-orders-for-woocommerce' ); ?></a>
						<a class="tofw-admin-sidecard__service" target="_blank" rel="noopener noreferrer" href="https://wpswings.com/woocommerce-development-services/"><?php esc_html_e( 'WooCommerce Development Services', 'track-orders-for-woocommerce' ); ?></a>
					</div>
					<button type="button" class="tofw-admin-sidecard__button is-dark tofw-admin-sidecard__button--expert" data-pgfw-open-expert-modal="true"><?php esc_html_e( 'Talk to an Expert', 'track-orders-for-woocommerce' ); ?></button>
					<p class="tofw-admin-sidecard__footer"><?php esc_html_e( 'Services by WP Swings', 'track-orders-for-woocommerce' ); ?></p>
				</div>

				<div class="tofw-admin-sidecard is-accent">
					<h2 class="tofw-admin-sidecard__title"><?php esc_html_e( 'Still facing problems?', 'track-orders-for-woocommerce' ); ?></h2>
					<p class="tofw-admin-sidecard__text"><?php esc_html_e( 'Reach the product team with the exact context behind your issue.', 'track-orders-for-woocommerce' ); ?></p>
					<a class="tofw-admin-sidecard__button is-dark" target="_blank" rel="noopener noreferrer" href="https://wpswings.com/contact-us/"><?php esc_html_e( 'Contact Us', 'track-orders-for-woocommerce' ); ?></a>
				</div>

				<div class="tofw-admin-sidecard">
					<h2 class="tofw-admin-sidecard__title"><?php esc_html_e( 'Explore more plugins', 'track-orders-for-woocommerce' ); ?></h2>
					<p class="tofw-admin-sidecard__text"><?php esc_html_e( 'This design system is meant to scale across the rest of your WooCommerce plugins.', 'track-orders-for-woocommerce' ); ?></p>
					<a class="tofw-admin-sidecard__button is-dark" target="_blank" rel="noopener noreferrer" href="https://wpswings.com/woocommerce-plugins/"><?php esc_html_e( 'View More Plugins', 'track-orders-for-woocommerce' ); ?></a>
				</div>
			</aside>
		</div>
	</div>
</div>
