function web_invoice_add_time(add_days) {

	if(add_days == 'clear') {

	jQuery("#new_web_invoice_form #mm").val('');
	jQuery("#new_web_invoice_form #jj").val('');
	jQuery("#new_web_invoice_form #aa").val('');
	}
	 else
	 {

	myDate = new Date();
	var week_from_now = new Date(myDate.getTime() + add_days*24*60*60*1000);;
	month = week_from_now.getMonth() + 1;

	jQuery("#new_web_invoice_form #mm").val(month);
	jQuery("#new_web_invoice_form #jj").val(week_from_now.getDate());
	jQuery("#new_web_invoice_form #aa").val(week_from_now.getFullYear());
		}


	return false;

}

function web_invoice_cancel_recurring() {
	jQuery("#web_invoice_subscription_name").val('');
	jQuery("#web_invoice_subscription_unit").val('');
	jQuery("#web_invoice_subscription_length").val('');
	jQuery("#web_invoice_subscription_start_month").val('');
	jQuery("#web_invoice_subscription_start_day").val('');
	jQuery("#web_invoice_subscription_start_year").val('');
	jQuery("#web_invoice_subscription_total_occurances").val('');

	//jQuery(".web_invoice_enable_recurring_billing").toggle();
	jQuery("#web_invoice_enable_recurring_billing").toggle();
	jQuery(".web_invoice_enable_recurring_billing").toggle();



}


function web_invoice_subscription_start_time(add_days) {

	function formatNum(num){
	var mynum = num * 1;
	var retVal = mynum<10?'0':'';
	return (retVal + mynum)
	}

	if(add_days == 'clear') {

	jQuery("#web_invoice_subscription_start_month").val('');
	jQuery("#web_invoice_subscription_start_day").val('');
	jQuery("#web_invoice_subscription_start_year").val('');
	}
	 else
	 {

	myDate = new Date();
	var week_from_now = new Date(myDate.getTime() + add_days*24*60*60*1000);;
	month = week_from_now.getMonth() + 1;

	jQuery("#web_invoice_subscription_start_month").val(formatNum(month));
	jQuery("#web_invoice_subscription_start_day").val(week_from_now.getDate());
	jQuery("#web_invoice_subscription_start_year").val(week_from_now.getFullYear());
		}


	return false;

}


