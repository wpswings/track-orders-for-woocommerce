<?php
/**
 * Carrier mapping of auto-imported tracking (WPS-8398).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\AutoImport;

use Brain\Monkey\Filters;
use WPS_TOFW_Integrations_Manager;

/**
 * WPS_TOFW_Integrations_Manager::map_carrier() and the "seen carriers" service list.
 */
class CarrierMappingTest extends AutoImportTestCase {

	/**
	 * Manager under test.
	 *
	 * @var WPS_TOFW_Integrations_Manager
	 */
	private $manager;

	/**
	 * Create the manager.
	 */
	protected function set_up() {
		parent::set_up();
		$this->manager = new WPS_TOFW_Integrations_Manager();
	}

	/**
	 * Carrier names as shipping plugins send them.
	 *
	 * @return array
	 */
	public function carrier_names() {
		return array(
			'FedEx'                        => array( 'FedEx', 'fedex' ),
			'FedEx service name'           => array( 'FedEx Ground', 'fedex' ),
			'Federal Express'              => array( 'Federal Express', 'fedex' ),
			'USPS lowercase'               => array( 'usps', 'usps' ),
			'Stamps.com (ShipStation)'     => array( 'Stamps.com', 'usps' ),
			'USPS long name'               => array( 'United States Postal Service', 'usps' ),
			'Canada Post'                  => array( 'Canada Post', 'canada_post' ),
			'Postes Canada'                => array( 'Postes Canada', 'canada_post' ),
			'UPS'                          => array( 'UPS', 'ups' ),
			'UPS service'                  => array( 'UPS® Ground', 'ups' ),
			'DHL Express prefix'           => array( 'DHL Express', 'dhl' ),
			'Royal Mail'                   => array( 'Royal Mail', 'royal_mail' ),
			'Hermes is Evri'               => array( 'Hermes', 'evri' ),
			'Australia Post short'         => array( 'AusPost', 'australia_post' ),
			'unknown carrier is slugified' => array( 'Acme Freight', 'acme_freight' ),
			'empty'                        => array( '', '' ),
			'punctuation only'             => array( ' - ', '' ),
		);
	}

	/**
	 * Known carrier names map to plugin service keys; unknown ones get a slug.
	 *
	 * @dataProvider carrier_names
	 *
	 * @param string $carrier  Carrier name.
	 * @param string $expected Service key.
	 */
	public function test_map_carrier( $carrier, $expected ) {
		$this->assertSame( $expected, $this->manager->map_carrier( $carrier ) );
	}

	/**
	 * Short keys must not swallow unrelated carriers by prefix (keys need >= 3 chars).
	 */
	public function test_short_prefixes_do_not_match() {
		$this->assertSame( 'up', $this->manager->map_carrier( 'UP' ) );
	}

	/**
	 * The carrier map can be extended with the wps_tofw_auto_import_carrier_map filter.
	 */
	public function test_carrier_map_is_filterable() {
		Filters\expectApplied( 'wps_tofw_auto_import_carrier_map' )->andReturnUsing(
			static function ( $map ) {
				$map['acmefreight'] = 'acme';
				return $map;
			}
		);

		$this->assertSame( 'acme', $this->manager->map_carrier( 'ACME Freight' ) );
	}

	/**
	 * Carriers seen during imports are offered in the shipping service list without
	 * overriding the plugin's own labels.
	 */
	public function test_seen_carriers_are_added_to_services() {
		$this->setOptions(
			array(
				WPS_TOFW_Integrations_Manager::SEEN_CARRIERS_OPTION => array(
					'acme_freight' => 'Acme Freight',
					'fedex'        => 'fedex lowercase',
				),
			)
		);

		$services = $this->manager->add_seen_carriers_to_services( array( 'fedex' => 'FedEx' ) );

		$this->assertSame(
			array(
				'fedex'        => 'FedEx',
				'acme_freight' => 'Acme Freight',
			),
			$services
		);
	}

	/**
	 * No seen carriers: service list unchanged.
	 */
	public function test_services_unchanged_without_seen_carriers() {
		$this->assertSame( array( 'usps' => 'USPS' ), $this->manager->add_seen_carriers_to_services( array( 'usps' => 'USPS' ) ) );
	}
}
