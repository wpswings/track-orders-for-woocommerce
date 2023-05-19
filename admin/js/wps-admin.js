//code for datatable

jQuery(document).ready(function() {



    	/*SETTING A DATEPICKER TO THE METABOX ON ORDER EDIT PAGE*/
	if( jQuery( '.wps_tofw_est_delivery_date' ).length > 0 ){

		jQuery( '.wps_tofw_est_delivery_date' ).datepicker({ minDate: new Date()});
		
    }
    
    /*SETTING A TIMEPICKER TO THE METABOX ON ORDER EDIT PAGE*/
	if( jQuery( '.wps_tofw_est_delivery_time' ).length > 0 ){

		jQuery( '.wps_tofw_est_delivery_time' ).timepicker();
	}
});
