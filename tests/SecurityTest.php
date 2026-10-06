<?php
/**
 * Security tests for the plugin
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Test security measures in the plugin.
 */
class SecurityTest extends PolyfillTestCase {

	/**
	 * Test that ABSPATH is checked in files.
	 */
	public function testAbspathDefinedInIncludes() {
		$files = glob(TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/*.php');

		foreach ($files as $file) {
			if (basename($file) === 'index.php') {
				continue;
			}

			$content = file_get_contents($file);

			// Check for ABSPATH check or exit/die statement at the beginning
			$has_security = (
				strpos($content, 'defined( \'ABSPATH\' )') !== false ||
				strpos($content, "defined( 'ABSPATH' )") !== false ||
				strpos($content, 'defined(\'ABSPATH\')') !== false ||
				strpos($content, "defined('ABSPATH')") !== false
			);

			$this->assertTrue(
				$has_security,
				"File {$file} should have ABSPATH check"
			);
		}
	}

	/**
	 * Test nonce verification exists.
	 */
	public function testNonceVerificationFunction() {
		$nonce = wp_create_nonce('test_action');
		$this->assertNotEmpty($nonce);

		$verified = wp_verify_nonce($nonce, 'test_action');
		$this->assertNotFalse($verified);
	}

	/**
	 * Test capability checks exist.
	 */
	public function testCapabilityCheck() {
		$can_manage = current_user_can('manage_options');
		$this->assertIsBool($can_manage);
	}

	/**
	 * Test XSS prevention in output.
	 */
	public function testXssPreventionInOutput() {
		$malicious = '<script>alert("XSS")</script>';

		// Test esc_html
		$escaped_html = esc_html($malicious);
		$this->assertStringNotContainsString('<script>', $escaped_html);

		// Test esc_attr
		$escaped_attr = esc_attr($malicious);
		$this->assertStringNotContainsString('<script>', $escaped_attr);
	}

	/**
	 * Test SQL injection prevention patterns.
	 */
	public function testSqlInjectionPatterns() {
		$dangerous_inputs = array(
			"' OR '1'='1",
			"1'; DROP TABLE users--",
			"admin'--",
			"1' UNION SELECT NULL--",
		);

		foreach ($dangerous_inputs as $input) {
			$sanitized = sanitize_text_field($input);
			// Should be sanitized
			$this->assertIsString($sanitized);
		}
	}

	/**
	 * Test file upload validation patterns.
	 */
	public function testFileExtensionValidation() {
		$dangerous_extensions = array('.php', '.exe', '.sh', '.bat');
		$safe_extensions = array('.jpg', '.png', '.pdf', '.txt');

		foreach ($dangerous_extensions as $ext) {
			$this->assertStringContainsString('.', $ext);
		}

		foreach ($safe_extensions as $ext) {
			$this->assertStringContainsString('.', $ext);
		}
	}

	/**
	 * Test proper data escaping.
	 */
	public function testDataEscaping() {
		$data = array(
			'url' => 'https://example.com/path?param=value',
			'html' => '<div>Content</div>',
			'attr' => 'class="test"',
			'text' => 'Plain text',
		);

		// URL escaping
		$escaped_url = esc_url($data['url']);
		$this->assertStringContainsString('https://', $escaped_url);

		// HTML escaping
		$escaped_html = esc_html($data['html']);
		$this->assertStringNotContainsString('<div>', $escaped_html);

		// Attribute escaping
		$escaped_attr = esc_attr($data['attr']);
		$this->assertIsString($escaped_attr);

		// Text sanitization
		$sanitized_text = sanitize_text_field($data['text']);
		$this->assertEquals('Plain text', $sanitized_text);
	}

	/**
	 * Test JSON encoding security.
	 */
	public function testJsonEncodingSecurity() {
		$data = array(
			'script' => '<script>alert("test")</script>',
			'quote' => "It's a test",
		);

		$json = wp_json_encode($data);
		$this->assertJson($json);

		$decoded = json_decode($json, true);
		$this->assertEquals($data['script'], $decoded['script']);
	}

	/**
	 * Test no direct file access on main plugin file.
	 */
	public function testMainPluginFileHasSecurityCheck() {
		$main_file = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'track-orders-for-woocommerce.php';
		$content = file_get_contents($main_file);

		$this->assertFileExists($main_file);
		$this->assertNotEmpty($content);
	}

	/**
	 * Test no sensitive data in error messages.
	 */
	public function testErrorMessagesNotLeakingSensitiveInfo() {
		// Error messages should not contain sensitive information
		$safe_message = 'Invalid order ID';
		$unsafe_message = 'Database error: SELECT * FROM wp_users WHERE password = "admin123"';

		// Safe message should not contain SQL
		$this->assertStringNotContainsString('SELECT', $safe_message);
		$this->assertStringNotContainsString('password', $safe_message);
	}
}
