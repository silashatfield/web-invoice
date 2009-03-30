jQuery(document).ready( function() {
	jQuery(".payment_form").hide();
	jQuery("#payment_methods a").click( function() {
		jQuery(".payment_form").hide();
		jQuery(jQuery(this).attr("href")).show();
	});
});
