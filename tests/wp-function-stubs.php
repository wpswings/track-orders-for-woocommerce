<?php
/**
 * Minimal WordPress function stubs for unit tests.
 *
 * Loaded from bootstrap.php AFTER Patchwork, so every function defined here can
 * still be redefined per test with Brain Monkey (Functions\when / Functions\expect).
 * Never define WordPress functions directly in bootstrap.php: Patchwork cannot
 * redefine functions declared before it was loaded.
 *
 * @package Track_Orders_For_Woocommerce
 */

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
		return trim(strip_tags((string) $str));
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

if (!function_exists('apply_filters')) {
	function apply_filters($hook_name, $value) {
		return $value;
	}
}
