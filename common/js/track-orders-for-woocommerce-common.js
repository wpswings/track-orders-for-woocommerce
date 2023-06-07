(function( $ ) {
	'use strict';

	/**
	 * All of the code for your common JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

})( jQuery );



	jQuery(document).ready(function($){
		jQuery(document).on( 'click', '.wps_export', function(e){
			e.preventDefault();
	
			jQuery.ajax({
				url:tofw_common_param.ajaxurl,
				type:"POST",
				datatType: 'JSON',
				data: {
					action : 'wps_wot_export_my_orders',
					// nonce : tofw_common_param.nonce,
				},success:function(response){
					var result = JSON.parse(response);
					if( 'success' == result.status ) {
						var filename = result.file_name;
						var order_data = result.order_data;
						var filename = filename + '.csv';
						let csvContent = "data:text/csv;charset=utf-8,";
						order_data.forEach(function(rowArray) {
						   let row = rowArray;
						   csvContent += row + "\r\n";
								 });
					   
					   var encodedUri = encodeURI(csvContent);
							download(filename, encodedUri);
					}
				
				}	
	
			});
	
		} );
	
		jQuery(document).on( 'click', '.wps_tofw_guest_user_export_button', function(e){
			e.preventDefault();
			var email = jQuery(this).parent().find( '.wps_wot_export_email' ).val();
			
			jQuery.ajax({
				url:tofw_common_param.ajaxurl,
				type:"POST",
				datatType: 'JSON',
				data: {
					action : 'wps_tofw_export_my_orders_guest_user',
					email  : email,
					nonce : tofw_common_param.nonce,
				},success:function(response){
					var result = JSON.parse(response);
					if( 'success' == result.status ) {
						var filename = result.file_name;
						var order_data = result.order_data;
						var filename = filename + '.csv';
						let csvContent = "data:text/csv;charset=utf-8,";
						order_data.forEach(function(rowArray) {
						   let row = rowArray;
						   csvContent += row + "\r\n";
								 });
					   
					   var encodedUri = encodeURI(csvContent);
							download(filename, encodedUri);
					}
				
				}	
	
			});
	
		} );
	
		function download(filename, text) {
			var element = document.createElement('a');
			element.setAttribute('href', text);
			element.setAttribute('download', filename);
			element.style.display = 'none';
			document.body.appendChild(element);
			// automatically run the click event for anchor tag
			element.click();
	   
			document.body.removeChild(element);
			
	
	   }
});