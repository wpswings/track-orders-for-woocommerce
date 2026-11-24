<?php
/**
 * WPS-8402: legacy embed block, shortcode back-compat and WooCommerce feature (HPOS / blocks) declarations.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Blocks;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Tests\Unit\TestCase;

/**
 * Tests for the WooCommerce compatibility declarations and the legacy embed block.
 */
class BlockCompatibilityTest extends TestCase {

	/**
	 * The legacy "WPSwings Tracking Info" block registers an existing script (admin/js, not admin/src/js).
	 */
	public function test_legacy_embed_block_registered_with_existing_script() {
		$this->requirePluginFile( 'admin/class-track-orders-for-woocommerce-admin.php' );

		$admin_dir = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/';
		Functions\when( 'plugin_dir_path' )->alias(
			static function ( $file ) {
				return rtrim( dirname( $file ), '/' ) . '/';
			}
		);
		Functions\when( 'plugins_url' )->alias(
			static function ( $path, $plugin_file ) {
				return 'http://example.org/wp-content/plugins/track-orders-for-woocommerce/' . basename( dirname( $plugin_file ) ) . '/' . $path;
			}
		);
		Functions\when( 'admin_url' )->alias(
			static function ( $path = '' ) {
				return 'http://example.org/wp-admin/' . $path;
			}
		);

		$registered = array();
		Functions\expect( 'wp_register_script' )
			->once()
			->andReturnUsing(
				static function ( $handle, $src, $deps, $ver, $in_footer ) use ( &$registered ) {
					$registered = compact( 'handle', 'src', 'deps', 'ver', 'in_footer' );
					return true;
				}
			);
		Functions\expect( 'wp_set_script_translations' )->once()->with( 'tofw-embed-block', 'track-orders-for-woocommerce', \Mockery::type( 'string' ) );
		Functions\expect( 'register_block_type' )
			->once()
			->with( 'wpswings/tofw-embed-block', array( 'editor_script' => 'tofw-embed-block' ) );
		Functions\expect( 'wp_localize_script' )
			->once()
			->with( 'tofw-embed-block', 'embed_block_param', \Mockery::type( 'array' ) );

		$admin = new \Track_Orders_For_Woocommerce_Admin( 'track-orders-for-woocommerce', '1.2.9' );
		$admin->register_google_embed_blocks();

		$this->assertSame( 'tofw-embed-block', $registered['handle'] );
		$this->assertStringEndsWith( '/admin/js/tofw-embed-block.js', $registered['src'] );
		$this->assertStringNotContainsString( '/src/js/', $registered['src'] );
		$this->assertFileExists( $admin_dir . 'js/tofw-embed-block.js' );
		$this->assertSame( filemtime( $admin_dir . 'js/tofw-embed-block.js' ), $registered['ver'] );
		$this->assertContains( 'wp-block-editor', $registered['deps'] );
		$this->assertNotContains( 'wp-editor', $registered['deps'], 'wp-editor is deprecated for blocks' );
		$this->assertTrue( $registered['in_footer'] );
	}

	/**
	 * The legacy embed block script uses block props from wp.blockEditor and keeps a deprecation for old markup.
	 */
	public function test_legacy_embed_block_script_uses_block_props_and_deprecation() {
		$js = (string) file_get_contents( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin/js/tofw-embed-block.js' );

		$this->assertStringContainsString( "registerBlockType('wpswings/tofw-embed-block'", $js );
		$this->assertMatchesRegularExpression( '/useBlockProps\s*\}\s*=\s*wp\.blockEditor/', $js );
		$this->assertStringContainsString( 'deprecated:', $js );
		$this->assertStringContainsString( '[wps_tracking_info order_id=', $js );
	}

	/**
	 * The [WPS_MUTIPLE_CARRIER_TRACKING_FORM] shortcode is still registered next to the block.
	 */
	public function test_multi_carrier_shortcode_is_kept() {
		$this->requirePluginFile( 'public/class-track-orders-for-woocommerce-public.php' );
		$public = new \Track_Orders_For_Woocommerce_Public( 'track-orders-for-woocommerce', '1.2.9' );

		Functions\expect( 'add_shortcode' )
			->once()
			->with( 'WPS_MUTIPLE_CARRIER_TRACKING_FORM', array( $public, 'wps_fetch_multiple_tracking_carrier_data' ) );

		$public->wps_track_order_shortcodes_multiple_carrier();
	}

	/**
	 * The guest track order page and the block share the same form partial.
	 */
	public function test_guest_page_template_uses_shared_form_partial() {
		$template = (string) file_get_contents( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'template/wps-guest-track-order-page.php' );

		$this->assertStringContainsString( 'wps-tofw-track-order-form.php', $template );
		$this->assertStringNotContainsString( 'name="order_email"', $template, 'The form markup lives only in the partial' );
	}

	/**
	 * The plugin declares HPOS (custom_order_tables) and cart/checkout blocks compatibility on
	 * before_woocommerce_init through FeaturesUtil, for its main plugin file.
	 *
	 * Runs in its own process: the main plugin file is loaded with WooCommerce inactive so only
	 * its top-level hooks run, and a FeaturesUtil spy is declared.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_declares_hpos_and_cart_checkout_blocks_compatibility() {
		require_once __DIR__ . '/Fixtures/FeaturesUtilSpy.php';

		$this->setOptions( array( 'active_plugins' => array() ) );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'register_activation_hook' )->justReturn( null );
		Functions\when( 'register_deactivation_hook' )->justReturn( null );

		$callback = null;
		Actions\expectAdded( 'before_woocommerce_init' )
			->once()
			->whenHappen(
				static function ( $cb ) use ( &$callback ) {
					$callback = $cb;
				}
			);

		$main_file = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'track-orders-for-woocommerce.php';
		require_once $main_file;

		$this->assertTrue( is_callable( $callback ), 'before_woocommerce_init callback registered' );
		$this->assertSame( array(), \Automattic\WooCommerce\Utilities\FeaturesUtil::$declared, 'Nothing is declared before WooCommerce asks' );

		call_user_func( $callback );

		$declared = \Automattic\WooCommerce\Utilities\FeaturesUtil::$declared;
		$this->assertCount( 2, $declared );
		$features = array();
		foreach ( $declared as $declaration ) {
			list( $feature, $plugin_file, $positive ) = $declaration;
			$this->assertSame( realpath( $main_file ), realpath( $plugin_file ), 'Declared for the main plugin file' );
			$this->assertTrue( $positive, $feature . ' must be declared compatible' );
			$features[] = $feature;
		}
		sort( $features );
		$this->assertSame( array( 'cart_checkout_blocks', 'custom_order_tables' ), $features );
	}
}
