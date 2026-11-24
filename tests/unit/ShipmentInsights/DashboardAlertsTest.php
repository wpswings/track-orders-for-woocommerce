<?php
/**
 * WPS-8404: admin notice and dashboard widget are limited to manage_options users.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\ShipmentInsights;

use Brain\Monkey\Functions;

/**
 * Capability gate, notice content, dismissal and dashboard widget rendering.
 */
class DashboardAlertsTest extends InsightsTestCase {

	/**
	 * Capabilities the current user has.
	 *
	 * @var array
	 */
	protected $caps = array();

	/**
	 * Enable alerts, one active alert, admin screen = dashboard.
	 */
	protected function set_up() {
		parent::set_up();
		$caps = &$this->caps;
		$this->caps = array( 'manage_options' );
		Functions\when( 'current_user_can' )->alias(
			static function ( $cap ) use ( &$caps ) {
				return in_array( $cap, $caps, true );
			}
		);
		Functions\when( 'get_current_screen' )->justReturn( (object) array( 'id' => 'dashboard' ) );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_user_meta' )->justReturn( '' );
		Functions\when( 'admin_url' )->alias(
			static function ( $path = '' ) {
				return 'http://example.org/wp-admin/' . $path;
			}
		);
		Functions\when( 'wp_nonce_url' )->alias(
			static function ( $url ) {
				return $url . '&_wpnonce=abc';
			}
		);
		$this->order( array( 'id' => 55 ) );
		$this->setOptions(
			array(
				'wps_tofw_delivery_alerts_enable' => 'on',
				'wps_tofw_delivery_active_alerts' => array(
					55 => array(
						'issues'   => array( 'exception', 'expected_days' ),
						'detected' => 1,
					),
				),
			)
		);
	}

	/**
	 * Render the admin notice.
	 *
	 * @return string
	 */
	protected function notice() {
		ob_start();
		$this->insights->wps_tofw_admin_notice();
		return (string) ob_get_clean();
	}

	/**
	 * Administrators see the notice with the count, analytics link and nonce'd dismiss link.
	 */
	public function test_admin_sees_notice() {
		$html = $this->notice();

		$this->assertStringContainsString( 'notice-warning', $html );
		$this->assertStringContainsString( '1 shipment has a delivery exception or is running late.', $html );
		$this->assertStringContainsString( 'tofw_tab=track-orders-for-woocommerce-shipping-analytics', $html );
		$this->assertStringContainsString( 'action=wps_tofw_dismiss_delivery_alerts', $html );
		$this->assertStringContainsString( '_wpnonce=', $html );
	}

	/**
	 * Shop managers / subscribers (no manage_options) never see the notice.
	 */
	public function test_users_without_manage_options_see_nothing() {
		$this->caps = array( 'manage_woocommerce', 'read' );
		$this->assertSame( '', $this->notice() );

		Functions\expect( 'wp_add_dashboard_widget' )->never();
		$this->insights->wps_tofw_register_dashboard_widget();
	}

	/**
	 * Widget registered for admins only.
	 */
	public function test_dashboard_widget_registered_for_admin() {
		Functions\expect( 'wp_add_dashboard_widget' )->once()->with( 'wps_tofw_delivery_alerts_widget', \Mockery::type( 'string' ), array( $this->insights, 'wps_tofw_render_dashboard_widget' ) );
		$this->insights->wps_tofw_register_dashboard_widget();
	}

	/**
	 * Notice/widget off when alerts are disabled or the dashboard option is off.
	 */
	public function test_disabled_settings_hide_notice_and_widget() {
		Functions\expect( 'wp_add_dashboard_widget' )->never();

		$this->setOptions( array( 'wps_tofw_delivery_alerts_dashboard_notice' => 'off' ) );
		$this->assertSame( '', $this->notice() );
		$this->insights->wps_tofw_register_dashboard_widget();

		$this->setOptions(
			array(
				'wps_tofw_delivery_alerts_dashboard_notice' => 'on',
				'wps_tofw_delivery_alerts_enable'           => 'off',
			)
		);
		$this->assertSame( '', $this->notice() );
		$this->insights->wps_tofw_register_dashboard_widget();
	}

	/**
	 * Notice only on allowed screens and only when there are alerts.
	 */
	public function test_notice_screen_and_empty_state() {
		Functions\when( 'get_current_screen' )->justReturn( (object) array( 'id' => 'edit-post' ) );
		$this->assertSame( '', $this->notice() );

		Functions\when( 'get_current_screen' )->justReturn( (object) array( 'id' => 'woocommerce_page_wc-orders' ) );
		$this->assertNotSame( '', $this->notice() );

		$this->setOptions( array( 'wps_tofw_delivery_active_alerts' => array() ) );
		$this->assertSame( '', $this->notice() );
	}

	/**
	 * A dismissed alert list stays hidden until the list changes.
	 */
	public function test_dismissed_signature_hides_notice_until_list_changes() {
		$signature = md5( wp_json_encode( array( 55 ) ) );
		Functions\when( 'get_user_meta' )->justReturn( $signature );
		$this->assertSame( '', $this->notice() );

		$this->order( array( 'id' => 56 ) );
		$this->setOptions(
			array(
				'wps_tofw_delivery_active_alerts' => array(
					55 => array( 'issues' => array( 'exception' ), 'detected' => 1 ),
					56 => array( 'issues' => array( 'exception' ), 'detected' => 2 ),
				),
			)
		);
		$this->assertStringContainsString( '2 shipments have a delivery exception or are running late.', $this->notice() );
	}

	/**
	 * Dismiss handler refuses users without manage_options before touching user meta.
	 */
	public function test_dismiss_requires_manage_options() {
		$this->caps = array( 'manage_woocommerce' );
		Functions\when( 'wp_die' )->alias(
			static function () {
				throw new \RuntimeException( 'wp_die' );
			}
		);
		Functions\expect( 'update_user_meta' )->never();

		$this->expectException( \RuntimeException::class );
		$this->insights->wps_tofw_dismiss_notice();
	}

	/**
	 * Dismiss handler checks the nonce.
	 */
	public function test_dismiss_checks_nonce() {
		Functions\expect( 'check_admin_referer' )->once()->with( 'wps_tofw_dismiss_delivery_alerts' )->andReturnUsing(
			static function () {
				throw new \RuntimeException( 'bad nonce' );
			}
		);
		Functions\expect( 'update_user_meta' )->never();

		$this->expectExceptionMessage( 'bad nonce' );
		$this->insights->wps_tofw_dismiss_notice();
	}

	/**
	 * Widget lists the alerted orders with their condition labels and a "View all" link.
	 */
	public function test_widget_renders_alerts() {
		ob_start();
		$this->insights->wps_tofw_render_dashboard_widget();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '#55', $html );
		$this->assertStringContainsString( 'Shipment is in Exception', $html );
		$this->assertStringContainsString( 'Shipped longer ago than the expected delivery days', $html );
		$this->assertStringContainsString( 'View all (1)', $html );
	}

	/**
	 * Widget empty state.
	 */
	public function test_widget_empty_state() {
		$this->setOptions( array( 'wps_tofw_delivery_active_alerts' => array() ) );
		ob_start();
		$this->insights->wps_tofw_render_dashboard_widget();
		$this->assertStringContainsString( 'No delivery exceptions or late shipments detected.', (string) ob_get_clean() );
	}
}
