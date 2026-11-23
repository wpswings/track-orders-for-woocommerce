<?php
/**
 * Admin review request notice.
 *
 * Shows a dismissible "rate us" notice once the store has added tracking
 * information to a minimum number of orders.
 *
 * @link       https://wpswings.com/
 * @since      1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Track_Orders_For_Woocommerce_Review_Notice' ) ) {
	return;
}

/**
 * Handles the review request admin notice.
 *
 * @since 1.3.0
 */
class Track_Orders_For_Woocommerce_Review_Notice {

	/**
	 * Option holding the notice state.
	 *
	 * Structure: array(
	 *   'status'       => 'active'|'dismissed'|'rated',
	 *   'snooze_until' => int timestamp,
	 *   'reached_at'   => int timestamp when the threshold was first met,
	 * ).
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const OPTION = 'wps_tofw_review_notice_state';

	/**
	 * Transient caching the tracked orders count.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const COUNT_TRANSIENT = 'wps_tofw_review_tracked_orders_count';

	/**
	 * AJAX action / nonce name.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const AJAX_ACTION = 'wps_tofw_review_notice_action';

	/**
	 * Snooze length in days for the "Later" button.
	 *
	 * @since 1.3.0
	 * @var int
	 */
	const SNOOZE_DAYS = 14;

	/**
	 * Review URL.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const REVIEW_URL = 'https://wordpress.org/support/plugin/track-orders-for-woocommerce/reviews/#new-post';

	/**
	 * Support URL.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const SUPPORT_URL = 'https://wordpress.org/support/plugin/track-orders-for-woocommerce/';

	/**
	 * Plugin settings page slug.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const PLUGIN_PAGE = 'track_orders_for_woocommerce_menu';

	/**
	 * Per-request cache of the visibility decision.
	 *
	 * @since 1.3.0
	 * @var bool|null
	 */
	private $should_show = null;

	/**
	 * Number of orders with tracking required before the notice appears.
	 *
	 * @since 1.3.0
	 * @return int
	 */
	public static function wps_tofw_get_threshold() {
		/**
		 * Filter the number of tracked orders required before asking for a review.
		 *
		 * @since 1.3.0
		 * @param int $threshold Default 10.
		 */
		return max( 1, (int) apply_filters( 'wps_tofw_review_notice_threshold', 10 ) );
	}

	/**
	 * Get the stored notice state merged with defaults.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function wps_tofw_get_state() {
		$state = get_option( self::OPTION, array() );
		if ( ! is_array( $state ) ) {
			$state = array();
		}
		return wp_parse_args(
			$state,
			array(
				'status'       => 'active',
				'snooze_until' => 0,
				'reached_at'   => 0,
			)
		);
	}

	/**
	 * Persist the notice state.
	 *
	 * @since 1.3.0
	 * @param array $state State to save.
	 * @return void
	 */
	private function wps_tofw_save_state( $state ) {
		update_option( self::OPTION, $state, false );
	}

