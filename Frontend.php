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

function web_invoice_the_content($content) {
	global $post;
	$ip=$_SERVER['REMOTE_ADDR'];

	// check if web_invoice_web_invoice_page is set, and that this it matches the current page, and the invoice_id is valid
	if(get_option('web_invoice_web_invoice_page') != '' && is_page(get_option('web_invoice_web_invoice_page'))) {

		// Check to see a proper invoice id is used, or show regular content
		if(!($invoice_id = web_invoice_md5_to_invoice($_GET['invoice_id']))) return $content;

		//If already paid, show thank you message
		// if(web_invoice_paid_status($invoice_id)) return web_invoice_show_already_paid($invoice_id).$content;

		// Show receipt if coming back from PayPal
		if(isset($_REQUEST['receipt_id'])) return web_invoice_show_paypal_receipt($invoice_id);

		// Invoice viewed, update log
		web_invoice_update_log($invoice_id,'visited',"Viewed by $ip");

		?>
<div id="invoice_page" class="clearfix"><?php
web_invoice_print_help($invoice_id);
do_action('web_invoice_front_top', $invoice_id);

//Billing Business Address
if(get_option('web_invoice_show_business_address') == 'yes') web_invoice_show_business_address();
if(get_option('web_invoice_show_billing_address') == 'yes') web_invoice_show_billing_address($invoice_id);

//If this is not recurring invoice, show regular message
if(!($recurring = web_invoice_recurring($invoice_id)))  web_invoice_show_invoice_overview($invoice_id);

// Show this if recurring
if($recurring)  web_invoice_show_recurring_info($invoice_id);

if(web_invoice_paid_status($invoice_id)) {
	web_invoice_show_already_paid($invoice_id);
	do_action('web_invoice_front_paid', $invoice_id);
} else {
	//Show Billing Information
	web_invoice_show_billing_information($invoice_id);
	do_action('web_invoice_front_unpaid', $invoice_id);
}
?>
</div>
<?php
	do_action('web_invoice_front_bottom', $invoice_id);
	} else return $content;
}

