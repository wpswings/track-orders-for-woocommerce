<?php
/**
 * 17TRACK (API v2.2) tracking provider - generic provider for 1000+ carriers.
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

if ( ! class_exists( 'Wps_Tofw_Tracking_Provider_17track' ) ) {

	/**
	 * Fetches shipment status from the 17TRACK tracking API.
	 *
	 * Tracking numbers must be registered with 17TRACK before their info can be
	 * read, so the first lookup of a number registers it and returns `pending`;
	 * subsequent lookups read the tracking info.
	 *
	 * @since 1.3.0
	 */
	class Wps_Tofw_Tracking_Provider_17track implements Wps_Tofw_Tracking_Provider {

		/**
		 * Option holding the 17TRACK API key.
		 *
		 * @since 1.3.0
		 */
		const API_KEY_OPTION = 'wps_tofw_carrier_sync_17track_api_key';

		/**
		 * 17TRACK error code returned when a number has not been registered yet.
		 *
		 * @since 1.3.0
		 */
		const ERROR_NOT_REGISTERED = -18019902;

		/**
		 * 17TRACK error code returned when a number is already registered.
		 *
		 * @since 1.3.0
		 */
		const ERROR_ALREADY_REGISTERED = -18019901;

		/**
		 * Provider id.
		 *
		 * @since 1.3.0
		 * @return string
		 */
		public function get_id() {
			return '17track';
		}

		/**
		 * Provider label.
		 *
		 * @since 1.3.0
		 * @return string
		 */
		public function get_label() {
			return __( '17TRACK (1000+ carriers)', 'track-orders-for-woocommerce' );
		}

		/**
		 * API key.
		 *
		 * @since 1.3.0
		 * @return string
		 */
		protected function get_api_key() {
			return trim( (string) get_option( self::API_KEY_OPTION, '' ) );
		}

		/**
		 * API base URL.
		 *
		 * @since 1.3.0
		 * @return string
		 */
		protected function get_api_base() {
			/**
			 * Filter the 17TRACK API base URL.
			 *
			 * @since 1.3.0
			 * @param string $base API base URL with trailing slash.
			 */
			return (string) apply_filters( 'wps_tofw_17track_api_base', 'https://api.17track.net/track/v2.2/' );
		}

		/**
		 * Whether an API key is set.
		 *
		 * @since 1.3.0
		 * @return bool
		 */
		public function is_configured() {
			return '' !== $this->get_api_key();
		}

		/**
		 * 17TRACK auto-detects the carrier, so every carrier is supported.
		 *
		 * @since 1.3.0
		 * @param string $carrier Carrier slug.
		 * @return bool
		 */
		public function supports( $carrier ) {
			return true;
		}

		/**
		 * Map plugin carrier slugs to 17TRACK carrier codes. Unknown carriers are auto-detected.
		 *
		 * @since 1.3.0
		 * @param string $carrier Carrier slug.
		 * @return int
		 */
		protected function get_carrier_code( $carrier ) {
			/**
			 * Filter the map of carrier slug => 17TRACK carrier code.
			 *
			 * @since 1.3.0
			 * @param array $codes Carrier codes.
			 */
			$codes   = (array) apply_filters(
				'wps_tofw_17track_carrier_codes',
				array(
					'dhl'         => 100001,
					'ups'         => 100002,
					'fedex'       => 100003,
					'usps'        => 21051,
					'canada_post' => 3041,
				)
			);
			$carrier = strtolower( (string) $carrier );
			return isset( $codes[ $carrier ] ) ? absint( $codes[ $carrier ] ) : 0;
		}

		/**
		 * Fetch the current status of a tracking number.
		 *
		 * @since 1.3.0
		 * @param string $carrier         Carrier slug.
		 * @param string $tracking_number Tracking number.
		 * @return array|WP_Error
		 */
		public function get_status( $carrier, $tracking_number ) {
			if ( ! $this->is_configured() ) {
				return new WP_Error( 'wps_tofw_17track_no_key', __( '17TRACK API key is missing.', 'track-orders-for-woocommerce' ) );
			}

			$entry = array( 'number' => $tracking_number );
			$code  = $this->get_carrier_code( $carrier );
			if ( $code ) {
				$entry['carrier'] = $code;
			}

			$data = $this->request( 'gettrackinfo', array( $entry ) );
			if ( is_wp_error( $data ) ) {
				return $data;
			}

			$accepted = $this->find_number( isset( $data['accepted'] ) ? $data['accepted'] : array(), $tracking_number );
			if ( $accepted ) {
				return $this->parse_track_info( isset( $accepted['track_info'] ) ? (array) $accepted['track_info'] : array() );
			}

			$rejected = $this->find_number( isset( $data['rejected'] ) ? $data['rejected'] : array(), $tracking_number );
			$error    = ( $rejected && isset( $rejected['error'] ) ) ? (array) $rejected['error'] : array();

			if ( $this->is_not_registered_error( $error ) ) {
				$registered = $this->register( $entry );
				if ( is_wp_error( $registered ) ) {
					return $registered;
				}
				// Registered now; 17TRACK needs some time to collect the info.
				return array(
					'status'     => 'pending',
					'raw_status' => 'Registered',
					'events'     => array(),
				);
			}

			$message = isset( $error['message'] ) ? sanitize_text_field( $error['message'] ) : __( 'Tracking number was rejected by 17TRACK.', 'track-orders-for-woocommerce' );
			return new WP_Error( 'wps_tofw_17track_rejected', $message );
		}

		/**
		 * Whether a rejected-number error means "register first".
		 *
		 * @since 1.3.0
		 * @param array $error Error array.
		 * @return bool
		 */
		protected function is_not_registered_error( $error ) {
			if ( isset( $error['code'] ) && self::ERROR_NOT_REGISTERED === (int) $error['code'] ) {
				return true;
			}
			$message = isset( $error['message'] ) ? strtolower( (string) $error['message'] ) : '';
			return false !== strpos( $message, 'register' ) && false === strpos( $message, 'already' );
		}

		/**
		 * Register a tracking number with 17TRACK (consumes 17TRACK quota once per number).
		 *
		 * @since 1.3.0
		 * @param array $entry Array with `number` and optional `carrier`.
		 * @return true|WP_Error
		 */
		protected function register( $entry ) {
			$data = $this->request( 'register', array( $entry ) );
			if ( is_wp_error( $data ) ) {
				return $data;
			}
			if ( $this->find_number( isset( $data['accepted'] ) ? $data['accepted'] : array(), $entry['number'] ) ) {
				return true;
			}
			$rejected = $this->find_number( isset( $data['rejected'] ) ? $data['rejected'] : array(), $entry['number'] );
			$error    = ( $rejected && isset( $rejected['error'] ) ) ? (array) $rejected['error'] : array();
			if ( isset( $error['code'] ) && self::ERROR_ALREADY_REGISTERED === (int) $error['code'] ) {
				return true;
			}
			$message = isset( $error['message'] ) ? sanitize_text_field( $error['message'] ) : __( 'Could not register the tracking number with 17TRACK.', 'track-orders-for-woocommerce' );
			return new WP_Error( 'wps_tofw_17track_register_failed', $message );
		}

		/**
		 * Find a tracking number entry in an accepted/rejected list.
		 *
		 * @since 1.3.0
		 * @param array  $items           List.
		 * @param string $tracking_number Number.
		 * @return array|null
		 */
		protected function find_number( $items, $tracking_number ) {
			if ( ! is_array( $items ) ) {
				return null;
			}
			foreach ( $items as $item ) {
				if ( is_array( $item ) && isset( $item['number'] ) && 0 === strcasecmp( (string) $item['number'], (string) $tracking_number ) ) {
					return $item;
				}
			}
			return null;
		}

		/**
		 * Perform an API request.
		 *
		 * @since 1.3.0
		 * @param string $endpoint Endpoint (register|gettrackinfo).
		 * @param array  $body     Request body.
		 * @return array|WP_Error The `data` part of the response.
		 */
		protected function request( $endpoint, $body ) {
			$response = wp_safe_remote_post(
				$this->get_api_base() . $endpoint,
				array(
					'timeout' => 20,
					'headers' => array(
						'17token'      => $this->get_api_key(),
						'Content-Type' => 'application/json',
					),
					'body'    => wp_json_encode( $body ),
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$http_code = (int) wp_remote_retrieve_response_code( $response );
			$json      = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 200 !== $http_code || ! is_array( $json ) ) {
				/* translators: %d: HTTP status code. */
				return new WP_Error( 'wps_tofw_17track_http', sprintf( __( '17TRACK API request failed (HTTP %d).', 'track-orders-for-woocommerce' ), $http_code ) );
			}

			if ( ! isset( $json['code'] ) || 0 !== (int) $json['code'] ) {
				$message = __( '17TRACK API returned an error.', 'track-orders-for-woocommerce' );
				if ( ! empty( $json['data']['errors'][0]['message'] ) ) {
					$message = sanitize_text_field( $json['data']['errors'][0]['message'] );
				}
				return new WP_Error( 'wps_tofw_17track_api', $message );
			}

			return isset( $json['data'] ) && is_array( $json['data'] ) ? $json['data'] : array();
		}

		/**
		 * Map a 17TRACK main status to a normalized status.
		 *
		 * @since 1.3.0
		 * @param string $status 17TRACK status (e.g. `InTransit`).
		 * @return string
		 */
		protected function normalize_status( $status ) {
			$map = array(
				'NotFound'           => 'pending',
				'InfoReceived'       => 'info_received',
				'InTransit'          => 'in_transit',
				'Expired'            => 'expired',
				'AvailableForPickup' => 'available_for_pickup',
				'OutForDelivery'     => 'out_for_delivery',
				'DeliveryFailure'    => 'failed_attempt',
				'Delivered'          => 'delivered',
				'Exception'          => 'exception',
			);
			return isset( $map[ $status ] ) ? $map[ $status ] : 'in_transit';
		}

		/**
		 * Parse the `track_info` block.
		 *
		 * @since 1.3.0
		 * @param array $info Track info.
		 * @return array
		 */
		protected function parse_track_info( $info ) {
			$raw_status = isset( $info['latest_status']['status'] ) ? (string) $info['latest_status']['status'] : 'NotFound';
			$events     = array();
			$seen       = array();

			$providers = isset( $info['tracking']['providers'] ) && is_array( $info['tracking']['providers'] ) ? $info['tracking']['providers'] : array();
			foreach ( $providers as $provider ) {
				if ( empty( $provider['events'] ) || ! is_array( $provider['events'] ) ) {
					continue;
				}
				foreach ( $provider['events'] as $event ) {
					$time_string = '';
					if ( ! empty( $event['time_utc'] ) ) {
						$time_string = $event['time_utc'];
					} elseif ( ! empty( $event['time_iso'] ) ) {
						$time_string = $event['time_iso'];
					}
					$time        = $time_string ? strtotime( $time_string ) : 0;
					$description = isset( $event['description'] ) ? (string) $event['description'] : '';
					$key         = $time . '|' . $description;
					if ( isset( $seen[ $key ] ) ) {
						continue;
					}
					$seen[ $key ] = true;

					$sub_status = isset( $event['sub_status'] ) ? (string) $event['sub_status'] : '';
					$main       = $sub_status ? strtok( $sub_status, '_' ) : '';

					$events[] = array(
						'time'        => $time ? $time : 0,
						'description' => $description,
						'location'    => isset( $event['location'] ) ? (string) $event['location'] : '',
						'status'      => $main ? $this->normalize_status( $main ) : '',
					);
				}
			}

			return array(
				'status'     => $this->normalize_status( $raw_status ),
				'raw_status' => $raw_status,
				'events'     => $events,
			);
		}
	}
}
