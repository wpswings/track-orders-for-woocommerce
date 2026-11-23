<?php
/**
 * Gutenberg blocks of the plugin.
 *
 * @link       https://wpswings.com/
 * @since      1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "Track Order" block and renders it on the server.
 *
 * The block is dynamic: its output is produced by the same code that renders
 * the guest track order page form and the [WPS_MUTIPLE_CARRIER_TRACKING_FORM]
 * shortcode, so the markup is identical. The shortcode is kept for back-compat.
 *
 * @since      1.3.0
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */
class Track_Orders_For_Woocommerce_Blocks {

	/**
	 * The ID of this plugin.
	 *
	 * @since 1.3.0
	 * @var   string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 1.3.0
	 * @var   string
	 */
	private $version;

	/**
	 * Public class instance used to render the multi-carrier shortcode output.
	 *
	 * @since 1.3.0
	 * @var   Track_Orders_For_Woocommerce_Public|null
	 */
	private $public = null;

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of the plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the blocks from their block.json metadata.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function wps_tofw_register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'includes/blocks/track-order',
			array(
				'render_callback' => array( $this, 'wps_tofw_render_track_order_block' ),
			)
		);
	}

	/**
	 * Server-side render callback of the "Track Order" block.
	 *
	 * @since 1.3.0
	 * @param array         $attributes Block attributes.
	 * @param string        $content    Block inner content (unused, dynamic block).
	 * @param WP_Block|null $block      Block instance.
	 * @return string
	 */
	public function wps_tofw_render_track_order_block( $attributes, $content = '', $block = null ) {
		$attributes = wp_parse_args(
			(array) $attributes,
			array(
				'formType'  => 'order',
				'showTitle' => true,
				'position'  => 'left',
			)
		);
		$is_editor = $this->wps_tofw_is_editor_preview();

		if ( 'on' !== get_option( 'wps_tofw_is_plugin_enable' ) || 'on' !== get_option( 'tofw_enable_track_order', 'no' ) ) {
			return $is_editor ? $this->wps_tofw_editor_notice( __( 'Enable the plugin and the Track Order feature in WooCommerce > Track Orders settings to display the tracking form.', 'track-orders-for-woocommerce' ) ) : '';
		}

		if ( 'carrier' === $attributes['formType'] ) {
			if ( 'on' !== get_option( 'wps_tofwp_enable_multi_carrier_tracking' ) ) {
				return $is_editor ? $this->wps_tofw_editor_notice( __( 'Enable Multi-Carrier Tracking in the plugin settings to display the multi-carrier tracking form.', 'track-orders-for-woocommerce' ) ) : '';
			}
			$public = $this->wps_tofw_get_public();
			$html   = $public ? $public->wps_fetch_multiple_tracking_carrier_data( array( 'position' => sanitize_key( $attributes['position'] ) ) ) : '';
		} else {
			$html = $this->wps_tofw_get_track_order_form_html( ! empty( $attributes['showTitle'] ) );
		}

		/**
		 * Filter the inner HTML of the Track Order block.
		 *
		 * @since 1.3.0
		 * @param string        $html       Rendered form markup.
		 * @param array         $attributes Block attributes.
		 * @param WP_Block|null $block      Block instance.
		 */
		$html = apply_filters( 'wps_tofw_track_order_block_html', $html, $attributes, $block );

		if ( '' === trim( (string) $html ) ) {
			return '';
		}

		return sprintf(
			'<div %1$s>%2$s</div>',
			get_block_wrapper_attributes( array( 'class' => 'wps-tofw-track-order-block' ) ),
			$html
		);
	}

	/**
	 * Render the guest track order form (order id + billing email).
	 *
	 * Uses the same template partial as the guest track order page.
	 *
	 * @since 1.3.0
	 * @param bool $wps_tofw_show_title Whether to output the form heading.
	 * @return string
	 */
	public function wps_tofw_get_track_order_form_html( $wps_tofw_show_title = true ) {
		ob_start();
		include TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'template/wps-tofw-track-order-form.php';
		return (string) ob_get_clean();
	}

	/**
	 * Get (and lazily load) the public class instance.
	 *
	 * @since 1.3.0
	 * @return Track_Orders_For_Woocommerce_Public|null
	 */
	private function wps_tofw_get_public() {
		if ( null === $this->public ) {
			if ( ! class_exists( 'Track_Orders_For_Woocommerce_Public' ) ) {
				include_once TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'public/class-track-orders-for-woocommerce-public.php';
			}
			if ( class_exists( 'Track_Orders_For_Woocommerce_Public' ) ) {
				$this->public = new Track_Orders_For_Woocommerce_Public( $this->plugin_name, $this->version );
			}
		}
		return $this->public;
	}

	/**
	 * Whether the block is being rendered for the editor preview (ServerSideRender).
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	private function wps_tofw_is_editor_preview() {
		$is_rest = function_exists( 'wp_is_serving_rest_request' ) ? wp_is_serving_rest_request() : ( defined( 'REST_REQUEST' ) && REST_REQUEST );
		return $is_rest && current_user_can( 'edit_posts' );
	}

	/**
	 * Markup of a notice displayed in the editor only.
	 *
	 * @since 1.3.0
	 * @param string $message Notice text.
	 * @return string
	 */
	private function wps_tofw_editor_notice( $message ) {
		return sprintf(
			'<div %1$s><p>%2$s</p></div>',
			get_block_wrapper_attributes( array( 'class' => 'wps-tofw-track-order-block wps-tofw-track-order-block--notice' ) ),
			esc_html( $message )
		);
	}
}