jQuery(document).ready(function(){


	tooltip();

	jQuery("#submit_bulk_action").click( function(){
	if(jQuery("#web_invoice_action :selected").text() == 'Delete') {

	var r=confirm("Are you sure you want to delete the selected invoice(s)?");
	if (r==true)
	  {
	  return true;
	  }
	else
	  {
	  return false;
	  }
}

	});

	jQuery(".web_invoice_make_editable").click( function() {
	var element_name = jQuery(this).attr('id');
	var width = jQuery(this).width() * 2;
	var original_content = jQuery(this).html();
	var draw_input_field = "<input style='width: " + width +"px;' value='" + jQuery(this).html() +"' name='" + element_name +"' class='" + element_name +"'/>";

	if(!jQuery("input." + element_name).length > 0) { jQuery("#" + element_name).html(draw_input_field);  jQuery("input." + element_name).focus(); }

	jQuery("input." + element_name).blur(function() {
		if(jQuery("input." + element_name).val() == original_content || jQuery("input." + element_name).val() == '') jQuery("#" + element_name).html(original_content);

	}  );
	})

	jQuery("#invoices-filter").submit(function() {  if(jQuery("#invoices-filter select").val() == '-1') { return false;} })

	jQuery("#web_invoice_tax").keyup(function() { recalc(); })
	jQuery("#invoice_list").delegate("keyup", "input", function(event) {recalc();});
	jQuery("#web_invoice_subscription_total_occurances ").delegate("keyup", "input", function(event) {recalc();});

	jQuery("a.web_invoice_custom_invoice_id").click(function() { jQuery("input.web_invoice_custom_invoice_id").toggle(); return false;})

	jQuery("#web_invoice_show_archived").click(function() { jQuery(".web_invoice_archived").toggle(); return false;})
	jQuery("#web_invoice_enable_recurring_billing").click(function() { jQuery(".web_invoice_enable_recurring_billing").toggle(); jQuery("#web_invoice_enable_recurring_billing").toggle();  })
	jQuery("#web_invoice_need_mm").click(function() { jQuery(".web_invoice_credit_card_processors").toggle();  })
	jQuery("#web_invoice_copy_invoice").click(function() { jQuery(".web_invoice_copy_invoice").toggle();jQuery("#web_invoice_create_new_web_invoice").toggle();jQuery("#web_invoice_copy_invoice").toggle();  })
	jQuery("#web_invoice_copy_invoice_cancel").click(function() { jQuery(".web_invoice_copy_invoice").toggle();jQuery("#web_invoice_create_new_web_invoice").toggle();jQuery("#web_invoice_copy_invoice").toggle();  })


	jQuery("#web_invoice_merchantexpress_prefill").click(function() { jQuery("#web_invoice_gateway_url").val('https://gateway.merchantexpress.com');  })
	jQuery("#web_invoice_merchantwarehouse_prefill").click(function() { jQuery("#web_invoice_gateway_url").val('https://gateway.merchantwarehouse.com');  })

	if(jQuery('#web_invoice_payment_method').val() == 'cc') { jQuery('.gateway_info').show();  }
	if(jQuery('#web_invoice_payment_method').val() == 'paypal') {  jQuery('.paypal_info').show();  }
	if(jQuery('#web_invoice_payment_method').val() == 'moneybookers') {
		jQuery('.moneybookers_info').show();

		if(jQuery('#web_invoice_moneybookers_merchant').val() == 'False') {  jQuery('.moneybookers_info_merchant').hide();  }
	}

	jQuery('#web_invoice_payment_method').change(function(){
		if(jQuery(this).val() == 'paypal') {  jQuery('.paypal_info').show(); jQuery('.moneybookers_info').hide(); jQuery('.gateway_info').hide();  }
		if(jQuery(this).val() == 'moneybookers') {
			jQuery('.moneybookers_info').show(); jQuery('.paypal_info').hide(); jQuery('.gateway_info').hide();
			if(jQuery('#web_invoice_moneybookers_merchant').val() == 'False') {  jQuery('.moneybookers_info_merchant').hide();  }
		}
		if(jQuery(this).val() == 'cc') {  jQuery('.gateway_info').show(); jQuery('.moneybookers_info').hide(); jQuery('.paypal_info').hide(); }
	});

	jQuery('#web_invoice_moneybookers_merchant').change(function(){
		if(jQuery(this).val() == 'True') {
			jQuery('.moneybookers_info_merchant').show();
		}
		if(jQuery(this).val() == 'False') {
			jQuery('.moneybookers_info_merchant').hide();
		}
	});

	if(jQuery('#first_name').val() == '') {jQuery('#first_name').addClass("error"); }
	if(jQuery('#last_name').val() == '') {jQuery('#last_name').addClass("error"); }
	if(jQuery('#streetaddress').val() == '') {jQuery('#streetaddress').addClass("error"); }
	if(jQuery('#state').val() == '') {jQuery('#state').addClass("error"); }
	if(jQuery('#city').val() == '') {jQuery('#city').addClass("error"); }
	if(jQuery('#zip').val() == '') {jQuery('#zip').addClass("error"); }

	jQuery('#delete_all_web_invoice_databases').click(function() {
		var txt = 'Are you sure you want to delete all the databases?  All your invoice and log data will be lost forever. ';
		jQuery.prompt(txt,{	buttons:{Delete:true, Cancel:false}, callback: function(v,m){ if(v){  document.location = "admin.php?page=new_web_invoice&web_invoice_action=complete_removal"; }	}
	});
	return false
	});

	var tog = false; // or true if they are checked on load
	 jQuery('#invoice_sorter_table #CheckAll').click(function() {
	    jQuery("input[type=checkbox]").attr("checked",!tog);
	  tog = !tog;
	 });

	jQuery('#web_invoice_main_info .invoice_description_box').autogrow();
	jQuery('#web_invoice_main_info .autogrow').autogrow();
	jQuery('#web_invoice_main_info #add_itemized_item').bind('click', add_itemized_list_row);

	jQuery('#invoices-filter .subsubsub a').click(function() {
		jQuery("#FilterTextBox").val(jQuery(this).attr('class'));
		var s = jQuery(this).attr('class').toLowerCase().split(" ");
		jQuery("#invoice_sorter_table tr:hidden").show();
		jQuery.each(s, function(){
		   jQuery("#invoice_sorter_table tr:visible .indexColumn:not(:contains('"
			  + this + "'))").parent().hide();
		});
		return false;
	});

	jQuery("#invoice_sorter_table tr:has(td)").each(function(){
	   var t = jQuery(this).text().toLowerCase(); //all row text
	   jQuery("<td class='indexColumn'></td>")
		.hide().text(t).appendTo(this);
	});//each tr

	jQuery("#FilterTextBox").keyup(function(){
	   var s = jQuery(this).val().toLowerCase().split(" ");
	   //show all rows.

	   jQuery("#invoice_sorter_table tr:hidden").show();
	   jQuery.each(s, function(){
		   jQuery("#invoice_sorter_table tr:visible .indexColumn:not(:contains('"
			  + this + "'))").parent().hide();
	   });//each
	 });//key up.


	jQuery('#new_web_invoice_form').submit(function() {
	      if(jQuery("#invoice_subject").val() == '') { jQuery("#invoice_subject").addClass("error"); jQuery("#invoice_subject").blur(); return false; }
	});

	jQuery("#invoice_sorter_table").tablesorter({headers:{0:{sorter:false},6:{sorter:false}}});
	recalc();
});


