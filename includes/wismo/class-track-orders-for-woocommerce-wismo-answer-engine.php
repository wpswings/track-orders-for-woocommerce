<?php
/**
 * Deterministic answer engine for the "Where is my order?" assistant.
 *
 * Works purely on a prepared, already verified order context array so it never
 * touches the database and always answers without any external API.
 *
 * @link       https://wpswings.com/
 * @since      1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes/wismo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Intent matching + templated replies built from order context.
 *
 * @since 1.3.0
 */
class Track_Orders_For_Woocommerce_Wismo_Answer_Engine {

	/**
	 * Intent keyword map. Order matters: the first matching intent wins.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public static function get_intents() {
		$intents = array(
			'support'  => '/\b(cancel+(ed|ing|ation)?|return(s|ing|ed)?|refund(s|ed)?|exchange|damaged?|broken|wrong|missing|complain(t)?|human|agent|support|contact)\b/i',
			'tracking' => '/\b(tracking|track|carrier|courier|shipper|fedex|usps|ups|dhl|canada post|awb|consignment|link)\b/i',
			'delivery' => '/\b(deliver(y|ed|ing)?|arriv(e|al|ing)|eta|when|how long|expected|estimate(d)?|reach)\b/i',
			'items'    => '/\b(items?|products?|contents?|bought|purchased|what did i (order|buy)|quantity|qty)|what(\'s| is) in\b/i',
			'address'  => '/\b(address|ship(ping)? to|destination|city|going to|sent to)\b/i',
			'status'   => '/\b(status|where|shipped|dispatched|processing|progress|update|order|hi|hello|hey)\b/i',
		);

		/**
		 * Filter the intent => regex map of the WISMO assistant.
		 *
		 * @since 1.3.0
		 * @param array $intents Intent regex map.
		 */
		return apply_filters( 'wps_tofw_wismo_intents', $intents );
	}

	/**
	 * Detect the intent of a customer question.
	 *
	 * @since 1.3.0
	 * @param string $question Customer question.
	 * @return string Intent slug or 'unknown'.
	 */
	public static function detect_intent( $question ) {
		$question = trim( (string) $question );
		if ( '' === $question ) {
			return 'status';
		}
		foreach ( self::get_intents() as $intent => $regex ) {
			if ( @preg_match( $regex, $question ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- filtered regex may be invalid.
				return $intent;
			}
		}
		return 'unknown';
	}

	/**
	 * Build an answer.
	 *
	 * @since 1.3.0
	 * @param string $question Customer question.
	 * @param array  $context  Verified order context (see Track_Orders_For_Woocommerce_Wismo::build_order_context()).
	 * @param array  $support  Support contact array( 'email' => '', 'url' => '' ).
	 * @return array array( 'intent' => string, 'text' => string, 'links' => array, 'confident' => bool )
	 */
	public static function answer( $question, $context, $support = array() ) {
		$intent = self::detect_intent( $question );
		$links  = array();

		switch ( $intent ) {
			case 'status':
				$text = self::status_text( $context );
				break;
			case 'tracking':
				list( $text, $links ) = self::tracking_text( $context );
				break;
			case 'delivery':
				$text = self::delivery_text( $context );
				break;
			case 'items':
				$text = self::items_text( $context );
				break;
			case 'address':
				$text = self::address_text( $context );
				break;
			case 'support':
				$text = __( 'Cancellations, returns, refunds and problems with an order are handled by our support team, who can review your request directly.', 'track-orders-for-woocommerce' );
				break;
			default:
				$text = __( 'I am not sure I understood that. Here is the latest on your order:', 'track-orders-for-woocommerce' ) . ' ' . self::status_text( $context );
				break;
		}

		$confident = ! in_array( $intent, array( 'support', 'unknown' ), true );
		if ( ! $confident ) {
			$support_text = self::support_text( $support );
			if ( '' !== $support_text ) {
				$text .= ' ' . $support_text;
			}
			$links = array_merge( $links, self::support_links( $support ) );
		}

		$result = array(
			'intent'    => $intent,
			'text'      => $text,
			'links'     => $links,
			'confident' => $confident,
		);

		/**
		 * Filter the deterministic WISMO answer.
		 *
		 * @since 1.3.0
		 * @param array  $result   Answer array.
		 * @param string $question Customer question.
		 * @param array  $context  Verified order context.
		 */
		return apply_filters( 'wps_tofw_wismo_deterministic_answer', $result, $question, $context );
	}

	/**
	 * Status sentence.
	 *
	 * @since 1.3.0
	 * @param array $c Context.
	 * @return string
	 */
	public static function status_text( $c ) {
		$number = isset( $c['order_number'] ) ? $c['order_number'] : '';
		$label  = isset( $c['status_label'] ) ? $c['status_label'] : '';
		$status = isset( $c['status'] ) ? $c['status'] : '';

		switch ( $status ) {
			case 'pending':
				/* translators: %s: order number. */
				$text = sprintf( __( 'Order #%s is pending payment. It will be prepared once the payment is confirmed.', 'track-orders-for-woocommerce' ), $number );
				break;
			case 'processing':
				/* translators: %s: order number. */
				$text = sprintf( __( 'Order #%s is being processed: we have received your payment and are preparing it for shipment.', 'track-orders-for-woocommerce' ), $number );
				break;
			case 'on-hold':
				/* translators: %s: order number. */
				$text = sprintf( __( 'Order #%s is on hold. This usually means we are waiting for payment confirmation or reviewing the order.', 'track-orders-for-woocommerce' ), $number );
				break;
			case 'completed':
				/* translators: %s: order number. */
				$text = sprintf( __( 'Order #%s has been completed.', 'track-orders-for-woocommerce' ), $number );
				if ( ! empty( $c['date_completed'] ) ) {
					/* translators: %s: date. */
					$text .= ' ' . sprintf( __( 'It was marked complete on %s.', 'track-orders-for-woocommerce' ), $c['date_completed'] );
				}
				break;
			case 'cancelled':
				/* translators: %s: order number. */
				$text = sprintf( __( 'Order #%s has been cancelled.', 'track-orders-for-woocommerce' ), $number );
				break;
			case 'refunded':
				/* translators: %s: order number. */
				$text = sprintf( __( 'Order #%s has been refunded.', 'track-orders-for-woocommerce' ), $number );
				break;
			case 'failed':
				/* translators: %s: order number. */
				$text = sprintf( __( 'The payment for order #%s failed, so the order has not been processed.', 'track-orders-for-woocommerce' ), $number );
				break;
			default:
				/* translators: 1: order number, 2: status label. */
				$text = sprintf( __( 'The current status of order #%1$s is "%2$s".', 'track-orders-for-woocommerce' ), $number, $label );
				break;
		}

		if ( ! empty( $c['status_updated'] ) ) {
			/* translators: %s: date/time. */
			$text .= ' ' . sprintf( __( 'Last update: %s.', 'track-orders-for-woocommerce' ), $c['status_updated'] );
		}
		if ( ! in_array( $status, array( 'cancelled', 'refunded', 'failed' ), true ) ) {
			$carrier_text = self::carrier_status_text( $c );
			if ( '' !== $carrier_text ) {
				$text .= ' ' . $carrier_text;
			}
		}
		if ( ! empty( $c['tracking_number'] ) && ! in_array( $status, array( 'cancelled', 'refunded', 'failed' ), true ) ) {
			$text .= ' ' . __( 'A tracking number is available - ask me "tracking" to see it.', 'track-orders-for-woocommerce' );
		}
		return $text;
	}

	/**
	 * Tracking sentence and links.
	 *
	 * @since 1.3.0
	 * @param array $c Context.
	 * @return array array( string $text, array $links )
	 */
	public static function tracking_text( $c ) {
		$links = array();
		if ( empty( $c['tracking_number'] ) ) {
			$text = __( 'There is no tracking number on this order yet. It is added as soon as the parcel is handed to the carrier.', 'track-orders-for-woocommerce' ) . ' ' . self::status_text( $c );
			if ( ! empty( $c['tracking_page_url'] ) ) {
				$links[] = array(
					'label' => __( 'Open order tracking page', 'track-orders-for-woocommerce' ),
					'url'   => $c['tracking_page_url'],
				);
			}
			return array( $text, $links );
		}

		if ( ! empty( $c['carrier'] ) ) {
			/* translators: 1: carrier name, 2: tracking number. */
			$text = sprintf( __( 'Your parcel is shipped with %1$s. Tracking number: %2$s.', 'track-orders-for-woocommerce' ), $c['carrier'], $c['tracking_number'] );
		} else {
			/* translators: %s: tracking number. */
			$text = sprintf( __( 'Your tracking number is %s.', 'track-orders-for-woocommerce' ), $c['tracking_number'] );
		}
		if ( ! empty( $c['tracking_url'] ) ) {
			$links[] = array(
				'label' => __( 'Track with the carrier', 'track-orders-for-woocommerce' ),
				'url'   => $c['tracking_url'],
			);
		}
		if ( ! empty( $c['tracking_page_url'] ) ) {
			$links[] = array(
				'label' => __( 'Open order tracking page', 'track-orders-for-woocommerce' ),
				'url'   => $c['tracking_page_url'],
			);
		}
		return array( $text, $links );
	}

	/**
	 * Delivery sentence.
	 *
	 * @since 1.3.0
	 * @param array $c Context.
	 * @return string
	 */
	public static function delivery_text( $c ) {
		$status = isset( $c['status'] ) ? $c['status'] : '';
		if ( in_array( $status, array( 'cancelled', 'refunded', 'failed' ), true ) ) {
			return self::status_text( $c );
		}
		$carrier_status = isset( $c['carrier_status'] ) ? $c['carrier_status'] : '';
		$carrier_text   = self::carrier_status_text( $c );
		if ( in_array( $carrier_status, array( 'delivered', 'out_for_delivery', 'available_for_pickup' ), true ) ) {
			return $carrier_text;
		}
		if ( ! empty( $c['estimated_delivery'] ) ) {
			/* translators: %s: estimated delivery date. */
			$text = sprintf( __( 'The estimated delivery for your order is %s.', 'track-orders-for-woocommerce' ), $c['estimated_delivery'] );
			return '' !== $carrier_text ? $text . ' ' . $carrier_text : $text;
		}
		if ( 'completed' === $status ) {
			return self::status_text( $c );
		}
		return __( 'An estimated delivery date has not been set for this order yet.', 'track-orders-for-woocommerce' ) . ' ' . self::status_text( $c );
	}

	/**
	 * Carrier status sentence (from the automatic carrier status sync), or '' when unknown.
	 *
	 * @since 1.3.0
	 * @param array $c Context.
	 * @return string
	 */
	public static function carrier_status_text( $c ) {
		$status = isset( $c['carrier_status'] ) ? (string) $c['carrier_status'] : '';
		$label  = ! empty( $c['carrier_status_label'] ) ? (string) $c['carrier_status_label'] : ucwords( str_replace( '_', ' ', $status ) );
		if ( '' === $status || 'pending' === $status ) {
			return '';
		}

		switch ( $status ) {
			case 'delivered':
				$text = __( 'The carrier reports that your parcel has been delivered.', 'track-orders-for-woocommerce' );
				break;
			case 'out_for_delivery':
				$text = __( 'The carrier reports that your parcel is out for delivery and should arrive soon.', 'track-orders-for-woocommerce' );
				break;
			case 'available_for_pickup':
				$text = __( 'The carrier reports that your parcel is ready for pickup.', 'track-orders-for-woocommerce' );
				break;
			case 'in_transit':
				$text = __( 'The carrier reports that your parcel is in transit.', 'track-orders-for-woocommerce' );
				break;
			case 'info_received':
				$text = __( 'A shipping label has been created and the carrier is waiting to receive the parcel.', 'track-orders-for-woocommerce' );
				break;
			case 'exception':
			case 'failed_attempt':
				/* translators: %s: carrier status label. */
				$text = sprintf( __( 'The carrier reported a delivery issue (%s). Please check the carrier tracking page or contact our support team.', 'track-orders-for-woocommerce' ), $label );
				break;
			default:
				/* translators: %s: carrier status label. */
				$text = sprintf( __( 'Latest carrier status: %s.', 'track-orders-for-woocommerce' ), $label );
				break;
		}

		if ( ! empty( $c['carrier_status_updated'] ) ) {
			/* translators: %s: date/time. */
			$text .= ' ' . sprintf( __( 'Carrier update: %s.', 'track-orders-for-woocommerce' ), $c['carrier_status_updated'] );
		}
		return $text;
	}

	/**
	 * Items sentence.
	 *
	 * @since 1.3.0
	 * @param array $c Context.
	 * @return string
	 */
	public static function items_text( $c ) {
		if ( empty( $c['items'] ) || ! is_array( $c['items'] ) ) {
			return __( 'I could not find any items on this order.', 'track-orders-for-woocommerce' );
		}
		$parts = array();
		foreach ( $c['items'] as $item ) {
			$parts[] = sprintf( '%s x %d', $item['name'], (int) $item['qty'] );
		}
		/* translators: %s: comma separated list of items. */
		return sprintf( __( 'Your order contains: %s.', 'track-orders-for-woocommerce' ), implode( ', ', $parts ) );
	}

	/**
	 * Address sentence (city/country only, never the full address).
	 *
	 * @since 1.3.0
	 * @param array $c Context.
	 * @return string
	 */
	public static function address_text( $c ) {
		$place = trim( implode( ', ', array_filter( array( isset( $c['ship_city'] ) ? $c['ship_city'] : '', isset( $c['ship_country'] ) ? $c['ship_country'] : '' ) ) ) );
		if ( '' === $place ) {
			return __( 'This order has no shipping destination on file.', 'track-orders-for-woocommerce' );
		}
		/* translators: %s: city, country. */
		return sprintf( __( 'Your order is being shipped to %s. For security I can only share the city here - see your order confirmation e-mail for the full address.', 'track-orders-for-woocommerce' ), $place );
	}

	/**
	 * Support sentence.
	 *
	 * @since 1.3.0
	 * @param array $support Support data.
	 * @return string
	 */
	public static function support_text( $support ) {
		$email = isset( $support['email'] ) ? $support['email'] : '';
		$url   = isset( $support['url'] ) ? $support['url'] : '';
		if ( '' !== $email ) {
			/* translators: %s: support e-mail. */
			return sprintf( __( 'Please contact our support team at %s and include your order number.', 'track-orders-for-woocommerce' ), $email );
		}
		if ( '' !== $url ) {
			return __( 'Please contact our support team using the link below and include your order number.', 'track-orders-for-woocommerce' );
		}
		return __( 'Please contact the store support team and include your order number.', 'track-orders-for-woocommerce' );
	}

	/**
	 * Support links.
	 *
	 * @since 1.3.0
	 * @param array $support Support data.
	 * @return array
	 */
	public static function support_links( $support ) {
		$links = array();
		if ( ! empty( $support['url'] ) ) {
			$links[] = array(
				'label' => __( 'Contact support', 'track-orders-for-woocommerce' ),
				'url'   => $support['url'],
			);
		}
		if ( ! empty( $support['email'] ) ) {
			$links[] = array(
				'label' => $support['email'],
				'url'   => 'mailto:' . $support['email'],
			);
		}
		return $links;
	}
}
