<?php
/**
 * FedEx tracking provider built on the existing FedEx integration credentials.
 *
 * @link  https://wpswings.com/
 * @since 1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes/carrier-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wps_Tofw_Tracking_Provider_Fedex' ) ) {

	/**
	 * Fetches shipment status from FedEx (Track Service v10, SOAP) using the
	 * credentials already configured under Shipping Services.
	 *
	 * @since 1.3.0
	 */
	class Wps_Tofw_Tracking_Provider_Fedex implements Wps_Tofw_Tracking_Provider {

		/**
		 * Provider id.
		 *
		 * @since 1.3.0
		 * @return string
		 */
		public function get_id() {
			return 'fedex';
		}

		/**
		 * Provider label.
		 *
		 * @since 1.3.0
		 * @return string
		 */
		public function get_label() {
			return __( 'FedEx', 'track-orders-for-woocommerce' );
		}

		/**
		 * Existing FedEx credentials.
		 *
		 * @since 1.3.0
		 * @return array
		 */
		protected function get_credentials() {
			return array(
				'key'      => (string) get_option( 'wps_fedex_userkey', '' ),
				'password' => (string) get_option( 'wps_fedex_userpassword', '' ),
				'account'  => (string) get_option( 'wps_fedex_account_number', '' ),
				'meter'    => (string) get_option( 'wps_fedex_meter_number', '' ),
			);
		}

		/**
		 * WSDL path shipped with the plugin.
		 *
		 * @since 1.3.0
		 * @return string
		 */
		protected function get_wsdl_path() {
			return TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'integration/TrackService_v10.wsdl';
		}

		/**
		 * Configured when FedEx tracking is enabled, all credentials are set and SOAP is available.
		 *
		 * @since 1.3.0
		 * @return bool
		 */
		public function is_configured() {
			if ( 'on' !== get_option( 'wps_tofw_enable_track_order_using_api', 'no' ) ) {
				return false;
			}
			if ( ! class_exists( 'SoapClient' ) || ! file_exists( $this->get_wsdl_path() ) ) {
				return false;
			}
			$credentials = $this->get_credentials();
			return '' !== $credentials['key'] && '' !== $credentials['password'] && '' !== $credentials['account'] && '' !== $credentials['meter'];
		}

		/**
		 * Supports FedEx shipments only.
		 *
		 * @since 1.3.0
		 * @param string $carrier Carrier slug.
		 * @return bool
		 */
		public function supports( $carrier ) {
			return 'fedex' === strtolower( (string) $carrier );
		}

		/**
		 * Fetch the current status of a FedEx tracking number.
		 *
		 * @since 1.3.0
		 * @param string $carrier         Carrier slug.
		 * @param string $tracking_number Tracking number.
		 * @return array|WP_Error
		 */
		public function get_status( $carrier, $tracking_number ) {
			if ( ! $this->is_configured() ) {
				return new WP_Error( 'wps_tofw_fedex_not_configured', __( 'FedEx credentials are missing or SOAP is not available.', 'track-orders-for-woocommerce' ) );
			}

			$credentials = $this->get_credentials();
			$request     = array(
				'WebAuthenticationDetail' => array(
					'UserCredential' => array(
						'Key'      => $credentials['key'],
						'Password' => $credentials['password'],
					),
				),
				'ClientDetail'            => array(
					'AccountNumber' => $credentials['account'],
					'MeterNumber'   => $credentials['meter'],
				),
				'TransactionDetail'       => array( 'CustomerTransactionId' => 'wps-tofw-carrier-sync' ),
				'Version'                 => array(
					'ServiceId'    => 'trck',
					'Major'        => '10',
					'Intermediate' => '0',
					'Minor'        => '0',
				),
				'SelectionDetails'        => array(
					'PackageIdentifier' => array(
						'Type'  => 'TRACKING_NUMBER_OR_DOORTAG',
						'Value' => $tracking_number,
					),
				),
				'ProcessingOptions'       => 'INCLUDE_DETAILED_SCANS',
			);

			try {
				$client   = new SoapClient(
					$this->get_wsdl_path(),
					array(
						'exceptions'         => true,
						'connection_timeout' => 20,
						'cache_wsdl'         => WSDL_CACHE_NONE,
					)
				);
				$response = $client->track( $request );
			} catch ( Exception $exception ) {
				return new WP_Error( 'wps_tofw_fedex_soap', sanitize_text_field( $exception->getMessage() ) );
			}

			if ( isset( $response->HighestSeverity ) && in_array( $response->HighestSeverity, array( 'FAILURE', 'ERROR' ), true ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				return new WP_Error( 'wps_tofw_fedex_error', $this->get_notification_message( $response ) );
			}

			$details = isset( $response->CompletedTrackDetails->TrackDetails ) ? $response->CompletedTrackDetails->TrackDetails : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( is_array( $details ) ) {
				$details = reset( $details );
			}
			if ( ! is_object( $details ) ) {
				return new WP_Error( 'wps_tofw_fedex_empty', __( 'FedEx returned no tracking details.', 'track-orders-for-woocommerce' ) );
			}

			if ( isset( $details->Notification->Severity ) && in_array( $details->Notification->Severity, array( 'FAILURE', 'ERROR' ), true ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				return new WP_Error( 'wps_tofw_fedex_error', isset( $details->Notification->Message ) ? sanitize_text_field( $details->Notification->Message ) : __( 'FedEx could not find this tracking number.', 'track-orders-for-woocommerce' ) ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}

			$raw_events = array();
			if ( isset( $details->Events ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$raw_events = is_array( $details->Events ) ? $details->Events : array( $details->Events ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}

			$events = array();
			foreach ( $raw_events as $event ) {
				$location_parts = array();
				foreach ( array( 'City', 'StateOrProvinceCode', 'CountryCode' ) as $part ) {
					if ( ! empty( $event->Address->$part ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$location_parts[] = $event->Address->$part; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					}
				}
				$type     = isset( $event->EventType ) ? (string) $event->EventType : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$events[] = array(
					'time'        => isset( $event->Timestamp ) ? (int) strtotime( $event->Timestamp ) : 0, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					'description' => isset( $event->EventDescription ) ? (string) $event->EventDescription : '', // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					'location'    => implode( ', ', $location_parts ),
					'status'      => $type ? $this->normalize_status( $type ) : '',
				);
			}

			$raw_status = isset( $details->StatusDetail->Code ) ? (string) $details->StatusDetail->Code : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( '' === $raw_status && ! empty( $raw_events ) && isset( $raw_events[0]->EventType ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$raw_status = (string) $raw_events[0]->EventType; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}

			return array(
				'status'     => '' === $raw_status ? 'pending' : $this->normalize_status( $raw_status ),
				'raw_status' => $raw_status,
				'events'     => $events,
			);
		}

		/**
		 * Map FedEx status / event codes to a normalized status.
		 *
		 * @since 1.3.0
		 * @param string $code FedEx code.
		 * @return string
		 */
		protected function normalize_status( $code ) {
			$map  = array(
				'OC' => 'info_received',
				'DL' => 'delivered',
				'OD' => 'out_for_delivery',
				'HL' => 'available_for_pickup',
				'HP' => 'available_for_pickup',
				'DE' => 'exception',
				'SE' => 'exception',
				'CA' => 'exception',
				'RS' => 'exception',
				'DY' => 'exception',
				'CD' => 'exception',
			);
			$code = strtoupper( (string) $code );
			return isset( $map[ $code ] ) ? $map[ $code ] : 'in_transit';
		}

		/**
		 * Extract a readable message from a FedEx reply.
		 *
		 * @since 1.3.0
		 * @param object $response SOAP response.
		 * @return string
		 */
		protected function get_notification_message( $response ) {
			$notifications = isset( $response->Notifications ) ? $response->Notifications : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( is_array( $notifications ) ) {
				$notifications = reset( $notifications );
			}
			if ( is_object( $notifications ) && ! empty( $notifications->Message ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				return sanitize_text_field( $notifications->Message ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			}
			return __( 'FedEx returned an error.', 'track-orders-for-woocommerce' );
		}
	}
}
