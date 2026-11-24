<?php
/**
 * Shared setup for the shipping plugin auto-import tests (WPS-8398).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\AutoImport;

use Brain\Monkey\Functions;
use Tests\Unit\TestCase;

/**
 * Loads the integration classes and stubs the WordPress functions they use.
 */
abstract class AutoImportTestCase extends TestCase {

	/**
	 * Order notes added through WC_Order::add_order_note(), keyed by order ID.
	 *
	 * @var array
	 */
	protected $order_notes = array();

	/**
	 * Recipients of wc_mail() calls.
	 *
	 * @var array
	 */
	protected $mails = array();

	/**
	 * Load the classes under test and stub WordPress.
	 */
	protected function set_up() {
		parent::set_up();
		$this->order_notes = array();
		$this->mails       = array();

		$this->requirePluginFile( 'includes/class-wps-tofw-tracking-service.php' );
		$this->requirePluginFile( 'includes/integrations/interface-wps-tofw-shipping-integration.php' );
		$this->requirePluginFile( 'includes/integrations/abstract-class-wps-tofw-shipping-integration.php' );
		$this->requirePluginFile( 'includes/integrations/class-wps-tofw-integrations-manager.php' );
		foreach ( array( 'shipstation', 'woocommerce-shipping', 'shipment-tracking', 'shippo', 'sendcloud', 'printful', 'printify' ) as $slug ) {
			$this->requirePluginFile( 'includes/integrations/class-wps-tofw-integration-' . $slug . '.php' );
		}

		$this->stubCommonWpFunctions();
		$this->setOptions( array( 'active_plugins' => array() ) );

		$mails = &$this->mails;
		Functions\stubs(
			array(
				'wp_strip_all_tags'        => static function ( $text ) {
					return trim( strip_tags( (string) $text ) );
				},
				'wp_parse_args'            => static function ( $args, $defaults = array() ) {
					return array_merge( (array) $defaults, (array) $args );
				},
				'sanitize_title'           => static function ( $title ) {
					$title = strtolower( trim( (string) $title ) );
					$title = preg_replace( '/[^a-z0-9\s\-_]/', '', $title );
					return trim( preg_replace( '/[\s_\-]+/', '-', $title ), '-' );
				},
				'maybe_unserialize'        => static function ( $value ) {
					if ( is_string( $value ) ) {
						$data = @unserialize( $value ); // phpcs:ignore
						if ( false !== $data || 'b:0;' === $value ) {
							return $data;
						}
					}
					return $value;
				},
				'wc_get_order_status_name' => static function ( $status ) {
					return ucfirst( (string) $status );
				},
				'wc_mail'                  => static function ( $to ) use ( &$mails ) {
					$mails[] = $to;
					return true;
				},
				// Integrations that look for a WooCommerce REST API key: answer from the "cache" (no key found).
				'wp_cache_get'             => 0,
				'wp_cache_set'             => true,
			)
		);
	}

	/**
	 * Mocked order that records order notes and has a get_type().
	 *
	 * @param array $props mockOrder() props.
	 * @return \Mockery\MockInterface|\WC_Order
	 */
	protected function mockShopOrder( array $props = array() ) {
		$id                         = isset( $props['id'] ) ? (int) $props['id'] : 1;
		$this->order_notes[ $id ]   = array();
		$notes                      = &$this->order_notes[ $id ];
		$props['get_type']          = isset( $props['get_type'] ) ? $props['get_type'] : 'shop_order';
		$props['add_order_note']    = static function ( $note ) use ( &$notes ) {
			$notes[] = $note;
			return count( $notes );
		};
		return $this->mockOrder( $props );
	}

	/**
	 * Turn integrations on (option "wps_tofw_auto_import_<id>" = on).
	 *
	 * @param string ...$ids Integration IDs.
	 */
	protected function enableIntegrations( ...$ids ) {
		$options = array();
		foreach ( $ids as $id ) {
			$options[ 'wps_tofw_auto_import_' . $id ] = 'on';
		}
		$this->setOptions( $options );
	}

	/**
	 * Mark plugins active (option "active_plugins").
	 *
	 * @param string ...$plugin_files Plugin files, e.g. "woocommerce-shipstation-integration/woocommerce-shipstation.php".
	 */
	protected function activatePlugins( ...$plugin_files ) {
		$this->setOptions( array( 'active_plugins' => $plugin_files ) );
	}
}
