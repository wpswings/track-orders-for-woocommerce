<?php
/**
 * Guest track order page.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/template
 */
add_action(
	'woocommerce_admin_order_data_after_order_details',
	function ( $order ) {
		woocommerce_wp_text_input(
			array(
				'id'            => '_wps_dhl_tracking_number',
				'label'         => esc_html__( 'DHL Tracking Number', 'track-orders-for-woocommerce' ),
				'wrapper_class' => 'form-field-wide',
				'value'         => esc_attr( get_post_meta( $order->get_id(), '_wps_dhl_tracking_number', true ) ),
				'placeholder'   => esc_html__( 'Enter DHL tracking number', 'track-orders-for-woocommerce' ),
			)
		);
	}
);

add_action(
	'woocommerce_process_shop_order_meta',
	function ( $order_id ) {
		if (
			isset( $_POST['_wps_dhl_tracking_number'] ) &&
			check_admin_referer( 'woocommerce_save_data', 'woocommerce_meta_nonce' )
		) {
			$tracking_number = sanitize_text_field( wp_unslash( $_POST['_wps_dhl_tracking_number'] ) );
			update_post_meta( $order_id, '_wps_dhl_tracking_number', $tracking_number );
		}
	}
);

add_filter(
	'woocommerce_my_account_my_orders_actions',
	function ( $actions, $order ) {
		$tracking = get_post_meta( $order->get_id(), '_wps_dhl_tracking_number', true );
		if ( $tracking ) {
			$url                      = wc_get_endpoint_url( 'dhl-tracking', $order->get_id(), wc_get_page_permalink( 'myaccount' ) );
			$actions['dhl_tracking'] = array(
				'url'    => esc_url( $url ),
				'name'   => esc_html__( 'DHL Tracking', 'track-orders-for-woocommerce' ),
				'target' => '_blank',
			);
		}
		return $actions;
	},
	10,
	2
);

add_action(
	'init',
	function () {
		add_rewrite_endpoint( 'dhl-tracking', EP_ROOT | EP_PAGES );
	}
);

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'dhl-tracking';
		return $vars;
	}
);

add_action(
	'woocommerce_account_dhl-tracking_endpoint',
	function () {
		$order_id = absint( get_query_var( 'dhl-tracking' ) );
		$tracking = get_post_meta( $order_id, '_wps_dhl_tracking_number', true );

		echo '<div class="woocommerce">';
		echo '<h2>' . esc_html__( 'DHL Shipment Tracking', 'track-orders-for-woocommerce' ) . '</h2>';

		if ( ! $tracking ) {
			echo '<p>' . esc_html__( 'No tracking number found for this order.', 'track-orders-for-woocommerce' ) . '</p>';
			echo '</div>';
			return;
		}

		echo do_shortcode( '[wps_dhl_track tracking="' . esc_attr( $tracking ) . '"]' );
		echo '</div>';
	}
);

/**
 * Shortcode handler for DHL tracking.
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string
 */
