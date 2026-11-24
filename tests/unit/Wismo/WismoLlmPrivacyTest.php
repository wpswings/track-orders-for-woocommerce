<?php
/**
 * WPS-8403: minimal order context, optional LLM mode and API key secrecy of the WISMO assistant.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Wismo;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Mockery;

/**
 * build_order_context(), llm_answer() (through ajax_ask()), enqueue_assets() and settings_fields().
 */
class WismoLlmPrivacyTest extends WismoTestCase {

	const API_KEY = 'sk-test-SECRET-123456';

	/**
	 * Enable LLM mode.
	 *
	 * @param array $extra Extra options.
	 */
	private function enableLlm( array $extra = array() ) {
		$this->setOptions(
			array_merge(
				array(
					'wps_tofw_wismo_llm_enable'   => 'on',
					'wps_tofw_wismo_llm_api_key'  => self::API_KEY,
					'wps_tofw_wismo_llm_endpoint' => 'https://llm.example.test/v1/chat/completions',
					'wps_tofw_wismo_llm_model'    => 'test-model',
				),
				$extra
			)
		);
	}

	/**
	 * Verify order 701 and ask a question.
	 *
	 * @param string $question Question.
	 * @return JsonResponse
	 */
	private function verifyAndAsk( $question ) {
		$this->wismoOrder(
			array(
				'id'   => 701,
				'meta' => array(
					'wps_tofw_package_tracking_number' => 'TRK701',
					'wps_tofw_selected_shipping_service' => 'usps',
				),
			)
		);
		$token = $this->verifyByEmail( 701, 'buyer@example.com' )->data['token'];
		return $this->ajax(
			'ajax_ask',
			array(
				'token'    => $token,
				'question' => $question,
			)
		);
	}

	public function test_order_context_contains_only_minimal_non_personal_data() {
		$this->setOptions( array( 'tofw_enable_track_order' => 'on', 'wps_tofw_tracking_page' => array( 'pages' => array( 'wps_guest_track_order_page' => 33 ) ) ) );
		$order = $this->wismoOrder(
			array(
				'id'   => 702,
				'meta' => array(
					'wps_tofw_selected_shipping_service' => 'canada_post',
					'wps_tofw_enhanced_tracking_no'      => 'CP-1',
					'wps_tofw_estimated_delivery_date'   => '2026-12-01',
					'wps_tofw_estimated_delivery_time'   => '10am',
					'wps_tofw_carrier_status'            => 'out_for_delivery',
					'wps_tofw_carrier_status_updated'    => 1790000000,
					'wps_track_order_onchange_time'      => array( 'wc-processing' => 'Nov 20, 2026 9:00' ),
				),
			)
		);

		$context = $this->wismo()->build_order_context( $order );

		$this->assertSame(
			array( 'order_number', 'status', 'status_label', 'status_updated', 'date_created', 'date_completed', 'items', 'ship_city', 'ship_country', 'carrier', 'tracking_number', 'tracking_url', 'tracking_page_url', 'estimated_delivery', 'carrier_status', 'carrier_status_label', 'carrier_status_updated' ),
			array_keys( $context )
		);
		$this->assertSame( 'Canada Post', $context['carrier'] );
		$this->assertSame( 'CP-1', $context['tracking_number'], 'Falls back to the enhanced tracking number.' );
		$this->assertStringStartsWith( 'https://www.canadapost-postescanada.ca/', $context['tracking_url'] );
		// The estimated delivery date feature (WPS-8400) formats the manual date.
		$this->assertSame( 'December 1, 2026 10am', $context['estimated_delivery'] );
		$this->assertSame( 'out_for_delivery', $context['carrier_status'] );
		$this->assertSame( 'Out for delivery', $context['carrier_status_label'] );
		$this->assertSame( 'November 21, 2026 10:00', $context['carrier_status_updated'] );
		$this->assertSame( 'Nov 20, 2026 9:00', $context['status_updated'] );
		$this->assertSame( array( array( 'name' => 'Blue Shirt', 'qty' => 2 ) ), $context['items'] );
		$this->assertSame( 'Pune', $context['ship_city'] );
		$this->assertSame( 'https://shop.test/page-33/', $context['tracking_page_url'] );

		$json = strtolower( wp_json_encode( $context ) );
		foreach ( array( 'buyer@example.com', 'jane', 'secret', '555-0100', 'hidden street' ) as $personal ) {
			$this->assertStringNotContainsString( $personal, $json );
		}
	}

	public function test_llm_disabled_never_calls_the_api() {
		$this->setOptions( array( 'wps_tofw_wismo_llm_api_key' => self::API_KEY ) );
		Functions\expect( 'wp_remote_post' )->never();

		$response = $this->verifyAndAsk( 'Where is my order?' );

		$this->assertSame( 'rules', $response->data['source'] );
	}

	public function test_llm_receives_only_the_minimal_context_and_the_question() {
		$this->enableLlm();
		$this->http_response = array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode( array( 'choices' => array( array( 'message' => array( 'content' => '<b>It ships soon.</b>' ) ) ) ) ),
		);

		$response = $this->verifyAndAsk( 'Where is my order?' );

		$this->assertTrue( $response->success );
		$this->assertSame( 'llm', $response->data['source'] );
		$this->assertSame( 'It ships soon.', $response->data['text'] );

		$this->assertCount( 1, $this->http_requests );
		list( $url, $args ) = $this->http_requests[0];
		$this->assertSame( 'https://llm.example.test/v1/chat/completions', $url );
		$this->assertSame( 'Bearer ' . self::API_KEY, $args['headers']['Authorization'] );

