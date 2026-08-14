<?php
/**
 * Tests for data sanitization and validation
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Test sanitization and validation functions.
 */
class SanitizationTest extends PolyfillTestCase {

	/**
	 * Test sanitize_text_field function.
	 */
	public function testSanitizeTextField() {
		$input = '<script>alert("xss")</script>Hello World';
		$result = sanitize_text_field($input);
		$this->assertStringNotContainsString('<script>', $result);
		$this->assertStringNotContainsString('</script>', $result);
	}

	/**
	 * Test sanitize_key function.
	 */
	public function testSanitizeKey() {
		$input = 'Test Key With Spaces!@#';
		$result = sanitize_key($input);
		$this->assertMatchesRegularExpression('/^[a-z0-9_\-]+$/', $result);
		$this->assertStringNotContainsString(' ', $result);
		$this->assertStringNotContainsString('!', $result);
	}

	/**
	 * Test esc_html function.
	 */
	public function testEscHtml() {
		$input = '<div>Test & Content</div>';
		$result = esc_html($input);
		$this->assertStringContainsString('&lt;div&gt;', $result);
		$this->assertStringContainsString('&amp;', $result);
	}

	/**
	 * Test esc_attr function.
	 */
	public function testEscAttr() {
		$input = 'value"onclick="alert(\'xss\')"';
		$result = esc_attr($input);
		$this->assertStringContainsString('&quot;', $result);
	}

	/**
	 * Test esc_url function.
	 */
	public function testEscUrl() {
		$input = 'https://example.com/path?param=value';
		$result = esc_url($input);
		$this->assertIsString($result);
		$this->assertEquals($input, $result);
	}

	/**
	 * Test XSS prevention in input.
	 */
	public function testXssPrevention() {
		$malicious_input = '<img src=x onerror="alert(1)">';
		$sanitized = esc_html($malicious_input);
		$this->assertStringNotContainsString('onerror=', $sanitized);
		$this->assertStringNotContainsString('<img', $sanitized);
	}

	/**
	 * Test SQL injection prevention patterns.
	 */
	public function testSqlInjectionPrevention() {
		$malicious_input = "' OR '1'='1";
		$sanitized = sanitize_text_field($malicious_input);
		// Should not contain dangerous SQL patterns
		$this->assertIsString($sanitized);
	}

	/**
	 * Test wp_unslash function.
	 */
	public function testWpUnslash() {
		$slashed = "Test\'s data with \"quotes\"";
		$unslashed = wp_unslash($slashed);
		$this->assertStringNotContainsString('\\', $unslashed);
	}

	/**
	 * Test stripslashes_deep with array.
	 */
	public function testStripslashesDeep() {
		$slashed_array = array(
			'name' => "Test\'s",
			'value' => "Quote \"test\"",
		);
		$unslashed = stripslashes_deep($slashed_array);
		$this->assertIsArray($unslashed);
		$this->assertStringNotContainsString('\\', $unslashed['name']);
	}

	/**
	 * Test empty input handling.
	 */
	public function testEmptyInputHandling() {
		$this->assertEquals('', sanitize_text_field(''));
		$this->assertEquals('', esc_html(''));
		$this->assertEquals('', esc_attr(''));
	}
}