function recalc(){
	jQuery("[@id^=total_item]").calc(
		// the equation to use for the calculation
		"qty * price",
		// define the variables used in the equation, these can be a jQuery object
		{
			qty: 	jQuery("[@id^=qty_item_]"),
			price: 	jQuery("[@id^=price_item_]")
		},
		// define the formatting callback, the results of the calculation are passed to this function
		function (s){
			// return the number as a dollar amount


			return s.toFixed(2);
		},
		// define the finish callback, this runs after the calculation has been complete
		function ($this){
			// sum the total of the $("[@id^=total_item]") selector
			var tax = jQuery('#web_invoice_tax').val() / 100;
			var sum = $this.sum() + ($this.sum() * tax);
			var total_occurances = jQuery("#web_invoice_subscription_total_occurances").val();
			if(total_occurances) {
				var total_overtime = sum*total_occurances;
				jQuery("#recurring_total").html(total_overtime.toFixed(2));
			}
			jQuery("#amount").html(sum.toFixed(2));
			jQuery("#total_amount").val(sum.toFixed(2));
		}
	);


}

function add_itemized_list_row() {
	var lastRow = jQuery('#invoice_list tr:last').clone();
	var id = parseInt(jQuery('.id', lastRow).html()) + 1;;

	jQuery('.id', lastRow).html(id);
	jQuery('.item_name', lastRow).attr('name', 'itemized_list[' + id + '][name]');
	jQuery('.item_description', lastRow).attr('name', 'itemized_list[' + id + '][description]');
	jQuery('.item_quantity', lastRow).attr('name', 'itemized_list[' + id + '][quantity]');
	jQuery('.item_price', lastRow).attr('name', 'itemized_list[' + id + '][price]');
	jQuery('.item_total', lastRow).attr('id', 'total_item_' + id + '');

	jQuery('.item_name', lastRow).val('');
	jQuery('.item_description', lastRow).val('');
	jQuery('.item_quantity', lastRow).val('');
	jQuery('.item_price', lastRow).val('');
	jQuery('.item_total', lastRow).html('');

	jQuery('#invoice_list').append(lastRow);

	recalc();


	return false;

}


this.tooltip = function(){

	/* CONFIG */
		xOffset = 10;
		yOffset = 20;
		// these 2 variable determine popup's distance from the cursor
		// you might want to adjust to get the right result
	/* END CONFIG */
	jQuery(".web_invoice_tooltip").hover(function(e) {
		this.t = this.title;
		this.title = "";
		jQuery("body").append("<p id='web_invoice_tooltip'>"+ this.t +"</p>");
		jQuery("#web_invoice_tooltip")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px")
			.fadeIn("fast");
    },
	function(){
		this.title = this.t;
		jQuery("#web_invoice_tooltip").remove();
    });
	jQuery("a.web_invoice_tooltip").mousemove(function(e){
		jQuery("#tooltip")
			.css("top",(e.pageY - xOffset) + "px")
			.css("left",(e.pageX + yOffset) + "px");
	});
};

