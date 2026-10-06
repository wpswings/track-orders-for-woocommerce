<?php
/**
 * The file that defines the core plugin api class
 *
 * A class definition that includes api's endpoints and functions used across the plugin
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/package/rest-api/version1
 */

/**
 * The core plugin  api class.
 *
 * This is used to define internationalization, api-specific hooks, and
 * endpoints for plugin.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/package/rest-api/version1
 * @author     WPSwings <webmaster@wpswings.com>
 */
class Track_Orders_For_Woocommerce_Rest_Api {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin api.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the merthods, and set the hooks for the api and
	 *
	 * @since    1.0.0
	 * @param   string $plugin_name    Name of the plugin.
	 * @param   string $version        Version of the plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}


	/**
	 * Define endpoints for the plugin.
	 *
	 * Uses the Track_Orders_For_Woocommerce_Rest_Api class in order to create the endpoint
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function wps_tofw_add_endpoint() {
		register_rest_route(
			'tofw-route/v1',
			'/tofw-dummy-data/',
			array(
				// 'methods'  => 'POST',
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'wps_tofw_default_callback' ),
				'permission_callback' => array( $this, 'wps_tofw_default_permission_check' ),
			)
		);

		register_rest_route(
			'tofw-route/v1',
			'/orders',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'wps_tofw_get_orders_callback' ),
					'permission_callback' => array( $this, 'wps_tofw_read_permission_check' ),
					'args' => array(
						'page' => array(
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page' => array(
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
						'status' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
						'customer_id' => array(
							'sanitize_callback' => 'absint',
						),
						'search' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
						'fields' => array(
							'sanitize_callback' => 'sanitize_text_field',
						),
						'include_items' => array(
							'default'           => false,
							'sanitize_callback' => array( $this, 'wps_tofw_sanitize_bool' ),
						),
					),
				),
				array(
					'methods'  => WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'wps_tofw_create_order_callback' ),
					'permission_callback' => array( $this, 'wps_tofw_write_permission_check' ),
				),
			)
		);

		register_rest_route(
			'tofw-route/v1',
			'/orders/(?P<id>\d+)',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'wps_tofw_get_order_callback' ),
				'permission_callback' => array( $this, 'wps_tofw_read_permission_check' ),
				'args' => array(
					'include_items' => array(
						'default'           => true,
						'sanitize_callback' => array( $this, 'wps_tofw_sanitize_bool' ),
					),
					'fields' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'tofw-route/v1',
			'/order-statuses',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'wps_tofw_get_order_statuses_callback' ),
				'permission_callback' => array( $this, 'wps_tofw_read_permission_check' ),
			)
		);
	}


	/**
	 * Begins validation process of api endpoint.
	 *
	 * @param   Array $request    All information related with the api request containing in this array.
	 * @return  Array   $result   return rest response to server from where the endpoint hits.
	 * @since    1.0.0
	 */
	public function wps_tofw_default_permission_check( $request ) {

		return $this->wps_tofw_read_permission_check( $request );
	}

	/**
	 * Permission callback for read endpoints.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function wps_tofw_read_permission_check( $request ) {
		return $this->wps_tofw_check_permissions( array( 'manage_woocommerce', 'edit_shop_orders' ) );
	}

	/**
	 * Permission callback for create/update endpoints.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function wps_tofw_write_permission_check( $request ) {
		return $this->wps_tofw_check_permissions( array( 'manage_woocommerce', 'edit_shop_orders' ) );
	}


	/**
	 * Begins execution of api endpoint.
	 *
	 * @param   Array $request    All information related with the api request containing in this array.
	 * @return  Array   $wps_tofw_response   return rest response to server from where the endpoint hits.
	 * @since    1.0.0
	 */
	public function wps_tofw_default_callback( $request ) {

		require_once TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . 'package/rest-api/version1/class-track-orders-for-woocommerce-api-process.php';
		$wps_tofw_api_obj = new Track_Orders_For_Woocommerce_Api_Process();
		$wps_tofw_resultsdata = $wps_tofw_api_obj->wps_tofw_default_process( $request );
		if ( is_array( $wps_tofw_resultsdata ) && isset( $wps_tofw_resultsdata['status'] ) && 200 == $wps_tofw_resultsdata['status'] ) {
			unset( $wps_tofw_resultsdata['status'] );
			$wps_tofw_response = new WP_REST_Response( $wps_tofw_resultsdata, 200 );
		} else {
			$wps_tofw_response = new WP_Error( $wps_tofw_resultsdata );
		}
		return $wps_tofw_response;
	}

	/**
	 * Get all orders.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function wps_tofw_get_orders_callback( $request ) {
		$page          = max( 1, absint( $request->get_param( 'page' ) ) );
		$per_page      = absint( $request->get_param( 'per_page' ) );
		$per_page      = ( $per_page > 0 ) ? min( $per_page, 100 ) : 20;
		$customer_id   = absint( $request->get_param( 'customer_id' ) );
		$search        = trim( (string) $request->get_param( 'search' ) );
		$fields        = (string) $request->get_param( 'fields' );
		$include_items = $this->wps_tofw_sanitize_bool( $request->get_param( 'include_items' ) );
		$status        = $request->get_param( 'status' );
		$statuses      = $this->wps_tofw_sanitize_statuses_param( $status );

		if ( ! empty( $status ) && empty( $statuses ) ) {
			return new WP_Error(
				'tofw_invalid_status',
				esc_html__( 'Invalid order status filter provided.', 'track-orders-for-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$query_args = array(
			'type'     => 'shop_order',
			'limit'    => $per_page,
			'paginate' => true,
			'page'     => $page,
			'orderby'  => 'date',
			'order'    => 'DESC',
		);

		if ( ! empty( $statuses ) ) {
			$query_args['status'] = $statuses;
		}

		if ( $customer_id > 0 ) {
			$query_args['customer_id'] = $customer_id;
		}

		if ( '' !== $search ) {
			$query_args['search'] = '*' . sanitize_text_field( $search ) . '*';
		}

		try {
			$orders_result = wc_get_orders( $query_args );
		} catch ( Exception $exception ) {
			return new WP_Error(
				'tofw_orders_fetch_error',
				$exception->getMessage(),
				array( 'status' => 500 )
			);
		}

		$orders      = array();
		$total       = 0;
		$total_pages = 0;
		if ( is_object( $orders_result ) && isset( $orders_result->orders ) ) {
			$orders      = $orders_result->orders;
			$total       = isset( $orders_result->total ) ? (int) $orders_result->total : 0;
			$total_pages = isset( $orders_result->max_num_pages ) ? (int) $orders_result->max_num_pages : 0;
		} elseif ( is_array( $orders_result ) ) {
			$orders      = $orders_result;
			$total       = count( $orders_result );
			$total_pages = 1;
		}

		$orders_data = array();
		foreach ( $orders as $order ) {
			$order = ( $order instanceof WC_Order ) ? $order : wc_get_order( $order );
			if ( ! $order ) {
				continue;
			}
			$order_data    = $this->wps_tofw_prepare_order_response( $order, $include_items );
			$orders_data[] = $this->wps_tofw_apply_requested_fields( $order_data, $fields );
		}

		$response_data = array(
			'success'     => true,
			'page'        => $page,
			'per_page'    => $per_page,
			'total'       => $total,
			'total_pages' => $total_pages,
			'orders'      => $orders_data,
		);

		return new WP_REST_Response( $response_data, 200 );
	}

	/**
	 * Get order by id.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function wps_tofw_get_order_callback( $request ) {
		$order_id = absint( $request->get_param( 'id' ) );
		$order    = wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_Error(
				'tofw_order_not_found',
				esc_html__( 'Order not found.', 'track-orders-for-woocommerce' ),
				array( 'status' => 404 )
			);
		}

		$include_items = $this->wps_tofw_sanitize_bool( $request->get_param( 'include_items' ) );
		$fields        = (string) $request->get_param( 'fields' );
		$order_data    = $this->wps_tofw_prepare_order_response( $order, $include_items );
		$order_data    = $this->wps_tofw_apply_requested_fields( $order_data, $fields );

		return new WP_REST_Response(
			array(
				'success' => true,
				'order'   => $order_data,
			),
			200
		);
	}

	/**
	 * Get all order statuses including custom statuses.
	 *
	 * @return WP_REST_Response
	 */
	public function wps_tofw_get_order_statuses_callback() {
		$statuses     = wc_get_order_statuses();
		$status_items = array();

		foreach ( $statuses as $status_key => $status_label ) {
			$status_slug    = ( 0 === strpos( $status_key, 'wc-' ) ) ? substr( $status_key, 3 ) : $status_key;
			$status_items[] = array(
				'key'   => $status_key,
				'slug'  => $status_slug,
				'label' => wp_strip_all_tags( $status_label ),
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'count'   => count( $status_items ),
				'statuses' => $status_items,
			),
			200
		);
	}

	/**
	 * Create a new order via API.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 * @throws Exception Throws an exception when order creation steps fail.
	 */
	public function wps_tofw_create_order_callback( $request ) {
		$payload = $request->get_json_params();
		if ( empty( $payload ) || ! is_array( $payload ) ) {
			$payload = $request->get_params();
		}

		if ( ! is_array( $payload ) ) {
			return new WP_Error(
				'tofw_invalid_payload',
				esc_html__( 'Invalid request payload.', 'track-orders-for-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$line_items = isset( $payload['line_items'] ) ? $payload['line_items'] : array();
		if ( ! is_array( $line_items ) || empty( $line_items ) ) {
			return new WP_Error(
				'tofw_missing_line_items',
				esc_html__( 'line_items is required and must be a non-empty array.', 'track-orders-for-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		$prepared_line_items = array();
		foreach ( $line_items as $index => $line_item ) {
			if ( ! is_array( $line_item ) ) {
				return new WP_Error(
					'tofw_invalid_line_item',
					sprintf(
						/* translators: %d: line item index. */
						esc_html__( 'Invalid line item at index %d.', 'track-orders-for-woocommerce' ),
						(int) $index
					),
					array( 'status' => 400 )
				);
			}

			$product_id = isset( $line_item['variation_id'] ) && absint( $line_item['variation_id'] ) > 0
				? absint( $line_item['variation_id'] )
				: absint( isset( $line_item['product_id'] ) ? $line_item['product_id'] : 0 );

			if ( $product_id <= 0 ) {
				return new WP_Error(
					'tofw_missing_product',
					sprintf(
						/* translators: %d: line item index. */
						esc_html__( 'line_items[%d] must include a valid product_id.', 'track-orders-for-woocommerce' ),
						(int) $index
					),
					array( 'status' => 400 )
				);
			}

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				return new WP_Error(
					'tofw_product_not_found',
					sprintf(
						/* translators: %1$d: product id, %2$d: line item index. */
						esc_html__( 'Product %1$d not found for line_items[%2$d].', 'track-orders-for-woocommerce' ),
						(int) $product_id,
						(int) $index
					),
					array( 'status' => 404 )
				);
			}

			$quantity = isset( $line_item['quantity'] ) ? absint( $line_item['quantity'] ) : 1;
			if ( $quantity <= 0 ) {
				return new WP_Error(
					'tofw_invalid_quantity',
					sprintf(
						/* translators: %d: line item index */
						esc_html__( 'line_items[%d].quantity must be greater than 0.', 'track-orders-for-woocommerce' ),
						(int) $index
					),
					array( 'status' => 400 )
				);
			}

			$item_args = array();
			if ( isset( $line_item['subtotal'] ) ) {
				$item_args['subtotal'] = wc_format_decimal( $line_item['subtotal'] );
			}
			if ( isset( $line_item['total'] ) ) {
				$item_args['total'] = wc_format_decimal( $line_item['total'] );
			}
			if ( isset( $line_item['variation'] ) && is_array( $line_item['variation'] ) ) {
				$item_args['variation'] = map_deep( $line_item['variation'], 'sanitize_text_field' );
			}

			$prepared_line_items[] = array(
				'product'   => $product,
				'quantity'  => $quantity,
				'item_args' => $item_args,
				'meta_data' => isset( $line_item['meta_data'] ) ? $line_item['meta_data'] : array(),
			);
		}

		$status = 'pending';
		if ( isset( $payload['status'] ) && '' !== (string) $payload['status'] ) {
			$status = $this->wps_tofw_normalize_order_status( $payload['status'] );
			if ( is_wp_error( $status ) ) {
				return $status;
			}
		}

		$order = null;
		try {
			$order_args = array(
				'status' => $status,
			);

			if ( isset( $payload['customer_id'] ) && absint( $payload['customer_id'] ) > 0 ) {
				$order_args['customer_id'] = absint( $payload['customer_id'] );
			}

			$order = wc_create_order( $order_args );
			if ( ! $order ) {
				throw new Exception( esc_html__( 'Unable to initialize order.', 'track-orders-for-woocommerce' ) );
			}

			if ( isset( $payload['currency'] ) ) {
				$order->set_currency( sanitize_text_field( (string) $payload['currency'] ) );
			}

			if ( isset( $payload['billing'] ) && is_array( $payload['billing'] ) ) {
				$order->set_address( $this->wps_tofw_normalize_address( $payload['billing'] ), 'billing' );
			}

			if ( isset( $payload['shipping'] ) && is_array( $payload['shipping'] ) ) {
				$order->set_address( $this->wps_tofw_normalize_address( $payload['shipping'] ), 'shipping' );
			}

			if ( isset( $payload['payment_method'] ) ) {
				$order->set_payment_method( sanitize_text_field( (string) $payload['payment_method'] ) );
			}

			if ( isset( $payload['payment_method_title'] ) ) {
				$order->set_payment_method_title( sanitize_text_field( (string) $payload['payment_method_title'] ) );
			}

			if ( isset( $payload['customer_note'] ) ) {
				$order->set_customer_note( sanitize_text_field( (string) $payload['customer_note'] ) );
			}

			if ( isset( $payload['transaction_id'] ) ) {
				$order->set_transaction_id( sanitize_text_field( (string) $payload['transaction_id'] ) );
			}

			foreach ( $prepared_line_items as $prepared_line_item ) {
				$order_item_id = $order->add_product(
					$prepared_line_item['product'],
					$prepared_line_item['quantity'],
					$prepared_line_item['item_args']
				);

				if ( ! $order_item_id ) {
					throw new Exception( esc_html__( 'Failed to add line item in order.', 'track-orders-for-woocommerce' ) );
				}

				if ( ! empty( $prepared_line_item['meta_data'] ) ) {
					$order_item = $order->get_item( $order_item_id );
					if ( $order_item ) {
						$this->wps_tofw_add_item_meta_data( $order_item, $prepared_line_item['meta_data'] );
					}
				}
			}

			if ( isset( $payload['shipping_lines'] ) && is_array( $payload['shipping_lines'] ) ) {
				foreach ( $payload['shipping_lines'] as $shipping_line ) {
					if ( ! is_array( $shipping_line ) ) {
						continue;
					}

					$order_shipping_item = new WC_Order_Item_Shipping();
					if ( isset( $shipping_line['method_title'] ) ) {
						$order_shipping_item->set_method_title( sanitize_text_field( (string) $shipping_line['method_title'] ) );
					}
					if ( isset( $shipping_line['method_id'] ) ) {
						$order_shipping_item->set_method_id( sanitize_text_field( (string) $shipping_line['method_id'] ) );
					}
					if ( isset( $shipping_line['total'] ) ) {
						$order_shipping_item->set_total( (float) wc_format_decimal( $shipping_line['total'] ) );
					}
					$order->add_item( $order_shipping_item );
				}
			}

			if ( isset( $payload['fee_lines'] ) && is_array( $payload['fee_lines'] ) ) {
				foreach ( $payload['fee_lines'] as $fee_line ) {
					if ( ! is_array( $fee_line ) ) {
						continue;
					}

					$order_fee_item = new WC_Order_Item_Fee();
					$order_fee_item->set_name(
						isset( $fee_line['name'] ) ? sanitize_text_field( (string) $fee_line['name'] ) : esc_html__( 'Fee', 'track-orders-for-woocommerce' )
					);

					$fee_total = isset( $fee_line['total'] ) ? (float) wc_format_decimal( $fee_line['total'] ) : 0;
					$order_fee_item->set_amount( $fee_total );
					$order_fee_item->set_total( $fee_total );

					if ( isset( $fee_line['tax_status'] ) ) {
						$order_fee_item->set_tax_status( sanitize_text_field( (string) $fee_line['tax_status'] ) );
					}

					if ( isset( $fee_line['tax_class'] ) ) {
						$order_fee_item->set_tax_class( sanitize_text_field( (string) $fee_line['tax_class'] ) );
					}

					$order->add_item( $order_fee_item );
				}
			}

			if ( isset( $payload['coupon_lines'] ) && is_array( $payload['coupon_lines'] ) ) {
				foreach ( $payload['coupon_lines'] as $coupon_line ) {
					$coupon_code = '';
					if ( is_array( $coupon_line ) && isset( $coupon_line['code'] ) ) {
						$coupon_code = sanitize_text_field( (string) $coupon_line['code'] );
					} elseif ( is_string( $coupon_line ) ) {
						$coupon_code = sanitize_text_field( $coupon_line );
					}

					if ( '' === $coupon_code ) {
						continue;
					}

					$coupon_result = $order->apply_coupon( $coupon_code );
					if ( false === $coupon_result || is_wp_error( $coupon_result ) ) {
						throw new Exception(
							sprintf(
								/* translators: %s: coupon code */
								esc_html__( 'Unable to apply coupon: %s', 'track-orders-for-woocommerce' ),
								$coupon_code
							)
						);
					}
				}
			}

			if ( isset( $payload['meta_data'] ) && is_array( $payload['meta_data'] ) ) {
				$this->wps_tofw_add_order_meta_data( $order, $payload['meta_data'] );
			}

			if ( isset( $payload['tracking'] ) && is_array( $payload['tracking'] ) ) {
				$tracking_map = array(
					'service'                 => 'wps_tofw_selected_shipping_service',
					'tracking_number'         => 'wps_tofw_package_tracking_number',
					'estimated_delivery_date' => 'wps_tofw_estimated_delivery_date',
					'estimated_delivery_time' => 'wps_tofw_estimated_delivery_time',
				);

				foreach ( $tracking_map as $tracking_key => $tracking_meta_key ) {
					if ( isset( $payload['tracking'][ $tracking_key ] ) ) {
						$order->update_meta_data(
							$tracking_meta_key,
							sanitize_text_field( (string) $payload['tracking'][ $tracking_key ] )
						);
					}
				}
			}

			$order->calculate_totals( true );
			$order->save();

			if ( isset( $payload['set_paid'] ) && $this->wps_tofw_sanitize_bool( $payload['set_paid'] ) ) {
				$order->payment_complete();
				$order->save();
			}
		} catch ( Exception $exception ) {
			if ( $order instanceof WC_Order && $order->get_id() > 0 ) {
				$order->delete( true );
			}

			return new WP_Error(
				'tofw_order_create_failed',
				$exception->getMessage(),
				array( 'status' => 400 )
			);
		}

		$created_order = wc_get_order( $order->get_id() );
		$response_data = array(
			'success' => true,
			'message' => esc_html__( 'Order created successfully.', 'track-orders-for-woocommerce' ),
			'order'   => $this->wps_tofw_prepare_order_response( $created_order, true ),
		);

		return new WP_REST_Response( $response_data, 201 );
	}

	/**
	 * Verify the current user capability.
	 *
	 * @param array $capabilities Capabilities list.
	 * @return bool|WP_Error
	 */
	private function wps_tofw_check_permissions( $capabilities ) {
		foreach ( $capabilities as $capability ) {
			if ( current_user_can( $capability ) ) {
				return true;
			}
		}

		return new WP_Error(
			'tofw_rest_forbidden',
			esc_html__( 'You are not allowed to access this endpoint.', 'track-orders-for-woocommerce' ),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * Prepare a normalized response for an order.
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $include_items Include line items.
	 * @return array
	 */
	private function wps_tofw_prepare_order_response( $order, $include_items = false ) {
		$date_created  = $order->get_date_created();
		$date_modified = $order->get_date_modified();
		$status        = $order->get_status();

		$order_data = array(
			'id'                   => $order->get_id(),
			'number'               => $order->get_order_number(),
			'parent_id'            => $order->get_parent_id(),
			'status'               => $status,
			'status_label'         => $this->wps_tofw_get_status_label( $status ),
			'currency'             => $order->get_currency(),
			'total'                => (string) $order->get_total(),
			'subtotal'             => (string) $order->get_subtotal(),
			'total_tax'            => (string) $order->get_total_tax(),
			'shipping_total'       => (string) $order->get_shipping_total(),
			'discount_total'       => (string) $order->get_discount_total(),
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => $order->get_payment_method_title(),
			'customer_id'          => $order->get_customer_id(),
			'customer_note'        => $order->get_customer_note(),
			'date_created'         => $date_created ? $date_created->date( 'c' ) : null,
			'date_modified'        => $date_modified ? $date_modified->date( 'c' ) : null,
			'billing'              => $order->get_address( 'billing' ),
			'shipping'             => $order->get_address( 'shipping' ),
			'tracking'             => $this->wps_tofw_get_tracking_meta( $order ),
		);

		if ( $include_items ) {
			$line_items     = array();
			$shipping_lines = array();
			$fee_lines      = array();
			$coupon_lines   = array();

			foreach ( $order->get_items( 'line_item' ) as $line_item ) {
				$line_items[] = array(
					'id'               => $line_item->get_id(),
					'product_id'       => $line_item->get_product_id(),
					'variation_id'     => $line_item->get_variation_id(),
					'name'             => $line_item->get_name(),
					'quantity'         => $line_item->get_quantity(),
					'subtotal'         => (string) $line_item->get_subtotal(),
					'total'            => (string) $line_item->get_total(),
					'total_tax'        => (string) $line_item->get_total_tax(),
					'line_item_status' => wc_get_order_item_meta( $line_item->get_id(), '_line_item_status', true ),
				);
			}

			foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
				$shipping_lines[] = array(
					'id'           => $shipping_item->get_id(),
					'method_id'    => $shipping_item->get_method_id(),
					'method_title' => $shipping_item->get_method_title(),
					'total'        => (string) $shipping_item->get_total(),
					'total_tax'    => (string) $shipping_item->get_total_tax(),
				);
			}

			foreach ( $order->get_items( 'fee' ) as $fee_item ) {
				$fee_lines[] = array(
					'id'         => $fee_item->get_id(),
					'name'       => $fee_item->get_name(),
					'total'      => (string) $fee_item->get_total(),
					'tax_status' => $fee_item->get_tax_status(),
					'tax_class'  => $fee_item->get_tax_class(),
				);
			}

			foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
				$coupon_lines[] = array(
					'id'    => $coupon_item->get_id(),
					'code'  => $coupon_item->get_code(),
					'total' => (string) $coupon_item->get_discount(),
				);
			}

			$order_data['line_items'] = $line_items;
			$order_data['shipping_lines'] = $shipping_lines;
			$order_data['fee_lines'] = $fee_lines;
			$order_data['coupon_lines'] = $coupon_lines;
		}

		return $order_data;
	}

	/**
	 * Get custom tracking metadata from order.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	private function wps_tofw_get_tracking_meta( $order ) {
		return array(
			'service'                 => $order->get_meta( 'wps_tofw_selected_shipping_service', true ),
			'tracking_number'         => $order->get_meta( 'wps_tofw_package_tracking_number', true ),
			'estimated_delivery_date' => $order->get_meta( 'wps_tofw_estimated_delivery_date', true ),
			'estimated_delivery_time' => $order->get_meta( 'wps_tofw_estimated_delivery_time', true ),
			'tracking_id_sent'        => $order->get_meta( 'wps_tofw_tracking_id_sent', true ),
			'child_order_ids'         => (array) $order->get_meta( '_wps_child_order_ids', true ),
			'is_child_order'          => $order->get_meta( '_wps_is_child_order', true ),
			'parent_order_id'         => $order->get_meta( '_wps_parent_order_id', true ),
			'parent_item_id'          => $order->get_meta( '_wps_parent_item_id', true ),
		);
	}

	/**
	 * Get order status label from order status slug.
	 *
	 * @param string $status Status slug.
	 * @return string
	 */
	private function wps_tofw_get_status_label( $status ) {
		$status = sanitize_key( (string) $status );
		$key    = 'wc-' . $status;
		$labels = wc_get_order_statuses();
		return isset( $labels[ $key ] ) ? wp_strip_all_tags( $labels[ $key ] ) : $status;
	}

	/**
	 * Sanitize and validate statuses from query parameter.
	 *
	 * @param mixed $status_param status parameter.
	 * @return array
	 */
	private function wps_tofw_sanitize_statuses_param( $status_param ) {
		if ( empty( $status_param ) ) {
			return array();
		}

		$input_statuses = is_array( $status_param ) ? $status_param : explode( ',', (string) $status_param );
		$valid_statuses = array();
		$all_statuses   = array_keys( wc_get_order_statuses() );

		foreach ( $input_statuses as $status ) {
			$status = sanitize_key( trim( (string) $status ) );
			if ( '' === $status ) {
				continue;
			}
			if ( 0 === strpos( $status, 'wc-' ) ) {
				$status = substr( $status, 3 );
			}
			if ( in_array( 'wc-' . $status, $all_statuses, true ) ) {
				$valid_statuses[] = $status;
			}
		}

		return array_values( array_unique( $valid_statuses ) );
	}

	/**
	 * Normalize a status for order creation.
	 *
	 * @param string $status Status slug.
	 * @return string|WP_Error
	 */
	private function wps_tofw_normalize_order_status( $status ) {
		$status = sanitize_key( (string) $status );
		if ( 0 === strpos( $status, 'wc-' ) ) {
			$status = substr( $status, 3 );
		}

		$all_statuses = array_keys( wc_get_order_statuses() );
		if ( ! in_array( 'wc-' . $status, $all_statuses, true ) ) {
			return new WP_Error(
				'tofw_invalid_order_status',
				esc_html__( 'Invalid status provided for order creation.', 'track-orders-for-woocommerce' ),
				array( 'status' => 400 )
			);
		}

		return $status;
	}

	/**
	 * Normalize billing/shipping address payload.
	 *
	 * @param array $address Address payload.
	 * @return array
	 */
	private function wps_tofw_normalize_address( $address ) {
		$allowed_fields = array(
			'first_name',
			'last_name',
			'company',
			'email',
			'phone',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
		);

		$normalized_address = array();
		foreach ( $allowed_fields as $allowed_field ) {
			if ( isset( $address[ $allowed_field ] ) ) {
				$normalized_address[ $allowed_field ] = sanitize_text_field( (string) $address[ $allowed_field ] );
			}
		}

		return $normalized_address;
	}

	/**
	 * Attach order meta_data payload.
	 *
	 * @param WC_Order $order Order object.
	 * @param array    $meta_data Meta data payload.
	 * @return void
	 */
	private function wps_tofw_add_order_meta_data( $order, $meta_data ) {
		if ( ! is_array( $meta_data ) || empty( $meta_data ) ) {
			return;
		}

		if ( $this->wps_tofw_is_associative_array( $meta_data ) ) {
			foreach ( $meta_data as $meta_key => $meta_value ) {
				$meta_key = sanitize_text_field( (string) $meta_key );
				if ( '' === $meta_key ) {
					continue;
				}
				$order->update_meta_data( $meta_key, $this->wps_tofw_sanitize_meta_value( $meta_value ) );
			}
			return;
		}

		foreach ( $meta_data as $meta_item ) {
			if ( ! is_array( $meta_item ) || ! isset( $meta_item['key'] ) ) {
				continue;
			}
			$meta_key = sanitize_text_field( (string) $meta_item['key'] );
			if ( '' === $meta_key ) {
				continue;
			}

			$meta_value = isset( $meta_item['value'] ) ? $meta_item['value'] : '';
			$order->update_meta_data( $meta_key, $this->wps_tofw_sanitize_meta_value( $meta_value ) );
		}
	}

	/**
	 * Attach order item meta_data payload.
	 *
	 * @param WC_Order_Item $order_item Order item object.
	 * @param array         $meta_data Meta data payload.
	 * @return void
	 */
	private function wps_tofw_add_item_meta_data( $order_item, $meta_data ) {
		if ( ! is_array( $meta_data ) || empty( $meta_data ) ) {
			return;
		}

		if ( $this->wps_tofw_is_associative_array( $meta_data ) ) {
			foreach ( $meta_data as $meta_key => $meta_value ) {
				$meta_key = sanitize_text_field( (string) $meta_key );
				if ( '' === $meta_key ) {
					continue;
				}
				$order_item->add_meta_data( $meta_key, $this->wps_tofw_sanitize_meta_value( $meta_value ), true );
			}
			return;
		}

		foreach ( $meta_data as $meta_item ) {
			if ( ! is_array( $meta_item ) || ! isset( $meta_item['key'] ) ) {
				continue;
			}
			$meta_key = sanitize_text_field( (string) $meta_item['key'] );
			if ( '' === $meta_key ) {
				continue;
			}

			$meta_value = isset( $meta_item['value'] ) ? $meta_item['value'] : '';
			$order_item->add_meta_data( $meta_key, $this->wps_tofw_sanitize_meta_value( $meta_value ), true );
		}
	}

	/**
	 * Filter response data by requested fields.
	 *
	 * @param array  $order_data Full order data.
	 * @param string $fields_csv Comma separated fields.
	 * @return array
	 */
	private function wps_tofw_apply_requested_fields( $order_data, $fields_csv ) {
		if ( '' === $fields_csv ) {
			return $order_data;
		}

		$fields = array_filter( array_map( 'sanitize_key', explode( ',', $fields_csv ) ) );
		if ( empty( $fields ) ) {
			return $order_data;
		}

		$filtered_data = array();
		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $order_data ) ) {
				$filtered_data[ $field ] = $order_data[ $field ];
			}
		}

		return ! empty( $filtered_data ) ? $filtered_data : $order_data;
	}

	/**
	 * Check whether an array is associative.
	 *
	 * @param array $value Array value.
	 * @return bool
	 */
	private function wps_tofw_is_associative_array( $value ) {
		if ( ! is_array( $value ) || empty( $value ) ) {
			return false;
		}
		return array_keys( $value ) !== range( 0, count( $value ) - 1 );
	}

	/**
	 * Sanitize meta value.
	 *
	 * @param mixed $value Meta value.
	 * @return mixed
	 */
	private function wps_tofw_sanitize_meta_value( $value ) {
		if ( is_array( $value ) ) {
			return map_deep( $value, 'sanitize_text_field' );
		}
		if ( is_scalar( $value ) ) {
			return sanitize_text_field( (string) $value );
		}
		return '';
	}

	/**
	 * Boolean sanitizer wrapper for REST args.
	 *
	 * @param mixed $value Value to sanitize.
	 * @return bool
	 */
	public function wps_tofw_sanitize_bool( $value ) {
		return rest_sanitize_boolean( $value );
	}
}
