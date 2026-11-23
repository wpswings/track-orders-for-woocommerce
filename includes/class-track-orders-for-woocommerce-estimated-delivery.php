<?php
/**
 * Rules based Estimated Delivery Date (EDD).
 *
 * Computes an estimated delivery date range from processing days, transit days
 * per shipping method, non-working week days, holidays and an order cut-off time,
 * and shows it on the product page, cart / checkout (classic and block), order
 * emails, order details and the tracking page. A manual date saved on the order
 * (wps_tofw_estimated_delivery_date) always overrides the computed value.
 *
 * @link  https://wpswings.com/
 * @since 1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Estimated delivery date feature.
 *
 * @since 1.3.0
 */
class Track_Orders_For_Woocommerce_Estimated_Delivery {

	/**
	 * Option name holding all EDD settings.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const OPTION = 'wps_tofw_edd_settings';

	/**
	 * Order meta: computed range start (Y-m-d).
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const META_FROM = 'wps_tofw_edd_from';

	/**
	 * Order meta: computed range end (Y-m-d).
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const META_TO = 'wps_tofw_edd_to';

	/**
	 * Shipping rate / shipping item meta: range start (Y-m-d).
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const RATE_META_FROM = '_wps_tofw_edd_from';

	/**
	 * Shipping rate / shipping item meta: range end (Y-m-d).
	 *
	 * @since 1.3.0
	 * @var string
	 */
	const RATE_META_TO = '_wps_tofw_edd_to';

	/**
	 * Safety limit when walking the calendar.
	 *
	 * @since 1.3.0
	 * @var int
	 */
	const MAX_LOOP = 400;

	/**
	 * Whether the settings were just saved (for the admin notice).
	 *
	 * @since 1.3.0
	 * @var bool
	 */
	public static $settings_saved = false;

	/**
	 * Whether the product page estimate was already printed for this request.
	 *
	 * @since 1.3.0
	 * @var array
	 */
	private $printed_products = array();

