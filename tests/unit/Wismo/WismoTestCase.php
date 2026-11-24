<?php
/**
 * Shared fixtures for the "Where is my order?" (WISMO) assistant unit tests.
 *
 * Not a test itself (the file name does not end in Test.php); the WISMO tests extend it.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Wismo;

use Brain\Monkey\Functions;
use Mockery;
use Tests\Unit\TestCase;

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

/**
 * Thrown by the wp_send_json_success()/wp_send_json_error() stubs so the AJAX handlers stop like they do in WordPress.
 */
class JsonResponse extends \Exception {

	/**
	 * Whether wp_send_json_success() was used.
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
	 * @param bool     $success Success.
	 * @param mixed    $data    Data.
	 * @param int|null $status  HTTP status.
	 */
	public function __construct( $success, $data, $status ) {
		parent::__construct( $success ? 'success' : 'error' );
		$this->success = $success;
		$this->data    = $data;
		$this->status  = $status;
	}
}

/**
 * Base class: stubs the WordPress APIs used by Track_Orders_For_Woocommerce_Wismo.
 */
abstract class WismoTestCase extends TestCase {

	/**
	 * In-memory transients.
	 *
	 * @var array
	 */
	protected $transients = array();

	/**
	 * Current user ID returned by get_current_user_id().
	 *
	 * @var int
	 */
	protected $current_user = 0;

	/**
	 * Whether check_ajax_referer() passes.
	 *
	 * @var bool
	 */
	protected $nonce_ok = true;

	/**
	 * Requests sent with wp_remote_post(): list of [ url, args ].
	 *
	 * @var array
	 */
	protected $http_requests = array();

	/**
	 * Response returned by wp_remote_post().
	 *
	 * @var mixed
	 */
	protected $http_response = null;

	/**
	 * Saved $_POST / $_SERVER.
	 *
	 * @var array
	 */
	private $saved_globals = array();

	/**
	 * Set up stubs.
	 */
	protected function set_up() {
		parent::set_up();
		$this->requirePluginFile( 'includes/wismo/class-track-orders-for-woocommerce-wismo-answer-engine.php' );
		$this->requirePluginFile( 'includes/wismo/class-track-orders-for-woocommerce-wismo.php' );

		$this->saved_globals = array( $_POST, $_SERVER ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST               = array();
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		$this->transients    = array();
		$this->current_user  = 0;
		$this->nonce_ok      = true;
		$this->http_requests = array();
		$this->http_response = null;

		$this->stubCommonWpFunctions();
		$this->stubWcGetOrder();
		$this->setOptions(
			array(
				'wps_tofw_wismo_enable'        => 'on',
				'wps_tofw_wismo_support_email' => 'help@shop.test',
				'wps_tofw_wismo_support_url'   => 'https://shop.test/contact',
				'admin_email'                  => 'admin@shop.test',
			)
		);

		$transients = &$this->transients;
		$self       = $this;
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
		Functions\when( 'get_current_user_id' )->alias(
			static function () use ( $self ) {
				return $self->currentUser();
			}
		);
		Functions\when( 'is_user_logged_in' )->alias(
			static function () use ( $self ) {
				return $self->currentUser() > 0;
			}
		);
		Functions\when( 'check_ajax_referer' )->alias(
			static function () use ( $self ) {
				return $self->nonceOk();
			}
		);
		Functions\when( 'wp_send_json_success' )->alias(
			static function ( $data = null, $status = null ) {
				throw new JsonResponse( true, $data, $status );
			}
		);
		Functions\when( 'wp_send_json_error' )->alias(
			static function ( $data = null, $status = null ) {
				throw new JsonResponse( false, $data, $status );
			}
		);
		Functions\when( 'sanitize_email' )->alias(
			static function ( $email ) {
				$email = trim( (string) $email );
				return false !== filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : '';
			}
		);
		Functions\when( 'sanitize_textarea_field' )->alias(
			static function ( $value ) {
				return trim( strip_tags( (string) $value ) );
			}
		);
		Functions\when( 'wp_strip_all_tags' )->alias(
			static function ( $value ) {
				return trim( strip_tags( (string) $value ) );
			}
		);
		// Like WordPress: only the allowed protocols survive.
		Functions\when( 'esc_url_raw' )->alias(
			static function ( $url, $protocols = null ) {
				$url = trim( (string) $url );
				if ( '' === $url ) {
					return '';
				}
				$protocols = $protocols ? $protocols : array( 'http', 'https', 'mailto' );
				$scheme    = strtolower( (string) parse_url( $url, PHP_URL_SCHEME ) );
				return in_array( $scheme, $protocols, true ) ? $url : '';
			}
		);
		Functions\when( 'wp_parse_url' )->alias(
			static function ( $url, $component = -1 ) {
				return parse_url( $url, $component );
			}
		);
		Functions\when( 'wp_generate_password' )->alias(
			static function ( $length = 12 ) {
				return substr( str_repeat( bin2hex( random_bytes( 16 ) ), 2 ), 0, $length );
			}
		);
		Functions\when( 'wp_salt' )->justReturn( 'unit-test-salt' );
		Functions\when( 'wp_create_nonce' )->justReturn( 'fresh-nonce' );
		Functions\when( 'nocache_headers' )->justReturn( null );
		Functions\when( 'get_permalink' )->alias(
			static function ( $id ) {
				return 'https://shop.test/page-' . (int) $id . '/';
			}
		);
		Functions\when( 'wc_get_order_status_name' )->alias(
			static function ( $status ) {
				return ucwords( str_replace( '-', ' ', $status ) );
			}
		);
		Functions\when( 'wc_format_datetime' )->justReturn( 'November 20, 2026' );
		Functions\when( 'date_i18n' )->alias(
			static function ( $format, $timestamp = null ) {
				return gmdate( 'F j, Y', null === $timestamp ? time() : (int) $timestamp );
			}
		);
		Functions\when( 'wp_date' )->justReturn( 'November 21, 2026 10:00' );
		Functions\when( 'wp_remote_post' )->alias(
			static function ( $url, $args ) use ( $self ) {
				return $self->recordHttp( $url, $args );
			}
		);
		Functions\when( 'wp_remote_retrieve_response_code' )->alias(
			static function ( $response ) {
				return is_array( $response ) && isset( $response['response']['code'] ) ? $response['response']['code'] : '';
			}
		);
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static function ( $response ) {
				return is_array( $response ) && isset( $response['body'] ) ? $response['body'] : '';
			}
		);
	}

