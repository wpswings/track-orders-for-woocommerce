<?php
/**
 * Carrier Status Sync settings tab.
 *
 * @link       https://wpswings.com/
 * @since      1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wps_tofw_obj;

$tofw_carrier_sync_settings =
	/**
	 * Carrier status sync settings fields.
	 *
	 * @since 1.3.0
	 */
	apply_filters( 'tofw_carrier_sync_settings_array', array() );

$tofw_carrier_sync = new Track_Orders_For_Woocommerce_Carrier_Sync();
$tofw_usage        = $tofw_carrier_sync->get_usage_summary();
$tofw_date_format  = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
$tofw_percent      = ( ! $tofw_usage['unlimited'] && $tofw_usage['limit'] > 0 ) ? min( 100, round( $tofw_usage['used'] / $tofw_usage['limit'] * 100 ) ) : 0;
?>
<div class="tofwp-license-tab__hero">
	<div class="tofwp-license-tab__hero-copy">
		<p class="tofwp-license-tab__eyebrow"><?php esc_html_e( 'SECTION', 'track-orders-for-woocommerce' ); ?></p>
		<h2 class="tofwp-license-tab__hero-title"><?php esc_html_e( 'Carrier Status Sync', 'track-orders-for-woocommerce' ); ?></h2>
		<p class="tofwp-license-tab__hero-text"><?php esc_html_e( 'Automatically update shipment status (In transit, Out for delivery, Delivered, Exception) from carrier APIs.', 'track-orders-for-woocommerce' ); ?></p>
	</div>
	<a class="tofwp-license-tab__doc-link button button-primary" target="_blank" rel="noopener noreferrer" href="https://docs.wpswings.com/track-orders-for-woocommerce/?utm_source=ot-org-page&utm_medium=referral&utm_campaign=ot-doc-free"><?php esc_html_e( 'Read Documentation', 'track-orders-for-woocommerce' ); ?></a>
</div>

<?php if ( isset( $_GET['wps_tofw_sync_queued'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
	<?php if ( '1' === sanitize_text_field( wp_unslash( $_GET['wps_tofw_sync_queued'] ) ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success inline"><p><?php esc_html_e( 'A sync batch has been queued and will run in the background shortly.', 'track-orders-for-woocommerce' ); ?></p></div>
	<?php else : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'Enable the carrier status sync and save the settings first.', 'track-orders-for-woocommerce' ); ?></p></div>
	<?php endif; ?>
<?php endif; ?>

<div class="wps-tofw-carrier-sync-usage" style="border:1px solid #dcdcde;border-radius:8px;padding:16px;margin:16px 0;background:#fff;">
	<h3 style="margin-top:0;"><?php esc_html_e( 'Monthly Usage', 'track-orders-for-woocommerce' ); ?></h3>
	<p class="wps-tofw-carrier-sync-usage__count" style="font-size:16px;">
		<?php
		printf(
			/* translators: 1: lookups used, 2: monthly limit. */
			esc_html__( 'Lookups used this month: %1$s / %2$s', 'track-orders-for-woocommerce' ),
			'<strong>' . esc_html( number_format_i18n( $tofw_usage['used'] ) ) . '</strong>',
			'<strong>' . esc_html( $tofw_usage['limit_label'] ) . '</strong>'
		);
		?>
	</p>
	<?php if ( ! $tofw_usage['unlimited'] ) : ?>
		<div style="background:#f0f0f1;border-radius:4px;height:8px;max-width:420px;overflow:hidden;">
			<div style="background:<?php echo esc_attr( $tofw_usage['limit_reached'] ? '#d63638' : '#2271b1' ); ?>;height:8px;width:<?php echo esc_attr( $tofw_percent ); ?>%;"></div>
		</div>
		<p class="description">
			<?php
			/* translators: %s: date the counter resets. */
			printf( esc_html__( 'The counter resets on %s.', 'track-orders-for-woocommerce' ), esc_html( $tofw_usage['resets_on'] ) );
			?>
		</p>
		<?php if ( $tofw_usage['limit_reached'] ) : ?>
			<p style="color:#d63638;"><?php esc_html_e( 'The monthly lookup limit has been reached. Statuses will be updated again next month, or upgrade to Pro for unlimited lookups.', 'track-orders-for-woocommerce' ); ?></p>
		<?php endif; ?>
	<?php endif; ?>
	<p>
		<?php esc_html_e( 'Last run:', 'track-orders-for-woocommerce' ); ?>
		<strong><?php echo esc_html( $tofw_usage['last_run'] ? wp_date( $tofw_date_format, $tofw_usage['last_run'] ) : __( 'Never', 'track-orders-for-woocommerce' ) ); ?></strong>
		&nbsp;|&nbsp;
		<?php esc_html_e( 'Next run:', 'track-orders-for-woocommerce' ); ?>
		<strong><?php echo esc_html( $tofw_usage['next_run'] ? wp_date( $tofw_date_format, $tofw_usage['next_run'] ) : __( 'Not scheduled', 'track-orders-for-woocommerce' ) ); ?></strong>
	</p>
	<p>
		<?php esc_html_e( 'Providers:', 'track-orders-for-woocommerce' ); ?>
		<?php foreach ( $tofw_usage['providers'] as $tofw_provider ) : ?>
			<span style="display:inline-block;margin-right:12px;">
				<?php echo esc_html( $tofw_provider['label'] ); ?>
				&ndash;
				<?php if ( $tofw_provider['configured'] ) : ?>
					<span style="color:#008a20;"><?php esc_html_e( 'Ready', 'track-orders-for-woocommerce' ); ?></span>
				<?php else : ?>
					<span style="color:#646970;"><?php esc_html_e( 'Not configured', 'track-orders-for-woocommerce' ); ?></span>
				<?php endif; ?>
			</span>
		<?php endforeach; ?>
	</p>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
		<input type="hidden" name="action" value="wps_tofw_carrier_sync_run_now">
		<?php wp_nonce_field( 'wps_tofw_carrier_sync_run_now' ); ?>
		<button type="submit" class="button" <?php disabled( ! $tofw_usage['enabled'] ); ?>><?php esc_html_e( 'Run Sync Now', 'track-orders-for-woocommerce' ); ?></button>
	</form>
	<p class="description"><?php esc_html_e( 'FedEx shipments use the FedEx credentials from the Shipping Services tab; every other carrier is tracked via 17TRACK.', 'track-orders-for-woocommerce' ); ?></p>
</div>

<form action="" method="POST" class="wps-tofw-gen-section-form">
	<div class="tofw-secion-wrap">
		<?php
		$tofw_carrier_sync_html = $wps_tofw_obj->wps_std_plug_generate_html( $tofw_carrier_sync_settings );
		echo esc_html( $tofw_carrier_sync_html );
		wp_nonce_field( 'admin_save_data', 'wps_tabs_nonce' );
		?>
	</div>
</form>