	/**
	 * Count orders that have a non-empty tracking number (up to the threshold).
	 *
	 * Uses wc_get_orders() with a meta_query so it works with both HPOS and the
	 * legacy posts storage. The query is limited to the threshold because only
	 * "threshold reached or not" matters, and the result is cached in a transient.
	 *
	 * @since 1.3.0
	 * @return int
	 */
	public function wps_tofw_get_tracked_orders_count() {
		$cached = get_transient( self::COUNT_TRANSIENT );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$count = 0;
		if ( function_exists( 'wc_get_orders' ) ) {
			$ids   = wc_get_orders(
				array(
					'limit'      => self::wps_tofw_get_threshold(),
					'return'     => 'ids',
					'type'       => 'shop_order',
					'status'     => array_keys( wc_get_order_statuses() ),
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'wps_tofw_package_tracking_number',
							'value'   => '',
							'compare' => '!=',
						),
					),
				)
			);
			$count = is_array( $ids ) ? count( $ids ) : 0;
		}

		set_transient( self::COUNT_TRANSIENT, $count, 12 * HOUR_IN_SECONDS );
		return $count;
	}

	/**
	 * Invalidate the cached count when an order is saved from the admin.
	 *
	 * Hooked to woocommerce_process_shop_order_meta after the tracking number is saved.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function wps_tofw_flush_tracked_count() {
		$state = $this->wps_tofw_get_state();
		if ( empty( $state['reached_at'] ) ) {
			delete_transient( self::COUNT_TRANSIENT );
		}
	}

	/**
	 * Whether the threshold has been met (remembered permanently once true).
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function wps_tofw_threshold_reached() {
		$state = $this->wps_tofw_get_state();
		if ( ! empty( $state['reached_at'] ) ) {
			return true;
		}
		if ( $this->wps_tofw_get_tracked_orders_count() >= self::wps_tofw_get_threshold() ) {
			$state['reached_at'] = time();
			$this->wps_tofw_save_state( $state );
			delete_transient( self::COUNT_TRANSIENT );
			return true;
		}
		return false;
	}

	/**
	 * Whether the current admin screen is allowed to show the notice.
	 *
	 * Allowed: main Dashboard, WooCommerce screens, and this plugin's settings page.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function wps_tofw_is_allowed_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		if ( 'dashboard' === $screen->id ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( self::PLUGIN_PAGE === $page || false !== strpos( $screen->id, self::PLUGIN_PAGE ) ) {
			return true;
		}

		$order_screens = array( 'woocommerce_page_wc-orders', 'edit-shop_order', 'shop_order' );
		if ( in_array( $screen->id, $order_screens, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Decide whether the notice should render on this request.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function wps_tofw_should_show() {
		if ( null !== $this->should_show ) {
			return $this->should_show;
		}

		$this->should_show = false;

		if ( ! current_user_can( 'manage_woocommerce' ) || ! $this->wps_tofw_is_allowed_screen() ) {
			return false;
		}

		$state = $this->wps_tofw_get_state();
		if ( 'active' !== $state['status'] ) {
			return false;
		}
		if ( ! empty( $state['snooze_until'] ) && time() < (int) $state['snooze_until'] ) {
			return false;
		}

		$this->should_show = $this->wps_tofw_threshold_reached();
		return $this->should_show;
	}

	/**
	 * Enqueue the notice script when the notice will render.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function wps_tofw_enqueue_scripts() {
		if ( ! $this->wps_tofw_should_show() ) {
			return;
		}

		wp_enqueue_script(
			'wps-tofw-review-notice',
			TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/js/wps-tofw-review-notice.js',
			array( 'jquery' ),
			TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION,
			true
		);
		wp_localize_script(
			'wps-tofw-review-notice',
			'wps_tofw_review_notice',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'action'   => self::AJAX_ACTION,
				'nonce'    => wp_create_nonce( self::AJAX_ACTION ),
			)
		);
	}

	/**
	 * Render the notice.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function wps_tofw_render_notice() {
		if ( ! $this->wps_tofw_should_show() ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible wps-tofw-review-notice" id="wps-tofw-review-notice">
			<p>
				<strong><?php esc_html_e( 'Track Orders for WooCommerce', 'track-orders-for-woocommerce' ); ?></strong><br/>
				<?php
				printf(
					/* translators: %d: number of orders with tracking added. */
					esc_html__( 'Great job! You have added tracking to %d+ orders with Track Orders for WooCommerce. If it is helping your store, would you mind leaving us a quick review on WordPress.org? It really helps us keep improving the plugin.', 'track-orders-for-woocommerce' ),
					(int) self::wps_tofw_get_threshold()
				);
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( self::REVIEW_URL ); ?>" class="button button-primary wps-tofw-review-action" data-review-action="rated" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Rate us', 'track-orders-for-woocommerce' ); ?></a>
				<a href="<?php echo esc_url( self::SUPPORT_URL ); ?>" class="button wps-tofw-review-help" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Need help', 'track-orders-for-woocommerce' ); ?></a>
				<a href="#" class="button wps-tofw-review-action" data-review-action="later"><?php esc_html_e( 'Later', 'track-orders-for-woocommerce' ); ?></a>
				<a href="#" class="wps-tofw-review-action" data-review-action="dismiss" style="margin-left:8px;vertical-align:middle;"><?php esc_html_e( "Don't ask again", 'track-orders-for-woocommerce' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * AJAX handler for notice actions (rated / later / dismiss).
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function wps_tofw_handle_ajax() {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'track-orders-for-woocommerce' ) ), 403 );
		}

		$review_action = isset( $_POST['review_action'] ) ? sanitize_key( wp_unslash( $_POST['review_action'] ) ) : '';
		$state         = $this->wps_tofw_get_state();

		switch ( $review_action ) {
			case 'rated':
				$state['status'] = 'rated';
				break;
			case 'dismiss':
				$state['status'] = 'dismissed';
				break;
			case 'later':
				$state['snooze_until'] = time() + ( self::SNOOZE_DAYS * DAY_IN_SECONDS );
				break;
			default:
				wp_send_json_error( array( 'message' => __( 'Invalid action.', 'track-orders-for-woocommerce' ) ), 400 );
		}

		$this->wps_tofw_save_state( $state );
		wp_send_json_success( $state );
	}
}
