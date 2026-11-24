<?php
/**
 * Script dependencies for the Track Order block editor script (no build step).
 *
 * @since   1.3.0
 * @package Track_Orders_For_Woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
	'version'      => '1.3.0',
);
