<?php
/**
 * Tests for WordPress helper functions
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Test WordPress helper functions used in the plugin.
 */
class HelperFunctionsTest extends PolyfillTestCase {

	/**
	 * Test admin_url function.
	 */
	public function testAdminUrl() {
		$url = admin_url('admin.php?page=test');
		$this->assertStringContainsString('wp-admin', $url);
		$this->assertStringContainsString('admin.php?page=test', $url);
	}

	/**
	 * Test plugin_dir_path function.
	 */
	public function testPluginDirPath() {
		$file = '/path/to/plugin/plugin.php';
		$path = plugin_dir_path($file);
		$this->assertStringEndsWith('/', $path);
	}

	/**
	 * Test plugin_dir_url function.
	 */
	public function testPluginDirUrl() {
		$file = '/path/to/plugin/plugin.php';
		$url = plugin_dir_url($file);
		$this->assertStringContainsString('http', $url);
		$this->assertStringEndsWith('/', $url);
	}

	/**
	 * Test plugin_basename function.
	 */
	public function testPluginBasename() {
		$file = '/path/to/plugin/plugin.php';
		$basename = plugin_basename($file);
		$this->assertStringContainsString('plugin.php', $basename);
	}

	/**
	 * Test get_option function.
	 */
	public function testGetOption() {
		$option = get_option('test_option', 'default_value');
		$this->assertEquals('default_value', $option);
	}

	/**
	 * Test update_option function.
	 */
	public function testUpdateOption() {
		$result = update_option('test_option', 'test_value');
		$this->assertTrue($result);
	}

	/**
	 * Test add_option function.
	 */
	public function testAddOption() {
		$result = add_option('new_option', 'value');
		$this->assertTrue($result);
	}

	/**
	 * Test delete_option function.
	 */
	public function testDeleteOption() {
		$result = delete_option('test_option');
		$this->assertTrue($result);
	}

	/**
	 * Test wp_json_encode function.
	 */
	public function testWpJsonEncode() {
		$data = array('key' => 'value', 'number' => 123);
		$json = wp_json_encode($data);
		$this->assertJson($json);
		$this->assertStringContainsString('key', $json);
		$this->assertStringContainsString('value', $json);
	}

	/**
	 * Test is_admin function.
	 */
	public function testIsAdmin() {
		$result = is_admin();
		$this->assertIsBool($result);
	}

	/**
	 * Test is_multisite function.
	 */
	public function testIsMultisite() {
		$result = is_multisite();
		$this->assertIsBool($result);
	}

	/**
	 * Test current_user_can function.
	 */
	public function testCurrentUserCan() {
		$result = current_user_can('manage_options');
		$this->assertIsBool($result);
	}

	/**
	 * Test wp_verify_nonce function.
	 */
	public function testWpVerifyNonce() {
		$result = wp_verify_nonce('test_nonce', 'test_action');
		$this->assertIsInt($result);
	}

	/**
	 * Test wp_create_nonce function.
	 */
	public function testWpCreateNonce() {
		$nonce = wp_create_nonce('test_action');
		$this->assertIsString($nonce);
		$this->assertNotEmpty($nonce);
	}

	/**
	 * Test translation function.
	 */
	public function testTranslationFunction() {
		$text = __('Hello World', 'track-orders-for-woocommerce');
		$this->assertEquals('Hello World', $text);
		$this->assertIsString($text);
	}

	/**
	 * Test esc_html__ function.
	 */
	public function testEscHtmlTranslation() {
		$text = esc_html__('Hello <World>', 'track-orders-for-woocommerce');
		$this->assertStringContainsString('&lt;World&gt;', $text);
	}
}
