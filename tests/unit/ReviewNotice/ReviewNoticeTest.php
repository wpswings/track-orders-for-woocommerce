<?php
/**
 * WPS-8395: admin review request notice.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\ReviewNotice;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Tests\Unit\TestCase;
use Track_Orders_For_Woocommerce_Review_Notice as Notice;

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

/**
 * Thrown by the wp_send_json_* / check_ajax_referer stubs to emulate wp_die().
 */
class JsonResponseException extends \RuntimeException {

	/**
	 * Whether the response was a success.
	 *
	 * @var bool
	 */
	public $success;

	/**
	 * Response data.
	 *
	 * @var mixed
	 */
	public $data;

	/**
	 * HTTP status.
	 *
	 * @var int|null
	 */
	public $status;

	/**
	 * Constructor.
	 *
	 * @param bool     $success Success flag.
	 * @param mixed    $data    Data.
	 * @param int|null $status  HTTP status.
	 */
	public function __construct( $success, $data = null, $status = null ) {
		parent::__construct( $success ? 'json success' : 'json error' );
		$this->success = $success;
		$this->data    = $data;
		$this->status  = $status;
	}
}

/**
 * Tests for Track_Orders_For_Woocommerce_Review_Notice.
 */
class ReviewNoticeTest extends TestCase {

	/**
	 * Transients store.
	 *
	 * @var array
	 */
	private $transients = array();

	/**
	 * Capabilities of the current user.
	 *
	 * @var string[]
	 */
	private $caps = array( 'manage_woocommerce' );

	/**
	 * Current screen id (null = no screen).
	 *
	 * @var string|null
	 */
	private $screen_id = 'dashboard';

	/**
	 * Number of orders with tracking returned by wc_get_orders().
	 *
	 * @var int
	 */
	private $tracked_orders = 0;

	/**
	 * Arguments of every wc_get_orders() call.
	 *
	 * @var array
	 */
	private $order_queries = array();

	/**
	 * Arguments of every check_ajax_referer() call.
	 *
	 * @var array
	 */
	private $nonce_checks = array();

	/**
	 * Whether check_ajax_referer() accepts the nonce.
	 *
	 * @var bool
	 */
	private $nonce_valid = true;

