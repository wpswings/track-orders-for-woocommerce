const { registerBlockType } = wp.blocks;
const { TextControl, PanelBody } = wp.components;
const { useState } = wp.element;

registerBlockType('wpswings/tofw-embed-block', {
    title: 'WPSwings Tracking Info',
    icon: 'location-alt',
    category: 'widgets',
    attributes: {
        id: { type: 'string', default: 'Order ID' },
        alig: { type: 'string', default: 'right' },
    },
    edit: function (props) {
        return wp.element.createElement('div', useBlockProps(),
            wp.element.createElement(TextControl, {
                label: 'Order ID',
                value: props.attributes.id,
                onChange: function (id) { props.setAttributes({ id: id }) },
                placeholder: 'Enter Order ID'
            }),
            wp.element.createElement(TextControl, {
                label: 'Alignment',
                value: props.attributes.alig,
                onChange: function (alig) { props.setAttributes({ alig: alig }) },
                placeholder: 'Enter Alignment (e.g., right)'
            }),
            wp.element.createElement('p', {}, `Shortcode Output: [wps_tracking_info order_id="${props.attributes.id}" align="${props.attributes.alig}"]`)
        );
    },
    save: function (props) {
        return wp.element.createElement('div', useBlockProps.save(), `[wps_tracking_info order_id="${props.attributes.id}" align="${props.attributes.alig}"]`);
    }
});