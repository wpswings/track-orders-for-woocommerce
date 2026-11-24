/**
 * WPS-8402: dynamic "Track Order" block, block-based My Account / order-received pages,
 * HPOS + cart/checkout blocks compatibility and the legacy embed block.
 */
import { Browser, expect, Page, test } from '@playwright/test';
import {
	createOrder,
	GUEST_STORAGE_STATE,
	setOptions,
	setupPlugin,
	wpEvalJson,
} from '../utils';
import { createBlockPage, ensureCustomer, loginAs, PLUGIN_FILE, TRACK_ORDER_BLOCK } from '../utils/wps-8402';

test.describe.configure( { mode: 'serial' } );

/** Fill and submit the guest tracking form rendered by the block. */
async function trackAsGuest( page: Page, orderId: number, email: string ): Promise< void > {
	const block = page.locator( '.wps-tofw-track-order-block' );
	await expect( block ).toBeVisible();
	await block.locator( '#order_id' ).fill( String( orderId ) );
	await block.locator( '#order_email' ).fill( email );
	await block.locator( 'input[name="wps_tofw_order_id_submit"]' ).click();
}

/** Open a fresh logged-out browser context. */
async function guestPage( browser: Browser ): Promise< Page > {
	const context = await browser.newContext( { storageState: GUEST_STORAGE_STATE } );
	return context.newPage();
}

