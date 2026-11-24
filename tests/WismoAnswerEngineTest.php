<?php
/**
 * Tests for the "Where is my order?" deterministic answer engine.
 *
 * @package Track_Orders_For_Woocommerce
 * @since   1.3.0
 */

namespace Tests;

use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

require_once dirname( __DIR__ ) . '/includes/wismo/class-track-orders-for-woocommerce-wismo-answer-engine.php';

/**
 * Answer engine tests.
 */
class WismoAnswerEngineTest extends PolyfillTestCase {

	/**
	 * Build a context.
	 *
	 * @param array $overrides Overrides.
	 * @return array
	 */
	private function ctx( $overrides = array() ) {
		return array_merge(
			array(
				'order_number'       => '1001',
				'status'             => 'processing',
				'status_label'       => 'Processing',
				'status_updated'     => '',
				'date_created'       => 'November 20, 2026',
				'date_completed'     => '',
				'items'              => array(
					array(
						'name' => 'Blue Shirt',
						'qty'  => 2,
					),
				),
				'ship_city'          => 'Pune',
				'ship_country'       => 'India',
				'carrier'            => '',
				'tracking_number'    => '',
				'tracking_url'       => '',
				'tracking_page_url'  => '',
				'estimated_delivery' => '',
			),
			$overrides
		);
	}

	/**
	 * Intent detection.
	 */
	public function testDetectIntent() {
		$e = '\Track_Orders_For_Woocommerce_Wismo_Answer_Engine';
		$this->assertSame( 'status', $e::detect_intent( 'Where is my order?' ) );
		$this->assertSame( 'tracking', $e::detect_intent( 'What is my tracking number?' ) );
		$this->assertSame( 'delivery', $e::detect_intent( 'When will it be delivered?' ) );
		$this->assertSame( 'items', $e::detect_intent( 'What items did I buy' ) );
		$this->assertSame( 'address', $e::detect_intent( 'Which city is it shipped to?' ) );
		$this->assertSame( 'support', $e::detect_intent( 'I want to cancel' ) );
		$this->assertSame( 'support', $e::detect_intent( 'How do I return this?' ) );
		$this->assertSame( 'unknown', $e::detect_intent( 'tell me a joke' ) );
	}

	/**
	 * Status answers per WooCommerce status.
	 */
	public function testStatusAnswers() {
		$e     = '\Track_Orders_For_Woocommerce_Wismo_Answer_Engine';
		$cases = array(
			'pending'    => 'pending payment',
			'processing' => 'being processed',
			'on-hold'    => 'on hold',
			'completed'  => 'has been completed',
			'cancelled'  => 'has been cancelled',
			'refunded'   => 'has been refunded',
			'failed'     => 'failed',
		);
		foreach ( $cases as $status => $needle ) {
			$a = $e::answer( 'status?', $this->ctx( array( 'status' => $status ) ) );
			$this->assertStringContainsString( $needle, $a['text'], $status );
			$this->assertStringContainsString( '#1001', $a['text'] );
			$this->assertTrue( $a['confident'] );
		}
		$a = $e::answer( 'status?', $this->ctx( array( 'status' => 'shipped', 'status_label' => 'Shipped' ) ) );
		$this->assertStringContainsString( '"Shipped"', $a['text'] );
	}

	/**
	 * Tracking and delivery.
	 */
	public function testTrackingAndDelivery() {
		$e   = '\Track_Orders_For_Woocommerce_Wismo_Answer_Engine';
		$ctx = $this->ctx(
			array(
				'carrier'            => 'FedEx',
				'tracking_number'    => '7777',
				'tracking_url'       => 'https://www.fedex.com/fedextrack/?trknbr=7777',
				'estimated_delivery' => '2026-11-30',
			)
		);
		$a = $e::answer( 'tracking number', $ctx );
		$this->assertStringContainsString( 'FedEx', $a['text'] );
		$this->assertStringContainsString( '7777', $a['text'] );
		$this->assertSame( 'https://www.fedex.com/fedextrack/?trknbr=7777', $a['links'][0]['url'] );

		$a = $e::answer( 'when will it arrive', $ctx );
		$this->assertStringContainsString( '2026-11-30', $a['text'] );

		$a = $e::answer( 'tracking number', $this->ctx() );
		$this->assertStringContainsString( 'no tracking number', $a['text'] );
	}

	/**
	 * Address answer only exposes the city.
	 */
	public function testAddressCityOnly() {
		$e = '\Track_Orders_For_Woocommerce_Wismo_Answer_Engine';
		$a = $e::answer( 'what address is it going to', $this->ctx() );
		$this->assertStringContainsString( 'Pune, India', $a['text'] );
	}

	/**
	 * Support fallback.
	 */
	public function testSupportFallback() {
		$e       = '\Track_Orders_For_Woocommerce_Wismo_Answer_Engine';
		$support = array(
			'email' => 'help@shop.test',
			'url'   => 'https://shop.test/contact',
		);
		$a = $e::answer( 'I want a refund', $this->ctx(), $support );
		$this->assertFalse( $a['confident'] );
		$this->assertStringContainsString( 'help@shop.test', $a['text'] );
		$this->assertSame( 'https://shop.test/contact', $a['links'][0]['url'] );

		$a = $e::answer( 'tell me a joke', $this->ctx(), $support );
		$this->assertSame( 'unknown', $a['intent'] );
		$this->assertStringContainsString( 'being processed', $a['text'] );
		$this->assertStringContainsString( 'help@shop.test', $a['text'] );
	}

	/**
	 * Carrier status and estimated delivery.
	 */
	public function testCarrierStatus() {
		$e = '\Track_Orders_For_Woocommerce_Wismo_Answer_Engine';
		$c = $this->ctx(
			array(
				'carrier_status'       => 'in_transit',
				'carrier_status_label' => 'In transit',
				'estimated_delivery'   => 'Nov 25 - Nov 27',
			)
		);
		$this->assertStringContainsString( 'in transit', $e::status_text( $c ) );
		$this->assertStringContainsString( 'Nov 25 - Nov 27', $e::delivery_text( $c ) );

		$c['carrier_status'] = 'delivered';
		$this->assertStringContainsString( 'has been delivered', $e::delivery_text( $c ) );

		$c['carrier_status'] = 'pending';
		$this->assertSame( '', $e::carrier_status_text( $c ) );
	}
}
