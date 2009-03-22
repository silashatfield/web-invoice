<?php
/*
	Created by TwinCitiesTech.com
	(website: twincitiestech.com       email : support@twincitiestech.com)

	Modified by S H Mohanjith
	(website: mohanjith.com       email : support@mohanjith.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 3 of the License, with the
    exception of the JQuery JavaScript framework which is released
    under it's own license.  You may view the details of that license in
    the prototype.js file.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/



function web_invoice_default($message='')
{
	global $wpdb;
	//Make sure tables exist



	// The error takes precedence over others being that nothing can be done w/o tables
	if(!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('log')."';")) { $warning_message = ""; }

	if($warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>";
	if($message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>";

	$all_invoices = $wpdb->get_results("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num != ''");

?>




	<form id="invoices-filter" action="" method="post" >
	<h2>Invoice Overview</h2>
	<div class="tablenav clearfix">

	<div class="alignleft">
	<select name="web_invoice_action">
		<option value="-1" selected="selected">-- Actions --</option>
		<option value="send_invoice" name="sendit" >Send Invoice(s)</option>
		<option value="send_reminder" name="sendit" >Send Reminder(s)</option>
		<option value="archive_invoice" name="archive" >Archive Invoice(s)</option>
		<option value="unrachive_invoice" name="unarchive" >Un-Archive Invoice(s)</option>
		<option value="mark_as_sent" name="mark_as_sent" >Mark as Sent</option>
		<option value="mark_as_paid" name="mark_as_paid" >Mark as Paid</option>
		<?php /*<option value="make_template" name="unarchive" >Make Template</option>
		<option value="unmake_template" name="unarchive" >Unmake Template</option>*/ ?>
		<option  value="delete_invoice" name="deleteit" >Delete</option>
	</select>
	<input type="submit" value="Apply" class="button-secondary action" />
	</div>

	<div class="alignright">
		<ul class="subsubsub" style="margin:0;">
		<li>Filter:</li>
		<li><a href='#' class="" id="">All Invoices</a> |</li>
		<li><a href='#'  class="paid" id="">Paid</a> |</li>
		<li><a href='#'  class="sent" id="">Unpaid</a> |</li>
		<li>Custom: <input type="text" id="FilterTextBox" class="search-input" name="FilterTextBox" /> </li>
		</ul>
	</div>
	</div>
	<br class="clear" />



	<table class="widefat" id="invoice_sorter_table">
	<thead>
	<tr>
		<th class="check-column"><input type="checkbox" id="CheckAll" /></th>
		<th class="invoice_id_col">Invoice Id</th>
		<th>Subject</th>
		<th>Amount</th>
		<th>Status</th>
		<th>User</th>
		<th></th>
	</tr>
	</thead>
	<tbody>
	<?php

	$x_counter = 0;
	foreach ($all_invoices as $invoice) {
		// Stop if this is a recurring bill
		if(!web_invoice_meta($invoice->invoice_num,'web_invoice_recurring_billing')) {
		$x_counter++;
		unset($class_settings);

		//Basic Settings
		$invoice_id = $invoice->invoice_num;
		$subject = $invoice->subject;
		$invoice_link = web_invoice_build_invoice_link($invoice_id);
		$user_id = $invoice->user_id;



		//Determine if unique/custom id used
		$custom_id = web_invoice_meta($invoice_id,'web_invoice_custom_invoice_id');
		$display_id = ($custom_id ? $custom_id : $invoice_id);

		// Determine Currency
		$currency_code = web_invoice_determine_currency($invoice_id);


		$show_money = web_invoice_currency_symbol($currency_code) . web_invoice_currency_format($invoice->amount);

		// Determine What to Call Recipient
		$profileuser = get_user_to_edit($user_id);
		$first_name = $profileuser->first_name;
		$last_name = $profileuser->last_name;
		$user_nicename = $profileuser->user_nicename;
		if(empty($first_name) || empty($last_name)) $call_me_this = $user_nicename; else $call_me_this = $first_name . " " . $last_name;

		// Color coding
		if(web_invoice_paid_status($invoice_id)) $class_settings .= " alternate ";
		if(web_invoice_meta($invoice_id,'archive_status') == 'archived')  $class_settings .= " web_invoice_archived ";

		//Days since sent

		// Days Since Sent
		if(web_invoice_paid_status($invoice_id)) {
		$days_since = "<span style='display:none;'>-1</span> Paid"; }
		else {
			if(web_invoice_meta($invoice_id,'sent_date')) {

			$date1 = web_invoice_meta($invoice_id,'sent_date');
			$date2 = date("Y-m-d", time());
			$difference = abs(strtotime($date2) - strtotime($date1));
			$days = round(((($difference/60)/60)/24), 0);
			if($days == 0) { $days_since = "<span style='display:none;'>$days</span>Sent Today. "; }
			elseif($days == 1) { $days_since = "<span style='display:none;'>$days</span>Sent Yesterday. "; }
			elseif($days > 1) { $days_since = "<span style='display:none;'>$days</span>Sent $days days ago. "; }
			}
			else {
			$days_since ="<span style='display:none;'>999</span>Not Sent";	}
		}


		$output_row  = "<tr class='$class_settings'>\n";
		$output_row .= "	<th class='check-column'><input type='checkbox' name='multiple_invoices[]' value='$invoice_id'/></th>\n";
		$output_row .= "	<td><a href='admin.php?page=new_web_invoice&web_invoice_action=doInvoice&invoice_id=$invoice_id'>$display_id</a></td>\n";
		$output_row .= "	<td><a href='admin.php?page=new_web_invoice&web_invoice_action=doInvoice&invoice_id=$invoice_id'>$subject</a></td>\n";
		$output_row .= "	<td>$show_money</td>\n";
		$output_row .= "	<td>$days_since</td>\n";
		$output_row .= "	<td> <a href='user-edit.php?user_id=$user_id'>$call_me_this</a></td>\n";
		$output_row .= "	<td><a href='$invoice_link'>View Web Invoice</a></td>\n";
		$output_row .= "</tr>";

		echo $output_row;
	} /* Recurring Billing Stop */
}
	if($x_counter == 0) {
	// No result
	?>
<tr><td colspan="6" align="center"><div style="padding: 20px;">You have not created any invoices yet, <a href="admin.php?page=new_web_invoice">create one now.</a></div></td></tr>
	<?php

	}
?>
	</tbody>
	</table>
	<?php if($wpdb->query("SELECT meta_value FROM `".Web_Invoice::tablename('meta')."` WHERE meta_value = 'archived'")) { ?><a href="" id="web_invoice_show_archived">Show / Hide Archived</a><?php }?>
	</form>
<?php



	// web_invoice_options_manageInvoice();
	 if(web_invoice_is_not_merchant()) web_invoice_cc_setup(false);

}

function web_invoice_recurring_overview($message='')
{
	global $wpdb;
	//Make sure tables exist



	// The error takes precedence over others being that nothing can be done w/o tables
	if(!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('log')."';")) { $warning_message = ""; }

	if($warning_message) echo "<div id='message' class='error' ><p>$warning_message</p></div>";
	if($message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>";

	$all_invoices = $wpdb->get_results("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num != ''");

?>




	<form id="invoices-filter" action="" method="post" >
	<input type="hidden" name="web_invoice_recurring_billing" value="true" />
	<h2>Recurring Billing Overview</h2>

	<?php if(web_invoice_is_not_merchant()) { ?>
	<div class="web_invoice_rounded_box">
		<p><b>You need a credit card processing account to use recurring billing. </b>
			You may get an ARB (Automated Recurring Billing) account from <a href="http://keti.ws/37281">MerchantPlus</a> (800-546-1997),
			<a href="http://keti.ws/37282">MerchantExpress.com</a> (888-845-9457) or
			<a href="http://keti.ws/36282">MerchantWarehouse</a> (866-400-9706).</p>
		<p>	Once you have an account, enter in your username and transaction key into the <a href="admin.php?page=web_invoice_settings">settings page</a>.</p>
	</div>
	<?php } ?>

	<div class="tablenav clearfix">

	<div class="alignleft">
	<select name="web_invoice_action">
		<option value="-1" selected="selected">-- Actions --</option>
		<option value="send_invoice" name="sendit" >Send Invoice(s)</option>
		<option value="archive_invoice" name="archive" >Archive Invoice(s)</option>
		<option value="unrachive_invoice" name="unarchive" >Un-Archive Invoice(s)</option>
		<option value="stop_web_invoice_recurring_billing" name="mark_as_sent" >Stop Recurring Billing</option>
		<option  value="delete_invoice" onClick="if(confirm('If you delete a recurring invoice, the subscription will be cancelled.')) {return true;} return false;">Delete</option>
	</select>
	<input type="submit" value="Apply" class="button-secondary action" />
	</div>

	<div class="alignright">
		<ul class="subsubsub" style="margin:0;">
		<li>Filter: <input type="text" id="FilterTextBox" class="search-input" name="FilterTextBox" /> </li>
		</ul>
	</div>
	</div>
	<br class="clear" />



	<table class="widefat" id="invoice_sorter_table">
	<thead>
	<tr>
		<th class="check-column"><input type="checkbox" id="CheckAll" /></th>
		<th class="invoice_id_col">Invoice Id</th>
		<th>Subject</th>
		<th>Amount</th>
		<th>Status</th>
		<th>User</th>
		<th>&nbsp;</th>
	</tr>
	</thead>
	<tbody>
	<?php

	$web_invoice_payment_link = get_option("web_invoice_payment_link");
	if(!empty($web_invoice_payment_link)) { if(strpos('?',$web_invoice_payment_link)) { $web_invoice_payment_link = $web_invoice_payment_link . "&";} else {$web_invoice_payment_link = $web_invoice_payment_link . "?";} }

	$x_counter = 0;
	foreach ($all_invoices as $invoice) {
		if(web_invoice_meta($invoice->invoice_num,'web_invoice_recurring_billing')) {
		$x_counter++;

		unset($class_settings);

		//Basic Settings
		$invoice_id = $invoice->invoice_num;


		if(web_invoice_meta($invoice_id,'web_invoice_custom_invoice_id')) $custom_id = web_invoice_meta($invoice_id,'web_invoice_custom_invoice_id'); else $custom_id = $invoice_id;
		$subject = $invoice->subject;
		$invoice_link = web_invoice_build_invoice_link($invoice_id);
		$user_id = $invoice->user_id;
		// Determine Currency
		$currency_code = web_invoice_determine_currency($invoice_id);

		$show_money = web_invoice_currency_symbol($currency_code) . web_invoice_currency_format($invoice->amount);

		// Determine What to Call Recipient
		$profileuser = get_user_to_edit($user_id);
		$first_name = $profileuser->first_name;
		$last_name = $profileuser->last_name;
		$user_nicename = $profileuser->user_nicename;
		if(empty($first_name) || empty($last_name)) $call_me_this = $user_nicename; else $call_me_this = $first_name . " " . $last_name;

		// Color coding
		if(web_invoice_paid_status($invoice_id)) $class_settings .= " alternate ";
		if(web_invoice_meta($invoice_id,'archive_status') == 'archived')  $class_settings .= " web_invoice_archived ";

		//Days since sent

		// Days Since Sent
		if(web_invoice_paid_status($invoice_id)) {
		$days_since = "<span style='display:none;'>-2</span> Paid"; }
		else {
			if(web_invoice_meta($invoice_id,'sent_date')) {

			$date1 = web_invoice_meta($invoice_id,'sent_date');
			$date2 = date("Y-m-d", time());
			$difference = abs(strtotime($date2) - strtotime($date1));
			$days = round(((($difference/60)/60)/24), 0);
			if($days == 0) { $days_since = "<span style='display:none;'>$days</span>Sent Today. "; }
			elseif($days == 1) { $days_since = "<span style='display:none;'>$days</span>Sent Yesterday. "; }
			elseif($days > 1) { $days_since = "<span style='display:none;'>$days</span>Sent $days days ago. "; }
			}
			else {
			$days_since ="<span style='display:none;'>999</span>Not Sent";	}
		}

		if(web_invoice_recurring_started($invoice_id)) $days_since = "<span style='display:none;'>-1</span>Active Recurring";



		$output_row  = "<tr class='$class_settings'>\n";
		$output_row .= "	<th class='check-column'><input type='checkbox' name='multiple_invoices[]' value='$invoice_id'/></th>\n";
		$output_row .= "	<td><a href='admin.php?page=new_web_invoice&web_invoice_action=doInvoice&invoice_id=$invoice_id'>$custom_id</a></td>\n";
		$output_row .= "	<td><a href='admin.php?page=new_web_invoice&web_invoice_action=doInvoice&invoice_id=$invoice_id'>$subject</a></td>\n";
		$output_row .= "	<td>$show_money</td>\n";
		$output_row .= "	<td>$days_since</td>\n";
		$output_row .= "	<td> <a href='user-edit.php?user_id=$user_id'>$call_me_this</a></td>\n";
		$output_row .= "	<td><a href='$invoice_link'>View Web Invoice</a></td>\n";
		$output_row .= "</tr>";

		echo $output_row;
	} /* Recurring Billing */
}
	if($x_counter == 0) {
	// No result
	?>
<tr><td colspan="6" align="center"><div style="padding: 20px;">You have not created any recurring invoices yet, <a href="admin.php?page=new_web_invoice">create one now.</a></div></td></tr>
	<?php

	}

		?>
	</tbody>
	</table>
		<?php if($wpdb->query("SELECT meta_value FROM `".Web_Invoice::tablename('meta')."` WHERE meta_value = 'archived'")) { ?><a href="" id="web_invoice_show_archived">Show / Hide Archived</a><?php }?>
		</form>

		<?php



	// web_invoice_options_manageInvoice();
	 if(web_invoice_is_not_merchant()) web_invoice_cc_setup(false);

}

