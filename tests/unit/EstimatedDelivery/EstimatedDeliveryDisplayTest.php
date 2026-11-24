<?php
/**
 * WPS-8400: estimated delivery formatting, manual override, order storage and the
 * product page / shipping rate / email / order details / tracking page output.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\EstimatedDelivery;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use Mockery;
use Tests\Unit\TestCase;
use Track_Orders_For_Woocommerce_Estimated_Delivery as Edd;

require_once __DIR__ . '/Support/EddTestStubs.php';

/**
 * Tests for Track_Orders_For_Woocommerce_Estimated_Delivery output.
 */
class EstimatedDeliveryDisplayTest extends TestCase {

	/**
	 * Load the class and stub WordPress.
	 */
	protected function set_up() {
		parent::set_up();
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-estimated-delivery.php' );
		$this->stubCommonWpFunctions();
		EddTestStubs::install( $this );
		$this->setOptions(
			array(
				'wps_tofw_is_plugin_enable' => 'on',
				'date_format'               => 'F j, Y',
				Edd::OPTION                 => array( 'enable' => 'on' ),
			)
		);
	}

	/**
	 * Site timezone for the stubs.
	 *
	 * @return string
	 */
	public function siteTimezone() {
		return 'UTC';
	}

	/**
	 * Save EDD settings (merged over enable=on).
	 *
	 * @param array $settings Settings.
	 */
	private function eddSettings( array $settings ) {
		$this->setOptions( array( Edd::OPTION => array_merge( array( 'enable' => 'on' ), $settings ) ) );
	}

	/**
	 * Run a callback and return its output.
	 *
	 * @param callable $callback Callback.
	 * @return string
	 */
	private function output( callable $callback ) {
		ob_start();
		$callback();
		return (string) ob_get_clean();
	}

	/**
	 * Shipping order item double.
	 *
	 * @param int    $instance_id Instance id.
	 * @param string $from        Rate meta from.
	 * @param string $to          Rate meta to.
	 * @return Mockery\MockInterface
	 */
	private function shippingItem( $instance_id, $from = '', $to = '' ) {
		$item = Mockery::mock( 'WC_Order_Item_Shipping' );
		$item->shouldReceive( 'get_instance_id' )->andReturn( $instance_id );
		$item->shouldReceive( 'get_meta' )->with( Edd::RATE_META_FROM, true )->andReturn( $from );
		$item->shouldReceive( 'get_meta' )->with( Edd::RATE_META_TO, true )->andReturn( $to );
		return $item;
	}

	/*
	 * Formatting: one consistent range format everywhere.
	 */

	public function test_format_range_uses_the_configured_date_format() {
		$this->assertSame( 'Fri, Mar 6 – Tue, Mar 10', Edd::format_range( '2026-03-06', '2026-03-10' ) );

		$this->eddSettings( array( 'date_format' => 'd/m/Y' ) );
		$this->assertSame( '06/03/2026 – 10/03/2026', Edd::format_range( '2026-03-06', '2026-03-10' ) );
	}

	public function test_same_day_range_is_a_single_date() {
		$this->assertSame( 'Fri, Mar 6', Edd::format_range( '2026-03-06', '2026-03-06' ) );
	}

	public function test_incomplete_or_invalid_range_is_empty() {
		$this->assertSame( '', Edd::format_range( '', '2026-03-06' ) );
		$this->assertSame( '', Edd::format_range( '2026-03-06', '' ) );
		$this->assertSame( '', Edd::format_date( 'not-a-date' ) );
	}

	public function test_display_text_uses_label() {
		$this->assertSame( 'Estimated delivery: Fri, Mar 6', Edd::display_text( 'Fri, Mar 6' ) );

		$this->eddSettings( array( 'label' => 'Arrives' ) );
		$this->assertSame( 'Arrives: Fri, Mar 6', Edd::display_text( 'Fri, Mar 6' ) );

		$this->eddSettings( array( 'label' => '   ' ) );
		$this->assertSame( 'Estimated delivery: Fri, Mar 6', Edd::display_text( 'Fri, Mar 6' ), 'Blank label falls back to the default.' );

		$this->assertSame( '', Edd::display_text( '' ) );
	}

