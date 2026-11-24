<?php
/**
 * WPS-8404: carrier status mapping and normalized shipment status.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\ShipmentInsights;

use Brain\Monkey\Filters;

/**
 * Carrier status => shipment status mapping and order shipment status resolution.
 */
class ShipmentStatusTest extends InsightsTestCase {

	/**
	 * Carrier statuses and the shipment status they map to.
	 *
	 * @return array
	 */
	public function carrier_status_provider() {
		return array(
			'failed attempt is an exception' => array( 'failed_attempt', 'exception' ),
			'expired is an exception'        => array( 'expired', 'exception' ),
			'exception'                      => array( 'exception', 'exception' ),
			'pickup counts as out for delivery' => array( 'available_for_pickup', 'out_for_delivery' ),
			'out for delivery'               => array( 'out_for_delivery', 'out_for_delivery' ),
			'in transit'                     => array( 'in_transit', 'in_transit' ),
			'delivered'                      => array( 'delivered', 'delivered' ),
			'info received is not shipped'   => array( 'info_received', 'not_shipped' ),
			'pending is not shipped'         => array( 'pending', 'not_shipped' ),
			'dashes and case are normalized' => array( ' Failed-Attempt ', 'exception' ),
			'spaces are normalized'          => array( 'Out For Delivery', 'out_for_delivery' ),
			'unknown status'                 => array( 'teleported', '' ),
			'empty status'                   => array( '', '' ),
		);
	}

	/**
	 * Carrier sync statuses are normalized to the shipment statuses used by alerts/analytics.
	 *
	 * @dataProvider carrier_status_provider
	 * @param string $carrier  Carrier status.
	 * @param string $expected Shipment status.
	 */
	public function test_map_carrier_status( $carrier, $expected ) {
		$this->assertSame( $expected, $this->insights->wps_tofw_map_carrier_status( $carrier ) );
	}

	/**
	 * The map can be extended with the wps_tofw_carrier_status_map filter.
	 */
	public function test_carrier_status_map_is_filterable() {
		Filters\expectApplied( 'wps_tofw_carrier_status_map' )->once()->andReturnUsing(
			function ( $map ) {
				$map['held_at_customs'] = 'exception';
				return $map;
			}
		);
		$this->assertSame( 'exception', $this->insights->wps_tofw_map_carrier_status( 'held at customs' ) );
	}

	/**
	 * Cancelled / refunded / failed orders are "cancelled".
	 */
	public function test_cancelled_like_orders() {
		foreach ( array( 'cancelled', 'refunded', 'failed' ) as $i => $status ) {
			$order = $this->order( array( 'id' => 10 + $i, 'status' => $status ) );
			$this->assertSame( 'cancelled', $this->insights->wps_tofw_get_shipment_status( $order ), $status );
		}
	}

	/**
	 * A configured exception order status wins, even over "failed".
	 */
	public function test_configured_exception_order_status() {
		$this->setOptions( array( 'wps_tofw_delivery_alerts_exception_statuses' => array( 'wc-failed', 'wc-delivery-failed' ) ) );

		$this->assertSame( 'exception', $this->insights->wps_tofw_get_shipment_status( $this->order( array( 'id' => 1, 'status' => 'failed' ) ) ) );
		$this->assertSame( 'exception', $this->insights->wps_tofw_get_shipment_status( $this->order( array( 'id' => 2, 'status' => 'delivery-failed' ) ) ) );
	}

	/**
	 * Carrier status meta is used before the order status (expired carrier => exception on a completed order).
	 */
	public function test_carrier_status_meta_takes_priority() {
		$order = $this->order(
			array(
				'status' => 'completed',
				'meta'   => array( 'wps_tofw_carrier_status' => 'expired' ),
			)
		);
		$this->assertSame( 'exception', $this->insights->wps_tofw_get_shipment_status( $order ) );

		$order = $this->order(
			array(
				'id'     => 2,
				'status' => 'processing',
				'meta'   => array( 'wps_tofw_carrier_status' => 'out_for_delivery' ),
			)
		);
		$this->assertSame( 'out_for_delivery', $this->insights->wps_tofw_get_shipment_status( $order ) );
	}

	/**
	 * Completed orders without carrier data are delivered.
	 */
	public function test_completed_is_delivered() {
		$this->assertSame( 'delivered', $this->insights->wps_tofw_get_shipment_status( $this->order( array( 'status' => 'completed' ) ) ) );
	}

	/**
	 * "Label created" carrier status keeps a tracked order as not shipped.
	 */
	public function test_info_received_carrier_status_is_not_shipped() {
		$order = $this->order(
			array(
				'meta' => array(
					'wps_tofw_carrier_status'          => 'info_received',
					'wps_tofw_package_tracking_number' => 'TRK1',
				),
			)
		);
		$this->assertSame( 'not_shipped', $this->insights->wps_tofw_get_shipment_status( $order ) );
	}

	/**
	 * A tracking number, a shipped timestamp or a shipping order status mean in transit.
	 */
	public function test_in_transit_signals() {
		$this->setOptions( array( 'wps_tofw_order_status_in_shipping' => array( 'wc-dispatched' ) ) );

		$tracking = $this->order( array( 'id' => 1, 'meta' => array( 'wps_tofw_package_tracking_number' => 'TRK1' ) ) );
		$shipped  = $this->order( array( 'id' => 2, 'meta' => array( 'wps_tofw_shipped_at' => time() - 100 ) ) );
		$status   = $this->order( array( 'id' => 3, 'status' => 'dispatched' ) );
		$nothing  = $this->order( array( 'id' => 4, 'status' => 'processing' ) );

		$this->assertSame( 'in_transit', $this->insights->wps_tofw_get_shipment_status( $tracking ) );
		$this->assertSame( 'in_transit', $this->insights->wps_tofw_get_shipment_status( $shipped ) );
		$this->assertSame( 'in_transit', $this->insights->wps_tofw_get_shipment_status( $status ) );
		$this->assertSame( 'not_shipped', $this->insights->wps_tofw_get_shipment_status( $nothing ) );
	}

	/**
	 * A filter returning an unknown status falls back to not_shipped.
	 */
	public function test_invalid_filtered_status_falls_back() {
		Filters\expectApplied( 'wps_tofw_shipment_status' )->once()->andReturn( '<script>' );
		$this->assertSame( 'not_shipped', $this->insights->wps_tofw_get_shipment_status( $this->order( array( 'status' => 'completed' ) ) ) );
	}

	/**
	 * All six statuses shown in the "Orders by shipment status" chart have labels.
	 */
	public function test_status_labels() {
		$this->assertSame(
			array( 'not_shipped', 'in_transit', 'out_for_delivery', 'delivered', 'exception', 'cancelled' ),
			array_keys( $this->insights->wps_tofw_shipment_status_labels() )
		);
	}
}
