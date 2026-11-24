<?php
/**
 * WPS-8403: order verification of the "Where is my order?" assistant.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Wismo;

use Brain\Monkey\Filters;

/**
 * Track_Orders_For_Woocommerce_Wismo::ajax_verify() and ajax_my_orders().
 */
class WismoVerifyTest extends WismoTestCase {

	public function test_guest_with_matching_billing_email_gets_a_session_and_status_answer() {
		$this->wismoOrder( array( 'id' => 501 ) );

		$response = $this->verifyByEmail( 501, 'buyer@example.com' );

		$this->assertTrue( $response->success );
		$this->assertNotEmpty( $response->data['token'] );
		$this->assertSame( '501', $response->data['order_number'] );
		$this->assertStringContainsString( 'Order #501 is being processed', $response->data['text'] );
	}

	public function test_email_match_ignores_case_and_surrounding_spaces_and_hash_prefix() {
		$this->wismoOrder( array( 'id' => 502 ) );

		$response = $this->verifyByEmail( ' #502 ', '  BUYER@example.COM ' );

		$this->assertTrue( $response->success );
	}

	public function test_wrong_email_returns_generic_error_with_support_links_and_no_order_data() {
		$this->wismoOrder( array( 'id' => 503 ) );

		$response = $this->verifyByEmail( 503, 'someone-else@example.com' );

		$this->assertNotVerified( $response );
		$this->assertArrayNotHasKey( 'token', $response->data );
		$this->assertStringNotContainsString( 'processing', wp_json_encode( $response->data ) );
	}

	public function test_unknown_order_gives_exactly_the_same_response_as_a_wrong_email() {
		$this->wismoOrder( array( 'id' => 504 ) );

		$wrong_email   = $this->verifyByEmail( 504, 'nope@example.com' );
		$missing_order = $this->verifyByEmail( 999999, 'nope@example.com' );

		$this->assertNotVerified( $missing_order );
		$this->assertEquals( $wrong_email->data, $missing_order->data );
		$this->assertSame( $wrong_email->status, $missing_order->status );
	}

	/**
	 * @dataProvider invalid_input_provider
	 *
	 * @param string $order_id Order ID typed.
	 * @param string $email    E-mail typed.
	 */
	public function test_invalid_input_is_not_verified( $order_id, $email ) {
		$this->wismoOrder( array( 'id' => 505 ) );

		$this->assertNotVerified( $this->verifyByEmail( $order_id, $email ) );
	}

	/**
	 * Invalid inputs.
	 *
	 * @return array
	 */
	public function invalid_input_provider() {
		return array(
			'empty e-mail'         => array( '505', '' ),
			'malformed e-mail'     => array( '505', 'buyer@' ),
			'non numeric order id' => array( '505abc', 'buyer@example.com' ),
			'sql-ish order id'     => array( '505 OR 1=1', 'buyer@example.com' ),
			'empty order id'       => array( '', 'buyer@example.com' ),
		);
	}

	public function test_order_without_billing_email_can_never_be_verified_by_email() {
		$this->wismoOrder( array( 'id' => 506, 'billing_email' => '' ) );

		$this->assertNotVerified( $this->verifyByEmail( 506, '' ) );
	}

	public function test_invalid_nonce_is_rejected_before_anything_else() {
		$this->wismoOrder( array( 'id' => 507 ) );
		$this->nonce_ok = false;

		$response = $this->verifyByEmail( 507, 'buyer@example.com' );

		$this->assertFalse( $response->success );
		$this->assertSame( 'bad_nonce', $response->data['code'] );
		$this->assertSame( 403, $response->status );
		$this->assertSame( array(), $this->transients, 'No rate limit counter or session is written for a bad nonce.' );
	}

	public function test_logged_in_owner_can_verify_own_order_without_email() {
		$this->current_user = 7;
		$this->wismoOrder( array( 'id' => 510, 'customer_id' => 7 ) );

		$response = $this->ajax(
			'ajax_verify',
			array(
				'mode'     => 'account',
				'order_id' => '510',
			)
		);

		$this->assertTrue( $response->success );
		$this->assertNotEmpty( $response->data['token'] );
	}

	public function test_logged_in_user_cannot_open_another_customers_order_in_account_mode() {
		$this->current_user = 8;
		$this->wismoOrder( array( 'id' => 511, 'customer_id' => 7 ) );

		$response = $this->ajax(
			'ajax_verify',
			array(
				'mode'     => 'account',
				'order_id' => '511',
			)
		);

		$this->assertNotVerified( $response );
	}

	public function test_guest_cannot_use_account_mode() {
		$this->wismoOrder( array( 'id' => 512, 'customer_id' => 0 ) );

		$response = $this->ajax(
			'ajax_verify',
			array(
				'mode'     => 'account',
				'order_id' => '512',
			)
		);

		$this->assertNotVerified( $response );
	}

	public function test_logged_in_user_cannot_open_another_registered_customers_order_even_with_the_right_email() {
		$this->current_user = 8;
		$this->wismoOrder( array( 'id' => 513, 'customer_id' => 7 ) );

		$this->assertNotVerified( $this->verifyByEmail( 513, 'buyer@example.com' ) );
	}