function web_invoice_frontend_js() {
	if(get_option('web_invoice_web_invoice_page') != '' && is_page(get_option('web_invoice_web_invoice_page')))  {
		function web_invoice_curPageURL() {
			$host_x = preg_split('/\//', get_option('siteurl'));
			$host = $host_x[2];  
						
			$pageURL = "http://".$host.$_SERVER['REQUEST_URI'];
			
			if(	get_option('web_invoice_force_https') == 'true' ) {  
				$pageURL = preg_replace('/^http/', 'https', $pageURL); 
			}
			return $pageURL;
		}
		?>
<script type="text/javascript">

var _invoice_id = "<?php print $_GET['invoice_id'];?>";

function cc_card_pick(card_image, card_num){
	if (card_image == null) {
		card_image = '#cardimage';
	}
	if (card_num == null) {
		card_num = '#card_num';
	}

	numLength = jQuery(card_num).val().length;
	number = jQuery(card_num).val();
	if (numLength > 10)
	{
		if((number.charAt(0) == '4') && ((numLength == 13)||(numLength==16))) { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage visa_card'); }
		else if((number.charAt(0) == '5' && ((number.charAt(1) >= '1') && (number.charAt(1) <= '5'))) && (numLength==16)) { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage mastercard'); }
		else if(number.substring(0,4) == "6011" && (numLength==16)) 	{ jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage amex'); }
		else if((number.charAt(0) == '3' && ((number.charAt(1) == '4') || (number.charAt(1) == '7'))) && (numLength==15)) { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage discover_card'); }
		else { jQuery(card_image).removeClass(); jQuery(card_image).addClass('cardimage nocard'); }

	}
}

function process_cc_checkout(type) {
	if (type == null) {
		type = '';
	}

	jQuery('#web_invoice_process_wait span').html('<img src="<?php echo Web_Invoice::frontend_path(); ?>/images/processing-ajax.gif">');

	site_url = '<?php echo web_invoice_curPageURL(); ?>';
	
	if (type == 'pfp') {
		link_id = 'wp_pfp_response';
		_checkout_form = 'pfp_checkout_form';
	} else if (type == 'sagepay') {
		link_id = 'wp_sagepay_response';
		_checkout_form = 'sagepay_checkout_form';
	} else {
		link_id = 'wp_cc_response';
		_checkout_form = 'checkout_form';
	}

	var req = jQuery.post ( site_url, jQuery('#' + _checkout_form).serialize(), function(html) {

			var explode = html.toString().split('\n');
			var shown = false;
			var msg = '<?php _e('<b>There are problems with your transaction:</b>', WEB_INVOICE_TRANS_DOMAIN); ?><ol>';


			for ( var i in explode )
			{
				var explode_again = explode[i].toString().split('|');
				if (explode_again[0]=='error')
				{
					if ( ! shown ) {
						jQuery('#' + link_id).fadeIn("slow");
					}
					shown = true;
					add_remove_class('ok','error',explode_again[1]);
					/*jQuery('#err_' + explode_again[1]).html(explode_again[2]); */
					msg += "<li>" + explode_again[2] + "</li>";
				}
				else if (explode_again[0]=='ok') {
					add_remove_class('error','ok',explode_again[1]);
					/*jQuery('#err_' + explode_again[1]).hide(); */
				}
			}

			if ( ! shown )
			{
			if(html == 'Transaction okay.') {

				jQuery('#' + link_id).fadeIn("slow");
				jQuery('#' + link_id).html("<?php _e('Thank you! <br />Payment processed successfully!', WEB_INVOICE_TRANS_DOMAIN); ?>");
				jQuery("#credit_card_information").hide();

				jQuery('#' + link_id).show();
				window.location = '';
				}
			} else {
				add_remove_class('success','error',link_id);
				jQuery('#' + link_id).html(msg + "</ol>");
			}
			jQuery('#web_invoice_process_wait span').html('');
			req = null;
		}
	);
}

function process_sagepay_process(type) {
	if (type == null) {
		type = 'form';
	}

	jQuery('#web_invoice_process_wait span').html('<img src="<?php echo Web_Invoice::frontend_path(); ?>/images/processing-ajax.gif">');

	site_url = '<?php echo web_invoice_curPageURL(); ?>';
	
	if (type == 'form') {
		link_id = 'wp_sagepay_response';
		_checkout_form = 'sagepay_checkout_form';
	}

	var req = jQuery.post ( site_url, jQuery('#' + _checkout_form).serialize(), function(html) {

			var explode = html.toString().split('\n');
			var shown = false;
			var msg = '<?php _e('<b>There are problems with your transaction:</b>', WEB_INVOICE_TRANS_DOMAIN); ?><ol>';


			for ( var i in explode )
			{
				var explode_again = explode[i].toString().split('|');
				if (explode_again[0]=='error')
				{
					if ( ! shown ) {
						jQuery('#' + link_id).fadeIn("slow");
					}
					shown = true;
					add_remove_class('ok','error',explode_again[1]);
					/*jQuery('#err_' + explode_again[1]).html(explode_again[2]); */
					msg += "<li>" + explode_again[2] + "</li>";
				}
				else if (explode_again[0]=='ok') {
					add_remove_class('error','ok',explode_again[1]);
					/*jQuery('#err_' + explode_again[1]).hide(); */
				}
			}

			if ( ! shown )
			{
				jQuery('#sagepay_crypt').val(html);
				jQuery('#sagepay_submit_form').submit();
			} else {
				add_remove_class('success','error',link_id);
				jQuery('#' + link_id).html(msg + "</ol>");
			}
			jQuery('#web_invoice_process_wait span').html('');
			req = null;
		}
	);
}

function add_remove_class(search,replace,element_id)
{
	if (jQuery('#' + element_id).hasClass(search)){
		jQuery('#' + element_id).removeClass(search);
	}
	jQuery('#' + element_id).addClass(replace);
}

</script>
		<?php
	}

}

function web_invoice_frontend_css() {
	if(get_option('web_invoice_web_invoice_page') != '' && is_page(get_option('web_invoice_web_invoice_page')))  {
		echo '<meta name="robots" content="noindex, nofollow" />';

		if(get_option('web_invoice_use_css') == 'yes') {
			echo '<link type="text/css" media="print" rel="stylesheet" href="' . Web_Invoice::frontend_path() . '/css/web_invoice-print.css?2010022101"></link>' . "\n";
			echo '<link type="text/css" media="screen" rel="stylesheet" href="' . Web_Invoice::frontend_path() . '/css/web_invoice-screen.css?201022201"></link>' . "\n";
		}
	}
}

function web_invoice_print_pdf() {
	global $post, $web_invoice_print;
	$web_invoice_print = true;
	$ip=$_SERVER['REMOTE_ADDR'];

	ob_start();
		
	// Check to see a proper invoice id is used, or show regular content
	if(!($invoice_id = web_invoice_md5_to_invoice($_GET['invoice_id']))) return $content;

	//If already paid, show thank you message
	// if(web_invoice_paid_status($invoice_id)) return web_invoice_show_already_paid($invoice_id).$content;

	// Show receipt if coming back from PayPal
	if(isset($_REQUEST['receipt_id'])) return web_invoice_show_paypal_receipt($invoice_id);

	// Invoice viewed, update log
	web_invoice_update_log($invoice_id, 'visited', "PDF downloaded by $ip");

	?>
	<style type="text/css">
		.noprint { display: none; }
		#invoice_page { width: 500px; margin: 0 auto; font-size: 11px; font-family: 'Trebuchet MS','Lucida Grande',Verdana,Tahoma,Arial; }
		th { text-align: left; font-size: 13px; padding: 5px; }
		td { font-size: 12px; vertical-align: top; padding: 5px; }
		tr td { background-color: #fefefe; }
		tr.alt_row  td { background-color: #eee; }
		span.description_text { color: #333; font-size: 0.8px; }
		tr.web_invoice_bottom_line { font-size: 1.1em; font-weight: bold; }
		table { width: 100%; }
		h2 { font-size: 1.1em; }
		h1 { text-align: center; }
		p { margin: 5px; 0px; }
		div.clear { clear: both; }
		
		#invoice_client_info { float: right; }
	</style>
	<?php
	
		do_action('web_invoice_front_top', $invoice_id);
		
		//Billing Business Address
		if(get_option('web_invoice_show_business_address') == 'yes') web_invoice_show_business_address();
		if(get_option('web_invoice_show_billing_address') == 'yes') web_invoice_show_billing_address($invoice_id);
	
		print '<div class="clear"></div>';
		
		//If this is not recurring invoice, show regular message
		if(!($recurring = web_invoice_recurring($invoice_id)))  web_invoice_show_invoice_overview($invoice_id);
	
		// Show this if recurring
		if($recurring)  web_invoice_show_recurring_info($invoice_id);
	
		if(web_invoice_paid_status($invoice_id)) {
			web_invoice_show_already_paid($invoice_id);
			do_action('web_invoice_front_paid', $invoice_id);
		} else {
			//Show Billing Information
			web_invoice_show_billing_information($invoice_id);
			do_action('web_invoice_front_unpaid', $invoice_id);
		}
		do_action('web_invoice_front_bottom', $invoice_id);
		?>
	<script type="text/php">
		if ( isset($pdf) ) {
    		$font = Font_Metrics::get_font("verdana", "bold");
			$font_light = Font_Metrics::get_font("verdana");
			$pdf->page_text(52, 810, "Powered by Web Invoice ".WEB_INVOICE_VERSION_NUM, $font_light, 10, array(0,0,0));
    		$pdf->page_text(510, 810, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 10, array(0,0,0));
  		}
	</script>
	<?php
	
	$content = preg_replace(array('/  /', '/\n\n/i', '/&euro;/i'), array(" ", "\n", "&#0128;"), '<div id="invoice_page" class="clearfix"><h1>Invoice</h1>'.ob_get_contents().'</div>');
	
	ob_clean();
	
	require_once "lib/dompdf_config.inc.php";
	
	$dompdf = new DOMPDF();
	$dompdf->load_html($content);
	$dompdf->set_paper("a4", "portrait");
	$dompdf->render();
	$dompdf->stream("web-invoice-{$invoice_id}.pdf");
	
	exit(0);
}
