<?php
/**
 * 17TRACK provider: status mapping and API error handling (WPS-8397).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\CarrierSync;

use Brain\Monkey\Functions;

require_once __DIR__ . '/CarrierSyncTestSupport.php';

/**
 * Tests for Wps_Tofw_Tracking_Provider_17track.
 */
class SeventeenTrackProviderTest extends CarrierSyncTestCase {

	/**
	 * Requests sent to wp_safe_remote_post(): [ url, args ].
	 *
	 * @var array
	 */
	private $requests = array();

	/**
	 * Queued fake HTTP responses ( [ code, body ] ).
	 *
	 * @var array
	 */
	private $responses = array();

	protected function set_up() {
		parent::set_up();
		$this->setOptions( array( \Wps_Tofw_Tracking_Provider_17track::API_KEY_OPTION => 'test-key' ) );

		$requests  = &$this->requests;
		$responses = &$this->responses;
		Functions\when( 'wp_safe_remote_post' )->alias(
			static function ( $url, $args ) use ( &$requests, &$responses ) {
				$requests[] = array( $url, $args );
				$next       = array_shift( $responses );
				if ( $next instanceof \WP_Error ) {
					return $next;
				}
				return array(
					'response' => array( 'code' => $next[0] ),
					'body'     => is_string( $next[1] ) ? $next[1] : json_encode( $next[1] ),
				);
			}
		);
		Functions\when( 'wp_remote_retrieve_response_code' )->alias(
			static function ( $response ) {
				return $response['response']['code'];
			}
		);
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static function ( $response ) {
				return $response['body'];
			}
		);
	}

	/**
	 * A successful gettrackinfo reply for one number.
	 *
	 * @param string $number Tracking number.
	 * @param string $status 17TRACK main status.
	 * @param array  $events Provider events.
	 * @return array
	 */
	private function trackInfo( $number, $status, $events = array() ) {
		return array(
			'code' => 0,
			'data' => array(
				'accepted' => array(
					array(
						'number'     => $number,
						'track_info' => array(
							'latest_status' => array( 'status' => $status ),
							'tracking'      => array( 'providers' => array( array( 'events' => $events ) ) ),
						),
					),
				),
				'rejected' => array(),
			),
		);
	}

	public function statusMapProvider() {
		return array(
			'in transit'       => array( 'InTransit', 'in_transit' ),
			'out for delivery' => array( 'OutForDelivery', 'out_for_delivery' ),
			'delivered'        => array( 'Delivered', 'delivered' ),
			'exception'        => array( 'Exception', 'exception' ),
			'label created'    => array( 'InfoReceived', 'info_received' ),
			'failed attempt'   => array( 'DeliveryFailure', 'failed_attempt' ),
			'pickup'           => array( 'AvailableForPickup', 'available_for_pickup' ),
			'expired'          => array( 'Expired', 'expired' ),
			'not found'        => array( 'NotFound', 'pending' ),
			'unknown status'   => array( 'SomethingNew', 'in_transit' ),
		);
	}

	/**
	 * @dataProvider statusMapProvider
	 *
	 * @param string $raw      17TRACK status.
	 * @param string $expected Normalized status.
	 */
	public function test_maps_17track_status_to_normalized_status( $raw, $expected ) {
		$this->responses[] = array( 200, $this->trackInfo( 'TRK1', $raw ) );

		$result = ( new \Wps_Tofw_Tracking_Provider_17track() )->get_status( 'ups', 'TRK1' );

		$this->assertSame( $expected, $result['status'] );
		$this->assertSame( $raw, $result['raw_status'] );
	}

	public function test_sends_api_key_carrier_code_and_number() {
		$this->responses[] = array( 200, $this->trackInfo( 'TRK1', 'InTransit' ) );

		( new \Wps_Tofw_Tracking_Provider_17track() )->get_status( 'ups', 'TRK1' );

		$this->assertCount( 1, $this->requests );
		list( $url, $args ) = $this->requests[0];
		$this->assertSame( 'https://api.17track.net/track/v2.2/gettrackinfo', $url );
		$this->assertSame( 'test-key', $args['headers']['17token'] );
		$this->assertSame( array( array( 'number' => 'TRK1', 'carrier' => 100002 ) ), json_decode( $args['body'], true ) );
	}

	public function test_unknown_carrier_is_auto_detected() {
		$this->responses[] = array( 200, $this->trackInfo( 'TRK1', 'InTransit' ) );

		( new \Wps_Tofw_Tracking_Provider_17track() )->get_status( 'some_local_courier', 'TRK1' );

		$this->assertSame( array( array( 'number' => 'TRK1' ) ), json_decode( $this->requests[0][1]['body'], true ) );
	}

	public function test_parses_events_and_drops_duplicates() {
		$events            = array(
			array(
				'time_utc'    => '2026-01-02T10:00:00Z',
				'description' => 'Out for delivery',
				'location'    => 'Berlin',
				'sub_status'  => 'OutForDelivery_Other',
			),
			array(
				'time_utc'    => '2026-01-02T10:00:00Z',
				'description' => 'Out for delivery',
			),
			array(
				'time_iso'    => '2026-01-01T08:00:00+00:00',
				'description' => 'Departed facility',
				'sub_status'  => 'InTransit_PickedUp',
			),
		);
		$this->responses[] = array( 200, $this->trackInfo( 'TRK1', 'OutForDelivery', $events ) );

		$result = ( new \Wps_Tofw_Tracking_Provider_17track() )->get_status( '', 'TRK1' );

		$this->assertCount( 2, $result['events'] );
		$this->assertSame(
			array(
				'time'        => strtotime( '2026-01-02T10:00:00Z' ),
				'description' => 'Out for delivery',
				'location'    => 'Berlin',
				'status'      => 'out_for_delivery',
			),
			$result['events'][0]
		);
		$this->assertSame( 'in_transit', $result['events'][1]['status'] );
	}

	public function test_unregistered_number_is_registered_and_reported_pending() {
		$this->responses[] = array(
			200,
			array(
				'code' => 0,
				'data' => array(
					'accepted' => array(),
					'rejected' => array(
						array(
							'number' => 'TRK1',
							'error'  => array(
								'code'    => \Wps_Tofw_Tracking_Provider_17track::ERROR_NOT_REGISTERED,
								'message' => 'The tracking number is not registered.',
							),
						),
					),
				),
			),
		);
		$this->responses[] = array(
			200,
			array(
				'code' => 0,
				'data' => array(
					'accepted' => array( array( 'number' => 'TRK1' ) ),
					'rejected' => array(),
				),
			),
		);

		$result = ( new \Wps_Tofw_Tracking_Provider_17track() )->get_status( '', 'TRK1' );

		$this->assertSame( 'pending', $result['status'] );
		$this->assertCount( 2, $this->requests );
		$this->assertStringEndsWith( '/register', $this->requests[1][0] );
	}

	public function test_rejected_number_returns_error() {
		$this->responses[] = array(
			200,
			array(
				'code' => 0,
				'data' => array(
					'accepted' => array(),
					'rejected' => array(
						array(
							'number' => 'TRK1',
							'error'  => array(
								'code'    => -18010012,
								'message' => 'Invalid number <b>format</b>',
							),
						),
					),
				),
			),
		);

		$result = ( new \Wps_Tofw_Tracking_Provider_17track() )->get_status( '', 'TRK1' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'wps_tofw_17track_rejected', error_code( $result ) );
		$this->assertSame( 'Invalid number format', $result->get_error_message() );
	}

	public function test_http_error_returns_wp_error() {
		$this->responses[] = array( 500, 'Internal Server Error' );

		$result = ( new \Wps_Tofw_Tracking_Provider_17track() )->get_status( '', 'TRK1' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'wps_tofw_17track_http', error_code( $result ) );
	}

	public function test_api_error_code_returns_wp_error() {
		$this->responses[] = array(
			200,
			array(
				'code' => -18010002,
				'data' => array( 'errors' => array( array( 'message' => 'Invalid access key' ) ) ),
			),
		);

		$result = ( new \Wps_Tofw_Tracking_Provider_17track() )->get_status( '', 'TRK1' );

		$this->assertSame( 'wps_tofw_17track_api', error_code( $result ) );
		$this->assertSame( 'Invalid access key', $result->get_error_message() );
	}

	public function test_transport_error_is_passed_through() {
		$this->responses[] = new \WP_Error( 'http_request_failed', 'timed out' );

		$result = ( new \Wps_Tofw_Tracking_Provider_17track() )->get_status( '', 'TRK1' );

		$this->assertSame( 'http_request_failed', error_code( $result ) );
	}

	public function test_without_api_key_it_is_not_configured_and_makes_no_request() {
		$this->setOptions( array( \Wps_Tofw_Tracking_Provider_17track::API_KEY_OPTION => '  ' ) );
		$provider = new \Wps_Tofw_Tracking_Provider_17track();

		$this->assertFalse( $provider->is_configured() );
		$result = $provider->get_status( '', 'TRK1' );
		$this->assertSame( 'wps_tofw_17track_no_key', error_code( $result ) );
		$this->assertSame( array(), $this->requests );
	}
}
