<?php
/**
 * WPS-8400: estimated delivery date rules (processing + transit days per shipping
 * method, non-working days, holidays, cut-off time) and the enabled flags.
 *
 * Calendar used below (March 2026): Mon 2, Tue 3, Wed 4, Thu 5, Fri 6, Sat 7, Sun 8,
 * Mon 9, Tue 10, Wed 11, Thu 12, Fri 13, ..., Tue 17.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\EstimatedDelivery;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;
use Tests\Unit\TestCase;
use Track_Orders_For_Woocommerce_Estimated_Delivery as Edd;

require_once __DIR__ . '/Support/EddTestStubs.php';

/**
 * Tests for Track_Orders_For_Woocommerce_Estimated_Delivery calculation.
 */
class EstimatedDeliveryCalculationTest extends TestCase {

	/**
	 * Site timezone used by wp_timezone().
	 *
	 * @var string
	 */
	private $tz = 'UTC';

	/**
	 * Load the class and stub WordPress.
	 */
	protected function set_up() {
		parent::set_up();
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-estimated-delivery.php' );
		$this->stubCommonWpFunctions();
		$this->tz = 'UTC';
		EddTestStubs::install( $this );
		$this->setOptions(
			array(
				'wps_tofw_is_plugin_enable' => 'on',
				Edd::OPTION                 => array( 'enable' => 'on' ),
			)
		);
	}