	/**
	 * Load the class and stub WordPress.
	 */
	protected function set_up() {
		parent::set_up();
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-review-notice.php' );
		$this->stubCommonWpFunctions();
		$this->setOptions();

		$_GET  = array();
		$_POST = array();

		$this->transients    = array();
		$this->caps          = array( 'manage_woocommerce' );
		$this->screen_id     = 'dashboard';
		$this->tracked_orders = 0;
		$this->order_queries  = array();

		$transients = &$this->transients;
		Functions\when( 'get_transient' )->alias(
			static function ( $key ) use ( &$transients ) {
				return array_key_exists( $key, $transients ) ? $transients[ $key ] : false;
			}
		);
		Functions\when( 'set_transient' )->alias(
			static function ( $key, $value ) use ( &$transients ) {
				$transients[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_transient' )->alias(
			static function ( $key ) use ( &$transients ) {
				unset( $transients[ $key ] );
				return true;
			}
		);
		Functions\when( 'wp_parse_args' )->alias(
			static function ( $args, $defaults = array() ) {
				return array_merge( $defaults, (array) $args );
			}
		);

		$test = $this;
		Functions\when( 'current_user_can' )->alias(
			static function ( $cap ) use ( $test ) {
				return $test->userCan( $cap );
			}
		);
		Functions\when( 'get_current_screen' )->alias(
			static function () use ( $test ) {
				return $test->currentScreen();
			}
		);
		Functions\when( 'wc_get_order_statuses' )->justReturn(
			array(
				'wc-pending'    => 'Pending',
				'wc-processing' => 'Processing',
				'wc-completed'  => 'Completed',
			)
		);
		Functions\when( 'wc_get_orders' )->alias(
			static function ( $args ) use ( $test ) {
				return $test->queryOrders( $args );
			}
		);
		Functions\when( 'wp_send_json_success' )->alias(
			static function ( $data = null, $status = null ) {
				throw new JsonResponseException( true, $data, $status );
			}
		);
		Functions\when( 'wp_send_json_error' )->alias(
			static function ( $data = null, $status = null ) {
				throw new JsonResponseException( false, $data, $status );
			}
		);
		$this->nonce_checks = array();
		$this->nonce_valid  = true;
		Functions\when( 'check_ajax_referer' )->alias(
			static function ( $action = -1, $query_arg = false ) use ( $test ) {
				return $test->checkNonce( $action, $query_arg );
			}
		);
	}

	/**
	 * Reset superglobals.
	 */
	protected function tear_down() {
		$_GET  = array();
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Capability check used by the current_user_can stub.
	 *
	 * @param string $cap Capability.
	 * @return bool
	 */
	public function userCan( $cap ) {
		return in_array( $cap, $this->caps, true );
	}

	/**
	 * Fake check_ajax_referer(): records the call and dies (403, -1) on an invalid nonce.
	 *
	 * @param string|int  $action    Nonce action.
	 * @param string|bool $query_arg Request key.
	 * @return int
	 * @throws JsonResponseException When the nonce is invalid (emulates wp_die()).
	 */
	public function checkNonce( $action, $query_arg ) {
		$this->nonce_checks[] = array( $action, $query_arg );
		if ( ! $this->nonce_valid ) {
			throw new JsonResponseException( false, -1, 403 );
		}
		return 1;
	}

	/**
	 * Screen used by the get_current_screen stub.
	 *
	 * @return object|null
	 */
	public function currentScreen() {
		return null === $this->screen_id ? null : (object) array( 'id' => $this->screen_id );
	}

	/**
	 * Fake wc_get_orders(): returns up to `limit` IDs of tracked orders.
	 *
	 * @param array $args Query args.
	 * @return int[]
	 */
	public function queryOrders( $args ) {
		$this->order_queries[] = $args;
		$count                 = min( $this->tracked_orders, isset( $args['limit'] ) ? (int) $args['limit'] : $this->tracked_orders );
		return $count > 0 ? range( 1, $count ) : array();
	}

	/**
	 * Render the notice and return its HTML.
	 *
	 * @param Notice|null $notice Instance (new one by default).
	 * @return string
	 */
	private function render( $notice = null ) {
		$notice = $notice ? $notice : new Notice();
		ob_start();
		$notice->wps_tofw_render_notice();
		return (string) ob_get_clean();
	}

	/**
	 * Run the AJAX handler and return the JSON response.
	 *
	 * @param array $post $_POST data.
	 * @return JsonResponseException
	 */
	private function ajax( array $post ) {
		$_POST = $post;
		try {
			( new Notice() )->wps_tofw_handle_ajax();
		} catch ( JsonResponseException $response ) {
			return $response;
		}
		$this->fail( 'The AJAX handler did not send a JSON response' );
		return null;
	}

	/**
	 * Stored notice state.
	 *
	 * @return array
	 */
	private function state() {
		return $this->getOption( Notice::OPTION, array() );
	}

	/* ------------------------------------------------------------------ threshold */

	/**
	 * Default threshold is 10, filterable, never below 1.
	 */
	public function test_threshold_default_and_filter() {
		$this->assertSame( 10, Notice::wps_tofw_get_threshold() );

		Filters\expectApplied( 'wps_tofw_review_notice_threshold' )->once()->with( 10 )->andReturn( 25 );
		$this->assertSame( 25, Notice::wps_tofw_get_threshold() );
	}

	/**
	 * A filtered threshold of 0 or less is clamped to 1.
	 */
	public function test_threshold_is_clamped_to_one() {
		Filters\expectApplied( 'wps_tofw_review_notice_threshold' )->andReturn( -5 );
		$this->assertSame( 1, Notice::wps_tofw_get_threshold() );
	}

	/**
	 * Tracked orders are counted by the tracking number meta, HPOS-safe, limited to the threshold.
	 */
	public function test_count_queries_orders_with_tracking_number_meta() {
		$this->tracked_orders = 50;

		$this->assertSame( 10, ( new Notice() )->wps_tofw_get_tracked_orders_count() );

		$this->assertCount( 1, $this->order_queries );
		$args = $this->order_queries[0];
		$this->assertSame( 10, $args['limit'] );
		$this->assertSame( 'ids', $args['return'] );
		$this->assertSame( 'shop_order', $args['type'] );
		$this->assertSame( array( 'wc-pending', 'wc-processing', 'wc-completed' ), $args['status'] );
		$this->assertSame(
			array(
				array(
					'key'     => 'wps_tofw_package_tracking_number',
					'value'   => '',
					'compare' => '!=',
				),
			),
			$args['meta_query']
		);
	}

	/**
	 * The count is cached in a transient and the cache is reused.
	 */
	public function test_count_is_cached_in_transient() {
		$this->tracked_orders = 4;
		$notice               = new Notice();

		$this->assertSame( 4, $notice->wps_tofw_get_tracked_orders_count() );
		$this->assertSame( 4, $this->transients[ Notice::COUNT_TRANSIENT ] );

		$this->tracked_orders = 9;
		$this->assertSame( 4, $notice->wps_tofw_get_tracked_orders_count(), 'Cached value is reused' );
		$this->assertCount( 1, $this->order_queries );
	}

	/**
	 * Below the threshold (9 tracked orders) the notice is not shown.
	 */
	public function test_hidden_below_threshold() {
		$this->tracked_orders = 9;

		$this->assertFalse( ( new Notice() )->wps_tofw_should_show() );
		$this->assertSame( '', $this->render() );
		$this->assertSame( array(), $this->state(), 'Nothing stored before the threshold is met' );
	}

	/**
	 * At the threshold (10 tracked orders) the notice is shown with all four buttons.
	 */
	public function test_shown_at_threshold_with_all_buttons() {
		$this->tracked_orders = 10;

		$html = $this->render();

		$this->assertStringContainsString( 'id="wps-tofw-review-notice"', $html );
		$this->assertStringContainsString( 'notice notice-info is-dismissible', $html );
		$this->assertStringContainsString( '10+ orders', $html );
		$this->assertMatchesRegularExpression( '#href="' . preg_quote( Notice::REVIEW_URL, '#' ) . '"[^>]*data-review-action="rated"[^>]*>Rate us<#', $html );
		$this->assertMatchesRegularExpression( '#href="' . preg_quote( Notice::SUPPORT_URL, '#' ) . '"[^>]*>Need help<#', $html );
		$this->assertMatchesRegularExpression( '#data-review-action="later"[^>]*>Later<#', $html );
		$this->assertMatchesRegularExpression( '#data-review-action="dismiss"[^>]*>Don(&\#039;|\')t ask again<#', $html );
		$this->assertStringContainsString( 'rel="noopener noreferrer"', $html );
	}

	/**
	 * Reaching the threshold is remembered, so the notice survives a later drop in the count.
	 */
	public function test_threshold_reached_is_remembered() {
		$this->tracked_orders = 10;
		$before               = time();

		$this->assertTrue( ( new Notice() )->wps_tofw_threshold_reached() );

		$state = $this->state();
		$this->assertGreaterThanOrEqual( $before, $state['reached_at'] );
		$this->assertArrayNotHasKey( Notice::COUNT_TRANSIENT, $this->transients, 'Count cache cleared once reached' );

		$this->tracked_orders = 0;
		$this->order_queries  = array();
		$this->assertTrue( ( new Notice() )->wps_tofw_should_show() );
		$this->assertSame( array(), $this->order_queries, 'No more order queries once reached' );
	}

	/**
	 * Saving an order flushes the count cache only while the threshold is not reached.
	 */
	public function test_flush_tracked_count() {
		$this->transients[ Notice::COUNT_TRANSIENT ] = 3;
		( new Notice() )->wps_tofw_flush_tracked_count();
		$this->assertArrayNotHasKey( Notice::COUNT_TRANSIENT, $this->transients );

		$this->setOptions( array( Notice::OPTION => array( 'reached_at' => 123 ) ) );
		$this->transients[ Notice::COUNT_TRANSIENT ] = 3;
		( new Notice() )->wps_tofw_flush_tracked_count();
		$this->assertSame( 3, $this->transients[ Notice::COUNT_TRANSIENT ] );
	}

	/**
	 * A corrupt (non-array) option is treated as the default state.
	 */
	public function test_corrupt_state_falls_back_to_defaults() {
		$this->setOptions( array( Notice::OPTION => 'garbage' ) );

		$this->assertSame(
			array(
				'status'       => 'active',
				'snooze_until' => 0,
				'reached_at'   => 0,
			),
			( new Notice() )->wps_tofw_get_state()
		);
	}

	/* ------------------------------------------------------------------ snooze / dismiss */

	/**
	 * While snoozed the notice is hidden; after the snooze it is shown again.
	 */
	public function test_snooze_hides_until_expiry() {
		$this->tracked_orders = 10;

		$this->setOptions( array( Notice::OPTION => array( 'snooze_until' => time() + DAY_IN_SECONDS ) ) );
		$this->assertFalse( ( new Notice() )->wps_tofw_should_show() );
		$this->assertSame( '', $this->render() );

		$this->setOptions( array( Notice::OPTION => array( 'snooze_until' => time() - 1 ) ) );
		$this->assertTrue( ( new Notice() )->wps_tofw_should_show() );
	}

	/**
	 * "Don't ask again" (dismissed) and "Rate us" (rated) hide the notice for good.
	 *
	 * @dataProvider final_status_provider
	 *
	 * @param string $status Stored status.
	 */
	public function test_final_status_never_shows( $status ) {
		$this->tracked_orders = 100;
		$this->setOptions(
			array(
				Notice::OPTION => array(
					'status'     => $status,
					'reached_at' => 1,
				),
			)
		);

		$this->assertFalse( ( new Notice() )->wps_tofw_should_show() );
		$this->assertSame( '', $this->render() );
	}

	/**
	 * Final statuses.
	 *
	 * @return array
	 */
	public function final_status_provider() {
		return array(
			'dismissed' => array( 'dismissed' ),
			'rated'     => array( 'rated' ),
		);
	}

	/* ------------------------------------------------------------------ capability / screens */

	/**
	 * Users without manage_woocommerce never see it (and no order query runs).
	 */
	public function test_hidden_for_users_without_manage_woocommerce() {
		$this->tracked_orders = 10;
		$this->caps           = array( 'read', 'edit_posts' );

		$this->assertFalse( ( new Notice() )->wps_tofw_should_show() );
		$this->assertSame( '', $this->render() );
		$this->assertSame( array(), $this->order_queries );
	}

	/**
	 * The notice only renders on the dashboard, the plugin page and the core order screens.
	 *
	 * @dataProvider screen_provider
	 *
	 * @param string|null $screen_id Screen id.
	 * @param string      $page      $_GET['page'].
	 * @param bool        $expected  Allowed.
	 */
	public function test_screen_restriction( $screen_id, $page, $expected ) {
		$this->tracked_orders = 10;
		$this->screen_id      = $screen_id;
		if ( '' !== $page ) {
			$_GET['page'] = $page;
		}

		$notice = new Notice();
		$this->assertSame( $expected, $notice->wps_tofw_is_allowed_screen() );
		$this->assertSame( $expected, '' !== $this->render( $notice ) );
	}

	/**
	 * Screens.
	 *
	 * @return array
	 */
	public function screen_provider() {
		return array(
			'dashboard'                 => array( 'dashboard', '', true ),
			'plugin page by $_GET'      => array( 'woocommerce_page_track_orders_for_woocommerce_menu', 'track_orders_for_woocommerce_menu', true ),
			'plugin page by screen id'  => array( 'wp-swings_page_track_orders_for_woocommerce_menu', '', true ),
			'HPOS orders list'          => array( 'woocommerce_page_wc-orders', '', true ),
			'legacy orders list'        => array( 'edit-shop_order', '', true ),
			'legacy order edit'         => array( 'shop_order', '', true ),
			'Settings > General'        => array( 'options-general', '', false ),
			'WooCommerce > Settings'    => array( 'woocommerce_page_wc-settings', 'wc-settings', false ),
			'Plugins'                   => array( 'plugins', '', false ),
			'Posts'                     => array( 'edit-post', '', false ),
			'Products'                  => array( 'edit-product', '', false ),
			'no screen'                 => array( null, '', false ),
		);
	}

	/* ------------------------------------------------------------------ assets */

	/**
	 * The script and a nonce are enqueued only when the notice is shown.
	 */
	public function test_enqueue_only_when_shown() {
		$this->tracked_orders = 10;
		Functions\when( 'admin_url' )->justReturn( 'http://example.org/wp-admin/admin-ajax.php' );
		Functions\expect( 'wp_create_nonce' )->once()->with( Notice::AJAX_ACTION )->andReturn( 'nonce123' );
		Functions\expect( 'wp_enqueue_script' )->once()->with( 'wps-tofw-review-notice', \Mockery::pattern( '#admin/js/wps-tofw-review-notice\.js$#' ), array( 'jquery' ), \Mockery::any(), true );
		Functions\expect( 'wp_localize_script' )->once()->with(
			'wps-tofw-review-notice',
			'wps_tofw_review_notice',
			array(
				'ajax_url' => 'http://example.org/wp-admin/admin-ajax.php',
				'action'   => Notice::AJAX_ACTION,
				'nonce'    => 'nonce123',
			)
		);

		( new Notice() )->wps_tofw_enqueue_scripts();
	}

	/**
	 * Nothing is enqueued when the notice is hidden.
	 */
	public function test_no_enqueue_when_hidden() {
		$this->tracked_orders = 2;
		Functions\expect( 'wp_enqueue_script' )->never();
		Functions\expect( 'wp_create_nonce' )->never();

		( new Notice() )->wps_tofw_enqueue_scripts();
		$this->addToAssertionCount( 1 );
	}

	/* ------------------------------------------------------------------ AJAX */

	/**
	 * "Later" snoozes the notice for 14 days and keeps it active.
	 */
	public function test_ajax_later_snoozes_for_14_days() {
		$before = time();

		$response = $this->ajax( array( 'review_action' => 'later' ) );
		$after    = time();

		$this->assertTrue( $response->success );
		$state = $this->state();
		$this->assertSame( 'active', $state['status'] );
		$this->assertGreaterThanOrEqual( $before + 14 * DAY_IN_SECONDS, $state['snooze_until'] );
		$this->assertLessThanOrEqual( $after + 14 * DAY_IN_SECONDS, $state['snooze_until'] );
		$this->assertSame( $state, $response->data );
		$this->assertSame( array( array( Notice::AJAX_ACTION, 'nonce' ) ), $this->nonce_checks, 'Nonce verified from the "nonce" field' );
	}

	/**
	 * "Don't ask again" and "Rate us" store their final status.
	 *
	 * @dataProvider ajax_final_action_provider
	 *
	 * @param string $action   review_action.
	 * @param string $expected Stored status.
	 */
	public function test_ajax_final_actions( $action, $expected ) {
		$response = $this->ajax( array( 'review_action' => $action ) );

		$this->assertTrue( $response->success );
		$this->assertSame( $expected, $this->state()['status'] );
	}

	/**
	 * Final AJAX actions.
	 *
	 * @return array
	 */
	public function ajax_final_action_provider() {
		return array(
			"Don't ask again" => array( 'dismiss', 'dismissed' ),
			'Rate us'         => array( 'rated', 'rated' ),
		);
	}

	/**
	 * Unknown actions are rejected with 400 and nothing is saved.
	 */
	public function test_ajax_rejects_unknown_action() {
		$response = $this->ajax( array( 'review_action' => 'delete_everything' ) );

		$this->assertFalse( $response->success );
		$this->assertSame( 400, $response->status );
		$this->assertSame( array(), $this->state() );
	}

	/**
	 * Users without manage_woocommerce get 403 and cannot change the state.
	 */
	public function test_ajax_requires_manage_woocommerce() {
		$this->caps = array( 'read' );
		$this->setOptions( array( Notice::OPTION => array( 'status' => 'active' ) ) );

		$response = $this->ajax( array( 'review_action' => 'dismiss' ) );

		$this->assertFalse( $response->success );
		$this->assertSame( 403, $response->status );
		$this->assertSame( array( 'status' => 'active' ), $this->state() );
	}

	/**
	 * An invalid nonce stops the request before anything is saved.
	 */
	public function test_ajax_invalid_nonce_stops_request() {
		$this->nonce_valid = false;
		$this->setOptions( array( Notice::OPTION => array( 'status' => 'active' ) ) );

		$response = $this->ajax(
			array(
				'review_action' => 'dismiss',
				'nonce'         => 'forged',
			)
		);

		$this->assertFalse( $response->success );
		$this->assertSame( 403, $response->status );
		$this->assertSame( array( array( Notice::AJAX_ACTION, 'nonce' ) ), $this->nonce_checks );
		$this->assertSame( array( 'status' => 'active' ), $this->state(), 'State unchanged' );
	}

	/* ------------------------------------------------------------------ wiring */

	/**
	 * The notice is wired to the admin hooks in the main plugin class.
	 */
	public function test_hooks_are_registered() {
		$source = (string) file_get_contents( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/class-track-orders-for-woocommerce.php' );

		$this->assertStringContainsString( "add_action( 'admin_notices', \$tofw_review_notice, 'wps_tofw_render_notice' )", $source );
		$this->assertStringContainsString( "add_action( 'admin_enqueue_scripts', \$tofw_review_notice, 'wps_tofw_enqueue_scripts' )", $source );
		$this->assertStringContainsString( "add_action( 'wp_ajax_' . Track_Orders_For_Woocommerce_Review_Notice::AJAX_ACTION, \$tofw_review_notice, 'wps_tofw_handle_ajax' )", $source );
		$this->assertStringNotContainsString( 'wp_ajax_nopriv_' . Notice::AJAX_ACTION, $source, 'No unauthenticated AJAX endpoint' );
		$this->assertSame( 'wps_tofw_review_notice_state', Notice::OPTION );
		$this->assertSame( 14, Notice::SNOOZE_DAYS );
	}
}
