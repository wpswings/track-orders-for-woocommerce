/**
 * Vendor shipment tracking form (Dokan / WCFM / WC Vendors dashboards).
 *
 * @since 1.3.0
 * @package Track_Orders_For_Woocommerce
 */
( function ( $ ) {
	'use strict';

	if ( 'undefined' === typeof wps_tofw_vendor_tracking ) {
		return;
	}

	var cfg = wps_tofw_vendor_tracking;

	function notice( $box, message, isError ) {
		$box.find( '.wps-tofw-vt-notice' )
			.text( message || '' )
			.toggleClass( 'wps-tofw-vt-error', !! isError )
			.toggleClass( 'wps-tofw-vt-success', ! isError );
	}

	function send( $box, data, $button ) {
		data.nonce       = cfg.nonce;
		data.marketplace = $box.data( 'marketplace' );
		data.order_id    = $box.data( 'order' );

		$button.prop( 'disabled', true );
		$box.addClass( 'wps-tofw-vt-loading' );

		$.post( cfg.ajax_url, data )
			.done( function ( response ) {
				if ( response && response.success ) {
					$box.find( '.wps-tofw-vt-inner' ).html( response.data.html );
					notice( $box, response.data.message, false );
				} else {
					notice( $box, response && response.data && response.data.message ? response.data.message : cfg.i18n.error, true );
				}
			} )
			.fail( function ( xhr ) {
				var json = xhr && xhr.responseJSON;
				notice( $box, json && json.data && json.data.message ? json.data.message : cfg.i18n.error, true );
			} )
			.always( function () {
				$button.prop( 'disabled', false );
				$box.removeClass( 'wps-tofw-vt-loading' );
			} );
	}

	$( document ).on( 'change', '.wps-tofw-vt-carrier', function () {
		$( this ).closest( '.wps-tofw-vt-form' ).find( '.wps-tofw-vt-custom' ).toggle( 'other' === $( this ).val() );
	} );

	$( document ).on( 'change', '.wps-tofw-vt-item-check', function () {
		$( this ).closest( 'tr' ).find( '.wps-tofw-vt-item-qty' ).prop( 'disabled', ! this.checked );
	} );

	$( document ).on( 'click', '.wps-tofw-vt-save', function ( e ) {
		e.preventDefault();
		var $button = $( this ),
			$box    = $button.closest( '.wps-tofw-vt-box' ),
			$form   = $box.find( '.wps-tofw-vt-form' ),
			data    = {
				action: 'wps_tofw_save_vendor_shipment',
				carrier: $form.find( '.wps-tofw-vt-carrier' ).val(),
				carrier_name: $form.find( '.wps-tofw-vt-carrier-name' ).val(),
				tracking_url: $form.find( '.wps-tofw-vt-tracking-url' ).val(),
				tracking_number: $.trim( $form.find( '.wps-tofw-vt-tracking-number' ).val() ),
				date: $form.find( '.wps-tofw-vt-date' ).val()
			},
			hasItems = false;

		if ( ! data.tracking_number ) {
			notice( $box, cfg.i18n.no_tracking, true );
			return;
		}

		$form.find( '.wps-tofw-vt-items tbody tr' ).each( function () {
			var $row = $( this ),
				qty  = parseInt( $row.find( '.wps-tofw-vt-item-qty' ).val(), 10 );
			if ( $row.find( '.wps-tofw-vt-item-check' ).is( ':checked' ) && qty > 0 ) {
				data[ 'items[' + $row.data( 'item' ) + ']' ] = qty;
				hasItems = true;
			}
		} );

		if ( ! hasItems ) {
			notice( $box, cfg.i18n.no_items, true );
			return;
		}

		send( $box, data, $button );
	} );

	$( document ).on( 'click', '.wps-tofw-vt-delete', function ( e ) {
		e.preventDefault();
		if ( ! window.confirm( cfg.i18n.confirm_delete ) ) {
			return;
		}
		var $button = $( this );
		send(
			$button.closest( '.wps-tofw-vt-box' ),
			{
				action: 'wps_tofw_delete_vendor_shipment',
				shipment_id: $button.data( 'shipment' )
			},
			$button
		);
	} );
}( jQuery ) );
