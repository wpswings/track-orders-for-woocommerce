<?php
/**
 * Reusable "add tracking to order" service.
 *
 * Single entry point used by the CSV bulk import and the REST API to attach
 * shipment tracking (carrier, tracking number, ship date, optional partial
 * items and optional order status change) to a WooCommerce order.
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
 * Tracking service.
 *
 * Storage (HPOS compatible, always through the WC_Order CRUD API):
 * - `wps_tofw_tracking_shipments` : list of shipment arrays (one per tracking number).
 * - `wps_tofw_selected_shipping_service` / `wps_tofw_package_tracking_number` : kept in sync
 *   with the most recent shipment so the existing metabox, templates and integrations keep working.
 * - `wps_tofw_tracking_id_sent` : set to 1 once a tracking e-mail was sent (same flag as the manual flow).
 *
 * @since 1.3.0
 */
class Track_Orders_For_Woocommerce_Tracking_Service {

	/**
	 * Order meta key holding the list of shipments.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const META_SHIPMENTS = 'wps_tofw_tracking_shipments';

	/**
	 * Separator between item entries in the CSV "items" column.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const ITEMS_SEPARATOR = '|';

	/**
	 * Get the known shipping services (key => label).
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public static function get_known_carriers() {
		$services = array();
		if ( 'on' === get_option( 'wps_tofw_enable_track_order_using_api', 'no' ) ) {
			$services['fedex'] = 'FedEx';
		}

		/**
		 * Add different shipping services (same filter as the order metabox).
		 *
		 * @since 1.0.0
		 */
		$services = apply_filters( 'wps_tofw_add_diffrent_shipping_services', $services );

