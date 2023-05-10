(function($) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
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

  $(document).ready(function() {
    const MDCText = mdc.textField.MDCTextField;
    const textField = [].map.call(
      document.querySelectorAll(".mdc-text-field"),
      function(el) {
        return new MDCText(el);
      }
    );
    const MDCRipple = mdc.ripple.MDCRipple;
    const buttonRipple = [].map.call(
      document.querySelectorAll(".mdc-button"),
      function(el) {
        return new MDCRipple(el);
      }
    );
    const MDCSwitch = mdc.switchControl.MDCSwitch;
    const switchControl = [].map.call(
      document.querySelectorAll(".mdc-switch"),
      function(el) {
        return new MDCSwitch(el);
      }
    );

    $(".wps-password-hidden").click(function() {
      if ($(".wps-form__password").attr("type") == "text") {
        $(".wps-form__password").attr("type", "password");
      } else {
        $(".wps-form__password").attr("type", "text");
      }
    });
  });

  $(window).load(function() {
    // add select2 for multiselect.
    if ($(document).find(".wps-defaut-multiselect").length > 0) {
      $(document)
        .find(".wps-defaut-multiselect")
        .select2();
    }
  });
})(jQuery);

jQuery(document).ready(function ($) {

  jQuery('body').on('click','.wps_enhanced_tofw_remove',function(e){
    e.preventDefault();
    var wps_enhanced_tofw_remove=jQuery(this).data("id");
    jQuery("#wps_enhanced_tofw_class"+wps_enhanced_tofw_remove).remove();
      jQuery.ajax({
      url:ajax_url,
      type:"POST",
      data: {
        action : 'wps_provider_remove_company_data_from_plugin',
        wps_company_name:wps_enhanced_tofw_remove,
        nonce : tofw_admin_param.wps_tofw_nonce,
      },success:function(response){
        // console.log( response );
      
      }	

      });
  });
  //jquery add buttton
    jQuery('#wps_tofw_enhanced_woocommerce_shipment_tracking_add_providers').on('click',function(e){
        var wps_company_name=jQuery('.wps_toy_enhanced_provider').val();
        var wps_company_url=jQuery('.wps_toy_enhanced_provider_url').val();
        jQuery.ajax({
        url:ajax_url,
        type:"POST",
        data: {
          action : 'wps_provider_subbmission_data_from_plugin',
          nonce : tofw_admin_param.wps_tofw_nonce,
          wps_company_name : wps_company_name,
          wps_company_url : wps_company_url
        },success:function(response){
          jQuery('.wps_toy_enhanced_provider').val("");
          jQuery('.wps_toy_enhanced_provider_url').val("");
          var  wps_append="<div class='wps-tyo-courier-data' id='wps_enhanced_tofw_class"+wps_company_name+"'>";
            wps_append+="<input type='checkbox' id='wps_enhanced_checkbox"+wps_company_name+"' name='wps_tofw_courier_url["+wps_company_name+"]' value='"+wps_company_url+"'>";
                wps_append+="<label for='wps_enhanced_checkbox"+wps_company_name+"'>"+wps_company_name+"</label>";
                  wps_append+='<a href="#" id="wps_enhanced_cross'+wps_company_name+'" class="wps_enhanced_tofw_remove" data-id="'+wps_company_name+'">X</a></div>';
          jQuery(wps_append).appendTo(".wps_tofw_courier_content");
          
        }	

      });
      
    });
  
  // for custom order status image icons 
	jQuery('.wps_tofw_other_setting_upload_logo').click(function(){
    var imageurl = jQuery("#wps_tofw_other_setting_upload_logo").val();

        tb_show('', 'media-upload.php?TB_iframe=true');

        window.send_to_editor = function(html)
        {
           var imageurl = jQuery(html).attr('href');
          
           if(typeof imageurl == 'undefined')
           {
             imageurl = jQuery(html).attr('src');
           }
           var last_index = imageurl.lastIndexOf('/');
            var url_last_part = imageurl.substr(last_index+1);
            if( url_last_part == '' ){
              
              imageurl = jQuery(html).children("img").attr("src");  
            }   
           jQuery("#wps_tofw_other_setting_upload_logo").val(imageurl);
           jQuery("#wps_tofw_other_setting_upload_image").attr("src",imageurl);
           jQuery("#wps_tofw_other_setting_remove_logo").show();
           tb_remove();
        };
        return false;
  });


  jQuery(document).on('click','input#wps_tofw_create_role_box',function(){
    jQuery(this).toggleClass('role_box_open');
    jQuery("div#wps_tofw_create_box").slideToggle();
    if(jQuery(this).hasClass('role_box_open')) {
      jQuery(this).val(tofw_admin_param.wps_tofw_close_button);
    }
    else {
      jQuery(this).val('Create Custom Order Status');
    }
  });
  
  
  jQuery(document).on('click','input#wps_tofw_create_custom_order_status',function(){
    jQuery('#wps_tofw_send_loading').show();
    var wps_tofw_create_order_status = jQuery('#wps_tofw_create_order_name').val().trim();
    var wps_order_image_url = jQuery(document).find('#wps_tofw_other_setting_upload_logo').val();
    if(wps_tofw_create_order_status != "" && wps_tofw_create_order_status != null) 
    {
      if( /^[a-zA-Z0-9- ]*$/.test(wps_tofw_create_order_status) )
      {
        wps_tofw_create_order_status = wps_tofw_create_order_status;
  
        jQuery.ajax({
          url : tofw_admin_param.ajaxurl,
          type : 'post',
          data : {
            action : 'wps_tofw_create_custom_order_status',
            wps_tofw_new_role_name : wps_tofw_create_order_status,
            wps_custom_order_image_url : wps_order_image_url,
            nonce : tofw_admin_param.wps_tofw_nonce,
          },
          success : function( response ) {
            jQuery('#wps_tofw_send_loading').hide();
  
            if(response == "success") {
              jQuery('#wps_tofw_other_setting_upload_logo').val('');
              jQuery('input#wps_tofw_create_role_box').trigger('click');
              jQuery("div.wps_notices_order_tracker").html('<div id="message" class="notice notice-success"><p><strong>'+tofw_admin_param.message_success+'</strong></p></div>');
              jQuery('#wps_tofw_create_order_name').val('');
              location.reload();
            }
            else {
              jQuery("div.wps_notices_order_tracker").html('<div id="message" class="notice notice-error"><p><strong>'+tofw_admin_param.message_error_save+'</strong></p></div>').delay(2000).fadeOut(function(){});
            }	
          }
        });
      }
      else{
        jQuery('#wps_tofw_send_loading').hide();
        jQuery("div.wps_notices_order_tracker").html( '<div id="message" class="notice notice-error"><p><strong>'+tofw_admin_param.message_invalid_input+'</strong></p></div>' ).delay(4000).fadeOut(function(){});
        return;
      }	
    }else{
      jQuery('#wps_tofw_send_loading').hide();
      jQuery("div.wps_notices_order_tracker").html( '<div id="message" class="notice notice-error"><p><strong>'+tofw_admin_param.message_empty_data+'</strong></p></div>' ).delay(4000).fadeOut(function(){});
      return;
    }
    jQuery('#wps_tofw_send_loading').hide();
  
  });




  // delete custom status
  jQuery(document.body).on('click','.wps_delete_costom_order',function(){
		var wps_action=jQuery(this).data('action');
		var wps_key=jQuery(this).data('key');
		jQuery.ajax({
			url: tofw_admin_param.ajaxurl,
			type : 'post',
			data:{
				action : 'wps_tofw_delete_custom_order_status',
				wps_custom_action : wps_action,
				wps_custom_key	: wps_key,
				nonce : tofw_admin_param.wps_tofw_nonce,
			},
			success: function(response){
				if(response=='success')
				{
					location.reload();
				}

			}

    });
    

  });
  

  jQuery(document).on('click','.activate_button',function(e){
		
		var selectd_template_name=jQuery(this).attr('data-id');
		var activated_value='yes';
		jQuery.ajax({
			url : tofw_admin_param.ajaxurl,
			type : 'post',
			data : {
				action : 'wps_selected_template',
				selected_button_value : activated_value,
				template_name:selectd_template_name,
				nonce : tofw_admin_param.wps_tofw_nonce,
			},
			success : function( response ) {
				
				if(response == "success") {
					jQuery("div.wps_notices_templates_order_tracker").html('<div id="message" class="notice notice-success"><p><strong>'+tofw_admin_param.message_template_activated+'</strong></p></div>').delay(50000).fadeOut(function(){});
					
					location.reload();
				}
			}
		});
	});

	jQuery(document).on('click','#wps_tofw_preview_first',function(){
		jQuery('#wps_template_2').show();
		jQuery('#wps_template_3').hide();
		jQuery('#wps_template_1').hide();
		jQuery('#wps_template_4').hide();
		jQuery('#wps_new_template_1').hide();
		jQuery('#wps_new_template_2').hide();
		jQuery('#wps_new_template_3').hide();
	});
	jQuery(document).on('click','#wps_tofw_preview_second',function(){
		jQuery('#wps_template_3').show();
		jQuery('#wps_template_2').hide();
		jQuery('#wps_template_1').hide();
		jQuery('#wps_template_4').hide();
		jQuery('#wps_new_template_1').hide();
		jQuery('#wps_new_template_2').hide();
		jQuery('#wps_new_template_3').hide();

	});
	jQuery(document).on('click','#wps_tofw_preview_third',function(){
		jQuery('#wps_template_1').show();
		jQuery('#wps_template_2').hide();
		jQuery('#wps_template_3').hide();
		jQuery('#wps_template_4').hide();
		jQuery('#wps_new_template_1').hide();
		jQuery('#wps_new_template_2').hide();
		jQuery('#wps_new_template_3').hide();
	});
	jQuery(document).on('click','#wps_tofw_preview_fourth',function(){
		jQuery('#wps_template_4').show();
		jQuery('#wps_template_1').hide();
		jQuery('#wps_template_2').hide();
		jQuery('#wps_template_3').hide();
		jQuery('#wps_new_template_1').hide();
		jQuery('#wps_new_template_2').hide();
		jQuery('#wps_new_template_3').hide();
	});
	jQuery(document).on('click','#wps_tofw_preview_new_template_1',function(){
		
		jQuery('#wps_new_template_1').show();
		jQuery('#wps_template_1').hide();
		jQuery('#wps_template_2').hide();
		jQuery('#wps_template_4').hide();
		jQuery('#wps_template_3').hide();
		jQuery('#wps_new_template_2').hide();
		jQuery('#wps_new_template_3').hide();
	});
	jQuery(document).on('click','#wps_tofw_preview_new_template_2',function(){
		jQuery('#wps_new_template_2').show();
		jQuery('#wps_template_1').hide();
		jQuery('#wps_template_2').hide();
		jQuery('#wps_template_4').hide();
		jQuery('#wps_template_3').hide();
		jQuery('#wps_new_template_1').hide();
		jQuery('#wps_new_template_3').hide();
		
	});
	jQuery(document).on('click','#wps_tofw_preview_new_template_3',function(){
		jQuery('#wps_new_template_3').show();
		jQuery('#wps_template_1').hide();
		jQuery('#wps_template_2').hide();
		jQuery('#wps_template_4').hide();
		jQuery('#wps_template_3').hide();
		jQuery('#wps_new_template_1').hide();
		jQuery('#wps_new_template_2').hide();
	});
	

	jQuery('.hidden_wrapper').hide();
	jQuery(document).on('click','#wps_tofw_preview_first',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#wps_tofw_preview_second',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#wps_tofw_preview_third',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery('.hidden_wrapper').hide();
	jQuery(document).on('click','#wps_tofw_preview_fourth',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#wps_tofw_preview_new_template_1',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#wps_tofw_preview_new_template_3',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#wps_tofw_preview_new_template_2',function(){
		jQuery(".hidden_wrapper").show();
	});
	jQuery(document).on('click','#wps_template_1',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#wps_template_2',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#wps_template_3',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#wps_template_4',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#wps_new_template_1',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#wps_new_template_2',function(){
		jQuery(".hidden_wrapper").hide();
	});
	jQuery(document).on('click','#wps_new_template_3',function(){
		jQuery(".hidden_wrapper").hide();
  });
  


  jQuery('.wps_tofw_get_approval').click(function(){
		
		jQuery('#wps-tyo-modal').show();
	});

	jQuery('.wps-tyo-modal-close').click(function(){
		
		jQuery('#wps-tyo-modal').hide();
	});

	jQuery('.wps-tyo-underlay').click(function(){
		
		jQuery('#wps-tyo-modal').hide();
	});
	jQuery(document).on('click','#wps_buyer_submit_no',function(){
		
		jQuery('#wps-tyo-modal').hide();
	});
	

	jQuery(document).on('click','#wps_buyer_submit_yes',function(){
		var notification_value = jQuery(this).attr('data-accept_value');
		jQuery.ajax({
			url:ajaxurl,
			type:"POST",
			data: {
				action : 'wps_form_subbmission_data_from_plugin',
				notification : notification_value,
				nonce : tofw_admin_param.wps_tofw_nonce,
			},success:function(response){
				window.location.reload();
			}	

		});
	});
	
	var current_url = window.location.href;
	if( current_url.indexOf( 'tab=wps_tofw_settings&section=custom_status' ) > 0 )
	{
		jQuery( document ).find( '.woocommerce-save-button' ).hide();
	}
	if(current_url.indexOf('tab=wps_tofw_settings&section=templates') > 0)
	{
		jQuery( document ).find( '.woocommerce-save-button' ).hide();
	}
	jQuery("div#wps_wps_mail_success").hide();
	jQuery("div#wps_wps_mail_failure").hide();
	jQuery("div#wps_wps_mail_empty").hide();
	jQuery("div#wps_wps_invalid_input").hide();
	jQuery("div#wps_wps_select_for_delete").hide();

	/*SETTING A TIMEPICKER TO THE METABOX ON ORDER EDIT PAGE*/
	if( jQuery( '.wps_tofw_est_delivery_time' ).length > 0 ){

		jQuery( '.wps_tofw_est_delivery_time' ).timepicker();
	}

	/*SETTING A DATEPICKER TO THE METABOX ON ORDER EDIT PAGE*/
	if( jQuery( '.wps_tofw_est_delivery_date' ).length > 0 ){

		jQuery( '.wps_tofw_est_delivery_date' ).datepicker({ minDate: new Date()});
		
	}

	/*SHOW / HIDE FOR SELECTING THE USE OF CUSTOM ORDER STATUS */
	jQuery( '#wps_tofw_enable_custom_order_feature' ).on( 'change', function(){
		if ( jQuery( '#wps_tofw_enable_custom_order_feature' ).is( ':checked' ) ) {
			jQuery('#wps_tofw_new_custom_statuses_for_order_tracking').closest('tr').show();
		}else{
			jQuery('#wps_tofw_new_custom_statuses_for_order_tracking').closest('tr').hide();
		}
	} );

	if ( jQuery( '#wps_tofw_enable_custom_order_feature' ).is( ':checked' ) ) {
		jQuery('#wps_tofw_new_custom_statuses_for_order_tracking').closest('tr').show();
	}else{
		jQuery('#wps_tofw_new_custom_statuses_for_order_tracking').closest('tr').hide();
	}

	jQuery( '#wps_tofw_order_status_in_hidden' ).closest('tr').hide();


	var selected_order_status_approval = jQuery( '#wps_tofw_order_status_in_approval' ).val();
	var selected_order_status_processing = jQuery( '#wps_tofw_order_status_in_processing' ).val();
	var selected_order_status_shipping = jQuery( '#wps_tofw_order_status_in_shipping' ).val();

	jQuery.each( selected_order_status_processing , function( key , value ){
		jQuery("#wps_tofw_order_status_in_approval option[value="+value+"]").remove();
		jQuery("#wps_tofw_order_status_in_shipping option[value="+value+"]").remove();
	} );

	jQuery.each( selected_order_status_approval , function( key , value ){
		jQuery("#wps_tofw_order_status_in_processing option[value="+value+"]").remove();
		jQuery("#wps_tofw_order_status_in_shipping option[value="+value+"]").remove();
	} );

	jQuery.each( selected_order_status_shipping , function( key , value ){
		jQuery("#wps_tofw_order_status_in_processing option[value="+value+"]").remove();
		jQuery("#wps_tofw_order_status_in_approval option[value="+value+"]").remove();
	} );

	jQuery( document ).on( 'change' , '#wps_tofw_new_custom_statuses_for_order_tracking', function(){
		var selected_order_status_approval = jQuery( '#wps_tofw_order_status_in_approval' ).val();
		
		var selected_order_status_processing = jQuery( '#wps_tofw_order_status_in_processing' ).val();
		var selected_order_status_shipping = jQuery( '#wps_tofw_order_status_in_shipping' ).val();
		var notSelected = jQuery("#wps_tofw_new_custom_statuses_for_order_tracking").find('option').not(':selected');
		var array1 = notSelected.map(function () {
			return this.value;
		}).get();
		
		jQuery.each( array1 , function( key, val ){
			jQuery("#wps_tofw_order_status_in_processing option[value='"+val+"']").remove();
			jQuery("#wps_tofw_order_status_in_approval option[value='"+val+"']").remove();
			jQuery("#wps_tofw_order_status_in_shipping option[value='"+val+"']").remove();
			var value = val.replace( 'wc-' , '' );
			if( jQuery(document).find( '.select2-selection__choice' ).attr( 'title' ) == value )
			{
				jQuery(document).find( '.select2-selection__choice' ).attr( 'title', value ).remove();
			}
		} );
		var order_statuses = tofw_admin_param.order_statuses;
		var val = jQuery(this).val();


		jQuery.each( val , function(key , value){
			var status_name = order_statuses[value];

			if( status_name == '' || status_name == null ){
				status_name = value.replace( 'wc-' , '' );
			}
			var  l = '<option value='+value+'>'+status_name+'</option>';
			if((jQuery.inArray(value, selected_order_status_approval)==-1) && (jQuery.inArray(value,selected_order_status_processing)==-1) && (jQuery.inArray(value,selected_order_status_shipping)==-1) )
			{
				
				if( jQuery("#wps_tofw_order_status_in_processing option[value="+value+"]").length <= 0 ){
					jQuery('#wps_tofw_order_status_in_processing').append( l );
				}
				if( jQuery("#wps_tofw_order_status_in_approval option[value="+value+"]").length <= 0 ){
					jQuery('#wps_tofw_order_status_in_approval').append( l );
				}
				if( jQuery("#wps_tofw_order_status_in_shipping option[value="+value+"]").length <= 0 ){
					jQuery('#wps_tofw_order_status_in_shipping').append( l );
				}
			}
			
		} );

	} );


	jQuery( document ).on( 'change', '#wps_tofw_order_status_in_approval', function()
	{
		var order_statuses = tofw_admin_param.order_statuses;
		var existing_value =jQuery('#wps_tofw_new_custom_statuses_for_order_tracking').val();
		var status = [];
		var selected_order_status_approval = jQuery('#wps_tofw_order_status_in_approval' ).val();
		var selected_order_status_processing = jQuery('#wps_tofw_order_status_in_processing' ).val();
		var selected_order_status_shipping = jQuery( '#wps_tofw_order_status_in_shipping' ).val();
		var hidden_value = []; 
		var previously_selected_value = []; 
		var previously_selected_value = jQuery( '#wps_tofw_order_status_in_hidden' ).val();
		jQuery.each( selected_order_status_approval, function( key, value ){
			hidden_value.push( value );
		} );
	jQuery.each( selected_order_status_processing, function( key, value ){
		hidden_value.push( value );
	} );
	jQuery.each( selected_order_status_shipping, function( key, value ){
		hidden_value.push( value );
	} );
	
	
	jQuery( '#wps_tofw_order_status_in_hidden' ).val( hidden_value );
	var pre_length = 0 ;
	if(previously_selected_value != null && previously_selected_value.length != null && previously_selected_value.length != 0)
	{
		var pre_length = previously_selected_value.length;
	}
	var hidden_length = hidden_value.length;
	if( pre_length >= hidden_length )
	{
		var i = 0;
		jQuery.grep(previously_selected_value, function(el) {

			if (jQuery.inArray(el, hidden_value) == -1) 
			{
				var status_name = order_statuses[el];
				var  l = '<option value='+el+'>'+status_name+'</option>';
				
				jQuery('#wps_tofw_order_status_in_processing').append( l );
				jQuery('#wps_tofw_order_status_in_shipping').append( l );
			}


			i++;

		});
	}
	else if( pre_length <= hidden_length )
	{
		var i = 0;
		jQuery.grep(hidden_value, function(el) {

			if (jQuery.inArray(el, previously_selected_value) == -1) 
			{
				jQuery("#wps_tofw_order_status_in_processing option[value="+el+"]").remove();
				jQuery("#wps_tofw_order_status_in_shipping option[value="+el+"]").remove();

			}


			i++;

		});
	}

} );

	jQuery( document ).on( 'change', '#wps_tofw_order_status_in_processing', function()
	{
		var order_statuses = tofw_admin_param.order_statuses;
		var existing_value =jQuery('#wps_tofw_new_custom_statuses_for_order_tracking').val();
		var status = [];
		var selected_order_status_approval = jQuery( '#wps_tofw_order_status_in_approval' ).val();
		var selected_order_status_processing = jQuery( '#wps_tofw_order_status_in_processing' ).val();
		var selected_order_status_shipping = jQuery( '#wps_tofw_order_status_in_shipping' ).val();
		var hidden_value = [] ; 
		var previously_selected_value = []; 
		var previously_selected_value = jQuery( '#wps_tofw_order_status_in_hidden' ).val();
		jQuery.each( selected_order_status_approval, function( key, value ){
			hidden_value.push( value );
		} );
		jQuery.each( selected_order_status_processing, function( key, value ){
			hidden_value.push( value );
		} );
		jQuery.each( selected_order_status_shipping, function( key, value ){
			hidden_value.push( value );
		} );
		jQuery( '#wps_tofw_order_status_in_hidden' ).val( hidden_value );

		var pre_length = 0 ;
		if(previously_selected_value != null && previously_selected_value.length != null && previously_selected_value.length != 0)
		{
			var pre_length = previously_selected_value.length;
		}
		var hidden_length = hidden_value.length;

		if( pre_length >= hidden_length )
		{

			var i = 0;
			jQuery.grep(previously_selected_value, function(el) {

				if (jQuery.inArray(el, hidden_value) == -1) 
				{
					var status_name = order_statuses[el];
					var  l = '<option value='+el+'>'+status_name+'</option>';
					jQuery('#wps_tofw_order_status_in_approval').append( l );
					jQuery('#wps_tofw_order_status_in_shipping').append( l );
				}


				i++;

			});
		}
		else if( pre_length <= hidden_length )
		{
			var i = 0;
			jQuery.grep(hidden_value, function(el) {

				if (jQuery.inArray(el, previously_selected_value) == -1) 
				{

					jQuery("#wps_tofw_order_status_in_approval option[value="+el+"]").remove();
					jQuery("#wps_tofw_order_status_in_shipping option[value="+el+"]").remove();

				}


				i++;

			});
		}
	} );

	
	jQuery( document ).on( 'change', '#wps_tofw_order_status_in_shipping', function()
	{
		var order_statuses = tofw_admin_param.order_statuses;
	
	var status = [];
	var selected_order_status_approval = jQuery( '#wps_tofw_order_status_in_approval' ).val();
	var selected_order_status_processing = jQuery( '#wps_tofw_order_status_in_processing' ).val();
	var selected_order_status_shipping = jQuery( '#wps_tofw_order_status_in_shipping' ).val();
	
	var hidden_value = []; 
	var previously_selected_value = []; 
	var previously_selected_value = jQuery( '#wps_tofw_order_status_in_hidden' ).val();
	jQuery.each( selected_order_status_approval, function( key, value ){
		hidden_value.push( value );
	} );
	jQuery.each( selected_order_status_processing, function( key, value ){
		hidden_value.push( value );
	} );
	jQuery.each( selected_order_status_shipping, function( key, value ){
		hidden_value.push( value );
	} );

	jQuery( '#wps_tofw_order_status_in_hidden' ).val( hidden_value );

	var pre_length = 0 ;
	if(previously_selected_value != null && previously_selected_value.length != null && previously_selected_value.length != 0)
	{
		var pre_length = previously_selected_value.length;
	}
	var hidden_length = hidden_value.length;

	if( pre_length >= hidden_length )
	{
		var i = 0;
		jQuery.grep(previously_selected_value, function(el) {

			if (jQuery.inArray(el, hidden_value) == -1) 
			{
				var status_name = order_statuses[el];
				var  l = '<option value='+el+'>'+status_name+'</option>';
				jQuery('#wps_tofw_order_status_in_processing').append( l );
				jQuery('#wps_tofw_order_status_in_approval').append( l );
			}
			i++;

		});
	}
	else if( pre_length <= hidden_length )
	{
		var i = 0;
		jQuery.grep(hidden_value, function(el) {

			if (jQuery.inArray(el, previously_selected_value) == -1) 
			{

				jQuery("#wps_tofw_order_status_in_processing option[value="+el+"]").remove();
				jQuery("#wps_tofw_order_status_in_approval option[value="+el+"]").remove();

			}

			i++;
		});
	}
} );

  // Fedex js

  jQuery('#wps_fedex_userkey').closest('tr').hide();
	jQuery('#wps_fedex_userpassword').closest('tr').hide();
	jQuery('#wps_fedex_account_number').closest('tr').hide();
	jQuery('#wps_fedex_meter_number').closest('tr').hide();
	jQuery('#wps_tofw_enable_track_order_using_api').closest('tr').hide();
	jQuery('#wps_tofw_enable_canadapost_tracking').closest('tr').hide();
	jQuery('#wps_tofw_canadapost_tracking_user_key').closest('tr').hide();
	jQuery('#wps_tofw_canadapost_tracking_user_password').closest('tr').hide();
	
	jQuery( '#wps_tofw_enable_third_party_tracking_api' ).on( 'change', function(){
		if ( jQuery( '#wps_tofw_enable_third_party_tracking_api' ).is( ':checked' ) ) 
		{	
			jQuery('#wps_fedex_userkey').closest('tr').show();
			jQuery('#wps_fedex_userpassword').closest('tr').show();
			jQuery('#wps_fedex_account_number').closest('tr').show();
			jQuery('#wps_fedex_meter_number').closest('tr').show();
			jQuery('#wps_tofw_enable_track_order_using_api').closest('tr').show();
			jQuery('#wps_tofw_enable_canadapost_tracking').closest('tr').show();
			jQuery('#wps_tofw_canadapost_tracking_user_key').closest('tr').show();
			jQuery('#wps_tofw_canadapost_tracking_user_password').closest('tr').show();
			jQuery('#wps_tofw_enable_usps_tracking').closest('tr').show();
			jQuery('#wps_tofw_usps_tracking_user_key').closest('tr').show();
			jQuery('#wps_tofw_usps_tracking_user_password').closest('tr').show();
		}
		else
		{
			jQuery('#wps_fedex_userkey').closest('tr').hide();
			jQuery('#wps_fedex_userpassword').closest('tr').hide();
			jQuery('#wps_fedex_account_number').closest('tr').hide();
			jQuery('#wps_fedex_meter_number').closest('tr').hide();
			jQuery('#wps_tofw_enable_track_order_using_api').closest('tr').hide();
			jQuery('#wps_tofw_enable_canadapost_tracking').closest('tr').hide();
			jQuery('#wps_tofw_canadapost_tracking_user_key').closest('tr').hide();
			jQuery('#wps_tofw_canadapost_tracking_user_password').closest('tr').hide();
			jQuery('#wps_tofw_enable_usps_tracking').closest('tr').hide();
			jQuery('#wps_tofw_usps_tracking_user_key').closest('tr').hide();
			jQuery('#wps_tofw_usps_tracking_user_password').closest('tr').hide();
		}
	} );

	if ( jQuery( '#wps_tofw_enable_third_party_tracking_api' ).is( ':checked' ) ) 
		{	
			jQuery('#wps_fedex_userkey').closest('tr').show();
			jQuery('#wps_fedex_userpassword').closest('tr').show();
			jQuery('#wps_fedex_account_number').closest('tr').show();
			jQuery('#wps_fedex_meter_number').closest('tr').show();
			jQuery('#wps_tofw_enable_track_order_using_api').closest('tr').show();
			jQuery('#wps_tofw_enable_canadapost_tracking').closest('tr').show();
			jQuery('#wps_tofw_canadapost_tracking_user_key').closest('tr').show();
			jQuery('#wps_tofw_canadapost_tracking_user_password').closest('tr').show();
			jQuery('#wps_tofw_enable_usps_tracking').closest('tr').show();
			jQuery('#wps_tofw_usps_tracking_user_key').closest('tr').show();
			jQuery('#wps_tofw_usps_tracking_user_password').closest('tr').show();
		}
		else
		{
			jQuery('#wps_fedex_userkey').closest('tr').hide();
			jQuery('#wps_fedex_userpassword').closest('tr').hide();
			jQuery('#wps_fedex_account_number').closest('tr').hide();
			jQuery('#wps_fedex_meter_number').closest('tr').hide();
			jQuery('#wps_tofw_enable_track_order_using_api').closest('tr').hide();
			jQuery('#wps_tofw_enable_canadapost_tracking').closest('tr').hide();
			jQuery('#wps_tofw_canadapost_tracking_user_key').closest('tr').hide();
			jQuery('#wps_tofw_canadapost_tracking_user_password').closest('tr').hide();
			jQuery('#wps_tofw_enable_usps_tracking').closest('tr').hide();
			jQuery('#wps_tofw_usps_tracking_user_key').closest('tr').hide();
			jQuery('#wps_tofw_usps_tracking_user_password').closest('tr').hide();
		}
  
  
  
  // Export order
  jQuery(document).on('click', '#wot_export_order', function(e){
		e.preventDefault();
		var order_status = jQuery('#wot_select_order_status').val();
		
		jQuery.ajax({
			url:ajax_url,
			type:"POST",
			datatType: 'JSON',
			data: {
				action : 'wps_wot_export_order_using_order_status',
				order_status : order_status,
				nonce : tofw_admin_param.wps_tofw_nonce,
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
	});

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
  
  
  
  // status icon

  var is_enable_status_icon = tofw_admin_param.is_enable_status_icon;
	if( 'yes' == is_enable_status_icon ) {

		// /home/cedcoss/Local Sites/order-tracker/app/public/order_complted_icon.png
		var processing = tofw_admin_param.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/processing1.png';
		var completed = tofw_admin_param.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/deliver1.png';
		var on_hold = tofw_admin_param.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/approved1.png';
		var pending = tofw_admin_param.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/order-pending.jpeg';
		var cancelled = tofw_admin_param.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/cancel1.png';
		var failed = tofw_admin_param.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/order-cancelled.png';
		var refunded = tofw_admin_param.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/revert.png';
		var dispatched = tofw_admin_param.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/dispatch.png';
		var shipped = tofw_admin_param.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/shipped.png';
		var packed = tofw_admin_param.site_url + '/wp-content/plugins/woocommerce-order-tracker/assets/images/order-packed.png';
		 jQuery('.wp-list-table .status-wc-processing .order_status ').html('<mark class="order-status status-processing" ><img src="' + processing + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status-wc-completed .order_status ').html('<mark class="order-status status-completed" ><img src="' + completed + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status-wc-on-hold .order_status ').html('<mark class="order-status status-on-hold" ><img src="' + on_hold + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status-wc-pending .order_status ').html('<mark class="order-status status-pending" ><img src="' + pending + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status-wc-cancelled .order_status ').html('<mark class="order-status status-cancelled" ><img src="' + cancelled + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status-wc-failed .order_status ').html('<mark class="order-status status-failed" ><img src="' + failed + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status-wc-refunded .order_status ').html('<mark class="order-status status-refunded" ><img src="' + refunded + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status-wc-dispatched .order_status ').html('<mark class="order-status status-dispatched" ><img src="' + dispatched + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status-wc-shipped .order_status ').html('<mark class="order-status status-shipped" ><img src="' + shipped + '" height="50" width="50"></mark> ');
		 jQuery('.wp-list-table .status-wc-packed .order_status ').html('<mark class="order-status status-packed" ><img src="' + packed + '" height="50" width="50"></mark> ');
	
		 var custom_url = tofw_admin_param.custom_order_status_url;
		 if( custom_url != '' ){
			
			 $.each( custom_url, function( key, value ) {
				jQuery('.wp-list-table .status-wc-' + key + ' .order_status ').html('<mark class="order-status status-'+ key +'" ><img src="' + value + '" height="50" width="50"></mark> ');
			  });
			
		 }
	}
  
  
});
