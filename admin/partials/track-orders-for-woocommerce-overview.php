<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the html for system status.
 *
 * @link       https://wpswings.com/
 * @since      1.0.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wps-overview__wrapper">
	<div class="wps-overview__banner">
		<img src="<?php echo esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ); ?>admin/image/Plugin_banner_image_Track Order_ for_WooCommerce.jpg" alt="Overview banner image">
	</div>
	<div class="wps-overview__content">
		<div class="wps-overview__content-description">
			<h2><?php echo esc_html_e( 'What Is Track Orders For Woocommerce?', 'track-orders-for-woocommerce' ); ?></h2>
			<p>
				<?php
				esc_html_e(
					'The "Track Orders for WooCommerce" plugin is a versatile tool that enhances the order-tracking functionality of your store. It provides a visually appealing interface for customers to track their orders, and the unique tracking ID is enough to show the shipping timeline to users. You can also utilize custom order statuses to match specific business requirements. Also, provide Google tracking functionality to your customers.',
					'track-orders-for-woocommerce'
				);
				?>
			</p>
			<h3><?php esc_html_e( 'As a store owner, you get to:', 'track-orders-for-woocommerce' ); ?></h3>
			<ul class="wps-overview__features">
				<li><?php esc_html_e( 'Track Order With 3 Different Templates.', 'track-orders-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Icon-Based Order Status in the Order Table.', 'track-orders-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Order Export as a CSV File.', 'track-orders-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Add Custom Order Status.', 'track-orders-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Third-party shipment tracking (FedEx).', 'track-orders-for-woocommerce' ); ?></li>
			</ul>
		</div>
		<h2> <?php esc_html_e( 'The Free Plugin Benefits', 'track-orders-for-woocommerce' ); ?></h2>
		<div class="wps-overview__keywords">
			<div class="wps-overview__keywords-item">
				<div class="wps-overview__keywords-card">
					<div class="wps-overview__keywords-image">
						<img src="<?php echo esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/OT_track_order_with_3_different_templates.svg' ); ?>" alt="Advanced-report image">
					</div>
					<div class="wps-overview__keywords-text">
						<h3 class="wps-overview__keywords-heading"><?php echo esc_html_e( ' Three Different Order Tracking Templates ', 'track-orders-for-woocommerce' ); ?></h3>
						<p class="wps-overview__keywords-description">
							<?php
							esc_html_e( 'Make order tracking more engaging with various templates that elevate the design of your tracking interface. Customers will stay informed and entertained while following their shipment.  ', 'track-orders-for-woocommerce' );
							?>
						</p>
					</div>
				</div>
			</div>
			<div class="wps-overview__keywords-item">
				<div class="wps-overview__keywords-card">
					<div class="wps-overview__keywords-image">
						<img src="<?php echo esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/OT_Show_Icon_Instead_of_Text.svg' ); ?>" alt="Workflow image">
					</div>
					<div class="wps-overview__keywords-text">
						<h3 class="wps-overview__keywords-heading"><?php echo esc_html_e( ' Icon-Based Order Status in the Order Table ', 'track-orders-for-woocommerce' ); ?></h3>
						<p class="wps-overview__keywords-description">
							<?php
							esc_html_e( 'The order details are updated in a tabular format, offering a standard & organized view. You can customize the order status text and replace it with brand-uniform icons for faster visual input. ', 'track-orders-for-woocommerce' );
							?>
						</p>
					</div>
				</div>
			</div>
			<div class="wps-overview__keywords-item">
				<div class="wps-overview__keywords-card">
					<div class="wps-overview__keywords-image">
						<img src="<?php echo esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/OT_Order_Export_as_Csv_File.svg' ); ?>" alt="Variable product image">
					</div>
					<div class="wps-overview__keywords-text">
						<h3 class="wps-overview__keywords-heading"><?php echo esc_html_e( ' Order Export as a CSV File ', 'track-orders-for-woocommerce' ); ?></h3>
						<p class="wps-overview__keywords-description">
							<?php
							esc_html_e( 'Your customers & guest users can export WooCommerce order details in a CSV file and easily examine order history. This user-friendly feature lets users gather key order details. ', 'track-orders-for-woocommerce' );
							?>
						</p>
					</div>
				</div>
			</div>
			<div class="wps-overview__keywords-item">
				<div class="wps-overview__keywords-card">
					<div class="wps-overview__keywords-image">
						<img src="<?php echo esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/OT_Third-party_Shipment_Tracking.svg' ); ?>" alt="Variable product image">
					</div>
					<div class="wps-overview__keywords-text">
						<h3 class="wps-overview__keywords-heading"><?php echo esc_html_e( ' Third-party shipment tracking (FedEx) ', 'track-orders-for-woocommerce' ); ?></h3>
						<p class="wps-overview__keywords-description">
							<?php
							esc_html_e( 'Customers can enter their FedEx tracking number or other relevant shipment details and use the FedEx tracking system to retrieve real-time updates on the packages location and status. ', 'track-orders-for-woocommerce' );
							?>
						</p>
					</div>
				</div>
			</div>
			<div class="wps-overview__keywords-item">
				<div class="wps-overview__keywords-card">
					<div class="wps-overview__keywords-image">
						<img src="<?php echo esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/OT_Create_and_Use_Own_Custom_Order_Status.svg' ); ?>" alt="Variable product image">
					</div>
					<div class="wps-overview__keywords-text">
						<h3 class="wps-overview__keywords-heading"><?php echo esc_html_e( ' Add Custom Order Status ', 'track-orders-for-woocommerce' ); ?></h3>
						<p class="wps-overview__keywords-description">
							<?php
							esc_html_e( 'Create and assign unique labels to orders based on your specific business requirements. By adding custom order statuses, you gain greater flexibility and control over order management. ', 'track-orders-for-woocommerce' );
							?>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
