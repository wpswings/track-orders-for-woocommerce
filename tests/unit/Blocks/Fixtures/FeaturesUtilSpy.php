<?php
/**
 * Spy replacement for WooCommerce's FeaturesUtil, loaded only in an isolated test process.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Automattic\WooCommerce\Utilities;

if ( ! class_exists( __NAMESPACE__ . '\FeaturesUtil', false ) ) {
	/**
	 * Records declare_compatibility() calls.
	 */
	class FeaturesUtil {

		/**
		 * Recorded calls: [ feature_id, plugin_file, positive_compatibility ].
		 *
		 * @var array
		 */
		public static $declared = array();

		/**
		 * Record a compatibility declaration.
		 *
		 * @param string $feature_id             Feature id.
		 * @param string $plugin_file            Plugin main file.
		 * @param bool   $positive_compatibility Compatible or not.
		 * @return bool
		 */
		public static function declare_compatibility( $feature_id, $plugin_file, $positive_compatibility = true ) {
			self::$declared[] = array( $feature_id, $plugin_file, $positive_compatibility );
			return true;
		}
	}
}
