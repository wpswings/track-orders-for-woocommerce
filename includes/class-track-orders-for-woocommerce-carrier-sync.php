<?php
/**
 * Automatic carrier status sync.
 *
 * Periodically (Action Scheduler) fetches the live shipment status of orders
 * that have a tracking number from a carrier API, stores a normalized status
 * and event timeline in order meta and optionally completes delivered orders.
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

require_once __DIR__ . '/carrier-sync/interface-wps-tofw-tracking-provider.php';
require_once __DIR__ . '/carrier-sync/class-wps-tofw-tracking-provider-fedex.php';
require_once __DIR__ . '/carrier-sync/class-wps-tofw-tracking-provider-17track.php';

/**
 * Automatic carrier status sync.
 *
 * @since 1.3.0
 */
class Track_Orders_For_Woocommerce_Carrier_Sync {

	/**
	 * Recurring Action Scheduler hook.
	 *
	 * @since 1.3.0
	 */
	const RECURRING_HOOK = 'wps_tofw_carrier_sync_run';

	/**
	 * Async (chained batch) Action Scheduler hook.
	 *
	 * @since 1.3.0
	 */
	const BATCH_HOOK = 'wps_tofw_carrier_sync_batch';

	/**
	 * Action Scheduler group.
	 *
	 * @since 1.3.0
	 */
	const GROUP = 'wps-tofw-carrier-sync';

	/**
	 * Monthly usage counter option.
	 *
	 * @since 1.3.0
	 */
	const USAGE_OPTION = 'wps_tofw_carrier_sync_usage';

	/**
	 * Default free monthly lookup limit.
	 *
	 * @since 1.3.0
	 */
	const DEFAULT_MONTHLY_LIMIT = 50;

	/**
	 * Order meta: normalized carrier status.
	 *
	 * @since 1.3.0
	 */
	const META_STATUS = 'wps_tofw_carrier_status';

	/**
	 * Order meta: event timeline.
	 *
	 * @since 1.3.0
	 */
	const META_EVENTS = 'wps_tofw_carrier_status_events';

	/**
	 * Order meta: last check timestamp.
	 *
	 * @since 1.3.0
	 */
	const META_LAST_CHECKED = 'wps_tofw_carrier_status_last_checked';

	/**
	 * Order meta: timestamp of the last status change.
	 *
	 * @since 1.3.0
	 */
	const META_UPDATED = 'wps_tofw_carrier_status_updated';

	/**
	 * Order meta: provider id used for the last lookup.
	 *
	 * @since 1.3.0
	 */
	const META_PROVIDER = 'wps_tofw_carrier_status_provider';

	/**
	 * Order meta: last lookup error message.
	 *
	 * @since 1.3.0
	 */
	const META_ERROR = 'wps_tofw_carrier_status_error';

	/**
	 * Cached provider instances.
	 *
	 * @since 1.3.0
	 * @var Wps_Tofw_Tracking_Provider[]|null
	 */
	protected $providers = null;

	/*
	|--------------------------------------------------------------------------
	| Settings helpers.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Whether automatic sync is enabled.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function is_enabled() {
		return 'on' === get_option( 'wps_tofw_is_plugin_enable' ) && 'on' === get_option( 'wps_tofw_carrier_sync_enable' );
	}

	/**
	 * Sync interval in seconds.
	 *
	 * @since 1.3.0
	 * @return int
	 */
	public function get_interval() {
		$hours = absint( get_option( 'wps_tofw_carrier_sync_interval', 6 ) );
		if ( ! in_array( $hours, array_keys( $this->get_interval_options() ), true ) ) {
			$hours = 6;
		}
		return $hours * HOUR_IN_SECONDS;
	}