function web_invoice_saved_preview($invoice_id)
{

	?>

	<h2>Save and Preview</h2>

	<p>This is what your invoice will appear like in the email message. The recipient will see the itemized list after following their link to your website.</p>

	<div id="invoice_preview">
	<?php echo web_invoice_show_invoice($invoice_id); ?>
	</div>

	<div class="invoice_horizontal_buttons">
		<form method="post" action="admin.php?page=web-invoice/web-invoice.php">
		<input type="hidden" value="<?php echo $invoice_id; ?>" name="invoice_id" />
		<input type="hidden" value="doInvoice" name="web_invoice_action" />
		<input type="submit" value="Continue Editing" name="doInvoice" class="button-secondary" />
		</form>

		<form method="post" action="admin.php?page=web-invoice/web-invoice.php">
		<input type="hidden" value="<?php echo $invoice_id; ?>" name="invoice_id" />
		<input type="hidden" value="send_now" name="web_invoice_action" >
		<input type="submit" value="Email To Client" class="button-secondary" />
		</form>

		<form method="post" action="admin.php?page=web-invoice/web-invoice.php">
		<input type="hidden" value="<?php echo $invoice_id; ?>" name="invoice_id" />
		<input type="hidden" value="save_not_send" name="web_invoice_action" />
		<input type="submit" value="Save for Later" name="save" class="button-secondary" />
		</form>

	</div>
	Do not use the back button or you could have duplicates.

<?php

}


