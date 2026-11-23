<?php
/**
 * Multi-vendor (marketplace) shipment tracking.
 *
 * @link  https://wpswings.com/
 * @since 1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes/multi-vendor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lets marketplace vendors (Dokan, WCFM, WC Vendors) add carrier and
 * tracking details, including partial shipments, for their part of an order
 * and shows the per-vendor shipments to the customer.
 *
 * Storage format (one order meta `wps_tofw_vendor_shipments_<vendor_id>` per
 * vendor, so vendors of the same WCFM / WC Vendors parent order never
 * overwrite each other's shipments), on the Dokan sub-order or on the parent
 * order for WCFM / WC Vendors:
 *
 *     array(
 *         <shipment_id> => array(
 *                 'id'              => string,
 *                 'carrier'         => string  carrier key (same keys as `wps_tofw_selected_shipping_service`),
 *                 'carrier_name'    => string,
 *                 'tracking_number' => string,
 *                 'tracking_url'    => string,
 *                 'date'            => string  Y-m-d,
 *                 'items'           => array( <order_item_id> => <qty> ),
 *                 'created'         => int     timestamp,
 *         ),
 *     )
 *
 * The legacy combined meta `wps_tofw_vendor_shipments` (vendor_id =>
 * shipments) is still read; a vendor's own key takes precedence over it and
 * the vendor is moved out of the legacy meta on its next save.
 *
 * The latest shipment is also mirrored to the existing
 * `wps_tofw_selected_shipping_service` / `wps_tofw_package_tracking_number`
 * meta (Dokan sub-order, or WCFM parent order with a single shipment and no
 * own tracking) so the existing admin meta box, REST API and tracking
 * templates keep working, but only for carriers the base plugin supports.
 *
 * @since 1.3.0
 */
class Track_Orders_For_Woocommerce_Multi_Vendor {

	/**
	 * Legacy order meta key holding all vendor shipments, and prefix of the
	 * per-vendor meta keys (`wps_tofw_vendor_shipments_<vendor_id>`).
	 *
	 * @since 1.3.0
	 */
	const META_KEY = 'wps_tofw_vendor_shipments';

	/**
	 * Order meta flag set when the base tracking meta was mirrored from a
	 * vendor shipment (so it may be updated / cleared again).
	 *
	 * @since 1.3.0
	 */
	const MIRRORED_META_KEY = '_wps_tofw_vendor_tracking_mirrored';

	/**
	 * Nonce action.
	 *
	 * @since 1.3.0
	 */
	const NONCE_ACTION = 'wps_tofw_vendor_shipment';

	/**
	 * Plugin version, used for assets.
	 *
	 * @since 1.3.0
	 * @var   string
	 */
	protected $version;

	/**
	 * Active integrations keyed by marketplace key.
	 *
	 * @since 1.3.0
	 * @var   Track_Orders_For_Woocommerce_Vendor_Integration[]
	 */
	protected $integrations = array();

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 * @param string $version Plugin version.
	 */
	public function __construct( $version ) {
		$this->version = $version;
	}

	/**
	 * Load the integrations of the active marketplace plugins.
	 *
	 * Runs on `plugins_loaded` so marketplace plugins loaded after this
	 * plugin are detected too.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function wps_tofw_init_vendor_integrations() {
		/**
		 * Enable or disable vendor shipment tracking.
		 *
		 * @since 1.3.0
		 * @param bool $enabled Whether the feature is enabled.
		 */
		if ( ! apply_filters( 'wps_tofw_enable_vendor_tracking', true ) ) {
			return;
		}

		/**
		 * Marketplace integrations, marketplace key => class name.
		 *
		 * @since 1.3.0
		 * @param string[] $integrations Integration classes.
		 */
		$classes = apply_filters(
			'wps_tofw_vendor_tracking_integrations',
			array(
				'dokan'     => 'Track_Orders_For_Woocommerce_Vendor_Dokan',
				'wcfm'      => 'Track_Orders_For_Woocommerce_Vendor_Wcfm',
				'wcvendors' => 'Track_Orders_For_Woocommerce_Vendor_Wcvendors',
			)
		);

