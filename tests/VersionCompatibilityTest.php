<?php
/**
 * Version and compatibility tests
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Test version compatibility and requirements.
 */
class VersionCompatibilityTest extends PolyfillTestCase {

	/**
	 * Test PHP version requirement.
	 */
	public function testPhpVersionRequirement() {
		$this->assertTrue(version_compare(PHP_VERSION, '7.4', '>='));
	}

	/**
	 * Test plugin version format.
	 */
	public function testPluginVersionFormat() {
		$version = TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION;
		$this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);
	}

	/**
	 * Test version is semantic versioning compatible.
	 */
	public function testSemanticVersioning() {
		$version = TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION;
		$parts = explode('.', $version);

		$this->assertCount(3, $parts);
		$this->assertIsNumeric($parts[0]); // Major
		$this->assertIsNumeric($parts[1]); // Minor
		$this->assertIsNumeric($parts[2]); // Patch
	}

	/**
	 * Test README version matches plugin version.
	 */
	public function testReadmeVersionMatches() {
		$readme_path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'README.txt';
		$readme_content = file_get_contents($readme_path);

		$plugin_version = TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION;

		$this->assertStringContainsString($plugin_version, $readme_content);
	}

	/**
	 * Test main plugin file version matches constant.
	 */
	public function testMainFileVersionMatches() {
		$main_file = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'track-orders-for-woocommerce.php';
		$main_content = file_get_contents($main_file);

		$plugin_version = TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION;

		$this->assertStringContainsString("Version:           {$plugin_version}", $main_content);
		$this->assertStringContainsString("'TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION', '{$plugin_version}'", $main_content);
	}

	/**
	 * Test WordPress minimum version requirement exists.
	 */
	public function testWordPressMinVersionExists() {
		$readme_path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'README.txt';
		$readme_content = file_get_contents($readme_path);

		$this->assertStringContainsString('Requires at least:', $readme_content);
	}

	/**
	 * Test WordPress tested up to version exists.
	 */
	public function testWordPressTestedUpToExists() {
		$readme_path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'README.txt';
		$readme_content = file_get_contents($readme_path);

		$this->assertStringContainsString('Tested up to:', $readme_content);
	}

	/**
	 * Test WooCommerce compatibility declared.
	 */
	public function testWooCommerceCompatibilityDeclared() {
		$main_file = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'track-orders-for-woocommerce.php';
		$main_content = file_get_contents($main_file);

		$this->assertStringContainsString('WC requires at least:', $main_content);
		$this->assertStringContainsString('WC tested up to:', $main_content);
	}

	/**
	 * Test WooCommerce 11.0.1 compatibility.
	 */
	public function testWooCommerce1101Compatibility() {
		$main_file = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'track-orders-for-woocommerce.php';
		$main_content = file_get_contents($main_file);

		$this->assertStringContainsString('WC tested up to:      11.0.1', $main_content);

		$readme_file = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'README.txt';
		$readme_content = file_get_contents($readme_file);

		$this->assertStringContainsString('WC tested up to: 11.0.1', $readme_content);
	}

	/**
	 * Test HPOS compatibility declared.
	 */
	public function testHposCompatibilityDeclared() {
		$main_file = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'track-orders-for-woocommerce.php';
		$main_content = file_get_contents($main_file);

		$this->assertStringContainsString('custom_order_tables', $main_content);
		$this->assertStringContainsString('declare_compatibility', $main_content);
	}

	/**
	 * Test cart and checkout blocks compatibility.
	 */
	public function testCartCheckoutBlocksCompatibility() {
		$main_file = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'track-orders-for-woocommerce.php';
		$main_content = file_get_contents($main_file);

		$this->assertStringContainsString('cart_checkout_blocks', $main_content);
	}

	/**
	 * Test text domain is correct.
	 */
	public function testTextDomainIsCorrect() {
		$main_file = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'track-orders-for-woocommerce.php';
		$main_content = file_get_contents($main_file);

		$this->assertStringContainsString('Text Domain:       track-orders-for-woocommerce', $main_content);
	}

	/**
	 * Test license is declared.
	 */
	public function testLicenseIsDeclared() {
		$readme_path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'README.txt';
		$readme_content = file_get_contents($readme_path);

		$this->assertStringContainsString('License:', $readme_content);
		$this->assertStringContainsString('GPL', $readme_content);
	}

	/**
	 * Test PHP required version in README.
	 */
	public function testPhpRequirementInReadme() {
		$readme_path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'README.txt';
		$readme_content = file_get_contents($readme_path);

		$this->assertStringContainsString('Requires PHP:', $readme_content);
		$this->assertStringContainsString('7.4', $readme_content);
	}
}
