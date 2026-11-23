<?php
/**
 * Delivery exception / late shipment alerts and shipping analytics.
 *
 * @link  https://wpswings.com/
 * @since 1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shipment insights: delivery alerts, analytics data and lightweight counters.
 *
 * Shipment status is derived from the plugin's own order tracking meta
 * (`wps_track_order_status`, `wps_track_order_onchange_time`, tracking number,
 * estimated delivery date) and, when present, the normalized carrier status
 * stored in the `wps_tofw_carrier_status` order meta by the carrier sync feature.
 *
 * @since 1.3.0
 */
class Track_Orders_For_Woocommerce_Shipment_Insights {

	/**
	 * Normalized carrier status meta written by the carrier sync feature.
	 *
	 * @since 1.3.0
	 */
	const META_CARRIER_STATUS = 'wps_tofw_carrier_status';

	/**
	 * Timestamp (UTC) when the order was first marked as shipped.
	 *
	 * @since 1.3.0
	 */
	const META_SHIPPED_AT = 'wps_tofw_shipped_at';

	/**
	 * Timestamp (UTC) when the order was first marked as delivered.
	 *
	 * @since 1.3.0
	 */
	const META_DELIVERED_AT = 'wps_tofw_delivered_at';

	/**
	 * Alert fingerprints already sent for an order (dedupe).
	 *
	 * @since 1.3.0
	 */
	const META_ALERTS_SENT = 'wps_tofw_delivery_alerts_sent';

	/**
	 * Option holding the currently active alerts (order_id => data).
	 *
	 * @since 1.3.0
	 */
	const OPTION_ACTIVE_ALERTS = 'wps_tofw_delivery_active_alerts';

	/**
	 * Option holding alerts collected during a multi-batch run.
	 *
	 * @since 1.3.0
	 */
	const OPTION_PENDING_DIGEST = 'wps_tofw_delivery_alerts_pending_digest';

	/**
	 * Daily stats table schema version.
	 *
	 * @since 1.3.0
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Option name storing the installed schema version.
	 *
	 * @since 1.3.0
	 */
	const DB_VERSION_OPTION = 'wps_tofw_insights_db_version';

	/**
	 * Action Scheduler hook: daily check.
	 *
	 * @since 1.3.0
	 */
	const DAILY_HOOK = 'wps_tofw_delivery_alerts_daily_check';

	/**
	 * Action Scheduler hook: batch processing.
	 *
	 * @since 1.3.0
	 */
	const BATCH_HOOK = 'wps_tofw_delivery_alerts_process_batch';

	/**
	 * Action Scheduler group.
	 *
	 * @since 1.3.0
	 */
	const AS_GROUP = 'wps-tofw-delivery-alerts';

	/**
	 * Orders processed per alert batch.
	 *
	 * @since 1.3.0
	 */
	const BATCH_SIZE = 100;

	/**
	 * Settings tab slug for alerts.
	 *
	 * @since 1.3.0
	 */
	const TAB_ALERTS = 'track-orders-for-woocommerce-delivery-alerts';

	/**
	 * Settings tab slug for analytics.
	 *
	 * @since 1.3.0
	 */
	const TAB_ANALYTICS = 'track-orders-for-woocommerce-shipping-analytics';

	/**
	 * Plugin name.
	 *
	 * @since 1.3.0
	 * @var   string
	 */
	protected $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @since 1.3.0
	 * @var   string
	 */
	protected $version;

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 * @param string $plugin_name Plugin name.
	 * @param string $version     Plugin version.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/*
	|--------------------------------------------------------------------------
	| Settings.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Whether alerts are enabled.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function wps_tofw_alerts_enabled() {
		return 'on' === get_option( 'wps_tofw_delivery_alerts_enable', '' );
	}

	/**
	 * Expected delivery days after shipping.
	 *
	 * @since 1.3.0
	 * @return int
	 */
	public function wps_tofw_get_expected_days() {
		$days = absint( get_option( 'wps_tofw_delivery_alerts_expected_days', 7 ) );
		return $days > 0 ? $days : 7;
	}

