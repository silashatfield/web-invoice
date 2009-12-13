var _web_invoice_method_count = 0;

jQuery(document).ready( function() {
	if (_web_invoice_method_count > 1) {
		jQuery(".payment_form").hide();
	}
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
