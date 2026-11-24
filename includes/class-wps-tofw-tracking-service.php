<?php
/**
 * Shared tracking save routine.
 *
 * Used by the manual "Shipping Services" order metabox and by the
 * shipping-plugin auto import integrations, so both follow the same path
 * (order meta + tracking email + action hook).
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

if ( ! class_exists( 'WPS_TOFW_Tracking_Service' ) ) {

	/**
	 * Saves a shipping service + tracking number on an order.
	 *
	 * @since 1.3.0
	 */
	class WPS_TOFW_Tracking_Service {

		/**
		 * Save the shipping service and tracking number on an order.
		 *
		 * When a shipping service is given the tracking email is sent (once per
		 * order, same as the manual flow).
		 *
		 * @since 1.3.0
		 * @param WC_Order|int $order           Order object or ID.
		 * @param string       $service         Shipping service key (e.g. fedex, usps).
		 * @param string       $tracking_number Tracking number.
		 * @param array        $args            Extra context. Keys: source (manual|integration id).
		 * @return bool True when saved.
		 */
		public static function save_tracking( $order, $service, $tracking_number, $args = array() ) {
			$order = ( $order instanceof WC_Order ) ? $order : wc_get_order( $order );
			if ( ! $order ) {
				return false;
			}

			$service         = sanitize_text_field( (string) $service );
			$tracking_number = sanitize_text_field( (string) $tracking_number );
			$args            = wp_parse_args( $args, array( 'source' => 'manual' ) );

			if ( '' === $tracking_number ) {
				return false;
			}

			if ( '' !== $service ) {
				$order->update_meta_data( 'wps_tofw_selected_shipping_service', $service );
				// Carrier display name (e.g. unknown carriers from shipping plugins); empty for manual saves.
				if ( ! empty( $args['carrier_name'] ) ) {
					$order->update_meta_data( 'wps_tofw_selected_shipping_service_label', sanitize_text_field( (string) $args['carrier_name'] ) );
				} else {
					$order->delete_meta_data( 'wps_tofw_selected_shipping_service_label' );
				}
			}
			$order->update_meta_data( 'wps_tofw_package_tracking_number', $tracking_number );
			$order->save();

			if ( '' !== $service ) {
				self::maybe_send_tracking_email( $order );
			}

			/**
			 * Fires after a tracking number has been saved on an order.
			 *
			 * @since 1.3.0
			 * @param WC_Order $order           Order.
			 * @param string   $service         Shipping service key.
			 * @param string   $tracking_number Tracking number.
			 * @param array    $args            Context (source etc.).
			 */
			do_action( 'wps_tofw_tracking_saved', $order, $service, $tracking_number, $args );

			return true;
		}

		/**
		 * Send the tracking email to the customer if it has not been sent yet.
		 *
		 * Moved from Track_Orders_For_Woocommerce_Admin::wps_tofw_save_shipping_services_meta().
		 *
		 * @since 1.3.0
		 * @param WC_Order $order Order.
		 * @return void
		 */
		public static function maybe_send_tracking_email( $order ) {
			if ( 1 === (int) $order->get_meta( 'wps_tofw_tracking_id_sent', true ) ) {
				return;
			}

			$post_id             = $order->get_id();
			$headers             = array( 'Content-Type: text/html; charset=UTF-8' );
			$wps_tracking_url    = $order->get_meta( 'wps_tofw_enhanced_order_company', true );
			$wps_tracking_number = $order->get_meta( 'wps_tofw_enhanced_tracking_no', true );
			$to                  = $order->get_billing_email();
			$status_name         = wc_get_order_status_name( $order->get_status() );

			$subject     = __( 'Your Order Status for Order #', 'track-orders-for-woocommerce' ) . $post_id;
			$mail_header = __( 'Current Order Status is ', 'track-orders-for-woocommerce' ) . $status_name;
			$mail_footer = '';
			$message     = '<html>
				<body>
					<style>
						body {
							box-shadow: 2px 2px 10px #ccc;
							color: #767676;
							font-family: Arial,sans-serif;
							margin: 80px auto;
							max-width: 700px;
							padding-bottom: 30px;
							width: 100%;
						}

						h2 {
							font-size: 30px;
							margin-top: 0;
							color: #fff;
							padding: 40px;
							background-color: #557da1;
						}

						h4 {
							color: #557da1;
							font-size: 20px;
							margin-bottom: 10px;
						}

						.content {
							padding: 0 40px;
						}

						.Customer-detail ul li p {
							margin: 0;
						}

						.details .Shipping-detail {
							width: 40%;
							float: right;
						}

						.details .Billing-detail {
							width: 60%;
							float: left;
						}

						.details .Shipping-detail ul li,.details .Billing-detail ul li {
							list-style-type: none;
							margin: 0;
						}

						.details .Billing-detail ul,.details .Shipping-detail ul {
							margin: 0;
							padding: 0;
						}

						.clear {
							clear: both;
						}

						table,td,th {
							border: 2px solid #ccc;
							padding: 15px;
							text-align: left;
						}

						table {
							border-collapse: collapse;
							width: 100%;
						}

						.info {
							display: inline-block;
						}

						.bold {
							font-weight: bold;
						}

						.footer {
							margin-top: 30px;
							text-align: center;
							color: #99B1D8;
							font-size: 12px;
						}
						dl.variation dd {
							font-size: 12px;
							margin: 0;
						}
					</style>

					<div style="padding: 36px 48px; background-color:#557DA1;color: #fff; font-size: 30px; font-weight: 300; font-family:helvetica;" class="header">
						' . esc_html( $mail_header ) . '
					</div>

					<div class="content">

						<div class="Order">
							<h4>' . esc_html( sprintf( /* translators: %s: order ID. */ __( 'Order #%s', 'track-orders-for-woocommerce' ), $post_id ) ) . '</h4>
							<table>
								<tbody>
									<tr>
										<th>' . esc_html__( 'Order Id', 'track-orders-for-woocommerce' ) . '</th>
										<th>' . esc_html__( 'Tracking Number ', 'track-orders-for-woocommerce' ) . '</th>
									</tr>';
			$message    .= '<tr>
									<td>' . esc_html( $post_id ) . '</td>
									<td>' . esc_html( $order->get_meta( 'wps_tofw_package_tracking_number', true ) ) . '</td>
								</tr>';
			$message    .= '</tbody>
							</table>
							<div>
								<a href="' . esc_url( $wps_tracking_url ) . '">' . esc_html( $wps_tracking_number ) . '</a>
							</div>
						</div>
					</div>
					<div style="text-align: center; padding: 10px;" class="footer">
						' . $mail_footer . '
					</div>
				</body>
				</html>';

			wc_mail( $to, $subject, $message, $headers );

			$order->update_meta_data( 'wps_tofw_tracking_id_sent', 1 );
			$order->save();
		}
	}
}