		$body = json_decode( $args['body'], true );
		$this->assertSame( 'test-model', $body['model'] );
		$this->assertSame( 'system', $body['messages'][0]['role'] );
		$this->assertStringNotContainsString( self::API_KEY, $args['body'] );

		$user = $body['messages'][1]['content'];
		$this->assertStringEndsWith( 'Customer question: Where is my order?', $user );
		$this->assertSame( 1, preg_match( '/^Order data: (\{.*\})\n\nCustomer question:/s', $user, $m ) );
		$sent = json_decode( $m[1], true );
		$this->assertSame(
			array( 'order_number', 'status', 'status_updated', 'order_date', 'completed_date', 'items', 'ship_to_city', 'carrier', 'tracking_number', 'tracking_url', 'estimated_delivery', 'carrier_status', 'carrier_updated', 'support_email', 'support_url' ),
			array_keys( $sent )
		);
		$this->assertSame( '701', $sent['order_number'] );
		$this->assertSame( 'TRK701', $sent['tracking_number'] );
		$this->assertSame( 'Pune, IN', $sent['ship_to_city'] );
		foreach ( array( 'buyer@example.com', 'Jane', 'Secret', '555-0100', 'Hidden Street' ) as $personal ) {
			$this->assertStringNotContainsString( $personal, $user );
		}
	}

	public function test_llm_failure_falls_back_to_the_rules_answer() {
		$this->enableLlm();
		$this->http_response = Mockery::mock( 'WP_Error' );

		$response = $this->verifyAndAsk( 'Where is my order?' );

		$this->assertCount( 1, $this->http_requests );
		$this->assertSame( 'rules', $response->data['source'] );
		$this->assertStringContainsString( 'Order #701 is being processed', $response->data['text'] );
	}

	public function test_llm_non_200_or_malformed_body_falls_back() {
		$this->enableLlm();
		$this->http_response = array(
			'response' => array( 'code' => 500 ),
			'body'     => '{"choices":[{"message":{"content":"should not be used"}}]}',
		);

		$this->assertSame( 'rules', $this->verifyAndAsk( 'status' )->data['source'] );
	}

	/**
	 * @dataProvider unusable_llm_settings
	 *
	 * @param array $options Options.
	 */
	public function test_llm_is_not_called_without_a_key_or_with_an_unsafe_endpoint( array $options ) {
		$this->enableLlm( $options );
		Functions\expect( 'wp_remote_post' )->never();

		$this->assertSame( 'rules', $this->verifyAndAsk( 'status' )->data['source'] );
	}

	/**
	 * Settings that must not trigger an HTTP call.
	 *
	 * @return array
	 */
	public function unusable_llm_settings() {
		return array(
			'no api key'      => array( array( 'wps_tofw_wismo_llm_api_key' => '' ) ),
			'file endpoint'   => array( array( 'wps_tofw_wismo_llm_endpoint' => 'file:///etc/passwd' ) ),
			'gopher endpoint' => array( array( 'wps_tofw_wismo_llm_endpoint' => 'gopher://llm.example.test/' ) ),
		);
	}

	public function test_llm_can_be_short_circuited_without_any_http_call() {
		$this->enableLlm();
		Functions\expect( 'wp_remote_post' )->never();
		Filters\expectApplied( 'pre_wps_tofw_wismo_llm_response' )->once()->andReturn( 'From internal bot' );

		$response = $this->verifyAndAsk( 'status' );

		$this->assertSame( 'llm', $response->data['source'] );
		$this->assertSame( 'From internal bot', $response->data['text'] );
	}

	public function test_frontend_config_never_contains_the_api_key() {
		$this->enableLlm();
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'admin_url' )->justReturn( 'https://shop.test/wp-admin/admin-ajax.php' );
		Functions\when( 'wp_enqueue_style' )->justReturn( true );
		Functions\when( 'wp_add_inline_style' )->justReturn( true );
		Functions\when( 'wp_enqueue_script' )->justReturn( true );
		Functions\when( 'sanitize_hex_color' )->returnArg();
		Functions\when( 'wc_get_order' )->justReturn( false );
		$localized = null;
		Functions\when( 'wp_localize_script' )->alias(
			static function ( $handle, $name, $data ) use ( &$localized ) {
				$localized = array( $handle, $name, $data );
				return true;
			}
		);

		$this->wismo()->enqueue_assets();

		$this->assertSame( 'wps_tofw_wismo', $localized[1] );
		$this->assertSame( 'fresh-nonce', $localized[2]['nonce'] );
		$encoded = wp_json_encode( $localized );
		$this->assertStringNotContainsString( self::API_KEY, $encoded );
		$this->assertStringNotContainsString( 'llm.example.test', $encoded );
		$this->assertStringNotContainsString( 'test-model', $encoded );
	}

	public function test_widget_assets_are_not_loaded_when_disabled() {
		$this->setOptions( array( 'wps_tofw_wismo_enable' => '' ) );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\expect( 'wp_enqueue_script' )->never();
		Functions\expect( 'wp_localize_script' )->never();

		$this->wismo()->enqueue_assets();

		$this->addToAssertionCount( 1 );
	}

	public function test_settings_page_never_prints_the_saved_api_key() {
		$this->enableLlm();

		$fields = $this->wismo()->settings_fields();
		$key    = null;
		foreach ( $fields as $field ) {
			if ( 'wps_tofw_wismo_llm_api_key' === $field['id'] ) {
				$key = $field;
			}
		}

		$this->assertNotNull( $key );
		$this->assertSame( 'password', $key['type'] );
		$this->assertSame( '', $key['value'] );
		$this->assertSame( '********', $key['placeholder'] );
		$this->assertStringNotContainsString( self::API_KEY, wp_json_encode( $fields ) );
	}
}
