<?php
/**
 * WPS-8391 FedEx tracking page: the nonce is printed and enforced, and the submitted
 * order id is never written to a site option.
 *
 * The template is included for real with WordPress stubbed and its output captured.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\FedexTracking;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Tests\Unit\TestCase;

/**
 * FedEx tracking page template.
 */
class FedexTrackingPageTest extends TestCase {

	const TEMPLATE    = 'template/wps-order-tracking-page.php';
	const VALID_NONCE = 'abc123nonce';

	/**
	 * Set up.
	 */
	protected function set_up() {
		parent::set_up();
		$this->stubCommonWpFunctions();
		$this->setOptions();
		Functions\when( 'get_header' )->justReturn( null );
		Functions\when( 'get_footer' )->justReturn( null );
		Functions\when( 'absint' )->alias(
			static function ( $value ) {
				return abs( (int) $value );
			}
		);
		Functions\when( 'wp_nonce_field' )->alias(
			static function ( $action, $name ) {
				echo '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . ( 'wps_track_package_nonce' === $action ? self::VALID_NONCE : 'other' ) . '" />'; // phpcs:ignore
			}
		);
		Functions\when( 'wp_verify_nonce' )->alias(
			static function ( $nonce, $action ) {
				return 'wps_track_package_nonce' === $action && self::VALID_NONCE === $nonce ? 1 : false;
			}
		);
		$_POST = array();
	}

	/**
	 * Tear down.
	 */
	protected function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	/**
	 * Render the template and return its output.
	 *
	 * @return string
	 */
	private function render() {
		ob_start();
		include TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . self::TEMPLATE;
		return (string) ob_get_clean();
	}

	public function test_form_prints_a_non_empty_nonce() {
		$html = $this->render();
		$this->assertMatchesRegularExpression( '/name="wps_track_package_nonce_name" value="' . self::VALID_NONCE . '"/', $html );
		$this->assertDoesNotMatchRegularExpression( '/name="wps_track_package_nonce_name" value=""/', $html );
	}

	public function test_valid_submit_tracks_the_order_without_writing_an_option() {
		$_POST = array(
			'wps_track_package'            => '',
			'wps_user_order_id'            => '42',
			'wps_track_package_nonce_name' => self::VALID_NONCE,
		);
		Functions\expect( 'update_option' )->never();
		Functions\expect( 'add_option' )->never();
		Functions\expect( 'wps_tofw_get_order_meta' )->once()->with( '42', 'wps_tofw_selected_shipping_service' )->andReturn( '' );
		Actions\expectDone( 'wps_tofw_render_return_button' )->once()->with( 42 );
		Actions\expectDone( 'wps_tofw_after_tracking_details' )->once()->with( 42 );

		$html = $this->render();

		$this->assertStringNotContainsString( 'wps_tofw_track_package_error', $html );
		$this->assertNull( $this->getOption( 'wps_tofw_user_order_id' ) );
	}

	/**
	 * Forged, stale or missing nonce.
	 *
	 * @return array
	 */
	public function bad_nonces() {
		return array(
			'forged'  => array( 'forged-nonce' ),
			'empty'   => array( '' ),
			'missing' => array( null ),
		);
	}

	/**
	 * @dataProvider bad_nonces
	 * @param string|null $nonce Submitted nonce.
	 */
	public function test_submit_without_a_valid_nonce_is_rejected( $nonce ) {
		$_POST = array(
			'wps_track_package' => '',
			'wps_user_order_id' => '42',
		);
		if ( null !== $nonce ) {
			$_POST['wps_track_package_nonce_name'] = $nonce;
		}
		Functions\expect( 'update_option' )->never();
		Functions\expect( 'wps_tofw_get_order_meta' )->never();
		Actions\expectDone( 'wps_tofw_render_return_button' )->never();
		Actions\expectDone( 'wps_tofw_after_tracking_details' )->never();

		$html = $this->render();

		$this->assertStringContainsString( 'wps_tofw_track_package_error', $html );
		$this->assertStringContainsString( 'Please enter the order id and submit the form again.', $html );
		$this->assertNull( $this->getOption( 'wps_tofw_user_order_id' ) );
	}

	public function test_page_without_submit_shows_no_error_and_no_tracking() {
		Actions\expectDone( 'wps_tofw_after_tracking_details' )->never();
		$html = $this->render();

		$this->assertStringNotContainsString( 'wps_tofw_track_package_error', $html );
	}

	public function test_source_uses_the_nonce_result_and_never_stores_the_order_id() {
		$source = (string) file_get_contents( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . self::TEMPLATE );
		$this->assertStringNotContainsString( 'wps_tofw_user_order_id', $source );
		$this->assertDoesNotMatchRegularExpression( '/^\s*wp_verify_nonce\s*\(/m', $source, 'The wp_verify_nonce() result must be used.' );
		$this->assertDoesNotMatchRegularExpression( '/<\?php\s+wp_create_nonce\s*\(/', $source, 'A nonce must be echoed, not just created.' );
	}
}
