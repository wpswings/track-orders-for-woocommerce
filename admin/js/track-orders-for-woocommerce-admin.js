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

jQuery(document).ready(function($) {
  jQuery(document).on('click','input#mwb_mwb_create_role_box',function(){
    jQuery(this).toggleClass('role_box_open');
    jQuery("div#mwb_mwb_create_box").slideToggle();
    if(jQuery(this).hasClass('role_box_open')) {
      jQuery(this).val(global_tyo_admin.mwb_tyo_close_button);
    }
    else {
      jQuery(this).val('Create Custom Order Status');
    }
  });
  
  
  jQuery(document).on('click','input#mwb_mwb_create_custom_order_status',function(){
    jQuery('#mwb_mwb_send_loading').show();
    var mwb_mwb_create_order_status = jQuery('#mwb_mwb_create_order_name').val().trim();
    var mwb_order_image_url = jQuery(document).find('#mwb_tyo_other_setting_upload_logo').val();
    if(mwb_mwb_create_order_status != "" && mwb_mwb_create_order_status != null) 
    {
      if( /^[a-zA-Z0-9- ]*$/.test(mwb_mwb_create_order_status) )
      {
        mwb_mwb_create_order_status = mwb_mwb_create_order_status
  
        jQuery.ajax({
          url : global_tyo_admin.ajaxurl,
          type : 'post',
          data : {
            action : 'mwb_mwb_create_custom_order_status',
            mwb_mwb_new_role_name : mwb_mwb_create_order_status,
            mwb_custom_order_image_url : mwb_order_image_url,
            nonce : global_tyo_admin.mwb_tyo_nonce,
          },
          success : function( response ) {
            jQuery('#mwb_mwb_send_loading').hide();
  
            if(response == "success") {
              jQuery('#mwb_tyo_other_setting_upload_logo').val('');
              jQuery('input#mwb_mwb_create_role_box').trigger('click');
              jQuery("div.mwb_notices_order_tracker").html('<div id="message" class="notice notice-success"><p><strong>'+global_tyo_admin.message_success+'</strong></p></div>');
              jQuery('#mwb_mwb_create_order_name').val('');
              location.reload();
            }
            else {
              jQuery("div.mwb_notices_order_tracker").html('<div id="message" class="notice notice-error"><p><strong>'+global_tyo_admin.message_error_save+'</strong></p></div>').delay(2000).fadeOut(function(){});
            }	
          }
        });
      }
      else{
        jQuery('#mwb_mwb_send_loading').hide();
        jQuery("div.mwb_notices_order_tracker").html( '<div id="message" class="notice notice-error"><p><strong>'+global_tyo_admin.message_invalid_input+'</strong></p></div>' ).delay(4000).fadeOut(function(){});
        return;
      }	
    }else{
      jQuery('#mwb_mwb_send_loading').hide();
      jQuery("div.mwb_notices_order_tracker").html( '<div id="message" class="notice notice-error"><p><strong>'+global_tyo_admin.message_empty_data+'</strong></p></div>' ).delay(4000).fadeOut(function(){});
      return;
    }
    jQuery('#mwb_mwb_send_loading').hide();
  
  });
  
});
