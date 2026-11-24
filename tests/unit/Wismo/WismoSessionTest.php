<?php
/**
 * WPS-8403: session tokens, questions, nonce endpoint and rate limiting of the WISMO assistant.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Wismo;

use Brain\Monkey\Filters;

/**
 * Track_Orders_For_Woocommerce_Wismo::ajax_ask() / ajax_nonce() and token binding.
 */
class WismoSessionTest extends WismoTestCase {

	/**
	 * Verify order 601 as a guest and return the session token.
	 *
	 * @param array $props Order props.
	 * @return string
	 */
	private function guestToken( array $props = array() ) {
		$this->wismoOrder( array_merge( array( 'id' => 601 ), $props ) );
		$response = $this->verifyByEmail( 601, 'buyer@example.com' );
		$this->assertTrue( $response->success );
		return $response->data['token'];
	}

	/**
	 * Ask a question.
	 *
	 * @param string $token    Token.
	 * @param string $question Question.
	 * @return JsonResponse
	 */
	private function ask( $token, $question ) {
		return $this->ajax(
			'ajax_ask',
			array(
				'nonce'    => 'n',
				'token'    => $token,
				'question' => $question,
			)
		);
	}

	public function test_verified_session_answers_status_tracking_carrier_status_and_delivery() {
		$token = $this->guestToken(
			array(
				'meta' => array(
					'wps_tofw_selected_shipping_service' => 'fedex',
					'wps_tofw_package_tracking_number'   => 'TRK 123',
					'wps_tofw_estimated_delivery_date'   => '2026-11-30',
					'wps_tofw_carrier_status'            => 'in_transit',
				),
			)
		);

		$status = $this->ask( $token, 'Where is my order?' );
		$this->assertTrue( $status->success );
		$this->assertSame( 'status', $status->data['intent'] );
		$this->assertSame( 'rules', $status->data['source'] );
		$this->assertStringContainsString( 'Order #601 is being processed', $status->data['text'] );
		$this->assertStringContainsString( 'in transit', $status->data['text'] );

		$tracking = $this->ask( $token, 'What is my tracking number?' );
		$this->assertStringContainsString( 'FedEx', $tracking->data['text'] );
		$this->assertStringContainsString( 'TRK 123', $tracking->data['text'] );
		$this->assertSame( 'https://www.fedex.com/fedextrack/?trknbr=TRK%20123', $tracking->data['links'][0]['url'] );

		$delivery = $this->ask( $token, 'When will it be delivered?' );
		$this->assertStringContainsString( 'November 30, 2026', $delivery->data['text'] );
	}

	public function test_support_questions_get_support_links() {
		$token = $this->guestToken();

		$response = $this->ask( $token, 'I want to cancel and get a refund' );

		$this->assertSame( 'support', $response->data['intent'] );
		$this->assertStringContainsString( 'help@shop.test', $response->data['text'] );
		$this->assertSupportLinks( $response->data['links'] );
	}

