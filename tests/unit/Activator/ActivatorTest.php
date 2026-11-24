<?php
/**
 * Example: unit testing a plugin class loaded on its own (no WordPress).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Activator;

use Brain\Monkey\Functions;
use Tests\Unit\TestCase;

/**
 * Tests for Track_Orders_For_Woocommerce_Activator.
 */
class ActivatorTest extends TestCase {

	/**
	 * Load the class under test.
	 */
	protected function set_up() {
		parent::set_up();
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-activator.php' );
	}

	/**
	 * Activation creates the three tracking pages and stores their IDs.
	 */
	public function test_activation_creates_tracking_pages() {
		$this->setOptions( array( 'admin_email' => 'admin@example.com' ) );
		$this->stubCommonWpFunctions();

		Functions\when( 'get_user_by' )->justReturn( (object) array( 'ID' => 3 ) );
		Functions\when( 'get_page_by_path' )->justReturn( null );

		$next_id = 100;
		Functions\expect( 'wp_insert_post' )
			->times( 3 )
			->andReturnUsing(
				function ( $post ) use ( &$next_id ) {
					$this->assertSame( 3, $post['post_author'] );
					$this->assertSame( 'publish', $post['post_status'] );
					return $next_id++;
				}
			);

		\Track_Orders_For_Woocommerce_Activator::track_orders_for_woocommerce_activate();

		$pages = $this->getOption( 'wps_tofw_tracking_page' );
		$this->assertSame(
			array(
				'wps_track_order_page'       => 100,
				'wps_guest_track_order_page' => 101,
				'wps_fedex_track_order'      => 102,
			),
			$pages['pages']
		);
		$this->assertSame( 'template_1', $this->getOption( 'tofw_invoice_template' ) );
	}

	/**
	 * Existing pages are reused instead of being created again.
	 */
	public function test_activation_reuses_existing_pages() {
		$this->setOptions();
		Functions\when( 'get_user_by' )->justReturn( false );
		Functions\when( 'get_page_by_path' )->justReturn( (object) array( 'ID' => 55 ) );
		Functions\expect( 'wp_insert_post' )->never();

		\Track_Orders_For_Woocommerce_Activator::track_orders_for_woocommerce_activate();

		$pages = $this->getOption( 'wps_tofw_tracking_page' );
		$this->assertSame( 55, $pages['pages']['wps_guest_track_order_page'] );
	}
}
