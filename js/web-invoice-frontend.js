jQuery(document).ready( function() {
	jQuery(".payment_form").hide();
	jQuery("#payment_methods a").click( function() {
		if (_web_invoice_method_count > 1) {
			jQuery(".payment_form").hide();
		}
		jQuery(jQuery(this).attr("href")).show();
	});
});