		return is_array( $services ) ? $services : array();
	}

	/**
	 * Normalize a carrier input to a known service key when possible.
	 *
	 * @since 1.3.0
	 * @param string $carrier Carrier key or label.
	 * @return array{key:string,label:string}
	 */
	public static function normalize_carrier( $carrier ) {
		$carrier = trim( sanitize_text_field( (string) $carrier ) );
		$needle  = strtolower( $carrier );

		foreach ( self::get_known_carriers() as $key => $label ) {
			if ( strtolower( (string) $key ) === $needle || strtolower( wp_strip_all_tags( (string) $label ) ) === $needle ) {
				return array(
					'key'   => (string) $key,
					'label' => wp_strip_all_tags( (string) $label ),
				);
			}
		}

		return array(
			'key'   => $carrier,
			'label' => $carrier,
		);
	}

	/**
	 * Normalize a ship date to Y-m-d. Empty input defaults to today (site timezone).
	 *
	 * Accepted formats: Y-m-d, Y/m/d, Y-m-d H:i, Y-m-d H:i:s.
	 *
	 * @since 1.3.0
	 * @param string $date Raw date.
	 * @return string|false Normalized date or false when invalid.
	 */
	public static function normalize_date( $date ) {
		$date = trim( (string) $date );
		if ( '' === $date ) {
			return function_exists( 'current_time' ) ? current_time( 'Y-m-d' ) : gmdate( 'Y-m-d' );
		}

		foreach ( array( 'Y-m-d', 'Y/m/d', 'Y-m-d H:i', 'Y-m-d H:i:s' ) as $format ) {
			$parsed = DateTime::createFromFormat( '!' . $format, $date );
			$errors = DateTime::getLastErrors();
			if ( $parsed && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $parsed->format( $format ) === $date ) {
				return $parsed->format( 'Y-m-d' );
			}
		}

		return false;
	}

	/**
	 * Normalize an order status to its slug without the `wc-` prefix.
	 *
	 * @since 1.3.0
	 * @param string $status Raw status (slug, `wc-` slug or label).
	 * @return string|false Empty string when no status was given, false when invalid.
	 */
	public static function normalize_status( $status ) {
		$status = trim( (string) $status );
		if ( '' === $status ) {
			return '';
		}

		$needle = strtolower( $status );
		foreach ( wc_get_order_statuses() as $slug => $label ) {
			$short = 'wc-' === substr( $slug, 0, 3 ) ? substr( $slug, 3 ) : $slug;
			if ( $needle === $slug || $needle === $short || strtolower( $label ) === $needle ) {
				return $short;
			}
		}

		return false;
	}

	/**
	 * Parse the "items" definition used for partial shipments.
	 *
	 * Format: entries separated by "|", each entry "<ref>:<qty>" or "<ref>" (= all remaining qty).
	 * <ref> is a product/variation SKU, or "#<order item id>" to target a line item directly.
	 * Example: "TSHIRT-RED-M:2|MUG-01:1|#57:1".
	 *
	 * Also accepts an array (REST) of { sku|item_id, qty }.
	 *
	 * @since 1.3.0
	 * @param string|array $items Raw items.
	 * @return array|WP_Error List of array( 'type' => 'sku'|'item_id', 'ref' => string|int, 'qty' => int|null ).
	 */
	public static function parse_items( $items ) {
		$parsed = array();

		if ( is_array( $items ) ) {
			foreach ( $items as $entry ) {
				if ( ! is_array( $entry ) ) {
					return new WP_Error( 'wps_tofw_invalid_items', __( 'Each item must be an object with "sku" or "item_id" and "qty".', 'track-orders-for-woocommerce' ) );
				}
				$qty = isset( $entry['qty'] ) && '' !== $entry['qty'] ? $entry['qty'] : ( isset( $entry['quantity'] ) ? $entry['quantity'] : null );
				if ( null !== $qty && ( ! is_numeric( $qty ) || (int) $qty != $qty || (int) $qty < 1 ) ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
					return new WP_Error( 'wps_tofw_invalid_items', __( 'Item quantity must be a positive whole number.', 'track-orders-for-woocommerce' ) );
				}
				if ( ! empty( $entry['item_id'] ) ) {
					$parsed[] = array(
						'type' => 'item_id',
						'ref'  => absint( $entry['item_id'] ),
						'qty'  => null === $qty ? null : (int) $qty,
					);
				} elseif ( isset( $entry['sku'] ) && '' !== trim( (string) $entry['sku'] ) ) {
					$parsed[] = array(
						'type' => 'sku',
						'ref'  => sanitize_text_field( trim( (string) $entry['sku'] ) ),
						'qty'  => null === $qty ? null : (int) $qty,
					);
				} else {
					return new WP_Error( 'wps_tofw_invalid_items', __( 'Each item needs a "sku" or an "item_id".', 'track-orders-for-woocommerce' ) );
				}
			}
			return $parsed;
		}

		$items = trim( (string) $items );
		if ( '' === $items ) {
			return array();
		}

		foreach ( explode( self::ITEMS_SEPARATOR, $items ) as $chunk ) {
			$chunk = trim( $chunk );
			if ( '' === $chunk ) {
				continue;
			}

			$qty = null;
			$pos = strrpos( $chunk, ':' );
			if ( false !== $pos ) {
				$raw_qty = trim( substr( $chunk, $pos + 1 ) );
				$chunk   = trim( substr( $chunk, 0, $pos ) );
				if ( ! ctype_digit( $raw_qty ) || (int) $raw_qty < 1 ) {
					return new WP_Error(
						'wps_tofw_invalid_items',
						/* translators: %s: items entry */
						sprintf( __( 'Invalid quantity in items entry "%s" (use sku:qty).', 'track-orders-for-woocommerce' ), $chunk . ':' . $raw_qty )
					);
				}
				$qty = (int) $raw_qty;
			}

			if ( '' === $chunk ) {
				return new WP_Error( 'wps_tofw_invalid_items', __( 'Items entry is missing a SKU.', 'track-orders-for-woocommerce' ) );
			}

			if ( '#' === $chunk[0] ) {
				$item_id = substr( $chunk, 1 );
				if ( ! ctype_digit( $item_id ) ) {
					return new WP_Error(
						'wps_tofw_invalid_items',
						/* translators: %s: items entry */
						sprintf( __( 'Invalid order item id "%s".', 'track-orders-for-woocommerce' ), $chunk )
					);
				}
				$parsed[] = array(
					'type' => 'item_id',
					'ref'  => (int) $item_id,
					'qty'  => $qty,
				);
			} else {
				$parsed[] = array(
					'type' => 'sku',
					'ref'  => sanitize_text_field( $chunk ),
					'qty'  => $qty,
				);
			}
		}

		return $parsed;
	}

	/**
	 * Get the shipments stored on an order.
	 *
	 * When the order only has tracking saved through the order edit screen (legacy single
	 * tracking number), it is returned as one shipment with source "manual".
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return array
	 */
	public static function get_shipments( $order ) {
		$shipments = $order->get_meta( self::META_SHIPMENTS, true );
		$shipments = is_array( $shipments ) ? array_values( $shipments ) : array();

		$legacy = self::get_legacy_shipment( $order, $shipments, 'manual' );
		if ( $legacy ) {
			array_unshift( $shipments, $legacy );
		}

		return $shipments;
	}

	/**
	 * The single tracking number saved through the order edit screen (legacy keys), as a
	 * shipment entry, when it is not already part of the shipments list.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order     Order.
	 * @param array    $shipments Stored shipments.
	 * @param string   $id        Shipment id to use.
	 * @return array|null
	 */
	private static function get_legacy_shipment( $order, $shipments, $id ) {
		$legacy_number = trim( (string) $order->get_meta( 'wps_tofw_package_tracking_number', true ) );
		if ( '' === $legacy_number || self::tracking_number_exists( $shipments, $legacy_number ) ) {
			return null;
		}

		$legacy_carrier = self::normalize_carrier( (string) $order->get_meta( 'wps_tofw_selected_shipping_service', true ) );
		return array(
			'id'              => $id,
			'carrier'         => $legacy_carrier['key'],
			'carrier_label'   => $legacy_carrier['label'],
			'tracking_number' => $legacy_number,
			'ship_date'       => '',
			'items'           => array(),
			'source'          => 'manual',
			'date_added'      => '',
			'added_by'        => 0,
		);
	}

	/**
	 * Check if a tracking number is already present in a shipments list.
	 *
	 * @since 1.3.0
	 * @param array  $shipments Shipments.
	 * @param string $tracking_number Tracking number.
	 * @return bool
	 */
	private static function tracking_number_exists( $shipments, $tracking_number ) {
		foreach ( $shipments as $shipment ) {
			if ( isset( $shipment['tracking_number'] ) && 0 === strcasecmp( (string) $shipment['tracking_number'], (string) $tracking_number ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Quantities already shipped per order item id.
	 *
	 * @since 1.3.0
	 * @param array $shipments Shipments.
	 * @return array item_id => qty.
	 */
	public static function get_shipped_quantities( $shipments ) {
		$shipped = array();
		foreach ( $shipments as $shipment ) {
			if ( empty( $shipment['items'] ) || ! is_array( $shipment['items'] ) ) {
				continue;
			}
			foreach ( $shipment['items'] as $item ) {
				$item_id             = (int) $item['item_id'];
				$shipped[ $item_id ] = ( isset( $shipped[ $item_id ] ) ? $shipped[ $item_id ] : 0 ) + (int) $item['qty'];
			}
		}
		return $shipped;
	}

	/**
	 * Validate and normalize tracking input for an order without saving anything.
	 *
	 * Used for the CSV dry-run preview as well as before saving.
	 *
	 * @since 1.3.0
	 * @param WC_Order|int $order Order or order id.
	 * @param array        $args {
	 *     @type string       $carrier         Carrier key or label (required).
	 *     @type string       $tracking_number Tracking number (required).
	 *     @type string       $ship_date       Y-m-d, defaults to today.
	 *     @type string|array $items           Partial shipment items (see parse_items()). Empty = whole order.
	 *     @type string       $status          Optional new order status.
	 * }
	 * @param array        $pending_qty Optional item_id => qty already reserved by earlier rows of the same import.
	 * @return array|WP_Error Normalized data or error (all reasons merged in one WP_Error).
	 */
	public static function prepare( $order, $args, $pending_qty = array() ) {
		$errors = new WP_Error();

		if ( ! $order instanceof WC_Order ) {
			$order_id = is_numeric( $order ) ? absint( $order ) : 0;
			$order    = $order_id ? wc_get_order( $order_id ) : false;
		}

		if ( ! $order || ! $order instanceof WC_Order || 'shop_order_refund' === $order->get_type() ) {
			$errors->add( 'wps_tofw_invalid_order', __( 'Order not found.', 'track-orders-for-woocommerce' ), array( 'status' => 404 ) );
			return $errors;
		}

		$tracking_number = isset( $args['tracking_number'] ) ? trim( sanitize_text_field( (string) $args['tracking_number'] ) ) : '';
		$carrier_raw     = isset( $args['carrier'] ) ? (string) $args['carrier'] : '';
		$carrier         = self::normalize_carrier( $carrier_raw );

		if ( '' === $tracking_number ) {
			$errors->add( 'wps_tofw_missing_tracking_number', __( 'Tracking number is required.', 'track-orders-for-woocommerce' ) );
		} elseif ( strlen( $tracking_number ) > 100 ) {
			$errors->add( 'wps_tofw_invalid_tracking_number', __( 'Tracking number is too long (max 100 characters).', 'track-orders-for-woocommerce' ) );
		}

		if ( '' === $carrier['key'] ) {
			$errors->add( 'wps_tofw_missing_carrier', __( 'Carrier is required.', 'track-orders-for-woocommerce' ) );
		}

		$ship_date = self::normalize_date( isset( $args['ship_date'] ) ? $args['ship_date'] : '' );
		if ( false === $ship_date ) {
			$errors->add( 'wps_tofw_invalid_ship_date', __( 'Ship date must use the YYYY-MM-DD format.', 'track-orders-for-woocommerce' ) );
		}

		$status = self::normalize_status( isset( $args['status'] ) ? $args['status'] : '' );
		if ( false === $status ) {
			$errors->add(
				'wps_tofw_invalid_status',
				/* translators: %s: order status */
				sprintf( __( 'Unknown order status "%s".', 'track-orders-for-woocommerce' ), sanitize_text_field( (string) $args['status'] ) )
			);
		}

		$shipments = self::get_shipments( $order );
		if ( '' !== $tracking_number && self::tracking_number_exists( $shipments, $tracking_number ) ) {
			$errors->add( 'wps_tofw_duplicate_tracking', __( 'This tracking number is already added to the order.', 'track-orders-for-woocommerce' ), array( 'status' => 409 ) );
		}

		$items = self::resolve_items( $order, isset( $args['items'] ) ? $args['items'] : '', $shipments, $pending_qty );
		if ( is_wp_error( $items ) ) {
			$errors->merge_from( $items );
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return array(
			'order'           => $order,
			'carrier'         => $carrier['key'],
			'carrier_label'   => $carrier['label'],
			'tracking_number' => $tracking_number,
			'ship_date'       => $ship_date,
			'items'           => $items,
			'status'          => $status,
		);
	}

	/**
	 * Resolve parsed item references against the order line items and check remaining quantities.
	 *
	 * @since 1.3.0
	 * @param WC_Order     $order Order.
	 * @param string|array $raw_items Raw items.
	 * @param array        $shipments Existing shipments.
	 * @param array        $pending_qty item_id => qty reserved by earlier rows.
	 * @return array|WP_Error
	 */
	private static function resolve_items( $order, $raw_items, $shipments, $pending_qty ) {
		$parsed = self::parse_items( $raw_items );
		if ( is_wp_error( $parsed ) || empty( $parsed ) ) {
			return $parsed;
		}

		$shipped = self::get_shipped_quantities( $shipments );
		foreach ( (array) $pending_qty as $item_id => $qty ) {
			$shipped[ (int) $item_id ] = ( isset( $shipped[ (int) $item_id ] ) ? $shipped[ (int) $item_id ] : 0 ) + (int) $qty;
		}

		$line_items = $order->get_items( 'line_item' );
		$resolved   = array();
		$errors     = new WP_Error();

		foreach ( $parsed as $entry ) {
			$match = null;
			foreach ( $line_items as $item_id => $item ) {
				if ( 'item_id' === $entry['type'] ) {
					if ( (int) $item_id === (int) $entry['ref'] ) {
						$match = $item;
						break;
					}
					continue;
				}
				$product = $item->get_product();
				if ( $product && '' !== (string) $product->get_sku() && 0 === strcasecmp( (string) $product->get_sku(), (string) $entry['ref'] ) ) {
					$remaining = (int) $item->get_quantity() - ( isset( $shipped[ (int) $item_id ] ) ? $shipped[ (int) $item_id ] : 0 ) - ( isset( $resolved[ (int) $item_id ] ) ? $resolved[ (int) $item_id ]['qty'] : 0 );
					$match     = $item;
					if ( $remaining > 0 ) {
						break; // Prefer a line with remaining quantity when the same SKU appears on several lines.
					}
				}
			}

			if ( ! $match ) {
				$errors->add(
					'wps_tofw_item_not_found',
					/* translators: %s: SKU or item id */
					sprintf( __( 'Item "%s" is not part of this order.', 'track-orders-for-woocommerce' ), ( 'item_id' === $entry['type'] ? '#' : '' ) . $entry['ref'] )
				);
				continue;
			}

			$item_id   = (int) $match->get_id();
			$already   = ( isset( $shipped[ $item_id ] ) ? $shipped[ $item_id ] : 0 ) + ( isset( $resolved[ $item_id ] ) ? $resolved[ $item_id ]['qty'] : 0 );
			$remaining = (int) $match->get_quantity() - $already;
			$qty       = null === $entry['qty'] ? $remaining : (int) $entry['qty'];

			if ( $remaining <= 0 ) {
				$errors->add(
					'wps_tofw_item_already_shipped',
					/* translators: %s: item name */
					sprintf( __( 'Item "%s" is already fully shipped.', 'track-orders-for-woocommerce' ), $match->get_name() )
				);
				continue;
			}

			if ( $qty > $remaining ) {
				$errors->add(
					'wps_tofw_item_qty_exceeded',
					/* translators: 1: item name, 2: requested qty, 3: remaining qty */
					sprintf( __( 'Item "%1$s": quantity %2$d exceeds the %3$d unshipped.', 'track-orders-for-woocommerce' ), $match->get_name(), $qty, $remaining )
				);
				continue;
			}

			$product = $match->get_product();
			if ( isset( $resolved[ $item_id ] ) ) {
				$resolved[ $item_id ]['qty'] += $qty;
			} else {
				$resolved[ $item_id ] = array(
					'item_id' => $item_id,
					'sku'     => $product ? (string) $product->get_sku() : '',
					'name'    => $match->get_name(),
					'qty'     => $qty,
				);
			}
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return array_values( $resolved );
	}

	/**
	 * Add tracking to an order.
	 *
	 * @since 1.3.0
	 * @param WC_Order|int $order Order or id.
	 * @param array        $args See prepare(). Extra keys:
	 *                           - `source`          string 'csv'|'rest'|'manual' (default 'manual').
	 *                           - `notify_customer` null (send once per order, like the order edit screen),
	 *                             true (always send) or false (never send).
	 * @return array|WP_Error The stored shipment or error.
	 */
	public static function add_tracking( $order, $args ) {
		$data = self::prepare( $order, $args );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$order  = $data['order'];
		$source = isset( $args['source'] ) ? sanitize_key( $args['source'] ) : 'manual';

		$shipment = array(
			'id'              => 'shp_' . strtolower( wp_generate_password( 10, false, false ) ),
			'carrier'         => $data['carrier'],
			'carrier_label'   => $data['carrier_label'],
			'tracking_number' => $data['tracking_number'],
			'ship_date'       => $data['ship_date'],
			'items'           => $data['items'],
			'source'          => $source,
			'date_added'      => gmdate( 'c' ),
			'added_by'        => get_current_user_id(),
		);

		/**
		 * Filter a shipment before it is stored on the order.
		 *
		 * @since 1.3.0
		 * @param array    $shipment Shipment data.
		 * @param WC_Order $order    Order.
		 * @param array    $args     Raw arguments.
		 */
		$shipment = apply_filters( 'wps_tofw_tracking_shipment_data', $shipment, $order, $args );

		$stored = $order->get_meta( self::META_SHIPMENTS, true );
		$stored = is_array( $stored ) ? array_values( $stored ) : array();

		// Keep the tracking number saved from the order edit screen before the legacy keys are overwritten.
		$legacy = self::get_legacy_shipment( $order, $stored, 'shp_' . strtolower( wp_generate_password( 10, false, false ) ) );
		if ( $legacy ) {
			array_unshift( $stored, $legacy );
		}

		$stored[] = $shipment;

		$order->update_meta_data( self::META_SHIPMENTS, $stored );
		// Keep the legacy single-tracking keys in sync with the latest shipment.
		$order->update_meta_data( 'wps_tofw_selected_shipping_service', $shipment['carrier'] );
		$order->update_meta_data( 'wps_tofw_package_tracking_number', $shipment['tracking_number'] );

		$order->add_order_note( self::get_order_note( $shipment ) );
		$order->save();

		$notify = isset( $args['notify_customer'] ) ? $args['notify_customer'] : null;
		if ( self::should_send_email( $order, $notify ) && self::send_tracking_email( $order, $shipment ) ) {
			$order->update_meta_data( 'wps_tofw_tracking_id_sent', 1 );
			$order->save();
		}

		// Status change last so WooCommerce / plugin status e-mails fire per their own settings.
		if ( '' !== $data['status'] && ! $order->has_status( $data['status'] ) ) {
			$order->update_status(
				$data['status'],
				/* translators: %s: tracking number */
				sprintf( __( 'Status updated while adding tracking %s.', 'track-orders-for-woocommerce' ), $shipment['tracking_number'] ),
				true
			);
		}

		/**
		 * Fires after tracking was added to an order.
		 *
		 * @since 1.3.0
		 * @param WC_Order $order    Order.
		 * @param array    $shipment Shipment data.
		 * @param string   $source   Source: csv, rest, manual.
		 */
		do_action( 'wps_tofw_tracking_added', $order, $shipment, $source );

		/**
		 * Fires after a tracking number has been saved on an order (same action and signature
		 * as the manual/integration flow, so listeners also receive CSV and REST additions).
		 *
		 * @since 1.3.0
		 * @param WC_Order $order           Order.
		 * @param string   $service         Shipping service key.
		 * @param string   $tracking_number Tracking number.
		 * @param array    $args            Context (source, shipment).
		 */
		do_action(
			'wps_tofw_tracking_saved',
			$order,
			$shipment['carrier'],
			$shipment['tracking_number'],
			array(
				'source'   => $source,
				'shipment' => $shipment,
			)
		);

		return $shipment;
	}

	/**
	 * Build the private order note for a shipment.
	 *
	 * @since 1.3.0
	 * @param array $shipment Shipment.
	 * @return string
	 */
	private static function get_order_note( $shipment ) {
		$note = sprintf(
			/* translators: 1: carrier, 2: tracking number, 3: ship date */
			__( 'Tracking added: %1$s %2$s (shipped %3$s).', 'track-orders-for-woocommerce' ),
			$shipment['carrier_label'],
			$shipment['tracking_number'],
			$shipment['ship_date']
		);

		if ( ! empty( $shipment['items'] ) ) {
			$parts = array();
			foreach ( $shipment['items'] as $item ) {
				$parts[] = $item['name'] . ' x ' . $item['qty'];
			}
			/* translators: %s: list of items */
			$note .= ' ' . sprintf( __( 'Items: %s.', 'track-orders-for-woocommerce' ), implode( ', ', $parts ) );
		}

		return $note;
	}

	/**
	 * Whether the tracking e-mail should be sent.
	 *
	 * Same rule as the manual "Shipping Services" order metabox: the tracking e-mail is sent
	 * once per order (flag `wps_tofw_tracking_id_sent`). Callers can pass false (CSV column /
	 * REST `notify_customer`) to skip it, or true to send it even when it was already sent.
	 *
	 * @since 1.3.0
	 * @param WC_Order  $order Order.
	 * @param bool|null $notify Explicit override.
	 * @return bool
	 */
	public static function should_send_email( $order, $notify = null ) {
		if ( null === $notify ) {
			$send = 1 !== (int) $order->get_meta( 'wps_tofw_tracking_id_sent', true );
		} else {
			$send = (bool) $notify;
		}

		/**
		 * Filter whether the tracking added e-mail is sent to the customer.
		 *
		 * @since 1.3.0
		 * @param bool      $send   Send or not.
		 * @param WC_Order  $order  Order.
		 * @param bool|null $notify Explicit override passed by the caller.
		 */
		return (bool) apply_filters( 'wps_tofw_send_tracking_added_email', $send, $order, $notify );
	}

	/**
	 * Send the tracking e-mail to the customer.
	 *
	 * Uses the same subject and markup as the e-mail sent when tracking is saved from the
	 * order edit screen (Track_Orders_For_Woocommerce_Admin::wps_tofw_save_shipping_services_meta()).
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @param array    $shipment Shipment.
	 * @return bool
	 */
	public static function send_tracking_email( $order, $shipment ) {
		$to = $order->get_billing_email();
		if ( ! $to || ! function_exists( 'WC' ) ) {
			return false;
		}

		$subject = __( 'Your Order Status for Order #', 'track-orders-for-woocommerce' ) . $order->get_id();

		/**
		 * Filter the tracking e-mail body (full HTML document).
		 *
		 * @since 1.3.0
		 * @param string   $body     Body HTML.
		 * @param WC_Order $order    Order.
		 * @param array    $shipment Shipment.
		 */
		$message = apply_filters( 'wps_tofw_tracking_added_email_body', self::get_tracking_email_html( $order, $shipment ), $order, $shipment );

		return (bool) WC()->mailer()->send( $to, $subject, $message, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}

	/**
	 * Tracking e-mail markup of the order edit screen flow, for one shipment.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @param array    $shipment Shipment.
	 * @return string
	 */
	public static function get_tracking_email_html( $order, $shipment ) {
		$order_id            = $order->get_id();
		$wps_tracking_url    = (string) $order->get_meta( 'wps_tofw_enhanced_order_company', true );
		$wps_tracking_number = (string) $order->get_meta( 'wps_tofw_enhanced_tracking_no', true );
		$mail_header         = __( 'Current Order Status is ', 'track-orders-for-woocommerce' ) . wc_get_order_status_name( $order->get_status() );
		$mail_footer         = '';

		$message = '<html>
				<body>
					<style>
						body {
							box-shadow: 2px 2px 10px #ccc;
							color: #767676;
							font-family: Arial,sans-serif;
							margin: 80px auto;
							max-width: 700px;
							padding-bottom: 30px;
							width: 100%;
						}

						h2 {
							font-size: 30px;
							margin-top: 0;
							color: #fff;
							padding: 40px;
							background-color: #557da1;
						}

						h4 {
							color: #557da1;
							font-size: 20px;
							margin-bottom: 10px;
						}

						.content {
							padding: 0 40px;
						}

						.Customer-detail ul li p {
							margin: 0;
						}

						.details .Shipping-detail {
							width: 40%;
							float: right;
						}

						.details .Billing-detail {
							width: 60%;
							float: left;
						}

						.details .Shipping-detail ul li,.details .Billing-detail ul li {
							list-style-type: none;
							margin: 0;
						}

						.details .Billing-detail ul,.details .Shipping-detail ul {
							margin: 0;
							padding: 0;
						}

						.clear {
							clear: both;
						}

						table,td,th {
							border: 2px solid #ccc;
							padding: 15px;
							text-align: left;
						}

						table {
							border-collapse: collapse;
							width: 100%;
						}

						.info {
							display: inline-block;
						}

						.bold {
							font-weight: bold;
						}

						.footer {
							margin-top: 30px;
							text-align: center;
							color: #99B1D8;
							font-size: 12px;
						}
						dl.variation dd {
							font-size: 12px;
							margin: 0;
						}
					</style>

					<div style="padding: 36px 48px; background-color:#557DA1;color: #fff; font-size: 30px; font-weight: 300; font-family:helvetica;" class="header">
						' . esc_html( $mail_header ) . '
					</div>

					<div class="content">

						<div class="Order">
							<h4>Order #' . esc_html( $order_id ) . '</h4>
							<table>
								<tbody>
									<tr>
										<th>' . esc_html__( 'Order Id', 'track-orders-for-woocommerce' ) . '</th>
										<th>' . esc_html__( 'Tracking Number ', 'track-orders-for-woocommerce' ) . '</th>
									</tr>';
		$message .= '<tr>
									<td>' . esc_html( $order_id ) . '</td>
									<td>' . esc_html( $shipment['tracking_number'] ) . '</td>
								</tr>';
		$message .= '</tbody>
							</table>';
		if ( ! empty( $shipment['items'] ) ) {
			$list = array();
			foreach ( $shipment['items'] as $item ) {
				$list[] = esc_html( $item['name'] ) . ' &times; ' . absint( $item['qty'] );
			}
			$message .= '<p>' . esc_html__( 'Items in this shipment', 'track-orders-for-woocommerce' ) . ': ' . implode( ', ', $list ) . '</p>';
		}
		$message .= '<div>
								<a href="' . esc_url( $wps_tracking_url ) . '">' . esc_html( $wps_tracking_number ) . '</a>
							</div>
						</div>
					</div>
					<div style="text-align: center; padding: 10px;" class="footer">
						' . $mail_footer . '
					</div>
				</body>
				</html>';

		return $message;
	}

	/**
	 * Add tracking saved by another flow (manual metabox, shipping plugin auto import) to the
	 * shipments list, so every tracking number of the order stays visible.
	 *
	 * Hooked to `wps_tofw_tracking_saved` ( WC_Order|int $order, string $service, string $tracking_number, array $args ).
	 * Calls coming from add_tracking() carry `$args['shipment']` and are already stored.
	 *
	 * @since 1.3.0
	 * @param WC_Order|int $order           Order or id.
	 * @param string       $service         Shipping service key.
	 * @param string       $tracking_number Tracking number.
	 * @param array        $args            Context (source etc.).
	 * @return void
	 */
	public static function on_tracking_saved( $order, $service = '', $tracking_number = '', $args = array() ) {
		$args = is_array( $args ) ? $args : array();
		if ( isset( $args['shipment'] ) ) {
			return;
		}

		$order = $order instanceof WC_Order ? $order : wc_get_order( absint( $order ) );
		if ( ! $order || 'shop_order_refund' === $order->get_type() ) {
			return;
		}

		$tracking_number = trim( sanitize_text_field( (string) $tracking_number ) );
		if ( '' === $tracking_number ) {
			return;
		}
		$carrier = self::normalize_carrier( $service );

		$stored = $order->get_meta( self::META_SHIPMENTS, true );
		$stored = is_array( $stored ) ? array_values( $stored ) : array();
		foreach ( $stored as $shipment ) {
			if ( isset( $shipment['tracking_number'] ) && 0 === strcasecmp( (string) $shipment['tracking_number'], $tracking_number )
				&& 0 === strcasecmp( (string) ( isset( $shipment['carrier'] ) ? $shipment['carrier'] : '' ), $carrier['key'] ) ) {
				return;
			}
		}

		$stored[] = array(
			'id'              => 'shp_' . strtolower( wp_generate_password( 10, false, false ) ),
			'carrier'         => $carrier['key'],
			'carrier_label'   => $carrier['label'],
			'tracking_number' => $tracking_number,
			'ship_date'       => function_exists( 'current_time' ) ? current_time( 'Y-m-d' ) : gmdate( 'Y-m-d' ),
			'items'           => array(),
			'source'          => isset( $args['source'] ) ? sanitize_key( $args['source'] ) : 'manual',
			'date_added'      => gmdate( 'c' ),
			'added_by'        => get_current_user_id(),
		);

		$order->update_meta_data( self::META_SHIPMENTS, $stored );
		$order->save();
	}

	/**
	 * Register the read-only shipments meta box on the order edit screen.
	 *
	 * Hooked to `add_meta_boxes`.
	 *
	 * @since 1.3.0
	 * @param string              $screen_id     Screen / post type.
	 * @param WP_Post|WC_Order|null $post_or_order Post or order.
	 * @return void
	 */
	public static function add_shipments_meta_box( $screen_id, $post_or_order = null ) {
		$order_screen = 'shop_order';
		if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() && function_exists( 'wc_get_page_screen_id' ) ) {
			$order_screen = wc_get_page_screen_id( 'shop-order' );
		}
		if ( $screen_id !== $order_screen ) {
			return;
		}

		$order = $post_or_order instanceof WP_Post ? wc_get_order( $post_or_order->ID ) : $post_or_order;
		if ( ! $order instanceof WC_Order || empty( $order->get_meta( self::META_SHIPMENTS, true ) ) ) {
			return;
		}

		add_meta_box( 'wps_tofw_tracking_shipments', __( 'Shipments', 'track-orders-for-woocommerce' ), array( __CLASS__, 'render_shipments_meta_box' ), $order_screen, 'side', 'default' );
	}

	/**
	 * Meta box callback: list every shipment of the order.
	 *
	 * @since 1.3.0
	 * @param WP_Post|WC_Order $post_or_order Post or order.
	 * @return void
	 */
	public static function render_shipments_meta_box( $post_or_order ) {
		$order = $post_or_order instanceof WP_Post ? wc_get_order( $post_or_order->ID ) : $post_or_order;
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		echo '<ul class="wps-tofw-shipments">';
		foreach ( self::get_shipments( $order ) as $shipment ) {
			echo '<li style="border-bottom:1px solid #eee;padding:6px 0;margin:0;">';
			echo '<strong>' . esc_html( isset( $shipment['carrier_label'] ) && '' !== $shipment['carrier_label'] ? $shipment['carrier_label'] : $shipment['carrier'] ) . '</strong>: ';
			echo '<code>' . esc_html( $shipment['tracking_number'] ) . '</code>';
			if ( ! empty( $shipment['ship_date'] ) ) {
				/* translators: %s: ship date */
				echo '<br />' . esc_html( sprintf( __( 'Shipped: %s', 'track-orders-for-woocommerce' ), date_i18n( wc_date_format(), strtotime( $shipment['ship_date'] ) ) ) );
			}
			if ( ! empty( $shipment['items'] ) ) {
				echo '<br />';
				foreach ( $shipment['items'] as $item ) {
					echo esc_html( $item['name'] . ' x ' . absint( $item['qty'] ) ) . '<br />';
				}
			}
			if ( ! empty( $shipment['source'] ) ) {
				/* translators: %s: source (csv, rest, manual, integration id) */
				echo '<small>' . esc_html( sprintf( __( 'Source: %s', 'track-orders-for-woocommerce' ), $shipment['source'] ) ) . '</small>';
			}
			echo '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Show all shipments on the customer tracking page.
	 *
	 * Hooked to `wps_tofw_after_tracking_details`, fired by the tracking templates.
	 *
	 * @since 1.3.0
	 * @param int $order_id Order id.
	 * @return void
	 */
	public static function render_customer_shipments( $order_id ) {
		$order = wc_get_order( absint( $order_id ) );
		if ( ! $order || empty( $order->get_meta( self::META_SHIPMENTS, true ) ) ) {
			return;
		}

		/**
		 * Filter whether the shipments list is shown on the tracking page.
		 *
		 * @since 1.3.0
		 * @param bool     $show  Show.
		 * @param WC_Order $order Order.
		 */
		if ( ! apply_filters( 'wps_tofw_show_tracking_shipments', true, $order ) ) {
			return;
		}

		$shipments = self::get_shipments( $order );
		?>
		<div class="wps-tofw-shipments" style="margin:20px 0;">
			<h3><?php esc_html_e( 'Shipments', 'track-orders-for-woocommerce' ); ?></h3>
			<table class="shop_table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Carrier', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Tracking number', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Ship date', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Items', 'track-orders-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $shipments as $shipment ) : ?>
					<tr>
						<td><?php echo esc_html( isset( $shipment['carrier_label'] ) && '' !== $shipment['carrier_label'] ? $shipment['carrier_label'] : $shipment['carrier'] ); ?></td>
						<td><?php echo esc_html( $shipment['tracking_number'] ); ?></td>
						<td><?php echo esc_html( ! empty( $shipment['ship_date'] ) ? date_i18n( wc_date_format(), strtotime( $shipment['ship_date'] ) ) : '' ); ?></td>
						<td>
							<?php
							if ( empty( $shipment['items'] ) ) {
								esc_html_e( 'All items', 'track-orders-for-woocommerce' );
							} else {
								$names = array();
								foreach ( $shipment['items'] as $item ) {
									$names[] = $item['name'] . ' x ' . absint( $item['qty'] );
								}
								echo esc_html( implode( ', ', $names ) );
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Prepare a shipment for API output.
	 *
	 * @since 1.3.0
	 * @param array $shipment Shipment.
	 * @return array
	 */
	public static function format_for_response( $shipment ) {
		return array(
			'id'              => (string) $shipment['id'],
			'carrier'         => (string) $shipment['carrier'],
			'carrier_label'   => (string) ( isset( $shipment['carrier_label'] ) ? $shipment['carrier_label'] : $shipment['carrier'] ),
			'tracking_number' => (string) $shipment['tracking_number'],
			'ship_date'       => (string) $shipment['ship_date'],
			'items'           => isset( $shipment['items'] ) && is_array( $shipment['items'] ) ? array_values( $shipment['items'] ) : array(),
			'source'          => (string) ( isset( $shipment['source'] ) ? $shipment['source'] : '' ),
			'date_added'      => (string) ( isset( $shipment['date_added'] ) ? $shipment['date_added'] : '' ),
		);
	}
}
