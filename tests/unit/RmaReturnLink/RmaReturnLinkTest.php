<?php
/**
 * WPS-8405: "Return / Exchange" (RMA) button on the tracking pages.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\RmaReturnLink;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Tests\Unit\TestCase;
use Wps_Tofw_Rma_Return_Link;

/**
 * Tests for Wps_Tofw_Rma_Return_Link: visibility matrix, URL building (never with the
 * order key) and output escaping.
 */
class RmaReturnLinkTest extends TestCase {

	/** Order key of every mocked order; must never appear in a generated URL. */
	const ORDER_KEY = 'wc_order_SeCrEtKeY123';

	/** Order owner user ID. */
	const OWNER_ID = 7;

	/** Billing email of every mocked order. */
	const EMAIL = 'Buyer@Example.com';

	/** URL of the RMA request form page used by get_request_url(). */
	const RMA_PAGE_URL = 'https://shop.test/rma-request/';

	/** URL of the RMA guest form page. */
	const RMA_GUEST_URL = 'https://shop.test/rma-guest-form/';

	/** My Account page URL. */
	const MYACCOUNT_URL = 'https://shop.test/my-account/';

	/**
	 * Current user ID returned by get_current_user_id().
	 *
	 * @var int
	 */
	private $user_id = 0;

	/**
	 * Post status per page ID returned by get_post_status().
	 *
	 * @var array
	 */
	private $post_status = array();

