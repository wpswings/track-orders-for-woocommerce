<?php
/**
 * "Return / Exchange" button on the tracking pages, linking to the
 * WP Swings Return Refund and Exchange (RMA) request page.
 *
 * @since      1.3.0
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the RMA request button.
 *
 * @since 1.3.0
 */
class Wps_Tofw_Rma_Return_Link {

	/**
	 * RMA plugin basenames (lite and pro).
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public static function plugin_basenames() {
		return array(
			'woo-refund-and-exchange-lite/woo-refund-and-exchange-lite.php',
			'rma-return-refund-exchange-for-woocommerce-pro/rma-return-refund-exchange-for-woocommerce-pro.php',
		);
	}

	/**
	 * Whether an RMA plugin is active.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public static function is_rma_active() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$active = false;
		foreach ( self::plugin_basenames() as $basename ) {
			if ( is_plugin_active( $basename ) ) {
				$active = true;
				break;
			}
		}

		// Fall back to symbols defined by the RMA plugins (covers renamed plugin folders).
		if ( ! $active ) {
			$active = defined( 'WOO_REFUND_AND_EXCHANGE_LITE_VERSION' )
				|| defined( 'WOO_REFUND_AND_EXCHANGE_LITE_DIR_PATH' )
				|| class_exists( 'Woo_Refund_And_Exchange_Lite' )
				|| defined( 'RMA_RETURN_REFUND_EXCHANGE_FOR_WOOCOMMERCE_PRO_VERSION' )
				|| class_exists( 'Rma_Return_Refund_Exchange_For_Woocommerce_Pro' );
		}

		/**
		 * Filter whether the RMA plugin is considered active.
		 *
		 * @since 1.3.0
		 * @param bool $active Active state.
		 */
		return (bool) apply_filters( 'wps_tofw_is_rma_active', $active );
	}

	/**
	 * Whether the current visitor is the logged-in owner of the order.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public static function is_order_owner( $order ) {
		$user_id = get_current_user_id();
		return $user_id > 0 && (int) $order->get_customer_id() === $user_id;
	}

	/**
	 * Whether the current guest verified the order billing email through the
	 * guest tracking form (same session state the tracking page relies on).
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public static function is_verified_guest( $order ) {
		if ( is_user_logged_in() ) {
			return false;
		}
		$session_started = false;
		if ( ! session_id() && ! headers_sent() && isset( $_COOKIE[ session_name() ] ) ) {
			session_start();
			$session_started = true;
		}
		$session_email = isset( $_SESSION['wps_tofw_email'] ) ? sanitize_email( wp_unslash( $_SESSION['wps_tofw_email'] ) ) : '';
		if ( $session_started ) {
			session_write_close();
		}
		$billing_email = $order->get_billing_email();
		return '' !== $session_email && '' !== $billing_email && 0 === strcasecmp( $session_email, $billing_email );
	}

	/**
	 * URL of the RMA guest request form page, if the RMA plugin provides one.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public static function get_guest_form_url() {
		$url     = '';
		$page_id = (int) get_option( 'wps_rma_guest_form_page_id', 0 );
		if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
			$url = get_permalink( $page_id );
		}
		/**
		 * Filter the RMA guest form URL.
		 *
		 * @since 1.3.0
		 * @param string $url URL (empty when no guest form is available).
		 */
		return (string) apply_filters( 'wps_tofw_rma_guest_form_url', $url );
	}

	/**
	 * Whether the order is eligible for a return request.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	public static function is_order_eligible( $order ) {
		$statuses = array( 'completed', 'delivered' );
		/**
		 * Filter the order statuses (without wc- prefix) eligible for the button.
		 *
		 * @since 1.3.0
		 * @param array    $statuses Statuses.
		 * @param WC_Order $order    Order.
		 */
		$statuses = (array) apply_filters( 'wps_tofw_rma_eligible_statuses', $statuses, $order );
		$statuses = array_map(
			function ( $status ) {
				return 0 === strpos( $status, 'wc-' ) ? substr( $status, 3 ) : $status;
			},
			$statuses
		);
		return in_array( $order->get_status(), $statuses, true );
	}

	/**
	 * Build the RMA request URL.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return string
	 */
	public static function get_request_url( $order ) {
		$url = '';
		if ( self::is_order_owner( $order ) ) {
			$page_id = (int) get_option( 'wps_rma_return_request_form_page_id', 0 );
			if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
				$url = add_query_arg( 'order_id', $order->get_id(), get_permalink( $page_id ) );
			} else {
				// RMA renders its request form on the My Account order view.
				$url = $order->get_view_order_url();
			}
		} elseif ( self::is_verified_guest( $order ) ) {
			// Guest: RMA guest form if available, otherwise My Account login. Never expose the order key.
			$url = self::get_guest_form_url();
			if ( '' === $url ) {
				$url = wc_get_page_permalink( 'myaccount' );
			}
		}
		/**
		 * Filter the RMA request URL.
		 *
		 * @since 1.3.0
		 * @param string   $url   URL.
		 * @param WC_Order $order Order.
		 */
		return (string) apply_filters( 'wps_tofw_rma_request_url', $url, $order );
	}

	/**
	 * Output the button. Hooked to `wps_tofw_render_return_button`.
	 *
	 * @since 1.3.0
	 * @param int|WC_Order $order Order or order ID.
	 * @return void
	 */
	public function render_button( $order ) {
		if ( 'on' !== get_option( 'wps_tofw_enable_rma_return_button', 'on' ) ) {
			return;
		}
		if ( ! self::is_rma_active() ) {
			return;
		}
		$order = wc_get_order( $order );
		if ( ! $order || ! self::is_order_eligible( $order ) ) {
			return;
		}
		if ( ! self::is_order_owner( $order ) && ! self::is_verified_guest( $order ) ) {
			return;
		}
		$url = self::get_request_url( $order );
		if ( empty( $url ) ) {
			return;
		}
		$label = get_option( 'wps_tofw_rma_return_button_label', '' );
		if ( '' === $label ) {
			$label = __( 'Return / Exchange', 'track-orders-for-woocommerce' );
		}
		?>
		<div class="wps-tofw-rma-return-wrap" style="margin:15px 0;">
			<a href="<?php echo esc_url( $url ); ?>" class="button wps-tofw-rma-return-button"><?php echo esc_html( $label ); ?></a>
		</div>
		<?php
	}
}
