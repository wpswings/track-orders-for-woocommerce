<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to display Order API details.
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

$tofw_api_base_url = untrailingslashit( rest_url( 'tofw-route/v1' ) );
$tofw_sample_list_endpoint = $tofw_api_base_url . '/orders?page=1&per_page=20&include_items=true';
$tofw_sample_order_endpoint = $tofw_api_base_url . '/orders/123?include_items=true';
$tofw_sample_create_endpoint = $tofw_api_base_url . '/orders';
$tofw_sample_status_endpoint = $tofw_api_base_url . '/order-statuses';
$tofw_sample_create_payload = array(
	'status' => 'processing',
	'customer_id' => 1,
	'billing' => array(
		'first_name' => 'John',
		'last_name' => 'Doe',
		'email' => 'john@example.com',
		'phone' => '9999999999',
		'address_1' => '221B Baker Street',
		'city' => 'London',
		'state' => 'London',
		'postcode' => 'NW16XE',
		'country' => 'GB',
	),
	'shipping' => array(
		'first_name' => 'John',
		'last_name' => 'Doe',
		'address_1' => '221B Baker Street',
		'city' => 'London',
		'state' => 'London',
		'postcode' => 'NW16XE',
		'country' => 'GB',
	),
	'line_items' => array(
		array(
			'product_id' => 123,
			'quantity' => 2,
		),
	),
	'meta_data' => array(
		array(
			'key' => 'wps_tofw_package_tracking_number',
			'value' => 'TRK-12345',
		),
		array(
			'key' => 'wps_tofw_selected_shipping_service',
			'value' => 'fedex',
		),
	),
);
$tofw_sample_create_payload_json = wp_json_encode( $tofw_sample_create_payload, JSON_PRETTY_PRINT );
?>
<style>
.tofw-api-docs {
	--tofw-api-blue: #2196f3;
	--tofw-api-dark: #163062;
	--tofw-api-text: #2d3642;
	--tofw-api-muted: #657286;
	--tofw-api-bg: #ffffff;
	--tofw-api-border: #d9e4ef;
	--tofw-api-code-bg: #0f172a;
}
.tofw-api-docs * {
	box-sizing: border-box;
}
.tofw-api-card {
	background: var(--tofw-api-bg);
	border: 1px solid var(--tofw-api-border);
	border-radius: 12px;
	padding: 20px;
	box-shadow: 0 8px 22px rgba(14, 30, 49, 0.06);
}
.tofw-api-card + .tofw-api-card {
	margin-top: 18px;
}
.tofw-api-hero {
	background: linear-gradient(135deg, #eff7ff 0%, #ffffff 65%);
}
.tofw-api-title {
	font-size: 34px;
	line-height: 1.2;
	margin: 0 0 8px;
	color: var(--tofw-api-dark);
}
.tofw-api-subtitle {
	margin: 0;
	font-size: 15px;
	color: var(--tofw-api-text);
}
.tofw-api-base {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 10px;
	margin-top: 16px;
}
.tofw-api-label {
	background: #dbeeff;
	color: #085ca6;
	font-weight: 700;
	font-size: 12px;
	line-height: 1;
	padding: 8px 10px;
	border-radius: 999px;
}
.tofw-api-code-inline {
	background: #f1f7fc;
	color: #1a3657;
	padding: 8px 10px;
	border-radius: 8px;
	font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
	font-size: 13px;
}
.tofw-api-grid {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	gap: 18px;
	margin-top: 18px;
}
.tofw-api-card h3 {
	font-size: 19px;
	margin: 0 0 10px;
	color: var(--tofw-api-dark);
}
.tofw-api-list {
	margin: 0;
	padding-left: 18px;
	color: var(--tofw-api-text);
}
.tofw-api-list li + li {
	margin-top: 8px;
}
.tofw-api-muted {
	color: var(--tofw-api-muted);
	font-size: 13px;
	margin-top: 10px;
}
.tofw-api-endpoint {
	display: grid;
	grid-template-columns: auto 1fr;
	gap: 12px;
	padding: 14px;
	border-radius: 10px;
	border: 1px solid #e8eef5;
	background: #fcfdff;
}
.tofw-api-endpoint + .tofw-api-endpoint {
	margin-top: 10px;
}
.tofw-api-method {
	align-self: start;
	font-size: 11px;
	font-weight: 800;
	letter-spacing: 0.05em;
	padding: 7px 9px;
	border-radius: 6px;
	color: #fff;
	min-width: 52px;
	text-align: center;
}
.tofw-api-method.get {
	background: #059669;
}
.tofw-api-method.post {
	background: #2563eb;
}
.tofw-api-endpoint h4 {
	margin: 0 0 5px;
	font-size: 15px;
	color: var(--tofw-api-dark);
}
.tofw-api-endpoint p {
	margin: 0;
	color: var(--tofw-api-text);
	font-size: 13px;
}
.tofw-api-params {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin-top: 8px;
}
.tofw-api-param {
	background: #f0f4f9;
	color: #364255;
	font-size: 12px;
	padding: 5px 8px;
	border-radius: 999px;
}
.tofw-api-code-wrap {
	position: relative;
	margin-top: 10px;
}
.tofw-api-code-block {
	background: var(--tofw-api-code-bg);
	color: #d9ecff;
	border-radius: 10px;
	padding: 14px;
	font-size: 12px;
	line-height: 1.45;
	overflow: auto;
	margin: 0;
	font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
}
.tofw-api-copy-btn {
	padding: 6px 10px;
	border: 1px solid #98cfff;
	background: #ecf6ff;
	color: #055ea8;
	border-radius: 6px;
	font-size: 12px;
	font-weight: 700;
	cursor: pointer;
}
.tofw-api-code-wrap .tofw-api-copy-btn {
	position: absolute;
	top: 10px;
	right: 10px;
}
.tofw-api-copy-btn:hover {
	background: #dff0ff;
}
.tofw-api-copy-btn:focus {
	outline: 2px solid #90caf9;
	outline-offset: 1px;
}
@media (max-width: 1024px) {
	.tofw-api-grid {
		grid-template-columns: 1fr;
	}
}
@media (max-width: 767px) {
	.tofw-api-title {
		font-size: 28px;
	}
	.tofw-api-card {
		padding: 16px;
	}
}
</style>
<div class="tofw-api-docs">
	<div class="tofw-api-card tofw-api-hero">
		<h2 class="tofw-api-title"><?php esc_html_e( 'Order API', 'track-orders-for-woocommerce' ); ?></h2>
		<p class="tofw-api-subtitle">
			<?php esc_html_e( 'Use these endpoints to list orders, fetch order details, create orders, and read custom/default statuses. Authentication is mandatory for every request.', 'track-orders-for-woocommerce' ); ?>
		</p>
		<div class="tofw-api-base">
			<span class="tofw-api-label"><?php esc_html_e( 'Base URL', 'track-orders-for-woocommerce' ); ?></span>
			<code class="tofw-api-code-inline"><?php echo esc_html( $tofw_api_base_url ); ?></code>
			<button type="button" class="tofw-api-copy-btn" data-copy="<?php echo esc_attr( $tofw_api_base_url ); ?>">
				<?php esc_html_e( 'Copy', 'track-orders-for-woocommerce' ); ?>
			</button>
		</div>
	</div>

	<div class="tofw-api-grid">
			<div class="tofw-api-card">
				<h3><?php esc_html_e( 'Quick Start', 'track-orders-for-woocommerce' ); ?></h3>
				<ol class="tofw-api-list">
					<li><?php esc_html_e( 'Create an Application Password from your WordPress user profile.', 'track-orders-for-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'Use Basic Auth in Postman or your server-side app.', 'track-orders-for-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'Use the full endpoint URLs below (Base URL + route path).', 'track-orders-for-woocommerce' ); ?></li>
				</ol>
				<p class="tofw-api-muted">
					<?php esc_html_e( 'Required capability: manage_woocommerce or edit_shop_orders.', 'track-orders-for-woocommerce' ); ?>
			</p>
		</div>
		<div class="tofw-api-card">
			<h3><?php esc_html_e( 'Common Query Params', 'track-orders-for-woocommerce' ); ?></h3>
			<div class="tofw-api-params">
				<span class="tofw-api-param">page=1</span>
				<span class="tofw-api-param">per_page=20</span>
				<span class="tofw-api-param">status=processing,completed</span>
				<span class="tofw-api-param">customer_id=1</span>
				<span class="tofw-api-param">search=john</span>
				<span class="tofw-api-param">fields=id,status,total</span>
				<span class="tofw-api-param">include_items=true</span>
			</div>
		</div>
	</div>

		<div class="tofw-api-card">
			<h3><?php esc_html_e( 'Available Endpoints', 'track-orders-for-woocommerce' ); ?></h3>

		<div class="tofw-api-endpoint">
			<span class="tofw-api-method get">GET</span>
				<div>
					<h4><code><?php echo esc_html( '/orders' ); ?></code></h4>
					<p><?php esc_html_e( 'Returns paginated orders with status label, billing/shipping data, and tracking meta.', 'track-orders-for-woocommerce' ); ?></p>
					<div class="tofw-api-code-wrap">
						<pre class="tofw-api-code-block"><?php echo esc_html( $tofw_sample_list_endpoint ); ?></pre>
						<button type="button" class="tofw-api-copy-btn" data-copy="<?php echo esc_attr( $tofw_sample_list_endpoint ); ?>"><?php esc_html_e( 'Copy', 'track-orders-for-woocommerce' ); ?></button>
					</div>
				</div>
			</div>

		<div class="tofw-api-endpoint">
			<span class="tofw-api-method get">GET</span>
				<div>
					<h4><code><?php echo esc_html( '/orders/{id}' ); ?></code></h4>
					<p><?php esc_html_e( 'Returns a specific order. Use include_items=true to include line/shipping/fee/coupon items.', 'track-orders-for-woocommerce' ); ?></p>
					<div class="tofw-api-code-wrap">
						<pre class="tofw-api-code-block"><?php echo esc_html( $tofw_sample_order_endpoint ); ?></pre>
						<button type="button" class="tofw-api-copy-btn" data-copy="<?php echo esc_attr( $tofw_sample_order_endpoint ); ?>"><?php esc_html_e( 'Copy', 'track-orders-for-woocommerce' ); ?></button>
					</div>
				</div>
			</div>

			<div class="tofw-api-endpoint">
				<span class="tofw-api-method post">POST</span>
				<div>
					<h4><code><?php echo esc_html( '/orders' ); ?></code></h4>
					<p><?php esc_html_e( 'Creates a new WooCommerce order. line_items is required in the JSON body.', 'track-orders-for-woocommerce' ); ?></p>
					<div class="tofw-api-code-wrap">
						<pre class="tofw-api-code-block"><?php echo esc_html( $tofw_sample_create_endpoint ); ?></pre>
						<button type="button" class="tofw-api-copy-btn" data-copy="<?php echo esc_attr( $tofw_sample_create_endpoint ); ?>"><?php esc_html_e( 'Copy', 'track-orders-for-woocommerce' ); ?></button>
					</div>
				</div>
			</div>

		<div class="tofw-api-endpoint">
			<span class="tofw-api-method get">GET</span>
				<div>
					<h4><code><?php echo esc_html( '/order-statuses' ); ?></code></h4>
					<p><?php esc_html_e( 'Returns all statuses, including custom statuses.', 'track-orders-for-woocommerce' ); ?></p>
					<div class="tofw-api-code-wrap">
						<pre class="tofw-api-code-block"><?php echo esc_html( $tofw_sample_status_endpoint ); ?></pre>
						<button type="button" class="tofw-api-copy-btn" data-copy="<?php echo esc_attr( $tofw_sample_status_endpoint ); ?>"><?php esc_html_e( 'Copy', 'track-orders-for-woocommerce' ); ?></button>
					</div>
				</div>
			</div>
	</div>

	<div class="tofw-api-card">
		<h3><?php esc_html_e( 'Sample Create Order Payload', 'track-orders-for-woocommerce' ); ?></h3>
		<div class="tofw-api-code-wrap">
			<pre class="tofw-api-code-block"><?php echo esc_html( $tofw_sample_create_payload_json ); ?></pre>
			<button type="button" class="tofw-api-copy-btn" data-copy="<?php echo esc_attr( $tofw_sample_create_payload_json ); ?>"><?php esc_html_e( 'Copy', 'track-orders-for-woocommerce' ); ?></button>
		</div>
	</div>
</div>
<script>
( function () {
	const copyButtons = document.querySelectorAll( '.tofw-api-copy-btn' );
	if ( ! copyButtons.length || ! navigator.clipboard ) {
		return;
	}

	copyButtons.forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			const copyText = button.getAttribute( 'data-copy' ) || '';
			navigator.clipboard.writeText( copyText ).then( function () {
				const defaultText = '<?php echo esc_js( __( 'Copy', 'track-orders-for-woocommerce' ) ); ?>';
				button.textContent = '<?php echo esc_js( __( 'Copied', 'track-orders-for-woocommerce' ) ); ?>';
				window.setTimeout( function () {
					button.textContent = defaultText;
				}, 1400 );
			} );
		} );
	} );
}() );
</script>
