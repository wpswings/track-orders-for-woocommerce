<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 * namespace track_orders_for_woocommerce_public.
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/public
 */
class Track_Orders_For_Woocommerce_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function tofw_public_enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/css/track-orders-for-woocommerce-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function tofw_public_enqueue_scripts() {

		wp_register_script( $this->plugin_name, TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'public/js/track-orders-for-woocommerce-public.js', array( 'jquery' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'tofw_public_param', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( $this->plugin_name );

	}


	/**
	 * This function is for rendering track order button
	 *
	 * @link http://www.wpswings.com/
	 * @param object $order is a object.
	 */
	public function wps_tofw_track_order_button( $order ) {
		if ( '3.0.0' > WC()->version ) {
			$order_id = $order->id;
		} else {
			$order_id = $order->get_id();
		}
		$wps_tofw_enable_track_order_feature = get_option( 'tofw_enable_track_order', 'no' );
		if ( 'on' != $wps_tofw_enable_track_order_feature ) {
			return;
		}
		$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
		$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
		$track_order_url = get_permalink( $page_id );
		?>
		<p><label class="wps_enhanced_order_note"><?php esc_html_e( 'Note:', 'track-orders-for-woocommerce' ); ?></label><span class="wps_order_note_text"><?php esc_html_e( 'Click The Below To Track Your Order', 'track-orders-for-woocommerce' ); ?></span></p>
			<a href="<?php echo esc_attr( $track_order_url ) . '?' . esc_attr( $order_id ); ?>" class="button button-primary"><?php esc_html_e( 'TRACK ORDER', 'track-orders-for-woocommerce' ); ?></a>
		<?php
		
	}

	/**
	 * Function to add track order button.
	 *
	 * @param array $actions is an array.
	 * @param object $order is the object.
	 * @return void
	 */
	public function wps_tofw_add_track_order_button_on_orderpage($actions, $order){
		$wps_tofw_enable_track_order_feature = get_option( 'tofw_enable_track_order', 'no' );
		if ( 'on' != $wps_tofw_enable_track_order_feature ) {
			return $actions;
		}
		$wps_tofw_enable_track_order_popup = get_option( 'wps_tofw_enable_track_order_popup', 'no' );
		$wps_tofw_pages = get_option( 'wps_tofw_tracking_page' );
		$page_id = $wps_tofw_pages['pages']['wps_track_order_page'];
		if ( '3.0.0' > WC()->version ) {
			$order_id = $order->id;
			$track_order_url = get_permalink( $page_id );
			$actions['wps_track_order']['url']  = $track_order_url . '?' . $order_id;
			$actions['wps_track_order']['name']     = __( 'Track Order', 'track-orders-for-woocommerce' );
		} else {
			$order_id = $order->get_id();
			$track_order_url = get_permalink( $page_id );
			$actions['wps_track_order']['url']  = $track_order_url . '?' . $order_id;
			$actions['wps_track_order']['name']     = __( 'Track Order', 'track-orders-for-woocommerce' );
		}

		return $actions;
	}


}
