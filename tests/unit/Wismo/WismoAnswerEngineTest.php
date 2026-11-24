<?php
/**
 * WPS-8403: answer engine cases not covered by tests/WismoAnswerEngineTest.php.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Wismo;

use Brain\Monkey\Filters;
use Tests\Unit\TestCase;

/**
 * Track_Orders_For_Woocommerce_Wismo_Answer_Engine.
 */
class WismoAnswerEngineTest extends TestCase {

	/**
	 * Load the engine.
	 */
	protected function set_up() {
		parent::set_up();
		$this->stubCommonWpFunctions();
		$this->requirePluginFile( 'includes/wismo/class-track-orders-for-woocommerce-wismo-answer-engine.php' );
	}

	/**
	 * Context.
	 *
	 * @param array $overrides Overrides.
	 * @return array
	 */
	private function ctx( array $overrides = array() ) {
		return array_merge(
			array(
				'order_number'       => '77',
				'status'             => 'processing',
				'status_label'       => 'Processing',
				'status_updated'     => '',
				'date_completed'     => '',
				'items'              => array(),
				'ship_city'          => '',
				'ship_country'       => '',
				'carrier'            => '',
				'tracking_number'    => '',
				'tracking_url'       => '',
				'tracking_page_url'  => '',
				'estimated_delivery' => '',
			),
			$overrides
		);
	}

	public function test_empty_question_returns_the_status() {
		$a = \Track_Orders_For_Woocommerce_Wismo_Answer_Engine::answer( '', $this->ctx() );

		$this->assertSame( 'status', $a['intent'] );
		$this->assertTrue( $a['confident'] );
		$this->assertStringContainsString( 'Order #77 is being processed', $a['text'] );
	}

	/**
	 * @dataProvider carrier_status_provider
	 *
	 * @param string $status Carrier status.
	 * @param string $needle Expected text.
	 */
	public function test_status_answer_includes_carrier_status( $status, $needle ) {
		$text = \Track_Orders_For_Woocommerce_Wismo_Answer_Engine::status_text(
			$this->ctx(
				array(
					'carrier_status'         => $status,
					'carrier_status_label'   => 'Delivery attempt failed',
					'carrier_status_updated' => 'Nov 22',
				)
			)
		);

		$this->assertStringContainsString( $needle, $text );
		$this->assertStringContainsString( 'Carrier update: Nov 22.', $text );
	}

	/**
	 * Carrier statuses.
	 *
	 * @return array
	 */
	public function carrier_status_provider() {
		return array(
			array( 'delivered', 'has been delivered' ),
			array( 'out_for_delivery', 'out for delivery' ),
			array( 'available_for_pickup', 'ready for pickup' ),
			array( 'info_received', 'shipping label has been created' ),
			array( 'failed_attempt', 'delivery issue (Delivery attempt failed)' ),
			array( 'exception', 'contact our support team' ),
			array( 'something_new', 'Latest carrier status: Delivery attempt failed.' ),
		);
	}

	public function test_cancelled_orders_hide_carrier_status_and_tracking_hint() {
		$text = \Track_Orders_For_Woocommerce_Wismo_Answer_Engine::status_text(
			$this->ctx(
				array(
					'status'          => 'cancelled',
					'carrier_status'  => 'in_transit',
					'tracking_number' => 'T1',
				)
			)
		);

		$this->assertSame( 'Order #77 has been cancelled.', $text );
	}

	public function test_tracking_hint_for_active_orders() {
		$text = \Track_Orders_For_Woocommerce_Wismo_Answer_Engine::status_text( $this->ctx( array( 'tracking_number' => 'T1' ) ) );

		$this->assertStringContainsString( 'A tracking number is available', $text );
	}

	public function test_delivery_without_estimate() {
		$e = '\Track_Orders_For_Woocommerce_Wismo_Answer_Engine';

		$this->assertStringContainsString( 'has not been set', $e::delivery_text( $this->ctx() ) );
		$this->assertSame( 'Order #77 has been refunded.', $e::delivery_text( $this->ctx( array( 'status' => 'refunded', 'estimated_delivery' => 'Dec 1' ) ) ) );
		$this->assertSame( 'Order #77 has been completed.', $e::delivery_text( $this->ctx( array( 'status' => 'completed' ) ) ) );
	}

	public function test_tracking_without_number_links_the_tracking_page() {
		list( $text, $links ) = \Track_Orders_For_Woocommerce_Wismo_Answer_Engine::tracking_text( $this->ctx( array( 'tracking_page_url' => 'https://shop.test/track/' ) ) );

		$this->assertStringContainsString( 'no tracking number', $text );
		$this->assertSame( 'https://shop.test/track/', $links[0]['url'] );
	}

	public function test_items_and_empty_address() {
		$e = '\Track_Orders_For_Woocommerce_Wismo_Answer_Engine';

		$this->assertSame( 'I could not find any items on this order.', $e::items_text( $this->ctx() ) );
		$this->assertSame( 'Your order contains: Mug x 3.', $e::items_text( $this->ctx( array( 'items' => array( array( 'name' => 'Mug', 'qty' => '3' ) ) ) ) ) );
		$this->assertSame( 'This order has no shipping destination on file.', $e::address_text( $this->ctx() ) );
	}

	public function test_support_text_and_links_fallbacks() {
		$e = '\Track_Orders_For_Woocommerce_Wismo_Answer_Engine';

		$this->assertStringContainsString( 'using the link below', $e::support_text( array( 'url' => 'https://s.test' ) ) );
		$this->assertSame( 'Please contact the store support team and include your order number.', $e::support_text( array() ) );
		$this->assertSame( array(), $e::support_links( array() ) );
		$this->assertSame(
			array( 'https://s.test', 'mailto:a@s.test' ),
			array_column( $e::support_links( array( 'url' => 'https://s.test', 'email' => 'a@s.test' ) ), 'url' )
		);
	}

	public function test_invalid_filtered_intent_regex_is_ignored() {
		Filters\expectApplied( 'wps_tofw_wismo_intents' )->andReturn(
			array(
				'broken'   => '/(unclosed/',
				'tracking' => '/track/i',
			)
		);

		$this->assertSame( 'tracking', \Track_Orders_For_Woocommerce_Wismo_Answer_Engine::detect_intent( 'track it' ) );
		$this->assertSame( 'unknown', \Track_Orders_For_Woocommerce_Wismo_Answer_Engine::detect_intent( 'hello there' ) );
	}
}
