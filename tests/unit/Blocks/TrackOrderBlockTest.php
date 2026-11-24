<?php
/**
 * WPS-8402: dynamic "Track Order" block (registration, metadata and server-side render).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Blocks;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Tests\Unit\TestCase;

/**
 * Tests for Track_Orders_For_Woocommerce_Blocks.
 */
class TrackOrderBlockTest extends TestCase {

	/**
	 * Block folder, relative to the plugin root.
	 */
	const BLOCK_DIR = 'includes/blocks/track-order';

	/**
	 * Class under test.
	 *
	 * @var \Track_Orders_For_Woocommerce_Blocks
	 */
	private $blocks;

	/**
	 * Load the class and stub the WordPress functions used by the render callback.
	 */
	protected function set_up() {
		parent::set_up();
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-blocks.php' );
		$this->stubCommonWpFunctions();

		Functions\when( 'wp_parse_args' )->alias(
			static function ( $args, $defaults = array() ) {
				return array_merge( $defaults, (array) $args );
			}
		);
		Functions\when( 'get_block_wrapper_attributes' )->alias(
			static function ( $extra = array() ) {
				$class = 'wp-block-track-orders-for-woocommerce-track-order';
				if ( ! empty( $extra['class'] ) ) {
					$class .= ' ' . $extra['class'];
				}
				return 'class="' . htmlspecialchars( $class, ENT_QUOTES ) . '"';
			}
		);
		Functions\when( 'wp_create_nonce' )->justReturn( 'nonce123' );
		Functions\when( 'wp_is_serving_rest_request' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );

		$this->blocks = new \Track_Orders_For_Woocommerce_Blocks( 'track-orders-for-woocommerce', '1.2.9' );
	}

	/**
	 * Options that enable the plugin and the Track Order feature.
	 *
	 * @param array $extra Additional options.
	 */
	private function enableTracking( array $extra = array() ) {
		$this->setOptions(
			array_merge(
				array(
					'wps_tofw_is_plugin_enable' => 'on',
					'tofw_enable_track_order'   => 'on',
				),
				$extra
			)
		);
	}

	/**
	 * Decoded block.json.
	 *
	 * @return array
	 */
	private function blockJson() {
		$path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . self::BLOCK_DIR . '/block.json';
		$this->assertFileExists( $path );
		$json = json_decode( (string) file_get_contents( $path ), true );
		$this->assertIsArray( $json, 'block.json must be valid JSON' );
		return $json;
	}

	// ---------------------------------------------------------------------
	// Registration and metadata.
	// ---------------------------------------------------------------------

	/**
	 * The block is registered from its block.json folder with a server render callback.
	 */
	public function test_registers_block_from_metadata_with_render_callback() {
		$blocks = $this->blocks;
		Functions\expect( 'register_block_type' )
			->once()
			->andReturnUsing(
				function ( $path, $args ) use ( $blocks ) {
					$this->assertSame( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . self::BLOCK_DIR, $path );
					$this->assertFileExists( $path . '/block.json' );
					$this->assertSame( array( $blocks, 'wps_tofw_render_track_order_block' ), $args['render_callback'] );
					$this->assertTrue( is_callable( $args['render_callback'] ) );
					return true;
				}
			);

		$this->blocks->wps_tofw_register_blocks();
	}

	/**
	 * Registration is skipped on WordPress versions without the block API.
	 *
	 * Runs in its own process because register_block_type() must not exist at all.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_registration_is_skipped_without_block_api() {
		$this->assertFalse( function_exists( 'register_block_type' ) );
		$this->blocks->wps_tofw_register_blocks();
		$this->assertFalse( function_exists( 'register_block_type' ) );
	}

	/**
	 * block.json describes a dynamic block with the expected name, attributes and editor script.
	 */
	public function test_block_json_metadata() {
		$json = $this->blockJson();

		$this->assertSame( 'track-orders-for-woocommerce/track-order', $json['name'] );
		$this->assertGreaterThanOrEqual( 2, $json['apiVersion'] );
		$this->assertSame( 'Track Order', $json['title'] );
		$this->assertSame( 'track-orders-for-woocommerce', $json['textdomain'] );
		$this->assertSame( 'woocommerce', $json['category'] );

		$this->assertSame( 'order', $json['attributes']['formType']['default'] );
		$this->assertSame( array( 'order', 'carrier' ), $json['attributes']['formType']['enum'] );
		$this->assertTrue( $json['attributes']['showTitle']['default'] );
		$this->assertSame( 'left', $json['attributes']['position']['default'] );
		$this->assertSame( array( 'left', 'center', 'right' ), $json['attributes']['position']['enum'] );

		$this->assertFalse( $json['supports']['html'], 'HTML editing must be off for a dynamic block' );
		$this->assertArrayNotHasKey( 'render', $json, 'Rendering is done by the PHP render_callback' );

		$this->assertSame( 'file:./index.js', $json['editorScript'] );
		$this->assertFileExists( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . self::BLOCK_DIR . '/index.js' );
	}

