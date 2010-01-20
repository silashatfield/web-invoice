var _web_invoice_method_count = 0;

jQuery(document).ready( function() {
	if (_web_invoice_method_count > 1) {
		jQuery(".payment_form").hide();
	}
	jQuery(".noautocomplete").attr("autocomplete", "off");
	jQuery("#payment_methods a").click( function() {
		jQuery(".payment_form").hide();
		jQuery(jQuery(this).attr("href")).show();
	});
});

function payflow_copy_billing(suffix) {
	_payflow_billing_fields = ['NAME', 'EMAIL', 'PHONE', 'ADDRESS', 'CITY', 'STATE', 'ZIP', 'COUNTRY'];
	
	for (_i=0; _i<_payflow_billing_fields.length; _i++) {
		jQuery('form#payflow_form #'+_payflow_billing_fields[_i]+suffix).val(jQuery('form#payflow_form #'+_payflow_billing_fields[_i]).val());
	}
}

function pfp_copy_billing(prefix) {
	_pfp_billing_fields = ['first_name', 'last_name', 'phonenumber', 'email_address', 'address', 'city', 'state', 'zip', 'country'];
	
	for (_i=0; _i<_pfp_billing_fields.length; _i++) {
		jQuery('form#pfp_checkout_form #'+prefix+'_'+_pfp_billing_fields[_i]).val(jQuery('form#pfp_checkout_form #'+_pfp_billing_fields[_i]).val());
	}
}

function sagepay_copy_billing(prefix) {
	_sagepay_billing_fields = ['first_name', 'last_name', 'phonenumber', 'email_address', 'address', 'city', 'state', 'zip', 'country'];
	
	for (_i=0; _i<_sagepay_billing_fields.length; _i++) {
		jQuery('form#sagepay_checkout_form #'+prefix+'_'+_sagepay_billing_fields[_i]).val(jQuery('form#sagepay_checkout_form #'+_sagepay_billing_fields[_i]).val());
	}
}
