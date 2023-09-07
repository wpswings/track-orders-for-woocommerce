<?php
/**
 * Enable api.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/Include
 */

// phpcs:ignoreFile.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Track_Orders_For_Woocommerce_With_FedEx' ) ) {

	/**
	 * This is class for tracking order With FedEx Services .
	 *
	 * @name    wps_Track_Your_Order_With_FedEx
	 */
	class Track_Orders_For_Woocommerce_With_FedEx {

		/**
		 * This is construct of class
		 *
		 * @link http://www.wpswings.com/
		 */
		public function __construct() {
			require_once( 'fedex-common.php5' );
			ini_set( 'soap.wsdl_cache_enabled', 0 );
			ini_set( 'soap.wsdl_cache_ttl', 0 );
		}

		/**
		 * Fedex request.
		 *
		 * @param integer $order_id contains order id.
		 * @return void
		 */
		public function fedex_request( $order_id ) {
			$request = array();
			$path_to_wsdl = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'integration/TrackService_v10.wsdl';
			$wps_tofw_fedex_tracking_enable = get_option( 'wps_tofw_enable_third_party_tracking_api', 'no' );

			$wps_fedex_track_number = get_post_meta( $order_id, 'wps_tofw_package_tracking_number', true );

			if ( isset( $wps_tofw_fedex_tracking_enable ) && ( 'on' == $wps_tofw_fedex_tracking_enable ) ) {

				$wps_user_key = get_option( 'wps_fedex_userkey', false );

				$wps_user_password = get_option( 'wps_fedex_userpassword', false );

				$wps_user_account_number = get_option( 'wps_fedex_account_number', false );

				$wps_user_meter_number = get_option( 'wps_fedex_meter_number', false );

				$order = wc_get_order( $order_id );
			}

			if ( ! empty( $order ) ) {
				$order_billing_details = $order->get_formatted_billing_address();
				$client = new SoapClient(
					$path_to_wsdl,
					array(

						'stream_context' => stream_context_create(
							array(
								'ssl' => array(
									'verify_peer' => false,
									'verify_peer_name' => false,
									'allow_self_signed' => true,
								),
							)
						),
					)
				);

				if ( ( isset( $wps_user_key ) && ! empty( $wps_user_key ) ) && ( isset( $wps_user_password ) && ! empty( $wps_user_password ) ) ) {
					$request['WebAuthenticationDetail'] = array(
						'UserCredential' => array(
							'Key' => getuserkey( $wps_user_key ),
							'Password' => getuserPass( $wps_user_password ),
						),
					);
				}

				if ( '' == $wps_fedex_track_number || null == $wps_fedex_track_number ) {?>

				<div class="wps_tofw_error_processing_transaction">
					<?php esc_html_e( 'Please provide Tracking number For track your package', 'track-orders-for-woocommerce' ); ?>
				</div>
					<?php
					return;
				} else {
					if ( ( isset( $wps_user_account_number ) && ! empty( $wps_user_account_number ) ) && ( isset( $wps_user_meter_number ) && ! empty( $wps_user_meter_number ) ) && ( isset( $wps_fedex_track_number ) && ! empty( $wps_fedex_track_number ) ) ) {

						$request['ClientDetail'] = array(
							'AccountNumber' => getAccountNumber( $wps_user_account_number ),
							'MeterNumber' => getMeterNumber( $wps_user_meter_number ),
						);
						$request['TransactionDetail'] = array( 'CustomerTransactionId' => '*** Track Request using PHP ***' );
						$request['Version'] = array(
							'ServiceId' => 'trck',
							'Major' => '10',
							'Intermediate' => '0',
							'Minor' => '0',
						);
						$request['SelectionDetails'] = array(
							'PackageIdentifier' => array(
								'Type' => 'TRACKING_NUMBER_OR_DOORTAG',
								'Value' => getTrackingNumber( $wps_fedex_track_number ),
							),
						);
						$request['ProcessingOptions'] = 'INCLUDE_DETAILED_SCANS';

					}
				}
				if ( is_array( $request ) && ! empty( $request ) ) {
					try {
						$response = $client->track( $request );

						$fedex_response = $response->CompletedTrackDetails;

						$wps_counter = 0;
						$f = 0;
						if ( isset( $fedex_response->TrackDetails->Events ) && ! empty( $fedex_response->TrackDetails->Events ) ) {
							$wps_fedex_total_event = count( $fedex_response->TrackDetails->Events );
							$f = 1;
							$fedex_tracking_details = array_reverse( $fedex_response->TrackDetails->Events );

							?>
						<div class="wps-tofw-main-data-wrapper-template-fedex">
							<div class="wps-tofw-order-tracking-section-template-fedex">
								<?php
								foreach ( $fedex_tracking_details as $one_event ) {

									$status = isset( $one_event->EventDescription ) ? $one_event->EventDescription : '';
									if ( 'Delivered' == $status ) {
										$wps_counter = 1;
									}
									?>
									<div class="wps-tooltip-template-fedex" id="wps-temp-tooltip_fedex">
										
										<p><?php esc_html_e( ' Your Order is ', 'track-orders-for-woocommerce' ); ?><?php echo esc_html( $one_event->EventDescription ); ?><?php esc_html_e( ' on', 'track-orders-for-woocommerce' ); ?></p>
										<span><?php echo esc_html( date_i18n( 'F d, g:i a', strtotime( $one_event->Timestamp ) ) ); ?></span>
									</div>
									<?php
								}
								?>
							</div>
							<div class="wps-tofw-order-progress-data-field-template-fedex">
								<div class="wps-tofw-small-circle1-template-fedex wps-tofw-circle-template-fedex">
									<div class="sub-circle-template-fedex wps-tofw-sub-circle1-template-fedex">
									</div>
								</div>
								<div class="wps-tofw-skill-template-fedex">
									<div class="wps-tofw-outer-template-fedex" data-progress="<?php echo esc_attr( $wps_fedex_total_event ); ?>" data-progress-bar-height="<?php echo esc_attr( $wps_fedex_total_event ); ?>" data-template_no="fedex">
										<div class="wps-tofw-inner-template-fedex" >
										</div>        
									</div>
								</div>
								<div class="wps-tofw-small-circle2-template-fedex wps-tofw-circle-template-fedex">
									<div class="wps-tofw-sub-circle-template-fedex wps-tofw-sub-circle2-template-fedex">
									</div>
								</div>
							</div>
						</div>
							<?php
						}

						if ( 1 != $f ) {
							?>
						<div class="wps_tofw_error_processing_transaction"><?php esc_html_e( 'Error in processing transaction', 'track-orders-for-woocommerce' ); ?></div>
							<?php
							return;
						}
						?>
					<section id="wps_tofw_wrapper_third_party">
						<div id="wps-tofw-main-wrapper">
							<?php
							if ( 'FAILURE' == $response->HighestSeverity && 'ERROR' == $response->HighestSeverity ) {
								?>
								<div class="wps_tofw_error_processing_transaction"><?php esc_html_e( 'Error in processing transaction', 'track-orders-for-woocommerce' ); ?></div>
								<?php
							}
							?>
						</div>
					</section>
						<?php

					} catch ( SoapFault $exception ) {
						?>
					<div class="wps_tofw_error_processing_transaction"><?php esc_html_e( 'Please enter correct orderId/Fedex tracking number', 'track-orders-for-woocommerce' ); ?></div>
						<?php
					}
				} else {
					?>
				<div class="wps_tofw_error_processing_transaction"><?php esc_html_e( 'Please provide credentials For tracking your package with fedex tracking services', 'track-orders-for-woocommerce' ); ?></div>
					<?php
				}
			} else {
				?>
			<div class="wps_tofw_error_processing_transaction"><?php esc_html_e( 'Service not avilable', 'track-orders-for-woocommerce' ); ?></div>
				<?php
			}
		}


		/**
		 * Function for error request.
		 *
		 * @return void
		 */
		public function wps_tofw_error_request() {
			?>
		<div class="wps_tofw_error_processing_transaction"><?php esc_html_e( 'Please Enter Correct OrderID and Tracking Number To Track Your Package', 'track-orders-for-woocommerce' ); ?></div>
			<?php
		}
	}
}