	/**
	 * Default settings.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'enable'           => 'off',
			'show_product'     => 'on',
			'show_cart'        => 'on',
			'show_email'       => 'on',
			'show_tracking'    => 'on',
			'label'            => __( 'Estimated delivery', 'track-orders-for-woocommerce' ),
			'date_format'      => 'D, M j',
			'processing_days'  => 1,
			'transit_min'      => 3,
			'transit_max'      => 5,
			'cutoff_time'      => '14:00',
			'non_working_days' => array( 0, 6 ),
			'holidays'         => array(),
			'methods'          => array(),
		);
	}

	/**
	 * Get the saved settings merged with defaults.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::get_defaults() );
	}

	/**
	 * Is the feature enabled (and the plugin itself enabled)?
	 *
	 * @since 1.3.0
	 * @param string $location Optional display location key (product|cart|email|tracking).
	 * @return bool
	 */
	public static function is_enabled( $location = '' ) {
		if ( 'on' !== get_option( 'wps_tofw_is_plugin_enable' ) ) {
			return false;
		}
		$settings = self::get_settings();
		if ( 'on' !== $settings['enable'] ) {
			return false;
		}
		if ( '' !== $location ) {
			$key = 'show_' . $location;
			return isset( $settings[ $key ] ) && 'on' === $settings[ $key ];
		}
		return true;
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.3.0
	 * @param Track_Orders_For_Woocommerce_Loader $loader Plugin loader.
	 * @return void
	 */
	public function register_hooks( $loader ) {
		if ( is_admin() ) {
			$loader->add_action( 'admin_init', $this, 'wps_tofw_edd_save_settings' );
		}

		if ( ! self::is_enabled() ) {
			return;
		}

		// Product page (classic templates, block templates and shortcode).
		$loader->add_action( 'woocommerce_single_product_summary', $this, 'wps_tofw_edd_product_page', 25 );
		$loader->add_filter( 'render_block', $this, 'wps_tofw_edd_render_product_block', 10, 2 );
		$loader->add_action( 'init', $this, 'wps_tofw_edd_register_shortcode' );

		// Cart / checkout: rates (classic + Store API / blocks).
		$loader->add_filter( 'woocommerce_cart_shipping_packages', $this, 'wps_tofw_edd_bust_rate_cache', 20 );
		$loader->add_filter( 'woocommerce_package_rates', $this, 'wps_tofw_edd_add_to_rates', 100, 2 );
		$loader->add_action( 'woocommerce_after_shipping_rate', $this, 'wps_tofw_edd_after_shipping_rate', 10, 2 );

		// Order creation (classic + block checkout).
		$loader->add_action( 'woocommerce_checkout_order_processed', $this, 'wps_tofw_edd_on_classic_order', 20, 3 );
		$loader->add_action( 'woocommerce_store_api_checkout_order_processed', $this, 'wps_tofw_edd_store_on_order', 20, 1 );

		// Emails & order details.
		$loader->add_action( 'woocommerce_email_after_order_table', $this, 'wps_tofw_edd_email', 15, 4 );
		$loader->add_action( 'woocommerce_order_details_after_order_table', $this, 'wps_tofw_edd_order_details', 5, 1 );
	}

	/*
	|--------------------------------------------------------------------------
	| Calculation.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Resolve the rule (processing / transit days) for a shipping method instance.
	 *
	 * @since 1.3.0
	 * @param int $instance_id Shipping method instance ID (0 = defaults).
	 * @return array|null Rule array or null when the estimate is hidden for this method.
	 */
	public static function get_rule( $instance_id = 0 ) {
		$settings = self::get_settings();
		$rule     = array(
			'processing' => absint( $settings['processing_days'] ),
			'min'        => absint( $settings['transit_min'] ),
			'max'        => absint( $settings['transit_max'] ),
		);

		$instance_id = absint( $instance_id );
		if ( $instance_id && isset( $settings['methods'][ $instance_id ] ) && is_array( $settings['methods'][ $instance_id ] ) ) {
			$method = $settings['methods'][ $instance_id ];
			if ( ! empty( $method['hide'] ) ) {
				return null;
			}
			if ( isset( $method['processing'] ) && '' !== $method['processing'] ) {
				$rule['processing'] = absint( $method['processing'] );
			}
			if ( isset( $method['min'] ) && '' !== $method['min'] ) {
				$rule['min'] = absint( $method['min'] );
			}
			if ( isset( $method['max'] ) && '' !== $method['max'] ) {
				$rule['max'] = absint( $method['max'] );
			}
		}

		if ( $rule['max'] < $rule['min'] ) {
			$rule['max'] = $rule['min'];
		}

		/**
		 * Filter the resolved EDD rule for a shipping method instance.
		 *
		 * @since 1.3.0
		 * @param array|null $rule        Rule (processing, min, max).
		 * @param int        $instance_id Shipping method instance ID.
		 */
		return apply_filters( 'wps_tofw_edd_rule', $rule, $instance_id );
	}

	/**
	 * Is the given day a business (shipping / delivery) day?
	 *
	 * @since 1.3.0
	 * @param DateTimeImmutable $day Day in site timezone.
	 * @return bool
	 */
	public static function is_business_day( DateTimeImmutable $day ) {
		$settings = self::get_settings();
		$off_days = array_map( 'intval', (array) $settings['non_working_days'] );
		// Guard against a configuration with all 7 days off.
		if ( count( array_unique( $off_days ) ) >= 7 ) {
			$off_days = array();
		}
		if ( in_array( (int) $day->format( 'w' ), $off_days, true ) ) {
			return false;
		}
		if ( in_array( $day->format( 'Y-m-d' ), (array) $settings['holidays'], true ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Move forward to the first business day (inclusive).
	 *
	 * @since 1.3.0
	 * @param DateTimeImmutable $day Start day.
	 * @return DateTimeImmutable
	 */
	public static function first_business_day( DateTimeImmutable $day ) {
		$i = 0;
		while ( ! self::is_business_day( $day ) && $i++ < self::MAX_LOOP ) {
			$day = $day->modify( '+1 day' );
		}
		return $day;
	}

	/**
	 * Add N business days.
	 *
	 * @since 1.3.0
	 * @param DateTimeImmutable $day  Start day.
	 * @param int               $days Number of business days to add.
	 * @return DateTimeImmutable
	 */
	public static function add_business_days( DateTimeImmutable $day, $days ) {
		$days = absint( $days );
		$i    = 0;
		while ( $days > 0 && $i++ < self::MAX_LOOP ) {
			$day = $day->modify( '+1 day' );
			if ( self::is_business_day( $day ) ) {
				--$days;
			}
		}
		return $day;
	}

	/**
	 * Calculate the delivery range for a shipping method instance.
	 *
	 * @since 1.3.0
	 * @param int                    $instance_id Shipping method instance ID (0 = defaults).
	 * @param DateTimeInterface|null $placed_at   Time the order is (or would be) placed. Defaults to now.
	 * @return array|null array( 'from' => 'Y-m-d', 'to' => 'Y-m-d' ) or null when hidden.
	 */
	public static function calculate( $instance_id = 0, $placed_at = null ) {
		$rule = self::get_rule( $instance_id );
		if ( empty( $rule ) ) {
			return null;
		}

		$tz  = wp_timezone();
		$now = $placed_at instanceof DateTimeInterface
			? ( new DateTimeImmutable( '@' . $placed_at->getTimestamp() ) )->setTimezone( $tz )
			: new DateTimeImmutable( 'now', $tz );

		$settings = self::get_settings();
		$day      = $now->setTime( 0, 0, 0 );

		// Orders after the cut-off time start on the next day.
		$cutoff = (string) $settings['cutoff_time'];
		if ( preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $cutoff ) && $now->format( 'H:i' ) >= $cutoff ) {
			$day = $day->modify( '+1 day' );
		}

		$start = self::first_business_day( $day );
		$ship  = self::add_business_days( $start, $rule['processing'] );
		$from  = self::add_business_days( $ship, $rule['min'] );
		$to    = self::add_business_days( $ship, $rule['max'] );

		$range = array(
			'from' => $from->format( 'Y-m-d' ),
			'to'   => $to->format( 'Y-m-d' ),
		);

		/**
		 * Filter the calculated EDD range.
		 *
		 * @since 1.3.0
		 * @param array             $range       Range (from, to) in Y-m-d.
		 * @param int               $instance_id Shipping method instance ID.
		 * @param DateTimeImmutable $now         Placement time in site timezone.
		 */
		return apply_filters( 'wps_tofw_edd_calculated_range', $range, $instance_id, $now );
	}

	/*
	|--------------------------------------------------------------------------
	| Formatting.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Format a Y-m-d date in the site timezone and locale.
	 *
	 * @since 1.3.0
	 * @param string $ymd    Date (Y-m-d).
	 * @param string $format Optional PHP date format.
	 * @return string
	 */
	public static function format_date( $ymd, $format = '' ) {
		$settings = self::get_settings();
		$format   = $format ? $format : ( $settings['date_format'] ? $settings['date_format'] : 'D, M j' );
		try {
			$date = new DateTimeImmutable( $ymd . ' 12:00:00', wp_timezone() );
		} catch ( Exception $e ) {
			return '';
		}
		return wp_date( $format, $date->getTimestamp(), wp_timezone() );
	}

	/**
	 * Format a range, e.g. "Mon, Dec 1 – Wed, Dec 3".
	 *
	 * @since 1.3.0
	 * @param string $from Range start (Y-m-d).
	 * @param string $to   Range end (Y-m-d).
	 * @return string
	 */
	public static function format_range( $from, $to ) {
		if ( empty( $from ) || empty( $to ) ) {
			return '';
		}
		if ( $from === $to ) {
			return self::format_date( $from );
		}
		/* translators: 1: earliest delivery date, 2: latest delivery date. */
		return sprintf( __( '%1$s – %2$s', 'track-orders-for-woocommerce' ), self::format_date( $from ), self::format_date( $to ) );
	}

	/**
	 * Full display text, e.g. "Estimated delivery: Mon, Dec 1 – Wed, Dec 3".
	 *
	 * @since 1.3.0
	 * @param string $range_text Formatted range or date.
	 * @return string
	 */
	public static function display_text( $range_text ) {
		if ( '' === $range_text ) {
			return '';
		}
		$settings = self::get_settings();
		$label    = '' !== trim( (string) $settings['label'] ) ? $settings['label'] : __( 'Estimated delivery', 'track-orders-for-woocommerce' );
		/* translators: 1: label, 2: date range. */
		return sprintf( __( '%1$s: %2$s', 'track-orders-for-woocommerce' ), $label, $range_text );
	}

	/*
	|--------------------------------------------------------------------------
	| Order helpers.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the EDD for an order. Manual date (order meta) overrides computed range.
	 *
	 * @since 1.3.0
	 * @param WC_Order|int $order Order.
	 * @return array|null array( 'manual' => bool, 'from' => Y-m-d|'', 'to' => Y-m-d|'', 'text' => string ) or null.
	 */
	public static function get_order_edd( $order ) {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		$manual_date = (string) $order->get_meta( 'wps_tofw_estimated_delivery_date', true );
		if ( '' !== trim( $manual_date ) ) {
			$manual_time = (string) $order->get_meta( 'wps_tofw_estimated_delivery_time', true );
			$timestamp   = strtotime( $manual_date );
			$text        = $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : $manual_date;
			if ( '' !== trim( $manual_time ) ) {
				$text .= ' ' . $manual_time;
			}
			return array(
				'manual' => true,
				'from'   => $timestamp ? gmdate( 'Y-m-d', $timestamp ) : '',
				'to'     => $timestamp ? gmdate( 'Y-m-d', $timestamp ) : '',
				'text'   => $text,
			);
		}

		$from = (string) $order->get_meta( self::META_FROM, true );
		$to   = (string) $order->get_meta( self::META_TO, true );
		if ( '' === $from || '' === $to ) {
			return null;
		}
		return array(
			'manual' => false,
			'from'   => $from,
			'to'     => $to,
			'text'   => self::format_range( $from, $to ),
		);
	}

	/**
	 * Tracking templates helper: return the manual date or, when empty, the latest computed date (Y-m-d).
	 *
	 * @since 1.3.0
	 * @param WC_Order $order       Order.
	 * @param string   $manual_date Manual date already read by the template.
	 * @return string
	 */
	public static function tracking_date_fallback( $order, $manual_date ) {
		if ( '' !== (string) $manual_date || ! self::is_enabled( 'tracking' ) ) {
			return (string) $manual_date;
		}
		$edd = self::get_order_edd( $order );
		return ( $edd && ! $edd['manual'] ) ? $edd['to'] : (string) $manual_date;
	}

	/**
	 * Tracking templates helper: formatted range text when no manual date exists.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order       Order.
	 * @param string   $manual_date Manual date already read by the template.
	 * @return string
	 */
	public static function tracking_text_fallback( $order, $manual_date ) {
		if ( '' !== (string) $manual_date || ! self::is_enabled( 'tracking' ) ) {
			return (string) $manual_date;
		}
		$edd = self::get_order_edd( $order );
		return ( $edd && ! $edd['manual'] ) ? $edd['text'] : (string) $manual_date;
	}

	/**
	 * Compute and store the EDD on an order (only when no manual value exists).
	 *
	 * Prefers the range shown to the customer on the selected shipping rate, so
	 * cart, checkout and order always agree.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public static function store_order_edd( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		if ( '' !== trim( (string) $order->get_meta( 'wps_tofw_estimated_delivery_date', true ) ) ) {
			return;
		}

		$shipping_items = $order->get_items( 'shipping' );
		if ( empty( $shipping_items ) ) {
			return;
		}

		$from = '';
		$to   = '';
		foreach ( $shipping_items as $item ) {
			$item_from = (string) $item->get_meta( self::RATE_META_FROM, true );
			$item_to   = (string) $item->get_meta( self::RATE_META_TO, true );
			if ( '' === $item_from || '' === $item_to ) {
				$range = self::calculate( (int) $item->get_instance_id(), $order->get_date_created() );
				if ( empty( $range ) ) {
					continue;
				}
				$item_from = $range['from'];
				$item_to   = $range['to'];
			}
			// Multiple packages: the order arrives when the slowest package arrives.
			$from = ( '' === $from || $item_from > $from ) ? $item_from : $from;
			$to   = ( '' === $to || $item_to > $to ) ? $item_to : $to;
		}

		if ( '' === $from ) {
			return;
		}

		$order->update_meta_data( self::META_FROM, $from );
		$order->update_meta_data( self::META_TO, $to );
		$order->save();
	}

	/*
	|--------------------------------------------------------------------------
	| Product page.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Find the shipping method instance relevant to the current customer.
	 *
	 * Uses the chosen shipping method when it belongs to the customer's zone,
	 * otherwise the first enabled method of the matching zone.
	 *
	 * @since 1.3.0
	 * @return int|null Instance ID (0 = defaults) or null when no method ships to the customer.
	 */
	public static function get_customer_instance_id() {
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return 0;
		}

		$country  = '';
		$state    = '';
		$postcode = '';
		if ( function_exists( 'WC' ) && WC()->customer ) {
			$country  = WC()->customer->get_shipping_country();
			$state    = WC()->customer->get_shipping_state();
			$postcode = WC()->customer->get_shipping_postcode();
		}
		if ( empty( $country ) ) {
			$base     = wc_get_base_location();
			$country  = $base['country'];
			$state    = $base['state'];
			$postcode = get_option( 'woocommerce_store_postcode', '' );
		}

		$zone    = WC_Shipping_Zones::get_zone_matching_package(
			array(
				'destination' => array(
					'country'  => $country,
					'state'    => $state,
					'postcode' => $postcode,
				),
			)
		);
		$methods = $zone ? $zone->get_shipping_methods( true ) : array();
		if ( empty( $methods ) ) {
			return null;
		}

		$instance_ids = array();
		foreach ( $methods as $method ) {
			$instance_ids[] = (int) $method->get_instance_id();
		}

		if ( function_exists( 'WC' ) && WC()->session ) {
			$chosen = (array) WC()->session->get( 'chosen_shipping_methods', array() );
			if ( ! empty( $chosen[0] ) && false !== strpos( $chosen[0], ':' ) ) {
				$parts      = explode( ':', $chosen[0] );
				$chosen_iid = (int) end( $parts );
				if ( in_array( $chosen_iid, $instance_ids, true ) ) {
					return $chosen_iid;
				}
			}
		}

		// First method in the zone that does not hide the estimate.
		foreach ( $instance_ids as $instance_id ) {
			if ( null !== self::get_rule( $instance_id ) ) {
				return $instance_id;
			}
		}
		return null;
	}

	/**
	 * Build the product page HTML.
	 *
	 * @since 1.3.0
	 * @param WC_Product|null $product Product.
	 * @return string
	 */
	public function get_product_html( $product ) {
		if ( ! $product instanceof WC_Product || ! self::is_enabled( 'product' ) ) {
			return '';
		}
		if ( ! $product->needs_shipping() || ! $product->is_in_stock() || $product->is_on_backorder() ) {
			return '';
		}

		$instance_id = self::get_customer_instance_id();
		if ( null === $instance_id ) {
			return '';
		}
		$range = self::calculate( $instance_id );
		if ( empty( $range ) ) {
			return '';
		}

		$text = self::display_text( self::format_range( $range['from'], $range['to'] ) );

		/**
		 * Filter the product page EDD text.
		 *
		 * @since 1.3.0
		 * @param string     $text    Text.
		 * @param WC_Product $product Product.
		 * @param array      $range   Range (from, to).
		 */
		$text = apply_filters( 'wps_tofw_edd_product_text', $text, $product, $range );

		return '<p class="wps-tofw-edd wps-tofw-edd--product">' . esc_html( $text ) . '</p>';
	}

	/**
	 * Print the estimate on classic single product pages.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function wps_tofw_edd_product_page() {
		global $product;
		if ( ! $product instanceof WC_Product || isset( $this->printed_products[ $product->get_id() ] ) ) {
			return;
		}
		$html = $this->get_product_html( $product );
		if ( '' !== $html ) {
			$this->printed_products[ $product->get_id() ] = true;
			echo wp_kses_post( $html );
		}
	}

	/**
	 * Append the estimate after the add-to-cart block on block-based single product templates.
	 *
	 * @since 1.3.0
	 * @param string $block_content Rendered block.
	 * @param array  $block         Block data.
	 * @return string
	 */
	public function wps_tofw_edd_render_product_block( $block_content, $block ) {
		if ( empty( $block['blockName'] ) || ! in_array( $block['blockName'], array( 'woocommerce/add-to-cart-form', 'woocommerce/add-to-cart-with-options' ), true ) ) {
			return $block_content;
		}
		if ( ! is_product() ) {
			return $block_content;
		}
		$product = wc_get_product( get_the_ID() );
		if ( ! $product instanceof WC_Product || isset( $this->printed_products[ $product->get_id() ] ) ) {
			return $block_content;
		}
		$html = $this->get_product_html( $product );
		if ( '' === $html ) {
			return $block_content;
		}
		$this->printed_products[ $product->get_id() ] = true;
		return $block_content . $html;
	}

	/**
	 * Register the [wps_tofw_estimated_delivery] shortcode.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function wps_tofw_edd_register_shortcode() {
		add_shortcode( 'wps_tofw_estimated_delivery', array( $this, 'wps_tofw_edd_shortcode' ) );
	}

	/**
	 * Shortcode callback. Attribute product_id is optional (defaults to the current product).
	 *
	 * @since 1.3.0
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function wps_tofw_edd_shortcode( $atts ) {
		$atts       = shortcode_atts( array( 'product_id' => 0 ), $atts, 'wps_tofw_estimated_delivery' );
		$product_id = absint( $atts['product_id'] ) ? absint( $atts['product_id'] ) : get_the_ID();
		$product    = wc_get_product( $product_id );
		if ( $product instanceof WC_Product ) {
			$this->printed_products[ $product->get_id() ] = true;
		}
		return $this->get_product_html( $product );
	}

	/*
	|--------------------------------------------------------------------------
	| Cart / checkout.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Add the current "EDD day" to packages so cached rates refresh when the day or cut-off changes.
	 *
	 * @since 1.3.0
	 * @param array $packages Shipping packages.
	 * @return array
	 */
	public function wps_tofw_edd_bust_rate_cache( $packages ) {
		if ( ! self::is_enabled( 'cart' ) || ! is_array( $packages ) ) {
			return $packages;
		}
		$settings = self::get_settings();
		$now      = new DateTimeImmutable( 'now', wp_timezone() );
		$marker   = $now->format( 'Y-m-d' ) . ( $now->format( 'H:i' ) >= (string) $settings['cutoff_time'] ? '-late' : '-early' );
		$marker  .= '-' . md5( wp_json_encode( $settings ) );
		foreach ( $packages as $key => $package ) {
			$packages[ $key ]['wps_tofw_edd_marker'] = $marker;
		}
		return $packages;
	}

	/**
	 * Attach the estimate to each shipping rate (meta + delivery_time for block checkout).
	 *
	 * @since 1.3.0
	 * @param WC_Shipping_Rate[] $rates   Rates.
	 * @param array              $package Package.
	 * @return WC_Shipping_Rate[]
	 */
	public function wps_tofw_edd_add_to_rates( $rates, $package ) {
		if ( ! self::is_enabled( 'cart' ) || ! is_array( $rates ) ) {
			return $rates;
		}
		foreach ( $rates as $rate ) {
			if ( ! $rate instanceof WC_Shipping_Rate ) {
				continue;
			}
			$range = self::calculate( (int) $rate->get_instance_id() );
			if ( empty( $range ) ) {
				continue;
			}
			$rate->add_meta_data( self::RATE_META_FROM, $range['from'] );
			$rate->add_meta_data( self::RATE_META_TO, $range['to'] );

			$text = self::display_text( self::format_range( $range['from'], $range['to'] ) );
			if ( method_exists( $rate, 'set_delivery_time' ) ) {
				// WooCommerce 9.2+: shown by the Cart / Checkout blocks next to the rate.
				$rate->set_delivery_time( $text );
			} elseif ( WC()->is_rest_api_request() ) {
				// Older WooCommerce: fall back to the label for block checkout.
				$rate->set_label( $rate->get_label() . ' (' . $text . ')' );
			}
		}
		return $rates;
	}

	/**
	 * Print the estimate below each shipping rate on the classic cart / checkout.
	 *
	 * @since 1.3.0
	 * @param WC_Shipping_Rate $method Rate.
	 * @param int              $index  Package index.
	 * @return void
	 */
	public function wps_tofw_edd_after_shipping_rate( $method, $index ) {
		if ( ! $method instanceof WC_Shipping_Rate || ! self::is_enabled( 'cart' ) ) {
			return;
		}
		$meta = $method->get_meta_data();
		$from = isset( $meta[ self::RATE_META_FROM ] ) ? $meta[ self::RATE_META_FROM ] : '';
		$to   = isset( $meta[ self::RATE_META_TO ] ) ? $meta[ self::RATE_META_TO ] : '';
		if ( '' === $from || '' === $to ) {
			return;
		}
		echo '<div class="wps-tofw-edd wps-tofw-edd--rate"><small>' . esc_html( self::display_text( self::format_range( $from, $to ) ) ) . '</small></div>';
	}

	/*
	|--------------------------------------------------------------------------
	| Order creation.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Classic checkout order processed.
	 *
	 * @since 1.3.0
	 * @param int      $order_id    Order ID.
	 * @param array    $posted_data Posted data.
	 * @param WC_Order $order       Order.
	 * @return void
	 */
	public function wps_tofw_edd_on_classic_order( $order_id, $posted_data = array(), $order = null ) {
		$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
		self::store_order_edd( $order );
	}

	/**
	 * Block checkout (Store API) order processed.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function wps_tofw_edd_store_on_order( $order ) {
		self::store_order_edd( $order );
	}

	/*
	|--------------------------------------------------------------------------
	| Emails & order details.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Should the estimate be shown for this order status?
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	private function is_displayable_status( $order ) {
		$hidden = array( 'completed', 'cancelled', 'refunded', 'failed' );
		/**
		 * Filter order statuses for which the EDD is not shown.
		 *
		 * @since 1.3.0
		 * @param array $hidden Statuses without "wc-" prefix.
		 */
		$hidden = apply_filters( 'wps_tofw_edd_hidden_statuses', $hidden );
		return ! in_array( $order->get_status(), (array) $hidden, true );
	}

	/**
	 * Show the estimate in order emails.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order         Order.
	 * @param bool     $sent_to_admin Sent to admin.
	 * @param bool     $plain_text    Plain text email.
	 * @param WC_Email $email         Email object.
	 * @return void
	 */
	public function wps_tofw_edd_email( $order, $sent_to_admin = false, $plain_text = false, $email = null ) {
		if ( ! $order instanceof WC_Order || ! self::is_enabled( 'email' ) || ! $this->is_displayable_status( $order ) ) {
			return;
		}
		$edd = self::get_order_edd( $order );
		if ( empty( $edd ) ) {
			return;
		}
		$text = self::display_text( $edd['text'] );
		if ( $plain_text ) {
			echo "\n" . esc_html( $text ) . "\n\n";
			return;
		}
		echo '<p class="wps-tofw-edd wps-tofw-edd--email" style="margin:0 0 16px;"><strong>' . esc_html( $text ) . '</strong></p>';
	}

	/**
	 * Show the estimate on the thank-you / view-order page.
	 *
	 * @since 1.3.0
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function wps_tofw_edd_order_details( $order ) {
		if ( ! $order instanceof WC_Order || ! self::is_enabled( 'tracking' ) || ! $this->is_displayable_status( $order ) ) {
			return;
		}
		$edd = self::get_order_edd( $order );
		if ( empty( $edd ) ) {
			return;
		}
		echo '<p class="wps-tofw-edd wps-tofw-edd--order"><strong>' . esc_html( self::display_text( $edd['text'] ) ) . '</strong></p>';
	}

	/*
	|--------------------------------------------------------------------------
	| Admin settings.
	|--------------------------------------------------------------------------
	*/

	/**
	 * List WooCommerce shipping zones with their methods.
	 *
	 * @since 1.3.0
	 * @return array List of array( 'name' => string, 'methods' => array( instance_id => title ) ).
	 */
	public static function get_zones_with_methods() {
		$list = array();
		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			return $list;
		}
		$zones   = WC_Shipping_Zones::get_zones();
		$zones[] = array(
			'zone_name'        => __( 'Locations not covered by your other zones', 'track-orders-for-woocommerce' ),
			'shipping_methods' => ( new WC_Shipping_Zone( 0 ) )->get_shipping_methods(),
		);
		foreach ( $zones as $zone ) {
			$methods = array();
			foreach ( (array) $zone['shipping_methods'] as $method ) {
				$title = $method->get_title() ? $method->get_title() : $method->get_method_title();
				if ( 'yes' !== $method->enabled ) {
					$title .= ' ' . __( '(disabled)', 'track-orders-for-woocommerce' );
				}
				$methods[ (int) $method->get_instance_id() ] = $title;
			}
			$list[] = array(
				'name'    => $zone['zone_name'],
				'methods' => $methods,
			);
		}
		return $list;
	}

