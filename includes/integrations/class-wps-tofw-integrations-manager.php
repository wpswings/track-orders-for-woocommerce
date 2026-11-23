<?php
/**
 * Registers the shipping-plugin tracking auto import integrations.
 *
 * @link       https://wpswings.com/
 * @since      1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/includes/integrations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WPS_TOFW_Integrations_Manager' ) ) {

	/**
	 * Integrations manager.
	 *
	 * Owns the shared listeners (order notes, order meta), the carrier
	 * mapping, de-duplication and the settings rows.
	 *
	 * @since 1.3.0
	 */
	class WPS_TOFW_Integrations_Manager {

		/**
		 * Order meta holding tracking numbers already imported (de-duplication).
		 */
		const IMPORTED_META = 'wps_tofw_auto_imported_tracking_numbers';

		/**
		 * Option holding carriers seen by the auto import (key => label).
		 */
		const SEEN_CARRIERS_OPTION = 'wps_tofw_auto_import_seen_carriers';

		/**
		 * Integration instances keyed by ID.
		 *
		 * @var WPS_TOFW_Shipping_Integration_Interface[]|null
		 */
		protected $integrations = null;

		/**
		 * True while this manager is saving, to ignore events it triggers itself.
		 *
		 * @var bool
		 */
		protected $importing = false;

		/**
		 * Comment IDs already handled in this request.
		 *
		 * @var int[]
		 */
		protected $handled_notes = array();

		/**
		 * Get all registered integrations.
		 *
		 * @return WPS_TOFW_Shipping_Integration_Interface[]
		 */
		public function get_integrations() {
			if ( null !== $this->integrations ) {
				return $this->integrations;
			}

			$classes = array(
				'WPS_TOFW_Integration_ShipStation',
				'WPS_TOFW_Integration_WooCommerce_Shipping',
				'WPS_TOFW_Integration_Shipment_Tracking',
				'WPS_TOFW_Integration_Shippo',
				'WPS_TOFW_Integration_Sendcloud',
				'WPS_TOFW_Integration_Printful',
				'WPS_TOFW_Integration_Printify',
			);

			/**
			 * Filter the integration classes. Each class must implement
			 * WPS_TOFW_Shipping_Integration_Interface and accept the manager in
			 * its constructor.
			 *
			 * @since 1.3.0
			 * @param string[] $classes Class names.
			 */
			$classes = apply_filters( 'wps_tofw_shipping_integration_classes', $classes );

			$this->integrations = array();
			foreach ( (array) $classes as $class ) {
				if ( ! is_string( $class ) || ! class_exists( $class ) ) {
					continue;
				}
				$integration = new $class( $this );
				if ( $integration instanceof WPS_TOFW_Shipping_Integration_Interface ) {
					$this->integrations[ $integration->get_id() ] = $integration;
				}
			}
			return $this->integrations;
		}

		/**
		 * Enabled integrations whose plugin is active.
		 *
		 * @return WPS_TOFW_Shipping_Integration_Interface[]
		 */
		public function get_active_integrations() {
			$active = array();
			foreach ( $this->get_integrations() as $id => $integration ) {
				if ( $integration->is_enabled() && $integration->is_plugin_active() ) {
					$active[ $id ] = $integration;
				}
			}
			return $active;
		}

		/**
		 * Hook listeners for enabled integrations. Runs on "init".
		 *
		 * @return void
		 */
		public function init() {
			$active = $this->get_active_integrations();
			if ( empty( $active ) ) {
				return;
			}

			$has_meta = false;
			foreach ( $active as $integration ) {
				$integration->register_hooks();
				if ( $integration->get_watched_meta_keys() ) {
					$has_meta = true;
				}
			}

			// Order note fallback (all integrations without a dedicated hook).
			add_action( 'woocommerce_order_note_added', array( $this, 'handle_order_note_added' ), 20, 2 );
			add_action( 'wp_insert_comment', array( $this, 'handle_insert_comment' ), 20, 2 );

			if ( $has_meta ) {
				// CRUD saves (HPOS and posts storage).
				add_action( 'woocommerce_after_order_object_save', array( $this, 'handle_order_saved' ), 20, 1 );
				// Direct update_post_meta() calls (posts storage).
				add_action( 'added_post_meta', array( $this, 'handle_post_meta' ), 20, 4 );
				add_action( 'updated_post_meta', array( $this, 'handle_post_meta' ), 20, 4 );
			}
		}

		/**
		 * Handler for woocommerce_order_note_added.
		 *
		 * @param int      $comment_id Note ID.
		 * @param WC_Order $order      Order.
		 * @return void
		 */
		public function handle_order_note_added( $comment_id, $order ) {
			$comment = get_comment( $comment_id );
			if ( $comment ) {
				$this->process_note( $comment, $order );
			}
		}

		/**
		 * Handler for wp_insert_comment (fallback for notes inserted without the WC action).
		 *
		 * @param int        $comment_id Comment ID.
		 * @param WP_Comment $comment    Comment.
		 * @return void
		 */
		public function handle_insert_comment( $comment_id, $comment ) {
			if ( ! $comment || 'order_note' !== $comment->comment_type ) {
				return;
			}
			$this->process_note( $comment, null );
		}

		/**
		 * Parse an order note with every active integration.
		 *
		 * @param WP_Comment    $comment Note.
		 * @param WC_Order|null $order   Order if known.
		 * @return void
		 */
		protected function process_note( $comment, $order ) {
			if ( $this->importing || in_array( (int) $comment->comment_ID, $this->handled_notes, true ) ) {
				return;
			}
			$this->handled_notes[] = (int) $comment->comment_ID;

			foreach ( $this->get_active_integrations() as $id => $integration ) {
				$shipment = $integration->extract_from_note( $comment->comment_content );
				if ( empty( $shipment['tracking_number'] ) ) {
					continue;
				}
				$order = ( $order instanceof WC_Order ) ? $order : wc_get_order( $comment->comment_post_ID );
				if ( ! $order ) {
					return;
				}
				$this->import_tracking( $order, $shipment['tracking_number'], isset( $shipment['carrier'] ) ? $shipment['carrier'] : '', $id );
				return; // One integration per note.
			}
		}

		/**
		 * Handler for woocommerce_after_order_object_save.
		 *
		 * @param WC_Order $order Order.
		 * @return void
		 */
		public function handle_order_saved( $order ) {
			if ( $this->importing || ! ( $order instanceof WC_Order ) || 'shop_order_refund' === $order->get_type() ) {
				return;
			}
			foreach ( $this->get_active_integrations() as $integration ) {
				foreach ( $integration->get_watched_meta_keys() as $meta_key ) {
					$value = $order->get_meta( $meta_key, true );
					if ( ! empty( $value ) ) {
						$this->import_meta_shipments( $order, $integration, $meta_key, $value );
					}
				}
			}
		}

		/**
		 * Handler for added_post_meta / updated_post_meta.
		 *
		 * @param int    $meta_id    Meta ID.
		 * @param int    $object_id  Post ID.
		 * @param string $meta_key   Meta key.
		 * @param mixed  $meta_value Meta value.
		 * @return void
		 */
		public function handle_post_meta( $meta_id, $object_id, $meta_key, $meta_value ) {
			if ( $this->importing || empty( $meta_value ) ) {
				return;
			}
			foreach ( $this->get_active_integrations() as $integration ) {
				if ( ! in_array( $meta_key, $integration->get_watched_meta_keys(), true ) ) {
					continue;
				}
				$order = wc_get_order( $object_id );
				if ( $order && 'shop_order_refund' !== $order->get_type() ) {
					$this->import_meta_shipments( $order, $integration, $meta_key, $meta_value );
				}
			}
		}

		/**
		 * Import every shipment found in a meta value.
		 *
		 * @param WC_Order                                $order       Order.
		 * @param WPS_TOFW_Shipping_Integration_Interface $integration Integration.
		 * @param string                                  $meta_key    Meta key.
		 * @param mixed                                   $meta_value  Meta value.
		 * @return void
		 */
		protected function import_meta_shipments( $order, $integration, $meta_key, $meta_value ) {
			$shipments = $integration->extract_from_meta( $meta_key, maybe_unserialize( $meta_value ) );
			foreach ( (array) $shipments as $shipment ) {
				if ( ! empty( $shipment['tracking_number'] ) ) {
					$this->import_tracking( $order, $shipment['tracking_number'], isset( $shipment['carrier'] ) ? $shipment['carrier'] : '', $integration->get_id() );
				}
			}
		}

		/**
		 * Save an imported tracking number through the manual tracking code path.
		 *
		 * @param WC_Order $order           Order.
		 * @param string   $tracking_number Tracking number.
		 * @param string   $carrier         Carrier name as reported by the integration.
		 * @param string   $source          Integration ID.
		 * @return bool True when saved.
		 */
		public function import_tracking( $order, $tracking_number, $carrier, $source ) {
			$tracking_number = strtoupper( preg_replace( '/\s+/', '', sanitize_text_field( (string) $tracking_number ) ) );
			$carrier         = sanitize_text_field( (string) $carrier );
			if ( '' === $tracking_number || ! ( $order instanceof WC_Order ) || $this->importing ) {
				return false;
			}

			// Cheap de-duplication on the object we were given (runs on every order save).
			if ( in_array( $tracking_number, array_map( 'strtoupper', (array) $order->get_meta( self::IMPORTED_META, true ) ), true ) ) {
				return false;
			}

			// Fresh instance so we never save over a stale object held by the caller.
			$order = wc_get_order( $order->get_id() );
			if ( ! $order ) {
				return false;
			}

			$imported = array_map( 'strtoupper', (array) $order->get_meta( self::IMPORTED_META, true ) );
			if ( in_array( $tracking_number, $imported, true ) ) {
				return false;
			}

			$data = array(
				'tracking_number' => $tracking_number,
				'carrier'         => $carrier,
				'service'         => $this->map_carrier( $carrier ),
				'source'          => $source,
			);

			/**
			 * Filter tracking data before it is saved. Return an empty value to skip.
			 *
			 * @since 1.3.0
			 * @param array    $data  tracking_number, carrier, service, source.
			 * @param WC_Order $order Order.
			 */
			$data = apply_filters( 'wps_tofw_auto_import_tracking_data', $data, $order );
			if ( empty( $data ) || empty( $data['tracking_number'] ) ) {
				return false;
			}

			$this->importing = true;
			try {
				$current = strtoupper( (string) $order->get_meta( 'wps_tofw_package_tracking_number', true ) );
				$saved   = false;
				if ( $current !== $data['tracking_number'] ) {
					$saved = WPS_TOFW_Tracking_Service::save_tracking(
						$order,
						$data['service'],
						$data['tracking_number'],
						array(
							'source'       => $data['source'],
							'carrier_name' => $data['carrier'],
						)
					);
				}

				$imported[] = $data['tracking_number'];
				$order->update_meta_data( self::IMPORTED_META, array_values( array_unique( $imported ) ) );
				if ( $saved ) {
					$order->update_meta_data( 'wps_tofw_auto_import_source', $data['source'] );
					$order->update_meta_data( 'wps_tofw_auto_import_carrier_name', $data['carrier'] );
					$this->remember_carrier( $data['service'], $data['carrier'] );

					$integrations = $this->get_integrations();
					$label        = isset( $integrations[ $data['source'] ] ) ? $integrations[ $data['source'] ]->get_label() : $data['source'];
					$order->add_order_note(
						sprintf(
							/* translators: 1: tracking number, 2: carrier, 3: integration name. */
							__( 'Track Orders: tracking number %1$s (%2$s) imported automatically from %3$s.', 'track-orders-for-woocommerce' ),
							$data['tracking_number'],
							'' !== $data['carrier'] ? $data['carrier'] : __( 'unknown carrier', 'track-orders-for-woocommerce' ),
							$label
						)
					);
				}
				$order->save();
			} finally {
				$this->importing = false;
			}

			return $saved;
		}

		/**
		 * Carrier map: normalised carrier name => shipping service key used by
		 * this plugin (see the "wps_tofw_add_diffrent_shipping_services" filter).
		 *
		 * @return array
		 */
		public function get_carrier_map() {
			$map = array(
				'fedex'                     => 'fedex',
				'federalexpress'            => 'fedex',
				'fdx'                       => 'fedex',
				'usps'                      => 'usps',
				'stampscom'                 => 'usps',
				'stamps'                    => 'usps',
				'endicia'                   => 'usps',
				'unitedstatespostalservice' => 'usps',
				'uspostalservice'           => 'usps',
				'canadapost'                => 'canada_post',
				'postescanada'              => 'canada_post',
				'ups'                       => 'ups',
				'dhl'                       => 'dhl',
				'royalmail'                 => 'royal_mail',
				'australiapost'             => 'australia_post',
				'auspost'                   => 'australia_post',
				'postnl'                    => 'postnl',
				'dpd'                       => 'dpd',
				'gls'                       => 'gls',
				'ontrac'                    => 'ontrac',
				'purolator'                 => 'purolator',
				'colissimo'                 => 'colissimo',
				'bpost'                     => 'bpost',
				'hermes'                    => 'evri',
				'evri'                      => 'evri',
				'parcelforce'               => 'parcelforce',
				'asendia'                   => 'asendia',
			);

			/**
			 * Filter the carrier name => shipping service key mapping.
			 * Keys are lower-case with all non alphanumeric characters removed.
			 *
			 * @since 1.3.0
			 * @param array $map Carrier map.
			 */
			return (array) apply_filters( 'wps_tofw_auto_import_carrier_map', $map );
		}

		/**
		 * Map a carrier name to a shipping service key.
		 *
		 * Exact normalised match first, then "starts with" (e.g. "FedEx Ground"
		 * => fedex, "USPS Priority Mail" => usps). Unknown carriers fall back to
		 * a sanitized slug of the name.
		 *
		 * @param string $carrier Carrier name.
		 * @return string
		 */
		public function map_carrier( $carrier ) {
			$normalised = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $carrier ) );
			if ( '' === $normalised ) {
				return '';
			}
			$map = $this->get_carrier_map();
			if ( isset( $map[ $normalised ] ) ) {
				return $map[ $normalised ];
			}
			// Longest key first so "dhlexpress" style keys win over "dhl".
			$keys = array_keys( $map );
			usort(
				$keys,
				function ( $a, $b ) {
					return strlen( $b ) - strlen( $a );
				}
			);
			foreach ( $keys as $key ) {
				if ( strlen( $key ) >= 3 && 0 === strpos( $normalised, (string) $key ) ) {
					return $map[ $key ];
				}
			}
			return str_replace( '-', '_', sanitize_title( $carrier ) );
		}

		/**
		 * Remember carriers seen by the auto import so they are selectable in
		 * the order metabox (otherwise saving the order would drop them).
		 *
		 * @param string $service Service key.
		 * @param string $carrier Carrier name.
		 * @return void
		 */
		protected function remember_carrier( $service, $carrier ) {
			if ( '' === $service ) {
				return;
			}
			$seen = (array) get_option( self::SEEN_CARRIERS_OPTION, array() );
			if ( ! isset( $seen[ $service ] ) ) {
				$seen[ $service ] = '' !== $carrier ? $carrier : $service;
				update_option( self::SEEN_CARRIERS_OPTION, $seen, false );
			}
		}

		/**
		 * Add carriers seen by the auto import to the metabox service list.
		 * Hooked to "wps_tofw_add_diffrent_shipping_services".
		 *
		 * @param array $services Services key => label.
		 * @return array
		 */
		public function add_seen_carriers_to_services( $services ) {
			$seen = (array) get_option( self::SEEN_CARRIERS_OPTION, array() );
			foreach ( $seen as $key => $label ) {
				if ( ! isset( $services[ $key ] ) ) {
					$services[ $key ] = $label;
				}
			}
			return $services;
		}

		/**
		 * Append one switch per integration to the Shipping Services settings.
		 * Hooked to "tofw_shipping_services_settings_array_filter".
		 *
		 * @param array $settings Settings rows.
		 * @return array
		 */
		public function add_settings( $settings ) {
			foreach ( $this->get_integrations() as $integration ) {
				$option     = method_exists( $integration, 'get_option_name' ) ? $integration->get_option_name() : 'wps_tofw_auto_import_' . $integration->get_id();
				$settings[] = array(
					'title'       => sprintf(
						/* translators: %s: integration name. */
						__( 'Auto-import tracking from %s', 'track-orders-for-woocommerce' ),
						$integration->get_label()
					),
					'type'        => 'radio-switch',
					'description' => $integration->get_status_text(),
					'id'          => $option,
					'value'       => get_option( $option, '' ),
					'class'       => 'tofw-radio-switch-class',
					'options'     => array(
						'yes' => __( 'YES', 'track-orders-for-woocommerce' ),
						'no'  => __( 'NO', 'track-orders-for-woocommerce' ),
					),
				);
			}
			return $settings;
		}
	}
}