	/*
	 * Order EDD: manual override wins over the computed range.
	 */

	public function test_manual_date_overrides_the_computed_range() {
		$order = $this->mockOrder(
			array(
				'id'   => 11,
				'meta' => array(
					'wps_tofw_estimated_delivery_date' => '2026-03-20',
					'wps_tofw_estimated_delivery_time' => '10:00',
					Edd::META_FROM                     => '2026-03-06',
					Edd::META_TO                       => '2026-03-10',
				),
			)
		);

		$this->assertSame(
			array( 'manual' => true, 'from' => '2026-03-20', 'to' => '2026-03-20', 'text' => 'March 20, 2026 10:00' ),
			Edd::get_order_edd( $order )
		);
	}

	public function test_computed_range_is_used_without_manual_date() {
		$this->mockOrder( array( 'id' => 12, 'meta' => array( Edd::META_FROM => '2026-03-06', Edd::META_TO => '2026-03-10' ) ) );

		$this->assertSame(
			array( 'manual' => false, 'from' => '2026-03-06', 'to' => '2026-03-10', 'text' => 'Fri, Mar 6 – Tue, Mar 10' ),
			Edd::get_order_edd( 12 ),
			'Accepts an order id too.'
		);
	}

	public function test_no_edd_without_meta_or_order() {
		$this->mockOrder( array( 'id' => 13 ) );

		$this->assertNull( Edd::get_order_edd( 13 ) );
		$this->assertNull( Edd::get_order_edd( 999 ) );
	}

	public function test_store_order_edd_uses_the_range_shown_on_the_chosen_rate() {
		$order = $this->mockOrder(
			array(
				'id'        => 14,
				'get_items' => array( $this->shippingItem( 5, '2026-03-06', '2026-03-10' ) ),
			)
		);
		$order->shouldReceive( 'save' )->once();

		Edd::store_order_edd( $order );

		$this->assertSame( '2026-03-06', $this->getOrderMeta( 14, Edd::META_FROM ) );
		$this->assertSame( '2026-03-10', $this->getOrderMeta( 14, Edd::META_TO ) );
	}

	public function test_store_order_edd_takes_the_slowest_package() {
		$order = $this->mockOrder(
			array(
				'id'        => 15,
				'get_items' => array(
					$this->shippingItem( 5, '2026-03-06', '2026-03-10' ),
					$this->shippingItem( 6, '2026-03-09', '2026-03-12' ),
				),
			)
		);

		Edd::store_order_edd( $order );

		$this->assertSame( '2026-03-09', $this->getOrderMeta( 15, Edd::META_FROM ) );
		$this->assertSame( '2026-03-12', $this->getOrderMeta( 15, Edd::META_TO ) );
	}

	public function test_store_order_edd_calculates_from_the_order_date_when_rate_meta_is_missing() {
		$order = $this->mockOrder(
			array(
				'id'               => 16,
				'get_items'        => array( $this->shippingItem( 5 ) ),
				'get_date_created' => new DateTimeImmutable( '2026-03-02 10:00:00' ),
			)
		);

		Edd::store_order_edd( $order );

		$this->assertSame( '2026-03-06', $this->getOrderMeta( 16, Edd::META_FROM ) );
		$this->assertSame( '2026-03-10', $this->getOrderMeta( 16, Edd::META_TO ) );
	}

	public function test_store_order_edd_never_overwrites_a_manual_date() {
		$order = $this->mockOrder(
			array(
				'id'        => 17,
				'meta'      => array( 'wps_tofw_estimated_delivery_date' => '2026-04-01' ),
				'get_items' => array( $this->shippingItem( 5, '2026-03-06', '2026-03-10' ) ),
			)
		);
		$order->shouldNotReceive( 'save' );

		Edd::store_order_edd( $order );

		$this->assertNull( $this->getOrderMeta( 17, Edd::META_FROM ) );
	}

