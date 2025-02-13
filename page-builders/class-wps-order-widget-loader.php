<?php
/**
 * Order elementor widgets collection loader file.
 *
 * @link       https://wpswings.com/?utm_source=wpswings-official&utm_medium=order-org-backend&utm_campaign=official
 * @since      1.1.1
 *
 * @package    track-orders-for-woocommerce
 * @subpackage track-orders-for-woocommerce/page-builders
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit; // Exit if accessed directly.
}

if ( class_exists( 'WPS_Order_Widget_Loader' ) ) {
	return;
}

/**
 * WPS_Order_Widget_Loader.
 */
class WPS_Order_Widget_Loader {

	const WPS_Order_Widget_Loader = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'page-builders/';

	/**
	 * The instance.
	 *
	 * @var stdClass
	 */
	protected static $instance;

	/**
	 * The unique widget storage of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Woocommerce_Order_Widgets_Loader    $widgets    Maintains and registers all widgets for the plugin.
	 */
	protected $widgets;

	/**
	 * The active builders.
	 *
	 * @var stdClass
	 */
	protected $active_builders;

	/**
	 * The builders that are in compatible list.
	 *
	 * @var stdClass
	 */
	const COMPATIBLE_BUILDERS = array(
		'elementor/elementor.php' => 'Elementor',
	);

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    3.1.2
	 */
	public function __construct() {
		$this->load_builders();
		$this->load_widgets();
	}

	/**
	 * Ensures only one instance of Widget Loader is loaded or can be loaded.
	 *
	 * @since 3.1.2
	 * @static
	 * @return Widget Loader - Main instance.
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {

			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Ensures plugins are active or not loaded.
	 *
	 * @since 3.1.2
	 */
	public function load_builders() {

		$builders = self::COMPATIBLE_BUILDERS;

		if ( ! empty( $builders ) && is_array( $builders ) ) {
			foreach ( $builders as $slug => $class_name ) {
				$active = false;

				
				$active = true;
				if ( ! $active ) {
					// If still in active then unset from active builders.
					unset( $builders[ $slug ] );
				}
			}
		}

		$this->active_builders = apply_filters( 'wps_active_page_builders', $builders );

		return $this->active_builders;
	}

	/**
	 * Ensures plugins are active or not loaded.
	 *
	 * @since 3.1.2
	 */
	public function load_widgets() {

		if ( ! empty( $this->active_builders ) && is_array( $this->active_builders ) ) {
			foreach ( $this->active_builders as $b_slug => $b_name ) {
				$widget_file = $this->retrieve_loader_file( $b_slug, $b_slug );
				$widget_path = self::WPS_Order_Widget_Loader . $widget_file;

				if ( file_exists( $widget_path ) ) {
					require_once $widget_path;
				}
			}
		}
	}

	/**
	 * Ensures plugins are active or not loaded.
	 *
	 * @param string $slug The plugin directory.
	 * @param string $builders The builders name.
	 * @since 3.1.2
	 */
	public function retrieve_loader_file( $slug = '', $builders = '' ) {

		if ( empty( $slug ) && empty( $builders ) ) {
			return false;
		}

		switch ( $slug ) {
			case 'elementor/elementor.php':
				$loader_file = 'elementor/class-elementor-widget-loader.php';
				break;
		}

		return $loader_file;
	}

	// End of class.
}
