<?php
/**
 * PHPUnit bootstrap file for Track Orders for WooCommerce
 *
 * @package Track_Orders_For_Woocommerce
 */

// Composer autoloader.
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load Polyfills for PHPUnit.
require_once dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

// Define constants.
define('TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION', '1.2.7');
define('TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH', dirname(__DIR__) . '/');
define('TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL', 'http://example.org/wp-content/plugins/track-orders-for-woocommerce/');

// Define WordPress constants if not defined.
if (!defined('ABSPATH')) {
	define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WP_PLUGIN_DIR')) {
	define('WP_PLUGIN_DIR', '/tmp/wordpress/wp-content/plugins');
}

if (!defined('WP_CONTENT_DIR')) {
	define('WP_CONTENT_DIR', '/tmp/wordpress/wp-content');
}

// Mock WordPress functions for testing.
if (!function_exists('__')) {
	function __($text, $domain = 'default') {
		return $text;
	}
}

if (!function_exists('esc_html__')) {
	function esc_html__($text, $domain = 'default') {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('esc_html')) {
	function esc_html($text) {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('esc_attr')) {
	function esc_attr($text) {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('esc_url')) {
	function esc_url($url) {
		return $url;
	}
}

if (!function_exists('esc_url_raw')) {
	function esc_url_raw($url) {
		return $url;
	}
}

if (!function_exists('sanitize_text_field')) {
	function sanitize_text_field($str) {
		return filter_var($str, FILTER_SANITIZE_STRING);
	}
}

if (!function_exists('sanitize_key')) {
	function sanitize_key($key) {
		return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
	}
}

if (!function_exists('wp_unslash')) {
	function wp_unslash($value) {
		return stripslashes_deep($value);
	}
}

if (!function_exists('stripslashes_deep')) {
	function stripslashes_deep($value) {
		return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
	}
}

if (!function_exists('admin_url')) {
	function admin_url($path = '', $scheme = 'admin') {
		return 'http://example.org/wp-admin/' . $path;
	}
}

if (!function_exists('plugin_dir_path')) {
	function plugin_dir_path($file) {
		return dirname($file) . '/';
	}
}

if (!function_exists('plugin_dir_url')) {
	function plugin_dir_url($file) {
		return 'http://example.org/wp-content/plugins/' . basename(dirname($file)) . '/';
	}
}

if (!function_exists('plugin_basename')) {
	function plugin_basename($file) {
		return basename(dirname($file)) . '/' . basename($file);
	}
}

if (!function_exists('get_option')) {
	function get_option($option, $default = false) {
		return $default;
	}
}

if (!function_exists('update_option')) {
	function update_option($option, $value, $autoload = null) {
		return true;
	}
}

if (!function_exists('add_option')) {
	function add_option($option, $value = '', $deprecated = '', $autoload = 'yes') {
		return true;
	}
}

if (!function_exists('delete_option')) {
	function delete_option($option) {
		return true;
	}
}

if (!function_exists('wp_json_encode')) {
	function wp_json_encode($data, $options = 0, $depth = 512) {
		return json_encode($data, $options, $depth);
	}
}

if (!function_exists('is_admin')) {
	function is_admin() {
		return false;
	}
}

if (!function_exists('is_multisite')) {
	function is_multisite() {
		return false;
	}
}

if (!function_exists('current_user_can')) {
	function current_user_can($capability) {
		return true;
	}
}

if (!function_exists('wp_verify_nonce')) {
	function wp_verify_nonce($nonce, $action = -1) {
		return 1;
	}
}

if (!function_exists('wp_create_nonce')) {
	function wp_create_nonce($action = -1) {
		return 'test_nonce';
	}
}

// Initialize test environment.
echo "PHPUnit Bootstrap loaded successfully.\n";
