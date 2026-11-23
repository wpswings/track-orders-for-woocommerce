<?php
/**
 * Base class for shipping-plugin tracking auto import integrations.
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

if ( ! class_exists( 'WPS_TOFW_Abstract_Shipping_Integration' ) ) {

	/**
	 * Shared helpers: option handling, plugin detection and order note parsing.
	 *
	 * @since 1.3.0
	 */
	abstract class WPS_TOFW_Abstract_Shipping_Integration implements WPS_TOFW_Shipping_Integration_Interface {

		/**
		 * Integrations manager.
		 *
		 * @var WPS_TOFW_Integrations_Manager
		 */
		protected $manager;

		/**
		 * Constructor.
		 *
		 * @param WPS_TOFW_Integrations_Manager $manager Manager.
		 */
		public function __construct( $manager ) {
			$this->manager = $manager;
		}

		/**
		 * Option name holding the on/off switch.
		 *
		 * @return string
		 */
		public function get_option_name() {
			return 'wps_tofw_auto_import_' . $this->get_id();
		}

		/**
		 * Whether the merchant enabled this integration (default off).
		 *
		 * @return bool
		 */
		public function is_enabled() {
			return 'on' === get_option( $this->get_option_name(), '' );
		}

		/**
		 * Default: no specific hooks.
		 *
		 * @return void
		 */
		public function register_hooks() {}

		/**
		 * Default: no meta keys watched.
		 *
		 * @return string[]
		 */
		public function get_watched_meta_keys() {
			return array();
		}

		/**
		 * Default: nothing to extract from meta.
		 *
		 * @param string $meta_key   Meta key.
		 * @param mixed  $meta_value Meta value.
		 * @return array[]
		 */
		public function extract_from_meta( $meta_key, $meta_value ) {
			return array();
		}

		/**
		 * Status text shown under the setting.
		 *
		 * @return string
		 */
		public function get_status_text() {
			return $this->is_plugin_active()
				? __( 'Status: plugin detected and active.', 'track-orders-for-woocommerce' )
				: __( 'Status: plugin not detected / inactive. The setting has no effect until it is active.', 'track-orders-for-woocommerce' );
		}

		/**
		 * Regex that identifies an order note as written by this integration.
		 * Return an empty string to disable order note parsing.
		 *
		 * @return string
		 */
		protected function get_note_signature() {
			return '';
		}

		/**
		 * Regexes (named groups "tracking" and optionally "carrier") tried in
		 * order against the note. Subclasses may prepend more specific ones.
		 *
		 * @return string[]
		 */
		protected function get_note_patterns() {
			return array(
				// "... shipped via USPS on <date> with tracking number 9400..." (ShipStation style).
				'/via\s+(?P<carrier>[^\r\n]{2,60}?)\s+on\s+.{3,60}?\s+tracking\s+(?:number|no\.?|#|code)\s*[:#]?\s*(?P<tracking>[A-Z0-9][A-Z0-9\-]{5,39})/i',
				// "Carrier: UPS ... Tracking number: 1Z..." .
				'/carrier\s*[:\-]\s*(?P<carrier>[^\r\n,;|]{2,60}?)\s*(?:[,;|\r\n]|(?=tracking)).*?tracking\s*(?:number|no\.?|#|code|id)?\s*[:#\-]\s*(?P<tracking>[A-Z0-9][A-Z0-9\-]{5,39})/is',
				// "Tracking number: 1Z... (UPS)" / "tracking number 1Z... via UPS".
				'/tracking\s*(?:number|no\.?|#|code|id)?\s*(?:is|[:#\-])?\s*(?P<tracking>[A-Z0-9][A-Z0-9\-]{5,39})(?:\s*\((?P<carrier>[^)]{2,40})\)|\s+(?:via|with|by)\s+(?P<carrier2>[A-Za-z][A-Za-z0-9 &\.\-]{1,40}))?/i',
			);
		}

		/**
		 * Parse a note written by this integration.
		 *
		 * @param string $note Raw note content (may contain HTML links).
		 * @return array|null
		 */
		public function extract_from_note( $note ) {
			$signature = $this->get_note_signature();
			if ( '' === $signature || ! is_string( $note ) || '' === $note ) {
				return null;
			}

			// Keep link targets: tracking URLs often carry carrier + number.
			$hrefs = array();
			if ( preg_match_all( '/href=["\']([^"\']+)["\']/i', $note, $m ) ) {
				$hrefs = $m[1];
			}
			$text = trim( wp_strip_all_tags( $note ) . ' ' . implode( ' ', $hrefs ) );

			if ( ! preg_match( $signature, $text ) ) {
				return null;
			}

			$from_url = $this->extract_from_tracking_url( $text );
			if ( $from_url ) {
				return $from_url;
			}

			foreach ( $this->get_note_patterns() as $pattern ) {
				if ( preg_match( $pattern, $text, $match ) ) {
					$tracking = isset( $match['tracking'] ) ? $match['tracking'] : '';
					if ( ! $this->looks_like_tracking_number( $tracking ) ) {
						continue;
					}
					$carrier = '';
					if ( ! empty( $match['carrier'] ) ) {
						$carrier = $match['carrier'];
					} elseif ( ! empty( $match['carrier2'] ) ) {
						$carrier = $match['carrier2'];
					}
					return array(
						'tracking_number' => $tracking,
						'carrier'         => trim( $carrier, " \t\n\r\0\x0B.,;:" ),
					);
				}
			}

			return null;
		}

		/**
		 * Extract carrier + number from a known tracking URL in the note.
		 *
		 * @param string $text Note text including hrefs.
		 * @return array|null
		 */
		protected function extract_from_tracking_url( $text ) {
			$patterns = array(
				'USPS'        => '#usps\.com/[^\s"\']*?(?:tLabels|qtc_tLabels1|tRef=fullpage&tLabels)=(?P<tracking>[A-Z0-9]{8,40})#i',
				'FedEx'       => '#fedex\.com/[^\s"\']*?(?:trknbr|tracknumbers|trackingnumber)=(?P<tracking>[A-Z0-9]{8,40})#i',
				'UPS'         => '#ups\.com/[^\s"\']*?(?:tracknum|trackNums|InquiryNumber1)=(?P<tracking>[A-Z0-9]{8,40})#i',
				'DHL'         => '#dhl\.[a-z.]+/[^\s"\']*?(?:AWB|tracking-id|trackingnumber|id)=(?P<tracking>[A-Z0-9]{8,40})#i',
				'Canada Post' => '#canadapost(?:-postescanada)?\.ca/[^\s"\']*?(?:trackingNumber|pin)[=/](?P<tracking>[A-Z0-9]{8,40})#i',
			);
			foreach ( $patterns as $carrier => $pattern ) {
				if ( preg_match( $pattern, $text, $m ) && $this->looks_like_tracking_number( $m['tracking'] ) ) {
					return array(
						'tracking_number' => $m['tracking'],
						'carrier'         => $carrier,
					);
				}
			}
			return null;
		}

		/**
		 * Basic sanity check so words like "available" are not imported.
		 *
		 * @param string $value Candidate.
		 * @return bool
		 */
		protected function looks_like_tracking_number( $value ) {
			$value = (string) $value;
			return strlen( $value ) >= 6 && strlen( $value ) <= 40 && (bool) preg_match( '/\d/', $value );
		}

		/**
		 * Check if any of the given plugin files is active (single or network).
		 *
		 * @param string[] $plugin_files Plugin basenames.
		 * @return bool
		 */
		protected function is_any_plugin_file_active( $plugin_files ) {
			$active = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
			}
			return (bool) array_intersect( $plugin_files, $active );
		}

		/**
		 * Check if any of the given plugin folders has an active plugin (handles renamed main files).
		 *
		 * @param string[] $folders Plugin folder names.
		 * @return bool
		 */
		protected function is_any_plugin_folder_active( $folders ) {
			$active = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
			}
			foreach ( $active as $plugin ) {
				$folder = strtok( (string) $plugin, '/' );
				if ( in_array( $folder, $folders, true ) ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Whether a WooCommerce REST API key whose description contains the
		 * given app name exists (apps such as Printify / Shippo connect via
		 * the WooCommerce REST API instead of a WordPress plugin).
		 *
		 * @param string $app_name App name.
		 * @return bool
		 */
		protected function has_rest_api_key_for( $app_name ) {
			global $wpdb;
			$cache_key = 'wps_tofw_api_key_' . sanitize_key( $app_name );
			$found     = wp_cache_get( $cache_key, 'wps_tofw' );
			if ( false === $found ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$found = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_api_keys WHERE description LIKE %s",
						'%' . $wpdb->esc_like( $app_name ) . '%'
					)
				);
				wp_cache_set( $cache_key, $found, 'wps_tofw', HOUR_IN_SECONDS );
			}
			return $found > 0;
		}
	}
}
