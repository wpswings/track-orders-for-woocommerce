//code for datatable

jQuery(document).ready(function() {

    	/*SETTING A DATEPICKER TO THE METABOX ON ORDER EDIT PAGE*/
	if( jQuery( '.wps_tofw_est_delivery_date' ).length > 0 ){

		jQuery( '.wps_tofw_est_delivery_date' ).datepicker({ minDate: new Date()});
		
    }
    
    /*SETTING A TIMEPICKER TO THE METABOX ON ORDER EDIT PAGE*/
	if( jQuery( '.wps_tofw_est_delivery_time' ).length > 0 ){

		jQuery('.wps_tofw_est_delivery_time').timepicker();
	
	}

	 // status icon
	var is_enable_status_icon = wps_admin_param.is_enable_status_icon;

	if ('on' == is_enable_status_icon) {
		var processing = wps_admin_param.wps_file_include + 'image/processing1.png';
		var completed = wps_admin_param.wps_file_include + 'image/deliver1.png';
		var on_hold = wps_admin_param.wps_file_include + 'image/approved1.png';
		var pending =  wps_admin_param.wps_file_include + 'image/order-pending.jpeg';
		var cancelled =  wps_admin_param.wps_file_include + 'image/cancel1.png';
		var failed =  wps_admin_param.wps_file_include + 'image/order-cancelled.png';
		var refunded =  wps_admin_param.wps_file_include + 'image/revert.png';
		var dispatched =  wps_admin_param.wps_file_include + 'image/dispatch.png';
		var shipped =  wps_admin_param.wps_file_include + 'image/shipped.png';
		var packed =  wps_admin_param.wps_file_include + 'image/order-packed.png';
			jQuery('.wp-list-table .status-processing .order_status ').html('<mark class="order-status status-processing" ><img src="' + processing + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-completed .order_status ').html('<mark class="order-status status-completed" ><img src="' + completed + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-on-hold .order_status ').html('<mark class="order-status status-on-hold" ><img src="' + on_hold + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-pending .order_status ').html('<mark class="order-status status-pending" ><img src="' + pending + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-cancelled .order_status ').html('<mark class="order-status status-cancelled" ><img src="' + cancelled + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-failed .order_status ').html('<mark class="order-status status-failed" ><img src="' + failed + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-refunded .order_status ').html('<mark class="order-status status-refunded" ><img src="' + refunded + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-dispatched .order_status ').html('<mark class="order-status status-dispatched" ><img src="' + dispatched + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-shipped .order_status ').html('<mark class="order-status status-shipped" ><img src="' + shipped + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-packed .order_status ').html('<mark class="order-status status-packed" ><img src="' + packed + '" height="50" width="50"></mark> ');
	   
		var custom_url = wps_admin_param.custom_order_status_url;
		
			if( custom_url != '' ){
			   
				jQuery.each(custom_url, function (key, value) {
					console.log('prince');
				   jQuery('.wp-list-table .status-' + key + ' .order_status ').html('<mark class="order-status status-'+ key +'" ><img src="' + value + '" height="50" width="50"></mark> ');
				 });
			   
			}
	   }

});