	/**
	 * Interval choices (hours => label).
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function get_interval_options() {
		return array(
			1  => __( 'Every hour', 'track-orders-for-woocommerce' ),
			3  => __( 'Every 3 hours', 'track-orders-for-woocommerce' ),
			6  => __( 'Every 6 hours', 'track-orders-for-woocommerce' ),
			12 => __( 'Every 12 hours', 'track-orders-for-woocommerce' ),
			24 => __( 'Once a day', 'track-orders-for-woocommerce' ),
		);
	}

	/**
	 * Normalized statuses and their labels.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public static function get_status_labels() {
		/**
		 * Filter the normalized carrier status labels.
		 *
		 * @since 1.3.0
		 * @param array $labels Status => label.
		 */
		return (array) apply_filters(
			'wps_tofw_carrier_status_labels',
			array(
				'pending'              => __( 'Awaiting carrier update', 'track-orders-for-woocommerce' ),
				'info_received'        => __( 'Label created', 'track-orders-for-woocommerce' ),
				'in_transit'           => __( 'In transit', 'track-orders-for-woocommerce' ),
				'out_for_delivery'     => __( 'Out for delivery', 'track-orders-for-woocommerce' ),
				'available_for_pickup' => __( 'Available for pickup', 'track-orders-for-woocommerce' ),
				'failed_attempt'       => __( 'Delivery attempt failed', 'track-orders-for-woocommerce' ),
				'delivered'            => __( 'Delivered', 'track-orders-for-woocommerce' ),
				'exception'            => __( 'Exception', 'track-orders-for-woocommerce' ),
				'expired'              => __( 'Tracking expired', 'track-orders-for-woocommerce' ),
			)
		);
	}

	/**
	 * Label for a normalized status.
	 *
	 * @since 1.3.0
	 * @param string $status Status.
	 * @return string
	 */
	public static function get_status_label( $status ) {
		$labels = self::get_status_labels();
		return isset( $labels[ $status ] ) ? $labels[ $status ] : ucwords( str_replace( '_', ' ', (string) $status ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Usage limit.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Monthly lookup limit. A negative value means unlimited.
	 *
	 * @since 1.3.0
	 * @return int
	 */
	public function get_monthly_limit() {
		/**
		 * Filter the number of carrier status lookups allowed per month.
		 * Return -1 (or PHP_INT_MAX) for unlimited.
		 *
		 * @since 1.3.0
		 * @param int $limit Monthly limit.
		 */
		return (int) apply_filters( 'wps_tofw_carrier_sync_monthly_limit', self::DEFAULT_MONTHLY_LIMIT );
	}

	/**
	 * Whether the limit is unlimited.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function is_unlimited() {
		$limit = $this->get_monthly_limit();
		return $limit < 0 || PHP_INT_MAX === $limit;
	}

	/**
	 * Current usage period key (site timezone).
	 *
	 * @since 1.3.0
	 * @return string
	 */
	protected function get_period() {
		return wp_date( 'Y-m' );
	}

	/**
	 * Lookups used in the current month (counter resets monthly).
	 *
	 * @since 1.3.0
	 * @return int
	 */
	public function get_usage_count() {
		wp_cache_delete( self::USAGE_OPTION, 'options' );
		$usage = get_option( self::USAGE_OPTION, array() );
		if ( ! is_array( $usage ) || ! isset( $usage['period'] ) || $usage['period'] !== $this->get_period() ) {
			return 0;
		}
		return isset( $usage['count'] ) ? absint( $usage['count'] ) : 0;
	}

	/**
	 * Increment the monthly usage counter.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	protected function increment_usage() {
		update_option(
			self::USAGE_OPTION,
			array(
				'period' => $this->get_period(),
				'count'  => $this->get_usage_count() + 1,
			),
			false
		);
	}

	/**
	 * Whether another lookup is allowed this month.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function has_quota() {
		return $this->is_unlimited() || $this->get_usage_count() < $this->get_monthly_limit();
	}

	/**
	 * Label shown for the monthly limit in admin.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_limit_label() {
		$label = $this->is_unlimited() ? __( 'Unlimited', 'track-orders-for-woocommerce' ) : number_format_i18n( $this->get_monthly_limit() );
		/**
		 * Filter the monthly limit label shown in the usage counter.
		 *
		 * @since 1.3.0
		 * @param string $label Label.
		 * @param int    $limit Monthly limit.
		 */
		return (string) apply_filters( 'wps_tofw_carrier_sync_limit_label', $label, $this->get_monthly_limit() );
	}

	/*
	|--------------------------------------------------------------------------
	| Providers.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Registered providers, in priority order.
	 *
	 * @since 1.3.0
	 * @return Wps_Tofw_Tracking_Provider[]
	 */
	public function get_providers() {
		if ( null === $this->providers ) {
			/**
			 * Filter the carrier status providers. Carrier specific providers should
			 * come before generic ones; the first configured provider supporting the
			 * order's carrier is used.
			 *
			 * @since 1.3.0
			 * @param Wps_Tofw_Tracking_Provider[] $providers Providers keyed by id.
			 */
			$providers       = apply_filters(
				'wps_tofw_carrier_sync_providers',
				array(
					'fedex'   => new Wps_Tofw_Tracking_Provider_Fedex(),
					'17track' => new Wps_Tofw_Tracking_Provider_17track(),
				)
			);
			$this->providers = array();
			foreach ( (array) $providers as $id => $provider ) {
				if ( $provider instanceof Wps_Tofw_Tracking_Provider ) {
					$this->providers[ $id ] = $provider;
				}
			}
		}
		return $this->providers;
	}

	/**
	 * Pick the provider for a carrier.
	 *
	 * @since 1.3.0
	 * @param string   $carrier Carrier slug.
	 * @param WC_Order $order   Order.
	 * @return Wps_Tofw_Tracking_Provider|null
	 */
	public function get_provider_for( $carrier, $order ) {
		$selected = null;
		foreach ( $this->get_providers() as $provider ) {
			if ( $provider->is_configured() && $provider->supports( $carrier ) ) {
				$selected = $provider;
				break;
			}
		}
		/**
		 * Filter the provider used for an order.
		 *
		 * @since 1.3.0
		 * @param Wps_Tofw_Tracking_Provider|null $selected Provider.
		 * @param string                          $carrier  Carrier slug.
		 * @param WC_Order                        $order    Order.
		 */
		$selected = apply_filters( 'wps_tofw_carrier_sync_provider_for_order', $selected, $carrier, $order );
		return $selected instanceof Wps_Tofw_Tracking_Provider ? $selected : null;
	}

	/**
	 * Carrier slug and tracking number stored on the order.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return array array( carrier, tracking_number ).
	 */
	public function get_order_tracking( $order ) {
		$number  = trim( (string) $order->get_meta( 'wps_tofw_package_tracking_number', true ) );
		$carrier = (string) $order->get_meta( 'wps_tofw_selected_shipping_service', true );
		if ( '' === $number ) {
			// Pro "enhanced tracking" stores its own number; the carrier is then auto-detected.
			$number  = trim( (string) $order->get_meta( 'wps_tofwp_enhanced_tracking_no', true ) );
			$carrier = '';
		}
		/**
		 * Filter the carrier and tracking number used for an order.
		 *
		 * @since 1.3.0
		 * @param array    $tracking array( carrier, tracking_number ).
		 * @param WC_Order $order    Order.
		 */
		$tracking = apply_filters( 'wps_tofw_carrier_sync_order_tracking', array( sanitize_key( $carrier ), $number ), $order );
		return array(
			isset( $tracking[0] ) ? (string) $tracking[0] : '',
			isset( $tracking[1] ) ? (string) $tracking[1] : '',
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Scheduling.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Make sure the recurring action matches the settings.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function maybe_schedule() {
		if ( ! function_exists( 'as_schedule_recurring_action' ) || ! function_exists( 'as_next_scheduled_action' ) ) {
			return;
		}

		$enabled   = $this->is_enabled();
		$signature = $enabled ? 'on|' . $this->get_interval() : 'off';
		$stored    = get_option( 'wps_tofw_carrier_sync_schedule_signature', '' );

		if ( $signature === $stored ) {
			if ( ! $enabled || get_transient( 'wps_tofw_carrier_sync_schedule_checked' ) ) {
				return;
			}
			set_transient( 'wps_tofw_carrier_sync_schedule_checked', 1, HOUR_IN_SECONDS );
			if ( false !== as_next_scheduled_action( self::RECURRING_HOOK, array(), self::GROUP ) ) {
				return;
			}
		}

		as_unschedule_all_actions( self::RECURRING_HOOK, array(), self::GROUP );
		if ( $enabled ) {
			as_schedule_recurring_action( time() + MINUTE_IN_SECONDS, $this->get_interval(), self::RECURRING_HOOK, array(), self::GROUP );
		}
		update_option( 'wps_tofw_carrier_sync_schedule_signature', $signature, false );
	}

	/**
	 * Remove all scheduled sync actions (plugin deactivation).
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public static function unschedule_all() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::RECURRING_HOOK, array(), self::GROUP );
			as_unschedule_all_actions( self::BATCH_HOOK, array(), self::GROUP );
		}
		delete_option( 'wps_tofw_carrier_sync_schedule_signature' );
	}

	/*
	|--------------------------------------------------------------------------
	| Sync.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Recurring action callback.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function run_scheduled() {
		$this->run_batch( 0 );
	}

	/**
	 * Process one batch of orders and chain the next one if needed.
	 *
	 * @since 1.3.0
	 * @param int $chain Number of batches already chained in this run.
	 * @return int Number of orders processed.
	 */
	public function run_batch( $chain = 0 ) {
		if ( ! $this->is_enabled() ) {
			return 0;
		}
		if ( get_transient( 'wps_tofw_carrier_sync_lock' ) ) {
			return 0;
		}
		set_transient( 'wps_tofw_carrier_sync_lock', 1, 5 * MINUTE_IN_SECONDS );

		/**
		 * Filter the number of orders processed per batch.
		 *
		 * @since 1.3.0
		 * @param int $size Batch size.
		 */
		$batch_size = max( 1, (int) apply_filters( 'wps_tofw_carrier_sync_batch_size', 20 ) );
		$order_ids  = $this->get_orders_to_sync( $batch_size );
		$processed  = 0;

		foreach ( $order_ids as $order_id ) {
			if ( ! $this->has_quota() ) {
				update_option( 'wps_tofw_carrier_sync_limit_reached', $this->get_period(), false );
				break;
			}
			$this->sync_order( $order_id );
			++$processed;
		}

		update_option( 'wps_tofw_carrier_sync_last_run', time(), false );
		delete_transient( 'wps_tofw_carrier_sync_lock' );

		/**
		 * Filter the max number of chained batches per scheduled run.
		 *
		 * @since 1.3.0
		 * @param int $max Max batches.
		 */
		$max_chain = (int) apply_filters( 'wps_tofw_carrier_sync_max_batches', 10 );
		if ( count( $order_ids ) === $batch_size && $processed === $batch_size && $this->has_quota() && ( $chain + 1 ) < $max_chain && function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::BATCH_HOOK, array( 'chain' => $chain + 1 ), self::GROUP );
		}

		return $processed;
	}

	/**
	 * Find orders that have tracking info, are not delivered and are due for a check.
	 *
	 * @since 1.3.0
	 * @param int $limit Max orders.
	 * @return int[]
	 */
	public function get_orders_to_sync( $limit ) {
		$excluded = array( 'wc-cancelled', 'wc-refunded', 'wc-failed', 'wc-pending', 'wc-checkout-draft' );
		$statuses = array_values( array_diff( array_keys( wc_get_order_statuses() ), $excluded ) );

		/**
		 * Filter the carrier statuses that end the sync for an order.
		 *
		 * @since 1.3.0
		 * @param array $final Final statuses.
		 */
		$final = (array) apply_filters( 'wps_tofw_carrier_sync_final_statuses', array( 'delivered' ) );

		/**
		 * Filter how many days after creation an order is still synced.
		 *
		 * @since 1.3.0
		 * @param int $days Days.
		 */
		$max_age  = max( 1, (int) apply_filters( 'wps_tofw_carrier_sync_max_order_age_days', 60 ) );
		$min_gap  = max( 15 * MINUTE_IN_SECONDS, $this->get_interval() - 10 * MINUTE_IN_SECONDS );
		$due_time = time() - $min_gap;

		$args = array(
			'limit'        => $limit,
			'return'       => 'ids',
			'status'       => $statuses,
			'type'         => 'shop_order',
			'orderby'      => 'date',
			'order'        => 'DESC',
			'date_created' => '>' . ( time() - $max_age * DAY_IN_SECONDS ),
			'meta_query'   => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => 'wps_tofw_package_tracking_number',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => 'wps_tofwp_enhanced_tracking_no',
						'value'   => '',
						'compare' => '!=',
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => self::META_STATUS,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => self::META_STATUS,
						'value'   => $final,
						'compare' => 'NOT IN',
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => self::META_LAST_CHECKED,
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => self::META_LAST_CHECKED,
						'value'   => $due_time,
						'compare' => '<',
						'type'    => 'NUMERIC',
					),
				),
			),
		);

		/**
		 * Filter the wc_get_orders() args used to find orders to sync.
		 *
		 * @since 1.3.0
		 * @param array $args Query args.
		 */
		$args = apply_filters( 'wps_tofw_carrier_sync_order_query_args', $args );

		return array_map( 'absint', (array) wc_get_orders( $args ) );
	}

	/**
	 * Fetch and store the carrier status for one order.
	 *
	 * @since 1.3.0
	 * @param int $order_id Order id.
	 * @return array|WP_Error Provider result or error.
	 */
	public function sync_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'wps_tofw_no_order', __( 'Order not found.', 'track-orders-for-woocommerce' ) );
		}

		list( $carrier, $number ) = $this->get_order_tracking( $order );
		if ( '' === $number ) {
			return new WP_Error( 'wps_tofw_no_tracking', __( 'The order has no tracking number.', 'track-orders-for-woocommerce' ) );
		}

		$provider = $this->get_provider_for( $carrier, $order );
		if ( ! $provider ) {
			$error = new WP_Error( 'wps_tofw_no_provider', __( 'No configured carrier status provider supports this carrier.', 'track-orders-for-woocommerce' ) );
			$this->store_error( $order, $error );
			return $error;
		}

		if ( ! $this->has_quota() ) {
			update_option( 'wps_tofw_carrier_sync_limit_reached', $this->get_period(), false );
			return new WP_Error( 'wps_tofw_limit_reached', __( 'The monthly carrier status lookup limit has been reached.', 'track-orders-for-woocommerce' ) );
		}

		$this->increment_usage();
		$result = $provider->get_status( $carrier, $number );

		if ( is_wp_error( $result ) ) {
			$order->update_meta_data( self::META_PROVIDER, $provider->get_id() );
			$this->store_error( $order, $result );
			return $result;
		}

		$this->apply_result( $order, (array) $result, $provider );
		return $result;
	}

	/**
	 * Save a lookup error on the order.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @param WP_Error $error Error.
	 * @return void
	 */
	protected function store_error( $order, $error ) {
		$order->update_meta_data( self::META_LAST_CHECKED, time() );
		$order->update_meta_data( self::META_ERROR, sanitize_text_field( $error->get_error_message() ) );
		$order->save();
	}

	/**
	 * Store a provider result on the order.
	 *
	 * @since 1.3.0
	 * @param WC_Order                   $order    Order.
	 * @param array                      $result   Provider result.
	 * @param Wps_Tofw_Tracking_Provider $provider Provider.
	 * @return void
	 */
	protected function apply_result( $order, $result, $provider ) {
		$labels     = self::get_status_labels();
		$new_status = isset( $result['status'] ) ? sanitize_key( $result['status'] ) : 'pending';
		if ( ! isset( $labels[ $new_status ] ) ) {
			$new_status = 'pending';
		}
		$old_status = (string) $order->get_meta( self::META_STATUS, true );

		// Do not downgrade a known status to "pending" (e.g. temporary empty reply).
		if ( 'pending' === $new_status && '' !== $old_status && 'pending' !== $old_status ) {
			$new_status = $old_status;
		}

		$events = $this->sanitize_events( isset( $result['events'] ) ? (array) $result['events'] : array() );

		$order->update_meta_data( self::META_LAST_CHECKED, time() );
		$order->update_meta_data( self::META_PROVIDER, $provider->get_id() );
		$order->delete_meta_data( self::META_ERROR );
		if ( ! empty( $events ) || '' === $old_status ) {
			$order->update_meta_data( self::META_EVENTS, $events );
		}

		$changed = $new_status !== $old_status;
		if ( $changed ) {
			$order->update_meta_data( self::META_STATUS, $new_status );
			$order->update_meta_data( self::META_UPDATED, time() );
			$order->add_order_note(
				sprintf(
					/* translators: 1: provider name, 2: carrier status. */
					__( 'Carrier status updated via %1$s: %2$s', 'track-orders-for-woocommerce' ),
					$provider->get_label(),
					self::get_status_label( $new_status )
				)
			);
		}
		$order->save();

		if ( $changed ) {
			/**
			 * Fires when the carrier status of an order changes.
			 *
			 * @since 1.3.0
			 * @param WC_Order $order      Order.
			 * @param string   $new_status New normalized status.
			 * @param string   $old_status Old normalized status ('' if none).
			 * @param array    $result     Provider result.
			 */
			do_action( 'wps_tofw_carrier_status_changed', $order, $new_status, $old_status, $result );

			/**
			 * Fires after each carrier status change (lightweight signature used by
			 * delivery alerts / analytics).
			 *
			 * @since 1.3.0
			 * @param int    $order_id Order id.
			 * @param string $status   New normalized status.
			 */
			do_action( 'wps_tofw_carrier_status_updated', $order->get_id(), $new_status );

			if ( 'delivered' === $new_status && 'on' === get_option( 'wps_tofw_carrier_sync_auto_complete' ) && ! $order->has_status( array( 'completed', 'cancelled', 'refunded', 'failed' ) ) ) {
				$order->update_status( 'completed', __( 'Order completed automatically: the carrier reported the shipment as delivered.', 'track-orders-for-woocommerce' ) );
			}
		}
	}

	/**
	 * Sanitize, sort (newest first) and cap provider events.
	 *
	 * @since 1.3.0
	 * @param array $events Raw events.
	 * @return array
	 */
	protected function sanitize_events( $events ) {
		$clean = array();
		foreach ( $events as $event ) {
			if ( ! is_array( $event ) ) {
				continue;
			}
			$description = isset( $event['description'] ) ? sanitize_text_field( $event['description'] ) : '';
			if ( '' === $description ) {
				continue;
			}
			$clean[] = array(
				'time'        => isset( $event['time'] ) ? absint( $event['time'] ) : 0,
				'description' => $description,
				'location'    => isset( $event['location'] ) ? sanitize_text_field( $event['location'] ) : '',
				'status'      => isset( $event['status'] ) ? sanitize_key( $event['status'] ) : '',
			);
		}
		usort(
			$clean,
			function ( $a, $b ) {
				return $b['time'] - $a['time'];
			}
		);
		/**
		 * Filter the max number of events stored per order.
		 *
		 * @since 1.3.0
		 * @param int $max Max events.
		 */
		return array_slice( $clean, 0, max( 1, (int) apply_filters( 'wps_tofw_carrier_sync_max_events', 50 ) ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Admin.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Settings fields for the Carrier Status Sync tab.
	 *
	 * @since 1.3.0
	 * @param array $settings Settings.
	 * @return array
	 */
	public function settings_array( $settings ) {
		$settings = array(
			array(
				'title'       => __( 'Enable Automatic Carrier Status Sync', 'track-orders-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Periodically fetch the shipment status (In transit, Out for delivery, Delivered, Exception) of orders with a tracking number.', 'track-orders-for-woocommerce' ),
				'id'          => 'wps_tofw_carrier_sync_enable',
				'value'       => get_option( 'wps_tofw_carrier_sync_enable' ),
				'class'       => 'tofw-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no'  => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
			array(
				'title'       => __( '17TRACK API Key', 'track-orders-for-woocommerce' ),
				'type'        => 'password',
				'description' => __( 'Security key from your 17TRACK API account (api.17track.net). Used for all carriers that have no dedicated provider.', 'track-orders-for-woocommerce' ),
				'id'          => Wps_Tofw_Tracking_Provider_17track::API_KEY_OPTION,
				'value'       => get_option( Wps_Tofw_Tracking_Provider_17track::API_KEY_OPTION ),
				'class'       => '',
				'placeholder' => __( '17TRACK API Key', 'track-orders-for-woocommerce' ),
			),
			array(
				'title'       => __( 'Sync Interval', 'track-orders-for-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'How often tracking statuses are refreshed.', 'track-orders-for-woocommerce' ),
				'id'          => 'wps_tofw_carrier_sync_interval',
				'value'       => (string) ( $this->get_interval() / HOUR_IN_SECONDS ),
				'class'       => '',
				'options'     => $this->get_interval_options(),
			),
			array(
				'title'       => __( 'Auto-complete Order When Delivered', 'track-orders-for-woocommerce' ),
				'type'        => 'radio-switch',
				'description' => __( 'Mark the order as Completed when the carrier reports the shipment as delivered.', 'track-orders-for-woocommerce' ),
				'id'          => 'wps_tofw_carrier_sync_auto_complete',
				'value'       => get_option( 'wps_tofw_carrier_sync_auto_complete' ),
				'class'       => 'tofw-radio-switch-class',
				'options'     => array(
					'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
					'no'  => __( 'NO', 'track-orders-for-woocommerce' ),
				),
			),
		);

		/**
		 * Filter the carrier status sync settings.
		 *
		 * @since 1.3.0
		 * @param array $settings Settings.
		 */
		$settings = apply_filters( 'tofw_carrier_sync_settings_array_filter', $settings );

		$settings[] = array(
			'type'        => 'button',
			'id'          => 'wps_tofw_carrier_sync_settings_save',
			'button_text' => __( 'Save Settings', 'track-orders-for-woocommerce' ),
			'class'       => 'tofw-button-class',
			'main-class'  => 'wps_tofw_main_class',
		);
		return $settings;
	}

	/**
	 * Data for the usage box in the settings tab.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function get_usage_summary() {
		$next_run = false;
		if ( $this->is_enabled() && function_exists( 'as_next_scheduled_action' ) ) {
			$next_run = as_next_scheduled_action( self::RECURRING_HOOK, array(), self::GROUP );
		}
		$providers = array();
		foreach ( $this->get_providers() as $provider ) {
			$providers[] = array(
				'label'      => $provider->get_label(),
				'configured' => $provider->is_configured(),
			);
		}
		return array(
			'used'          => $this->get_usage_count(),
			'limit'         => $this->get_monthly_limit(),
			'limit_label'   => $this->get_limit_label(),
			'unlimited'     => $this->is_unlimited(),
			'limit_reached' => ! $this->has_quota(),
			'resets_on'     => wp_date( get_option( 'date_format' ), strtotime( 'first day of next month 00:00', current_time( 'timestamp' ) ) ), // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			'last_run'      => (int) get_option( 'wps_tofw_carrier_sync_last_run', 0 ),
			'next_run'      => is_numeric( $next_run ) ? (int) $next_run : 0,
			'enabled'       => $this->is_enabled(),
			'providers'     => $providers,
		);
	}

	/**
	 * Queue a sync batch right away (admin-post handler).
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function handle_run_now() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'track-orders-for-woocommerce' ) );
		}
		check_admin_referer( 'wps_tofw_carrier_sync_run_now' );

		$queued = 0;
		if ( $this->is_enabled() && function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::BATCH_HOOK, array( 'chain' => 0 ), self::GROUP );
			$queued = 1;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                 => 'track_orders_for_woocommerce_menu',
					'tofw_tab'             => 'track-orders-for-woocommerce-carrier-sync',
					'wps_tofw_sync_queued' => $queued,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Sync a single order now (admin-post handler, from the order metabox).
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function handle_sync_order() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'track-orders-for-woocommerce' ) );
		}
		$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		check_admin_referer( 'wps_tofw_carrier_sync_order_' . $order_id );

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_die( esc_html__( 'Order not found.', 'track-orders-for-woocommerce' ) );
		}
		if ( $this->is_enabled() ) {
			$this->sync_order( $order_id );
		}

		wp_safe_redirect( $order->get_edit_order_url() );
		exit;
	}

	/**
	 * Register the order edit metabox.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function add_order_metabox() {
		if ( ! $this->is_enabled() || ! function_exists( 'wc_get_page_screen_id' ) ) {
			return;
		}
		add_meta_box(
			'wps_tofw_carrier_status',
			__( 'Carrier Status', 'track-orders-for-woocommerce' ),
			array( $this, 'render_order_metabox' ),
			wc_get_page_screen_id( 'shop-order' ),
			'side',
			'default'
		);
	}

	/**
	 * Order edit metabox output.
	 *
	 * @since 1.3.0
	 * @param WP_Post|WC_Order $post_or_order Post or order.
	 * @return void
	 */
	public function render_order_metabox( $post_or_order ) {
		$order = ( $post_or_order instanceof WP_Post ) ? wc_get_order( $post_or_order->ID ) : $post_or_order;
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$status       = (string) $order->get_meta( self::META_STATUS, true );
		$last_checked = (int) $order->get_meta( self::META_LAST_CHECKED, true );
		$error        = (string) $order->get_meta( self::META_ERROR, true );
		$events       = (array) $order->get_meta( self::META_EVENTS, true );
		$format       = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$sync_url     = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'wps_tofw_carrier_sync_order',
					'order_id' => $order->get_id(),
				),
				admin_url( 'admin-post.php' )
			),
			'wps_tofw_carrier_sync_order_' . $order->get_id()
		);
		?>
		<p>
			<strong><?php esc_html_e( 'Status:', 'track-orders-for-woocommerce' ); ?></strong>
			<?php echo esc_html( $status ? self::get_status_label( $status ) : __( 'Not synced yet', 'track-orders-for-woocommerce' ) ); ?>
		</p>
		<?php if ( $last_checked ) : ?>
			<p><strong><?php esc_html_e( 'Last checked:', 'track-orders-for-woocommerce' ); ?></strong> <?php echo esc_html( wp_date( $format, $last_checked ) ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $events[0]['description'] ) ) : ?>
			<p><strong><?php esc_html_e( 'Latest event:', 'track-orders-for-woocommerce' ); ?></strong> <?php echo esc_html( $events[0]['description'] ); ?></p>
		<?php endif; ?>
		<?php if ( $error ) : ?>
			<p style="color:#b32d2e;"><?php echo esc_html( $error ); ?></p>
		<?php endif; ?>
		<p><a class="button" href="<?php echo esc_url( $sync_url ); ?>"><?php esc_html_e( 'Sync status now', 'track-orders-for-woocommerce' ); ?></a></p>
		<p class="description">
			<?php
			printf(
				/* translators: 1: used lookups, 2: monthly limit. */
				esc_html__( 'Lookups this month: %1$s / %2$s', 'track-orders-for-woocommerce' ),
				esc_html( number_format_i18n( $this->get_usage_count() ) ),
				esc_html( $this->get_limit_label() )
			);
			?>
		</p>
		<?php
	}

	/*
	|--------------------------------------------------------------------------
	| Public.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Render the carrier status timeline on the customer tracking page.
	 *
	 * Hooked to `wps_tofw_after_tracking_details`, fired by the tracking templates.
	 *
	 * @since 1.3.0
	 * @param int $order_id Order id.
	 * @return void
	 */
	public function render_timeline( $order_id ) {
		$order = wc_get_order( absint( $order_id ) );
		if ( ! $order ) {
			return;
		}
		$status = (string) $order->get_meta( self::META_STATUS, true );
		$events = $order->get_meta( self::META_EVENTS, true );
		$events = is_array( $events ) ? $events : array();

		/**
		 * Filter whether to show the carrier status timeline for an order.
		 *
		 * @since 1.3.0
		 * @param bool     $show  Show.
		 * @param WC_Order $order Order.
		 */
		if ( '' === $status || ! apply_filters( 'wps_tofw_show_carrier_timeline', true, $order ) ) {
			return;
		}

		wp_enqueue_style(
			'wps-tofw-carrier-timeline',
			TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/css/track-orders-for-woocommerce-carrier-timeline.css',
			array(),
			defined( 'TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION' ) ? TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION : '1.3.0'
		);

		$wps_tofw_carrier_status       = $status;
		$wps_tofw_carrier_status_label = self::get_status_label( $status );
		$wps_tofw_carrier_events       = $events;
		$wps_tofw_carrier_updated      = (int) $order->get_meta( self::META_LAST_CHECKED, true );

		/**
		 * Filter the carrier timeline template path.
		 *
		 * @since 1.3.0
		 * @param string   $path  Template path.
		 * @param WC_Order $order Order.
		 */
		$template = apply_filters( 'wps_tofw_carrier_timeline_template', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'public/partials/track-orders-for-woocommerce-carrier-timeline.php', $order );
		if ( file_exists( $template ) ) {
			include $template;
		}
	}
}
