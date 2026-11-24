const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { TextControl, PanelBody } = wp.components;
const { useState } = wp.element;

registerBlockType('wpswings/tofw-embed-block', {
    title: __( 'WPSwings Tracking Info', 'track-orders-for-woocommerce' ),
    icon: 'location-alt',
    category: 'widgets',
    attributes: {
        id: { type: 'string', default: 'Order ID' },
        alig: { type: 'string', default: 'right' },
    },
    edit: function (props) {
        return wp.element.createElement('div', useBlockProps(),
            wp.element.createElement(TextControl, {
                label: __( 'Order ID', 'track-orders-for-woocommerce' ),
                value: props.attributes.id,
                onChange: function (id) { props.setAttributes({ id: id }) },
                placeholder: __( 'Enter Order ID', 'track-orders-for-woocommerce' )
            }),
            wp.element.createElement(TextControl, {
                label: __( 'Alignment', 'track-orders-for-woocommerce' ),
                value: props.attributes.alig,
                onChange: function (alig) { props.setAttributes({ alig: alig }) },
                placeholder: __( 'Enter Alignment (e.g., right)', 'track-orders-for-woocommerce' )
            }),
            wp.element.createElement('p', {}, __( 'Shortcode Output:', 'track-orders-for-woocommerce' ) + ` [wps_tracking_info order_id="${props.attributes.id}" align="${props.attributes.alig}"]`)
        );
    },
    save: function (props) {
        return wp.element.createElement('div', useBlockProps.save(), `[wps_tracking_info order_id="${props.attributes.id}" align="${props.attributes.alig}"]`);
    }
});