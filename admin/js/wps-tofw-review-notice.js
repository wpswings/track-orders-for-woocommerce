/**
 * Review request notice actions.
 *
 * @since 1.3.0
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $notice = $( '#wps-tofw-review-notice' );
		if ( ! $notice.length || 'undefined' === typeof wps_tofw_review_notice ) {
			return;
		}

		function send( reviewAction ) {
			return $.post( wps_tofw_review_notice.ajax_url, {
				action: wps_tofw_review_notice.action,
				nonce: wps_tofw_review_notice.nonce,
				review_action: reviewAction
			} );
		}

		$notice.on( 'click', '.wps-tofw-review-action', function ( e ) {
			var reviewAction = $( this ).data( 'review-action' );
			// Let "Rate us" open its link in a new tab.
			if ( 'rated' !== reviewAction ) {
				e.preventDefault();
			}
			send( reviewAction );
			$notice.fadeTo( 100, 0, function () {
				$notice.slideUp( 100, function () {
					$notice.remove();
				} );
			} );
		} );

		// The core "X" dismiss button behaves like "Later".
		$notice.on( 'click', '.notice-dismiss', function () {
			send( 'later' );
		} );
	} );
}( jQuery ) );
