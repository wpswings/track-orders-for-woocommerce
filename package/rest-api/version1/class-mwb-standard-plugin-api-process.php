<?php
/**
 * Fired during plugin activation
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Track_Orders_For_Woocommerce_Api_Process' ) ) {

	/**
	 * The plugin API class.
	 *
	 * This is used to define the functions and data manipulation for custom endpoints.
	 *
	 * @since      1.0.0
	 * @package    Hydroshop_Api_Management
	 * @subpackage Hydroshop_Api_Management/includes
	 * @author     WPSwings <wpswings.com>
	 */
	class Track_Orders_For_Woocommerce_Api_Process {

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {

		}

		/**
		 * Define the function to process data for custom endpoint.
		 *
		 * @since    1.0.0
		 * @param   Array $tofw_request  data of requesting headers and other information.
		 * @return  Array $wps_tofw_rest_response    returns processed data and status of operations.
		 */
		public function wps_tofw_default_process( $tofw_request ) {
			$wps_tofw_rest_response = array();

			// Write your custom code here.

			$wps_tofw_rest_response['status'] = 200;
			$wps_tofw_rest_response['data'] = $tofw_request->get_headers();
			return $wps_tofw_rest_response;
		}
	}
}