	/**
	 * Load the class and stub the WordPress functions it uses.
	 */
	protected function set_up() {
		parent::set_up();
		$this->requirePluginFile( 'includes/class-wps-tofw-rma-return-link.php' );
		$this->stubCommonWpFunctions();
		$this->setOptions();

		$this->user_id     = 0;
		$this->post_status = array();
		// No real PHP session in unit tests: provide the superglobal the guest check reads.
		if ( ! isset( $_SESSION ) || ! is_array( $_SESSION ) ) {
			$_SESSION = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
		unset( $_SESSION['wps_tofw_email'] );

		$user_id     = &$this->user_id;
		$post_status = &$this->post_status;
		Functions\when( 'get_current_user_id' )->alias(
			static function () use ( &$user_id ) {
				return $user_id;
			}
		);
		Functions\when( 'is_user_logged_in' )->alias(
			static function () use ( &$user_id ) {
				return $user_id > 0;
			}
		);
		Functions\when( 'get_post_status' )->alias(
			static function ( $id ) use ( &$post_status ) {
				return isset( $post_status[ $id ] ) ? $post_status[ $id ] : false;
			}
		);
		Functions\when( 'get_permalink' )->alias(
			static function ( $id ) {
				$map = array(
					11 => self::RMA_PAGE_URL,
					12 => self::RMA_GUEST_URL,
				);
				return isset( $map[ $id ] ) ? $map[ $id ] : false;
			}
		);
		Functions\when( 'wc_get_page_permalink' )->alias(
			static function ( $page ) {
				return 'myaccount' === $page ? self::MYACCOUNT_URL : '';
			}
		);
		Functions\when( 'add_query_arg' )->alias(
			static function ( $key, $value, $url ) {
				return $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . rawurlencode( $key ) . '=' . rawurlencode( (string) $value );
			}
		);
		Functions\when( 'sanitize_email' )->alias(
			static function ( $email ) {
				return filter_var( trim( (string) $email ), FILTER_SANITIZE_EMAIL );
			}
		);
		// No RMA plugin installed unless a test says otherwise.
		Functions\when( 'is_plugin_active' )->justReturn( false );
	}

	/**
	 * Clean the session state shared with the guest tracking form.
	 */
	protected function tear_down() {
		if ( isset( $_SESSION ) && is_array( $_SESSION ) ) {
			unset( $_SESSION['wps_tofw_email'] );
		}
		parent::tear_down();
	}

	/**
	 * Mocked order with a view-order URL that has no key (like WooCommerce) and an order key
	 * that must never leak.
	 *
	 * @param array $props Extra mockOrder() props.
	 * @return \Mockery\MockInterface|\WC_Order
	 */
	private function order( array $props = array() ) {
		return $this->mockOrder(
			array_merge(
				array(
					'id'                 => 501,
					'status'             => 'completed',
					'billing_email'      => self::EMAIL,
					'customer_id'        => self::OWNER_ID,
					'get_order_key'      => self::ORDER_KEY,
					'get_view_order_url' => 'https://shop.test/my-account/view-order/501/',
				),
				$props
			)
		);
	}

	/**
	 * Force the RMA plugin "active" through the documented filter.
	 *
	 * @param bool $active Active state.
	 */
	private function rmaActive( $active = true ) {
		Filters\expectApplied( 'wps_tofw_is_rma_active' )->andReturn( $active );
	}

	/**
	 * Render the button for an order ID and return the HTML.
	 *
	 * @param int $order_id Order ID.
	 * @return string
	 */
	private function render( $order_id = 501 ) {
		ob_start();
		( new Wps_Tofw_Rma_Return_Link() )->render_button( $order_id );
		return (string) ob_get_clean();
	}

	/**
	 * Assert a URL does not carry the order key in any form.
	 *
	 * @param string $url URL.
	 */
	private function assertNoOrderKey( $url ) {
		$this->assertStringNotContainsString( self::ORDER_KEY, $url );
		$this->assertStringNotContainsString( 'key=', $url );
		$this->assertStringNotContainsString( 'order_key', $url );
	}

	// ---------------------------------------------------------------------------------------------
	// RMA detection.
	// ---------------------------------------------------------------------------------------------

	public function test_rma_inactive_when_no_rma_plugin_is_present() {
		$this->assertFalse( Wps_Tofw_Rma_Return_Link::is_rma_active() );
	}

	public function test_rma_active_when_lite_plugin_is_active() {
		Functions\when( 'is_plugin_active' )->alias(
			static function ( $basename ) {
				return 'woo-refund-and-exchange-lite/woo-refund-and-exchange-lite.php' === $basename;
			}
		);
		$this->assertTrue( Wps_Tofw_Rma_Return_Link::is_rma_active() );
	}

	public function test_rma_active_when_pro_plugin_is_active() {
		Functions\when( 'is_plugin_active' )->alias(
			static function ( $basename ) {
				return 'rma-return-refund-exchange-for-woocommerce-pro/rma-return-refund-exchange-for-woocommerce-pro.php' === $basename;
			}
		);
		$this->assertTrue( Wps_Tofw_Rma_Return_Link::is_rma_active() );
	}

	public function test_is_rma_active_filter_can_force_both_ways() {
		Filters\expectApplied( 'wps_tofw_is_rma_active' )->once()->with( false )->andReturn( true );
		$this->assertTrue( Wps_Tofw_Rma_Return_Link::is_rma_active() );
	}

	public function test_is_rma_active_filter_can_disable_an_active_plugin() {
		Functions\when( 'is_plugin_active' )->justReturn( true );
		Filters\expectApplied( 'wps_tofw_is_rma_active' )->once()->with( true )->andReturn( false );
		$this->assertFalse( Wps_Tofw_Rma_Return_Link::is_rma_active() );
	}

	// ---------------------------------------------------------------------------------------------
	// Owner / verified guest.
	// ---------------------------------------------------------------------------------------------

	public function test_is_order_owner() {
		$order = $this->order();

		$this->user_id = 0;
		$this->assertFalse( Wps_Tofw_Rma_Return_Link::is_order_owner( $order ), 'guest is not the owner' );

		$this->user_id = 99;
		$this->assertFalse( Wps_Tofw_Rma_Return_Link::is_order_owner( $order ), 'another customer is not the owner' );

		$this->user_id = self::OWNER_ID;
		$this->assertTrue( Wps_Tofw_Rma_Return_Link::is_order_owner( $order ) );
	}

	public function test_guest_order_has_no_owner_even_for_user_zero() {
		$order         = $this->order( array( 'customer_id' => 0 ) );
		$this->user_id = 0;
		$this->assertFalse( Wps_Tofw_Rma_Return_Link::is_order_owner( $order ) );
	}

	public function test_verified_guest_requires_session_email_matching_billing_email_case_insensitively() {
		$order = $this->order();

		$this->assertFalse( Wps_Tofw_Rma_Return_Link::is_verified_guest( $order ), 'no session email' );

		$_SESSION['wps_tofw_email'] = 'someone-else@example.com';
		$this->assertFalse( Wps_Tofw_Rma_Return_Link::is_verified_guest( $order ), 'other email' );

		$_SESSION['wps_tofw_email'] = 'buyer@example.COM';
		$this->assertTrue( Wps_Tofw_Rma_Return_Link::is_verified_guest( $order ) );
	}

	public function test_logged_in_user_is_never_a_verified_guest() {
		$_SESSION['wps_tofw_email'] = self::EMAIL;
		$this->user_id              = 99;
		$this->assertFalse( Wps_Tofw_Rma_Return_Link::is_verified_guest( $this->order() ) );
	}

	public function test_order_without_billing_email_cannot_be_verified() {
		$_SESSION['wps_tofw_email'] = '';
		$this->assertFalse( Wps_Tofw_Rma_Return_Link::is_verified_guest( $this->order( array( 'billing_email' => '' ) ) ) );
	}

	// ---------------------------------------------------------------------------------------------
	// Eligible statuses.
	// ---------------------------------------------------------------------------------------------

	/**
	 * @return array
	 */
	public function statusProvider() {
		return array(
			'completed'  => array( 'completed', true ),
			'delivered'  => array( 'delivered', true ),
			'processing' => array( 'processing', false ),
			'on-hold'    => array( 'on-hold', false ),
			'pending'    => array( 'pending', false ),
			'shipped'    => array( 'shipped', false ),
			'cancelled'  => array( 'cancelled', false ),
			'refunded'   => array( 'refunded', false ),
			'failed'     => array( 'failed', false ),
		);
	}

	/**
	 * @dataProvider statusProvider
	 *
	 * @param string $status   Order status.
	 * @param bool   $eligible Expected.
	 */
	public function test_only_delivered_orders_are_eligible( $status, $eligible ) {
		$this->assertSame( $eligible, Wps_Tofw_Rma_Return_Link::is_order_eligible( $this->order( array( 'status' => $status ) ) ) );
	}

	public function test_eligible_statuses_filter_accepts_wc_prefixed_statuses() {
		Filters\expectApplied( 'wps_tofw_rma_eligible_statuses' )->andReturn( array( 'wc-shipped' ) );

		$this->assertTrue( Wps_Tofw_Rma_Return_Link::is_order_eligible( $this->order( array( 'status' => 'shipped' ) ) ) );
		$this->assertFalse( Wps_Tofw_Rma_Return_Link::is_order_eligible( $this->order( array( 'status' => 'completed' ) ) ) );
	}

	// ---------------------------------------------------------------------------------------------
	// Request URL.
	// ---------------------------------------------------------------------------------------------

	public function test_owner_url_uses_published_rma_request_page_with_order_id_only() {
		$this->setOptions( array( 'wps_rma_return_request_form_page_id' => 11 ) );
		$this->post_status[11] = 'publish';
		$this->user_id         = self::OWNER_ID;

		$url = Wps_Tofw_Rma_Return_Link::get_request_url( $this->order() );

		$this->assertSame( self::RMA_PAGE_URL . '?order_id=501', $url );
		$this->assertNoOrderKey( $url );
	}

	public function test_owner_url_falls_back_to_view_order_when_rma_page_is_missing_or_unpublished() {
		$this->user_id = self::OWNER_ID;
		$this->assertSame( 'https://shop.test/my-account/view-order/501/', Wps_Tofw_Rma_Return_Link::get_request_url( $this->order() ) );

		$this->setOptions( array( 'wps_rma_return_request_form_page_id' => 11 ) );
		$this->post_status[11] = 'draft';
		$url                   = Wps_Tofw_Rma_Return_Link::get_request_url( $this->order() );
		$this->assertSame( 'https://shop.test/my-account/view-order/501/', $url );
		$this->assertNoOrderKey( $url );
	}

	public function test_verified_guest_url_is_rma_guest_form_without_order_key() {
		$this->setOptions( array( 'wps_rma_guest_form_page_id' => 12 ) );
		$this->post_status[12]      = 'publish';
		$_SESSION['wps_tofw_email'] = self::EMAIL;

		$url = Wps_Tofw_Rma_Return_Link::get_request_url( $this->order( array( 'customer_id' => 0 ) ) );

		$this->assertSame( self::RMA_GUEST_URL, $url );
		$this->assertNoOrderKey( $url );
	}

	public function test_verified_guest_url_falls_back_to_my_account_without_order_key() {
		$_SESSION['wps_tofw_email'] = self::EMAIL;

		$url = Wps_Tofw_Rma_Return_Link::get_request_url( $this->order( array( 'customer_id' => 0 ) ) );

		$this->assertSame( self::MYACCOUNT_URL, $url );
		$this->assertNoOrderKey( $url );
	}

	public function test_guest_form_url_filter() {
		Filters\expectApplied( 'wps_tofw_rma_guest_form_url' )->once()->with( '' )->andReturn( 'https://shop.test/custom-guest/' );
		$this->assertSame( 'https://shop.test/custom-guest/', Wps_Tofw_Rma_Return_Link::get_guest_form_url() );
	}

	public function test_stranger_gets_no_url() {
		$this->user_id = 0;
		$this->assertSame( '', Wps_Tofw_Rma_Return_Link::get_request_url( $this->order() ) );

		$this->user_id = 99;
		$this->assertSame( '', Wps_Tofw_Rma_Return_Link::get_request_url( $this->order() ) );
	}

	public function test_request_url_filter_receives_url_and_order() {
		$this->user_id = self::OWNER_ID;
		$order         = $this->order();
		Filters\expectApplied( 'wps_tofw_rma_request_url' )
			->once()
			->with( 'https://shop.test/my-account/view-order/501/', $order )
			->andReturn( 'https://shop.test/filtered/' );

		$this->assertSame( 'https://shop.test/filtered/', Wps_Tofw_Rma_Return_Link::get_request_url( $order ) );
	}

	// ---------------------------------------------------------------------------------------------
	// render_button(): visibility matrix.
	// ---------------------------------------------------------------------------------------------

	/**
	 * Setting, RMA active, status, viewer => visible.
	 *
	 * @return array
	 */
	public function visibilityProvider() {
		return array(
			'owner, completed'                    => array( 'on', true, 'completed', 'owner', true ),
			'owner, delivered'                    => array( 'on', true, 'delivered', 'owner', true ),
			'verified guest, completed'           => array( 'on', true, 'completed', 'guest', true ),
			'setting missing defaults to on'      => array( null, true, 'completed', 'owner', true ),
			'owner, processing'                   => array( 'on', true, 'processing', 'owner', false ),
			'owner, on-hold'                      => array( 'on', true, 'on-hold', 'owner', false ),
			'verified guest, processing'          => array( 'on', true, 'processing', 'guest', false ),
			'setting off'                         => array( 'off', true, 'completed', 'owner', false ),
			'setting empty'                       => array( '', true, 'completed', 'owner', false ),
			'rma inactive'                        => array( 'on', false, 'completed', 'owner', false ),
			'rma inactive, verified guest'        => array( 'on', false, 'completed', 'guest', false ),
			'stranger guest (order id only)'      => array( 'on', true, 'completed', 'stranger', false ),
			'other logged-in customer'            => array( 'on', true, 'completed', 'other_user', false ),
			'other customer with matching session' => array( 'on', true, 'completed', 'other_user_with_session', false ),
		);
	}

	/**
	 * @dataProvider visibilityProvider
	 *
	 * @param string|null $setting    Value of wps_tofw_enable_rma_return_button (null = not saved).
	 * @param bool        $rma_active Whether the RMA plugin is active.
	 * @param string      $status     Order status.
	 * @param string      $viewer     owner|guest|stranger|other_user|other_user_with_session.
	 * @param bool        $visible    Expected visibility.
	 */
	public function test_visibility_matrix( $setting, $rma_active, $status, $viewer, $visible ) {
		if ( null !== $setting ) {
			$this->setOptions( array( 'wps_tofw_enable_rma_return_button' => $setting ) );
		}
		$this->rmaActive( $rma_active );
		$this->order( array( 'status' => $status ) );

		switch ( $viewer ) {
			case 'owner':
				$this->user_id = self::OWNER_ID;
				break;
			case 'guest':
				$_SESSION['wps_tofw_email'] = self::EMAIL;
				break;
			case 'other_user':
				$this->user_id = 99;
				break;
			case 'other_user_with_session':
				$this->user_id              = 99;
				$_SESSION['wps_tofw_email'] = self::EMAIL;
				break;
		}

		$html = $this->render();

		if ( $visible ) {
			$this->assertStringContainsString( 'wps-tofw-rma-return-button', $html );
			$this->assertStringContainsString( 'Return / Exchange', $html );
			$this->assertNoOrderKey( $html );
		} else {
			$this->assertSame( '', trim( $html ) );
		}
	}

	public function test_no_output_for_unknown_order() {
		$this->rmaActive();
		$this->user_id = self::OWNER_ID;
		$this->stubWcGetOrder();

		$this->assertSame( '', trim( $this->render( 404 ) ) );
	}

	public function test_no_output_when_url_filter_returns_empty() {
		$this->rmaActive();
		$this->order();
		$this->user_id = self::OWNER_ID;
		Filters\expectApplied( 'wps_tofw_rma_request_url' )->andReturn( '' );

		$this->assertSame( '', trim( $this->render() ) );
	}

	public function test_owner_button_links_to_view_order_and_never_to_order_key() {
		$this->rmaActive();
		$this->order();
		$this->user_id = self::OWNER_ID;

		$html = $this->render();

		$this->assertMatchesRegularExpression( '#<a href="https://shop\.test/my-account/view-order/501/" class="button wps-tofw-rma-return-button">#', $html );
		$this->assertNoOrderKey( $html );
	}

	public function test_custom_label_and_url_are_escaped() {
		$this->setOptions( array( 'wps_tofw_rma_return_button_label' => '<script>alert(1)</script>Return' ) );
		$this->rmaActive();
		$this->order();
		$this->user_id = self::OWNER_ID;
		Filters\expectApplied( 'wps_tofw_rma_request_url' )->andReturn( 'https://shop.test/r?a=1&b="x"' );
		$escaped = array();
		Functions\when( 'esc_url' )->alias(
			static function ( $url ) use ( &$escaped ) {
				$escaped[] = $url;
				return 'ESCAPED_URL';
			}
		);

		$html = $this->render();

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;alert(1)&lt;/script&gt;Return', $html );
		$this->assertStringContainsString( 'href="ESCAPED_URL"', $html );
		$this->assertSame( array( 'https://shop.test/r?a=1&b="x"' ), $escaped, 'the URL goes through esc_url()' );
	}

	public function test_empty_label_falls_back_to_default() {
		$this->setOptions( array( 'wps_tofw_rma_return_button_label' => '' ) );
		$this->rmaActive();
		$this->order();
		$this->user_id = self::OWNER_ID;

		$this->assertStringContainsString( '>Return / Exchange</a>', $this->render() );
	}

	public function test_render_accepts_an_order_object() {
		$this->rmaActive();
		$order         = $this->order();
		$this->user_id = self::OWNER_ID;

		ob_start();
		( new Wps_Tofw_Rma_Return_Link() )->render_button( $order );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'wps-tofw-rma-return-button', $html );
	}

