<?php
/**
 * Bulk tracking import (CSV) settings tab.
 *
 * @link       https://wpswings.com/
 * @since      1.3.0
 *
 * @package    Track_Orders_For_Woocommerce
 * @subpackage Track_Orders_For_Woocommerce/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_woocommerce' ) ) {
	return;
}

$tofw_import_sample_url = wp_nonce_url( admin_url( 'admin-post.php?action=wps_tofw_tracking_import_sample' ), Track_Orders_For_Woocommerce_Tracking_Import::NONCE );
$tofw_import_emails_on  = 'on' === get_option( 'wps_tofw_email_notifier', 'no' );
?>
<div class="tofwp-license-tab__hero">
	<div class="tofwp-license-tab__hero-copy">
		<p class="tofwp-license-tab__eyebrow"><?php esc_html_e( 'SECTION', 'track-orders-for-woocommerce' ); ?></p>
		<h2 class="tofwp-license-tab__hero-title"><?php esc_html_e( 'Bulk Tracking Import', 'track-orders-for-woocommerce' ); ?></h2>
		<p class="tofwp-license-tab__hero-text"><?php esc_html_e( 'Upload a CSV file to add tracking numbers to many orders at once. Rows are validated first (dry run), then imported in the background.', 'track-orders-for-woocommerce' ); ?></p>
	</div>
	<a class="tofwp-license-tab__doc-link button button-secondary" href="<?php echo esc_url( $tofw_import_sample_url ); ?>"><?php esc_html_e( 'Download Sample CSV', 'track-orders-for-woocommerce' ); ?></a>
</div>

<style>
.wps-tofw-import { margin-top: 18px; }
.wps-tofw-import__card { background: #fff; border: 1px solid #d9e4ef; border-radius: 12px; padding: 20px; margin-bottom: 18px; }
.wps-tofw-import__card h3 { margin: 0 0 10px; }
.wps-tofw-import__columns { border-collapse: collapse; width: 100%; }
.wps-tofw-import__columns th, .wps-tofw-import__columns td { border: 1px solid #e3e8ef; padding: 8px 10px; text-align: left; vertical-align: top; }
.wps-tofw-import__columns code { white-space: nowrap; }
.wps-tofw-import__row { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
.wps-tofw-import__bar { background: #eef2f7; border-radius: 999px; height: 12px; overflow: hidden; margin: 10px 0; }
.wps-tofw-import__bar span { background: #2196f3; display: block; height: 100%; width: 0; transition: width .3s; }
.wps-tofw-import__summary span { display: inline-block; margin-right: 16px; font-weight: 600; }
.wps-tofw-import__summary .is-valid, .wps-tofw-import__table .is-valid { color: #00753a; }
.wps-tofw-import__summary .is-invalid, .wps-tofw-import__table .is-invalid { color: #b32d2e; }
.wps-tofw-import__table-wrap { max-height: 420px; overflow: auto; margin: 12px 0; border: 1px solid #e3e8ef; }
.wps-tofw-import__table { border-collapse: collapse; width: 100%; font-size: 13px; }
.wps-tofw-import__table th { position: sticky; top: 0; background: #f6f8fb; }
.wps-tofw-import__table th, .wps-tofw-import__table td { border-bottom: 1px solid #eef2f7; padding: 6px 8px; text-align: left; }
.wps-tofw-import__message { margin: 8px 0; }
.wps-tofw-import__message.is-error { color: #b32d2e; }
.wps-tofw-import [hidden] { display: none !important; }
</style>

<div class="wps-tofw-import" id="wps-tofw-import">
	<div class="wps-tofw-import__card">
		<h3><?php esc_html_e( 'CSV format', 'track-orders-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'The first row must be a header row. Column order does not matter. Comma or semicolon separated, UTF-8.', 'track-orders-for-woocommerce' ); ?></p>
		<table class="wps-tofw-import__columns">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Column', 'track-orders-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Required', 'track-orders-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Description', 'track-orders-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>order_id</code></td>
					<td><?php esc_html_e( 'Yes', 'track-orders-for-woocommerce' ); ?></td>
					<td><?php esc_html_e( 'WooCommerce order ID.', 'track-orders-for-woocommerce' ); ?></td>
				</tr>
				<tr>
					<td><code>carrier</code></td>
					<td><?php esc_html_e( 'Yes', 'track-orders-for-woocommerce' ); ?></td>
					<td><?php esc_html_e( 'Shipping service key or name (e.g. fedex, UPS, DHL). Known services from the Shipping Services settings are matched automatically.', 'track-orders-for-woocommerce' ); ?></td>
				</tr>
				<tr>
					<td><code>tracking_number</code></td>
					<td><?php esc_html_e( 'Yes', 'track-orders-for-woocommerce' ); ?></td>
					<td><?php esc_html_e( 'Tracking number. A tracking number already present on the order is rejected as a duplicate, so re-uploading the same file is safe.', 'track-orders-for-woocommerce' ); ?></td>
				</tr>
				<tr>
					<td><code>ship_date</code></td>
					<td><?php esc_html_e( 'No', 'track-orders-for-woocommerce' ); ?></td>
					<td><?php esc_html_e( 'Date shipped in YYYY-MM-DD format. Defaults to today.', 'track-orders-for-woocommerce' ); ?></td>
				</tr>
				<tr>
					<td><code>items</code></td>
					<td><?php esc_html_e( 'No', 'track-orders-for-woocommerce' ); ?></td>
					<td>
						<?php esc_html_e( 'Partial shipment. Leave empty when the tracking number covers the whole order. Otherwise list entries separated by "|", each entry is SKU:quantity, for example:', 'track-orders-for-woocommerce' ); ?>
						<code>TSHIRT-RED-M:2|MUG-01:1</code>.
						<?php esc_html_e( 'Use #<order item id> instead of a SKU for products without SKU (e.g. #57:1). Omitting ":quantity" ships all remaining quantity of that item. Quantities cannot exceed what is still unshipped.', 'track-orders-for-woocommerce' ); ?>
					</td>
				</tr>
				<tr>
					<td><code>status</code></td>
					<td><?php esc_html_e( 'No', 'track-orders-for-woocommerce' ); ?></td>
					<td><?php esc_html_e( 'New order status after adding tracking (e.g. completed, wc-completed or a custom status slug). Leave empty to keep the current status.', 'track-orders-for-woocommerce' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<div class="wps-tofw-import__card" data-step="upload">
		<h3><?php esc_html_e( '1. Upload file', 'track-orders-for-woocommerce' ); ?></h3>
		<form class="wps-tofw-import__row" id="wps-tofw-import-form" enctype="multipart/form-data">
			<input type="file" name="wps_tofw_import_file" id="wps-tofw-import-file" accept=".csv,text/csv" />
			<button type="submit" class="button button-primary" id="wps-tofw-import-upload"><?php esc_html_e( 'Upload & Preview', 'track-orders-for-woocommerce' ); ?></button>
		</form>
		<div class="wps-tofw-import__validate" hidden>
			<div class="wps-tofw-import__bar"><span></span></div>
			<p class="wps-tofw-import__status"></p>
		</div>
		<p class="wps-tofw-import__message" id="wps-tofw-import-upload-message"></p>
	</div>

	<div class="wps-tofw-import__card" id="wps-tofw-import-preview" hidden>
		<h3><?php esc_html_e( '2. Preview (dry run)', 'track-orders-for-woocommerce' ); ?></h3>
		<p class="wps-tofw-import__summary"></p>
		<div class="wps-tofw-import__table-wrap">
			<table class="wps-tofw-import__table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Line', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Result', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Order', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Carrier', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Tracking number', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Ship date', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Items', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Status', 'track-orders-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'track-orders-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<p class="wps-tofw-import__note description" hidden></p>
		<div class="wps-tofw-import__row">
			<label for="wps-tofw-import-notify"><?php esc_html_e( 'Customer tracking e-mail', 'track-orders-for-woocommerce' ); ?></label>
			<select id="wps-tofw-import-notify">
				<option value="settings">
					<?php
					echo esc_html(
						$tofw_import_emails_on
							? __( 'Follow plugin e-mail settings (currently: send)', 'track-orders-for-woocommerce' )
							: __( 'Follow plugin e-mail settings (currently: do not send)', 'track-orders-for-woocommerce' )
					);
					?>
				</option>
				<option value="none"><?php esc_html_e( 'Do not send tracking e-mails', 'track-orders-for-woocommerce' ); ?></option>
			</select>
			<button type="button" class="button button-primary" id="wps-tofw-import-start"><?php esc_html_e( 'Import valid rows', 'track-orders-for-woocommerce' ); ?></button>
			<a class="button" id="wps-tofw-import-report" href="#" hidden><?php esc_html_e( 'Download error report', 'track-orders-for-woocommerce' ); ?></a>
		</div>
		<p class="description"><?php esc_html_e( 'Order status changes always send the usual WooCommerce / plugin status e-mails according to their own settings.', 'track-orders-for-woocommerce' ); ?></p>
	</div>

	<div class="wps-tofw-import__card" id="wps-tofw-import-progress" hidden>
		<h3><?php esc_html_e( '3. Import progress', 'track-orders-for-woocommerce' ); ?></h3>
		<div class="wps-tofw-import__bar"><span></span></div>
		<p class="wps-tofw-import__status"></p>
		<p class="wps-tofw-import__summary"></p>
		<div class="wps-tofw-import__row">
			<a class="button" id="wps-tofw-import-final-report" href="#" hidden><?php esc_html_e( 'Download error report', 'track-orders-for-woocommerce' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=track_orders_for_woocommerce_menu&tofw_tab=' . Track_Orders_For_Woocommerce_Tracking_Import::TAB ) ); ?>"><?php esc_html_e( 'Start a new import', 'track-orders-for-woocommerce' ); ?></a>
		</div>
	</div>
</div>
