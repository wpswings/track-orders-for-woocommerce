<?php
/**
 * Minimal WooCommerce class doubles for the WPS-8400 estimated delivery unit tests.
 *
 * Declared only when WooCommerce is not loaded. WC_Shipping_Rate mirrors the
 * WooCommerce 9.2+ API (delivery_time), which is what the plugin targets.
 *
 * @package Track_Orders_For_Woocommerce
 */

if ( ! class_exists( 'WC_Shipping_Rate' ) ) {
	/**
	 * WC_Shipping_Rate double (WooCommerce 9.2+ API subset).
	 */
	class WC_Shipping_Rate { // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound

		/**
		 * Data.
		 *
		 * @var array
		 */
		protected $data = array(
			'id'            => '',
			'label'         => '',
			'instance_id'   => 0,
			'delivery_time' => '',
		);

		/**
		 * Meta data.
		 *
		 * @var array
		 */
		protected $meta_data = array();

		/**
		 * Constructor.
		 *
		 * @param string $id          Rate id.
		 * @param string $label       Label.
		 * @param int    $instance_id Instance id.
		 */
		public function __construct( $id = '', $label = '', $instance_id = 0 ) {
			$this->data['id']          = $id;
			$this->data['label']       = $label;
			$this->data['instance_id'] = $instance_id;
		}

		/**
		 * Instance id.
		 *
		 * @return int
		 */
		public function get_instance_id() {
			return $this->data['instance_id'];
		}

		/**
		 * Label.
		 *
		 * @return string
		 */
		public function get_label() {
			return $this->data['label'];
		}

		/**
		 * Set label.
		 *
		 * @param string $label Label.
		 */
		public function set_label( $label ) {
			$this->data['label'] = $label;
		}

		/**
		 * Delivery time (WC 9.2+).
		 *
		 * @return string
		 */
		public function get_delivery_time() {
			return $this->data['delivery_time'];
		}

		/**
		 * Set delivery time (WC 9.2+).
		 *
		 * @param string $delivery_time Text.
		 */
		public function set_delivery_time( $delivery_time ) {
			$this->data['delivery_time'] = $delivery_time;
		}

		/**
		 * Add meta.
		 *
		 * @param string $key   Key.
		 * @param mixed  $value Value.
		 */
		public function add_meta_data( $key, $value ) {
			$this->meta_data[ $key ] = $value;
		}

		/**
		 * Meta.
		 *
		 * @return array
		 */
		public function get_meta_data() {
			return $this->meta_data;
		}
	}
}
