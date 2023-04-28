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
// License.
jQuery(document).ready(function($) {
  $("#wps_tofw_license_key").on("click", function(e) {
    $("#wps_tofw_license_activation_status").html("");
  });

  $("form#wps_tofw_license_form").on("submit", function(e) {
    e.preventDefault();

    //   $("#wps_license_ajax_loader").show();
    var license_key = $("#wps_tofw_license_key").val();
    wps_tofw_send_license_request(license_key);
  });

  function wps_tofw_send_license_request(license_key) {
    $.ajax({
      type: "POST",
      dataType: "JSON",
      url: tofw_admin_param.ajaxurl,
      data: {
        action: "wps_tofw_validate_license_key",
        purchase_code: license_key,
        nonce: tofw_admin_param.wps_standard_nonce,
      },

      success: function(data) {
        //   $("#wps_upsell_license_ajax_loader").hide();

        if (data.status == true) {
          $("#wps_tofw_license_activation_status").css("color", "#42b72a");

          jQuery("#wps_tofw_license_activation_status").html(data.msg);

          location = tofw_admin_param.tofw_admin_param_location;
        } else {
          $("#wps_tofw_license_activation_status").css("color", "#ff3333");

          jQuery("#wps_tofw_license_activation_status").html(data.msg);

          jQuery("#wps_tofw_license_key").val("");
        }
      },
    });
  }
});