	/**
	 * Enabled alert conditions.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function wps_tofw_get_alert_conditions() {
		$conditions = get_option( 'wps_tofw_delivery_alerts_conditions', array( 'exception', 'expected_days', 'estimated_date' ) );
		if ( ! is_array( $conditions ) ) {
			$conditions = array();
		}
		return array_values( array_intersect( array_keys( $this->wps_tofw_condition_labels() ), $conditions ) );
	}

	/**
	 * Alert condition labels.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function wps_tofw_condition_labels() {
		return array(
			'exception'      => __( 'Shipment is in Exception', 'track-orders-for-woocommerce' ),
			'expected_days'  => __( 'Shipped longer ago than the expected delivery days', 'track-orders-for-woocommerce' ),
			'estimated_date' => __( 'Estimated delivery date has passed', 'track-orders-for-woocommerce' ),
		);
	}

	/**
	 * Alert recipients.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function wps_tofw_get_recipients() {
		$raw        = (string) get_option( 'wps_tofw_delivery_alerts_recipients', '' );
		$recipients = array();
		foreach ( preg_split( '/[\s,;]+/', $raw ) as $email ) {
			$email = sanitize_email( $email );
			if ( $email && is_email( $email ) ) {
				$recipients[] = $email;
			}
		}
		if ( empty( $recipients ) ) {
			$recipients[] = get_option( 'admin_email' );
		}
		/**
		 * Filter delivery alert email recipients.
		 *
		 * @since 1.3.0
		 * @param array $recipients Email addresses.
		 */
		return (array) apply_filters( 'wps_tofw_delivery_alert_recipients', array_unique( $recipients ) );
	}

	/**
	 * Order statuses that mean "shipped" (with wc- prefix).
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function wps_tofw_get_shipped_statuses() {
		$statuses = get_option( 'wps_tofw_order_status_in_shipping', array() );
		$statuses = is_array( $statuses ) ? $statuses : array();
		/**
		 * Filter the order statuses treated as "shipped".
		 *
		 * @since 1.3.0
		 * @param array $statuses Order statuses with the wc- prefix.
		 */
		return (array) apply_filters( 'wps_tofw_shipped_order_statuses', $statuses );
	}

	/**
	 * Order statuses that mean "exception" (with wc- prefix).
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function wps_tofw_get_exception_statuses() {
		$statuses = get_option( 'wps_tofw_delivery_alerts_exception_statuses', array() );
		$statuses = is_array( $statuses ) ? $statuses : array();
		/**
		 * Filter the order statuses treated as a delivery exception.
		 *
		 * @since 1.3.0
		 * @param array $statuses Order statuses with the wc- prefix.
		 */
		return (array) apply_filters( 'wps_tofw_exception_order_statuses', $statuses );
	}

	/**
	 * Register the two new tabs (alerts settings + analytics).
	 *
	 * @since 1.3.0
	 * @param array $tabs Tabs.
	 * @return array
	 */
	public function wps_tofw_add_settings_tabs( $tabs ) {
		$tabs[ self::TAB_ALERTS ]    = array(
			'title'     => esc_html__( 'Delivery Alerts', 'track-orders-for-woocommerce' ),
			'name'      => self::TAB_ALERTS,
			'file_path' => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-delivery-alerts.php',
		);
		$tabs[ self::TAB_ANALYTICS ] = array(
			'title'     => esc_html__( 'Shipping Analytics', 'track-orders-for-woocommerce' ),
			'name'      => self::TAB_ANALYTICS,
			'file_path' => TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/partials/track-orders-for-woocommerce-shipping-analytics.php',
		);
		return $tabs;
	}

	/**
	 * Settings fields for the Delivery Alerts tab.
	 *
	 * @since 1.3.0
	 * @param array $settings Settings.
	 * @return array
	 */
	public function wps_tofw_alert_settings_fields( $settings = array() ) {
		$order_statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		$conditions     = get_option( 'wps_tofw_delivery_alerts_conditions', array( 'exception', 'expected_days', 'estimated_date' ) );

		$settings = array(
			array(
				'title'       => __( 'Enable Delivery Alerts', 'track-orders-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Check shipments daily and alert the store admin about delivery exceptions and late shipments.', 'track-orders-for-woocommerce' ),
				'id'          => 'wps_tofw_delivery_alerts_enable',
				'value'       => get_option( 'wps_tofw_delivery_alerts_enable' ),
				'class'       => 'wps_tofw_delivery_alerts_enable',
				'options'     => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no'  => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( 'Alert Recipients', 'track-orders-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Comma separated email addresses. Leave empty to use the site admin email.', 'track-orders-for-woocommerce' ),
				'id'          => 'wps_tofw_delivery_alerts_recipients',
				'value'       => get_option( 'wps_tofw_delivery_alerts_recipients', '' ),
				'class'       => 'wps_tofw_delivery_alerts_recipients',
				'placeholder' => get_option( 'admin_email' ),
			),
			array(
				'title'       => __( 'Expected Delivery Days', 'track-orders-for-woocommerce' ),
				'type'        => 'number',
				'description' => __( 'A shipment is late when it is not delivered this many days after it was shipped (tracking added or shipping status reached).', 'track-orders-for-woocommerce' ),
				'id'          => 'wps_tofw_delivery_alerts_expected_days',
				'value'       => $this->wps_tofw_get_expected_days(),
				'class'       => 'wps_tofw_delivery_alerts_expected_days',
			),
			array(
				'title'       => __( 'Alert Conditions', 'track-orders-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Choose which conditions trigger an alert.', 'track-orders-for-woocommerce' ),
				'id'          => 'wps_tofw_delivery_alerts_conditions',
				'value'       => is_array( $conditions ) ? $conditions : array(),
				'class'       => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => '',
				'options'     => $this->wps_tofw_condition_labels(),
			),
			array(
				'title'       => __( 'Exception Order Statuses', 'track-orders-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Order statuses (e.g. a custom "Delivery Failed" status) that mean the shipment is in Exception. The carrier statuses "exception", "failed attempt" and "expired" are always treated as Exception.', 'track-orders-for-woocommerce' ),
				'id'          => 'wps_tofw_delivery_alerts_exception_statuses',
				'value'       => $this->wps_tofw_get_exception_statuses(),
				'class'       => 'tofw-multiselect-class wps-defaut-multiselect',
				'placeholder' => '',
				'options'     => $order_statuses,
			),
			array(
				'title'       => __( 'Show Dashboard Notice & Widget', 'track-orders-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Show an admin notice and a WordPress dashboard widget listing shipments that need attention.', 'track-orders-for-woocommerce' ),
				'id'          => 'wps_tofw_delivery_alerts_dashboard_notice',
				'value'       => get_option( 'wps_tofw_delivery_alerts_dashboard_notice', 'on' ),
				'class'       => 'wps_tofw_delivery_alerts_dashboard_notice',
				'options'     => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no'  => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'type'        => 'button',
				'id'          => 'wps_tofw_delivery_alerts_settings_save',
				'main-class'  => 'wps_tofw_main_class',
				'button_text' => __( 'Save Settings', 'track-orders-for-woocommerce' ),
				'class'       => 'wps_tofw_delivery_alerts_settings_save',
				'name'        => 'wps_tofw_delivery_alerts_settings_save',
			),
		);

		/**
		 * Filter the delivery alert settings fields.
		 *
		 * @since 1.3.0
		 * @param array $settings Settings fields.
		 */
		return apply_filters( 'wps_tofw_delivery_alerts_settings_fields', $settings );
	}

	/*
	|--------------------------------------------------------------------------
	| Shipment status helpers.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Shipment status labels.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function wps_tofw_shipment_status_labels() {
		return array(
			'not_shipped'      => __( 'Not shipped', 'track-orders-for-woocommerce' ),
			'in_transit'       => __( 'In transit', 'track-orders-for-woocommerce' ),
			'out_for_delivery' => __( 'Out for delivery', 'track-orders-for-woocommerce' ),
			'delivered'        => __( 'Delivered', 'track-orders-for-woocommerce' ),
			'exception'        => __( 'Exception', 'track-orders-for-woocommerce' ),
			'cancelled'        => __( 'Cancelled / Refunded', 'track-orders-for-woocommerce' ),
		);
	}

	/**
	 * Get the normalized shipment status of an order.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return string One of the keys of wps_tofw_shipment_status_labels().
	 */
	public function wps_tofw_get_shipment_status( $order ) {
		$order_status = $order->get_status();
		$wc_status    = 'wc-' . $order_status;
		$carrier      = $this->wps_tofw_get_carrier_status( $order );

		if ( in_array( $order_status, array( 'cancelled', 'refunded', 'failed' ), true ) && ! in_array( $wc_status, $this->wps_tofw_get_exception_statuses(), true ) ) {
			$status = 'cancelled';
		} elseif ( in_array( $wc_status, $this->wps_tofw_get_exception_statuses(), true ) ) {
			$status = 'exception';
		} elseif ( '' !== $carrier && 'not_shipped' !== $carrier ) {
			$status = $carrier;
		} elseif ( 'completed' === $order_status ) {
			$status = 'delivered';
		} elseif ( 'not_shipped' === $carrier ) {
			// Carrier only has the label / no scan yet.
			$status = 'not_shipped';
		} elseif ( in_array( $wc_status, $this->wps_tofw_get_shipped_statuses(), true ) || $this->wps_tofw_get_shipped_timestamp( $order ) || '' !== (string) $order->get_meta( 'wps_tofw_package_tracking_number' ) ) {
			$status = 'in_transit';
		} else {
			$status = 'not_shipped';
		}

		/**
		 * Filter the normalized shipment status for an order.
		 *
		 * Carrier integrations can use this to feed their own status.
		 *
		 * @since 1.3.0
		 * @param string   $status Normalized status (not_shipped, in_transit, out_for_delivery, delivered, exception, cancelled).
		 * @param WC_Order $order  Order.
		 */
		$status = (string) apply_filters( 'wps_tofw_shipment_status', $status, $order );

		return array_key_exists( $status, $this->wps_tofw_shipment_status_labels() ) ? $status : 'not_shipped';
	}

	/**
	 * Read the carrier status meta (from carrier sync), mapped to a shipment status.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return string Shipment status (in_transit, out_for_delivery, delivered, exception, not_shipped) or '' when unknown.
	 */
	public function wps_tofw_get_carrier_status( $order ) {
		return $this->wps_tofw_map_carrier_status( (string) $order->get_meta( self::META_CARRIER_STATUS ) );
	}

	/**
	 * Map a carrier sync status to a shipment status.
	 *
	 * @since 1.3.0
	 * @param string $carrier Carrier sync status (e.g. failed_attempt, available_for_pickup).
	 * @return string Shipment status or '' when unknown.
	 */
	public function wps_tofw_map_carrier_status( $carrier ) {
		$carrier = str_replace( array( '-', ' ' ), '_', strtolower( trim( (string) $carrier ) ) );
		$map     = array(
			'in_transit'           => 'in_transit',
			'out_for_delivery'     => 'out_for_delivery',
			'available_for_pickup' => 'out_for_delivery',
			'delivered'            => 'delivered',
			'exception'            => 'exception',
			'failed_attempt'       => 'exception',
			'expired'              => 'exception',
			'info_received'        => 'not_shipped',
			'pending'              => 'not_shipped',
		);
		/**
		 * Filter the carrier status => shipment status map.
		 *
		 * @since 1.3.0
		 * @param array $map Map.
		 */
		$map = (array) apply_filters( 'wps_tofw_carrier_status_map', $map );
		return isset( $map[ $carrier ] ) ? (string) $map[ $carrier ] : '';
	}

	/**
	 * Parse a time stored in `wps_track_order_onchange_time` ("d F, Y H:i", site timezone).
	 *
	 * @since 1.3.0
	 * @param string $value Value.
	 * @return int Timestamp or 0.
	 */
	protected function wps_tofw_parse_change_time( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return 0;
		}
		$date = DateTime::createFromFormat( 'd F, Y H:i', $value, wp_timezone() );
		if ( $date ) {
			return $date->getTimestamp();
		}
		$ts = strtotime( str_replace( ',', '', $value ) );
		return $ts ? (int) $ts : 0;
	}

	/**
	 * Timestamp the order was shipped (tracking added / shipping status reached).
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return int Timestamp or 0.
	 */
	public function wps_tofw_get_shipped_timestamp( $order ) {
		$ts = absint( $order->get_meta( self::META_SHIPPED_AT ) );

		if ( ! $ts ) {
			$times    = $order->get_meta( 'wps_track_order_onchange_time' );
			$shipping = $this->wps_tofw_get_shipped_statuses();
			if ( is_array( $times ) && ! empty( $shipping ) ) {
				foreach ( $times as $status => $when ) {
					if ( in_array( $status, $shipping, true ) ) {
						$parsed = $this->wps_tofw_parse_change_time( $when );
						if ( $parsed && ( ! $ts || $parsed < $ts ) ) {
							$ts = $parsed;
						}
					}
				}
			}
		}

		/**
		 * Filter the shipped timestamp of an order.
		 *
		 * @since 1.3.0
		 * @param int      $ts    Unix timestamp, 0 when unknown.
		 * @param WC_Order $order Order.
		 */
		return absint( apply_filters( 'wps_tofw_shipment_shipped_timestamp', $ts, $order ) );
	}

	/**
	 * Timestamp the order was delivered.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return int Timestamp or 0.
	 */
	public function wps_tofw_get_delivered_timestamp( $order ) {
		$ts = absint( $order->get_meta( self::META_DELIVERED_AT ) );
		if ( ! $ts && ( 'completed' === $order->get_status() || 'delivered' === $this->wps_tofw_get_carrier_status( $order ) ) ) {
			$completed = $order->get_date_completed();
			$ts        = $completed ? $completed->getTimestamp() : 0;
		}
		/**
		 * Filter the delivered timestamp of an order.
		 *
		 * @since 1.3.0
		 * @param int      $ts    Unix timestamp, 0 when unknown.
		 * @param WC_Order $order Order.
		 */
		return absint( apply_filters( 'wps_tofw_shipment_delivered_timestamp', $ts, $order ) );
	}

	/**
	 * Estimated delivery timestamp from the order meta, if set.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return int Timestamp or 0.
	 */
	public function wps_tofw_get_estimated_timestamp( $order ) {
		$date = trim( (string) $order->get_meta( 'wps_tofw_estimated_delivery_date' ) );
		$time = trim( (string) $order->get_meta( 'wps_tofw_estimated_delivery_time' ) );
		if ( '' === $date && class_exists( 'Track_Orders_For_Woocommerce_Estimated_Delivery' ) && method_exists( 'Track_Orders_For_Woocommerce_Estimated_Delivery', 'get_order_edd' ) ) {
			// Computed estimate (latest date of the range) from the estimated delivery date feature.
			$edd  = Track_Orders_For_Woocommerce_Estimated_Delivery::get_order_edd( $order );
			$date = ( is_array( $edd ) && ! empty( $edd['to'] ) ) ? (string) $edd['to'] : '';
			$time = '';
		}
		if ( '' === $date ) {
			return 0;
		}
		try {
			$dt = new DateTime( $date . ' ' . ( '' !== $time ? $time : '23:59' ), wp_timezone() );
			return $dt->getTimestamp();
		} catch ( Exception $e ) {
			return 0;
		}
	}

	/**
	 * Human carrier label for an order.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return string
	 */
	public function wps_tofw_get_carrier_label( $order ) {
		$carrier = (string) $order->get_meta( 'wps_tofw_selected_shipping_service' );
		$label   = '' !== $carrier ? ucwords( str_replace( array( '_', '-' ), ' ', $carrier ) ) : __( 'Unspecified', 'track-orders-for-woocommerce' );
		/**
		 * Filter the carrier label used in analytics.
		 *
		 * @since 1.3.0
		 * @param string   $label Label.
		 * @param WC_Order $order Order.
		 */
		return (string) apply_filters( 'wps_tofw_shipment_carrier_label', $label, $order );
	}

	/**
	 * Evaluate the delivery issues of an order.
	 *
	 * @since 1.3.0
	 * @param WC_Order   $order      Order.
	 * @param array|null $conditions Conditions to check (null = configured ones).
	 * @return array condition => array( 'fingerprint' => string, 'label' => string, 'deadline' => int ).
	 */
	public function wps_tofw_evaluate_order( $order, $conditions = null ) {
		$conditions = null === $conditions ? $this->wps_tofw_get_alert_conditions() : $conditions;
		$status     = $this->wps_tofw_get_shipment_status( $order );
		$issues     = array();
		$now        = time();

		if ( in_array( $status, array( 'delivered', 'cancelled' ), true ) ) {
			return $issues;
		}

		if ( in_array( 'exception', $conditions, true ) && 'exception' === $status ) {
			$issues['exception'] = array(
				'fingerprint' => 'exception',
				'label'       => __( 'Delivery exception', 'track-orders-for-woocommerce' ),
				'deadline'    => 0,
			);
		}

		if ( in_array( 'expected_days', $conditions, true ) ) {
			$shipped = $this->wps_tofw_get_shipped_timestamp( $order );
			if ( $shipped ) {
				$deadline = $shipped + ( $this->wps_tofw_get_expected_days() * DAY_IN_SECONDS );
				if ( $deadline < $now ) {
					$issues['expected_days'] = array(
						'fingerprint' => 'expected_days:' . $deadline,
						/* translators: %d: number of days. */
						'label'       => sprintf( __( 'Not delivered %d days after shipping', 'track-orders-for-woocommerce' ), $this->wps_tofw_get_expected_days() ),
						'deadline'    => $deadline,
					);
				}
			}
		}

		if ( in_array( 'estimated_date', $conditions, true ) ) {
			$eta = $this->wps_tofw_get_estimated_timestamp( $order );
			if ( $eta && $eta < $now ) {
				$issues['estimated_date'] = array(
					'fingerprint' => 'estimated_date:' . $eta,
					'label'       => __( 'Estimated delivery date passed', 'track-orders-for-woocommerce' ),
					'deadline'    => $eta,
				);
			}
		}

		/**
		 * Filter the detected delivery issues for an order.
		 *
		 * @since 1.3.0
		 * @param array    $issues     Issues keyed by condition.
		 * @param WC_Order $order      Order.
		 * @param array    $conditions Conditions checked.
		 */
		return (array) apply_filters( 'wps_tofw_delivery_issues', $issues, $order, $conditions );
	}

	/*
	|--------------------------------------------------------------------------
	| Shipped / delivered timestamp recording.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Record shipped / delivered timestamps on order status change.
	 *
	 * @since 1.3.0
	 * @param int      $order_id   Order id.
	 * @param string   $old_status Old status.
	 * @param string   $new_status New status.
	 * @param WC_Order $order      Order.
	 */
	public function wps_tofw_record_status_timestamps( $order_id, $old_status, $new_status, $order = null ) {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$changed = false;
		if ( in_array( 'wc-' . $new_status, $this->wps_tofw_get_shipped_statuses(), true ) && ! $order->get_meta( self::META_SHIPPED_AT ) ) {
			$order->update_meta_data( self::META_SHIPPED_AT, time() );
			$changed = true;
		}
		if ( 'completed' === $new_status && ! $order->get_meta( self::META_DELIVERED_AT ) ) {
			$order->update_meta_data( self::META_DELIVERED_AT, time() );
			$changed = true;
		}
		if ( $changed ) {
			$order->save_meta_data();
		}
	}

	/**
	 * Record the shipped timestamp when a tracking number is saved from the order screen.
	 *
	 * @since 1.3.0
	 * @param int $order_id Order id.
	 */
	public function wps_tofw_record_tracking_added( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		if ( '' !== (string) $order->get_meta( 'wps_tofw_package_tracking_number' ) && ! $order->get_meta( self::META_SHIPPED_AT ) ) {
			$order->update_meta_data( self::META_SHIPPED_AT, time() );
			$order->save_meta_data();
		}
	}

	/**
	 * Record timestamps when the carrier sync feature updates the carrier status.
	 *
	 * Carrier integrations should fire:
	 * do_action( 'wps_tofw_carrier_status_updated', $order_id, $status ).
	 *
	 * @since 1.3.0
	 * @param int    $order_id Order id.
	 * @param string $status   Normalized carrier status.
	 */
	public function wps_tofw_record_carrier_status( $order_id, $status = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$status  = $this->wps_tofw_map_carrier_status( $status );
		$changed = false;
		if ( in_array( $status, array( 'in_transit', 'out_for_delivery', 'delivered', 'exception' ), true ) && ! $order->get_meta( self::META_SHIPPED_AT ) ) {
			$order->update_meta_data( self::META_SHIPPED_AT, time() );
			$changed = true;
		}
		if ( 'delivered' === $status && ! $order->get_meta( self::META_DELIVERED_AT ) ) {
			$order->update_meta_data( self::META_DELIVERED_AT, time() );
			$changed = true;
		}
		if ( $changed ) {
			$order->save_meta_data();
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Scheduling and alert processing.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Ensure the recurring daily check is (un)scheduled according to the setting.
	 *
	 * Runs on admin_init, throttled by a transient.
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_maybe_schedule() {
		if ( get_transient( 'wps_tofw_delivery_alerts_schedule_checked' ) ) {
			return;
		}
		$this->wps_tofw_sync_schedule();
		set_transient( 'wps_tofw_delivery_alerts_schedule_checked', 1, HOUR_IN_SECONDS );
	}

	/**
	 * Schedule or unschedule the daily action.
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_sync_schedule() {
		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}
		$scheduled = as_next_scheduled_action( self::DAILY_HOOK, array(), self::AS_GROUP );
		if ( $this->wps_tofw_alerts_enabled() ) {
			if ( ! $scheduled ) {
				$first = ( new DateTime( 'tomorrow 06:00', wp_timezone() ) )->getTimestamp();
				as_schedule_recurring_action( $first, DAY_IN_SECONDS, self::DAILY_HOOK, array(), self::AS_GROUP );
			}
		} elseif ( $scheduled ) {
			as_unschedule_all_actions( self::DAILY_HOOK, array(), self::AS_GROUP );
		}
	}

	/**
	 * Re-sync the schedule right after the enable option changes.
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_on_enable_option_change() {
		delete_transient( 'wps_tofw_delivery_alerts_schedule_checked' );
		$this->wps_tofw_sync_schedule();
	}

	/**
	 * Daily check entry point (Action Scheduler).
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_run_daily_check() {
		if ( ! $this->wps_tofw_alerts_enabled() ) {
			return;
		}
		$this->wps_tofw_prune_active_alerts();
		update_option( self::OPTION_PENDING_DIGEST, array(), false );
		$this->wps_tofw_process_alert_batch( 1 );
	}

	/**
	 * Order statuses to scan for alerts (without wc- prefix).
	 *
	 * @since 1.3.0
	 * @return array
	 */
	protected function wps_tofw_get_scan_statuses() {
		$statuses = array_merge( array( 'wc-processing', 'wc-on-hold' ), $this->wps_tofw_get_shipped_statuses(), $this->wps_tofw_get_exception_statuses() );
		$statuses = array_diff( array_unique( $statuses ), array( 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-checkout-draft' ) );
		$statuses = array_map(
			function ( $status ) {
				return 0 === strpos( $status, 'wc-' ) ? substr( $status, 3 ) : $status;
			},
			$statuses
		);
		/**
		 * Filter the order statuses scanned for delivery alerts.
		 *
		 * @since 1.3.0
		 * @param array $statuses Statuses without wc- prefix.
		 */
		return array_values( (array) apply_filters( 'wps_tofw_delivery_alert_scan_statuses', $statuses ) );
	}

	/**
	 * Process one page of candidate orders.
	 *
	 * @since 1.3.0
	 * @param int $page Page number.
	 */
	public function wps_tofw_process_alert_batch( $page = 1 ) {
		if ( ! $this->wps_tofw_alerts_enabled() ) {
			return;
		}
		$page = max( 1, absint( $page ) );

		/**
		 * Filter how many days back orders are scanned for delivery alerts.
		 *
		 * @since 1.3.0
		 * @param int $days Days.
		 */
		$lookback = absint( apply_filters( 'wps_tofw_delivery_alert_lookback_days', 90 ) );

		$orders = wc_get_orders(
			array(
				'type'         => 'shop_order',
				'status'       => $this->wps_tofw_get_scan_statuses(),
				'date_created' => '>' . ( time() - ( max( 1, $lookback ) * DAY_IN_SECONDS ) ),
				'limit'        => self::BATCH_SIZE,
				'paged'        => $page,
				'orderby'      => 'date',
				'order'        => 'DESC',
			)
		);

		$active  = get_option( self::OPTION_ACTIVE_ALERTS, array() );
		$active  = is_array( $active ) ? $active : array();
		$pending = get_option( self::OPTION_PENDING_DIGEST, array() );
		$pending = is_array( $pending ) ? $pending : array();

		foreach ( $orders as $order ) {
			$order_id = $order->get_id();
			$issues   = $this->wps_tofw_evaluate_order( $order );

			if ( empty( $issues ) ) {
				unset( $active[ $order_id ] );
				continue;
			}

			$active[ $order_id ] = array(
				'issues'   => array_keys( $issues ),
				'detected' => isset( $active[ $order_id ]['detected'] ) ? $active[ $order_id ]['detected'] : time(),
			);

			$sent = $order->get_meta( self::META_ALERTS_SENT );
			$sent = is_array( $sent ) ? $sent : array();
			$new  = array();
			foreach ( $issues as $key => $issue ) {
				if ( ! in_array( $issue['fingerprint'], $sent, true ) ) {
					$new[ $key ] = $issue['label'];
					$sent[]      = $issue['fingerprint'];
				}
			}
			if ( ! empty( $new ) ) {
				$pending[ $order_id ] = $new;
				$order->update_meta_data( self::META_ALERTS_SENT, array_slice( array_unique( $sent ), -20 ) );
				$order->save_meta_data();
			}
		}

		// Keep the option bounded.
		if ( count( $active ) > 500 ) {
			$active = array_slice( $active, -500, null, true );
		}
		update_option( self::OPTION_ACTIVE_ALERTS, $active, false );

		/**
		 * Filter the maximum number of alert batches per daily run.
		 *
		 * @since 1.3.0
		 * @param int $max_pages Max pages.
		 */
		$max_pages = absint( apply_filters( 'wps_tofw_delivery_alert_max_batches', 50 ) );

		if ( count( $orders ) === self::BATCH_SIZE && $page < $max_pages && function_exists( 'as_enqueue_async_action' ) ) {
			update_option( self::OPTION_PENDING_DIGEST, $pending, false );
			as_enqueue_async_action( self::BATCH_HOOK, array( $page + 1 ), self::AS_GROUP );
			return;
		}

		update_option( self::OPTION_PENDING_DIGEST, array(), false );
		if ( ! empty( $pending ) ) {
			$this->wps_tofw_send_alert_email( $pending );
		}
	}

	/**
	 * Remove resolved / missing orders from the active alerts option.
	 *
	 * @since 1.3.0
	 * @return array Pruned list.
	 */
	public function wps_tofw_prune_active_alerts() {
		$active = get_option( self::OPTION_ACTIVE_ALERTS, array() );
		$active = is_array( $active ) ? $active : array();
		foreach ( array_keys( $active ) as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order || empty( $this->wps_tofw_evaluate_order( $order ) ) ) {
				unset( $active[ $order_id ] );
			}
		}
		update_option( self::OPTION_ACTIVE_ALERTS, $active, false );
		return $active;
	}

	/**
	 * Send the alert digest email.
	 *
	 * @since 1.3.0
	 * @param array $alerts order_id => array( condition => label ).
	 * @return bool
	 */
	public function wps_tofw_send_alert_email( $alerts ) {
		$rows = '';
		foreach ( $alerts as $order_id => $labels ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}
			$statuses = $this->wps_tofw_shipment_status_labels();
			$status   = $this->wps_tofw_get_shipment_status( $order );
			$rows    .= '<tr>'
				. '<td style="padding:6px;border:1px solid #e5e5e5;"><a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . esc_html( $order->get_order_number() ) . '</a></td>'
				. '<td style="padding:6px;border:1px solid #e5e5e5;">' . esc_html( $this->wps_tofw_get_carrier_label( $order ) ) . '</td>'
				. '<td style="padding:6px;border:1px solid #e5e5e5;">' . esc_html( (string) $order->get_meta( 'wps_tofw_package_tracking_number' ) ) . '</td>'
				. '<td style="padding:6px;border:1px solid #e5e5e5;">' . esc_html( $statuses[ $status ] ) . '</td>'
				. '<td style="padding:6px;border:1px solid #e5e5e5;">' . esc_html( implode( ', ', (array) $labels ) ) . '</td>'
				. '</tr>';
		}
		if ( '' === $rows ) {
			return false;
		}

		/* translators: %s: site name. */
		$subject = sprintf( __( '[%s] Shipments need attention', 'track-orders-for-woocommerce' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
		$heading = __( 'Delivery exceptions & late shipments', 'track-orders-for-woocommerce' );
		$body    = '<p>' . esc_html__( 'The following shipments have a delivery exception or are running late:', 'track-orders-for-woocommerce' ) . '</p>'
			. '<table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;"><thead><tr>'
			. '<th style="padding:6px;border:1px solid #e5e5e5;text-align:left;">' . esc_html__( 'Order', 'track-orders-for-woocommerce' ) . '</th>'
			. '<th style="padding:6px;border:1px solid #e5e5e5;text-align:left;">' . esc_html__( 'Carrier', 'track-orders-for-woocommerce' ) . '</th>'
			. '<th style="padding:6px;border:1px solid #e5e5e5;text-align:left;">' . esc_html__( 'Tracking number', 'track-orders-for-woocommerce' ) . '</th>'
			. '<th style="padding:6px;border:1px solid #e5e5e5;text-align:left;">' . esc_html__( 'Shipment status', 'track-orders-for-woocommerce' ) . '</th>'
			. '<th style="padding:6px;border:1px solid #e5e5e5;text-align:left;">' . esc_html__( 'Issue', 'track-orders-for-woocommerce' ) . '</th>'
			. '</tr></thead><tbody>' . $rows . '</tbody></table>'
			. '<p><a href="' . esc_url( admin_url( 'admin.php?page=track_orders_for_woocommerce_menu&tofw_tab=' . self::TAB_ANALYTICS ) ) . '">' . esc_html__( 'Open shipping analytics', 'track-orders-for-woocommerce' ) . '</a></p>';

		/**
		 * Filter the delivery alert email body (HTML, before WooCommerce wrapping).
		 *
		 * @since 1.3.0
		 * @param string $body   Body.
		 * @param array  $alerts Alerts.
		 */
		$body       = apply_filters( 'wps_tofw_delivery_alert_email_body', $body, $alerts );
		$recipients = $this->wps_tofw_get_recipients();

		if ( function_exists( 'WC' ) && WC()->mailer() ) {
			$mailer  = WC()->mailer();
			$message = $mailer->wrap_message( $heading, $body );
			return (bool) $mailer->send( implode( ',', $recipients ), $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
		}
		return (bool) wp_mail( $recipients, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Admin notice and dashboard widget.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Whether the dashboard notice/widget is enabled and the user may see it.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	protected function wps_tofw_can_show_dashboard_alerts() {
		return $this->wps_tofw_alerts_enabled()
			&& 'on' === get_option( 'wps_tofw_delivery_alerts_dashboard_notice', 'on' )
			&& current_user_can( 'manage_options' );
	}

	/**
	 * Get active alerts.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function wps_tofw_get_active_alerts() {
		$active = get_option( self::OPTION_ACTIVE_ALERTS, array() );
		return is_array( $active ) ? $active : array();
	}

	/**
	 * Admin notice listing the count of shipments that need attention.
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_admin_notice() {
		if ( ! $this->wps_tofw_can_show_dashboard_alerts() ) {
			return;
		}
		$screen  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$allowed = array( 'dashboard', 'woocommerce_page_wc-orders', 'edit-shop_order', 'wp-swings_page_track_orders_for_woocommerce_menu', 'wpswings_page_track_orders_for_woocommerce_menu' );
		if ( ! $screen || ! in_array( $screen->id, $allowed, true ) ) {
			return;
		}
		$active = $this->wps_tofw_get_active_alerts();
		if ( empty( $active ) ) {
			return;
		}
		$signature = md5( wp_json_encode( array_keys( $active ) ) );
		if ( get_user_meta( get_current_user_id(), 'wps_tofw_delivery_alerts_dismissed', true ) === $signature ) {
			return;
		}
		$dismiss_url = wp_nonce_url( admin_url( 'admin-post.php?action=wps_tofw_dismiss_delivery_alerts&signature=' . $signature ), 'wps_tofw_dismiss_delivery_alerts' );
		$view_url    = admin_url( 'admin.php?page=track_orders_for_woocommerce_menu&tofw_tab=' . self::TAB_ANALYTICS );
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Track Orders:', 'track-orders-for-woocommerce' ); ?></strong>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of shipments. */
						_n( '%d shipment has a delivery exception or is running late.', '%d shipments have a delivery exception or are running late.', count( $active ), 'track-orders-for-woocommerce' ),
						count( $active )
					)
				);
				?>
				<a href="<?php echo esc_url( $view_url ); ?>"><?php esc_html_e( 'View late shipments', 'track-orders-for-woocommerce' ); ?></a>
				|
				<a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss', 'track-orders-for-woocommerce' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Dismiss the admin notice until the alert list changes.
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_dismiss_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'track-orders-for-woocommerce' ) );
		}
		check_admin_referer( 'wps_tofw_dismiss_delivery_alerts' );
		$signature = isset( $_GET['signature'] ) ? sanitize_key( wp_unslash( $_GET['signature'] ) ) : '';
		update_user_meta( get_current_user_id(), 'wps_tofw_delivery_alerts_dismissed', $signature );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Register the WordPress dashboard widget.
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_register_dashboard_widget() {
		if ( ! $this->wps_tofw_can_show_dashboard_alerts() ) {
			return;
		}
		wp_add_dashboard_widget( 'wps_tofw_delivery_alerts_widget', esc_html__( 'Shipments Needing Attention', 'track-orders-for-woocommerce' ), array( $this, 'wps_tofw_render_dashboard_widget' ) );
	}

	/**
	 * Render the dashboard widget.
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_render_dashboard_widget() {
		$active = $this->wps_tofw_get_active_alerts();
		$labels = $this->wps_tofw_condition_labels();
		if ( empty( $active ) ) {
			echo '<p>' . esc_html__( 'No delivery exceptions or late shipments detected.', 'track-orders-for-woocommerce' ) . '</p>';
			return;
		}
		echo '<ul>';
		$shown = 0;
		foreach ( array_reverse( $active, true ) as $order_id => $data ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}
			$issue_labels = array();
			foreach ( (array) $data['issues'] as $issue ) {
				$issue_labels[] = isset( $labels[ $issue ] ) ? $labels[ $issue ] : $issue;
			}
			printf(
				'<li><a href="%1$s">#%2$s</a> &mdash; %3$s</li>',
				esc_url( $order->get_edit_order_url() ),
				esc_html( $order->get_order_number() ),
				esc_html( implode( ', ', $issue_labels ) )
			);
			if ( ++$shown >= 10 ) {
				break;
			}
		}
		echo '</ul>';
		printf(
			'<p><a class="button" href="%1$s">%2$s</a></p>',
			esc_url( admin_url( 'admin.php?page=track_orders_for_woocommerce_menu&tofw_tab=' . self::TAB_ANALYTICS ) ),
			/* translators: %d: number of shipments. */
			esc_html( sprintf( __( 'View all (%d)', 'track-orders-for-woocommerce' ), count( $active ) ) )
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Daily counters (tracking page visits, upsell clicks).
	|--------------------------------------------------------------------------
	*/

	/**
	 * Stats table name.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public static function wps_tofw_stats_table() {
		global $wpdb;
		return $wpdb->prefix . 'wps_tofw_daily_stats';
	}

	/**
	 * Create / upgrade the daily stats table when the schema version changes.
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_maybe_upgrade_db() {
		if ( get_option( self::DB_VERSION_OPTION ) === self::DB_VERSION ) {
			return;
		}
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::wps_tofw_stats_table();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			stat_date date NOT NULL,
			metric varchar(40) NOT NULL,
			stat_count bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (stat_date,metric)
		) {$charset};";
		dbDelta( $sql );
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Increment a daily counter.
	 *
	 * @since 1.3.0
	 * @param string $metric Metric key.
	 */
	public function wps_tofw_increment_counter( $metric ) {
		global $wpdb;
		if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
			return;
		}
		$metric = substr( sanitize_key( $metric ), 0, 40 );
		$table  = self::wps_tofw_stats_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$table} (stat_date, metric, stat_count) VALUES (%s, %s, 1) ON DUPLICATE KEY UPDATE stat_count = stat_count + 1", current_time( 'Y-m-d' ), $metric ) );
	}

	/**
	 * Whether the current request should be counted (skip bots / prefetch).
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	protected function wps_tofw_is_countable_request() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		if ( '' === $ua || preg_match( '/bot|crawl|spider|slurp|preview|monitor|curl|wget/i', $ua ) ) {
			return false;
		}
		$purpose = isset( $_SERVER['HTTP_SEC_PURPOSE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_SEC_PURPOSE'] ) ) : ( isset( $_SERVER['HTTP_PURPOSE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_PURPOSE'] ) ) : '' );
		return false === stripos( $purpose, 'prefetch' );
	}

	/**
	 * Tracking page ids (my-account tracking page + guest tracking page).
	 *
	 * @since 1.3.0
	 * @return array
	 */
	protected function wps_tofw_tracking_page_ids() {
		$pages = get_option( 'wps_tofw_tracking_page', array() );
		$ids   = array();
		if ( is_array( $pages ) && isset( $pages['pages'] ) && is_array( $pages['pages'] ) ) {
			foreach ( array( 'wps_track_order_page', 'wps_guest_track_order_page' ) as $key ) {
				if ( ! empty( $pages['pages'][ $key ] ) ) {
					$ids[] = absint( $pages['pages'][ $key ] );
				}
			}
		}
		/**
		 * Filter the page ids counted as tracking page visits.
		 *
		 * @since 1.3.0
		 * @param array $ids Page ids.
		 */
		return array_filter( (array) apply_filters( 'wps_tofw_tracking_page_ids', $ids ) );
	}

	/**
	 * Whether the current request is a tracking page.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	protected function wps_tofw_is_tracking_page() {
		$ids = $this->wps_tofw_tracking_page_ids();
		return ! empty( $ids ) && is_page( $ids );
	}

	/**
	 * Count a tracking page render.
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_count_tracking_visit() {
		if ( ! $this->wps_tofw_count_visits_server_side() ) {
			// Counted by the JS beacon instead (works with full page caching, no double counting).
			return;
		}
		if ( is_admin() || wp_doing_ajax() || is_preview() || ! $this->wps_tofw_is_tracking_page() ) {
			return;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : 'GET';
		if ( 'HEAD' === strtoupper( $method ) || ! $this->wps_tofw_is_countable_request() ) {
			return;
		}
		/**
		 * Filter whether the current tracking page render is counted.
		 *
		 * @since 1.3.0
		 * @param bool $count Count it.
		 */
		if ( apply_filters( 'wps_tofw_count_tracking_visit', true ) ) {
			$this->wps_tofw_increment_counter( 'tracking_visit' );
		}
	}

	/**
	 * Whether tracking page visits are counted server side (template_redirect)
	 * instead of by the JS beacon.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	protected function wps_tofw_count_visits_server_side() {
		/**
		 * Filter whether tracking page visits are counted server side instead of by the JS beacon.
		 * Server side counting misses visits served from a full page cache.
		 *
		 * @since 1.3.0
		 * @param bool $server_side Count server side. Default false (JS beacon).
		 */
		return (bool) apply_filters( 'wps_tofw_insights_count_visits_server_side', false );
	}

	/**
	 * Enqueue the visit / upsell click beacon on tracking pages.
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_enqueue_beacon() {
		if ( ! $this->wps_tofw_is_tracking_page() ) {
			return;
		}
		wp_enqueue_script( 'wps-tofw-insights-beacon', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/js/wps-tofw-insights-beacon.js', array(), $this->version, true );
		wp_localize_script(
			'wps-tofw-insights-beacon',
			'wps_tofw_insights',
			array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'wps_tofw_insights_beacon' ),
				'count_visit' => $this->wps_tofw_count_visits_server_side() ? 0 : 1,
				/**
				 * Filter the CSS selectors whose clicks count as upsell clicks.
				 *
				 * @since 1.3.0
				 * @param string $selectors Comma separated selectors.
				 */
				'selectors'   => apply_filters( 'wps_tofw_upsell_click_selectors', '.wps-tofw-upsell-wrap a, .wps-tofw-upsell-wrap button, .wps-tofw-upsell-wrap input[type="submit"], .wps-tofw-upsell-wrap label, [data-wps-tofw-upsell]' ),
				/**
				 * Filter the CSS selectors of inputs whose change (accepting an offer) counts as an upsell click.
				 *
				 * @since 1.3.0
				 * @param string $selectors Comma separated selectors.
				 */
				'inputs'      => apply_filters( 'wps_tofw_upsell_accept_selectors', '.wps-tofw-upsell-wrap input, .wps-tofw-upsell-wrap select' ),
			)
		);
	}

	/**
	 * AJAX: record an upsell click or a tracking page visit (logged-in and guest).
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_ajax_record_upsell_click() {
		if ( ! check_ajax_referer( 'wps_tofw_insights_beacon', 'nonce', false ) ) {
			wp_send_json_error( null, 403 );
		}
		$metric = isset( $_POST['metric'] ) && 'tracking_visit' === $_POST['metric'] ? 'tracking_visit' : 'upsell_click'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- strict comparison only.
		if ( ! $this->wps_tofw_is_countable_request() ) {
			wp_send_json_success();
		}
		if ( 'tracking_visit' === $metric ) {
			/** This filter is documented in includes/class-track-orders-for-woocommerce-shipment-insights.php */
			if ( ! $this->wps_tofw_count_visits_server_side() && apply_filters( 'wps_tofw_count_tracking_visit', true ) ) {
				$this->wps_tofw_increment_counter( 'tracking_visit' );
			}
		} else {
			$this->wps_tofw_increment_counter( 'upsell_click' );
		}
		wp_send_json_success();
	}

	/*
	|--------------------------------------------------------------------------
	| Analytics.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Parse the requested analytics range (Y-m-d strings, site timezone).
	 *
	 * @since 1.3.0
	 * @return array( 'from' => 'Y-m-d', 'to' => 'Y-m-d', 'from_ts' => int, 'to_ts' => int ).
	 */
	public function wps_tofw_get_requested_range() {
		$tz      = wp_timezone();
		$today   = new DateTime( 'now', $tz );
		$default = ( clone $today )->modify( '-29 days' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only report filters.
		$from = isset( $_GET['wps_tofw_from'] ) ? sanitize_text_field( wp_unslash( $_GET['wps_tofw_from'] ) ) : '';
		$to   = isset( $_GET['wps_tofw_to'] ) ? sanitize_text_field( wp_unslash( $_GET['wps_tofw_to'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$from_dt = DateTime::createFromFormat( '!Y-m-d', $from, $tz );
		$to_dt   = DateTime::createFromFormat( '!Y-m-d', $to, $tz );
		$from_dt = $from_dt ? $from_dt : DateTime::createFromFormat( '!Y-m-d', $default->format( 'Y-m-d' ), $tz );
		$to_dt   = $to_dt ? $to_dt : DateTime::createFromFormat( '!Y-m-d', $today->format( 'Y-m-d' ), $tz );
		if ( $from_dt > $to_dt ) {
			$tmp     = $from_dt;
			$from_dt = $to_dt;
			$to_dt   = $tmp;
		}

		return array(
			'from'    => $from_dt->format( 'Y-m-d' ),
			'to'      => $to_dt->format( 'Y-m-d' ),
			'from_ts' => $from_dt->getTimestamp(),
			'to_ts'   => $to_dt->getTimestamp() + DAY_IN_SECONDS - 1,
		);
	}

	/**
	 * Build the analytics report for a date range (orders created within it).
	 *
	 * @since 1.3.0
	 * @param array $range Range from wps_tofw_get_requested_range().
	 * @param bool  $force Skip the cache.
	 * @return array
	 */
	public function wps_tofw_get_analytics( $range, $force = false ) {
		$cache_key = 'wps_tofw_analytics_' . md5( wp_json_encode( array( $range['from'], $range['to'], $this->wps_tofw_get_expected_days(), self::DB_VERSION ) ) );
		if ( ! $force ) {
			$cached = get_transient( $cache_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$status_counts = array_fill_keys( array_keys( $this->wps_tofw_shipment_status_labels() ), 0 );
		$carriers      = array();
		$late          = array();
		$scanned       = 0;
		$total_days    = 0;
		$delivered_n   = 0;
		$per_page      = 200;
		$page          = 1;
		$truncated     = false;
		$late_unshipped = 0;
		$all_cond      = array_keys( $this->wps_tofw_condition_labels() );

		/**
		 * Filter the maximum number of orders scanned for analytics.
		 *
		 * @since 1.3.0
		 * @param int $max Max orders.
		 */
		$max_orders = max( $per_page, absint( apply_filters( 'wps_tofw_analytics_max_orders', 2000 ) ) );

		do {
			$orders = wc_get_orders(
				array(
					'type'         => 'shop_order',
					'limit'        => $per_page,
					'paged'        => $page,
					'date_created' => $range['from_ts'] . '...' . $range['to_ts'],
					'orderby'      => 'date',
					'order'        => 'DESC',
				)
			);

			foreach ( $orders as $order ) {
				++$scanned;
				$status = $this->wps_tofw_get_shipment_status( $order );
				++$status_counts[ $status ];

				if ( 'cancelled' === $status ) {
					continue;
				}

				$carrier = $this->wps_tofw_get_carrier_label( $order );

				if ( 'not_shipped' === $status ) {
					// Only the estimated delivery date can make an unshipped order late.
					$issues = $this->wps_tofw_evaluate_order( $order, array( 'estimated_date' ) );
					if ( ! empty( $issues ) ) {
						++$late_unshipped;
						if ( count( $late ) < 100 ) {
							$late[] = $this->wps_tofw_late_row( $order, $carrier, $status, $issues );
						}
					}
					continue;
				}

				if ( ! isset( $carriers[ $carrier ] ) ) {
					$carriers[ $carrier ] = array(
						'shipments' => 0,
						'delivered' => 0,
						'days'      => 0,
						'late'      => 0,
					);
				}
				++$carriers[ $carrier ]['shipments'];

				if ( 'delivered' === $status ) {
					$shipped   = $this->wps_tofw_get_shipped_timestamp( $order );
					$delivered = $this->wps_tofw_get_delivered_timestamp( $order );
					if ( $shipped && $delivered && $delivered >= $shipped ) {
						$days                          = ( $delivered - $shipped ) / DAY_IN_SECONDS;
						$carriers[ $carrier ]['days'] += $days;
						++$carriers[ $carrier ]['delivered'];
						$total_days += $days;
						++$delivered_n;
					}
					continue;
				}

				$issues = $this->wps_tofw_evaluate_order( $order, $all_cond );
				if ( ! empty( $issues ) ) {
					++$carriers[ $carrier ]['late'];
					if ( count( $late ) < 100 ) {
						$late[] = $this->wps_tofw_late_row( $order, $carrier, $status, $issues );
					}
				}
			}

			$has_more = count( $orders ) === $per_page;
			if ( $has_more && $scanned >= $max_orders ) {
				$truncated = true;
				break;
			}
			++$page;
		} while ( $has_more );

		foreach ( $carriers as $name => $row ) {
			$carriers[ $name ]['avg_days'] = $row['delivered'] ? round( $row['days'] / $row['delivered'], 1 ) : null;
		}
		uasort(
			$carriers,
			function ( $a, $b ) {
				return $b['shipments'] - $a['shipments'];
			}
		);

		$report = array(
			'scanned'       => $scanned,
			'truncated'     => $truncated,
			'status_counts' => $status_counts,
			'carriers'      => $carriers,
			'late'          => $late,
			'late_count'    => array_sum( wp_list_pluck( $carriers, 'late' ) ) + $late_unshipped,
			'avg_days'      => $delivered_n ? round( $total_days / $delivered_n, 1 ) : null,
			'delivered_n'   => $delivered_n,
			'counters'      => $this->wps_tofw_get_counters( $range['from'], $range['to'] ),
			'generated'     => time(),
		);

		set_transient( $cache_key, $report, 10 * MINUTE_IN_SECONDS );
		return $report;
	}

	/**
	 * Build a late shipment row for the analytics report.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order   Order.
	 * @param string   $carrier Carrier label.
	 * @param string   $status  Shipment status.
	 * @param array    $issues  Issues.
	 * @return array
	 */
	protected function wps_tofw_late_row( $order, $carrier, $status, $issues ) {
		return array(
			'order_id' => $order->get_id(),
			'number'   => $order->get_order_number(),
			'edit_url' => $order->get_edit_order_url(),
			'carrier'  => $carrier,
			'tracking' => (string) $order->get_meta( 'wps_tofw_package_tracking_number' ),
			'status'   => $status,
			'shipped'  => $this->wps_tofw_get_shipped_timestamp( $order ),
			'issues'   => wp_list_pluck( $issues, 'label' ),
		);
	}

	/**
	 * Daily counters within a date range.
	 *
	 * @since 1.3.0
	 * @param string $from Y-m-d.
	 * @param string $to   Y-m-d.
	 * @return array( 'totals' => array, 'daily' => array( date => array( metric => count ) ) ).
	 */
	public function wps_tofw_get_counters( $from, $to ) {
		global $wpdb;
		$result = array(
			'totals' => array(
				'tracking_visit' => 0,
				'upsell_click'   => 0,
			),
			'daily'  => array(),
		);
		if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
			return $result;
		}
		$table = self::wps_tofw_stats_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT stat_date, metric, stat_count FROM {$table} WHERE stat_date BETWEEN %s AND %s ORDER BY stat_date DESC", $from, $to ), ARRAY_A );
		foreach ( (array) $rows as $row ) {
			$metric = $row['metric'];
			if ( ! isset( $result['totals'][ $metric ] ) ) {
				$result['totals'][ $metric ] = 0;
			}
			$result['totals'][ $metric ]                       += (int) $row['stat_count'];
			$result['daily'][ $row['stat_date'] ][ $metric ] = (int) $row['stat_count'];
		}
		return $result;
	}

	/**
	 * Enqueue analytics CSS on the plugin settings screen.
	 *
	 * @since 1.3.0
	 */
	public function wps_tofw_admin_enqueue_styles() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( (string) $screen->id, 'track_orders_for_woocommerce_menu' ) ) {
			return;
		}
		wp_enqueue_style( 'wps-tofw-shipment-insights', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/css/wps-tofw-shipment-insights.css', array(), $this->version );
	}
}
