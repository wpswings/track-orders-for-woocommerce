/**
 * Records upsell clicks on the order tracking page.
 *
 * @since 1.3.0
 */
( function () {
	'use strict';

	if ( 'undefined' === typeof window.wps_tofw_insights ) {
		return;
	}

	var settings = window.wps_tofw_insights;
	var sent     = false;

	function send() {
		if ( sent ) {
			return;
		}
		sent = true;

		var data = new FormData();
		data.append( 'action', 'wps_tofw_record_upsell_click' );
		data.append( 'nonce', settings.nonce );

		if ( navigator.sendBeacon && navigator.sendBeacon( settings.ajaxurl, data ) ) {
			return;
		}
		if ( window.fetch ) {
			window.fetch( settings.ajaxurl, { method: 'POST', body: data, credentials: 'same-origin', keepalive: true } );
		}
	}

	document.addEventListener(
		'click',
		function ( event ) {
			var target = event.target;
			if ( ! target || ! target.closest || ! settings.selectors ) {
				return;
			}
			try {
				if ( target.closest( settings.selectors ) ) {
					send();
				}
			} catch ( e ) {
				// Invalid selector from a filter; ignore.
			}
		},
		true
	);
}() );
