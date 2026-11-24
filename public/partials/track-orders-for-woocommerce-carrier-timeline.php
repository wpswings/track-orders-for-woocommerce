<?php
/**
 * Carrier status timeline shown on the customer tracking page.
 *
 * Available variables:
 * - $wps_tofw_carrier_status       string Normalized status.
 * - $wps_tofw_carrier_status_label string Status label.
 * - $wps_tofw_carrier_events       array  Events (newest first).
 * - $wps_tofw_carrier_updated      int    Last checked timestamp.
 *
 * @link       https://wpswings.com/
 * @since      1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/public/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wps_tofw_timeline_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
?>
<div class="wps-tofw-carrier-timeline wps-tofw-carrier-timeline--<?php echo esc_attr( $wps_tofw_carrier_status ); ?>">
	<div class="wps-tofw-carrier-timeline__header">
		<h3 class="wps-tofw-carrier-timeline__title"><?php esc_html_e( 'Shipment Status', 'track-orders-for-woocommerce' ); ?></h3>
		<span class="wps-tofw-carrier-timeline__badge"><?php echo esc_html( $wps_tofw_carrier_status_label ); ?></span>
	</div>
	<?php if ( $wps_tofw_carrier_updated ) : ?>
		<p class="wps-tofw-carrier-timeline__updated">
			<?php
			/* translators: %s: date and time. */
			printf( esc_html__( 'Last updated: %s', 'track-orders-for-woocommerce' ), esc_html( wp_date( $wps_tofw_timeline_format, $wps_tofw_carrier_updated ) ) );
			?>
		</p>
	<?php endif; ?>
	<?php if ( ! empty( $wps_tofw_carrier_events ) ) : ?>
		<ol class="wps-tofw-carrier-timeline__events">
			<?php foreach ( $wps_tofw_carrier_events as $wps_tofw_index => $wps_tofw_event ) : ?>
				<li class="wps-tofw-carrier-timeline__event<?php echo 0 === $wps_tofw_index ? ' is-latest' : ''; ?>">
					<span class="wps-tofw-carrier-timeline__dot" aria-hidden="true"></span>
					<div class="wps-tofw-carrier-timeline__content">
						<p class="wps-tofw-carrier-timeline__description"><?php echo esc_html( $wps_tofw_event['description'] ); ?></p>
						<p class="wps-tofw-carrier-timeline__meta">
							<?php if ( ! empty( $wps_tofw_event['time'] ) ) : ?>
								<time datetime="<?php echo esc_attr( gmdate( 'c', $wps_tofw_event['time'] ) ); ?>"><?php echo esc_html( wp_date( $wps_tofw_timeline_format, $wps_tofw_event['time'] ) ); ?></time>
							<?php endif; ?>
							<?php if ( ! empty( $wps_tofw_event['location'] ) ) : ?>
								<span class="wps-tofw-carrier-timeline__location"><?php echo esc_html( $wps_tofw_event['location'] ); ?></span>
							<?php endif; ?>
						</p>
					</div>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php else : ?>
		<p class="wps-tofw-carrier-timeline__empty"><?php esc_html_e( 'No tracking events from the carrier yet.', 'track-orders-for-woocommerce' ); ?></p>
	<?php endif; ?>
</div>