	// ---------------------------------------------------------------------------------------------
	// Wiring: hook registration and every free tracking template fires the action.
	// ---------------------------------------------------------------------------------------------

	public function test_render_button_is_hooked_to_the_render_action() {
		$source = (string) file_get_contents( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/class-track-orders-for-woocommerce.php' );

		$this->assertStringContainsString( "includes/class-wps-tofw-rma-return-link.php'", $source );
		$this->assertMatchesRegularExpression(
			"/add_action\(\s*'wps_tofw_render_return_button',\s*\\\$wps_tofw_rma_link,\s*'render_button',\s*10,\s*1\s*\)/",
			$source
		);
	}

	/**
	 * @return array
	 */
	public function templateProvider() {
		return array(
			'template1'    => array( 'template/wps-track-order-myaccount-page-template1.php', '$order_id' ),
			'template2'    => array( 'template/wps-track-order-myaccount-page-template2.php', '$order_id' ),
			'template3'    => array( 'template/wps-track-order-myaccount-page-template3.php', '$order_id' ),
			'map template' => array( 'template/wps-map-new-template.php', '$order_id' ),
			'FedEx page'   => array( 'template/wps-order-tracking-page.php', 'absint( $wps_user_order_id )' ),
		);
	}

	/**
	 * @dataProvider templateProvider
	 *
	 * @param string $file Template file.
	 * @param string $arg  Expected argument expression.
	 */
	public function test_tracking_template_fires_render_action_once_with_order_id( $file, $arg ) {
		$source = (string) file_get_contents( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . $file );

		$this->assertSame( 1, substr_count( $source, "do_action( 'wps_tofw_render_return_button'" ), $file );
		$this->assertStringContainsString( "do_action( 'wps_tofw_render_return_button', {$arg} );", $source );
	}
}