	/**
	 * Current site timezone name (read by the wp_timezone() stub).
	 *
	 * @return string
	 */
	public function siteTimezone() {
		return $this->tz;
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
	 * Placement time.
	 *
	 * @param string $local Local time in the site timezone.
	 * @return DateTimeImmutable
	 */
	private function placedAt( $local ) {
		return new DateTimeImmutable( $local, new DateTimeZone( $this->tz ) );
	}

	/*
	 * Defaults: 1 processing day, 3-5 transit days, Sat/Sun off, cut-off 14:00.
	 */

	public function test_defaults() {
		$defaults = Edd::get_defaults();

		$this->assertSame( 'off', $defaults['enable'], 'Feature is opt-in.' );
		$this->assertSame( 1, $defaults['processing_days'] );
		$this->assertSame( 3, $defaults['transit_min'] );
		$this->assertSame( 5, $defaults['transit_max'] );
		$this->assertSame( '14:00', $defaults['cutoff_time'] );
		$this->assertSame( array( 0, 6 ), $defaults['non_working_days'] );
	}

	public function test_order_before_cutoff_starts_today() {
		// Mon 10:00 -> ships Tue (1 processing day) -> Fri 6 .. Tue 10 (3-5 business days).
		$this->assertSame(
			array( 'from' => '2026-03-06', 'to' => '2026-03-10' ),
			Edd::calculate( 0, $this->placedAt( '2026-03-02 10:00:00' ) )
		);
	}

	public function test_order_after_cutoff_starts_next_business_day() {
		// Mon 15:00 -> starts Tue -> ships Wed -> Mon 9 .. Wed 11.
		$this->assertSame(
			array( 'from' => '2026-03-09', 'to' => '2026-03-11' ),
			Edd::calculate( 0, $this->placedAt( '2026-03-02 15:00:00' ) )
		);
	}

	public function test_order_exactly_at_cutoff_counts_as_after_cutoff() {
		$this->assertSame(
			Edd::calculate( 0, $this->placedAt( '2026-03-02 15:00:00' ) ),
			Edd::calculate( 0, $this->placedAt( '2026-03-02 14:00:00' ) )
		);
		$this->assertSame(
			array( 'from' => '2026-03-06', 'to' => '2026-03-10' ),
			Edd::calculate( 0, $this->placedAt( '2026-03-02 13:59:59' ) )
		);
	}

	public function test_friday_after_cutoff_skips_the_weekend() {
		// Fri 6 16:00 -> starts Mon 9 -> ships Tue 10 -> Fri 13 .. Tue 17.
		$this->assertSame(
			array( 'from' => '2026-03-13', 'to' => '2026-03-17' ),
			Edd::calculate( 0, $this->placedAt( '2026-03-06 16:00:00' ) )
		);
	}

	public function test_weekend_order_starts_monday() {
		$expected = array( 'from' => '2026-03-13', 'to' => '2026-03-17' );

		$this->assertSame( $expected, Edd::calculate( 0, $this->placedAt( '2026-03-07 09:00:00' ) ), 'Saturday morning.' );
		$this->assertSame( $expected, Edd::calculate( 0, $this->placedAt( '2026-03-08 20:00:00' ) ), 'Sunday evening.' );
	}

	public function test_holidays_are_not_business_days() {
		$this->eddSettings( array( 'holidays' => array( '2026-03-04' ) ) );

		// Mon 10:00 -> ships Tue 3 -> Wed 4 holiday -> Thu 5, Fri 6, Mon 9 (from) .. Tue 10, Wed 11 (to).
		$this->assertSame(
			array( 'from' => '2026-03-09', 'to' => '2026-03-11' ),
			Edd::calculate( 0, $this->placedAt( '2026-03-02 10:00:00' ) )
		);
	}

	public function test_holiday_on_order_day_delays_processing() {
		$this->eddSettings( array( 'holidays' => array( '2026-03-02' ) ) );

		// Mon 2 is a holiday: start Tue 3 -> ship Wed 4 -> Mon 9 .. Wed 11.
		$this->assertSame(
			array( 'from' => '2026-03-09', 'to' => '2026-03-11' ),
			Edd::calculate( 0, $this->placedAt( '2026-03-02 09:00:00' ) )
		);
	}

	public function test_custom_non_working_days() {
		// Only Sunday off: Mon 10:00 -> ships Tue 3 -> Fri 6 .. Mon 9 (Sat 7 counts, Sun 8 does not).
		$this->eddSettings( array( 'non_working_days' => array( 0 ) ) );

		$this->assertSame(
			array( 'from' => '2026-03-06', 'to' => '2026-03-09' ),
			Edd::calculate( 0, $this->placedAt( '2026-03-02 10:00:00' ) )
		);
	}

	public function test_all_days_off_is_treated_as_no_days_off() {
		$this->eddSettings( array( 'non_working_days' => array( 0, 1, 2, 3, 4, 5, 6 ) ) );

		// Calendar days: Mon 2 -> ships Tue 3 -> Fri 6 .. Sun 8.
		$this->assertSame(
			array( 'from' => '2026-03-06', 'to' => '2026-03-08' ),
			Edd::calculate( 0, $this->placedAt( '2026-03-02 10:00:00' ) )
		);
	}

	public function test_empty_cutoff_means_same_day_processing() {
		$this->eddSettings( array( 'cutoff_time' => '' ) );

		$this->assertSame(
			array( 'from' => '2026-03-06', 'to' => '2026-03-10' ),
			Edd::calculate( 0, $this->placedAt( '2026-03-02 23:30:00' ) )
		);
	}

	public function test_custom_cutoff_time() {
		$this->eddSettings( array( 'cutoff_time' => '09:30' ) );

		$this->assertSame( '2026-03-09', Edd::calculate( 0, $this->placedAt( '2026-03-02 09:30:00' ) )['from'] );
		$this->assertSame( '2026-03-06', Edd::calculate( 0, $this->placedAt( '2026-03-02 09:29:00' ) )['from'] );
	}

	public function test_cutoff_uses_the_site_timezone() {
		$this->tz = 'America/New_York';
		$placed   = new DateTimeImmutable( '2026-03-02 18:30:00', new DateTimeZone( 'UTC' ) );

		// 18:30 UTC = 13:30 in New York (EST, UTC-5): before the 14:00 cut-off.
		$this->assertSame( array( 'from' => '2026-03-06', 'to' => '2026-03-10' ), Edd::calculate( 0, $placed ) );

		// 19:30 UTC = 14:30 in New York: after the cut-off.
		$placed = new DateTimeImmutable( '2026-03-02 19:30:00', new DateTimeZone( 'UTC' ) );
		$this->assertSame( array( 'from' => '2026-03-09', 'to' => '2026-03-11' ), Edd::calculate( 0, $placed ) );
	}

	public function test_zero_processing_days_ships_on_the_start_day() {
		$this->eddSettings( array( 'processing_days' => 0, 'transit_min' => 1, 'transit_max' => 1 ) );

		$this->assertSame(
			array( 'from' => '2026-03-03', 'to' => '2026-03-03' ),
			Edd::calculate( 0, $this->placedAt( '2026-03-02 10:00:00' ) )
		);
	}

	/*
	 * Rules per shipping method.
	 */

	public function test_method_rule_overrides_the_defaults() {
		$this->eddSettings(
			array(
				'methods' => array(
					7 => array( 'processing' => 0, 'min' => 1, 'max' => 2, 'hide' => 0 ),
				),
			)
		);

		$this->assertSame( array( 'processing' => 0, 'min' => 1, 'max' => 2 ), Edd::get_rule( 7 ) );
		$this->assertSame(
			array( 'from' => '2026-03-03', 'to' => '2026-03-04' ),
			Edd::calculate( 7, $this->placedAt( '2026-03-02 10:00:00' ) )
		);
		// Another method uses the defaults.
		$this->assertSame(
			array( 'from' => '2026-03-06', 'to' => '2026-03-10' ),
			Edd::calculate( 8, $this->placedAt( '2026-03-02 10:00:00' ) )
		);
	}

	public function test_empty_method_fields_fall_back_to_defaults() {
		$this->eddSettings(
			array(
				'processing_days' => 2,
				'transit_min'     => 4,
				'transit_max'     => 6,
				'methods'         => array( 7 => array( 'processing' => '', 'min' => 1, 'max' => '' ) ),
			)
		);

		$this->assertSame( array( 'processing' => 2, 'min' => 1, 'max' => 6 ), Edd::get_rule( 7 ) );
	}

	public function test_hidden_method_has_no_estimate() {
		$this->eddSettings( array( 'methods' => array( 9 => array( 'hide' => 1 ) ) ) );

		$this->assertNull( Edd::get_rule( 9 ) );
		$this->assertNull( Edd::calculate( 9, $this->placedAt( '2026-03-02 10:00:00' ) ) );
	}

	public function test_max_below_min_is_raised_to_min() {
		$this->eddSettings( array( 'transit_min' => 5, 'transit_max' => 2 ) );

		$this->assertSame( 5, Edd::get_rule()['max'] );
		$range = Edd::calculate( 0, $this->placedAt( '2026-03-02 10:00:00' ) );
		$this->assertSame( $range['from'], $range['to'] );
	}

	public function test_rule_and_range_are_filterable() {
		Filters\expectApplied( 'wps_tofw_edd_rule' )->once()->andReturn( array( 'processing' => 0, 'min' => 0, 'max' => 0 ) );
		Filters\expectApplied( 'wps_tofw_edd_calculated_range' )->once()->andReturnUsing(
			static function ( $range, $instance_id, $now ) {
				$range['to'] = '2026-12-31';
				return $range;
			}
		);

		$this->assertSame(
			array( 'from' => '2026-03-02', 'to' => '2026-12-31' ),
			Edd::calculate( 3, $this->placedAt( '2026-03-02 10:00:00' ) )
		);
	}

	public function test_corrupt_option_falls_back_to_defaults() {
		$this->setOptions( array( Edd::OPTION => 'garbage' ) );

		$this->assertSame( Edd::get_defaults(), Edd::get_settings() );
	}

	/*
	 * Enabled flags (read without translating anything: runs before "init").
	 */

	public function test_is_enabled_requires_plugin_and_feature() {
		$this->assertTrue( Edd::is_enabled() );

		$this->setOptions( array( 'wps_tofw_is_plugin_enable' => 'off' ) );
		$this->assertFalse( Edd::is_enabled(), 'Plugin disabled.' );

		$this->setOptions( array( 'wps_tofw_is_plugin_enable' => 'on', Edd::OPTION => array() ) );
		$this->assertFalse( Edd::is_enabled(), 'Feature off by default.' );
	}

	public function test_is_enabled_per_location() {
		$this->eddSettings( array( 'show_product' => 'off', 'show_email' => 'off' ) );

		$this->assertFalse( Edd::is_enabled( 'product' ) );
		$this->assertFalse( Edd::is_enabled( 'email' ) );
		$this->assertTrue( Edd::is_enabled( 'cart' ) );
		$this->assertTrue( Edd::is_enabled( 'tracking' ) );
		$this->assertFalse( Edd::is_enabled( 'unknown' ) );
	}

	public function test_is_enabled_and_register_hooks_do_not_translate() {
		foreach ( array( '__', '_x', 'esc_html__', 'esc_attr__', 'translate' ) as $fn ) {
			Functions\expect( $fn )->never();
		}
		Functions\when( 'is_admin' )->justReturn( false );
		$loader = \Mockery::mock();
		$loader->shouldReceive( 'add_action', 'add_filter' );

		$this->assertTrue( Edd::is_enabled( 'cart' ) );
		( new Edd() )->register_hooks( $loader );
	}

	public function test_register_hooks_when_disabled_only_registers_settings_save() {
		$this->setOptions( array( Edd::OPTION => array( 'enable' => 'off' ) ) );
		Functions\when( 'is_admin' )->justReturn( true );
		$loader = \Mockery::mock();
		$edd    = new Edd();
		$loader->shouldReceive( 'add_action' )->once()->with( 'admin_init', $edd, 'wps_tofw_edd_save_settings' );
		$loader->shouldNotReceive( 'add_filter' );

		$edd->register_hooks( $loader );
	}

	public function test_register_hooks_when_enabled_covers_all_locations() {
		Functions\when( 'is_admin' )->justReturn( false );
		$hooks  = array();
		$loader = \Mockery::mock();
		$loader->shouldReceive( 'add_action', 'add_filter' )->andReturnUsing(
			static function ( $hook, $component, $callback ) use ( &$hooks ) {
				$hooks[ $hook ] = $callback;
			}
		);

		( new Edd() )->register_hooks( $loader );

		$this->assertSame( 'wps_tofw_edd_product_page', $hooks['woocommerce_single_product_summary'] );
		$this->assertSame( 'wps_tofw_edd_render_product_block', $hooks['render_block'] );
		$this->assertSame( 'wps_tofw_edd_add_to_rates', $hooks['woocommerce_package_rates'] );
		$this->assertSame( 'wps_tofw_edd_after_shipping_rate', $hooks['woocommerce_after_shipping_rate'] );
		$this->assertSame( 'wps_tofw_edd_on_classic_order', $hooks['woocommerce_checkout_order_processed'] );
		$this->assertSame( 'wps_tofw_edd_store_on_order', $hooks['woocommerce_store_api_checkout_order_processed'] );
		$this->assertSame( 'wps_tofw_edd_email', $hooks['woocommerce_email_after_order_table'] );
		$this->assertSame( 'wps_tofw_edd_order_details', $hooks['woocommerce_order_details_after_order_table'] );
		$this->assertArrayNotHasKey( 'admin_init', $hooks );
	}
}