function wps_dhl_tracking_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'tracking' => '',
		),
		$atts,
		'wps_dhl_track'
	);

	$tracking_number = sanitize_text_field( $atts['tracking'] );
	if ( empty( $tracking_number ) ) {
		return '<p>' . esc_html__( 'No tracking number provided.', 'track-orders-for-woocommerce' ) . '</p>';
	}

	$wps_dhl_api_key = get_option( 'wps_enable_dhl_api_key'  , '');

	$url = 'https://api-eu.dhl.com/track/shipments?trackingNumber=' . urlencode( $tracking_number );

	$response = wp_remote_get(
		$url,
		array(
			'headers' => array(
				'DHL-API-Key' => $wps_dhl_api_key,
				'Accept'      => 'application/json',
			),
			'timeout' => 20,
		)
	);

	if ( is_wp_error( $response ) ) {
		return '<p>' . esc_html__( 'Failed to connect to DHL API.', 'track-orders-for-woocommerce' ) . '</p>';
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( empty( $data['shipments'][0] ) ) {
		return '<p>' . esc_html__( 'Tracking info not found.', 'track-orders-for-woocommerce' ) . '</p>';
	}

	$shipment            = $data['shipments'][0];
	$status              = ! empty( $shipment['status']['description'] ) ? esc_html( $shipment['status']['description'] ) : esc_html__( 'Unknown status', 'track-orders-for-woocommerce' );
	$events              = $shipment['events'] ?? array();
	$estimated_delivery  = ! empty( $shipment['estimatedDelivery'] ) ? $shipment['estimatedDelivery'] : null;

	ob_start();

	$truck_icon = get_option( 'wps_enable_dhl_track_icon' , '');
	$truck_icon_url = esc_url( $truck_icon ? $truck_icon : TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'assets/truck.svg' );

	$box_icon = get_option( 'wps_dhl_truck_icon' );
	$box_icon_url = esc_url( $box_icon ? $box_icon : TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'assets/box.svg' );

	$wps_dhl_tracking_color   = esc_attr( get_option( 'wps_enable_dhl_tracking_color', '#e0f7fa' ) );
	$wps_dhl_tracking_text_color = esc_attr( get_option( 'wps_enable_dhl_tracking_text_color', '#0277bd' ) );
	?>
	<style>
		.wps-dhl-status {
			color: <?php echo esc_html( $wps_dhl_tracking_text_color ); ?>;
			background: <?php echo esc_html( $wps_dhl_tracking_color ); ?>;
		}
		.wps-dhl-event-item strong,
		.wps-dhl-event-item small,
		.wps_tracking_number_css {
			color: <?php echo esc_html( $wps_dhl_tracking_text_color ); ?> !important;
		}
	</style>

	<div class="wps-dhl-tracking">
		<h3 class="wps_tracking_number_css"><?php esc_html_e( 'Tracking Number:', 'track-orders-for-woocommerce' ); ?> <?php echo esc_html( $tracking_number ); ?></h3>
        <input type="hidden" value = "<?php echo esc_attr($tracking_number); ?>" id = "wps_dhl_tracking_id"/>
		<div class="wps-dhl-status">
			<p><img src="<?php echo esc_url( $box_icon_url ); ?>" alt="Box Icon"> <?php esc_html_e( 'Current Status:', 'track-orders-for-woocommerce' ); ?> <?php echo esc_html( $status ); ?></p>
			<?php if ( ! empty( $estimated_delivery ) ) : ?>
				<p>📅 <?php esc_html_e( 'Estimated Delivery:', 'track-orders-for-woocommerce' ); ?> <strong><?php echo esc_html( gmdate( 'F j, Y', strtotime( $estimated_delivery ) ) ); ?></strong></p>
			<?php endif; ?>
		</div>

		<div class="wps-truck-icon" id="wpsTruckIcon">
			<img src="<?php echo esc_url( $truck_icon_url ); ?>" alt="Truck Icon">
		</div>
		<div class="wps-dhl-step-list-wrapper">
			<ul class="wps-dhl-event-list" id="wpsEventList">
				<?php foreach ( ( $events ) as $event ) : ?>
					<li class="wps-dhl-event-item">
						<strong><?php echo esc_html( $event['description'] ); ?></strong>
						<small>
							<?php echo esc_html( gmdate( 'F j, Y, H:i', strtotime( $event['timestamp'] ) ) ); ?>
							<?php if ( ! empty( $event['location']['address']['addressLocality'] ) ) : ?>
								– <?php echo esc_html( $event['location']['address']['addressLocality'] ); ?>
							<?php endif; ?>
						</small>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>

	<script>
		document.addEventListener("DOMContentLoaded", function () {
			const truck = document.getElementById("wpsTruckIcon");
			const items = document.querySelectorAll(".wps-dhl-event-item");

			items.forEach(item => {
				item.addEventListener("click", () => {
					items.forEach(i => i.classList.remove("lifted"));
					item.classList.add("lifted");
				});
			});

			let current = 0;

			if (items.length > 0) {
				const firstItemTop = items[0].offsetTop;
				truck.style.top = (firstItemTop + 150) + "px";
			}

			truck.classList.add("wps-truck-starting");

			function moveTruck() {
				if (current >= items.length) {
					truck.classList.remove("wps-truck-starting");
					return;
				}

				const item = items[current];
				item.classList.add("active");

				const topPosition = item.offsetTop;
				truck.style.top = (topPosition + 200) + "px";

				current++;
				setTimeout(moveTruck, 1000);
			}

			setTimeout(() => {
				moveTruck();
			}, 800);
		});
	</script>

	<?php
	return ob_get_clean();
}
add_shortcode( 'wps_dhl_track', 'wps_dhl_tracking_shortcode' );
