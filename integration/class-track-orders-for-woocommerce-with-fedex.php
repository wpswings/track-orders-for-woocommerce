<?php
/**
 * Enable api.
 *
 * @version  1.0.0
 * @package  Woocommece_Order_Tracker/Include
 */

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
		 * Canada post request.
		 *
		 * @return void
		 */
		public function canadapost_request() {
			$wps_tofw_canadapost_tracking_enable = get_option( 'wps_tofw_enable_canadapost_tracking', 'no' );

			$wps_tofw_canadapost_userkey = get_option( 'wps_tofw_canadapost_tracking_user_key', false );
			$wps_tofw_canadapost_password = get_option( 'wps_tofw_canadapost_tracking_user_password', false );

			$wps_tofw_order_id = get_option( 'wps_tofw_user_order_id', false );
			$wps_tofw_pin_no = get_post_meta( $wps_tofw_order_id, 'wps_tofw_package_tracking_number', true );

			if ( isset( $wps_tofw_canadapost_tracking_enable ) && ( 'yes' == $wps_tofw_canadapost_tracking_enable ) ) {
				$user_order = wc_get_order( $wps_tofw_order_id );
			}

			if ( ! empty( $user_order ) ) {
				$order_billing_details = $user_order->get_formatted_billing_address();
			}
			if ( ( isset( $wps_tofw_canadapost_userkey ) && ( ! empty( $wps_tofw_canadapost_userkey ) || '' != $wps_tofw_canadapost_userkey ) && ( isset( $wps_tofw_canadapost_password ) && ( ! empty( $wps_tofw_canadapost_password ) || '' != $wps_tofw_canadapost_password ) ) ) ) {

				$wsdl = realpath( dirname( isset( $_SERVER['SCRIPT_FILENAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) ) : '' ) ) . '/wp-content/plugins/track-orders-for-woocommerce/integration/wsdl/track.wsdl';

				$host_name = 'ct.soa-gw.canadapost.ca';

				// SOAP URI.
				$location = 'https://' . $host_name . '/vis/soap/track';

				// SSL Options.
				$opts = array(
					'ssl' =>
										array(
											'verify_peer' => false,
											'verify_peer_name' => false,
											'cafile' => realpath( dirname( isset( $_SERVER['SCRIPT_FILENAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_FILENAME'] ) ) : '' ) ) . '/wp-content/plugins/woocommerce-order-tracker/third-party/cert/cacert.pem',

										),
				);

				$ctx = stream_context_create( $opts );
				$client = new SoapClient(
					$wsdl,
					array(
						'location' => $location,
						'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
						'stream_context' => $ctx,
					)
				);

				// Set WS Security username_token.
				$w_s_s_e_n_s = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
				$username_token = new stdClass();
				$username_token->Username = new SoapVar( $wps_tofw_canadapost_userkey, XSD_STRING, null, null, null, $w_s_s_e_n_s );
				$username_token->Password = new SoapVar( $wps_tofw_canadapost_password, XSD_STRING, null, null, null, $w_s_s_e_n_s );

				$content = new stdClass();
				$content->username_token = new SoapVar( $username_token, SOAP_ENC_OBJECT, null, null, null, $w_s_s_e_n_s );
				$header = new SOAPHeader( $w_s_s_e_n_s, 'Security', $content );
				$client->__setSoapHeaders( $header );

				try {

					// Execute Request.
					if ( isset( $wps_tofw_pin_no ) && ! empty( $wps_tofw_pin_no ) ) {
						$result = $client->__soapCall(
							'GetTrackingDetail',
							array(
								'get-tracking-detail-request' => array(
									'locale'    => 'FR',
									// PIN or DNC Choice.
																'pin'       => $wps_tofw_pin_no,

								),
							),
							null,
							null
						);

						if ( isset( $result->{'tracking-detail'} ) && ! empty( $result->{'tracking-detail'} ) ) {
							foreach ( $result->{'tracking-detail'} as $tracking_kety => $tracking_value ) {
								if ( 'significant-events' == $tracking_kety ) {
									if ( isset( $tracking_value->{'occurrence'} ) && ! empty( $tracking_value->{'occurrence'} ) ) {

										$wps_tofw_progressbar_width = count( $tracking_value->{'occurrence'} );
									}
								}
							}
							if ( isset( $wps_tofw_progressbar_width ) && ! empty( $wps_tofw_progressbar_width ) ) {
								$wps_tofw_width = ( 100 / 13 ) * $wps_tofw_progressbar_width;

							} else {

								$wps_tofw_width = 10;
							}
							?>

							<?php
							foreach ( $result->{'tracking-detail'} as $tracking_kety => $tracking_value ) {
								if ( 'significant-events' == $tracking_kety ) {
									if ( isset( $tracking_value->{'occurrence'} ) && ! empty( $tracking_value->{'occurrence'} ) ) {
										$wps_tofw_progressbar_width = count( $tracking_value->{'occurrence'} );
										?>
									<div class="wps-tofw-main-data-wrapper-canadapost">
										<div class="wps-tofw-order-progress-data-field-template-canadapost">
											<div class="wps-tofw-small-circle1-template-canadapost wps-tofw-circle-template-canadapost">
												<div class="wps-tofw-sub-circle-template-canadapost wps-tofw-sub-circle1-template-canadapost">
												</div>
											</div>
											<div class="wps-tofw-skill-template-canadapost">
												<div class="wps-tofw-outer-template-canadapost" data-progress="<?php echo count( $tracking_value->{'occurrence'} ); ?>" data-progress-bar-height="<?php echo count( $tracking_value->{'occurrence'} ); ?>" data-template_no="canadapost">
													<div class="wps-tofw-inner-template-canadapost" ></div>        
												</div>
											</div>
											<div class="wps-tofw-small-circle2-template-canadapost wps-tofw-circle-template-canadapost">
												<div class="wps-tofw-sub-circle-template-canadapost wps-tofw-sub-circle2-template-canadapost">
												</div>
											</div>
										</div>
										<div class="wps-tofw-order-tracking-section-canadapost">
											<?php
											foreach ( $tracking_value->{'occurrence'} as $occurence_key => $occurence_value ) {
												?>
												<div class="wps-tooltip-canadapost">
													<h4><?php echo esc_html( $occurence_value->{'event-description'} ); ?></h4>
													<p><?php esc_html_e( 'your order reached at ', 'track-orders-for-woocommerce' ); ?></p>
													<p><?php echo esc_html( $occurence_value->{'event-site'} ); ?></p>
													<span><?php echo esc_html( date_i18n( 'F d', strtotime( $occurence_value->{'event-date'} ) ) ); ?><?php echo ' ' . esc_html( date_i18n( 'Y h:i', strtotime( $occurence_value->{'event-time'} ) ) ); ?></span>
												</div>
												<?php
											}
											?>
										</div>
									</div>
										<?php
									}
								}
							}
						} else {
							?>
						<div class="wps_tofw_error_tracking_message">
							<h4><?php esc_html_e( 'Service Unavailable', 'track-orders-for-woocommerce' ); ?></h4>
						</div>
							<?php
						}
					} else {
						?>
					<div class="wps_tofw_error_transaction"><?php esc_html_e( 'Please provide tracking number For tracking your package with canada post tracking services', 'track-orders-for-woocommerce' ); ?></div>
						<?php
					}
				} catch ( SoapFault $exception ) {
					echo 'Fault Code: ' . esc_html( trim( $exception->faultcode ) ) . "\n";
					echo 'Fault Reason: ' . esc_html( trim( $exception->getMessage() ) ) . "\n";
					echo '<h3>Enter your canada post authorization details correctly</h3>';
				}
			} else {
				?>
			<div class="wps_tofw_error_processing_transaction"><?php esc_html_e( 'Please provide credentials For tracking your package with canada post tracking services', 'track-orders-for-woocommerce' ); ?></div>
				<?php
			}
		}

		/**
		 * Function for handling usps request.
		 *
		 * @param int $order_id is the id of order.
		 * @return void
		 */
		public function wps_tofw_usps_tracking_request( $order_id ) {

			$wps_tofw_usps_user_key = get_option( 'wps_tofw_usps_tracking_user_key', false );
			$wps_tofw_usps_user_password = get_option( 'wps_tofw_usps_tracking_user_password', false );
			$wps_tofw_order_tracking_no = get_post_meta( $order_id, 'wps_tofw_package_tracking_number', true );

			if ( ( isset( $wps_tofw_usps_user_key ) && isset( $wps_tofw_order_tracking_no ) ) && ( ' ' != $wps_tofw_usps_user_key && ' ' != $wps_tofw_order_tracking_no ) ) {

				$wps_tofw_xml_response = simplexml_load_file( 'http://production.shippingapis.com/ShippingAPI.dll?API=TrackV2&XML=<TrackRequest USERID="' . $wps_tofw_usps_user_key . '"><TrackID ID="' . $wps_tofw_order_tracking_no . '"></TrackID></TrackRequest>' ) || die( 'Error' );

				$wps_tofw_xml_response_to_array = json_decode( json_encode( $wps_tofw_xml_response ), 1 );

				if ( is_array( $wps_tofw_xml_response_to_array ) && ! empty( $wps_tofw_xml_response_to_array ) ) {
					foreach ( $wps_tofw_xml_response_to_array as $xml_key => $xml_value ) {
						if ( is_array( $xml_value ) && ! empty( $xml_value ) ) {
							if ( array_key_exists( 'TrackDetail', $xml_value ) ) {

								$wps_tofw_usps_track_details = $xml_value['TrackDetail'];
							} else {
								$wps_tofw_usps_track_details = $xml_value['TrackSummary'];
							}
						}
					}
				}
				if ( is_array( $wps_tofw_usps_track_details ) && ! empty( $wps_tofw_usps_track_details ) ) {

					$wps_tofw_usps_track_details = array_reverse( $wps_tofw_usps_track_details );
					$wps_tofw_usps_place = '';
					$wps_tofw_place_msg = __( ' On ', 'track-orders-for-woocommerce' );
					?>
				<div class="wps-tofw-main-data-wrapper-usps">
					<div class="wps-tofw-order-progress-data-field-template-usps">
						<div class="wps-tofw-small-circle1-template-usps wps-tofw-circle-template-usps">
							<div class="wps-tofw-sub-circle-template-usps wps-tofw-sub-circle1-template-usps">
							</div>
						</div>
						<div class="wps-tofw-skill-template-usps">
							<div class="wps-tofw-outer-template-usps" data-progress="<?php echo esc_attr( count( $wps_tofw_usps_track_details ) ); ?>" data-progress-bar-height="<?php echo esc_attr( count( $wps_tofw_usps_track_details ) ); ?>" data-template_no="usps">
								<div class="wps-tofw-inner-template-usps" ></div>        
							</div>
						</div>
						<div class="wps-tofw-small-circle2-template-usps wps-tofw-circle-template-usps">
							<div class="wps-tofw-sub-circle-template-usps wps-tofw-sub-circle2-template-usps">
							</div>
						</div>
					</div>
					<div class="wps-tofw-order-tracking-section-usps">
						<?php
						foreach ( $wps_tofw_usps_track_details as $usps_keys => $usps_values ) {
							$usps_values = explode( ',', $usps_values );
							$wps_tofw_usps_status = $usps_values[0];
							$wps_tofw_usps_date = $usps_values[1] . $usps_values[2];
							$wps_tofw_usps_time = $usps_values[3];
							unset( $usps_values[0] );
							unset( $usps_values[1] );
							unset( $usps_values[2] );
							unset( $usps_values[3] );
							foreach ( $usps_values as $keys1 => $values1 ) {

								$wps_tofw_usps_place .= $values1;
							}
							?>
							<div class="wps-tooltip-usps">
								<h4><?php echo esc_html( $wps_tofw_usps_status ); ?></h4>
								<p><?php esc_html_e( 'your order reached at ', 'track-orders-for-woocommerce' ); ?></p>
								<p><?php echo esc_html( $wps_tofw_usps_place ) . esc_html( $wps_tofw_place_msg ); ?></p>
								<span><?php echo esc_html( $wps_tofw_usps_date ) . esc_html( $wps_tofw_usps_time ); ?></span>
							</div>
							<?php
							$wps_tofw_usps_place = '';
						}

						?>
					</div>
				</div>
					<?php

				} else {
					echo esc_html( $wps_tofw_usps_track_details );
				}
			} else {
				?>
			<div class="wps_tofw_error_processing_transaction"><?php esc_html_e( 'UserID Or Tracking Number Not Found', 'track-orders-for-woocommerce' ); ?></div>
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
