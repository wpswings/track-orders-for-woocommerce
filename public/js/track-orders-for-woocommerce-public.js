(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
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


jQuery(window).bind("load", function() {
	setTimeout(function(){
		jQuery('#wps_placed_order > svg').addClass('show_img_slow');
	},600)
	setTimeout(function(){
		jQuery('#wps_approval_order > svg').addClass('show_img_slow');
	},1000)
	setTimeout(function(){
		jQuery('#wps_processing_order > svg').addClass('show_img_slow');
	},1400)
	setTimeout(function(){
		jQuery('#wps_shipping_order > svg').addClass('show_img_slow');
	},1800)
	setTimeout(function(){
		jQuery('#wps_delivered_order > svg').addClass('show_img_slow');
	},2200)
	setTimeout(function(){
		jQuery('#wps_cancelled_order > svg').addClass('show_img_slow');
	},2600)
});
jQuery( document ).ready( function()
{

	jQuery("body").on('click','#wps_live_tracking',function(e){
         e.preventDefault(); 
         var number=jQuery(this).text();
         var wps_tofw_url = jQuery(this).attr('href'); 
         window.open(wps_tofw_url+number, '_blank');
	});
	jQuery( document ).on( 'mouseover', '.wps-circle', function(){

		var status = jQuery(this).attr( 'data-status' );
		if (status != '' ) 
		{
			var status_msg = '<h4>'+status+'</h4>';
			jQuery( '.wps-tofw-wps-delivery-msg' ).html( status_msg );
			jQuery( '.wps-tofw-wps-delivery-msg' ).show();
		}
	} );
	jQuery( document ).on( 'mouseout', '.wps-circle', function(){

		jQuery( '.wps-tofw-wps-delivery-msg' ).hide();

	} );
	jQuery('a.thickbox').each( function( key, value ){
		var link = jQuery( this ).attr( 'href' );
		var order_id = link.substring( link.lastIndexOf( '/' )+1, link.indexOf( '?' ) );
		
	} );

	jQuery('.woocommerce-orders-table__cell').on('click','a.thickbox',function(){
		var wps_tofw_iframe_obj = jQuery(document).find('#TB_window');
	});
	
	jQuery('#site-navigation-menu-toggle').hide();
	var completed_condition_data = jQuery('.wps_completed_condition > img').attr('data-completed_data');
	var cancelled_condition_data = jQuery('.wps_cancelled_condition > img').attr('data-cancelled_data');
	if(completed_condition_data == 1)
	{
		jQuery('.wps_tofw_header-wrapper').addClass('wps_tofw_completed_condition');
	}
	if(cancelled_condition_data == 1)
	{
		jQuery('.wps_tofw_header-wrapper').addClass('wps_tofw_completed_condition');
	}
	jQuery('#wps_order_detail_section').addClass('wps_active_tab');
	jQuery('#wps_progress_bar').hide();
	jQuery('#wps_approval').hide();
	jQuery('div#wps_notice').hide();
	jQuery('#wps_process').hide();
	jQuery('#wps_ship').hide();
	jQuery('#wps_delivery').hide();
	jQuery('#wps_tofw_enhanced_customer_notice_template2').hide();
	jQuery('#wps_order_track_section').on('click',function(){
		jQuery('#wps_order_detail_section').removeClass();
		jQuery('#wps_order_track_customer_notice').removeClass();
		jQuery('#wps_order_track_section').addClass('wps_active_tab');

		if ('template8' == tofw_public_param.wps_activated_template) {
			jQuery('#wps_progress_bar').show(700);
			jQuery('#wps_approval').show(1000);
			jQuery('div#wps_notice').show(1000);
			jQuery('#wps_process').show(1000);
			jQuery('#wps_ship').show(1000);
			jQuery('#wps_delivery').show(1000);
		} else {

			jQuery('#wps_progress_bar').show(1700);
			jQuery('#wps_approval').show(2000);
			jQuery('div#wps_notice').show(2000);
			jQuery('#wps_process').show(2000);
			jQuery('#wps_ship').show(2000);
			jQuery('#wps_delivery').show(2000);

		}
		
		var i = 1;
		jQuery('.wps_progress .circle_tofw_wps').removeClass().addClass('circle_tofw_wps');
		jQuery('.wps_progress .bar_tofw_wps').removeClass().addClass('bar_tofw_wps');
		setInterval(function() {

			var z = jQuery('.hidden_value').val();
			if(i <= jQuery('.hidden_value').val())
			{
				jQuery('.wps_progress .circle_tofw_wps:nth-of-type(' + i + ')').addClass('active_tofw_wps');

				jQuery('.wps_progress .circle_tofw_wps:nth-of-type(' + (i-1) + ')').removeClass('active_tofw_wps').addClass('wps_done');

				jQuery('.wps_progress .circle_tofw_wps:nth-of-type(' + (i-1) + ') .label').html('&#10003;');

				jQuery('.wps_progress .bar_tofw_wps:nth-of-type(' + (i-1) + ')').addClass('active_tofw_wps');

				jQuery('.wps_progress .bar_tofw_wps:nth-of-type(' + (i-2) + ')').removeClass('active_tofw_wps').addClass('wps_done');

				i++;

				if (i==0) {
					jQuery('.wps_progress .bar_tofw_wps').removeClass().addClass('bar_tofw_wps');
					jQuery('.wps_progress div.circle_tofw_wps').removeClass().addClass('circle_tofw_wps');
					i = 1;
				}
			}
		}, 1200);
		jQuery('#wps_product').hide();
		jQuery('#wps_order').hide();
		jQuery('#wps_tofw_enhanced_customer_notice_template2').hide();
	});
	jQuery('#wps_order_detail_section').on('click',function(){
		jQuery('#wps_order_detail_section').addClass('wps_active_tab');
		jQuery('#wps_order_track_customer_notice').removeClass();
		jQuery('#wps_order_track_section').removeClass();


		if ('template8' == tofw_public_param.wps_activated_template) {
			jQuery('#wps_product').show(700);
			jQuery('#wps_order').show(700);
		} else {

			jQuery('#wps_product').show(1700);
			jQuery('#wps_order').show(1700);

		}


		jQuery('#wps_progress_bar').hide();
		jQuery('div#wps_notice').hide();
		jQuery('#wps_approval').hide();
		jQuery('#wps_approval').hide();
		jQuery('#wps_process').hide();
		jQuery('#wps_ship').hide();
		jQuery('#wps_delivery').hide();
		jQuery('#wps_tofw_enhanced_customer_notice_template2').hide();
	});
	jQuery('#wps_order_track_customer_notice').on('click',function(){
		jQuery('#wps_order_track_customer_notice').addClass('wps_active_tab');
		jQuery('#wps_order_detail_section').removeClass();
		jQuery('#wps_order_track_section').removeClass();
	
		if ('template8' == tofw_public_param.wps_activated_template) {
			jQuery('#wps_tofw_enhanced_customer_notice_template2').show(700);
		} else {
			jQuery('#wps_tofw_enhanced_customer_notice_template2').show(1700);
		}

		jQuery('#wps_progress_bar').hide();
		jQuery('#wps_approval').hide();
		jQuery('#wps_process').hide();
		jQuery('#wps_ship').hide();
		jQuery('#wps_delivery').hide();
		jQuery('#wps_product').hide();
		jQuery('#wps_order').hide();
	});
	
	jQuery('.wps_product-details-section ').hide();
	jQuery('.wps_product-details-section wps-table-responsive').hide();
	jQuery('#wps_shop_location').hide();

	jQuery('#get_current_shop_location').click(function(){
		
		if ('template8' == tofw_public_param.wps_activated_template) {
			jQuery('#wps_shop_location').show(700);
		} else {
			jQuery('#wps_shop_location').show(1700);
		}
		
		jQuery('.wps_header-wrapper').hide();
		jQuery('.wps_order_tracker_content').hide();
		jQuery('.wps_product-details-section ').hide();
		jQuery('#wps_tofw_enhanced_customer_notice_template4').hide();
		jQuery('.wps_product-details-section wps-table-responsive').hide();
		jQuery('#wps_tofw_track_order_status').removeClass();
		jQuery('#wps_tofw_track_order_details').removeClass();
		jQuery('#get_current_shop_customer_notice').removeClass();
		jQuery('#get_current_shop_location').addClass('wps_tofw_active');
	});
	jQuery('#wps_tofw_track_order_details').click(function () {
		
		if ('template8' == tofw_public_param.wps_activated_template) {
			jQuery('.wps_product-details-section ').show(700);
		} else {
			jQuery('.wps_product-details-section ').show(1700);
		}

		jQuery('#wps_shop_location').hide();
		jQuery('.wps_header-wrapper').hide();
		jQuery('#wps_tofw_enhanced_customer_notice_template4').hide();
		jQuery('.wps_order_tracker_content').hide();
		jQuery('#wps_tofw_track_order_status').removeClass();
		jQuery('#get_current_shop_location').removeClass();
		jQuery('#get_current_shop_customer_notice').removeClass();
		jQuery('#wps_tofw_track_order_details').addClass('wps_tofw_active');
	});
	jQuery('#wps_tofw_track_order_status').click(function () {

		if ('template8' == tofw_public_param.wps_activated_template) {
			jQuery('.wps_header-wrapper').show(700);
			jQuery('.wps_order_tracker_content').show(700);
		} else {
			jQuery('.wps_header-wrapper').show(1700);
			jQuery('.wps_order_tracker_content').show(1700);
		}
		
		jQuery('.wps_product-details-section ').hide();
		jQuery('#wps_tofw_enhanced_customer_notice_template4').hide();
		jQuery('#wps_shop_location').hide();
		jQuery('.wps_product-details-section').hide();
		jQuery('#wps_tofw_track_order_details').removeClass();
		jQuery('#get_current_shop_location').removeClass();
		jQuery('#get_current_shop_customer_notice').removeClass();
		jQuery('#wps_tofw_track_order_status').addClass('wps_tofw_active');
	});
	jQuery('#get_current_shop_customer_notice').click(function () {
		
		
		if ('template8' == tofw_public_param.wps_activated_template) {
			jQuery('#wps_tofw_enhanced_customer_notice_template4').show(700);
		} else {
			jQuery('#wps_tofw_enhanced_customer_notice_template4').show(1700);
		}

		jQuery('.wps_product-details-section').hide();
		jQuery('#wps_shop_location').hide();
		jQuery('.wps_header-wrapper').hide();
		jQuery('.wps_order_tracker_content').hide();
		jQuery('#wps_tofw_track_order_details').removeClass();
		jQuery('#get_current_shop_location').removeClass();
		jQuery('#wps_tofw_track_order_status').removeClass();
		jQuery('#get_current_shop_customer_notice').addClass('wps_tofw_active');
	});
});

jQuery(document).on('click','.wps-tofw-order-tracking-section .wps_tofw_order_tab ul li a', function(){
		jQuery('.wps-ot_order-track-wrap').parent('.wps_tofw_order_tracker_content-main-alpha-in').css('opacity','0');
	setTimeout(function(){
		jQuery('.wps-ot_order-track-wrap').parent('.wps_tofw_order_tracker_content-main-alpha-in').css('opacity','1');
	},1000);
});

jQuery('.wps-tooltip-template-fedex').hide();
jQuery('.wps-tooltip-canadapost').hide();
jQuery('.wps-tooltip-usps').hide();


jQuery(document).ready(function(){
	
	if ('template8' == tofw_public_param.wps_activated_template) {
		$wps_time = 11000;
	} else {
		$wps_time = 12000
	}
	var div_height_template2 = jQuery('.wps-tooltip-template-fedex').height()+60;
	var progress_width_template2 = jQuery('.wps-tofw-outer-template-fedex').attr('data-progress');
	var progress_width_template2_final = (progress_width_template2*div_height_template2)+(140);
	jQuery('.wps-tofw-outer-template-fedex').css('height',progress_width_template2_final+'px','important');
	jQuery('.wps-tofw-inner-template-fedex').animate(
	{
		height : progress_width_template2_final+'px'
		}, $wps_time);
	

	jQuery('.wps-tooltip-template-fedex').each(function(i,v){
		jQuery(this).delay(1500*i).fadeIn(800);
	});

	var div_height_canadapost = jQuery('.wps-tooltip-canadapost').height()+60;
	var div_height_ok_canadapost = jQuery('.wps-tooltip-canadapost').height();
	var progress_width_template1_canadapost = jQuery('.wps-tofw-outer-template-canadapost').attr('data-progress');
	var progressBarHeight_canadapost = jQuery('.wps-tofw-outer-template-canadapost').attr('data-progress-bar-height');
	var progress_bar_final_canadapost = (progressBarHeight_canadapost*div_height_canadapost)+(div_height_canadapost+170);

	jQuery('.wps-tofw-outer-template-canadapost').css('height',progress_bar_final_canadapost+'px','important');

	jQuery('.wps-tofw-inner-template-canadapost').animate(
	{
		height : progress_bar_final_canadapost+'px'
	},14000);	

	jQuery('.wps-tooltip-canadapost').each(function(ind,val){
		jQuery(this).delay(1100*ind).fadeIn(800);
	});


	var div_height_usps = jQuery('.wps-tooltip-usps').height()+60;
	var div_height_ok_usps = jQuery('.wps-tooltip-usps').height();
	var progress_width_template1_usps = jQuery('.wps-tofw-outer-template-usps').attr('data-progress');
	var progressBarHeight_usps = jQuery('.wps-tofw-outer-template-usps').attr('data-progress-bar-height');
	var progress_bar_final_usps = (progressBarHeight_usps*div_height_usps)+(div_height_usps+200);

	jQuery('.wps-tofw-outer-template-usps').css('height',progress_bar_final_usps+'px','important');

	jQuery('.wps-tofw-inner-template-usps').animate(
	{
		height : progress_bar_final_usps+'px'
	},14000);	

	jQuery('.wps-tooltip-usps').each(function(inte,va){
		jQuery(this).delay(1100*inte).fadeIn(800);
	});
	
});


if (window.history.replaceState) {
	window.history.replaceState(null, null, window.location.href);
}

jQuery(document).ready(function ($) {
	$('#wps-tofw_th-search-btn').on('click', function () {
		jQuery("#wps-tofw_t-main").hide();
		const wps_tracking_number = $('#wps-tofw_th-search').val();
		const wps_tracking_method = $('#wps-tofw_th-method').val();

		if (wps_tracking_number && wps_tracking_method) {
				
			$('.wps-tofw_loader').show();

			$.ajax({
				url: tofw_public_param.ajaxurl,
				type: 'POST',
				dataType: 'json',

				data: {
					action: 'wps_mult_carrier_data_tracking',
					// _ajax_nonce: tofwp_public_param.nonce,
					tracking_number: wps_tracking_number,
					courier_code: wps_tracking_method
				},
				beforeSend: function () {
					// optional: disable button / show loader
					$('#wps-tofw_th-search-btn').prop('disabled', true).text('Searching…');
				},
				success: function (response) {
					console.log('SUCCESS:', response);
					if (!response || !response.success) {
						console.error('Server returned an error payload:', response?.data || response);
					} else {
						$('.wps-tofw_loader').hide();
						jQuery("#wps-tofw_t-main").show();
						// do something with response.data
						console.log(response.data);

						let checkpoints = response.data[0];

						if (checkpoints && checkpoints.length) {

							// Step 4: Example: build HTML output
							let html = '<ul>';
							checkpoints.forEach(function (item) {
								// Remove leading $ if present
								wps_tofw_ts = item.checkpoint_date.replace(/^\$/, "");

								// Split into date and time parts
								let [wps_tofw_datePart, wps_tofw_timePart] = wps_tofw_ts.split("T");

								// Remove timezone from timePart (after "-")
								wps_tofw_timePart = wps_tofw_timePart.split("-")[0];

								// Format date (YYYY-MM-DD → DD-MM-YYYY)
								let [wps_tofw_year, wps_tofw_month, wps_tofw_day] = wps_tofw_datePart.split("-");
								let wps_tofw_formattedDate = `${wps_tofw_day}-${wps_tofw_month}-${wps_tofw_year}`;

								// Time stays HH:mm:ss
								let wps_tofw_formattedTime = wps_tofw_timePart;
				
								html += `<li class="active">
				<img src="${tofw_public_param.mutlple_carrer_image.wps_in_way}" alt="in way" />
				<div class="wps-tofw_tm-con">
				<div class="h3">${item.tracking_detail}</div>
				<div class="p">Date : ${wps_tofw_formattedDate}</div>
				<div class="p">Time : ${wps_tofw_formattedTime}</div>
				<div class="p">${item.location}</div>
					</div>
				</li>`;
							});
							html += '</ul>';
							html += `<div class="wps-tofw_t-status delivered" id="wps-tofw_t-status">Current Status : ${(response.data.delivery_status).toUpperCase()}</div>`;
							//delivery_status
							// console.log(response.data.delivery_status);

							jQuery("#wps-tofw_t-main").html(html);
						} else {
							jQuery("#wps-tofw_t-main").html('<p class = "wps_tofw_error">No tracking information found.</p>');
						}
						// jQuery("#wps-tofw_t-status").html(html);

						// console.log(tofwp_public_param_public.mutlple_carrer_image.wps_in_way);
					}
				},
				error: function (xhr, status, error) {
					console.error('AJAX Error:', status, error, xhr?.responseText);
				},
				complete: function () {
					$('#wps-tofw_th-search-btn').prop('disabled', false).text('Search');
				}
			});
		} else {
			alert('Please enter a tracking number and select a courier.');
		}
  });
});


jQuery(window).load(function () {
	
	document.body.classList.add("wps-tofw-tracking-modal");

	jQuery(document).on('click','.wps_tofw_17track',function(e){
		e.preventDefault();
		var num = jQuery(this).text();
		YQV5.trackSingleF2({
			YQ_ElementId:"YQElem1",
			YQ_Width:470,
			YQ_Height:560,
			YQ_Fc:"0",
			YQ_Lang:"en",
			YQ_Num:""+num+""
		});
	});
	jQuery(document).on('click','.wps_tofw_enhanced_17track',function(){
		
		var num = jQuery(document).find('#wps_tofw_enhanced_trackingid').val();
			YQV5.trackSingleF2({
				YQ_ElementId:"YQElem2",
				YQ_Width:470,
				YQ_Height:560,
				YQ_Fc:"0",
				YQ_Lang:"en",
				YQ_Num:""+num+""
			});
	 
	});
});