	/**
	 * Restore globals.
	 */
	protected function tear_down() {
		list( $_POST, $_SERVER ) = $this->saved_globals;
		parent::tear_down();
	}

	/**
	 * Current user (used by stubs).
	 *
	 * @return int
	 */
	public function currentUser() {
		return $this->current_user;
	}

	/**
	 * Nonce result (used by stubs).
	 *
	 * @return bool
	 */
	public function nonceOk() {
		return $this->nonce_ok;
	}

	/**
	 * Record an HTTP request and return the configured response.
	 *
	 * @param string $url  URL.
	 * @param array  $args Args.
	 * @return mixed
	 */
	public function recordHttp( $url, $args ) {
		$this->http_requests[] = array( $url, $args );
		return $this->http_response;
	}

	/**
	 * New assistant instance.
	 *
	 * @return \Track_Orders_For_Woocommerce_Wismo
	 */
	protected function wismo() {
		return new \Track_Orders_For_Woocommerce_Wismo( 'track-orders-for-woocommerce', '1.2.9' );
	}

	/**
	 * Mocked order with everything build_order_context() reads.
	 *
	 * @param array $props mockOrder() props.
	 * @return \Mockery\MockInterface
	 */
	protected function wismoOrder( array $props = array() ) {
		$item = Mockery::mock( 'WC_Order_Item_Product' );
		$item->shouldReceive( 'get_name' )->andReturn( 'Blue <b>Shirt</b>' );
		$item->shouldReceive( 'get_quantity' )->andReturn( 2 );

		$defaults = array(
			'id'                   => 501,
			'status'               => 'processing',
			'billing_email'        => 'Buyer@Example.com',
			'customer_id'          => 0,
			'get_items'            => array( $item ),
			'get_date_created'     => 'created',
			'get_date_completed'   => null,
			'get_shipping_city'    => 'Pune',
			'get_billing_city'     => 'Billing City',
			'get_shipping_country' => 'IN',
			'get_billing_country'  => 'IN',
			'get_billing_first_name' => 'Jane',
			'get_billing_last_name'  => 'Secret',
			'get_billing_phone'      => '+1-555-0100',
			'get_shipping_address_1' => '42 Hidden Street',
		);
		return $this->mockOrder( array_merge( $defaults, $props ) );
	}

	/**
	 * Call an AJAX handler and return the JSON response it sent.
	 *
	 * @param string $method Handler method.
	 * @param array  $post   $_POST data.
	 * @param object $wismo  Instance (new one by default).
	 * @return JsonResponse
	 */
	protected function ajax( $method, array $post = array(), $wismo = null ) {
		$_POST = $post;
		$wismo = $wismo ? $wismo : $this->wismo();
		try {
			$wismo->$method();
		} catch ( JsonResponse $response ) {
			return $response;
		}
		$this->fail( $method . '() did not send a JSON response.' );
		return null;
	}

	/**
	 * Verify an order by e-mail and return the response.
	 *
	 * @param mixed  $order_id Order ID as typed.
	 * @param string $email    E-mail.
	 * @return JsonResponse
	 */
	protected function verifyByEmail( $order_id, $email ) {
		return $this->ajax(
			'ajax_verify',
			array(
				'nonce'    => 'n',
				'mode'     => 'email',
				'order_id' => (string) $order_id,
				'email'    => $email,
			)
		);
	}

	/**
	 * Assert the generic "not verified" error with support links.
	 *
	 * @param JsonResponse $response Response.
	 */
	protected function assertNotVerified( JsonResponse $response ) {
		$this->assertFalse( $response->success );
		$this->assertSame( 'not_verified', $response->data['code'] );
		$this->assertSame( 'We could not verify that order. Please check the order number and billing e-mail.', $response->data['message'] );
		$this->assertSame( 200, $response->status );
		$this->assertSupportLinks( $response->data['links'] );
	}

	/**
	 * Assert the configured support links are present.
	 *
	 * @param array $links Links.
	 */
	protected function assertSupportLinks( $links ) {
		$urls = array_column( (array) $links, 'url' );
		$this->assertContains( 'https://shop.test/contact', $urls );
		$this->assertContains( 'mailto:help@shop.test', $urls );
	}
}