		foreach ( (array) $classes as $key => $class ) {
			if ( ! class_exists( $class ) || ! is_subclass_of( $class, 'Track_Orders_For_Woocommerce_Vendor_Integration' ) ) {
				continue;
			}
			if ( ! call_user_func( array( $class, 'is_marketplace_active' ) ) ) {
				continue;
			}
			$integration = new $class( $this );
			$integration->register_hooks();
			$this->integrations[ $integration->get_key() ] = $integration;
		}
	}

	/**
	 * Active integrations.
	 *
	 * @since 1.3.0
	 * @return Track_Orders_For_Woocommerce_Vendor_Integration[]
	 */
	public function get_integrations() {
		return $this->integrations;
	}

	/**
	 * Integration by key.
	 *
	 * @since 1.3.0
	 * @param string $key Marketplace key.
	 * @return Track_Orders_For_Woocommerce_Vendor_Integration|null
	 */
	public function get_integration( $key ) {
		return isset( $this->integrations[ $key ] ) ? $this->integrations[ $key ] : null;
	}

	/**
	 * Carriers vendors can choose from.
	 *
	 * Keys match the existing `wps_tofw_selected_shipping_service` values.
	 * `url` holds a `%s` placeholder for the tracking number.
	 *
	 * @since 1.3.0
	 * @return array[] key => array( 'name' => string, 'url' => string ).
	 */
	public function get_carriers() {
		$carriers = array(
			'fedex'          => array(
				'name' => 'FedEx',
				'url'  => 'https://www.fedex.com/fedextrack/?trknbr=%s',
			),
			'ups'            => array(
				'name' => 'UPS',
				'url'  => 'https://www.ups.com/track?tracknum=%s',
			),
			'usps'           => array(
				'name' => 'USPS',
				'url'  => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=%s',
			),
			'dhl'            => array(
				'name' => 'DHL Express',
				'url'  => 'https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id=%s',
			),
			'canada_post'    => array(
				'name' => 'Canada Post',
				'url'  => 'https://www.canadapost-postescanada.ca/track-reperage/en#/search?searchFor=%s',
			),
			'royal_mail'     => array(
				'name' => 'Royal Mail',
				'url'  => 'https://www.royalmail.com/track-your-item#/tracking-results/%s',
			),
			'australia_post' => array(
				'name' => 'Australia Post',
				'url'  => 'https://auspost.com.au/mypost/track/#/details/%s',
			),
			'dpd'            => array(
				'name' => 'DPD',
				'url'  => 'https://tracking.dpd.de/status/en_US/parcel/%s',
			),
		);

		/** This filter is documented in admin/class-track-orders-for-woocommerce-admin.php */
		$services = apply_filters( 'wps_tofw_add_diffrent_shipping_services', array() );
		foreach ( (array) $services as $key => $name ) {
			$key = sanitize_key( $key );
			if ( '' !== $key && ! isset( $carriers[ $key ] ) ) {
				$carriers[ $key ] = array(
					'name' => (string) $name,
					'url'  => '',
				);
			}
		}

		/**
		 * Carriers available to vendors.
		 *
		 * @since 1.3.0
		 * @param array[] $carriers key => array( 'name' => string, 'url' => string with %s for the tracking number ).
		 */
		$carriers = apply_filters( 'wps_tofw_vendor_tracking_carriers', $carriers );

		$carriers['other'] = array(
			'name' => __( 'Other', 'track-orders-for-woocommerce' ),
			'url'  => '',
		);

		return $carriers;
	}

	/**
	 * All vendor shipments of an order.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return array
	 */
	public function get_all_shipments( $order ) {
		$legacy = $order->get_meta( self::META_KEY, true );
		$all    = is_array( $legacy ) ? $legacy : array();
		$prefix = self::META_KEY . '_';

		foreach ( $order->get_meta_data() as $meta ) {
			$data = $meta->get_data();
			if ( 0 !== strpos( $data['key'], $prefix ) ) {
				continue;
			}
			$vendor_id = absint( substr( $data['key'], strlen( $prefix ) ) );
			if ( $vendor_id && is_array( $data['value'] ) ) {
				$all[ $vendor_id ] = $data['value'];
			}
		}

		foreach ( $all as $vendor_id => $shipments ) {
			if ( empty( $shipments ) || ! is_array( $shipments ) ) {
				unset( $all[ $vendor_id ] );
			}
		}
		return $all;
	}

	/**
	 * Per-vendor order meta key.
	 *
	 * @since 1.3.0
	 * @param int $vendor_id Vendor id.
	 * @return string
	 */
	protected function get_vendor_meta_key( $vendor_id ) {
		return self::META_KEY . '_' . absint( $vendor_id );
	}

	/**
	 * Save the shipments of one vendor in its own meta key (does not save the
	 * order). Moves the vendor out of the legacy combined meta if present.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order     Storage order.
	 * @param int      $vendor_id Vendor id.
	 * @param array    $shipments Shipments of the vendor.
	 * @return void
	 */
	protected function set_vendor_shipments( $order, $vendor_id, $shipments ) {
		if ( empty( $shipments ) ) {
			$order->delete_meta_data( $this->get_vendor_meta_key( $vendor_id ) );
		} else {
			$order->update_meta_data( $this->get_vendor_meta_key( $vendor_id ), $shipments );
		}

		$legacy = $order->get_meta( self::META_KEY, true );
		if ( is_array( $legacy ) && isset( $legacy[ $vendor_id ] ) ) {
			unset( $legacy[ $vendor_id ] );
			if ( empty( $legacy ) ) {
				$order->delete_meta_data( self::META_KEY );
			} else {
				$order->update_meta_data( self::META_KEY, $legacy );
			}
		}
	}

	/**
	 * Shipments of one vendor, oldest first.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order     Order.
	 * @param int      $vendor_id Vendor id.
	 * @return array[]
	 */
	public function get_vendor_shipments( $order, $vendor_id ) {
		$all       = $this->get_all_shipments( $order );
		$shipments = isset( $all[ $vendor_id ] ) && is_array( $all[ $vendor_id ] ) ? $all[ $vendor_id ] : array();
		uasort(
			$shipments,
			function ( $a, $b ) {
				return ( isset( $a['created'] ) ? (int) $a['created'] : 0 ) - ( isset( $b['created'] ) ? (int) $b['created'] : 0 );
			}
		);
		return $shipments;
	}

	/**
	 * Remaining (not yet shipped, not refunded) quantity per vendor item.
	 *
	 * @since 1.3.0
	 * @param Track_Orders_For_Woocommerce_Vendor_Integration $integration Integration.
	 * @param WC_Order                                        $order       Storage order.
	 * @param int                                             $vendor_id   Vendor id.
	 * @return array[] item_id => array( 'name', 'ordered', 'shipped', 'remaining' ).
	 */
	public function get_vendor_item_quantities( $integration, $order, $vendor_id ) {
		$shipped = array();
		foreach ( $this->get_vendor_shipments( $order, $vendor_id ) as $shipment ) {
			foreach ( (array) $shipment['items'] as $item_id => $qty ) {
				$shipped[ $item_id ] = ( isset( $shipped[ $item_id ] ) ? $shipped[ $item_id ] : 0 ) + (int) $qty;
			}
		}

		$rows = array();
		foreach ( $integration->get_vendor_items( $order, $vendor_id ) as $item_id => $item ) {
			$ordered          = (int) $item->get_quantity() + (int) $order->get_qty_refunded_for_item( $item_id );
			$shipped_qty      = isset( $shipped[ $item_id ] ) ? $shipped[ $item_id ] : 0;
			$rows[ $item_id ] = array(
				'name'      => $item->get_name(),
				'ordered'   => max( 0, $ordered ),
				'shipped'   => $shipped_qty,
				'remaining' => max( 0, $ordered - $shipped_qty ),
			);
		}
		return $rows;
	}

	/**
	 * Enqueue the vendor tracking assets.
	 *
	 * @since 1.3.0
	 * @param bool $vendor Whether the vendor form script is needed.
	 * @return void
	 */
	protected function enqueue_assets( $vendor = false ) {
		wp_enqueue_style(
			'wps-tofw-vendor-tracking',
			TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/css/wps-tofw-vendor-tracking.css',
			array(),
			$this->version
		);

		if ( ! $vendor ) {
			return;
		}

		wp_enqueue_script(
			'wps-tofw-vendor-tracking',
			TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/js/wps-tofw-vendor-tracking.js',
			array( 'jquery' ),
			$this->version,
			true
		);
		wp_localize_script(
			'wps-tofw-vendor-tracking',
			'wps_tofw_vendor_tracking',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( self::NONCE_ACTION ),
				'i18n'     => array(
					'confirm_delete' => __( 'Delete this shipment?', 'track-orders-for-woocommerce' ),
					'no_items'       => __( 'Select at least one item to ship.', 'track-orders-for-woocommerce' ),
					'no_tracking'    => __( 'Enter a tracking number.', 'track-orders-for-woocommerce' ),
					'error'          => __( 'Something went wrong. Please try again.', 'track-orders-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * Render the vendor tracking box on the vendor dashboard.
	 *
	 * @since 1.3.0
	 * @param Track_Orders_For_Woocommerce_Vendor_Integration $integration Integration.
	 * @param WC_Order                                        $order       Storage order.
	 * @param int                                             $vendor_id   Vendor id.
	 * @return void
	 */
	public function render_vendor_form( $integration, $order, $vendor_id ) {
		$this->enqueue_assets( true );
		?>
		<div class="wps-tofw-vt-box" data-marketplace="<?php echo esc_attr( $integration->get_key() ); ?>" data-order="<?php echo esc_attr( $order->get_id() ); ?>">
			<h3 class="wps-tofw-vt-title"><?php esc_html_e( 'Shipment Tracking', 'track-orders-for-woocommerce' ); ?></h3>
			<div class="wps-tofw-vt-notice" role="status" aria-live="polite"></div>
			<div class="wps-tofw-vt-inner">
				<?php echo $this->get_vendor_form_inner_html( $integration, $order, $vendor_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while building. ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Inner HTML of the vendor box: existing shipments and the add form.
	 *
	 * @since 1.3.0
	 * @param Track_Orders_For_Woocommerce_Vendor_Integration $integration Integration.
	 * @param WC_Order                                        $order       Storage order.
	 * @param int                                             $vendor_id   Vendor id.
	 * @return string
	 */
	public function get_vendor_form_inner_html( $integration, $order, $vendor_id ) {
		$items     = $this->get_vendor_item_quantities( $integration, $order, $vendor_id );
		$shipments = $this->get_vendor_shipments( $order, $vendor_id );
		$carriers  = $this->get_carriers();
		$remaining = array_sum( wp_list_pluck( $items, 'remaining' ) );

		ob_start();

		if ( ! empty( $shipments ) ) {
			echo $this->get_shipments_table_html( $order, $shipments, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while building.
		}

		if ( $remaining <= 0 ) {
			echo '<p class="wps-tofw-vt-all-shipped">' . esc_html__( 'All items of this order have been shipped.', 'track-orders-for-woocommerce' ) . '</p>';
			return ob_get_clean();
		}
		?>
		<div class="wps-tofw-vt-form">
			<h4><?php esc_html_e( 'Add shipment', 'track-orders-for-woocommerce' ); ?></h4>
			<p class="wps-tofw-vt-field">
				<label><?php esc_html_e( 'Carrier', 'track-orders-for-woocommerce' ); ?>
					<select class="wps-tofw-vt-carrier">
						<?php foreach ( $carriers as $key => $carrier ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $carrier['name'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</p>
			<p class="wps-tofw-vt-field wps-tofw-vt-custom" style="display:none;">
				<label><?php esc_html_e( 'Carrier name', 'track-orders-for-woocommerce' ); ?>
					<input type="text" class="wps-tofw-vt-carrier-name" />
				</label>
				<label><?php esc_html_e( 'Tracking URL', 'track-orders-for-woocommerce' ); ?>
					<input type="url" class="wps-tofw-vt-tracking-url" placeholder="https://" />
				</label>
			</p>
			<p class="wps-tofw-vt-field">
				<label><?php esc_html_e( 'Tracking number', 'track-orders-for-woocommerce' ); ?>
					<input type="text" class="wps-tofw-vt-tracking-number" />
				</label>
			</p>
			<p class="wps-tofw-vt-field">
				<label><?php esc_html_e( 'Shipped on', 'track-orders-for-woocommerce' ); ?>
					<input type="date" class="wps-tofw-vt-date" value="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>" />
				</label>
			</p>
			<table class="wps-tofw-vt-items">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Ship', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Item', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Remaining', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Qty', 'track-orders-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $items as $item_id => $row ) : ?>
						<?php
						if ( $row['remaining'] <= 0 ) {
							continue;
						}
						?>
						<tr data-item="<?php echo esc_attr( $item_id ); ?>">
							<td><input type="checkbox" class="wps-tofw-vt-item-check" checked="checked" /></td>
							<td><?php echo esc_html( $row['name'] ); ?></td>
							<td><?php echo esc_html( $row['remaining'] . ' / ' . $row['ordered'] ); ?></td>
							<td><input type="number" class="wps-tofw-vt-item-qty" min="1" max="<?php echo esc_attr( $row['remaining'] ); ?>" step="1" value="<?php echo esc_attr( $row['remaining'] ); ?>" /></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p>
				<button type="button" class="button wps-tofw-vt-save dokan-btn dokan-btn-theme"><?php esc_html_e( 'Save shipment', 'track-orders-for-woocommerce' ); ?></button>
			</p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shipments table.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order     Storage order.
	 * @param array[]  $shipments Shipments.
	 * @param bool     $editable  Whether to show delete buttons (vendor view).
	 * @return string
	 */
	public function get_shipments_table_html( $order, $shipments, $editable = false ) {
		ob_start();
		?>
		<table class="wps-tofw-vt-shipments">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Shipped on', 'track-orders-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Carrier', 'track-orders-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Tracking number', 'track-orders-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Items', 'track-orders-for-woocommerce' ); ?></th>
					<?php if ( $editable ) : ?>
						<th></th>
					<?php endif; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $shipments as $shipment ) : ?>
					<tr>
						<td><?php echo esc_html( ! empty( $shipment['date'] ) ? date_i18n( wc_date_format(), strtotime( $shipment['date'] ) ) : '' ); ?></td>
						<td><?php echo esc_html( $shipment['carrier_name'] ); ?></td>
						<td>
							<?php if ( ! empty( $shipment['tracking_url'] ) ) : ?>
								<a href="<?php echo esc_url( $shipment['tracking_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $shipment['tracking_number'] ); ?></a>
							<?php else : ?>
								<?php echo esc_html( $shipment['tracking_number'] ); ?>
							<?php endif; ?>
						</td>
						<td>
							<?php
							$lines = array();
							foreach ( (array) $shipment['items'] as $item_id => $qty ) {
								$item    = $order->get_item( $item_id );
								$lines[] = esc_html( ( $item ? $item->get_name() : '#' . $item_id ) . ' × ' . (int) $qty );
							}
							echo wp_kses_post( implode( '<br>', $lines ) );
							?>
						</td>
						<?php if ( $editable ) : ?>
							<td><button type="button" class="button wps-tofw-vt-delete" data-shipment="<?php echo esc_attr( $shipment['id'] ); ?>"><?php esc_html_e( 'Delete', 'track-orders-for-woocommerce' ); ?></button></td>
						<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Resolve the integration, order and vendor of an AJAX request, or die.
	 *
	 * @since 1.3.0
	 * @return array( integration, order, vendor_id ).
	 */
	protected function get_ajax_context() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		$key         = isset( $_POST['marketplace'] ) ? sanitize_key( wp_unslash( $_POST['marketplace'] ) ) : '';
		$order_id    = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		$integration = $this->get_integration( $key );
		$order       = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $integration || ! $order instanceof WC_Order ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order.', 'track-orders-for-woocommerce' ) ), 400 );
		}

		$vendor_id = $integration->get_current_vendor_id();
		if ( ! $integration->vendor_owns_order( $order, $vendor_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to update this order.', 'track-orders-for-woocommerce' ) ), 403 );
		}

		return array( $integration, $order, $vendor_id );
	}

	/**
	 * AJAX: save a vendor shipment.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function wps_tofw_save_vendor_shipment() {
		list( $integration, $order, $vendor_id ) = $this->get_ajax_context();

		// Nonce verified in get_ajax_context().
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$carriers        = $this->get_carriers();
		$carrier         = isset( $_POST['carrier'] ) ? sanitize_key( wp_unslash( $_POST['carrier'] ) ) : '';
		$tracking_number = isset( $_POST['tracking_number'] ) ? sanitize_text_field( wp_unslash( $_POST['tracking_number'] ) ) : '';
		$date            = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
		$custom_name     = isset( $_POST['carrier_name'] ) ? sanitize_text_field( wp_unslash( $_POST['carrier_name'] ) ) : '';
		$custom_url      = isset( $_POST['tracking_url'] ) ? esc_url_raw( wp_unslash( $_POST['tracking_url'] ) ) : '';
		$posted_items    = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? map_deep( wp_unslash( $_POST['items'] ), 'absint' ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( ! isset( $carriers[ $carrier ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Please choose a carrier.', 'track-orders-for-woocommerce' ) ), 400 );
		}
		if ( '' === $tracking_number ) {
			wp_send_json_error( array( 'message' => __( 'Enter a tracking number.', 'track-orders-for-woocommerce' ) ), 400 );
		}
		if ( 'other' === $carrier && '' === $custom_name ) {
			wp_send_json_error( array( 'message' => __( 'Enter the carrier name.', 'track-orders-for-woocommerce' ) ), 400 );
		}
		$date_obj = DateTime::createFromFormat( 'Y-m-d', $date );
		if ( ! $date_obj || $date_obj->format( 'Y-m-d' ) !== $date ) {
			$date = wp_date( 'Y-m-d' );
		}

		$quantities = $this->get_vendor_item_quantities( $integration, $order, $vendor_id );
		$items      = array();
		foreach ( $posted_items as $item_id => $qty ) {
			$item_id = absint( $item_id );
			$qty     = absint( $qty );
			if ( ! $qty ) {
				continue;
			}
			if ( ! isset( $quantities[ $item_id ] ) || $qty > $quantities[ $item_id ]['remaining'] ) {
				wp_send_json_error( array( 'message' => __( 'The shipped quantity exceeds the quantity left to ship.', 'track-orders-for-woocommerce' ) ), 400 );
			}
			$items[ $item_id ] = $qty;
		}
		if ( empty( $items ) ) {
			wp_send_json_error( array( 'message' => __( 'Select at least one item to ship.', 'track-orders-for-woocommerce' ) ), 400 );
		}

		if ( 'other' === $carrier ) {
			$carrier_name = $custom_name;
			$tracking_url = $custom_url;
		} else {
			$carrier_name = $carriers[ $carrier ]['name'];
			$tracking_url = ! empty( $carriers[ $carrier ]['url'] ) ? esc_url_raw( sprintf( $carriers[ $carrier ]['url'], rawurlencode( $tracking_number ) ) ) : '';
		}

		$shipment = array(
			'id'              => wp_generate_uuid4(),
			'carrier'         => $carrier,
			'carrier_name'    => $carrier_name,
			'tracking_number' => $tracking_number,
			'tracking_url'    => $tracking_url,
			'date'            => $date,
			'items'           => $items,
			'created'         => time(),
		);

		/**
		 * Filter a vendor shipment before it is saved.
		 *
		 * @since 1.3.0
		 * @param array    $shipment    Shipment.
		 * @param WC_Order $order       Storage order.
		 * @param int      $vendor_id   Vendor id.
		 * @param string   $marketplace Marketplace key.
		 */
		$shipment = apply_filters( 'wps_tofw_vendor_shipment_data', $shipment, $order, $vendor_id, $integration->get_key() );

		$shipments                    = $this->get_vendor_shipments( $order, $vendor_id );
		$shipments[ $shipment['id'] ] = $shipment;
		$this->set_vendor_shipments( $order, $vendor_id, $shipments );
		$this->mirror_legacy_meta( $integration, $order, $vendor_id );
		$order->save();

		$this->add_shipment_note( $integration, $order, $vendor_id, $shipment );

		/**
		 * Fires after a vendor shipment is saved.
		 *
		 * @since 1.3.0
		 * @param array    $shipment    Shipment.
		 * @param WC_Order $order       Storage order.
		 * @param int      $vendor_id   Vendor id.
		 * @param string   $marketplace Marketplace key.
		 */
		do_action( 'wps_tofw_vendor_shipment_saved', $shipment, $order, $vendor_id, $integration->get_key() );

		wp_send_json_success(
			array(
				'message' => __( 'Shipment saved.', 'track-orders-for-woocommerce' ),
				'html'    => $this->get_vendor_form_inner_html( $integration, $order, $vendor_id ),
			)
		);
	}

	/**
	 * AJAX: delete a vendor shipment.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function wps_tofw_delete_vendor_shipment() {
		list( $integration, $order, $vendor_id ) = $this->get_ajax_context();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in get_ajax_context().
		$shipment_id = isset( $_POST['shipment_id'] ) ? sanitize_text_field( wp_unslash( $_POST['shipment_id'] ) ) : '';
		$shipments   = $this->get_vendor_shipments( $order, $vendor_id );

		if ( '' === $shipment_id || ! isset( $shipments[ $shipment_id ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Shipment not found.', 'track-orders-for-woocommerce' ) ), 404 );
		}

		$shipment = $shipments[ $shipment_id ];
		unset( $shipments[ $shipment_id ] );
		$this->set_vendor_shipments( $order, $vendor_id, $shipments );
		$this->mirror_legacy_meta( $integration, $order, $vendor_id );
		$order->save();

		/**
		 * Fires after a vendor shipment is deleted.
		 *
		 * @since 1.3.0
		 * @param array    $shipment    Deleted shipment.
		 * @param WC_Order $order       Storage order.
		 * @param int      $vendor_id   Vendor id.
		 * @param string   $marketplace Marketplace key.
		 */
		do_action( 'wps_tofw_vendor_shipment_deleted', $shipment, $order, $vendor_id, $integration->get_key() );

		wp_send_json_success(
			array(
				'message' => __( 'Shipment deleted.', 'track-orders-for-woocommerce' ),
				'html'    => $this->get_vendor_form_inner_html( $integration, $order, $vendor_id ),
			)
		);
	}

	/**
	 * Carrier keys the base `wps_tofw_selected_shipping_service` meta
	 * understands (see template/wps-order-tracking-page.php and the public
	 * tracking info box).
	 *
	 * @since 1.3.0
	 * @return string[]
	 */
	protected function get_legacy_carrier_keys() {
		/**
		 * Carrier keys that may be mirrored to `wps_tofw_selected_shipping_service`.
		 *
		 * @since 1.3.0
		 * @param string[] $keys Carrier keys.
		 */
		return (array) apply_filters( 'wps_tofw_vendor_legacy_carrier_keys', array( 'fedex', 'usps', 'canada_post' ) );
	}

	/**
	 * Mirror the latest shipment to the single-shipment tracking meta:
	 * - Dokan: the sub-order belongs to one vendor only.
	 * - WCFM: the parent order, when it holds a single vendor shipment and has
	 *   no tracking of its own (or only tracking mirrored earlier).
	 * Only carriers the base plugin supports are mirrored.
	 *
	 * @since 1.3.0
	 * @param Track_Orders_For_Woocommerce_Vendor_Integration $integration Integration.
	 * @param WC_Order                                        $order       Storage order.
	 * @param int                                             $vendor_id   Vendor id.
	 * @return void
	 */
	protected function mirror_legacy_meta( $integration, $order, $vendor_id ) {
		$key = $integration->get_key();
		if ( 'dokan' === $key ) {
			$shipments = $this->get_vendor_shipments( $order, $vendor_id );
		} elseif ( 'wcfm' === $key ) {
			$shipments = array();
			foreach ( $this->get_all_shipments( $order ) as $vendor_shipments ) {
				$shipments = array_merge( $shipments, array_values( (array) $vendor_shipments ) );
			}
			$mirrored = (bool) $order->get_meta( self::MIRRORED_META_KEY, true );
			if ( ! $mirrored && '' !== (string) $order->get_meta( 'wps_tofw_package_tracking_number', true ) ) {
				// The order has its own (admin) tracking, keep it.
				return;
			}
			if ( count( $shipments ) > 1 ) {
				$shipments = array();
			}
		} else {
			return;
		}

		$latest = end( $shipments );
		if ( $latest && in_array( $latest['carrier'], $this->get_legacy_carrier_keys(), true ) ) {
			$order->update_meta_data( 'wps_tofw_selected_shipping_service', $latest['carrier'] );
			$order->update_meta_data( 'wps_tofw_package_tracking_number', $latest['tracking_number'] );
			$order->update_meta_data( self::MIRRORED_META_KEY, 'yes' );
		} elseif ( $order->get_meta( self::MIRRORED_META_KEY, true ) ) {
			$order->update_meta_data( 'wps_tofw_selected_shipping_service', '' );
			$order->update_meta_data( 'wps_tofw_package_tracking_number', '' );
			$order->delete_meta_data( self::MIRRORED_META_KEY );
		}
	}

	/**
	 * Stop mirroring vendor tracking once an admin enters their own tracking
	 * number in the order tracking meta box, so later vendor saves never
	 * overwrite or clear it.
	 *
	 * @since 1.3.0
	 * @param int      $order_id Order id.
	 * @param WC_Order $order    Order (unused, may be a post object).
	 * @return void
	 */
	public function wps_tofw_release_mirrored_tracking( $order_id, $order = null ) {
		$nonce = isset( $_POST['wps_tofw_selected_shipping_services_nonce_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_selected_shipping_services_nonce_name'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wps_tofw_selected_shipping_services_nonce' ) || ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}
		$tracking = isset( $_POST['wps_tofw_tracking_number'] ) ? sanitize_text_field( wp_unslash( $_POST['wps_tofw_tracking_number'] ) ) : '';
		if ( '' === $tracking ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->get_meta( self::MIRRORED_META_KEY, true ) ) {
			return;
		}
		foreach ( $this->get_all_shipments( $order ) as $vendor_shipments ) {
			foreach ( (array) $vendor_shipments as $shipment ) {
				if ( isset( $shipment['tracking_number'] ) && (string) $shipment['tracking_number'] === $tracking ) {
					return; // Still the mirrored vendor tracking.
				}
			}
		}
		$order->delete_meta_data( self::MIRRORED_META_KEY );
		$order->save();
	}

	/**
	 * Add an order note for a new shipment (a customer note by default, which
	 * also emails the customer).
	 *
	 * @since 1.3.0
	 * @param Track_Orders_For_Woocommerce_Vendor_Integration $integration Integration.
	 * @param WC_Order                                        $order       Storage order.
	 * @param int                                             $vendor_id   Vendor id.
	 * @param array                                           $shipment    Shipment.
	 * @return void
	 */
	protected function add_shipment_note( $integration, $order, $vendor_id, $shipment ) {
		$note = sprintf(
			/* translators: 1: store name, 2: carrier name, 3: tracking number. */
			__( 'Items from %1$s have been shipped with %2$s. Tracking number: %3$s', 'track-orders-for-woocommerce' ),
			$integration->get_store_name( $vendor_id ),
			$shipment['carrier_name'],
			$shipment['tracking_number']
		);
		if ( ! empty( $shipment['tracking_url'] ) ) {
			$note .= ' ' . esc_url( $shipment['tracking_url'] );
		}

		/**
		 * Whether the shipment note is sent to the customer.
		 *
		 * @since 1.3.0
		 * @param bool     $notify    Notify the customer.
		 * @param WC_Order $order     Storage order.
		 * @param int      $vendor_id Vendor id.
		 * @param array    $shipment  Shipment.
		 */
		$notify = apply_filters( 'wps_tofw_vendor_shipment_notify_customer', true, $order, $vendor_id, $shipment );
		$order->add_order_note( $note, $notify ? 1 : 0 );
	}

	/**
	 * Per-vendor shipment groups of an order, for the customer.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order the customer is looking at.
	 * @return array[] Each: vendor_id, store_name, order, shipments, items.
	 */
	public function get_customer_vendor_groups( $order ) {
		$groups = array();
		foreach ( $this->integrations as $integration ) {
			foreach ( $integration->get_customer_groups( $order ) as $group ) {
				$storage   = $group['order'];
				$vendor_id = (int) $group['vendor_id'];
				$groups[]  = array(
					'vendor_id'  => $vendor_id,
					'store_name' => $integration->get_store_name( $vendor_id ),
					'order'      => $storage,
					'shipments'  => $this->get_vendor_shipments( $storage, $vendor_id ),
					'items'      => $this->get_vendor_item_quantities( $integration, $storage, $vendor_id ),
				);
			}
		}

		/**
		 * Per-vendor shipment groups shown to the customer.
		 *
		 * @since 1.3.0
		 * @param array[]  $groups Groups.
		 * @param WC_Order $order  Order.
		 */
		return apply_filters( 'wps_tofw_customer_vendor_shipment_groups', $groups, $order );
	}

	/**
	 * Show the per-vendor shipments to the customer.
	 *
	 * Hooked to `woocommerce_order_details_after_order_table` (My Account view
	 * order / thank you page) and `wps_tofw_after_tracking_order_details`
	 * (plugin tracking page templates).
	 *
	 * @since 1.3.0
	 * @param WC_Order|int $order Order or order id.
	 * @return void
	 */
	public function wps_tofw_render_customer_vendor_shipments( $order ) {
		static $rendered = array();

		if ( empty( $this->integrations ) ) {
			return;
		}
		if ( ! $order instanceof WC_Order ) {
			$order = is_numeric( $order ) ? wc_get_order( absint( $order ) ) : false;
		}
		if ( ! $order instanceof WC_Order || isset( $rendered[ $order->get_id() ] ) ) {
			return;
		}

		$groups = $this->get_customer_vendor_groups( $order );
		if ( empty( $groups ) ) {
			return;
		}
		$rendered[ $order->get_id() ] = true;

		$this->enqueue_assets();
		?>
		<section class="wps-tofw-vt-customer">
			<h2><?php esc_html_e( 'Shipments by seller', 'track-orders-for-woocommerce' ); ?></h2>
			<?php foreach ( $groups as $group ) : ?>
				<div class="wps-tofw-vt-group">
					<h3 class="wps-tofw-vt-store"><?php echo esc_html( $group['store_name'] ); ?></h3>
					<?php if ( ! empty( $group['shipments'] ) ) : ?>
						<?php echo $this->get_shipments_table_html( $group['order'], $group['shipments'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped while building. ?>
					<?php endif; ?>
					<?php
					$pending = array();
					foreach ( $group['items'] as $row ) {
						if ( $row['remaining'] > 0 ) {
							$pending[] = $row['name'] . ' × ' . $row['remaining'];
						}
					}
					?>
					<?php if ( ! empty( $pending ) ) : ?>
						<p class="wps-tofw-vt-pending">
							<strong><?php esc_html_e( 'Awaiting shipment:', 'track-orders-for-woocommerce' ); ?></strong>
							<?php echo esc_html( implode( ', ', $pending ) ); ?>
						</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</section>
		<?php
	}
}