	/**
	 * The editor script asset file lists the wp-* globals the script uses (no build step).
	 */
	public function test_editor_script_asset_dependencies() {
		$asset = require TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . self::BLOCK_DIR . '/index.asset.php';

		$this->assertIsArray( $asset );
		foreach ( array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ) as $dep ) {
			$this->assertContains( $dep, $asset['dependencies'] );
		}
		$this->assertNotEmpty( $asset['version'] );

		$js = (string) file_get_contents( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . self::BLOCK_DIR . '/index.js' );
		$this->assertStringContainsString( "'track-orders-for-woocommerce/track-order'", $js );
		$this->assertMatchesRegularExpression( '/save\s*:\s*function\s*\(\s*\)\s*\{\s*return\s+null;/', $js, 'Dynamic block: save() returns null' );
	}

	// ---------------------------------------------------------------------
	// Render callback.
	// ---------------------------------------------------------------------

	/**
	 * Default attributes render the guest track order form (order id + email + nonce) in the block wrapper.
	 */
	public function test_renders_track_order_form_with_defaults() {
		$this->enableTracking();

		$html = $this->blocks->wps_tofw_render_track_order_block( array() );

		$this->assertStringStartsWith( '<div class="wp-block-track-orders-for-woocommerce-track-order wps-tofw-track-order-block">', $html );
		$this->assertStringContainsString( 'id="wps_tofw_guest_request_form_wrapper"', $html );
		$this->assertStringContainsString( '<h2>', $html );
		$this->assertStringContainsString( 'Track Your Order', $html );
		$this->assertStringContainsString( 'id="order_id"', $html );
		$this->assertStringContainsString( 'id="order_email"', $html );
		$this->assertStringContainsString( 'name="track_order_nonce_name" value="nonce123"', $html );
		$this->assertStringContainsString( 'name="wps_tofw_order_id_submit"', $html );
		$this->assertStringEndsWith( '</div>', $html );
	}

	/**
	 * showTitle=false hides the heading.
	 */
	public function test_show_title_false_hides_heading() {
		$this->enableTracking();

		$html = $this->blocks->wps_tofw_render_track_order_block( array( 'showTitle' => false ) );

		$this->assertStringContainsString( 'id="order_id"', $html );
		$this->assertStringNotContainsString( '<h2>', $html );
		$this->assertStringNotContainsString( 'Track Your Order', $html );
	}

	/**
	 * "Track using order id only" removes the email field.
	 */
	public function test_order_id_only_mode_has_no_email_field() {
		$this->enableTracking( array( 'wps_tofw_enable_track_order_using_order_id' => 'on' ) );

		$html = $this->blocks->wps_tofw_render_track_order_block( array() );

		$this->assertStringContainsString( 'id="order_id"', $html );
		$this->assertStringNotContainsString( 'order_email', $html );
	}

	/**
	 * Data provider: options that disable the tracking form.
	 *
	 * @return array
	 */
	public function disabledOptionsProvider() {
		return array(
			'plugin disabled'      => array( array( 'wps_tofw_is_plugin_enable' => 'off', 'tofw_enable_track_order' => 'on' ) ),
			'track order disabled' => array( array( 'wps_tofw_is_plugin_enable' => 'on', 'tofw_enable_track_order' => 'off' ) ),
			'nothing saved'        => array( array() ),
		);
	}

	/**
	 * On the frontend a disabled feature renders nothing.
	 *
	 * @dataProvider disabledOptionsProvider
	 * @param array $options Options.
	 */
	public function test_disabled_feature_renders_nothing_on_frontend( array $options ) {
		$this->setOptions( $options );

		$this->assertSame( '', $this->blocks->wps_tofw_render_track_order_block( array() ) );
	}

	/**
	 * In the editor preview (REST + edit_posts) a disabled feature renders an escaped notice.
	 *
	 * @dataProvider disabledOptionsProvider
	 * @param array $options Options.
	 */
	public function test_disabled_feature_renders_notice_in_editor( array $options ) {
		$this->setOptions( $options );
		Functions\when( 'wp_is_serving_rest_request' )->justReturn( true );
		Functions\when( 'current_user_can' )->alias(
			static function ( $cap ) {
				return 'edit_posts' === $cap;
			}
		);

		$html = $this->blocks->wps_tofw_render_track_order_block( array() );

		$this->assertStringContainsString( 'wps-tofw-track-order-block--notice', $html );
		$this->assertStringContainsString( 'Enable the plugin and the Track Order feature', $html );
		$this->assertStringNotContainsString( 'order_id', $html );
	}

	/**
	 * A REST request from a user who cannot edit posts does not get the editor notice.
	 */
	public function test_editor_notice_requires_edit_posts() {
		$this->setOptions( array( 'wps_tofw_is_plugin_enable' => 'off' ) );
		Functions\when( 'wp_is_serving_rest_request' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( false );

		$this->assertSame( '', $this->blocks->wps_tofw_render_track_order_block( array() ) );
	}

	/**
	 * The editor notice text is HTML escaped.
	 */
	public function test_editor_notice_is_escaped() {
		$this->setOptions( array() );
		Functions\when( 'wp_is_serving_rest_request' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( '__' )->justReturn( '<img src=x onerror=alert(1)>' );

		$html = $this->blocks->wps_tofw_render_track_order_block( array() );

		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $html );
	}

	/**
	 * Admin supplied wrapper classes and custom CSS cannot break out of their attribute / style tag.
	 */
	public function test_render_escapes_custom_classes_and_css() {
		$this->enableTracking(
			array(
				'wps_tofw_track_order_class'         => 'main" onmouseover="alert(1)',
				'wps_tofw_track_order_child_class'   => 'child"><script>alert(2)</script>',
				'wps_tofw_tracking_order_custom_css' => 'body{}</style><script>alert(3)</script>',
			)
		);

		$html = $this->blocks->wps_tofw_render_track_order_block( array() );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '" onmouseover="', $html );
		$this->assertStringNotContainsString( '</style><script>', $html );
		$this->assertStringContainsString( 'main&quot; onmouseover=&quot;alert(1)', $html );
		$this->assertStringContainsString( '&lt;/style&gt;&lt;script&gt;alert(3)&lt;/script&gt;', $html );
	}

	/**
	 * A session error message (e.g. wrong order id) is shown escaped and only once.
	 */
	public function test_session_notification_is_escaped_and_cleared() {
		$this->enableTracking();
		$_SESSION = array( 'wps_tofw_notification' => 'Bad <b>id</b>' );

		$html = $this->blocks->wps_tofw_render_track_order_block( array() );

		$this->assertStringContainsString( 'woocommerce-error', $html );
		$this->assertStringNotContainsString( '<b>id</b>', $html );
		$this->assertArrayNotHasKey( 'wps_tofw_notification', $_SESSION );
		unset( $_SESSION );
	}

	/**
	 * The wps_tofw_track_order_block_html filter can change the markup; empty markup renders nothing.
	 */
	public function test_block_html_filter() {
		$this->enableTracking();

		Filters\expectApplied( 'wps_tofw_track_order_block_html' )
			->once()
			->with( \Mockery::type( 'string' ), \Mockery::type( 'array' ), null )
			->andReturn( '<p>custom</p>' );
		$this->assertSame(
			'<div class="wp-block-track-orders-for-woocommerce-track-order wps-tofw-track-order-block"><p>custom</p></div>',
			$this->blocks->wps_tofw_render_track_order_block( array() )
		);
	}

	/**
	 * A filter returning whitespace only removes the block.
	 */
	public function test_block_html_filter_empty_removes_block() {
		$this->enableTracking();
		Filters\expectApplied( 'wps_tofw_track_order_block_html' )->once()->andReturn( "  \n" );

		$this->assertSame( '', $this->blocks->wps_tofw_render_track_order_block( array() ) );
	}

	/**
	 * formType=carrier needs the multi-carrier setting.
	 */
	public function test_carrier_form_requires_multi_carrier_setting() {
		$this->enableTracking();

		$this->assertSame( '', $this->blocks->wps_tofw_render_track_order_block( array( 'formType' => 'carrier' ) ) );
	}

	/**
	 * formType=carrier renders the multi-carrier shortcode output with a sanitized position.
	 */
	public function test_carrier_form_renders_multi_carrier_output_with_sanitized_position() {
		$this->enableTracking( array( 'wps_tofwp_enable_multi_carrier_tracking' => 'on' ) );

		$public = new class() {
			/**
			 * Received shortcode attributes.
			 *
			 * @var array
			 */
			public $atts = array();

			/**
			 * Fake shortcode output.
			 *
			 * @param array $atts Attributes.
			 * @return string
			 */
			public function wps_fetch_multiple_tracking_carrier_data( $atts ) {
				$this->atts = $atts;
				return '<div class="wps-carrier-form">carrier</div>';
			}
		};
		$property = new \ReflectionProperty( \Track_Orders_For_Woocommerce_Blocks::class, 'public' );
		$property->setAccessible( true );
		$property->setValue( $this->blocks, $public );

		$html = $this->blocks->wps_tofw_render_track_order_block(
			array(
				'formType' => 'carrier',
				'position' => 'Center"><script>',
			)
		);

		$this->assertSame( array( 'position' => 'centerscript' ), $public->atts );
		$this->assertStringContainsString( '<div class="wps-carrier-form">carrier</div>', $html );
		$this->assertStringContainsString( 'wps-tofw-track-order-block', $html );
		$this->assertStringNotContainsString( 'order_email', $html );
	}
}