test.describe( 'WPS-8402 Track Order block, block pages and HPOS', () => {
	test.beforeAll( () => {
		setupPlugin();
		setOptions( {
			wps_tofw_enable_track_order_using_order_id: 'no',
			wps_tofw_trackorder_with_google_map: 'no',
			wps_tofw_activated_template: 'template1',
		} );
	} );

	test( 'Track Order block and legacy embed block are registered on the server', () => {
		const info = wpEvalJson< {
			track: { exists: boolean; dynamic: boolean; attributes: Record< string, { default?: unknown } >; editorScript: string[] };
			embed: { exists: boolean; editorScript: string[] };
			embedSrc: string;
			embedFileExists: boolean;
		} >( `
			$registry = WP_Block_Type_Registry::get_instance();
			$track    = $registry->get_registered( ${ JSON.stringify( TRACK_ORDER_BLOCK ) } );
			$embed    = $registry->get_registered( 'wpswings/tofw-embed-block' );
			$script   = wp_scripts()->query( 'tofw-embed-block', 'registered' );
			$src      = $script ? $script->src : '';
			return array(
				'track' => array(
					'exists'       => (bool) $track,
					'dynamic'      => $track ? $track->is_dynamic() : false,
					'attributes'   => $track ? $track->attributes : array(),
					'editorScript' => $track ? array_values( (array) $track->editor_script_handles ) : array(),
				),
				'embed' => array(
					'exists'       => (bool) $embed,
					'editorScript' => $embed ? array_values( (array) $embed->editor_script_handles ) : array(),
				),
				'embedSrc'        => $src,
				'embedFileExists' => $src && file_exists( WP_PLUGIN_DIR . '/track-orders-for-woocommerce/admin/js/tofw-embed-block.js' ),
			);
		` );

		expect( info.track.exists ).toBe( true );
		expect( info.track.dynamic ).toBe( true );
		expect( info.track.attributes.formType.default ).toBe( 'order' );
		expect( info.track.attributes.showTitle.default ).toBe( true );
		expect( info.track.editorScript.length ).toBeGreaterThan( 0 );

		expect( info.embed.exists ).toBe( true );
		expect( info.embed.editorScript ).toContain( 'tofw-embed-block' );
		expect( info.embedSrc ).toMatch( /\/track-orders-for-woocommerce\/admin\/js\/tofw-embed-block\.js$/ );
		expect( info.embedFileExists ).toBe( true );
	} );

	test( 'legacy embed block script is served (no 404)', async ( { request } ) => {
		const src = wpEvalJson< string >( `$s = wp_scripts()->query( 'tofw-embed-block', 'registered' ); return $s ? $s->src : '';` );
		const response = await request.get( src );
		expect( response.status() ).toBe( 200 );
		expect( await response.text() ).toContain( 'wpswings/tofw-embed-block' );
	} );

	test( 'admin inserts the Track Order block in the block editor and a guest tracks an order with it', async ( { page, browser } ) => {
		const order = createOrder( { status: 'processing', billing_email: 'block-editor-8402@example.com' } );

		await page.goto( '/wp-admin/post-new.php?post_type=page' );
		await page.waitForFunction( () => {
			const wp = ( window as any ).wp;
			return !! ( wp && wp.data && wp.blocks && wp.blocks.getBlockType( 'track-orders-for-woocommerce/track-order' ) && wp.data.select( 'core/editor' ).getCurrentPostId() );
		} );

		// The block is available in the inserter.
		const inserterTitle = await page.evaluate( () => ( window as any ).wp.blocks.getBlockType( 'track-orders-for-woocommerce/track-order' ).title );
		expect( inserterTitle ).toBe( 'Track Order' );

		await page.evaluate( () => {
			const wp = ( window as any ).wp;
			wp.data.dispatch( 'core/editor' ).editPost( { title: 'WPS-8402 Block Editor Tracking' } );
			wp.data.dispatch( 'core/block-editor' ).insertBlocks( wp.blocks.createBlock( 'track-orders-for-woocommerce/track-order' ) );
		} );

		// ServerSideRender preview shows the real form in the editor canvas.
		const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
		const preview = ( await page.locator( 'iframe[name="editor-canvas"]' ).count() ) > 0
			? canvas.locator( '.wps-tofw-track-order-block #order_id' )
			: page.locator( '.wps-tofw-track-order-block #order_id' );
		await expect( preview ).toBeVisible( { timeout: 20_000 } );

		await page.evaluate( () => {
			const wp = ( window as any ).wp;
			wp.data.dispatch( 'core/editor' ).editPost( { status: 'publish' } );
			return wp.data.dispatch( 'core/editor' ).savePost();
		} );
		await page.waitForFunction( () => {
			const editor = ( window as any ).wp.data.select( 'core/editor' );
			return ! editor.isSavingPost() && ! editor.isEditedPostDirty() && 'publish' === editor.getCurrentPostAttribute( 'status' );
		} );
		const postId = await page.evaluate( () => ( window as any ).wp.data.select( 'core/editor' ).getCurrentPostId() );
		const saved = wpEvalJson< { content: string; url: string } >(
			`return array( 'content' => get_post_field( 'post_content', ${ Number( postId ) } ), 'url' => get_permalink( ${ Number( postId ) } ) );`
		);
		expect( saved.content ).toContain( '<!-- wp:track-orders-for-woocommerce/track-order /-->' );

		const guest = await guestPage( browser );
		await guest.goto( saved.url );
		await trackAsGuest( guest, order.id, order.billing_email );
		await guest.waitForURL( new RegExp( `\\?${ order.id }$` ) );
		await expect( guest.locator( '.wps-tofw-order-tracking-section' ) ).toBeVisible();
		await expect( guest.locator( 'body' ) ).toContainText( String( order.id ) );
		await guest.context().close();
	} );

	test.describe( 'as a guest', () => {
		test.use( { storageState: GUEST_STORAGE_STATE } );

		test( 'block page renders the form; wrong email shows an error and no tracking details', async ( { page } ) => {
			const order = createOrder( { status: 'processing', billing_email: 'block-guest-8402@example.com' } );
			const blockPage = createBlockPage( 'WPS-8402 Guest Block', `<!-- wp:${ TRACK_ORDER_BLOCK } /-->` );

			await page.goto( blockPage.url );
			await expect( page.locator( '.wps-tofw-track-order-block h2' ) ).toBeVisible();

			await trackAsGuest( page, order.id, 'someone-else@example.com' );
			await expect( page.locator( '.wps-tofw-track-order-block .woocommerce-error' ) ).toBeVisible();
			await expect( page.locator( '.wps-tofw-order-tracking-section' ) ).toHaveCount( 0 );
			expect( new URL( page.url() ).search ).toBe( '' );
		} );

		test( 'block page with correct order id and email opens the tracking details', async ( { page } ) => {
			const order = createOrder( { status: 'completed', billing_email: 'block-guest-ok-8402@example.com' } );
			const blockPage = createBlockPage( 'WPS-8402 Guest Block OK', `<!-- wp:${ TRACK_ORDER_BLOCK } {"showTitle":false} /-->` );

			await page.goto( blockPage.url );
			await expect( page.locator( '.wps-tofw-track-order-block h2' ) ).toHaveCount( 0 );
			await trackAsGuest( page, order.id, order.billing_email );

			await page.waitForURL( new RegExp( `\\?${ order.id }$` ) );
			await expect( page.locator( '.wps-tofw-order-tracking-section' ) ).toBeVisible();
			await expect( page.locator( 'body' ) ).toContainText( String( order.id ) );
		} );

		test( 'block renders nothing on the frontend when the Track Order feature is off', async ( { page } ) => {
			const blockPage = createBlockPage( 'WPS-8402 Disabled Block', `<!-- wp:${ TRACK_ORDER_BLOCK } /-->` );
			setOptions( { tofw_enable_track_order: 'off' } );
			try {
				await page.goto( blockPage.url );
				await expect( page.locator( 'h1, h2' ).filter( { hasText: 'WPS-8402 Disabled Block' } ).first() ).toBeVisible();
				await expect( page.locator( '.wps-tofw-track-order-block' ) ).toHaveCount( 0 );
				await expect( page.locator( '#order_id' ) ).toHaveCount( 0 );
			} finally {
				setOptions( { tofw_enable_track_order: 'on' } );
			}
		} );
	} );

	test( 'plugin declares HPOS and cart/checkout blocks compatibility (no incompatibility notice)', async ( { page } ) => {
		const compat = wpEvalJson< { hpos: boolean; features: { compatible: string[]; incompatible: string[]; uncertain: string[] } } >( `
			return array(
				'hpos'     => \\Automattic\\WooCommerce\\Utilities\\OrderUtil::custom_orders_table_usage_is_enabled(),
				'features' => \\Automattic\\WooCommerce\\Utilities\\FeaturesUtil::get_compatible_features_for_plugin( ${ JSON.stringify( PLUGIN_FILE ) } ),
			);
		` );
		expect( compat.hpos, 'wp-env site uses HPOS' ).toBe( true );
		expect( compat.features.compatible ).toEqual( expect.arrayContaining( [ 'custom_order_tables', 'cart_checkout_blocks' ] ) );
		expect( compat.features.incompatible ).not.toContain( 'custom_order_tables' );
		expect( compat.features.incompatible ).not.toContain( 'cart_checkout_blocks' );
		expect( compat.features.uncertain ).not.toContain( 'custom_order_tables' );

		for ( const feature of [ 'custom_order_tables', 'cart_checkout_blocks' ] ) {
			await page.goto( `/wp-admin/plugins.php?plugin_status=incompatible_with_feature&feature_id=${ feature }` );
			await expect( page.locator( '#wpbody-content' ) ).toBeVisible();
			await expect( page.locator( `tr[data-plugin="${ PLUGIN_FILE }"]` ) ).toHaveCount( 0 );
		}

		await page.goto( '/wp-admin/admin.php?page=wc-settings&tab=advanced&section=features' );
		await expect( page.locator( '#wpbody-content' ) ).toBeVisible();
		await expect( page.locator( 'body' ) ).not.toContainText( /incompatible[^.]*Track Orders for WooCommerce/i );
	} );

	test.describe( 'block theme order pages', () => {
		test.use( { storageState: GUEST_STORAGE_STATE } );

		const login = 'wps8402customer';
		const password = 'Wps8402-pass!';
		const email = 'wps8402customer@example.com';

		test.beforeAll( () => {
			setOptions( { wps_tofw_enable_third_party_tracking_api: 'on' } );
		} );

		test.afterAll( () => {
			setOptions( { wps_tofw_enable_third_party_tracking_api: 'no' } );
		} );

		test( 'order-received page (block theme) shows the tracking info', async ( { page } ) => {
			expect( wpEvalJson< boolean >( 'return wp_is_block_theme();' ), 'active theme is a block theme' ).toBe( true );
			const userId = ensureCustomer( login, email, password );
			const order = createOrder( {
				status: 'processing',
				customer_id: userId,
				billing_email: email,
				meta: {
					wps_tofw_selected_shipping_service: 'fedex',
					wps_tofw_package_tracking_number: 'TRK8402RECEIVED',
				},
			} );
			const receivedUrl = wpEvalJson< string >( `return wc_get_order( ${ order.id } )->get_checkout_order_received_url();` );

			await loginAs( page, login, password );
			await page.goto( receivedUrl );

			await expect( page.locator( '.wp-block-woocommerce-order-confirmation-status, .woocommerce-order' ).first() ).toBeVisible();
			await expect( page.getByText( 'Tracking Info', { exact: true } ) ).toBeVisible();
			await expect( page.locator( 'body' ) ).toContainText( 'TRK8402RECEIVED' );
			await expect( page.locator( 'body' ) ).toContainText( 'FedEx' );
		} );

		test( 'My Account view-order page shows the tracking info and Track Order button', async ( { page } ) => {
			const userId = ensureCustomer( login, email, password );
			setOptions( { tofw_enable_track_order_below: 'on' } );
			const order = createOrder( {
				status: 'processing',
				customer_id: userId,
				billing_email: email,
				meta: {
					wps_tofw_selected_shipping_service: 'usps',
					wps_tofw_package_tracking_number: 'TRK8402ACCOUNT',
				},
			} );

			try {
				await loginAs( page, login, password );
				await page.goto( order.view_url );

				await expect( page.getByText( 'Tracking Info', { exact: true } ) ).toBeVisible();
				await expect( page.locator( 'body' ) ).toContainText( 'TRK8402ACCOUNT' );
				const trackLink = page.locator( `a.button[href$="?${ order.id }"]` ).first();
				await expect( trackLink ).toBeVisible();

				await trackLink.click();
				await page.waitForURL( new RegExp( `\\?${ order.id }$` ) );
				await expect( page.locator( '.wps-tofw-order-tracking-section' ) ).toBeVisible();
			} finally {
				setOptions( { tofw_enable_track_order_below: 'no' } );
			}
		} );
	} );
} );
