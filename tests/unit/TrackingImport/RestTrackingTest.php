<?php
/**
 * WPS-8396: REST endpoints GET/POST tofw-route/v1/orders/{id}/tracking.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\TrackingImport;

require_once __DIR__ . '/fixtures.php';

use Brain\Monkey\Functions;
use Mockery;
use Tests\Unit\TestCase;

/**
 * Tests for the tracking routes of Track_Orders_For_Woocommerce_Rest_Api.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RestTrackingTest extends TestCase {

	use TrackingFixtures;

	/**
	 * API instance.
	 *
	 * @var \Track_Orders_For_Woocommerce_Rest_Api
	 */
	private $api;

	/**
	 * Load the classes and stub WordPress.
	 */
	protected function set_up() {
		parent::set_up();
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-tracking-service.php' );
		$this->requirePluginFile( 'package/rest-api/class-track-orders-for-woocommerce-rest-api.php' );
		$this->stubTrackingEnvironment();
		$this->api = new \Track_Orders_For_Woocommerce_Rest_Api( 'track-orders-for-woocommerce', '1.0.0' );
	}

	/**
	 * Mock a WP_REST_Request with the given params.
	 *
	 * @param array $params Params.
	 * @return \Mockery\MockInterface
	 */
	private function request( array $params ) {
		$request = Mockery::mock( 'WP_REST_Request' );
		$request->shouldReceive( 'get_param' )->andReturnUsing(
			static function ( $key ) use ( $params ) {
				return array_key_exists( $key, $params ) ? $params[ $key ] : null;
			}
		);
		return $request;
	}

	/**
	 * The tracking route registers GET + POST with permission callbacks and required args.
	 */
	public function test_tracking_route_registration() {
		$routes = array();
		Functions\when( 'register_rest_route' )->alias(
			static function ( $namespace, $route, $args ) use ( &$routes ) {
				$routes[ $namespace . $route ] = $args;
			}
		);

		$this->api->wps_tofw_add_endpoint();

		$this->assertArrayHasKey( 'tofw-route/v1/orders/(?P<id>\d+)/tracking', $routes );
		$route = $routes['tofw-route/v1/orders/(?P<id>\d+)/tracking'];
		$this->assertSame( 'GET', $route[0]['methods'] );
		$this->assertSame( array( $this->api, 'wps_tofw_read_permission_check' ), $route[0]['permission_callback'] );
		$this->assertSame( 'POST', $route[1]['methods'] );
		$this->assertSame( array( $this->api, 'wps_tofw_write_permission_check' ), $route[1]['permission_callback'] );
		$this->assertTrue( $route[1]['args']['carrier']['required'] );
		$this->assertTrue( $route[1]['args']['tracking_number']['required'] );
		$this->assertSame( 'boolean', $route[1]['args']['notify_customer']['type'] );
	}

	/**
	 * Only shop managers (manage_woocommerce / edit_shop_orders) may read or write tracking.
	 */
	public function test_permission_checks() {
		$caps = array();
		Functions\when( 'current_user_can' )->alias(
			static function ( $cap ) use ( &$caps ) {
				return in_array( $cap, $caps, true );
			}
		);
		$request = $this->request( array( 'id' => 1 ) );

		foreach ( array( 'wps_tofw_read_permission_check', 'wps_tofw_write_permission_check' ) as $check ) {
			$caps   = array( 'read' );
			$result = $this->api->$check( $request );
			$this->assertInstanceOf( \WP_Error::class, $result, $check );
			$this->assertSame( 'tofw_rest_forbidden', $result->get_error_code() );
			$this->assertSame( array( 'status' => 401 ), $result->get_error_data() );

			$caps = array( 'edit_shop_orders' );
			$this->assertTrue( $this->api->$check( $request ), $check );
			$caps = array( 'manage_woocommerce' );
			$this->assertTrue( $this->api->$check( $request ), $check );
		}
	}

	/**
	 * POST adds tracking (source rest) and returns 201 with the shipment and the full list,
	 * including tracking saved earlier through the order screen.
	 */
	public function test_post_adds_tracking() {
		$this->mockTrackableOrder(
			array(
				'id'   => 30,
				'meta' => array( 'wps_tofw_package_tracking_number' => 'OLD-1', 'wps_tofw_selected_shipping_service' => 'USPS' ),
			)
		);

		$response = $this->api->wps_tofw_add_order_tracking_callback(
			$this->request(
				array(
					'id'              => '30',
					'carrier'         => 'UPS',
					'tracking_number' => '1ZREST',
					'ship_date'       => '2026-11-21',
					'status'          => 'completed',
					'notify_customer' => 'false',
				)
			)
		);

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 201, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( 30, $data['order_id'] );
		$this->assertSame( 'completed', $data['status'] );
		$this->assertSame( '1ZREST', $data['shipment']['tracking_number'] );
		$this->assertSame( 'rest', $data['shipment']['source'] );
		$this->assertSame( array( 'OLD-1', '1ZREST' ), array_column( $data['tracking'], 'tracking_number' ) );
		$this->assertCount( 0, $this->sent_mails, 'notify_customer=false skips the e-mail' );
	}

	/**
	 * Without notify_customer the e-mail is sent once per order.
	 */
	public function test_post_sends_email_once_by_default() {
		$this->mockTrackableOrder( array( 'id' => 31 ) );

		$this->api->wps_tofw_add_order_tracking_callback( $this->request( array( 'id' => 31, 'carrier' => 'UPS', 'tracking_number' => 'R1' ) ) );
		$this->api->wps_tofw_add_order_tracking_callback( $this->request( array( 'id' => 31, 'carrier' => 'UPS', 'tracking_number' => 'R2' ) ) );

		$this->assertCount( 1, $this->sent_mails );
	}

	/**
	 * Array items (REST format) create a partial shipment.
	 */
	public function test_post_with_items_array() {
		$this->mockTrackableOrder( array( 'id' => 32, 'items' => array( $this->mockLineItem( 9, 'MUG', 3, 'Mug' ) ) ) );

		$response = $this->api->wps_tofw_add_order_tracking_callback(
			$this->request( array( 'id' => 32, 'carrier' => 'UPS', 'tracking_number' => 'P1', 'items' => array( array( 'sku' => 'MUG', 'qty' => 2 ) ) ) )
		);
		$this->assertSame( array( array( 'item_id' => 9, 'sku' => 'MUG', 'name' => 'Mug', 'qty' => 2 ) ), $response->get_data()['shipment']['items'] );
	}

	/**
	 * Errors map to 404 (order), 409 (duplicate) and 400 (validation).
	 */
	public function test_post_error_statuses() {
		$this->mockTrackableOrder( array( 'id' => 33, 'meta' => array( 'wps_tofw_package_tracking_number' => 'DUP' ) ) );
		$this->mockTrackableOrder( array( 'id' => 34, 'get_type' => 'shop_order_refund' ) );

		$missing = $this->api->wps_tofw_add_order_tracking_callback( $this->request( array( 'id' => 999, 'carrier' => 'UPS', 'tracking_number' => 'X' ) ) );
		$this->assertSame( 'tofw_order_not_found', $missing->get_error_code() );
		$this->assertSame( 404, $missing->get_error_data()['status'] );

		$refund = $this->api->wps_tofw_add_order_tracking_callback( $this->request( array( 'id' => 34, 'carrier' => 'UPS', 'tracking_number' => 'X' ) ) );
		$this->assertSame( 404, $refund->get_error_data()['status'] );

		$dup = $this->api->wps_tofw_add_order_tracking_callback( $this->request( array( 'id' => 33, 'carrier' => 'UPS', 'tracking_number' => 'dup' ) ) );
		$this->assertSame( 'tofw_tracking_invalid', $dup->get_error_code() );
		$this->assertSame( 409, $dup->get_error_data()['status'] );

		$bad = $this->api->wps_tofw_add_order_tracking_callback( $this->request( array( 'id' => 33, 'carrier' => 'UPS', 'tracking_number' => 'NEW', 'ship_date' => '21-11-2026', 'status' => 'nope' ) ) );
		$this->assertSame( 400, $bad->get_error_data()['status'] );
		$this->assertEqualsCanonicalizing( array( 'wps_tofw_invalid_ship_date', 'wps_tofw_invalid_status' ), $bad->get_error_data()['errors'] );
		$this->assertSame( 'DUP', $this->getOrderMeta( 33, 'wps_tofw_package_tracking_number' ), 'nothing saved on error' );
	}

	/**
	 * GET lists the shipments (legacy tracking included) or returns 404.
	 */
	public function test_get_tracking() {
		$this->mockTrackableOrder(
			array(
				'id'     => 35,
				'status' => 'completed',
				'meta'   => array(
					'wps_tofw_package_tracking_number' => 'L1',
					'wps_tofw_tracking_shipments'      => array( array( 'id' => 's1', 'carrier' => 'ups', 'tracking_number' => 'S1', 'ship_date' => '2026-11-01' ) ),
				),
			)
		);

		$response = $this->api->wps_tofw_get_order_tracking_callback( $this->request( array( 'id' => 35 ) ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'completed', $response->get_data()['status'] );
		$this->assertSame( array( 'L1', 'S1' ), array_column( $response->get_data()['tracking'], 'tracking_number' ) );

		$missing = $this->api->wps_tofw_get_order_tracking_callback( $this->request( array( 'id' => 404 ) ) );
		$this->assertSame( 404, $missing->get_error_data()['status'] );
	}
}