	/**
	 * Save the settings form.
	 *
	 * @since 1.3.0
	 * @return void
	 */
	public function wps_tofw_edd_save_settings() {
		if ( ! isset( $_POST['wps_tofw_edd_save'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		check_admin_referer( 'wps_tofw_edd_save_settings', 'wps_tofw_edd_nonce' );

		$post = wp_unslash( $_POST );

		$settings = array(
			'enable'           => isset( $post['wps_tofw_edd_enable'] ) ? 'on' : 'off',
			'show_product'     => isset( $post['wps_tofw_edd_show_product'] ) ? 'on' : 'off',
			'show_cart'        => isset( $post['wps_tofw_edd_show_cart'] ) ? 'on' : 'off',
			'show_email'       => isset( $post['wps_tofw_edd_show_email'] ) ? 'on' : 'off',
			'show_tracking'    => isset( $post['wps_tofw_edd_show_tracking'] ) ? 'on' : 'off',
			'label'            => isset( $post['wps_tofw_edd_label'] ) ? sanitize_text_field( $post['wps_tofw_edd_label'] ) : '',
			'date_format'      => isset( $post['wps_tofw_edd_date_format'] ) ? sanitize_text_field( $post['wps_tofw_edd_date_format'] ) : 'D, M j',
			'processing_days'  => isset( $post['wps_tofw_edd_processing_days'] ) ? absint( $post['wps_tofw_edd_processing_days'] ) : 0,
			'transit_min'      => isset( $post['wps_tofw_edd_transit_min'] ) ? absint( $post['wps_tofw_edd_transit_min'] ) : 0,
			'transit_max'      => isset( $post['wps_tofw_edd_transit_max'] ) ? absint( $post['wps_tofw_edd_transit_max'] ) : 0,
			'cutoff_time'      => '',
			'non_working_days' => array(),
			'holidays'         => array(),
			'methods'          => array(),
		);

		$cutoff = isset( $post['wps_tofw_edd_cutoff_time'] ) ? sanitize_text_field( $post['wps_tofw_edd_cutoff_time'] ) : '';
		if ( preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $cutoff ) ) {
			$settings['cutoff_time'] = $cutoff;
		}

		if ( ! empty( $post['wps_tofw_edd_non_working_days'] ) && is_array( $post['wps_tofw_edd_non_working_days'] ) ) {
			foreach ( $post['wps_tofw_edd_non_working_days'] as $day ) {
				$day = absint( $day );
				if ( $day <= 6 ) {
					$settings['non_working_days'][] = $day;
				}
			}
			$settings['non_working_days'] = array_values( array_unique( $settings['non_working_days'] ) );
		}

		if ( isset( $post['wps_tofw_edd_holidays'] ) ) {
			$lines = preg_split( '/[\r\n,]+/', sanitize_textarea_field( $post['wps_tofw_edd_holidays'] ) );
			foreach ( $lines as $line ) {
				$line = trim( $line );
				$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $line );
				if ( $date && $date->format( 'Y-m-d' ) === $line ) {
					$settings['holidays'][] = $line;
				}
			}
			$settings['holidays'] = array_values( array_unique( $settings['holidays'] ) );
			sort( $settings['holidays'] );
		}

		if ( ! empty( $post['wps_tofw_edd_methods'] ) && is_array( $post['wps_tofw_edd_methods'] ) ) {
			foreach ( $post['wps_tofw_edd_methods'] as $instance_id => $row ) {
				$instance_id = absint( $instance_id );
				if ( ! $instance_id || ! is_array( $row ) ) {
					continue;
				}
				$clean = array(
					'processing' => ( isset( $row['processing'] ) && '' !== trim( $row['processing'] ) ) ? absint( $row['processing'] ) : '',
					'min'        => ( isset( $row['min'] ) && '' !== trim( $row['min'] ) ) ? absint( $row['min'] ) : '',
					'max'        => ( isset( $row['max'] ) && '' !== trim( $row['max'] ) ) ? absint( $row['max'] ) : '',
					'hide'       => ! empty( $row['hide'] ) ? 1 : 0,
				);
				if ( '' === $clean['processing'] && '' === $clean['min'] && '' === $clean['max'] && ! $clean['hide'] ) {
					continue;
				}
				$settings['methods'][ $instance_id ] = $clean;
			}
		}

		update_option( self::OPTION, $settings );
		self::$settings_saved = true;
	}
}
