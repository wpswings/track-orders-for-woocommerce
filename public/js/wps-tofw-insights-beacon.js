/**
 * Records tracking page visits and upsell clicks on the order tracking page.
 *
 * @since 1.3.0
 */
( function () {
	'use strict';

	if ( 'undefined' === typeof window.wps_tofw_insights ) {
		return;
	}

	var settings = window.wps_tofw_insights;
	var sent     = {};

	// Each metric is sent at most once per page view, so one accept = one click
	// (a label click also fires click + change on its input).
	function send( metric ) {
		if ( sent[ metric ] ) {
			return;
		}
		sent[ metric ] = true;

		var data = new FormData();
		data.append( 'action', 'wps_tofw_record_upsell_click' );
		data.append( 'nonce', settings.nonce );
		data.append( 'metric', metric );

		if ( navigator.sendBeacon && navigator.sendBeacon( settings.ajaxurl, data ) ) {
			return;
		}
		if ( window.fetch ) {
			window.fetch( settings.ajaxurl, { method: 'POST', body: data, credentials: 'same-origin', keepalive: true } );
		}
	}

	function matches( target, selectors ) {
		if ( ! target || ! target.closest || ! selectors ) {
			return false;
		}
		try {
			return !! target.closest( selectors );
		} catch ( e ) {
			// Invalid selector from a filter; ignore.
			return false;
		}
	}

	if ( Number( settings.count_visit ) ) {
		send( 'tracking_visit' );
	}

	document.addEventListener(
		'click',
		function ( event ) {
			if ( matches( event.target, settings.selectors ) ) {
				send( 'upsell_click' );
			}
		},
		true
	);

	document.addEventListener(
		'change',
		function ( event ) {
			var target = event.target;
			if ( ! matches( target, settings.inputs ) ) {
				return;
			}
			// Unticking an offer is not an accept.
			if ( ( 'checkbox' === target.type || 'radio' === target.type ) && ! target.checked ) {
				return;
			}
			send( 'upsell_click' );
		},
		true
	);
}() );
