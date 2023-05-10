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

  jQuery('body').on('click','.wps_enhanced_tyo_remove',function(e){
    e.preventDefault();
    var wps_enhanced_tyo_remove=jQuery(this).data("id");
    jQuery("#wps_enhanced_tyo_class"+wps_enhanced_tyo_remove).remove();
      jQuery.ajax({
      url:ajax_url,
      type:"POST",
      data: {
        action : 'wps_provider_remove_company_data_from_plugin',
        wps_company_name:wps_enhanced_tyo_remove,
        nonce : global_tyo_admin.wps_tofw_nonce,
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
          nonce : global_tyo_admin.wps_tofw_nonce,
          wps_company_name : wps_company_name,
          wps_company_url : wps_company_url
        },success:function(response){
          jQuery('.wps_toy_enhanced_provider').val("");
          jQuery('.wps_toy_enhanced_provider_url').val("");
          var  wps_append="<div class='mwb-tyo-courier-data' id='wps_enhanced_tyo_class"+wps_company_name+"'>";
            wps_append+="<input type='checkbox' id='wps_enhanced_checkbox"+wps_company_name+"' name='wps_tofw_courier_url["+wps_company_name+"]' value='"+wps_company_url+"'>";
                wps_append+="<label for='wps_enhanced_checkbox"+wps_company_name+"'>"+wps_company_name+"</label>";
                  wps_append+='<a href="#" id="wps_enhanced_cross'+wps_company_name+'" class="wps_enhanced_tyo_remove" data-id="'+wps_company_name+'">X</a></div>';
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
              jQuery("div.wps_notices_order_tracker").html('<div id="message" class="notice notice-success"><p><strong>'+global_tyo_admin.message_success+'</strong></p></div>');
              jQuery('#wps_tofw_create_order_name').val('');
              location.reload();
            }
            else {
              jQuery("div.wps_notices_order_tracker").html('<div id="message" class="notice notice-error"><p><strong>'+global_tyo_admin.message_error_save+'</strong></p></div>').delay(2000).fadeOut(function(){});
            }	
          }
        });
      }
      else{
        jQuery('#wps_tofw_send_loading').hide();
        jQuery("div.wps_notices_order_tracker").html( '<div id="message" class="notice notice-error"><p><strong>'+global_tyo_admin.message_invalid_input+'</strong></p></div>' ).delay(4000).fadeOut(function(){});
        return;
      }	
    }else{
      jQuery('#wps_tofw_send_loading').hide();
      jQuery("div.wps_notices_order_tracker").html( '<div id="message" class="notice notice-error"><p><strong>'+global_tyo_admin.message_empty_data+'</strong></p></div>' ).delay(4000).fadeOut(function(){});
      return;
    }
    jQuery('#wps_tofw_send_loading').hide();
  
  });




  // delete custom status
  jQuery(document.body).on('click','.wps_delete_costom_order',function(){
		var wps_action=jQuery(this).data('action');
		var wps_key=jQuery(this).data('key');
		jQuery.ajax({
			url: global_tyo_admin.ajaxurl,
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
  
});