function web_invoice_options_manageInvoice($invoice_id = '',$message='')
{
	global $wpdb;

	//Load Defaults
	$currency = get_option("web_invoice_default_currency_code");


	if(!empty($_REQUEST['user_id'])) $user_id = $_REQUEST['user_id'];

	// Need to unset these values
	if(empty($_POST['copy_from_template'])) {unset($_POST['copy_from_template']);}
	if($invoice_id == '') {unset($invoice_id);}


	// New Invoice From Template
	if(isset($_POST['copy_from_template']) && $_POST['user_id']) {
		$template_invoice_id = $_POST['copy_from_template'];
		$invoice_info = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '".$template_invoice_id."'");
		$user_id = $_REQUEST['user_id'];
		$amount = $invoice_info->amount;
		$subject = $invoice_info->subject;
		$description = $invoice_info->description;
		$itemized = $invoice_info->itemized;
		$profileuser = get_user_to_edit($_POST['user_id']);
		$itemized_array = unserialize(urldecode($itemized));
		$web_invoice_tax = web_invoice_meta($template_invoice_id,'tax_value');
		$web_invoice_currency_code = web_invoice_meta($template_invoice_id,'web_invoice_currency_code');
		$web_invoice_due_date_day = web_invoice_meta($template_invoice_id,'web_invoice_due_date_day');
		$web_invoice_due_date_month = web_invoice_meta($template_invoice_id,'web_invoice_due_date_month');
		$web_invoice_due_date_year = web_invoice_meta($template_invoice_id,'web_invoice_due_date_year');

		$web_invoice_subscription_name = web_invoice_meta($template_invoice_id,'web_invoice_subscription_name');
		$web_invoice_subscription_unit = web_invoice_meta($template_invoice_id,'web_invoice_subscription_unit');
		$web_invoice_subscription_length = web_invoice_meta($template_invoice_id,'web_invoice_subscription_length');
		$web_invoice_subscription_start_month = web_invoice_meta($template_invoice_id,'web_invoice_subscription_start_month');
		$web_invoice_subscription_start_day = web_invoice_meta($template_invoice_id,'web_invoice_subscription_start_day');
		$web_invoice_subscription_start_year = web_invoice_meta($template_invoice_id,'web_invoice_subscription_start_year');
		$web_invoice_subscription_total_occurances = web_invoice_meta($template_invoice_id,'web_invoice_subscription_total_occurances');

		$web_invoice_recurring_billing = web_invoice_meta($template_invoice_id,'web_invoice_recurring_billing');

	}


	// Invoice Exists, we are modifying it
	if(isset($invoice_id)) {
		$invoice_info = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '".$invoice_id."'");
		$user_id = $invoice_info->user_id;
		$amount = $invoice_info->amount;
		$subject = $invoice_info->subject;
		$description = $invoice_info->description;
		$itemized = $invoice_info->itemized;
		$profileuser = get_user_to_edit($invoice_info->user_id);
		$itemized_array = unserialize(urldecode($itemized));
		$web_invoice_tax = web_invoice_meta($invoice_id,'tax_value');
		$web_invoice_custom_invoice_id = web_invoice_meta($invoice_id,'web_invoice_custom_invoice_id');
		$web_invoice_due_date_day = web_invoice_meta($invoice_id,'web_invoice_due_date_day');
		$web_invoice_due_date_month = web_invoice_meta($invoice_id,'web_invoice_due_date_month');
		$web_invoice_due_date_year = web_invoice_meta($invoice_id,'web_invoice_due_date_year');
		$web_invoice_currency_code = web_invoice_meta($invoice_id,'web_invoice_currency_code');
		$web_invoice_recurring_billing = web_invoice_meta($invoice_id,'web_invoice_recurring_billing');

		$web_invoice_subscription_name = web_invoice_meta($invoice_id,'web_invoice_subscription_name');
		$web_invoice_subscription_unit = web_invoice_meta($invoice_id,'web_invoice_subscription_unit');
		$web_invoice_subscription_length = web_invoice_meta($invoice_id,'web_invoice_subscription_length');
		$web_invoice_subscription_start_month = web_invoice_meta($invoice_id,'web_invoice_subscription_start_month');
		$web_invoice_subscription_start_day = web_invoice_meta($invoice_id,'web_invoice_subscription_start_day');
		$web_invoice_subscription_start_year = web_invoice_meta($invoice_id,'web_invoice_subscription_start_year');
		$web_invoice_subscription_total_occurances = web_invoice_meta($invoice_id,'web_invoice_subscription_total_occurances');



	}

	//Whether recurring bill will start when client pays, or a date is specified
	if($web_invoice_subscription_start_month && $web_invoice_subscription_start_year && $web_invoice_subscription_start_day) $recurring_auto_start = true; else $recurring_auto_start = false;


	// Brand New Invoice
	if(!isset($invoice_id) && isset($_REQUEST['user_id'])) {
	$profileuser = get_user_to_edit($_REQUEST['user_id']);
	}

	// Load Userdata
	$user_email = $profileuser->user_email;
	$first_name = $profileuser->first_name;
	$last_name = $profileuser->last_name;
	$streetaddress = $profileuser->streetaddress;
	$city = $profileuser->city;
	$state = $profileuser->state;
	$zip = $profileuser->zip;
	$country = $profileuser->country;

	//Load Invoice Specific Settings, and override default
	if(!empty($web_invoice_currency_code)) $currency = $web_invoice_currency_code;


	// Crreae two blank arrays for itemized list if none is set
	if(count($itemized_array) == 0) {
	$itemized_array[1] = "";
	$itemized_array[2] = "";
	}

	if(get_option("web_invoice_web_invoice_page") == '') { $warning_message .= "Invoice page not selected. "; }
	if(get_option("web_invoice_payment_method") == '') { $warning_message .= "Payment method not set. "; }
	if(get_option("web_invoice_payment_method") == '' || get_option("web_invoice_web_invoice_page") == '') { $warning_message .= "Visit <a href='admin.php?page=web_invoice_settings'>settings page</a> to configure."; }

	if(!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('meta')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('log')."';")) { $warning_message = "The plugin database tables are gone, deactivate and reactivate plugin to re-create them."; }

	if($warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>";
	if($message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>";


	?>



	<?php if(!isset($invoice_id)) { ?> <h2>New Web Invoice</h2><?php  web_invoice_draw_user_selection_form($user_id); } ?>
	<?php if(isset($user_id) && isset($invoice_id)) { ?><h2>Manage Invoice</h2><?php } ?>

	<?php if(isset($invoice_id) && web_invoice_paid_status($invoice_id) || web_invoice_recurring_started($invoice_id) || web_invoice_query_log($invoice_id, 'subscription_error')) { ?>
	<div class="updated web_invoice_status">
	<?php if(web_invoice_paid_status($invoice_id)) { ?>
	<h2>Invoice Paid</h2>
	<?php foreach(web_invoice_query_log($invoice_id, 'paid') as $info) {
	echo $info->value . " on <span class='web_invoice_tamp_stamp'>" . $info->time_stamp . "</span><br />";
	} ?>
	<?php } ?>

	<?php if(web_invoice_recurring_started($invoice_id)) { ?>
	<h2>Recurring Billing Initiated</h2>
	<?php foreach(web_invoice_query_log($invoice_id, 'subscription') as $info) {
	echo $info->value . " on " . $info->time_stamp . "<br />";
	} } ?>

	<?php
	$subscription_errors = web_invoice_query_log($invoice_id, 'subscription_error');
	if($subscription_errors) { ?>
	<h2>Recurring Billing Problems</h2>
	<ol>
	<?php
	foreach($subscription_errors as $info) {
	echo "<li>" . $info->value . " on " . $info->time_stamp . "</li>";
	} ?>
	</ol>
	<?php	}
	}  ?>



	</div>


	<?php if(isset($user_id)) { ?>
	<div id="poststuff" class="metabox-holder">
	<form id='new_web_invoice_form' action="admin.php?page=new_web_invoice&web_invoice_action=save_and_preview" method='POST'>

	<input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
	<input type="hidden" name="invoice_id" value="<?php if(isset($invoice_id)) { echo $invoice_id; } else { echo rand(10000000, 90000000);}  ?>" />
	<input type="hidden" name="amount" id="total_amount" value="<?php echo $amount; ?>" />


<div class="postbox" id="web_invoice_client_info_div">
<h3><label for="link_name">Client Information</label></h3>
<div class="inside">


<table class="form-table" id="add_new_web_invoice">

	<?php
	if(get_option('web_invoice_business_name') == '') 		echo "<tr><th colspan=\"2\">Your business name isn't set, go to Settings page to set it.</a></th></tr>\n"; 	?>

	<tr><th><?php _e("Email Address") ?></th><td><?php echo $user_email; ?> <a class="web_invoice_click_me" href="user-edit.php?user_id=<?php echo $user_id; ?>#billing_info">Go to User Profile</a></td>
	<tr style="height: 90px;"><th><?php _e("Billing Information") ?></th>
	<td>


	<div id="web_invoice_edit_user_from_invoice">
      <span class="web_invoice_make_editable<?php if(!$first_name) echo " web_invoice_unset"; ?>" id="web_invoice_first_name"><?php if($first_name) echo $first_name; else echo "Set First Name"; ?></span>
      <span class="web_invoice_make_editable<?php if(!$last_name) echo " web_invoice_unset"; ?>" id="web_invoice_last_name"><?php if($last_name) echo $last_name; else echo "Set Last Name"; ?></span><br />
      <span class="web_invoice_make_editable<?php if(!$streetaddress) echo " web_invoice_unset"; ?>" id="web_invoice_streetaddress"><?php if($streetaddress) echo $streetaddress; else echo "Set Street Address"; ?></span><br />
      <span class="web_invoice_make_editable<?php if(!$city) echo " web_invoice_unset"; ?>" id="web_invoice_city"><?php if($city) echo $city; else echo "Set City"; ?></span>
      <span class="web_invoice_make_editable<?php if(!$state) echo " web_invoice_unset"; ?>" id="web_invoice_state"><?php if($state) echo $state; else echo "Set State"; ?></span>
      <span class="web_invoice_make_editable<?php if(!$zip) echo " web_invoice_unset"; ?>" id="web_invoice_zip"><?php if($zip) echo $zip; else echo "Set Zip Code"; ?></span>
      <span class="web_invoice_make_editable<?php if(!$country) echo " web_invoice_unset"; ?>" id="web_invoice_country"><?php if($country) echo $country; else echo "Set Country"; ?></span>

	</div>
	</td>

</table>

</div>
</div>

<div class="postbox" id="web_invoice_client_info_div">
<h3><label for="link_name">Recurring Billing</label></h3>

<div id="web_invoice_enable_recurring_billing" class="web_invoice_click_me" <?php if($web_invoice_recurring_billing) { ?>style="display:none;"<?php } ?>>
	Create a recurring billing schedule for this invoice.
</div>

<div class="web_invoice_enable_recurring_billing" <?php if(!$web_invoice_recurring_billing) { ?>style="display:none;"<?php } ?>>

<table class="form-table" id="add_new_web_invoice">
	<tr>
		<th><a class="web_invoice_tooltip"  title="A name to identify this subscription by in addition to the invoice id. (ex: 'standard hosting')">Subscription Name</a></th>
		<td><?php echo web_invoice_draw_inputfield('web_invoice_subscription_name',$web_invoice_subscription_name); ?></td>
	</tr>

	<tr>
		<th>Start Date</th>
		<td>


			<span style="<?php if($recurring_auto_start) { ?>display:none;<?php } ?>" class="web_invoice_timestamp">Start automatically as soon as the customer enters their billing information. <span class="web_invoice_click_me" onclick="jQuery('.web_invoice_timestamp').toggle();">Specify Start Date</span></span>

			<div style="<?php if(!$recurring_auto_start) { ?>display:none;<?php } ?>" class="web_invoice_timestamp">
			<?php echo web_invoice_draw_select('web_invoice_subscription_start_month', array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec"), $web_invoice_subscription_start_month); ?>
			<?php echo web_invoice_draw_inputfield('web_invoice_subscription_start_day', $web_invoice_subscription_start_day, ' size="2" maxlength="2" autocomplete="off" '); ?>,
			<?php echo web_invoice_draw_inputfield('web_invoice_subscription_start_year', $web_invoice_subscription_start_year, ' size="4" maxlength="4" autocomplete="off" '); ?>
			<span onclick="web_invoice_subscription_start_time(7);" class="web_invoice_click_me">In One Week</span> |
			<span onclick="web_invoice_subscription_start_time(30);" class="web_invoice_click_me">In 30 Days</span> |
			<span onclick="jQuery('.web_invoice_timestamp').toggle();web_invoice_subscription_start_time('clear');"  class="web_invoice_click_me">Start automatically</span>
			</div>
		</td>
	</tr>

	<tr>
		<th><a class="web_invoice_tooltip"  title="This will be the number of times the client will be billed. (ex: 12)">Bill Every</a></th>
		<td><?php echo web_invoice_draw_inputfield('web_invoice_subscription_length', $web_invoice_subscription_length,' size="3" maxlength="3" autocomplete="off" '); ?> <?php echo web_invoice_draw_select('web_invoice_subscription_unit', array("months" => "month(s)","days"=> "days"), $web_invoice_subscription_unit); ?></td>
	</tr>

	<tr>
		<th><a class="web_invoice_tooltip"  title="Keep it under the maximum of 9999.">Total Billing Cycles</a></th>
		<td><?php echo web_invoice_draw_inputfield('web_invoice_subscription_total_occurances', $web_invoice_subscription_total_occurances,' size="4" maxlength="4" autocomplete="off" '); ?></td>
	</tr>

	<tr>
		<th></th>
		<td>All <b>recurring billing</b> fields must be filled out to activate recurring billing. <span onclick="web_invoice_cancel_recurring()" class="web_invoice_click_me">Cancel Recurring Billing</span></td>
	</tr>
</table>

</div>
</div>


<div id="web_invoice_main_info" class="metabox-holder">
<div id="submitdiv" class="postbox" style="">
<h3 class="hndle"><span>Invoice Details</span></h3>
<div class="inside">



<table class="form-table">




	<tr class="invoice_main">
		<th>Subject</th>
		<td>
			<input  id="invoice_subject" class="subject"  name='subject' value='<?php echo $subject; ?>' />
		</td>
	</tr>



	<tr class="invoice_main"><th>Description / PO</th><td><textarea class="invoice_description_box" name='description' value=''><?php echo $description; ?></textarea></td></tr>

	<tr class="invoice_main">
		<th>Itemized List</th>
	<td>
		<table id="invoice_list" class="itemized_list">
		<tr>
		<th class="id">ID</th>
		<th class="name">Name</th>
		<th class="description">Description</th>
		<th class="quantity">Quantity</th>
		<th class="price">Unit Price</th>
		<th class="item_total">Total</th>
		</tr>

		<?php
		$counter = 1;
		foreach($itemized_array as $itemized_item){	 ?>

		<tr valign="top">
			<td valign="top" class="id"><?php echo $counter; ?></td>
			<td valign="top" class="name"><input class="item_name" name="itemized_list[<?php echo $counter; ?>][name]" value="<?php echo stripslashes($itemized_item[name]); ?>" /></td>
			<td valign="top" class="description"><textarea style="height: 25px;" name="itemized_list[<?php echo $counter; ?>][description]" class="item_description autogrow"><?php echo stripslashes($itemized_item[description]); ?></textarea></td>
			<td valign="top" class="quantity"><input autocomplete="off"  value="<?php echo stripslashes($itemized_item[quantity]); ?>" name="itemized_list[<?php echo $counter; ?>][quantity]" id="qty_item_<?php echo $counter; ?>"  class="item_quantity" /></td>
			<td valign="top" class="price"><input autocomplete="off" value="<?php echo stripslashes($itemized_item[price]); ?>"  name="itemized_list[<?php echo $counter; ?>][price]" id="price_item_<?php echo $counter; ?>"  class="item_price" /></td>
			<td valign="top" class="item_total" id="total_item_<?php echo $counter; ?>" ></td>
		</tr>


		<?php $counter++; } ?>
		</table>
	</td>
	</tr>

	<tr class="invoice_main">
		<th style='vertical-align:bottom;text-align:right;'><p><a href="#" id="add_itemized_item">Add Another Item</a><br /><span class='web_invoice_light_text'></span></p></th>
		<td>
			<table class="itemized_list">

			<tr>
			<td align="right">Invoice Total:</td>
			<td class="item_total"><span id='amount'></span></td>
			</tr>

			<tr>
			<td align="right">Recurring Invoice Total:</td>
			<td class="item_total"><span id='recurring_total'></span></td>
			</tr>

			</table>
		</td>
	</tr>

</table>
</div></div></div>



<div id="submitdiv" class="postbox" style="">
<h3 class="hndle"><span>Publish</span></h3>
<div class="inside">
<div id="minor-publishing">

<div id="misc-publishing-actions">
<table class="form-table">


	<tr class="invoice_main">
		<th>Invoice ID </th>
		<td style="font-size: 1.1em; padding-top:7px;">
		<input class="web_invoice_custom_invoice_id<?php if(empty($web_invoice_custom_invoice_id)) { echo " web_invoice_hidden"; } ?>" name="web_invoice_custom_invoice_id" value="<?php echo $web_invoice_custom_invoice_id;?>" />
		<?php if(isset($invoice_id)) { echo $invoice_id; } else { echo rand(10000000, 90000000);}  ?> <a class="web_invoice_custom_invoice_id web_invoice_click_me <?php if(!empty($web_invoice_custom_invoice_id)) { echo " web_invoice_hidden"; } ?>" href="#">Custom Invoice ID</a>

		</td>
	</tr>

	<tr class="invoice_main">
		<th>Tax </th>
		<td style="font-size: 1.1em; padding-top:7px;">
			<input style="width: 35px;"  name="web_invoice_tax" id="web_invoice_tax" autocomplete="off" value="<?php echo $web_invoice_tax ?>" />%
		</td>
	</tr>

		<tr class="">
		<th>Currency</th>
		<td>
			<select name="web_invoice_currency_code">
				<?php foreach(web_invoice_currency_array() as $value=>$currency_x) {
				echo "<option value='$value'"; if($currency == $value) echo " SELECTED"; echo ">$value - $currency_x</option>\n";
				}
				?>
			</select>
		</td>
	</tr>

	<tr class="">
		<th>Due Date</th>
		<td>
			<div id="timestampdiv" style="display:block;">
			<select id="mm" name="web_invoice_due_date_month">
			<option></option>
			<option value="1" <?php if($web_invoice_due_date_month == '1') echo " selected='selected'";?>>Jan</option>
			<option value="2" <?php if($web_invoice_due_date_month == '2') echo " selected='selected'";?>>Feb</option>
			<option value="3" <?php if($web_invoice_due_date_month == '3') echo " selected='selected'";?>>Mar</option>
			<option value="4" <?php if($web_invoice_due_date_month == '4') echo " selected='selected'";?>>Apr</option>
			<option value="5" <?php if($web_invoice_due_date_month == '5') echo " selected='selected'";?>>May</option>
			<option value="6" <?php if($web_invoice_due_date_month == '6') echo " selected='selected'";?>>Jun</option>
			<option value="7" <?php if($web_invoice_due_date_month == '7') echo " selected='selected'";?>>Jul</option>
			<option value="8" <?php if($web_invoice_due_date_month == '8') echo " selected='selected'";?>>Aug</option>
			<option value="9" <?php if($web_invoice_due_date_month == '9') echo " selected='selected'";?>>Sep</option>
			<option value="10" <?php if($web_invoice_due_date_month == '10') echo " selected='selected'";?>>Oct</option>
			<option value="11" <?php if($web_invoice_due_date_month == '11') echo " selected='selected'";?>>Nov</option>
			<option value="12" <?php if($web_invoice_due_date_month == '12') echo " selected='selected'";?>>Dec</option>
			</select>
			<input type="text" id="jj" name="web_invoice_due_date_day" value="<?php echo $web_invoice_due_date_day; ?>" size="2" maxlength="2" autocomplete="off" />,
			<input type="text" id="aa" name="web_invoice_due_date_year" value="<?php echo $web_invoice_due_date_year; ?>" size="4" maxlength="5" autocomplete="off" />
			<span onclick="web_invoice_add_time(7);" class="web_invoice_click_me">In One Week</span> |
			<span onclick="web_invoice_add_time(30);" class="web_invoice_click_me">In 30 Days</span> |
			<span onclick="web_invoice_add_time('clear');" class="web_invoice_click_me">Clear</span>
			</div>
		</td>
	</tr>


</table>
</div>
<div class="clear"></div>
</div>

<div id="major-publishing-actions">


<div id="publishing-action">
	<input type="submit"  name="save" class="button-primary" value="Save and Preview" />
</div>
<div class="clear"></div>
</div>


</div>
</div>

</div>
</form>



<?php if(web_invoice_get_invoice_status($invoice_id,'100')) { ?>
	<div class="updated web_invoice_status">
		<h2>This Invoice's History (<a href="admin.php?page=new_web_invoice&invoice_id=<?php echo $invoice_id; ?>&web_invoice_action=clear_log">Clear Log</a>)</h2>
		<ul id="invoice_history_log">
		<?php echo web_invoice_get_invoice_status($invoice_id,'100'); ?>
		</ul>
	</div>
<?php } else { ?>

<?php }?>

<br class="cb" />

<?php } ?>

<?php
}

function web_invoice_show_welcome_message() {

global $wpdb; ?>

<h2>Web Invoice Setup Steps</h2>

	<ol style="list-style-type:decimal;padding-left: 20px;" id="web_invoice_first_time_setup">
<?php
	$web_invoice_web_invoice_page = get_option("web_invoice_web_invoice_page");
	$web_invoice_paypal_address = get_option("web_invoice_paypal_address");
	$web_invoice_moneybookers_address = get_option("web_invoice_moneybookers_address");
	$web_invoice_alertpay_address = get_option("web_invoice_alertpay_address");
	$web_invoice_gateway_username = get_option("web_invoice_gateway_username");
	$web_invoice_payment_method = get_option("web_invoice_payment_method");

?>
	<form action="admin.php?page=new_web_invoice" method='POST'>
	<input type="hidden" name="web_invoice_action" value="first_setup" />
<?php if(empty($web_invoice_web_invoice_page) ) { ?>
	<li><a class="web_invoice_tooltip"  title="Your clients will have to follow their secure link to this page to see their invoice. Opening this page without following a link will result in the standard page content begin shown.">Select a page to display your web invoices</a>:
		<select name='web_invoice_web_invoice_page'>
		<option></option>
		<?php $list_pages = $wpdb->get_results("SELECT ID, post_title, post_name, guid FROM ". $wpdb->prefix ."posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_title");
		foreach ($list_pages as $page)
		{
		echo "<option  style='padding-right: 10px;'";
		if(isset($web_invoice_web_invoice_page) && $web_invoice_web_invoice_page == $page->ID) echo " SELECTED ";
		echo " value=\"".$page->ID."\">". $page->post_title . "</option>\n";
		} ?>
		</select>
	</li>
<?php } ?>

<?php if(empty($web_invoice_payment_method)) { ?>
	<li>Select how you want to accept money:
		<select id="web_invoice_payment_method" name="web_invoice_payment_method[]" multiple="multiple" size="3">
		<option></option>
		<option value="paypal" style="padding-right: 10px;"<?php if(stristr(get_option('web_invoice_payment_method'), 'paypal')) echo 'selected="yes"';?>>PayPal</option>
		<option value="moneybookers" style="padding-right: 10px;"<?php if(stristr(get_option('web_invoice_payment_method'), 'moneybookers')) echo 'selected="yes"';?>>Moneybookers</option>
		<option value="alertpay" style="padding-right: 10px;"<?php if(stristr(get_option('web_invoice_payment_method'), 'alertpay')) echo 'selected="yes"';?>>AlertPay</option>
		<option value="cc" style="padding-right: 10px;"<?php if(stristr(get_option('web_invoice_payment_method'), 'cc')) echo 'selected="yes"';?>>Credit Card</option>
		</select>

		<li class="paypal_info">Your PayPal username: <input id='web_invoice_paypal_address' name="web_invoice_paypal_address" class="search-input input_field"  type="text" value="<?php echo stripslashes(get_option('web_invoice_paypal_address')); ?>" /></li>

		<li class="moneybookers_info">
			Your Moneybookers username: <input id='web_invoice_moneybookers_address' name="web_invoice_moneybookers_address" class="search-input input_field"  type="text" value="<?php echo stripslashes(get_option('web_invoice_moneybookers_address')); ?>" />
			<a href="http://keti.ws/27481" alt="moneybookers.com" class="web_invoice_click_me">Do you need a Moneybookers account?</a>
		</li>
		<li class="moneybookers_info">
			Enable Moneybookers payment notifications:
			<select id='web_invoice_moneybookers_merchant' name="web_invoice_moneybookers_merchant">
			<option value="True" <?php echo (get_option('web_invoice_moneybookers_merchant')=='False')?'selected="selected"':''; ?> >yes</option>
			<option value="False" <?php echo (get_option('web_invoice_moneybookers_merchant')=='True')?'':'selected="selected"'; ?> >no</option>
			</select>
		</li>
		<li class="moneybookers_info moneybookers_info_merchant">
			Moneybookers payment notification secret: <input id='web_invoice_moneybookers_secret' name="web_invoice_moneybookers_secret" class="search-input input_field"  type="text" value="<?php echo stripslashes(get_option('web_invoice_moneybookers_secret')); ?>" />
		</li>
		<li class="moneybookers_info moneybookers_info_merchant">
			Moneybookers payment notification IP: <input id='web_invoice_moneybookers_ip' name="web_invoice_moneybookers_ip" class="search-input input_field"  type="text" value="<?php echo stripslashes(get_option('web_invoice_moneybookers_ip')); ?>" />
		</li>

		<li class="alertpay_info">
			Your AlertPay username: <input id='web_invoice_alertpay_address' name="web_invoice_alertpay_address" class="search-input input_field" type="text" value="<?php echo stripslashes(get_option('web_invoice_alertpay_address')); ?>" />
			<a href="http://keti.ws/36283" alt="alertpay.com" class="web_invoice_click_me">Do you need an AlertPay account?</a>
		</li>
		<li class="alertpay_info">
			Enable AlertPay IPN:
			<select id='web_invoice_alertpay_merchant' name="web_invoice_alertpay_merchant">
			<option value="True" <?php echo (get_option('web_invoice_alertpay_merchant')=='False')?'selected="selected"':''; ?> >yes</option>
			<option value="False" <?php echo (get_option('web_invoice_alertpay_merchant')=='True')?'':'selected="selected"'; ?> >no</option>
			</select>
			<span class="web_invoice_alertpay_url web_invoice_info">Your alert URL is
				<a title="Copy this link" href="<?php echo web_invoice_get_alertpay_api_url(); ?>"><?php echo web_invoice_get_alertpay_api_url(); ?></a>.<br/>
				Please note that AlertPay has issues with some SSL certificates. (Your milage may vary).
			</span>
		</li>
		<li class="alertpay_info alertpay_info_merchant">
			AlertPay IPN security code: <input id='web_invoice_alertpay_secret' name="web_invoice_alertpay_secret" class="search-input input_field"  type="text" value="<?php echo stripslashes(get_option('web_invoice_alertpay_secret')); ?>" />
		</li>

		<li class="gateway_info">
		<a class="web_invoice_tooltip"  title="Your credit card processor will provide you with a gateway username.">Gateway Username</a>
		<input AUTOCOMPLETE="off" name="web_invoice_gateway_username" class="input_field search-input" type="text" value="<?php echo stripslashes(get_option('web_invoice_gateway_username')); ?>" />
		</li>

		<li class="gateway_info">
		<a class="web_invoice_tooltip"  title="You will be able to generate this in our credit card processor's control panel.">Gateway Transaction Key</a>
		<input AUTOCOMPLETE="off" name="web_invoice_gateway_tran_key" class="input_field search-input" type="text" value="<?php echo stripslashes(get_option('web_invoice_gateway_tran_key')); ?>" />
		</li>

		<li class="gateway_info">
		Gateway URL
		<input name="web_invoice_gateway_url" class="input_field search-input" type="text" value="<?php echo stripslashes(get_option('web_invoice_gateway_url')); ?>" />
		</li>

<?php } ?>

	<li>Send an invoice:
		<select name='user_id' class='user_selection'>
		<option ></option>
		<?php
		$get_all_users = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix . "users LEFT JOIN ". $wpdb->prefix . "usermeta on ". $wpdb->prefix . "users.id=". $wpdb->prefix . "usermeta.user_id and ". $wpdb->prefix . "usermeta.meta_key='last_name' ORDER BY ". $wpdb->prefix . "usermeta.meta_value");
		foreach ($get_all_users as $user)
		{
		$profileuser = get_user_to_edit($user->ID);
		echo "<option ";
		if(isset($user_id) && $user_id == $user->ID) echo " SELECTED ";
		if(!empty($profileuser->last_name) && !empty($profileuser->first_name)) { echo " value=\"".$user->ID."\">". $profileuser->last_name. ", " . $profileuser->first_name . " (".$profileuser->user_email.")</option>\n";  }
		else
		{
		echo " value=\"".$user->ID."\">". $profileuser->user_login. " (".$profileuser->user_email.")</option>\n";
		}
		}
		?>
		</select>
	</li>
	</ol>

	<input type='submit' class='button' value='Save Settings and Create Invoice' />
	</form>
	<?php  if(web_invoice_is_not_merchant()) web_invoice_cc_setup(false); ?>

<?php
}

function web_invoice_show_settings()
{
global $wpdb;


if(isset($_POST['web_invoice_billing_meta'])) {
	$web_invoice_billing_meta = explode('
	',$_POST['web_invoice_billing_meta']);
	$web_invoice_billing_meta = web_invoice_fix_billing_meta_array($web_invoice_billing_meta);
	update_option('web_invoice_billing_meta', urlencode(serialize($web_invoice_billing_meta)));
}

if(get_option('web_invoice_billing_meta') != '') $web_invoice_billing_meta = unserialize(urldecode(get_option('web_invoice_billing_meta')));



if(!$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('meta')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".Web_Invoice::tablename('log')."';")) { $warning_message = "The plugin database tables are gone, deactivate and reactivate plugin to re-create them."; }if($warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>";




?>
<h2>Invoice Settings</h2>
<form method="POST">
<iframe src="https://secure.mohanjith.com/wp/web-invoice.php" style="float: right; width: 187px; height: 220px;"></iframe>
<table class="form-table" id="settings_page_table" style="clear: none;">

<tr>
	<th><a class="web_invoice_tooltip"  title="Select the page where your invoices will be displayed. Clients must follow their secured link, simply opening the page will not show any invoices.">Page to Display Invoices</a>:</th>
	<td>
	<select name='web_invoice_web_invoice_page'>
	<option></option>
	<?php $list_pages = $wpdb->get_results("SELECT ID, post_title, post_name, guid FROM ". $wpdb->prefix ."posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_title");
	$web_invoice_web_invoice_page = get_option('web_invoice_web_invoice_page');
	foreach ($list_pages as $page)
	{
	echo "<option  style='padding-right: 10px;'";
	if(isset($web_invoice_web_invoice_page) && $web_invoice_web_invoice_page == $page->ID) echo " SELECTED ";
	echo " value=\"".$page->ID."\">". $page->post_title . "</option>\n";
	}
	echo "</select>";?>
	</td>
</tr>

<tr>
	<th><a class="web_invoice_tooltip"  title="If your website has an SSL certificate and you want to use it, the link to the invoice will be formatted for https.">Protocol to Use for Invoice URLs</a>:</th>
	<td>
	<select  name="web_invoice_protocol">
	<option></option>
	<option style="padding-right: 10px;"<?php if(get_option('web_invoice_protocol') == 'https') echo 'selected="yes"';?>>https</option>
	<option style="padding-right: 10px;"<?php if(get_option('web_invoice_protocol') == 'http') echo 'selected="yes"';?>>http</option>
	</select>
	</td>
</tr>
<tr>
	<th> <a class="web_invoice_tooltip"  title="If enforced, WordPress will automatically reload the invoice page into HTTPS mode even if the user attemps to open it in non-secure mode.">Enforce HTTPS</a>:</th>
	<td>
	<select  name="web_invoice_force_https">
	<option></option>
	<option value="true" style="padding-right: 10px;"<?php if(get_option('web_invoice_force_https') == 'true') echo 'selected="yes"';?>>Yes</option>
	<option value="false" style="padding-right: 10px;"<?php if(get_option('web_invoice_force_https') == 'false') echo 'selected="yes"';?>>No</option>
	</select> <a href="http://keti.ws/36281" alt="dynadot.com" class="web_invoice_click_me">Do you need an SSL Certificate?</a>
	</td>
</tr>

<tr>
	<th width="200">Business Name:</th>
	<td>
	<input name="web_invoice_business_name" type="text" class="input_field" value="<?php echo stripslashes(get_option('web_invoice_business_name')); ?>" />
	</td>
</tr>
<tr>
	<th width="200"><a class="web_invoice_tooltip"  title="This will display on the invoice page when printed for clients' records.">Business Address</a>:</th>
	<td>
	<textarea name="web_invoice_business_address" ><?php echo stripslashes(get_option('web_invoice_business_address')); ?></textarea>
	</td>
</tr>

<tr>
	<th width="200">Business Phone</th>
	<td>
	<input name="web_invoice_business_phone" type="text"  class="input_field" value="<?php echo stripslashes(get_option('web_invoice_business_phone')); ?>" />
	</td>
</tr>

<tr>
	<th><a class="web_invoice_tooltip"  title="Address used to send out e-mail to client with web invoice link.">Return eMail Address</a>:</th>
	<td>
	<input name="web_invoice_email_address" class="input_field" type="text" value="<?php echo stripslashes(get_option('web_invoice_email_address')); ?>" />
	</td>
</tr>

<tr>
	<th><a class="web_invoice_tooltip"  title="An email will be sent automatically to client thanking them for their payment.">Send Payment Confirmation</a>:</th>
	<td>
	<select name="web_invoice_send_thank_you_email">
	<option></option>
	<option style="padding-right: 10px;"<?php if(get_option('web_invoice_send_thank_you_email') == 'yes') echo 'selected="yes"';?>>yes</option>
	<option style="padding-right: 10px;"<?php if(get_option('web_invoice_send_thank_you_email') == 'no') echo 'selected="yes"';?>>no</option>
	</select>
	</td>
</tr>

<tr>
	<th><a class="web_invoice_tooltip"  title="Send a copy of email sent to client thanking them to you.">CC Payment Confirmation</a>:</th>
	<td>
	<select name="web_invoice_cc_thank_you_email">
	<option></option>
	<option style="padding-right: 10px;"<?php if(get_option('web_invoice_cc_thank_you_email') == 'yes') echo 'selected="yes"';?>>yes</option>
	<option style="padding-right: 10px;"<?php if(get_option('web_invoice_cc_thank_you_email') == 'no') echo 'selected="yes"';?>>no</option>
	</select>
	</td>
</tr>

<tr>
	<th>Minimum User Level to Manage web-invoice</a>:</th>
	<td>
	<?php echo web_invoice_draw_select('web_invoice_user_level',array("level_0" => "Subscriber","level_0" => "Contributor","level_2" => "Author","level_5" => "Editor","level_8" => "Administrator"), get_option('web_invoice_user_level')); ?>
	</td>
</tr>

<tr>
<td colspan="2"><h2>Invoice Page Display Settings</h2></td>
</tr>
<tr>
	<th><a class="web_invoice_tooltip"  title="Disable this if you want to use your own stylesheet.">Use CSS</a>:</th>
	<td>
	<select name="web_invoice_use_css">
	<option></option>
	<option style="padding-right: 10px;"<?php if(get_option('web_invoice_use_css') == 'yes') echo 'selected="yes"';?>>yes</option>
	<option style="padding-right: 10px;"<?php if(get_option('web_invoice_use_css') == 'no') echo 'selected="yes"';?>>no</option>
	</select>
	</td>
</tr>




<tr>
	<th><a class="web_invoice_tooltip"  title="Show your business name and address on invoice.">Show Address on Invoice</a>:</th>
	<td>
	<select name="web_invoice_show_business_address">
	<option></option>
	<option style="padding-right: 10px;"<?php if(get_option('web_invoice_show_business_address') == 'yes') echo 'selected="yes"';?>>yes</option>
	<option style="padding-right: 10px;"<?php if(get_option('web_invoice_show_business_address') == 'no') echo 'selected="yes"';?>>no</option>
	</select>
	</td>
</tr>


<tr>
	<th width="200"><a class="web_invoice_tooltip"  title="Show quantity breakdowns in the itemized list on the front-end.">Quantities on Front End</a></th><td>
	<select  name="web_invoice_show_quantities">
		<option  <?php if(get_option('web_invoice_show_quantities') == 'Show') echo 'selected="yes"';?>>Show</option>
		<option <?php if(get_option('web_invoice_show_quantities') == 'Hide') echo 'selected="yes"';?>>Hide</option>
	</select>
	</td>
</tr>

<tr>
<td colspan="2"><h2>Payment Settings</h2></td>
</tr>

<tr>
	<th>Default Currency:</th>
	<td>
	<?php echo web_invoice_draw_select('web_invoice_default_currency_code',web_invoice_currency_array(),get_option('web_invoice_default_currency_code')); ?>
	</td>
</tr>

<tr>
	<th><a class="web_invoice_tooltip"  title="Special proxy must be used to process credit card transactions on GoDaddy servers.">Using Godaddy Hosting</a></th>
	<td>
	<?php echo web_invoice_draw_select('web_invoice_using_godaddy',array("yes" => "Yes","no" => "No"),get_option('web_invoice_using_godaddy')); ?>
	</td>
</tr>

<tr>
	<th>Payment Method:</th>
	<td>
	<select id="web_invoice_payment_method" name="web_invoice_payment_method[]" multiple="multiple" size="3">
	<option value="paypal" style="padding-right: 10px;"<?php if(stristr(get_option('web_invoice_payment_method'), 'paypal')) echo 'selected="yes"';?>>PayPal</option>
	<option value="moneybookers" style="padding-right: 10px;"<?php if(stristr(get_option('web_invoice_payment_method'), 'moneybookers')) echo 'selected="yes"';?>>Moneybookers</option>
	<option value="alertpay" style="padding-right: 10px;"<?php if(stristr(get_option('web_invoice_payment_method'), 'alertpay')) echo 'selected="yes"';?>>AlertPay</option>
	<option value="cc" style="padding-right: 10px;"<?php if(stristr(get_option('web_invoice_payment_method'), 'cc')) echo 'selected="yes"';?>>Credit Card</option>
	</select>
	</td>
</tr>

<tr class="paypal_info">
	<th width="200">PayPal Username</th>
	<td><input id='web_invoice_paypal_address' name="web_invoice_paypal_address" class="input_field"  type="text" value="<?php echo stripslashes(get_option('web_invoice_paypal_address')); ?>" />
	</td>
</tr>

<tr class="moneybookers_info">
	<th width="200">Moneybookers Username</th>
	<td>
		<input id='web_invoice_moneybookers_address' name="web_invoice_moneybookers_address" class="input_field"  type="text" value="<?php echo stripslashes(get_option('web_invoice_moneybookers_address')); ?>" />
		<a id="web_invoice_moneybookers_register_link" href="http://keti.ws/27481" alt="moneybookers.com" class="web_invoice_click_me">Do you need a Moneybookers account?</a>
	</td>
</tr>

<tr class="moneybookers_info">
	<th width="200">Enable Moneybookers payment notifications:</th>
	<td><select id='web_invoice_moneybookers_merchant' name="web_invoice_moneybookers_merchant">
			<option value="True" <?php echo (get_option('web_invoice_moneybookers_merchant')=='False')?'selected="selected"':''; ?> >yes</option>
			<option value="False" <?php echo (get_option('web_invoice_moneybookers_merchant')=='True')?'':'selected="selected"'; ?> >no</option>
		</select>
	</td>
</tr>
<tr class="moneybookers_info moneybookers_info_merchant">
	<th>Moneybookers payment notification secret:</th>
	<td><input id='web_invoice_moneybookers_secret' name="web_invoice_moneybookers_secret" class="search-input input_field"  type="text" value="<?php echo stripslashes(get_option('web_invoice_moneybookers_secret')); ?>" /></td>
</tr>
<tr class="moneybookers_info moneybookers_info_merchant">
	<th>Moneybookers payment notification IP:</th>
	<td><input id='web_invoice_moneybookers_ip' name="web_invoice_moneybookers_ip" class="search-input input_field"  type="text" value="<?php echo stripslashes(get_option('web_invoice_moneybookers_ip')); ?>" /></td>
</tr>

<tr class="alertpay_info">
	<th>Your AlertPay username:</th>
	<td>
		<input id='web_invoice_alertpay_address' name="web_invoice_alertpay_address" class="search-input input_field"  type="text" value="<?php echo stripslashes(get_option('web_invoice_alertpay_address')); ?>" />
		<a id="web_invoice_alertpay_register_link" href="http://keti.ws/36283" alt="alertpay.com" class="web_invoice_click_me">Do you need an AlertPay account?</a>
	</td>
</tr>
<tr class="alertpay_info">
	<th>Enable AlertPay IPN:</th>
	<td><select id='web_invoice_alertpay_merchant' name="web_invoice_alertpay_merchant">
			<option value="True" <?php echo (get_option('web_invoice_alertpay_merchant')=='False')?'selected="selected"':''; ?> >yes</option>
			<option value="False" <?php echo (get_option('web_invoice_alertpay_merchant')=='True')?'':'selected="selected"'; ?> >no</option>
		</select>
		<span class="web_invoice_alertpay_url web_invoice_info">Your alert URL is
			<a title="Copy this link" href="<?php echo web_invoice_get_alertpay_api_url(); ?>"><?php echo web_invoice_get_alertpay_api_url(); ?></a>.<br/>
			Please note that AlertPay has issues with some SSL certificates. (Your milage may vary).
		</span>
	</td>
<tr class="alertpay_info alertpay_info_merchant">
	<th>AlertPay IPN security code:</th
	<td><input id='web_invoice_alertpay_secret' name="web_invoice_alertpay_secret" class="search-input input_field"  type="text" value="<?php echo stripslashes(get_option('web_invoice_alertpay_secret')); ?>" /></td>
</tr>
<tr class="alertpay_info alertpay_info_merchant">
	<th>Test / Live Mode:</th>
	<td>
	<select name="web_invoice_alertpay_test_mode">
	<option value="TRUE" style="padding-right: 10px;"<?php if(get_option('web_invoice_alertpay_test_mode') == 'TRUE') echo 'selected="yes"';?>>Test - Do Not Process Transactions</option>
	<option value="FALSE" style="padding-right: 10px;"<?php if(get_option('web_invoice_alertpay_test_mode') == 'FALSE') echo 'selected="yes"';?>>Live - Process Transactions</option>
	</select>
	</td>
</tr>

<tr>
	<th colspan="2">
	<?php web_invoice_cc_setup(false); ?>
	</td>
</tr>

<tr class="gateway_info">
	<th width="200"><a class="web_invoice_tooltip"  title="Your credit card processor will provide you with a gateway username.">Gateway Username</a></th>
	<td>
	<input AUTOCOMPLETE="off" name="web_invoice_gateway_username" class="input_field" type="text" value="<?php echo stripslashes(get_option('web_invoice_gateway_username')); ?>" />
	</td>
</tr>

<tr class="gateway_info">
	<th width="200"><a class="web_invoice_tooltip"  title="You will be able to generate this in your credit card processor's control panel.">Gateway Transaction Key</a></th>
	<td>
	<input AUTOCOMPLETE="off" name="web_invoice_gateway_tran_key" class="input_field" type="text" value="<?php echo stripslashes(get_option('web_invoice_gateway_tran_key')); ?>" />
	</td>
</tr>


<tr class="gateway_info">
	<th width="200"><a class="web_invoice_tooltip"  title="This is the URL provided to you by your credit card processing company.">Gateway URL</a></th>
	<td>
	<input name="web_invoice_gateway_url" id="web_invoice_gateway_url" class="input_field" type="text" value="<?php echo stripslashes(get_option('web_invoice_gateway_url')); ?>" />
	<br /><span class="web_invoice_click_me" onclick="jQuery('#web_invoice_gateway_url').val('https://gateway.merchantplus.com/cgi-bin/PAWebClient.cgi');">MerchantPlus</span> |
	<span class="web_invoice_click_me" onclick="jQuery('#web_invoice_gateway_url').val('https://secure.authorize.net/gateway/transact.dll');">Authorize.Net</span> |
	<span class="web_invoice_click_me" onclick="jQuery('#web_invoice_gateway_url').val('https://test.authorize.net/gateway/transact.dll');">Authorize.Net Developer</span>
	</td>
</tr>

<tr class="gateway_info">
	<th width="200"><a class="web_invoice_tooltip"  title="Recurring billing gateway URL is most likely different from the Gateway URL, and will almost always be with Authorize.net. Be advised - test credit card numbers will be declined even when in test mode.">Recurring Billing Gateway URL</a></th>
	<td>
	<input name="web_invoice_recurring_gateway_url" id="web_invoice_recurring_gateway_url" class="input_field" type="text" value="<?php echo stripslashes(get_option('web_invoice_recurring_gateway_url')); ?>" />
	<br /><span class="web_invoice_click_me" onclick="jQuery('#web_invoice_recurring_gateway_url').val('https://api.authorize.net/xml/v1/request.api');">Authorize.net ARB</span> |
	<span class="web_invoice_click_me" onclick="jQuery('#web_invoice_recurring_gateway_url').val('https://apitest.authorize.net/xml/v1/request.api');">Authorize.Net ARB Testing</span>
	</td>
</tr>

<tr class="gateway_info">
	<th>Test / Live Mode:</th>
	<td>
	<select name="web_invoice_gateway_test_mode">
	<option value="TRUE" style="padding-right: 10px;"<?php if(get_option('web_invoice_gateway_test_mode') == 'TRUE') echo 'selected="yes"';?>>Test - Do Not Process Transactions</option>
	<option value="FALSE" style="padding-right: 10px;"<?php if(get_option('web_invoice_gateway_test_mode') == 'FALSE') echo 'selected="yes"';?>>Live - Process Transactions</option>
	</select>
	</td>
</tr>

<tr  class="gateway_info">
<td colspan="2"><h2>Advanced Gateway Settings</h2></td>
</tr>

<tr class="gateway_info">
	<th width="200"><a class="web_invoice_tooltip"  title="Get this from your credit card processor. If the transactions are not going through, this character is most likely wrong.">Delimiter Character</a></th>
	<td>
	<input name="web_invoice_gateway_delim_char" class="input_field" type="text" value="<?php echo stripslashes(get_option('web_invoice_gateway_delim_char')); ?>" />
	</td>
</tr>

<tr class="gateway_info">
	<th width="200"><a class="web_invoice_tooltip"  title="Authorize.net default is blank.  Otherwise, get this from your credit card processor. If the transactions are going through, but getting strange responses, this character is most likely wrong.">Encapsulation Character</a></th>
	<td>
	<input name="web_invoice_gateway_encap_char" class="input_field" type="text" value="<?php echo stripslashes(get_option('web_invoice_gateway_encap_char')); ?>" />
	</td>
</tr>

<tr class="gateway_info">
	<th width="200">Merchant Email</th>
	<td>
	<input name="web_invoice_gateway_merchant_email" class="input_field" type="text" value="<?php echo stripslashes(get_option('web_invoice_gateway_merchant_email')); ?>" />
	</td>
</tr>



<tr class="gateway_info">
	<th>Email Customer (on success):</th>
	<td>
	<select name="web_invoice_gateway_email_customer">
	<option value="TRUE" style="padding-right: 10px;"<?php if(get_option('web_invoice_gateway_email_customer') == 'TRUE') echo 'selected="yes"';?>>True</option>
	<option value="FALSE" style="padding-right: 10px;"<?php if(get_option('web_invoice_gateway_email_customer') == 'FALSE') echo 'selected="yes"';?>>False</option>
	</select>
	</td>
</tr>

<tr class="gateway_info">
	<th width="200">Customer Receipt Email Header</th>
	<td>
	<input name="web_invoice_gateway_header_email_receipt" class="input_field" type="text" value="<?php echo stripslashes(get_option('web_invoice_gateway_header_email_receipt')); ?>" />
	</td>
</tr>


<tr class="gateway_info">
	<th width="200">Security: MD5 Hash</th>
	<td>
	<input name="web_invoice_gateway_MD5Hash" class="input_field" type="text" value="<?php echo stripslashes(get_option('web_invoice_gateway_MD5Hash')); ?>" />
	</td>
</tr>

<tr class="gateway_info">
	<th>Delim Data:</th>
	<td>
	<select name="web_invoice_gateway_delim_data">
	<option value="TRUE" style="padding-right: 10px;"<?php if(get_option('web_invoice_gateway_delim_data') == 'TRUE') echo 'selected="yes"';?>>True</option>
	<option value="FALSE" style="padding-right: 10px;"<?php if(get_option('web_invoice_gateway_delim_data') == 'FALSE') echo 'selected="yes"';?>>False</option>
	</select>
	</td>
</tr>

<tr>
	<td></td>
	<td><input type="submit" value="update" class="button" />
	</td>
</tr>
</table>

<table class="form-table" >

<td colspan="2"><a id="delete_all_web_invoice_databases" href="admin.php?page=new_web_invoice&web_invoice_action=complete_removal">Remove All Web Invoice Databases</a> - Only do this if you want to completely remove the plugin.  All invoices and logs will be gone... forever.</td>
</table>
</form>
</div>
<?php
}



function web_invoice_cc_setup($show_title = TRUE) {
if($show_title) { ?> 	<div id="web_invoice_need_mm" style="border-top: 1px solid #DFDFDF; ">Do you need to accept credit cards?</div> <?php } ?>
	<?php
}

function web_invoice_dashboard() {
	// Daten lesen von Funktion fs_getfeeds()
	$content ="helo";
	echo $content;
}


function web_invoice_show_invoice($invoice_id) {
	$invoice_info = new Web_Invoice_GetInfo($invoice_id);

	echo "<div class=\"subject\">Subject: <strong>" . $invoice_info->display('subject'). "</strong></div>";
	echo "<div class=\"main_content\">";
	echo str_replace("\n", "<br />", web_invoice_show_email($invoice_id));
	echo "</div>";
}

function web_invoice_show_email($invoice_id) {
	$invoice_info = new Web_Invoice_GetInfo($invoice_id);
	$recipient = new Web_Invoice_GetInfo($invoice_id);

	// Determine currency. First we check invoice-specific, then default code, and then we settle on USD
//	$currency_code = web_invoice_determine_currency($invoice_id);


	$message = "Dear ". $recipient->recipient('callsign') . ", \n\n";
 	$message .= stripslashes(get_option("web_invoice_business_name")) . " has sent you a ";
	$message .= (web_invoice_recurring($invoice_id) ? " recurring " : " ");
	$message .= "web invoice in the amount of ".  $invoice_info->display('display_amount') . ".\n\n";

	if($invoice_info->display('description')) $message .= $invoice_info->display('description') . "\n\n";

	$message .= "You may pay, view and print the invoice online by visiting the following link: \n";
	$message .= $invoice_info->display('link') . "\n\n";
	$message .= "Best regards,\n";
	$message .= stripslashes(get_option("web_invoice_business_name")) . "(" .  get_option("web_invoice_email_address")  . ")";

	return $message;
}

function web_invoice_show_receipt_email($invoice_id) {
	$invoice_info = new Web_Invoice_GetInfo($invoice_id);
	$recipient = new Web_Invoice_GetInfo($invoice_id);

	// Determine currency. First we check invoice-specific, then default code, and then we settle on USD
	// $currency_code = web_invoice_determine_currency($invoice_id);

	$message = "Dear ". $recipient->recipient('callsign') . ", \n\n";
 	$message .= stripslashes(get_option("web_invoice_business_name")) . " has sent you a receipt for the ";
	$message .= (web_invoice_recurring($invoice_id) ? " recurring " : " ");
	$message .= "web invoice in the amount of ".  $invoice_info->display('display_amount') . ".\n\n";
	$message .= "Thank you very much for your patronage.\n\n";

	$message .= "Best regards,\n";
	$message .= stripslashes(get_option("web_invoice_business_name")) . "(" .  get_option("web_invoice_email_address")  . ")";

	return $message;
}


function web_invoice_draw_itemized_table($invoice_id) {
	global $wpdb;

	$_invoice = new Web_Invoice_GetInfo($invoice_id);
	$invoice_info = $_invoice->_row_cache;
	$itemized = $invoice_info->itemized;
	$amount = $invoice_info->amount;
	$tax_percent = web_invoice_meta($invoice_id,'tax_value');

	// Determine currency. First we check invoice-specific, then default code, and then we settle on USD
	$currency_code = web_invoice_determine_currency($invoice_id);

	if($tax_percent) {
		$tax_free_amount = $amount*(100/(100+(100*($tax_percent/100))));
		$tax_value = $amount - $tax_free_amount;
		}


	if(!strpos($amount,'.')) $amount = $amount . ".00";
	$itemized_array = unserialize(urldecode($itemized));


	if(is_array($itemized_array)) {
		$response .= "<table id=\"web_invoice_itemized_table\">
		<tr>\n";
		if(get_option('web_invoice_show_quantities') == "Show") { $response .= '<th style="width: 40px; text-align: right;">Quantity</th>'; }
		$response .="<th>Item</th><th style=\"width: 70px; text-align: right;\">Cost</th>
		</tr> ";
		$i = 1;
		foreach($itemized_array as $itemized_item){
		//Show Quantites or not
		if(get_option('web_invoice_show_quantities') == '') $show_quantity = false;
		if(get_option('web_invoice_show_quantities') == 'Hide') $show_quantity = false;
		if(get_option('web_invoice_show_quantities') == 'Show') $show_quantity = true;


		if(!empty($itemized_item[name])) {
		if(!strpos($itemized_item[price],'.')) $itemized_item[price] = $itemized_item[price] . ".00";

		if($i % 2) { $response .= "<tr>"; }
		else { $response .= "<tr  class='alt_row'>"; }

		//Quantities
		if($show_quantity) {
		$response .= "<td style=\"width: 70px; text-align: right;\">" . $itemized_item[quantity] . "</td>";	}

		//Item Name
		$response .= "<td>" . stripslashes($itemized_item[name]) . " <br /><span class='description_text'>" . stripslashes($itemized_item[description]) . "</span></td>";

		//Item Price
		if(!$show_quantity) {
		 $response .= "<td style=\"width: 70px; text-align: right;\">" . web_invoice_currency_symbol($currency_code) .  web_invoice_currency_format($itemized_item[quantity] * $itemized_item[price]) . "</td>";
		 } else {
		 $response .= "<td style=\"width: 70px; text-align: right;\">". web_invoice_currency_symbol($currency_code) . web_invoice_currency_format($itemized_item[price]) . "</td>";
		 }

		$response .="</tr>";
		$i++;
		}

		}
		if($tax_percent) {
		$response .= "<tr>";
		if(get_option('web_invoice_show_quantities') == "Show") { $response .= "<td></td>"; }
		$response .= "<td>Tax (". round($tax_percent,2). "%) </td><td style='text-align:right;' colspan='2'>" . web_invoice_currency_symbol($currency_code) . web_invoice_currency_format($tax_value)."</td></tr>";
		}

		$response .="
		<tr class=\"web_invoice_bottom_line\">
		<td align=\"right\">Invoice Total:</td>
		<td  colspan=\"2\" style=\"text-align: right;\" class=\"grand_total\">";

		$response .= web_invoice_currency_symbol($currency_code) . web_invoice_currency_format($amount);
		$response .= "</td></table>";

		return $response;
	}

}


function web_invoice_draw_itemized_table_plaintext($invoice_id) {
	global $wpdb;
	$invoice_info = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '".$invoice_id."'");
	$itemized = $invoice_info->itemized;
	$amount = $invoice_info->amount;
	if(!strpos($amount,'.')) $amount = $amount . ".00";

	$itemized_array = unserialize(urldecode($itemized));

	if(is_array($itemized_array)) {


		foreach($itemized_array as $itemized_item){
			if(!empty($itemized_item[name])) {
			$item_cost = $itemized_item[price] * $itemized_item[quantity];
			if(!strpos($item_cost,'.')) $item_cost = $item_cost . ".00";

		$response .= " $" . $item_cost . " \t - \t " . stripslashes($itemized_item[name]) . "\n";

		}
		}

		return $response;
	}

}



function web_invoice_user_profile_fields()
{
	global $wpdb;
	$user_id =  $_REQUEST['user_id'];

	$profileuser = get_user_to_edit($user_id);
	?>

	<h3>Billing / Invoicing Info</h3>
	<a name="billing_info"></a>
	<table class="form-table" >

	<tr>
	<th><label for="streetaddress">Street Address</label></th>
	<td><input type="text" name="streetaddress" id="streetaddress" value="<?php echo get_usermeta($user_id,'streetaddress'); ?>" /></td>
	</tr>

	<tr>
	<th><label for="city">City</label></th>
	<td><input type="text" name="city" id="city" value="<?php echo get_usermeta($user_id,'city'); ?>" /></td>
	</tr>

	<tr>
	<th><label for="state">State</label></th>
	<td><input type="text" name="state" id="state" value="<?php echo get_usermeta($user_id,'state'); ?>" /><br />
	<p class="note">Use two-letter state codes for safe credit card processing.</p></td>
	</tr>

	<tr>
	<th><label for="streetaddress">ZIP Code</label></th>
	<td><input type="text" name="zip" id="zip" value="<?php echo get_usermeta($user_id,'zip'); ?>" /></td>
	</tr>

	<tr>
	<th><label for="phonenumber">Phone Number</label></th>
	<td><input type="text" name="phonenumber" id="phonenumber" value="<?php echo get_usermeta($user_id,'phonenumber'); ?>" />
	<p class="note">Enforce 555-555-5555 format if you are using PayPal.</p></td>
	</tr>

	<tr>
	<th><label for="country">Country</label></th>
	<td><input type="text" name="country" id="country" value="<?php echo get_usermeta($user_id,'country'); ?>" />
	<p class="note">Two letter country code, like US.</p></td>
	</tr>

	<tr>
	<th></th>
	<td>

	<input type='button' onclick="window.location='admin.php?page=new_web_invoice&user_id=<?PHP echo $_REQUEST['user_id']; ?>';" class='button' value='Create New Invoice For This User' />

	</td>
	</tr>


</table>
<?php
}

function web_invoice_show_paypal_receipt($invoice_id) {

	$invoice = new Web_Invoice_GetInfo($invoice_id);

	if(isset($_POST['first_name'])) update_usermeta($invoice->recipient('user_id'), 'first_name', $_POST['first_name']);
	if(isset($_POST['last_name'])) update_usermeta($invoice->recipient('user_id'), 'last_name', $_POST['last_name']);

	if(get_option('web_invoice_send_thank_you_email') == 'yes') web_invoice_send_email_receipt($invoice_id);

	web_invoice_paid($invoice_id);
	web_invoice_update_log($invoice_id,'paid',"PayPal Receipt: (" . $_REQUEST['receipt_id']. ")");
	if(isset($_REQUEST['payer_email'])) web_invoice_update_log($invoice_id,'paid',"PayPal payee user email: (" . $_REQUEST['payer_email']. ")");


	return '<div id="invoice_page" class="clearfix">
	<div id="invoice_overview" class="cleafix">
	<h2 class="invoice_page_subheading">'.$invoice->recipient("callsign"). ', thank you for your payment!</h2>
	<p><strong>Invoice ' . $invoice->display("display_id") . ' has been paid.</strong></p>
	</div>
	</div>';
}

function web_invoice_show_already_paid($invoice_id) {
	$invoice = new Web_Invoice_GetInfo($invoice_id);
	return '<p>This invoice was paid on '. $invoice->display('paid_date').'.</p>';
}

function web_invoice_show_invoice_overview($invoice_id) {
$invoice = new Web_Invoice_GetInfo($invoice_id);
?>
<div id="invoice_overview" class="clearfix">
	<h2 id="web_invoice_welcome_message" class="invoice_page_subheading">Welcome, <?php echo $invoice->recipient('callsign'); ?>!</h2>
	<p class="web_invoice_main_description">We have sent you invoice <b><?php echo $invoice->display('display_id'); ?></b> with a total amount of <?php echo $invoice->display('display_amount'); ?>.</p>
	<?php if($invoice->display('due_date')) { ?> <p class="web_invoice_due_date">Due Date: <?php echo $invoice->display('due_date'); } ?>
	<?php if($invoice->display('description')) { ?><p><?php echo $invoice->display('description');  ?></p><?php  } ?>
	<?php echo web_invoice_draw_itemized_table($invoice_id); ?>
</div>
<?php
}

function web_invoice_show_business_address() {
?>
<div id="invoice_business_info" class="clearfix">
	<h2 class="invoice_page_subheading">Bill From:</h2>
	<p class="web_invoice_business_name"><?php echo get_option('web_invoice_business_name'); ?></p>
	<p class="web_invoice_business_address"><?php echo nl2br(get_option('web_invoice_business_address')); ?></p>
</div>

<?php
}


function web_invoice_show_billing_information($invoice_id) {
$invoice = new Web_Invoice_GetInfo($invoice_id);
$Web_Invoice = new Web_Invoice();
$pp = false; $cc = false; $mb = false; $alertpay = false;

if(stristr(get_option('web_invoice_payment_method'),'paypal')) { $pp = true; }
if(stristr(get_option('web_invoice_payment_method'), 'moneybookers')) { $mb = true; }
if(stristr(get_option('web_invoice_payment_method'), 'alertpay')) { $alertpay = true; }
if(stristr(get_option('web_invoice_payment_method'), 'cc')) { $cc = true;}

?>

<div id="billing_overview" class="clearfix noprint">
	<div id="payment_methods">
		Pay with: <br/>
		    <?php if ($cc) { ?>
		    <a href="#cc_payment_form" title="Visa Master American Express"><img src="<?php echo Web_Invoice::frontend_path(); ?>/images/cc_logo.png" alt="Visa Master American Express" width="265" height="45" /></a>
		    <?php } ?>
		    <?php if ($alertpay) { ?>
		    <a href="#alertpay_payment_form" title="AlertPay"><img src="<?php echo Web_Invoice::frontend_path(); ?>/images/alertpay_logo.png" alt="AlertPay" width="81" height="45" /></a>
		    <?php } ?>
		    <?php if ($mb) { ?>
		    <a href="#moneybookers_payment_form" title="Moneybookers"><img src="<?php echo Web_Invoice::frontend_path(); ?>/images/moneybookers_logo.png" alt="Moneybookers" width="75" height="42" /></a>
		    <?php } ?>
		    <?php if ($pp) { ?>
		    <a href="#paypal_payment_form" title="PayPal"><img src="<?php echo Web_Invoice::frontend_path(); ?>/images/paypal_logo.png" alt="PayPal" width="80" height="45" /></a>
		    <?php } ?>
		<p></p>
	</div>
<?php

if ($alertpay) web_invoice_show_alertpay_form($invoice_id, $invoice);
if ($cc) web_invoice_show_cc_form($invoice_id, $invoice);
if ($mb) web_invoice_show_moneybookers_form($invoice_id, $invoice);
if ($pp) web_invoice_show_paypal_form($invoice_id, $invoice);

?>
</div>
<?php
}

function web_invoice_show_alertpay_form($invoice_id, $invoice) {
?>
<div id="alertpay_payment_form" class="payment_form">
<form action="https://www.alertpay.com/PayProcess.aspx" method="post" class="clearfix" target="_moneybookers">
	<input type="hidden" name="ap_currency" value="<?php echo $invoice->display('currency'); ?>" />
	<input type="hidden" name="ap_purchasetype" value="Service">
	<input type="hidden" name="ap_merchant" value="<?php echo get_option('web_invoice_alertpay_address'); ?>" />
	<input type="hidden" name="ap_totalamount" value="<?php echo $invoice->display('amount'); ?>" />
	<input type="hidden" name="ap_itemname" id="invoice_num" value="<?php echo  $invoice->display('display_id'); ?>" />
	<input type="hidden" name="ap_returnurl" value="<?php echo web_invoice_build_invoice_link($invoice_id); ?>" />
	<?php
	// Convert Itemized List into AlertPay Item List (Not supported, we just show an aggregated fields)
	if(is_array($invoice->display('itemized'))) echo web_invoice_create_alertpay_itemized_list($invoice->display('itemized'),$invoice_id);
	?>
	<fieldset id="credit_card_information">
	<ol>
		<li>
		<label for="submit">&nbsp;</label>
		<input type="image" src="https://www.alertpay.com//PayNow/4FF7280888FE4FD4AE1B4A286ED9B8D5a.gif" style="border:0; width:170px; height:70px; padding:0;" name="submit" alt="Pay now with AlertPay" />
		</li>
	</ol>
	</fieldset>
</form>
</div>
<?php
}

function web_invoice_show_moneybookers_form($invoice_id, $invoice) {
?>
<div id="moneybookers_payment_form" class="payment_form">
<h2 class="invoice_page_subheading">Billing Information</h2>
<form action="https://www.moneybookers.com/app/payment.pl" method="post" class="clearfix" target="_moneybookers">
	<input type="hidden" name="currency" value="<?php echo $invoice->display('currency'); ?>" />
	<input type="hidden" name="no_shipping" value="1" />
	<input type="hidden" name="rid" value="5413099" />
	<input type="hidden" name="pay_to_email" value="<?php echo get_option('web_invoice_moneybookers_address'); ?>" />
	<input type="hidden" name="return_url" value="<?php echo web_invoice_build_invoice_link($invoice_id); ?>" />
	<input type="hidden" name="status_url" value="<?php echo web_invoice_build_invoice_link($invoice_id); ?>" />
	<input type="hidden" name="amount" value="<?php echo $invoice->display('amount'); ?>" />
	<input type="hidden" name="transaction_id" id="invoice_num" value="<?php echo  $invoice->display('display_id'); ?>" />
	<?php
	// Convert Itemized List into Moneybookers Item List
	if(is_array($invoice->display('itemized'))) echo web_invoice_create_moneybookers_itemized_list($invoice->display('itemized'),$invoice_id);
	?>
	<fieldset id="credit_card_information">
	<ol>
		<li>
		<label for="firstname">First Name</label>
		<?php echo web_invoice_draw_inputfield("firstname",$invoice->recipient('first_name')); ?>
		</li>

		<li>
		<label for="lastname">Last Name</label>
		<?php echo web_invoice_draw_inputfield("lastname",$invoice->recipient('last_name')); ?>
		</li>

		<li>
		<label for="pay_from_email">Email Address</label>
		<?php echo web_invoice_draw_inputfield("pay_from_email",$invoice->recipient('email_address')); ?>
		</li>

		<li>
		<label class="inputLabel" for="phone_number">Phone Number</label>
		<input name="phone_number" class="input_field"  type="text" id="phone_number" size="40" maxlength="50" value="<?php print $invoice->recipient('phonenumber'); ?>" />
		</li>

		<li>
		<label for="address">Address</label>
		<?php echo web_invoice_draw_inputfield("address",$invoice->recipient('streetaddress')); ?>
		</li>

		<li>
		<label for="city">City</label>
		<?php echo web_invoice_draw_inputfield("city",$invoice->recipient('city')); ?>
		</li>

		<li>
		<label for="state">State/Province</label>
		<?php print web_invoice_draw_select('state',web_invoice_state_array(),$invoice->recipient('state'));  ?>
		</li>

		<li>
		<label for="postal_code">Zip/Postal Code</label>
		<?php echo web_invoice_draw_inputfield("postal_code",$invoice->recipient('zip')); ?>
		</li>

		<li>
		<label for="country">Country</label>
		<?php echo web_invoice_draw_select('country',web_invoice_country_array(),$invoice->recipient('country')); ?>
		</li>

		<li>
		<label for="submit">&nbsp;</label>
		<input type="image" src="http://www.moneybookers.com/images/logos/checkout_logos/checkoutlogo_CCs_240x80.gif" style="border:0; width:240px; height:80px; padding:0;" name="submit" alt="Moneybookers.com and money moves" />
		</li>
	<br class="cb" />
	</ol>
</fieldset>
</form>
</div>
<?php
}

function web_invoice_show_paypal_form($invoice_id, $invoice) {
?>
<div id="paypal_payment_form" class="payment_form">
<h2 class="invoice_page_subheading">Billing Information</h2>
<form action="https://www.paypal.com/us/cgi-bin/webscr" method="post" class="clearfix">
	<input type="hidden" name="currency_code" value="<?php echo $invoice->display('currency'); ?>" />
	<input type="hidden" name="no_shipping" value="1">
	<input type="hidden" name="upload" value="1">
	<input type="hidden" name="business" value="<?php echo get_option('web_invoice_paypal_address'); ?>" />
	<input type="hidden" name="return" value="<?php echo web_invoice_build_invoice_link($invoice_id); ?>" />
	<input type="hidden" name="rm" value="2">
	<input type="hidden" name="amount"  value="<?php echo $invoice->display('amount'); ?>">
	<input  type="hidden" name="invoice" id="invoice_num"  value="<?php echo  $invoice->display('display_id'); ?>" />
	<?php
	// Convert Itemized List into PayPal Item List
	if(is_array($invoice->display('itemized'))) echo web_invoice_create_paypal_itemized_list($invoice->display('itemized'),$invoice_id);
	?>
	<fieldset id="credit_card_information">
	<ol>
		<li>
		<label for="first_name">First Name</label>
		<?php echo web_invoice_draw_inputfield("first_name",$invoice->recipient('first_name')); ?>
		</li>

		<li>
		<label for="last_name">Last Name</label>
		<?php echo web_invoice_draw_inputfield("last_name",$invoice->recipient('last_name')); ?>
		</li>

		<li>
		<label for="email">Email Address</label>
		<?php echo web_invoice_draw_inputfield("email_address",$invoice->recipient('email_address')); ?>
		</li>

	<?php
		list($day_phone_a, $day_phone_b, $day_phone_c) = split('[/.-]', $invoice->recipient('paypal_phonenumber'));
		?>
		<li>
		<label for="day_phone_a">Phone Number</label>
		<?php echo web_invoice_draw_inputfield("night_phone_a",$day_phone_a,' style="width:25px;" size="3" maxlength="3" '); ?>-
		<?php echo web_invoice_draw_inputfield("night_phone_b",$day_phone_b,' style="width:25px;" size="3" maxlength="3" '); ?>-
		<?php echo web_invoice_draw_inputfield("night_phone_c",$day_phone_c,' style="width:35px;" size="4" maxlength="4" '); ?>
		</li>

		<li>
		<label for="address">Address</label>
		<?php echo web_invoice_draw_inputfield("address",$invoice->recipient('streetaddress')); ?>
		</li>

		<li>
		<label for="city">City</label>
		<?php echo web_invoice_draw_inputfield("city",$invoice->recipient('city')); ?>
		</li>

		<li>
		<label for="state">State/Province</label>
		<?php print web_invoice_draw_select('state',web_invoice_state_array(),$invoice->recipient('state'));  ?>
		</li>

		<li>
		<label for="zip">Zip/Postal Code</label>
		<?php echo web_invoice_draw_inputfield("zip",$invoice->recipient('zip')); ?>
		</li>

		<li>
		<label for="country">Country</label>
		<?php echo web_invoice_draw_select('country',web_invoice_country_array(),$invoice->recipient('country')); ?>
		</li>

		<li>
		<label for="submit">&nbsp;</label>
		<input type="image"  src="http://www.paypal.com/en_US/i/btn/btn_paynow_LG.gif" style="border:0; width:107px; height:26px;padding:0;" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" />
		</li>

	<br class="cb" />
	</ol>
</form>
</div>
<?php
}

function web_invoice_show_cc_form($invoice_id, $invoice) {
?>
<div id="cc_payment_form" class="payment_form">
<h2 class="invoice_page_subheading">Billing Information</h2>
<form method="post" name="checkout_form" id="checkout_form" class="online_payment_form" onsubmit="process_cc_checkout(); return false;" class="clearfix">
	<input type="hidden" name="amount" value="<?php echo $invoice->display('amount'); ?>" />
	<input type="hidden" name="user_id" value="<?php echo $invoice->recipient('user_id'); ?>" />
	<input type="hidden" name="email_address" value="<?php echo $invoice->recipient('email_address'); ?>" />
	<input type="hidden" name="invoice_num" value="<?php echo  $invoice_id; ?>" />
	<input type="hidden" name="currency_code" id="currency_code"  value="<?php echo $invoice->display('currency'); ?>" />
	<input type="hidden" name="web_invoice_id_hash" value="<?php echo $invoice->display('hash'); ?>" />
	<fieldset id="credit_card_information">
	<ol>
		<li>
		<label for="first_name">First Name</label>
		<?php echo web_invoice_draw_inputfield("first_name",$invoice->recipient('first_name')); ?>
		</li>

		<li>
		<label for="last_name">Last Name</label>
		<?php echo web_invoice_draw_inputfield("last_name",$invoice->recipient('last_name')); ?>
		</li>

		<li>
		<label for="email">Email Address</label>
		<?php echo web_invoice_draw_inputfield("email_address",$invoice->recipient('email_address')); ?>
		</li>

		<li>
		<label class="inputLabel" for="phonenumber">Phone Number</label>
		<input name="phonenumber" class="input_field"  type="text" id="phonenumber" size="40" maxlength="50" value="<?php print $invoice->recipient('phonenumber'); ?>" />
		</li>

		<li>
		<label for="address">Address</label>
		<?php echo web_invoice_draw_inputfield("address",$invoice->recipient('streetaddress')); ?>
		</li>

		<li>
		<label for="city">City</label>
		<?php echo web_invoice_draw_inputfield("city",$invoice->recipient('city')); ?>
		</li>

		<li>
		<label for="state">State/Province</label>
		<?php print web_invoice_draw_select('state',web_invoice_state_array(),$invoice->recipient('state'));  ?>
		</li>

		<li>
		<label for="zip">Zip/Postal Code</label>
		<?php echo web_invoice_draw_inputfield("zip",$invoice->recipient('zip')); ?>
		</li>

		<li>
		<label for="country">Country</label>
		<?php echo web_invoice_draw_select('country',web_invoice_country_array(),$invoice->recipient('country')); ?>
		</li>

		<li class="hide_after_success">
		<label class="inputLabel" for="card_num">Credit Card Number</label>
		<input name="card_num" autocomplete="off" onkeyup="cc_card_pick();"  id="card_num" class="credit_card_number input_field"  type="text"  size="22"  maxlength="22" />
		</li>

		<li class="hide_after_success nocard"  id="cardimage" style=" background: url(<?php echo Web_Invoice::frontend_path(); ?>/images/card_array.png) no-repeat;">
		</li>

		<li class="hide_after_success">
		<label class="inputLabel" for="exp_month">Expiration Date</label>
		Month <select name="exp_month" id="exp_month"><?php print web_invoice_printMonthDropdown(); ?></select>
		Year <select name="exp_year" id="exp_year"><?php print web_invoice_printYearDropdown(); ?></select>
		</li>

		<li class="hide_after_success">
		<label class="inputLabel" for="card_code">Security Code</label>
		<input id="card_code" autocomplete="off"  name="card_code" class="input_field"  style="width: 70px;" type="text" size="4" maxlength="4" />
		</li>

		<li id="web_invoice_process_wait">
		<label for="submit"><span></span>&nbsp;</label>
		<button type="submit" id="cc_pay_button" class="hide_after_success submit_button">
		Pay <?php echo $invoice->display('display_amount'); ?></button>
		</li>

	<br class="cb" />
	</ol>
	&nbsp;<div id="wp_cc_response"></div>
</form>
</div>
<?php
}

function web_invoice_show_recurring_info($invoice_id) {
	$invoice = new Web_Invoice_GetInfo($invoice_id);
?>
<div id="recurring_info" class="clearfix">
	<?php if($invoice->display('due_date')) { ?> <p class="web_invoice_due_date">Due Date: <?php echo $invoice->display('due_date'); } ?>
	<h2 id="web_invoice_welcome_message" class="invoice_page_subheading">Welcome, <?php echo $invoice->recipient('callsign'); ?>!</h2>
	<?php if($invoice->display('description')) { ?><p><?php echo $invoice->display('description');  ?></p><?php  } ?>

	<p class="recurring_info_breakdown">This is a recurring bill, id: <b><?php echo $invoice->display('display_id'); ?></b>.</p>
	<p>You will be billed <?php echo $invoice->display('display_billing_rate'); ?> in the amount of <?php echo $invoice->display('display_amount');

	// Determine if startning now or t a set date
	if (web_invoice_meta($invoice_id,'web_invoice_subscription_start_day') != '' && web_invoice_meta($invoice_id,'web_invoice_subscription_start_month')  != '' && web_invoice_meta($invoice_id,'web_invoice_subscription_start_year'  != ''))
	echo web_invoice_meta($invoice_id,'web_invoice_subscription_start_day') .", ". web_invoice_meta($invoice_id,'web_invoice_subscription_start_month') .", ".  web_invoice_meta($invoice_id,'web_invoice_subscription_start_year');
	?>.</p>

	<?php echo web_invoice_draw_itemized_table($invoice_id); ?>

</div
<?php
}


function web_invoice_draw_user_selection_form($user_id) {
	global $wpdb; ?>

<div class="postbox" id="wp_new_web_invoice_div">
<div class="inside">
	<form action="admin.php?page=new_web_invoice" method='POST'>
		<table class="form-table" id="get_user_info">
			<tr class="invoice_main">
				<th><?php if(isset($user_id)) { ?>Start New Invoice For: <?php } else { ?>Create New Invoice For:<?php } ?></th>
				<td>

					<select name='user_id' class='user_selection'>
					<option></option>
					<?php
					$get_all_users = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix . "users LEFT JOIN ". $wpdb->prefix . "usermeta on ". $wpdb->prefix . "users.id=". $wpdb->prefix . "usermeta.user_id and ". $wpdb->prefix . "usermeta.meta_key='last_name' ORDER BY ". $wpdb->prefix . "usermeta.meta_value");
					foreach ($get_all_users as $user)
					{
					$profileuser = get_user_to_edit($user->ID);
					echo "<option ";
					if(isset($user_id) && $user_id == $user->ID) echo " SELECTED ";
					if(!empty($profileuser->last_name) && !empty($profileuser->first_name)) { echo " value=\"".$user->ID."\">". $profileuser->last_name. ", " . $profileuser->first_name . " (".$profileuser->user_email.")</option>\n";  }
					else
					{
					echo " value=\"".$user->ID."\">". $profileuser->user_login. " (".$profileuser->user_email.")</option>\n";
					}
					}
					?>
					</select>
					<input type='submit' class='button' id="web_invoice_create_new_web_invoice" value='Create New Invoice' />


					<?php if(web_invoice_number_of_invoices() > 0) { ?><span id="web_invoice_copy_invoice" class="web_invoice_click_me">copy from another</span>
					<br />


			<div class="web_invoice_copy_invoice">
			<?php 	$all_invoices = $wpdb->get_results("SELECT * FROM ".Web_Invoice::tablename('main')); ?>
			<select name="copy_from_template">
<option SELECTED value=""></option>
		<?php 	foreach ($all_invoices as $invoice) {
		$profileuser = get_user_to_edit($invoice->user_id);
		?>

		<option value="<?php echo $invoice->invoice_num; ?>"><?php if(web_invoice_recurring($invoice->invoice_num)) {?>(recurring)<?php } ?> <?php echo $invoice->subject . " - $" .$invoice->amount; ?> </option>

		<?php } ?>

		</select><input type='submit' class='button' value='New Invoice from Template' /> <span id="web_invoice_copy_invoice_cancel" class="web_invoice_click_me">cancel</span>
			</div>
<?php } ?>

					<?php if(!isset($user_id)) { ?>User must have a profile to receive invoices.

					<?php if(current_user_can('create_users')) { if($GLOBALS['wp_version'] < '2.7') { echo "<a href=\"users.php\">Create a new user account.</a>";  }
					else { echo "<a href=\"user-new.php\">Create a new user account.</a>"; } } }	 ?>

				</td>
			</tr>

		</table>
	</form>
</div>
</div>


<?php
}
?>