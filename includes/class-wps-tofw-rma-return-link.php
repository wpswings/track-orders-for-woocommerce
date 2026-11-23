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
		/**
		 * Filter whether the RMA plugin is considered active.
		 *
		 * @since 1.3.0
		 * @param bool $active Active state.
		 */
		return (bool) apply_filters( 'wps_tofw_is_rma_active', $active );
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
		$page_id = (int) get_option( 'wps_rma_return_request_form_page_id', 0 );
		$url     = '';
		if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
			$url = add_query_arg( 'order_id', $order->get_id(), get_permalink( $page_id ) );
		} elseif ( is_user_logged_in() && (int) $order->get_user_id() === get_current_user_id() ) {
			// RMA renders its request form on the My Account order view.
			$url = $order->get_view_order_url();
		} else {
			// Guest: pass the order key so RMA can validate the order.
			$url = add_query_arg(
				array(
					'order_id'  => $order->get_id(),
					'order_key' => $order->get_order_key(),
				),
				wc_get_page_permalink( 'myaccount' )
			);
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
		if ( 'no' === get_option( 'wps_tofw_enable_rma_return_button', 'yes' ) ) {
			return;
		}
		if ( ! self::is_rma_active() ) {
			return;
		}
		$order = wc_get_order( $order );
		if ( ! $order || ! self::is_order_eligible( $order ) ) {
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
