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

// Load Patchwork (via Brain Monkey) BEFORE any WordPress function stub is declared,
// so tests can redefine those functions with Brain\Monkey\Functions\when()/expect().
require_once dirname(__DIR__) . '/vendor/brain/monkey/inc/patchwork-loader.php';

// Brain Monkey's hook API (add_action, apply_filters, do_action, ...) and small WP helpers
// (absint, is_wp_error, wp_json_encode, trailingslashit, ...). apply_filters() returns the
// filtered value unchanged unless a test sets an expectation with Brain\Monkey\Filters.
require_once dirname(__DIR__) . '/vendor/brain/monkey/inc/wp-hook-functions.php';
require_once dirname(__DIR__) . '/vendor/brain/monkey/inc/wp-helper-functions.php';

// Define constants.
// Read the version from the main plugin file so the tests never go stale on a release.
preg_match('/^\s*\*\s*Version:\s*(\S+)/m', (string) file_get_contents(dirname(__DIR__) . '/track-orders-for-woocommerce.php'), $tofw_version_match);
define('TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION', isset($tofw_version_match[1]) ? $tofw_version_match[1] : '0.0.0');
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

if (!defined('OBJECT')) {
	define('OBJECT', 'OBJECT');
}

if (!defined('ARRAY_A')) {
	define('ARRAY_A', 'ARRAY_A');
}

if (!defined('DAY_IN_SECONDS')) {
	define('DAY_IN_SECONDS', 86400);
}

// Load WordPress function stubs (after Patchwork, so they stay redefinable).
require_once __DIR__ . '/wp-function-stubs.php';

// Initialize test environment.
echo "PHPUnit Bootstrap loaded successfully.\n";