	public function test_store_order_edd_skips_orders_without_shipping_or_hidden_methods() {
		$this->eddSettings( array( 'methods' => array( 9 => array( 'hide' => 1 ) ) ) );
		$no_shipping = $this->mockOrder( array( 'id' => 18, 'get_items' => array() ) );
		$hidden      = $this->mockOrder(
			array(
				'id'               => 19,
				'get_items'        => array( $this->shippingItem( 9 ) ),
				'get_date_created' => new DateTimeImmutable( '2026-03-02 10:00:00' ),
			)
		);
		$no_shipping->shouldNotReceive( 'save' );
		$hidden->shouldNotReceive( 'save' );

		Edd::store_order_edd( $no_shipping );
		Edd::store_order_edd( $hidden );

		$this->assertNull( $this->getOrderMeta( 18, Edd::META_FROM ) );
		$this->assertNull( $this->getOrderMeta( 19, Edd::META_FROM ) );
	}

	/*
	 * Cart / checkout (block checkout reads WC_Shipping_Rate delivery_time on WC 9.2+).
	 */

	public function test_rates_get_range_meta_and_delivery_time() {
		$this->eddSettings( array( 'methods' => array( 5 => array( 'processing' => 0, 'min' => 1, 'max' => 2 ) ) ) );
		$flat  = new \WC_Shipping_Rate( 'flat_rate:5', 'Flat rate', 5 );
		$other = new \WC_Shipping_Rate( 'free_shipping:6', 'Free shipping', 6 );

		$rates = ( new Edd() )->wps_tofw_edd_add_to_rates( array( $flat, $other ), array() );

		$this->assertTrue( Edd::rates_support_delivery_time() );
		$meta = $rates[0]->get_meta_data();
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}$/', $meta[ Edd::RATE_META_FROM ] );
		$expected = 'Estimated delivery: ' . Edd::format_range( $meta[ Edd::RATE_META_FROM ], $meta[ Edd::RATE_META_TO ] );
		$this->assertSame( $expected, $rates[0]->get_delivery_time() );
		$this->assertSame( 'Flat rate', $rates[0]->get_label(), 'Label untouched on WC 9.2+.' );
		$this->assertSame( Edd::calculate( 5 ), array( 'from' => $meta[ Edd::RATE_META_FROM ], 'to' => $meta[ Edd::RATE_META_TO ] ) );
		$this->assertNotSame( $rates[0]->get_meta_data(), $rates[1]->get_meta_data(), 'Each method gets its own rule.' );
	}

	public function test_hidden_method_rate_gets_no_estimate() {
		$this->eddSettings( array( 'methods' => array( 9 => array( 'hide' => 1 ) ) ) );
		$rate = new \WC_Shipping_Rate( 'flat_rate:9', 'Flat rate', 9 );

		( new Edd() )->wps_tofw_edd_add_to_rates( array( $rate ), array() );

		$this->assertSame( array(), $rate->get_meta_data() );
		$this->assertSame( '', $rate->get_delivery_time() );
	}

	public function test_rates_untouched_when_cart_display_is_off() {
		$this->eddSettings( array( 'show_cart' => 'off' ) );
		$rate = new \WC_Shipping_Rate( 'flat_rate:5', 'Flat rate', 5 );

		( new Edd() )->wps_tofw_edd_add_to_rates( array( $rate ), array() );

		$this->assertSame( '', $rate->get_delivery_time() );
		$this->assertSame( '', $this->output( static function () use ( $rate ) {
			( new Edd() )->wps_tofw_edd_after_shipping_rate( $rate, 0 );
		} ) );
	}

	public function test_classic_cart_prints_the_rate_estimate() {
		$rate = new \WC_Shipping_Rate( 'flat_rate:5', 'Flat rate', 5 );
		$rate->add_meta_data( Edd::RATE_META_FROM, '2026-03-06' );
		$rate->add_meta_data( Edd::RATE_META_TO, '2026-03-10' );

		$html = $this->output( static function () use ( $rate ) {
			( new Edd() )->wps_tofw_edd_after_shipping_rate( $rate, 0 );
		} );

		$this->assertSame( '<div class="wps-tofw-edd wps-tofw-edd--rate"><small>Estimated delivery: Fri, Mar 6 – Tue, Mar 10</small></div>', $html );
	}

	public function test_rate_cache_marker_changes_with_settings() {
		$edd   = new Edd();
		$first = $edd->wps_tofw_edd_bust_rate_cache( array( array( 'contents' => array() ) ) );
		$this->eddSettings( array( 'transit_max' => 9 ) );
		$second = $edd->wps_tofw_edd_bust_rate_cache( array( array( 'contents' => array() ) ) );

		$this->assertArrayHasKey( 'wps_tofw_edd_marker', $first[0] );
		$this->assertNotSame( $first[0]['wps_tofw_edd_marker'], $second[0]['wps_tofw_edd_marker'] );
	}

	/*
	 * Product page.
	 */

	public function test_product_page_shows_the_estimate_for_shippable_products() {
		$product = Mockery::mock( 'WC_Product' );
		$product->shouldReceive( 'needs_shipping' )->andReturn( true );
		$product->shouldReceive( 'is_in_stock' )->andReturn( true );
		$product->shouldReceive( 'is_on_backorder' )->andReturn( false );

		$html  = ( new Edd() )->get_product_html( $product );
		$range = Edd::calculate( 0 );

		$this->assertSame(
			'<p class="wps-tofw-edd wps-tofw-edd--product">Estimated delivery: ' . Edd::format_range( $range['from'], $range['to'] ) . '</p>',
			$html
		);
	}

	/**
	 * No estimate for virtual, out of stock or backordered products.
	 *
	 * @dataProvider unshippable_products
	 *
	 * @param bool $needs_shipping Needs shipping.
	 * @param bool $in_stock       In stock.
	 * @param bool $backorder      On backorder.
	 */
	public function test_product_page_hides_the_estimate( $needs_shipping, $in_stock, $backorder ) {
		$product = Mockery::mock( 'WC_Product' );
		$product->shouldReceive( 'needs_shipping' )->andReturn( $needs_shipping );
		$product->shouldReceive( 'is_in_stock' )->andReturn( $in_stock );
		$product->shouldReceive( 'is_on_backorder' )->andReturn( $backorder );

		$this->assertSame( '', ( new Edd() )->get_product_html( $product ) );
	}

	/**
	 * Products without an estimate.
	 *
	 * @return array
	 */
	public function unshippable_products() {
		return array(
			'virtual'      => array( false, true, false ),
			'out of stock' => array( true, false, false ),
			'backorder'    => array( true, true, true ),
		);
	}

	public function test_product_page_respects_location_toggle_and_filter() {
		$product = Mockery::mock( 'WC_Product' );
		$product->shouldReceive( 'needs_shipping', 'is_in_stock' )->andReturn( true );
		$product->shouldReceive( 'is_on_backorder' )->andReturn( false );

		Filters\expectApplied( 'wps_tofw_edd_product_text' )->once()->andReturn( '<b>Soon</b>' );
		$this->assertSame( '<p class="wps-tofw-edd wps-tofw-edd--product">&lt;b&gt;Soon&lt;/b&gt;</p>', ( new Edd() )->get_product_html( $product ), 'Filtered text is escaped.' );

		$this->eddSettings( array( 'show_product' => 'off' ) );
		$this->assertSame( '', ( new Edd() )->get_product_html( $product ) );
	}

	/*
	 * Emails, order details and tracking pages.
	 */

	public function test_html_email_shows_the_order_range() {
		$order = $this->mockOrder( array( 'id' => 30, 'meta' => array( Edd::META_FROM => '2026-03-06', Edd::META_TO => '2026-03-10' ) ) );

		$html = $this->output( static function () use ( $order ) {
			( new Edd() )->wps_tofw_edd_email( $order, false, false );
		} );

		$this->assertSame( '<p class="wps-tofw-edd wps-tofw-edd--email" style="margin:0 0 16px;"><strong>Estimated delivery: Fri, Mar 6 – Tue, Mar 10</strong></p>', $html );
	}

	public function test_plain_text_email_shows_the_order_range() {
		$order = $this->mockOrder( array( 'id' => 31, 'meta' => array( Edd::META_FROM => '2026-03-06', Edd::META_TO => '2026-03-10' ) ) );

		$text = $this->output( static function () use ( $order ) {
			( new Edd() )->wps_tofw_edd_email( $order, false, true );
		} );

		$this->assertSame( "\nEstimated delivery: Fri, Mar 6 – Tue, Mar 10\n\n", $text );
	}

	/**
	 * No estimate on emails / order details for finished orders.
	 *
	 * @dataProvider finished_statuses
	 *
	 * @param string $status Order status.
	 */
	public function test_finished_orders_show_no_estimate( $status ) {
		$order = $this->mockOrder( array( 'id' => 32, 'status' => $status, 'meta' => array( Edd::META_FROM => '2026-03-06', Edd::META_TO => '2026-03-10' ) ) );
		$edd   = new Edd();

		$this->assertSame( '', $this->output( static function () use ( $edd, $order ) {
			$edd->wps_tofw_edd_email( $order );
			$edd->wps_tofw_edd_order_details( $order );
		} ) );
		$this->assertSame( '', Edd::tracking_range_text( $order, '' ) );
	}

	/**
	 * Statuses without an estimate.
	 *
	 * @return array
	 */
	public function finished_statuses() {
		return array( array( 'completed' ), array( 'cancelled' ), array( 'refunded' ), array( 'failed' ) );
	}

	public function test_email_toggle() {
		$this->eddSettings( array( 'show_email' => 'off' ) );
		$order = $this->mockOrder( array( 'id' => 33, 'meta' => array( Edd::META_FROM => '2026-03-06', Edd::META_TO => '2026-03-10' ) ) );

		$this->assertSame( '', $this->output( static function () use ( $order ) {
			( new Edd() )->wps_tofw_edd_email( $order );
		} ) );
	}

	public function test_order_details_show_manual_date_when_set() {
		$order = $this->mockOrder( array( 'id' => 34, 'meta' => array( 'wps_tofw_estimated_delivery_date' => '2026-03-20' ) ) );

		$html = $this->output( static function () use ( $order ) {
			( new Edd() )->wps_tofw_edd_order_details( $order );
		} );

		$this->assertSame( '<p class="wps-tofw-edd wps-tofw-edd--order"><strong>Estimated delivery: March 20, 2026</strong></p>', $html );
	}

	public function test_tracking_templates_get_the_same_range_text() {
		$order = $this->mockOrder( array( 'id' => 35, 'meta' => array( Edd::META_FROM => '2026-03-06', Edd::META_TO => '2026-03-10' ) ) );

		$this->assertSame( 'Fri, Mar 6 – Tue, Mar 10', Edd::tracking_range_text( $order, '' ) );
		$this->assertSame( 'Fri, Mar 6 – Tue, Mar 10', Edd::tracking_text_fallback( $order, '' ) );
		$this->assertSame( '2026-03-10', Edd::tracking_date_fallback( $order, '' ), 'Single-date templates get the latest date.' );
	}

	public function test_tracking_templates_keep_the_manual_date() {
		$order = $this->mockOrder( array( 'id' => 36, 'meta' => array( Edd::META_FROM => '2026-03-06', Edd::META_TO => '2026-03-10' ) ) );

		$this->assertSame( '', Edd::tracking_range_text( $order, '2026-04-01' ) );
		$this->assertSame( '2026-04-01', Edd::tracking_date_fallback( $order, '2026-04-01' ) );
		$this->assertSame( '2026-04-01', Edd::tracking_text_fallback( $order, '2026-04-01' ) );
	}

	public function test_tracking_toggle_hides_the_computed_range() {
		$this->eddSettings( array( 'show_tracking' => 'off' ) );
		$order = $this->mockOrder( array( 'id' => 37, 'meta' => array( Edd::META_FROM => '2026-03-06', Edd::META_TO => '2026-03-10' ) ) );

		$this->assertSame( '', Edd::tracking_range_text( $order, '' ) );
		$this->assertSame( '', Edd::tracking_date_fallback( $order, '' ) );
	}

	/*
	 * Settings form.
	 */

	public function test_settings_are_sanitized_on_save() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\expect( 'check_admin_referer' )->once()->with( 'wps_tofw_edd_save_settings', 'wps_tofw_edd_nonce' )->andReturn( 1 );
		Functions\when( 'sanitize_textarea_field' )->returnArg();
		$_POST = array(
			'wps_tofw_edd_save'             => '1',
			'wps_tofw_edd_enable'           => 'on',
			'wps_tofw_edd_show_product'     => 'on',
			'wps_tofw_edd_label'            => '<b>Arrives</b>',
			'wps_tofw_edd_processing_days'  => '2',
			'wps_tofw_edd_transit_min'      => '-3',
			'wps_tofw_edd_transit_max'      => '6',
			'wps_tofw_edd_cutoff_time'      => '25:00',
			'wps_tofw_edd_non_working_days' => array( '0', '6', '6', '9' ),
			'wps_tofw_edd_holidays'         => "2026-12-25\n2026-02-30, nonsense\n2026-01-01",
			'wps_tofw_edd_methods'          => array(
				'5' => array( 'processing' => '0', 'min' => '1', 'max' => '2' ),
				'6' => array( 'processing' => '', 'min' => '', 'max' => '' ),
				'7' => array( 'processing' => '', 'min' => '', 'max' => '', 'hide' => '1' ),
				'0' => array( 'processing' => '1' ),
			),
		);

		( new Edd() )->wps_tofw_edd_save_settings();
		$_POST = array();

		$saved = $this->getOption( Edd::OPTION );
		$this->assertSame( 'on', $saved['enable'] );
		$this->assertSame( 'on', $saved['show_product'] );
		$this->assertSame( 'off', $saved['show_cart'], 'Unchecked boxes are saved as off.' );
		$this->assertSame( 'Arrives', $saved['label'] );
		$this->assertSame( 2, $saved['processing_days'] );
		$this->assertSame( 3, $saved['transit_min'] );
		$this->assertSame( '', $saved['cutoff_time'], 'Invalid cut-off is dropped.' );
		$this->assertSame( array( 0, 6 ), $saved['non_working_days'] );
		$this->assertSame( array( '2026-01-01', '2026-12-25' ), $saved['holidays'], 'Invalid dates dropped, sorted.' );
		$this->assertSame(
			array(
				5 => array( 'processing' => 0, 'min' => 1, 'max' => 2, 'hide' => 0 ),
				7 => array( 'processing' => '', 'min' => '', 'max' => '', 'hide' => 1 ),
			),
			$saved['methods']
		);
		$this->assertTrue( Edd::$settings_saved );
		Edd::$settings_saved = false;
	}

	public function test_settings_are_not_saved_without_capability() {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'check_admin_referer' )->never();
		$_POST = array( 'wps_tofw_edd_save' => '1', 'wps_tofw_edd_enable' => 'on' );

		( new Edd() )->wps_tofw_edd_save_settings();
		$_POST = array();

		$this->assertSame( array( 'enable' => 'on' ), $this->getOption( Edd::OPTION ) );
	}
}
