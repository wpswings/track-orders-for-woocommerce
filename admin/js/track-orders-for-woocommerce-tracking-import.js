/**
 * Bulk tracking import (CSV) screen.
 *
 * @since 1.3.0
 */
( function ( $ ) {
	'use strict';

	if ( 'undefined' === typeof wps_tofw_import ) {
		return;
	}

	var cfg = wps_tofw_import;
	var i18n = cfg.i18n;
	var jobId = '';

	var $root = $( '#wps-tofw-import' );
	if ( ! $root.length ) {
		return;
	}

	var $uploadMsg = $( '#wps-tofw-import-upload-message' );
	var $validate = $root.find( '.wps-tofw-import__validate' );
	var $preview = $( '#wps-tofw-import-preview' );
	var $progress = $( '#wps-tofw-import-progress' );

	function esc( value ) {
		return $( '<div/>' ).text( null === value || undefined === value ? '' : String( value ) ).html();
	}

	function message( $el, text, isError ) {
		$el.text( text || '' ).toggleClass( 'is-error', !! isError );
	}

	function setBar( $scope, done, total ) {
		var pct = total > 0 ? Math.round( ( done / total ) * 100 ) : 0;
		$scope.find( '.wps-tofw-import__bar span' ).css( 'width', pct + '%' );
		return pct;
	}

	function reportUrl() {
		return cfg.report_url + '&job_id=' + encodeURIComponent( jobId );
	}

	function post( action, data ) {
		return $.post( cfg.ajaxurl, $.extend( { action: action, nonce: cfg.nonce, job_id: jobId }, data || {} ) );
	}

	function failure( $el ) {
		return function ( xhr ) {
			var msg = xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : i18n.request_error;
			message( $el, msg, true );
		};
	}

	// Step 1: upload.
	$( '#wps-tofw-import-form' ).on( 'submit', function ( e ) {
		e.preventDefault();

		var input = document.getElementById( 'wps-tofw-import-file' );
		if ( ! input.files || ! input.files.length ) {
			message( $uploadMsg, i18n.choose_file, true );
			return;
		}

		var formData = new FormData();
		formData.append( 'action', 'wps_tofw_tracking_import_upload' );
		formData.append( 'nonce', cfg.nonce );
		formData.append( 'wps_tofw_import_file', input.files[0] );

		$( '#wps-tofw-import-upload' ).prop( 'disabled', true );
		$preview.prop( 'hidden', true );
		$progress.prop( 'hidden', true );
		message( $uploadMsg, i18n.uploading, false );

		$.ajax( {
			url: cfg.ajaxurl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false
		} ).done( function ( res ) {
			if ( ! res || ! res.success ) {
				message( $uploadMsg, res && res.data ? res.data.message : i18n.request_error, true );
				$( '#wps-tofw-import-upload' ).prop( 'disabled', false );
				return;
			}
			jobId = res.data.job_id;
			message( $uploadMsg, '', false );
			$validate.prop( 'hidden', false );
			validateNext( res.data.total );
		} ).fail( function ( xhr ) {
			failure( $uploadMsg )( xhr );
			$( '#wps-tofw-import-upload' ).prop( 'disabled', false );
		} );
	} );

	// Step 2: chunked validation (dry run).
	function validateNext( total ) {
		post( 'wps_tofw_tracking_import_validate' ).done( function ( res ) {
			if ( ! res || ! res.success ) {
				message( $uploadMsg, res && res.data ? res.data.message : i18n.request_error, true );
				$( '#wps-tofw-import-upload' ).prop( 'disabled', false );
				return;
			}
			var d = res.data;
			var pct = setBar( $validate, d.validated, d.total );
			$validate.find( '.wps-tofw-import__status' ).text( i18n.validating + ': ' + d.validated + ' / ' + d.total + ' (' + pct + '%)' );
			if ( d.done ) {
				$( '#wps-tofw-import-upload' ).prop( 'disabled', false );
				renderPreview( d.summary, d.rows );
			} else {
				validateNext( total );
			}
		} ).fail( function ( xhr ) {
			failure( $uploadMsg )( xhr );
			$( '#wps-tofw-import-upload' ).prop( 'disabled', false );
		} );
	}

	function renderPreview( summary, rows ) {
		$preview.find( '.wps-tofw-import__summary' ).html(
			'<span>' + esc( i18n.rows ) + ': ' + esc( summary.total ) + '</span>' +
			'<span class="is-valid">' + esc( i18n.valid ) + ': ' + esc( summary.valid ) + '</span>' +
			'<span class="is-invalid">' + esc( i18n.invalid ) + ': ' + esc( summary.invalid ) + '</span>'
		);

		var html = '';
		$.each( rows, function ( _, row ) {
			html += '<tr>' +
				'<td>' + esc( row.line ) + '</td>' +
				'<td class="' + ( row.valid ? 'is-valid' : 'is-invalid' ) + '">' + esc( row.valid ? i18n.valid : i18n.invalid ) + '</td>' +
				'<td>' + esc( row.order_id ) + '</td>' +
				'<td>' + esc( row.carrier ) + '</td>' +
				'<td>' + esc( row.tracking_number ) + '</td>' +
				'<td>' + esc( row.ship_date ) + '</td>' +
				'<td>' + esc( row.items ) + '</td>' +
				'<td>' + esc( row.status ) + '</td>' +
				'<td>' + esc( ( row.errors || [] ).join( ' ' ) ) + '</td>' +
				'</tr>';
		} );
		$preview.find( 'tbody' ).html( html );
		$preview.find( '.wps-tofw-import__note' ).text( i18n.truncated ).prop( 'hidden', rows.length >= summary.total );

		$( '#wps-tofw-import-report' ).attr( 'href', reportUrl() ).prop( 'hidden', summary.invalid < 1 );
		$( '#wps-tofw-import-start' ).prop( 'disabled', summary.valid < 1 );
		$preview.prop( 'hidden', false );

		if ( summary.valid < 1 ) {
			message( $uploadMsg, i18n.no_valid, true );
		}
	}

	// Step 3: start the background import and poll progress.
	$( '#wps-tofw-import-start' ).on( 'click', function () {
		if ( ! jobId || ! window.confirm( i18n.confirm ) ) {
			return;
		}
		var $btn = $( this ).prop( 'disabled', true );
		var $status = $progress.find( '.wps-tofw-import__status' );

		post( 'wps_tofw_tracking_import_start', { notify: $( '#wps-tofw-import-notify' ).val() } ).done( function ( res ) {
			if ( ! res || ! res.success ) {
				message( $status, res && res.data ? res.data.message : i18n.request_error, true );
				$btn.prop( 'disabled', false );
				return;
			}
			$( '#wps-tofw-import-form' ).find( 'input, button' ).prop( 'disabled', true );
			$progress.prop( 'hidden', false );
			renderProgress( res.data );
			schedulePoll();
		} ).fail( function ( xhr ) {
			$progress.prop( 'hidden', false );
			failure( $status )( xhr );
			$btn.prop( 'disabled', false );
		} );
	} );

	function renderProgress( d ) {
		var pct = setBar( $progress, d.processed, d.total );
		var $status = $progress.find( '.wps-tofw-import__status' );
		if ( d.done ) {
			message( $status, i18n.completed, false );
		} else if ( 'queued' === d.status && 0 === d.processed ) {
			message( $status, i18n.queued, false );
		} else {
			message( $status, i18n.importing + ': ' + d.processed + ' / ' + d.total + ' (' + pct + '%)', false );
		}
		$progress.find( '.wps-tofw-import__summary' ).html(
			'<span class="is-valid">' + esc( i18n.imported ) + ': ' + esc( d.succeeded ) + '</span>' +
			'<span class="is-invalid">' + esc( i18n.failed ) + ': ' + esc( d.failed ) + '</span>' +
			'<span class="is-invalid">' + esc( i18n.invalid ) + ': ' + esc( d.invalid ) + '</span>'
		);
		$( '#wps-tofw-import-final-report' ).attr( 'href', reportUrl() ).prop( 'hidden', ! ( d.done && ( d.failed > 0 || d.invalid > 0 ) ) );
	}

	function schedulePoll() {
		window.setTimeout( function () {
			post( 'wps_tofw_tracking_import_progress' ).done( function ( res ) {
				if ( res && res.success ) {
					renderProgress( res.data );
					if ( ! res.data.done ) {
						schedulePoll();
					}
				} else {
					schedulePoll();
				}
			} ).fail( function () {
				schedulePoll();
			} );
		}, parseInt( cfg.poll_ms, 10 ) || 3000 );
	}
}( jQuery ) );
