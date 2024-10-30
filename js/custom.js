var ajaxurl = obj.ajaxurl;
jQuery('#checkp').click(function (e) {
	e.preventDefault();
	jQuery('#msg').html('Loading...');
	var pincode = jQuery('#pincode').val();
	if (pincode != '') {
		
		var data = {
			'action': 'cpincode',
			'pincode': pincode
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function (response) {
			jQuery('#msg').html(response);
			//alert(response);
		});
	} else {
		jQuery('#msg').html('Invalid pincode');
	}
});

jQuery(document).ready(function () {


	jQuery('.mark-order-shipped').before('<a class="fullfilment-shipped">Add fulfillment</a>');
	jQuery('.row-actions-order').find("a:eq(1)").addClass('shiplabel');
	jQuery('.row-actions-order').find("a:eq(1)").attr('href', '#');
	//jQuery('.row-actions-order').find("a:eq(3)").attr('href','https://www.ithinklogistics.com/track-order-status.php'); 
	jQuery('.row-actions-order').find("a:eq(3)").attr('id', '');
	//jQuery('.row-actions-order').find("a:eq(3)").attr("target","_blank"); 
	jQuery('.row-actions-order').find("a:eq(3)").html("Track order");
	jQuery('.row-actions-order').find("a:eq(3)").addClass("track-order");


	//jQuery('.mark-order-shipped').before('<div class="msg"></div>');
});
jQuery('body').on('click', 'a.fullfilment-shipped', function () {
	var number = jQuery(this).next("a").attr('href').split('=').pop();
	jQuery(this).html('Adding...');
	var elem = jQuery(this);
	var data = {
		'action': 'fulfillment',
		'order': number
	};

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajaxurl, data, function (response) {
		//jQuery(this).next("div").html(response);
		if (response == 1) {
			elem.html('Added');
			swal('Great', "Your order has been fulfilled. Please have your packed consignment ready for pick-up along with Printed Shipping Label for this Order.", 'success');
		} else {
			swal("Sorry", response, "error");

			//alert(response);
		}
		//alert(elem);
	});

	//var number = $(this).attr('id').split('_').pop();

});
jQuery('body').on('click', 'a.track-order', function (e) {
	e.preventDefault();
	var number = jQuery(this).prev("a").attr('id').split('-').pop();
	jQuery(this).html('Please wait...');
	var elem = jQuery(this);
	var data = {
		'action': 'tracking_order',
		'order': number
	};

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajaxurl, data, function (response) {
		//jQuery(this).next("div").html(response);
		if (response != 0) {
			window.open(response, '_blank');
		} else {
			swal("Sorry", 'Waybill not found, Please try again later', "error");

			//alert(response);
		}
		//alert(elem);
	});

	//var number = $(this).attr('id').split('_').pop();

});

jQuery('body').on('click', 'a.shiplabel', function (e) {
	e.preventDefault();
	var number = jQuery(this).prev("a").attr('id').split('-').pop();

	jQuery(this).html('Generating...');
	var elem = jQuery(this);
	var data = {
		'action': 'genrate_shiplabel',
		'order': number
	};

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajaxurl, data, function (response) {
		//jQuery(this).next("div").html(response);
		if (response == 0) {
			swal("Sorry", 'Waybill not found, Please try again later', "error");

		} else {
			elem.html('Generated');
			window.open(response, '_blank');

		}
		//alert(elem);
	});

	//var number = $(this).attr('id').split('_').pop();

});