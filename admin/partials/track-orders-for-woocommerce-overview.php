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
		<img src="<?php echo esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL ); ?>admin/image/banner.jpg" alt="Overview banner image">
	</div>
	<div class="wps-overview__content">
		<div class="wps-overview__content-description">
			<h2><?php echo esc_html_e( 'What Is wps Standard Plugin?', 'track-orders-for-woocommerce' ); ?></h2>
			<p>
				<?php
				esc_html_e(
					'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industrys standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.                '
				);
				?>
			</p>
			<h3><?php esc_html_e( 'As a store owner, you get to:', 'track-orders-for-woocommerce' ); ?></h3>
			<ul class="wps-overview__features">
				<li><?php esc_html_e( 'Lorem Ipsum is simply dummy text.', 'track-orders-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Lorem Ipsum is simply dummy text.', 'track-orders-for-woocommerce' ); ?></li>
			</ul>
		</div>
		<h2> <?php esc_html_e( 'The Free Plugin Benefits', 'track-orders-for-woocommerce' ); ?></h2>
		<div class="wps-overview__keywords">
			<div class="wps-overview__keywords-item">
				<div class="wps-overview__keywords-card">
					<div class="wps-overview__keywords-image">
						<img src="<?php echo esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/Advanced-report.png' ); ?>" alt="Advanced-report image">
					</div>
					<div class="wps-overview__keywords-text">
						<h3 class="wps-overview__keywords-heading"><?php echo esc_html_e( ' Lorem Ipsum ', 'track-orders-for-woocommerce' ); ?></h3>
						<p class="wps-overview__keywords-description">
							<?php
							esc_html_e( 'Lorem Ipsum ', 'track-orders-for-woocommerce' );
							?>
						</p>
					</div>
				</div>
			</div>
			<div class="wps-overview__keywords-item">
				<div class="wps-overview__keywords-card">
					<div class="wps-overview__keywords-image">
						<img src="<?php echo esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/Workflow.png' ); ?>" alt="Workflow image">
					</div>
					<div class="wps-overview__keywords-text">
						<h3 class="wps-overview__keywords-heading"><?php echo esc_html_e( ' Lorem Ipsum ', 'track-orders-for-woocommerce' ); ?></h3>
						<p class="wps-overview__keywords-description">
							<?php
							esc_html_e( 'Lorem Ipsum ', 'track-orders-for-woocommerce' );
							?>
						</p>
					</div>
				</div>
			</div>
			<div class="wps-overview__keywords-item">
				<div class="wps-overview__keywords-card">
					<div class="wps-overview__keywords-image">
						<img src="<?php echo esc_html( TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_URL . 'admin/image/Variable-product.png' ); ?>" alt="Variable product image">
					</div>
					<div class="wps-overview__keywords-text">
						<h3 class="wps-overview__keywords-heading"><?php echo esc_html_e( ' Lorem Ipsum ', 'track-orders-for-woocommerce' ); ?></h3>
						<p class="wps-overview__keywords-description">
							<?php
							esc_html_e( 'Lorem Ipsum ', 'track-orders-for-woocommerce' );
							?>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
