/**
 * Track Order block - editor script.
 *
 * Plain ES5 using the wp.* globals (no build step). The block is dynamic:
 * the markup is rendered on the server, so save() returns null.
 *
 * @since 1.3.0
 */
( function ( blocks, element, blockEditor, components, serverSideRender, i18n ) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var Disabled = components.Disabled;
	var ServerSideRender = serverSideRender && serverSideRender.default ? serverSideRender.default : serverSideRender;
	var BLOCK_NAME = 'track-orders-for-woocommerce/track-order';

	blocks.registerBlockType( BLOCK_NAME, {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var isCarrier = 'carrier' === attributes.formType;

			var settings = [
				el( SelectControl, {
					key: 'formType',
					label: __( 'Form type', 'track-orders-for-woocommerce' ),
					value: attributes.formType,
					options: [
						{ label: __( 'Order tracking (order ID and email)', 'track-orders-for-woocommerce' ), value: 'order' },
						{ label: __( 'Multi-carrier tracking (tracking number)', 'track-orders-for-woocommerce' ), value: 'carrier' }
					],
					onChange: function ( value ) {
						setAttributes( { formType: value } );
					},
					__nextHasNoMarginBottom: true
				} )
			];

			if ( isCarrier ) {
				settings.push(
					el( SelectControl, {
						key: 'position',
						label: __( 'Position', 'track-orders-for-woocommerce' ),
						value: attributes.position,
						options: [
							{ label: __( 'Left', 'track-orders-for-woocommerce' ), value: 'left' },
							{ label: __( 'Center', 'track-orders-for-woocommerce' ), value: 'center' },
							{ label: __( 'Right', 'track-orders-for-woocommerce' ), value: 'right' }
						],
						onChange: function ( value ) {
							setAttributes( { position: value } );
						},
						__nextHasNoMarginBottom: true
					} )
				);
			} else {
				settings.push(
					el( ToggleControl, {
						key: 'showTitle',
						label: __( 'Show heading', 'track-orders-for-woocommerce' ),
						checked: !! attributes.showTitle,
						onChange: function ( value ) {
							setAttributes( { showTitle: value } );
						},
						__nextHasNoMarginBottom: true
					} )
				);
			}

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el( PanelBody, { title: __( 'Tracking form', 'track-orders-for-woocommerce' ), initialOpen: true }, settings )
				),
				el(
					'div',
					useBlockProps(),
					el(
						Disabled,
						null,
						el( ServerSideRender, {
							block: BLOCK_NAME,
							attributes: attributes,
							skipBlockSupportAttributes: true
						} )
					)
				)
			);
		},

		save: function () {
			return null;
		}
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.serverSideRender,
	window.wp.i18n
);