	public function test_tampered_token_is_rejected() {
		$token = $this->guestToken();
		list( $payload, $mac ) = explode( '.', $token );

		// Re-point the token at another order without the secret.
		$decoded   = base64_decode( strtr( $payload, '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $payload ) % 4 ) % 4 ) );
		$forged    = preg_replace( '/^601\|/', '602|', $decoded );
		$forged_64 = rtrim( strtr( base64_encode( $forged ), '+/', '-_' ), '=' );
		$this->wismoOrder( array( 'id' => 602 ) );

		foreach ( array( $forged_64 . '.' . $mac, $payload . '.' . str_repeat( 'a', 64 ), 'garbage', '', $payload . '.' ) as $bad ) {
			$response = $this->ask( $bad, 'status' );
			$this->assertFalse( $response->success, $bad );
			$this->assertSame( 'session_expired', $response->data['code'] );
			$this->assertSame( 401, $response->status );
		}
	}

	public function test_guest_token_cannot_be_used_by_a_logged_in_user() {
		$token = $this->guestToken();

		$this->current_user = 7;

		$this->assertSame( 'session_expired', $this->ask( $token, 'status' )->data['code'] );
	}

	public function test_customer_token_cannot_be_used_by_another_user_or_a_guest() {
		$this->current_user = 7;
		$this->wismoOrder( array( 'id' => 610, 'customer_id' => 7 ) );
		$token = $this->ajax(
			'ajax_verify',
			array(
				'mode'     => 'account',
				'order_id' => '610',
			)
		)->data['token'];
		$this->assertTrue( $this->ask( $token, 'status' )->success );

		$this->current_user = 8;
		$this->assertSame( 'session_expired', $this->ask( $token, 'status' )->data['code'] );

		$this->current_user = 0;
		$this->assertSame( 'session_expired', $this->ask( $token, 'status' )->data['code'] );
	}

	public function test_token_is_only_valid_while_its_server_side_session_exists() {
		$token = $this->guestToken();

		foreach ( array_keys( $this->transients ) as $key ) {
			if ( 0 === strpos( $key, 'wps_tofw_wismo_s_' ) ) {
				unset( $this->transients[ $key ] );
			}
		}

		$this->assertSame( 'session_expired', $this->ask( $token, 'status' )->data['code'] );
	}

	public function test_session_lifetime_is_at_least_a_minute_and_filterable() {
		Filters\expectApplied( 'wps_tofw_wismo_session_ttl' )->andReturn( 5 );
		$before = time();
		$token  = $this->guestToken();

		list( $payload ) = explode( '.', $token );
		$fields          = explode( '|', base64_decode( strtr( $payload, '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $payload ) % 4 ) % 4 ) ) );

		$this->assertSame( '601', $fields[0] );
		$this->assertSame( '0', $fields[1] );
		$this->assertGreaterThanOrEqual( $before + 60, (int) $fields[2] );
		$this->assertLessThanOrEqual( time() + 60, (int) $fields[2] );
	}

	public function test_ask_rate_limit_per_ip() {
		$token = $this->guestToken();
		Filters\expectApplied( 'wps_tofw_wismo_ask_limit' )->andReturn( 3 );

		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertTrue( $this->ask( $token, 'status' )->success );
		}
		$limited = $this->ask( $token, 'status' );

		$this->assertSame( 'rate_limited', $limited->data['code'] );
		$this->assertSame( 429, $limited->status );
		$this->assertSupportLinks( $limited->data['links'] );
	}

	public function test_question_is_truncated_and_html_is_stripped_from_the_answer() {
		$token = $this->guestToken();
		$seen  = null;
		Filters\expectApplied( 'wps_tofw_wismo_answer' )->once()->andReturnUsing(
			static function ( $answer, $question ) use ( &$seen ) {
				$seen           = $question;
				$answer['text'] = '<script>alert(1)</script>Hello';
				$answer['links'] = array(
					array(
						'label' => '<b>x</b>',
						'url'   => 'javascript:alert(1)',
					),
					array(
						'label' => 'ok',
						'url'   => 'https://shop.test/ok',
					),
				);
				return $answer;
			}
		);

		$response = $this->ask( $token, str_repeat( 'a', 700 ) );

		$this->assertSame( 500, strlen( $seen ) );
		$this->assertSame( 'alert(1)Hello', $response->data['text'] );
		$this->assertSame(
			array(
				array(
					'label' => 'ok',
					'url'   => 'https://shop.test/ok',
				),
			),
			$response->data['links']
		);
	}

	public function test_ask_requires_a_valid_nonce() {
		$token          = $this->guestToken();
		$this->nonce_ok = false;

		$response = $this->ask( $token, 'status' );

		$this->assertSame( 'bad_nonce', $response->data['code'] );
		$this->assertSame( 403, $response->status );
	}

	public function test_nonce_endpoint_returns_a_fresh_uncached_nonce() {
		$no_cache = 0;
		\Brain\Monkey\Functions\when( 'nocache_headers' )->alias(
			static function () use ( &$no_cache ) {
				++$no_cache;
			}
		);

		$response = $this->ajax( 'ajax_nonce' );

		$this->assertTrue( $response->success );
		$this->assertSame( array( 'nonce' => 'fresh-nonce' ), $response->data );
		$this->assertSame( 1, $no_cache, 'The nonce response must not be cached.' );
	}

	public function test_nonce_endpoint_is_closed_when_the_widget_is_disabled() {
		$this->setOptions( array( 'wps_tofw_wismo_enable' => '' ) );

		$response = $this->ajax( 'ajax_nonce' );

		$this->assertFalse( $response->success );
		$this->assertSame( 'disabled', $response->data['code'] );
		$this->assertSame( 403, $response->status );
	}
}
