<?php
/**
 * Shipping analytics tab.
 *
 * @link  https://wpswings.com/
 * @since 1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wps_tofw_obj;

$tofw_insights = $wps_tofw_obj->tofw_get_shipment_insights();
if ( ! $tofw_insights || ! current_user_can( 'manage_woocommerce' ) ) {
	return;
}

$tofw_range   = $tofw_insights->wps_tofw_get_requested_range();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only cache bypass.
$tofw_refresh = isset( $_GET['wps_tofw_refresh'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['wps_tofw_refresh'] ) );
$tofw_report  = $tofw_insights->wps_tofw_get_analytics( $tofw_range, $tofw_refresh );
$tofw_labels  = $tofw_insights->wps_tofw_shipment_status_labels();
$tofw_base    = admin_url( 'admin.php' );
$tofw_shipped = array_sum( $tofw_report['status_counts'] ) - $tofw_report['status_counts']['not_shipped'] - $tofw_report['status_counts']['cancelled'];
$tofw_visits  = isset( $tofw_report['counters']['totals']['tracking_visit'] ) ? (int) $tofw_report['counters']['totals']['tracking_visit'] : 0;
$tofw_clicks  = isset( $tofw_report['counters']['totals']['upsell_click'] ) ? (int) $tofw_report['counters']['totals']['upsell_click'] : 0;
$tofw_date_f  = get_option( 'date_format' );

$tofw_presets = array(
	7   => __( 'Last 7 days', 'track-orders-for-woocommerce' ),
	30  => __( 'Last 30 days', 'track-orders-for-woocommerce' ),
	90  => __( 'Last 90 days', 'track-orders-for-woocommerce' ),
	365 => __( 'Last 12 months', 'track-orders-for-woocommerce' ),
);
$tofw_today = current_time( 'Y-m-d' );
?>
<div class="tofwp-license-tab__hero">
	<div class="tofwp-license-tab__hero-copy">
		<p class="tofwp-license-tab__eyebrow"><?php esc_html_e( 'REPORT', 'track-orders-for-woocommerce' ); ?></p>
		<h2 class="tofwp-license-tab__hero-title"><?php esc_html_e( 'Shipping Analytics', 'track-orders-for-woocommerce' ); ?></h2>
		<p class="tofwp-license-tab__hero-text"><?php esc_html_e( 'Delivery performance per carrier, shipment status breakdown, late shipments and tracking page engagement for orders created in the selected period.', 'track-orders-for-woocommerce' ); ?></p>
	</div>
</div>

<div class="wps-tofw-insights">
	<form method="get" action="<?php echo esc_url( $tofw_base ); ?>" class="wps-tofw-insights__filters">
		<input type="hidden" name="page" value="track_orders_for_woocommerce_menu">
		<input type="hidden" name="tofw_tab" value="<?php echo esc_attr( Track_Orders_For_Woocommerce_Shipment_Insights::TAB_ANALYTICS ); ?>">
		<label>
			<?php esc_html_e( 'From', 'track-orders-for-woocommerce' ); ?>
			<input type="date" name="wps_tofw_from" value="<?php echo esc_attr( $tofw_range['from'] ); ?>" max="<?php echo esc_attr( $tofw_today ); ?>">
		</label>
		<label>
			<?php esc_html_e( 'To', 'track-orders-for-woocommerce' ); ?>
			<input type="date" name="wps_tofw_to" value="<?php echo esc_attr( $tofw_range['to'] ); ?>" max="<?php echo esc_attr( $tofw_today ); ?>">
		</label>
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'track-orders-for-woocommerce' ); ?></button>
		<span class="wps-tofw-insights__presets">
			<?php foreach ( $tofw_presets as $tofw_days => $tofw_preset_label ) : ?>
				<a href="
				<?php
				echo esc_url(
					add_query_arg(
						array(
							'page'          => 'track_orders_for_woocommerce_menu',
							'tofw_tab'      => Track_Orders_For_Woocommerce_Shipment_Insights::TAB_ANALYTICS,
							'wps_tofw_from' => wp_date( 'Y-m-d', strtotime( '-' . ( $tofw_days - 1 ) . ' days' ) ),
							'wps_tofw_to'   => $tofw_today,
						),
						$tofw_base
					)
				);
				?>
				"><?php echo esc_html( $tofw_preset_label ); ?></a>
			<?php endforeach; ?>
		</span>
		<a class="wps-tofw-insights__refresh" href="
		<?php
		echo esc_url(
			add_query_arg(
				array(
					'page'             => 'track_orders_for_woocommerce_menu',
					'tofw_tab'         => Track_Orders_For_Woocommerce_Shipment_Insights::TAB_ANALYTICS,
					'wps_tofw_from'    => $tofw_range['from'],
					'wps_tofw_to'      => $tofw_range['to'],
					'wps_tofw_refresh' => '1',
				),
				$tofw_base
			)
		);
		?>
		"><?php esc_html_e( 'Refresh data', 'track-orders-for-woocommerce' ); ?></a>
	</form>

	<p class="wps-tofw-insights__note">
		<?php
		echo esc_html(
			sprintf(
				/* translators: 1: from date, 2: to date, 3: number of orders, 4: generated time. */
				__( '%1$s – %2$s · %3$d orders analysed · generated %4$s', 'track-orders-for-woocommerce' ),
				wp_date( $tofw_date_f, $tofw_range['from_ts'] ),
				wp_date( $tofw_date_f, $tofw_range['to_ts'] ),
				$tofw_report['scanned'],
				wp_date( get_option( 'time_format' ), $tofw_report['generated'] )
			)
		);
		if ( $tofw_report['truncated'] ) {
			echo ' · ' . esc_html__( 'Order limit reached: narrow the date range for complete figures.', 'track-orders-for-woocommerce' );
		}
		?>
	</p>

	<div class="wps-tofw-insights__cards">
		<div class="wps-tofw-insights__card">
			<span class="wps-tofw-insights__card-label"><?php esc_html_e( 'Shipments', 'track-orders-for-woocommerce' ); ?></span>
			<span class="wps-tofw-insights__card-value"><?php echo esc_html( number_format_i18n( $tofw_shipped ) ); ?></span>
		</div>
		<div class="wps-tofw-insights__card">
			<span class="wps-tofw-insights__card-label"><?php esc_html_e( 'Avg. delivery time', 'track-orders-for-woocommerce' ); ?></span>
			<span class="wps-tofw-insights__card-value">
				<?php
				echo null === $tofw_report['avg_days'] ? '&ndash;' : esc_html(
					/* translators: %s: number of days. */
					sprintf( __( '%s days', 'track-orders-for-woocommerce' ), number_format_i18n( $tofw_report['avg_days'], 1 ) )
				);
				?>
			</span>
		</div>
		<div class="wps-tofw-insights__card is-warning">
			<span class="wps-tofw-insights__card-label"><?php esc_html_e( 'Late / exception', 'track-orders-for-woocommerce' ); ?></span>
			<span class="wps-tofw-insights__card-value"><?php echo esc_html( number_format_i18n( $tofw_report['late_count'] ) ); ?></span>
		</div>
		<div class="wps-tofw-insights__card">
			<span class="wps-tofw-insights__card-label"><?php esc_html_e( 'Tracking page visits', 'track-orders-for-woocommerce' ); ?></span>
			<span class="wps-tofw-insights__card-value"><?php echo esc_html( number_format_i18n( $tofw_visits ) ); ?></span>
		</div>
		<div class="wps-tofw-insights__card">
			<span class="wps-tofw-insights__card-label"><?php esc_html_e( 'Upsell clicks', 'track-orders-for-woocommerce' ); ?></span>
			<span class="wps-tofw-insights__card-value">
				<?php
				echo esc_html( number_format_i18n( $tofw_clicks ) );
				if ( $tofw_visits > 0 ) {
					echo ' <small>(' . esc_html( number_format_i18n( ( $tofw_clicks / $tofw_visits ) * 100, 1 ) ) . '%)</small>';
				}
				?>
			</span>
		</div>
	</div>

	<div class="wps-tofw-insights__grid">
		<section class="wps-tofw-insights__panel">
			<h3><?php esc_html_e( 'Average delivery time per carrier', 'track-orders-for-woocommerce' ); ?></h3>
			<?php if ( empty( $tofw_report['carriers'] ) ) : ?>
				<p><?php esc_html_e( 'No shipments in this period.', 'track-orders-for-woocommerce' ); ?></p>
			<?php else : ?>
				<?php
				$tofw_max_avg = max( array_merge( array( 0.1 ), array_filter( wp_list_pluck( $tofw_report['carriers'], 'avg_days' ) ) ) );
				?>
				<table class="widefat striped wps-tofw-insights__table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Carrier', 'track-orders-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Shipments', 'track-orders-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Delivered', 'track-orders-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Late', 'track-orders-for-woocommerce' ); ?></th>
							<th class="wps-tofw-insights__bar-col"><?php esc_html_e( 'Avg. days (shipped → delivered)', 'track-orders-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $tofw_report['carriers'] as $tofw_carrier => $tofw_row ) : ?>
							<tr>
								<td><?php echo esc_html( $tofw_carrier ); ?></td>
								<td><?php echo esc_html( number_format_i18n( $tofw_row['shipments'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( $tofw_row['delivered'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( $tofw_row['late'] ) ); ?></td>
								<td class="wps-tofw-insights__bar-col">
									<?php if ( null === $tofw_row['avg_days'] ) : ?>
										&ndash;
									<?php else : ?>
										<span class="wps-tofw-insights__bar" style="width:<?php echo esc_attr( max( 2, round( ( $tofw_row['avg_days'] / $tofw_max_avg ) * 100 ) ) ); ?>%"></span>
										<span class="wps-tofw-insights__bar-value"><?php echo esc_html( number_format_i18n( $tofw_row['avg_days'], 1 ) ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>

		<section class="wps-tofw-insights__panel">
			<h3><?php esc_html_e( 'Orders by shipment status', 'track-orders-for-woocommerce' ); ?></h3>
			<?php $tofw_max_status = max( 1, max( $tofw_report['status_counts'] ) ); ?>
			<table class="widefat striped wps-tofw-insights__table">
				<tbody>
					<?php foreach ( $tofw_report['status_counts'] as $tofw_status => $tofw_count ) : ?>
						<tr>
							<td><?php echo esc_html( $tofw_labels[ $tofw_status ] ); ?></td>
							<td class="wps-tofw-insights__bar-col">
								<span class="wps-tofw-insights__bar is-<?php echo esc_attr( $tofw_status ); ?>" style="width:<?php echo esc_attr( $tofw_count ? max( 2, round( ( $tofw_count / $tofw_max_status ) * 100 ) ) : 0 ); ?>%"></span>
								<span class="wps-tofw-insights__bar-value"><?php echo esc_html( number_format_i18n( $tofw_count ) ); ?></span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	</div>

	<section class="wps-tofw-insights__panel">
		<h3><?php esc_html_e( 'Late shipments & exceptions', 'track-orders-for-woocommerce' ); ?></h3>
		<?php if ( empty( $tofw_report['late'] ) ) : ?>
			<p><?php esc_html_e( 'No late shipments in this period.', 'track-orders-for-woocommerce' ); ?></p>
		<?php else : ?>
			<table class="widefat striped wps-tofw-insights__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Order', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Carrier', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Tracking number', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Shipment status', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Shipped', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Issue', 'track-orders-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tofw_report['late'] as $tofw_late ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( $tofw_late['edit_url'] ); ?>">#<?php echo esc_html( $tofw_late['number'] ); ?></a></td>
							<td><?php echo esc_html( $tofw_late['carrier'] ); ?></td>
							<td><?php echo esc_html( '' !== $tofw_late['tracking'] ? $tofw_late['tracking'] : '–' ); ?></td>
							<td><?php echo esc_html( $tofw_labels[ $tofw_late['status'] ] ); ?></td>
							<td><?php echo esc_html( $tofw_late['shipped'] ? wp_date( $tofw_date_f, $tofw_late['shipped'] ) : '–' ); ?></td>
							<td><?php echo esc_html( implode( ', ', $tofw_late['issues'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $tofw_report['late_count'] > count( $tofw_report['late'] ) ) : ?>
				<p class="wps-tofw-insights__note"><?php esc_html_e( 'Showing the 100 most recent late shipments.', 'track-orders-for-woocommerce' ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	</section>

	<section class="wps-tofw-insights__panel">
		<h3><?php esc_html_e( 'Tracking page visits & upsell clicks', 'track-orders-for-woocommerce' ); ?></h3>
		<?php if ( empty( $tofw_report['counters']['daily'] ) ) : ?>
			<p><?php esc_html_e( 'No tracking page activity recorded in this period.', 'track-orders-for-woocommerce' ); ?></p>
		<?php else : ?>
			<?php
			$tofw_max_daily = 1;
			foreach ( $tofw_report['counters']['daily'] as $tofw_day_counts ) {
				$tofw_max_daily = max( $tofw_max_daily, isset( $tofw_day_counts['tracking_visit'] ) ? $tofw_day_counts['tracking_visit'] : 0, isset( $tofw_day_counts['upsell_click'] ) ? $tofw_day_counts['upsell_click'] : 0 );
			}
			?>
			<table class="widefat striped wps-tofw-insights__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'track-orders-for-woocommerce' ); ?></th>
						<th class="wps-tofw-insights__bar-col"><?php esc_html_e( 'Tracking page visits', 'track-orders-for-woocommerce' ); ?></th>
						<th class="wps-tofw-insights__bar-col"><?php esc_html_e( 'Upsell clicks', 'track-orders-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $tofw_report['counters']['daily'] as $tofw_day => $tofw_day_counts ) : ?>
						<?php
						$tofw_v = isset( $tofw_day_counts['tracking_visit'] ) ? (int) $tofw_day_counts['tracking_visit'] : 0;
						$tofw_c = isset( $tofw_day_counts['upsell_click'] ) ? (int) $tofw_day_counts['upsell_click'] : 0;
						?>
						<tr>
							<td><?php echo esc_html( wp_date( $tofw_date_f, strtotime( $tofw_day . ' 12:00:00' ) ) ); ?></td>
							<td class="wps-tofw-insights__bar-col">
								<span class="wps-tofw-insights__bar" style="width:<?php echo esc_attr( $tofw_v ? max( 2, round( ( $tofw_v / $tofw_max_daily ) * 100 ) ) : 0 ); ?>%"></span>
								<span class="wps-tofw-insights__bar-value"><?php echo esc_html( number_format_i18n( $tofw_v ) ); ?></span>
							</td>
							<td class="wps-tofw-insights__bar-col">
								<span class="wps-tofw-insights__bar is-upsell" style="width:<?php echo esc_attr( $tofw_c ? max( 2, round( ( $tofw_c / $tofw_max_daily ) * 100 ) ) : 0 ); ?>%"></span>
								<span class="wps-tofw-insights__bar-value"><?php echo esc_html( number_format_i18n( $tofw_c ) ); ?></span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>
</div>
