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
		var packed = wps_admin_param.wps_file_include + 'image/order-packed.png';
		var return_requested = wps_admin_param.wps_file_include + 'image/product-return.png';
		var return_approved = wps_admin_param.wps_file_include + 'image/product-approved.png';
		var return_cancelled = wps_admin_param.wps_file_include + 'image/product-cancelled.png';
		//exchange_request
		var exchange_requested = wps_admin_param.wps_file_include + 'image/exchange_request.svg';
		var exchange_approved = wps_admin_param.wps_file_include + 'image/exchange_approved.svg';
		var exchange_cancel = wps_admin_param.wps_file_include + 'image/exchange_cancel.svg';
		var partially_cancel = wps_admin_param.wps_file_include + 'image/partially_cancelled.svg';

			jQuery('.wp-list-table .status-processing .order_status ').html('<mark class="order-status status-processing" title ="processing"><img src="' + processing + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-completed .order_status ').html('<mark class="order-status status-completed"  title ="completed"><img src="' + completed + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-on-hold .order_status ').html('<mark class="order-status status-on-hold"  title ="on hold"><img src="' + on_hold + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-pending .order_status ').html('<mark class="order-status status-pending"  title ="pending"><img src="' + pending + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-cancelled .order_status ').html('<mark class="order-status status-cancelled"  title ="cancelled"><img src="' + cancelled + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-failed .order_status ').html('<mark class="order-status status-failed"  title ="failed"><img src="' + failed + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-refunded .order_status ').html('<mark class="order-status status-refunded"  title ="refunded"><img src="' + refunded + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-dispatched .order_status ').html('<mark class="order-status status-dispatched"  title ="dispatched"><img src="' + dispatched + '" height="50" width="50"></mark> ');
			jQuery('.wp-list-table .status-shipped .order_status ').html('<mark class="order-status status-shipped"  title ="shipped"><img src="' + shipped + '" height="50" width="50"></mark> ');
		jQuery('.wp-list-table .status-packed .order_status ').html('<mark class="order-status status-packed"  title ="packed"><img src="' + packed + '" height="50" width="50"></mark> ');

		jQuery('.wp-list-table .status-return-requested .order_status ').html('<mark class="order-status status-return-requested"  title ="return requested"><img src="' + return_requested + '" height="50" width="50"></mark> ');


		jQuery('.wp-list-table .status-return-approved .order_status ').html('<mark class="order-status status-return-approved"  title ="return approved"><img src="' + return_approved + '" height="50" width="50"></mark> ');



		jQuery('.wp-list-table .status-return-cancelled .order_status ').html('<mark class="order-status status-return-cancelled"  title ="return cancelled"><img src="' + return_cancelled + '" height="50" width="50"></mark> ');


		//RMA Exchange.
		jQuery('.wp-list-table .status-exchange-request .order_status ').html('<mark class="order-status status-exchange-request"  title ="exchange reques"><img src="' + exchange_requested + '" height="50" width="50"></mark> ');


		jQuery('.wp-list-table .status-exchange-approve .order_status ').html('<mark class="order-status status-exchange-approve"  title ="exchange approved"><img src="' + exchange_approved + '" height="50" width="50"></mark> ');


		jQuery('.wp-list-table .status-exchange-cancel .order_status ').html('<mark class="order-status status-exchange-cancel"  title ="exchange cancel"><img src="' + exchange_cancel + '" height="50" width="50"></mark> ');


		jQuery('.wp-list-table .status-partial-cancel .order_status ').html('<mark class="order-status status-partial-cancel"  title ="partial cancel"><img src="' + partially_cancel + '" height="50" width="50"></mark> ');
	   
		var custom_url = wps_admin_param.custom_order_status_url;
		
			if( custom_url != '' ){
			   
				jQuery.each(custom_url, function (key, value) {
					jQuery('.wp-list-table .status-' + key + ' .order_status ').html('<mark class="order-status status-'+ key +'"title="'+ key + '"><img src="' + value + '" height="50" width="50"></mark> ');
				  })
			   
			}
	   }

});
