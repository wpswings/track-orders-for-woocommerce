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
		var processing = wps_admin_param.wps_file_include + 'image/Processing.png';//
		var completed = wps_admin_param.wps_file_include + 'image/Complete.png';//
		var on_hold = wps_admin_param.wps_file_include + 'image/On-hold.png';//
		var pending =  wps_admin_param.wps_file_include + 'image/pending.png';//
		var cancelled =  wps_admin_param.wps_file_include + 'image/Cancelled.png';//
		var failed =  wps_admin_param.wps_file_include + 'image/Failed.png';//
		var refunded =  wps_admin_param.wps_file_include + 'image/Refund.png';//
		var dispatched =  wps_admin_param.wps_file_include + 'image/Dispatched.png';//
		var shipped =  wps_admin_param.wps_file_include + 'image/Order-Shipped.png';//
		var packed = wps_admin_param.wps_file_include + 'image/Order-Packed.png';//
		var return_requested = wps_admin_param.wps_file_include + 'image/product-return.png';
		var return_approved = wps_admin_param.wps_file_include + 'image/product-approved.png';
		var return_cancelled = wps_admin_param.wps_file_include + 'image/product-cancelled.png';
		//exchange_request
		var exchange_requested = wps_admin_param.wps_file_include + 'image/exchange_request.svg';
		var exchange_approved = wps_admin_param.wps_file_include + 'image/exchange_approved.svg';
		var exchange_cancel = wps_admin_param.wps_file_include + 'image/exchange_cancel.svg';
		var partially_cancel = wps_admin_param.wps_file_include + 'image/draft.png';

			jQuery('.wp-list-table .status-processing .order_status ').html('<mark class="order-status wps-tofw_status-icon status-processing" title ="processing"><img src="' + processing + '" height="40" width="40"></mark> ');
			jQuery('.wp-list-table .status-completed .order_status ').html('<mark class="order-status wps-tofw_status-icon status-completed"  title ="completed"><img src="' + completed + '" height="40" width="40"></mark> ');
			jQuery('.wp-list-table .status-on-hold .order_status ').html('<mark class="order-status wps-tofw_status-icon status-on-hold"  title ="on hold"><img src="' + on_hold + '" height="40" width="40"></mark> ');
			jQuery('.wp-list-table .status-pending .order_status ').html('<mark class="order-status wps-tofw_status-icon status-pending"  title ="pending"><img src="' + pending + '" height="40" width="40"></mark> ');
			jQuery('.wp-list-table .status-cancelled .order_status ').html('<mark class="order-status wps-tofw_status-icon status-cancelled"  title ="cancelled"><img src="' + cancelled + '" height="40" width="40"></mark> ');
			jQuery('.wp-list-table .status-failed .order_status ').html('<mark class="order-status wps-tofw_status-icon status-failed"  title ="failed"><img src="' + failed + '" height="40" width="40"></mark> ');
			jQuery('.wp-list-table .status-refunded .order_status ').html('<mark class="order-status wps-tofw_status-icon status-refunded"  title ="refunded"><img src="' + refunded + '" height="40" width="40"></mark> ');
			jQuery('.wp-list-table .status-dispatched .order_status ').html('<mark class="order-status wps-tofw_status-icon status-dispatched"  title ="dispatched"><img src="' + dispatched + '" height="40" width="40"></mark> ');
			jQuery('.wp-list-table .status-shipped .order_status ').html('<mark class="order-status wps-tofw_status-icon status-shipped"  title ="shipped"><img src="' + shipped + '" height="40" width="40"></mark> ');
		jQuery('.wp-list-table .status-packed .order_status ').html('<mark class="order-status wps-tofw_status-icon status-packed"  title ="packed"><img src="' + packed + '" height="40" width="40"></mark> ');

		jQuery('.wp-list-table .status-return-requested .order_status ').html('<mark class="order-status wps-tofw_status-icon status-return-requested"  title ="return requested"><img src="' + return_requested + '" height="40" width="40"></mark> ');


		jQuery('.wp-list-table .status-return-approved .order_status ').html('<mark class="order-status wps-tofw_status-icon status-return-approved"  title ="return approved"><img src="' + return_approved + '" height="40" width="40"></mark> ');



		jQuery('.wp-list-table .status-return-cancelled .order_status ').html('<mark class="order-status wps-tofw_status-icon status-return-cancelled"  title ="return cancelled"><img src="' + return_cancelled + '" height="40" width="40"></mark> ');


		//RMA Exchange.
		jQuery('.wp-list-table .status-exchange-request .order_status ').html('<mark class="order-status wps-tofw_status-icon status-exchange-request"  title ="exchange reques"><img src="' + exchange_requested + '" height="40" width="40"></mark> ');


		jQuery('.wp-list-table .status-exchange-approve .order_status ').html('<mark class="order-status wps-tofw_status-icon status-exchange-approve"  title ="exchange approved"><img src="' + exchange_approved + '" height="40" width="40"></mark> ');


		jQuery('.wp-list-table .status-exchange-cancel .order_status ').html('<mark class="order-status wps-tofw_status-icon status-exchange-cancel"  title ="exchange cancel"><img src="' + exchange_cancel + '" height="40" width="40"></mark> ');


		jQuery('.wp-list-table .status-partial-cancel .order_status ').html('<mark class="order-status wps-tofw_status-icon status-partial-cancel"  title ="partial cancel"><img src="' + partially_cancel + '" height="40" width="40"></mark> ');
	   
		var custom_url = wps_admin_param.custom_order_status_url;
		
			if( custom_url != '' ){
			   
				jQuery.each(custom_url, function (key, value) {
					jQuery('.wp-list-table .status-' + key + ' .order_status ').html('<mark class="order-status wps-tofw_status-icon status-'+ key +'"title="'+ key + '"><img src="' + value + '" height="40" width="40"></mark> ');
				  })
			   
			}
	   }

});
