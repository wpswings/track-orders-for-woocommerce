<?php
/**
 * Tests for Track Orders for WooCommerce Plugin
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Test basic plugin functionality.
 */
class PluginTest extends PolyfillTestCase {

	/**
	 * Test that plugin constants are defined.
	 */
	public function testPluginConstantsAreDefined() {
		$this->assertTrue(defined('TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION'));
		$this->assertTrue(defined('TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH'));
		$this->assertTrue(defined('TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL'));
	}

	/**
	 * Test plugin version constant.
	 */
	public function testPluginVersion() {
		$this->assertSame('1.2.7', TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION);
		$this->assertIsString(TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION);
	}

	/**
	 * Test plugin directory path constant.
	 */
	public function testPluginDirectoryPath() {
		$this->assertIsString(TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH);
		$this->assertStringEndsWith('/', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH);
	}

	/**
	 * Test plugin directory URL constant.
	 */
	public function testPluginDirectoryUrl() {
		$this->assertIsString(TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL);
		$this->assertStringContainsString('http', TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL);
	}

	/**
	 * Test main plugin file exists.
	 */
	public function testMainPluginFileExists() {
		$plugin_file = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'track-orders-for-woocommerce.php';
		$this->assertFileExists($plugin_file);
	}

	/**
	 * Test includes directory exists.
	 */
	public function testIncludesDirectoryExists() {
		$includes_dir = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'includes';
		$this->assertDirectoryExists($includes_dir);
	}

	/**
	 * Test admin directory exists.
	 */
	public function testAdminDirectoryExists() {
		$admin_dir = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'admin';
		$this->assertDirectoryExists($admin_dir);
	}

	/**
	 * Test public directory exists.
	 */
	public function testPublicDirectoryExists() {
		$public_dir = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'public';
		$this->assertDirectoryExists($public_dir);
	}

	/**
	 * Test README file exists.
	 */
	public function testReadmeFileExists() {
		$readme_file = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'README.txt';
		$this->assertFileExists($readme_file);
	}

	/**
	 * Test core class file exists.
	 */
	public function testCoreClassFileExists() {
		$core_class = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/class-track-orders-for-woocommerce.php';
		$this->assertFileExists($core_class);
	}
}