	public function test_logged_in_user_can_look_up_a_guest_order_with_its_billing_email() {
		$this->current_user = 8;
		$this->wismoOrder( array( 'id' => 514, 'customer_id' => 0 ) );

		$this->assertTrue( $this->verifyByEmail( 514, 'buyer@example.com' )->success );
	}

	public function test_verification_filter_can_deny_access() {
		$this->wismoOrder( array( 'id' => 515 ) );
		Filters\expectApplied( 'wps_tofw_wismo_verify_order' )->once()->andReturn( false );

		$this->assertNotVerified( $this->verifyByEmail( 515, 'buyer@example.com' ) );
	}

	public function test_per_ip_rate_limit_blocks_the_eleventh_attempt_with_support_links() {
		$this->wismoOrder( array( 'id' => 520 ) );

		for ( $i = 1; $i <= 10; $i++ ) {
			// Different (unknown) orders, so only the per-IP limiter is involved.
			$this->assertNotVerified( $this->verifyByEmail( 900000 + $i, 'x@example.com' ) );
		}
		$response = $this->verifyByEmail( 520, 'buyer@example.com' );

		$this->assertFalse( $response->success );
		$this->assertSame( 'rate_limited', $response->data['code'] );
		$this->assertSame( 429, $response->status );
		$this->assertSupportLinks( $response->data['links'] );

		// Another IP is not affected.
		$_SERVER['REMOTE_ADDR'] = '198.51.100.20';
		$this->assertTrue( $this->verifyByEmail( 520, 'buyer@example.com' )->success );
	}

	public function test_per_ip_limit_is_filterable() {
		Filters\expectApplied( 'wps_tofw_wismo_verify_limit' )->andReturn( 2 );

		$this->assertNotVerified( $this->verifyByEmail( 1, 'x@example.com' ) );
		$this->assertNotVerified( $this->verifyByEmail( 2, 'x@example.com' ) );
		$this->assertSame( 'rate_limited', $this->verifyByEmail( 3, 'x@example.com' )->data['code'] );
	}

	public function test_per_order_lock_after_five_failures_returns_the_same_generic_error_even_for_the_right_email() {
		$this->wismoOrder( array( 'id' => 530 ) );

		// Five wrong e-mails from five different IPs (so the per-IP limit never kicks in).
		for ( $i = 1; $i <= 5; $i++ ) {
			$_SERVER['REMOTE_ADDR'] = '192.0.2.' . $i;
			$this->assertNotVerified( $this->verifyByEmail( 530, 'guess' . $i . '@example.com' ) );
		}

		$_SERVER['REMOTE_ADDR'] = '192.0.2.99';
		$locked = $this->verifyByEmail( 530, 'buyer@example.com' );

		// Locked: same code/message/status as a wrong e-mail, so the lock does not reveal the order exists.
		$this->assertNotVerified( $locked );
		$this->assertEquals( $this->verifyByEmail( 999999, 'buyer@example.com' )->data, $locked->data );
	}

	public function test_successful_verifications_do_not_count_against_the_order_lock() {
		$this->wismoOrder( array( 'id' => 531 ) );

		for ( $i = 1; $i <= 7; $i++ ) {
			$_SERVER['REMOTE_ADDR'] = '192.0.2.' . $i;
			$this->assertTrue( $this->verifyByEmail( 531, 'buyer@example.com' )->success, 'attempt ' . $i );
		}
	}

	public function test_order_lock_window_expires() {
		$this->wismoOrder( array( 'id' => 532 ) );
		for ( $i = 1; $i <= 5; $i++ ) {
			$_SERVER['REMOTE_ADDR'] = '192.0.2.' . $i;
			$this->verifyByEmail( 532, 'bad@example.com' );
		}

		// Move every rate limit window into the past.
		foreach ( $this->transients as $key => $value ) {
			if ( 0 === strpos( $key, 'wps_tofw_wismo_rl_' ) ) {
				$this->transients[ $key ]['start'] = time() - 11 * 60;
			}
		}

		$this->assertTrue( $this->verifyByEmail( 532, 'buyer@example.com' )->success );
	}

	public function test_my_orders_requires_login() {
		$response = $this->ajax( 'ajax_my_orders' );

		$this->assertFalse( $response->success );
		$this->assertSame( 'not_logged_in', $response->data['code'] );
		$this->assertSame( 403, $response->status );
	}

	public function test_my_orders_lists_only_the_current_customers_orders() {
		$this->current_user = 7;
		$own     = $this->wismoOrder( array( 'id' => 540, 'customer_id' => 7, 'status' => 'completed' ) );
		$foreign = $this->wismoOrder( array( 'id' => 541, 'customer_id' => 9 ) );

		\Brain\Monkey\Functions\expect( 'wc_get_orders' )
			->once()
			->with( \Mockery::on( static function ( $args ) {
				return 7 === $args['customer_id'] && 'shop_order' === $args['type'];
			} ) )
			->andReturn( array( $own, $foreign ) );

		$response = $this->ajax( 'ajax_my_orders' );

		$this->assertTrue( $response->success );
		$this->assertCount( 1, $response->data['orders'] );
		$this->assertSame( 540, $response->data['orders'][0]['id'] );
		$this->assertSame( 'Completed', $response->data['orders'][0]['status'] );
	}
}
