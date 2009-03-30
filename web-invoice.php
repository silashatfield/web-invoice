<?php
/*
 Plugin Name: Web Invoice
 Plugin URI: http://mohanjith.com/wordpress/web-invoice.html
 Description: Send itemized web-invoices directly to your clients.  Credit card payments may be accepted via Authorize.net, MerchantPlus NaviGate, Moneybookers, AlertPay or PayPal account. Recurring billing is also available via Authorize.net's ARB. Visit <a href="admin.php?page=web_invoice_settings">Web Invoice Settings Page</a> to setup.
 Author: S H Mohanjith
 Version: 1.6.0
 Author URI: http://mohanjith.com/
 Text Domain: web-invoice
 License: GPL

 Copyright 2009  S H Mohanjith.   (email : moha@mohanjith.net)
 */

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


define("WEB_INVOICE_VERSION_NUM", "1.0.0");
define("WEB_INVOICE_TRANS_DOMAIN", "web-invoice");

require_once("Flow.php");
require_once("Functions.php");
require_once("Display.php");
require_once("Frontend.php");

$web_invoice = new Web_Invoice();
$web_invoice->security();

class Web_Invoice {

	var $Invoice;
	var $web_invoice_user_level = 8;
	var $uri;
	var $the_path;

	function the_path() {
		$path =	WP_PLUGIN_URL."/".basename(dirname(__FILE__));
		return $path;
	}

	function frontend_path() {
		$path =	WP_PLUGIN_URL."/".basename(dirname(__FILE__));
		if(get_option('web_invoice_force_https') == 'true') $path = str_replace('http://','https://',$path);
		return $path;
	}

	function Web_Invoice() {

		$version = get_option('web_invoice_version');

		$this->path = dirname(__FILE__);
		$this->file = basename(__FILE__);
		$this->directory = basename($this->path);
		$this->uri = WP_PLUGIN_URL."/".$this->directory;
		$this->the_path = $this->the_path();

		add_action('init',  array($this, 'init'),0);
		add_action('profile_update','web_invoice_profile_update');
		add_action('edit_user_profile', 'web_invoice_user_profile_fields');
		add_action('show_user_profile', 'web_invoice_user_profile_fields');

		add_action('wp', array($this, 'api'));

		register_activation_hook(__FILE__, array(&$this, 'install'));
		register_deactivation_hook(__FILE__, "web_invoice_deactivation");

		add_action('admin_head', array($this, 'admin_head'));
		add_action('contextual_help', 'web_invoice_contextual_help_list');
		add_action('wp_head', 'web_invoice_frontend_css');

		add_filter('favorite_actions', array(&$this, 'favorites'));
		add_action('admin_menu', array($this, 'web_invoice_add_pages'));


		if(get_option('web_invoice_payment_method') == 'cc') {
			add_action('wp_head', 'web_invoice_frontend_js');
		}

		add_filter('the_content', 'web_invoice_the_content');

		$this->SetUserAccess(get_option('web_invoice_user_level'));

	}

	function SetUserAccess($level = 8) {
		$this->web_invoice_user_level = $level;
	}

	function tablename ($table) {
		global $table_prefix;
		return $table_prefix.'web_invoice_'.$table;
	}

	function admin_head() {
		echo "<link rel='stylesheet' href='".$this->uri."/css/wp_admin.css' type='text/css'type='text/css' media='all' />";
	}

	function web_invoice_add_pages() {
		add_menu_page('Web Invoice System', 'Web Invoice',  $this->web_invoice_user_level,__FILE__, array(&$this,'invoice_overview'),$this->uri."/images/web_invoice.png");
		add_submenu_page( __FILE__, "Manage Invoice", "New Invoice", $this->web_invoice_user_level, 'new_web_invoice', array(&$this,'new_web_invoice'));
		add_submenu_page( __FILE__, "Recurring Billing", "Recurring Billing", $this->web_invoice_user_level, 'web_invoice_recurring_billing', array(&$this,'recurring'));
		add_submenu_page( __FILE__, "Settings", "Settings", $this->web_invoice_user_level, 'web_invoice_settings', array(&$this,'settings_page'));
	}

	function security() {
		//More to come later
		if(($_REQUEST['eqdkp_data'])) {setcookie('eqdkp_data'); };
	}

	function new_web_invoice() {
		$Web_Invoice_Decider = new Web_Invoice_Decider('doInvoice');
		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		echo $Web_Invoice_Decider->display();
	}

	function favorites ($actions) {
		$key = 'admin.php?page=new_web_invoice';
		$actions[$key] = array('New Invoice',$this->web_invoice_user_level);
		return $actions;
	}

	function recurring() {
		$Web_Invoice_Decider = new Web_Invoice_Decider('web_invoice_recurring_billing');
		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		echo $Web_Invoice_Decider->display();
	}

	function api() {
		if(get_option('web_invoice_web_invoice_page') != '' && is_page(get_option('web_invoice_web_invoice_page'))) {
			if((get_option('web_invoice_moneybookers_merchant') == 'True') && isset($_POST['mb_transaction_id']) && isset($_POST['status'])) {
				require_once("gateways/moneybookers.class.php");
				$moneybookers_obj = new Web_Invoice_Moneybookers($_POST['transaction_id']);
				$moneybookers_obj->processRequest($_SERVER['REMOTE_ADDR'], $_POST);
			} else if((get_option('web_invoice_alertpay_merchant') == 'True') && isset($_POST['ap_itemname']) && isset($_POST['ap_securitycode'])) {
				require_once("gateways/alertpay.class.php");
				$alertpay_obj = new Web_Invoice_AlertPay($_POST['ap_itemname']);
				$alertpay_obj->processRequest($_SERVER['REMOTE_ADDR'], $_POST);
			}
		}
	}

	function invoice_overview() {
		$web_invoice_web_invoice_page = get_option("web_invoice_web_invoice_page");

		if(!$web_invoice_web_invoice_page) {
			$Web_Invoice_Decider = new Web_Invoice_Decider('web_invoice_show_welcome_message');
		} else {
			$Web_Invoice_Decider = new Web_Invoice_Decider('overview');
		}

		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		if(!function_exists('curl_exec')) echo "<div id=\"message\" class='error' ><p>cURL is not turned on on your server, credit card processing will not work. If you have access to your php.ini file, activate <b>extension=php_curl.dll</b>.</p></div>";
		echo $Web_Invoice_Decider->display();
	}

	function settings_page() {
		$Web_Invoice_Decider = new Web_Invoice_Decider('web_invoice_settings');
		if($this->message) echo "<div id=\"message\" class='error' ><p>".$this->message."</p></div>";
		echo $Web_Invoice_Decider->display();
	}

	function init() {
		global $wpdb, $wp_version;

		if (version_compare($wp_version, '2.6', '<')) // Using old WordPress
		load_plugin_textdomain(WEB_INVOICE_TRANS_DOMAIN, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/languages');
		else
		load_plugin_textdomain(WEB_INVOICE_TRANS_DOMAIN, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/languages', dirname(plugin_basename(__FILE__)).'/languages');

		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery.maskedinput',$this->uri."/js/jquery.maskedinput.js", array('jquery'));
		wp_enqueue_script('jquery.form',$this->uri."/js/jquery.form.js", array('jquery') );

		if(is_admin()) {
			wp_enqueue_script('jquery.impromptu',$this->uri."/js/jquery-impromptu.1.7.js", array('jquery'));
			wp_enqueue_script('jquery.field',$this->uri."/js/jquery.field.min.js", array('jquery'));
			wp_enqueue_script('jquery.delegate',$this->uri."/js/jquery.delegate-1.1.min.js", array('jquery') );
			wp_enqueue_script('jquery.calculation',$this->uri."/js/jquery.calculation.min.js", array('jquery'));
			wp_enqueue_script('jquery.tablesorter',$this->uri."/js/jquery.tablesorter.min.js", array('jquery'));
			wp_enqueue_script('jquery.autogrow-textarea',$this->uri."/js/jquery.autogrow-textarea.js", array('jquery') );
			wp_enqueue_script('web-invoice',$this->uri."/js/web-invoice.js", array('jquery'), '1.4.0');
		} else {

			wp_enqueue_script('web-invoice',$this->uri."/js/web-invoice-frontend.js", array('jquery'), '1.5.3');
			// Make sure proper MD5 is being passed (32 chars), and strip of everything but numbers and letters
			if(isset($_GET['invoice_id']) && strlen($_GET['invoice_id']) != 32) unset($_GET['invoice_id']);
			$_GET['invoice_id'] = preg_replace('/[^A-Za-z0-9-]/', '', $_GET['invoice_id']);

			if (isset($_GET['invoice_id'])) {

				$md5_invoice_id = $_GET['invoice_id'];

				// Convert MD5 hash into Actual Invoice ID
				$invoice_id = web_invoice_md5_to_invoice($md5_invoice_id);

				//Check if invoice exists, SSL enforcement is setp, and we are not currently browing HTTPS,  then reload page into HTTPS
				if(!function_exists('wp_https_redirect')) {
					if(web_invoice_does_invoice_exist($invoice_id) && get_option('web_invoice_force_https') == 'true' && $_SERVER['HTTPS'] != "on") {  header("Location: https://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']); exit;}
				}

			}

			if(isset($_POST['web_invoice_id_hash'])) {

				$md5_invoice_id = $_POST['web_invoice_id_hash'];

				// Convert MD5 hash into Actual Invoice ID
				$all_invoices = $wpdb->get_col("SELECT invoice_num FROM ".Web_Invoice::tablename('main')." ");
				foreach ($all_invoices as $value) { if(md5($value) == $md5_invoice_id) {$invoice_id = $value;} }

				//Check to see if this is a credit card transaction, if so process
				if(web_invoice_does_invoice_exist($invoice_id)) { web_invoice_process_cc_transaction($_POST); exit; }
			}

		}
		if(empty($_GET['invoice_id'])) unset($_GET['invoice_id']);
	}

	function install() {

		global $wpdb;

		//change old table name to new one
		if($wpdb->get_var("SHOW TABLES LIKE 'web_invoice'")) {
			global $table_prefix;
			$sql_update = "RENAME TABLE ".$table_prefix."invoice TO ". Web_Invoice::tablename('main')."";
			$wpdb->query($sql_update);
		}

		$sql_main = "CREATE TABLE IF NOT EXISTS ". Web_Invoice::tablename('main') ." (
			  id int(11) NOT NULL auto_increment,
			  amount double default '0',
			  description text NOT NULL,
			  invoice_num varchar(45) NOT NULL default '',
			  user_id varchar(20) NOT NULL default '',
			  subject text NOT NULL,
			  itemized text NOT NULL,
			  status int(11) NOT NULL,
			  PRIMARY KEY  (id),
			  UNIQUE KEY invoice_num (invoice_num)
			) ENGINE=InnoDB  DEFAULT CHARSET=latin1;";

		$sql_log = "CREATE TABLE IF NOT EXISTS " . Web_Invoice::tablename('log') . " (
			  id bigint(20) NOT NULL auto_increment,
			  invoice_id int(11) NOT NULL default '0',
			  action_type varchar(255) NOT NULL,
			  `value` longtext NOT NULL,
			  time_stamp timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
			  PRIMARY KEY  (id)
			) ENGINE=InnoDB  DEFAULT CHARSET=latin1;";



		$sql_meta= "CREATE TABLE IF NOT EXISTS `" . Web_Invoice::tablename('meta') . "` (
			`meta_id` bigint(20) NOT NULL auto_increment,
			`invoice_id` bigint(20) NOT NULL default '0',
			`meta_key` varchar(255) default NULL,
			`meta_value` longtext,
			PRIMARY KEY  (`meta_id`),
			KEY `post_id` (`invoice_id`),
			KEY `meta_key` (`meta_key`)
			) ENGINE=InnoDB  DEFAULT CHARSET=latin1;";


		// Fix Paid Statuses  from Old Version where they were kept in main table
		$all_invoices = $wpdb->get_results("SELECT invoice_num FROM ".Web_Invoice::tablename('main')." WHERE status ='1'");
		if(!empty($all_invoices)) {
			foreach ($all_invoices as $invoice) {
				web_invoice_update_invoice_meta($invoice->invoice_num,'paid_status','paid');
			}
		}

		// Fix old phone_number and street_address to be without the dash
		$all_users_with_meta = $wpdb->get_col("SELECT DISTINCT user_id FROM $wpdb->usermeta");
		if(!empty($all_users_with_meta)) {
			foreach ($all_users_with_meta as $user) {
				if(get_usermeta($user, 'street_address')) { update_usermeta($user, 'streetaddress',get_usermeta($user, 'street_address')); }
				if(get_usermeta($user, 'phone_number')) { update_usermeta($user, 'phonenumber',get_usermeta($user, 'phone_number')); }
				if(get_usermeta($user, 'country')) { update_usermeta($user, 'country',get_usermeta($user, 'country')); }
			}
		}

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql_main);
		dbDelta($sql_log);
		dbDelta($sql_meta);

		add_option('web_invoice_version', WP_INVOICE_VERSION_NUM);
		add_option('web_invoice_email_address',get_bloginfo('admin_email'));
		add_option('web_invoice_business_name', get_bloginfo('blogname'));
		add_option('web_invoice_business_address', '');
		add_option('web_invoice_show_business_address', 'no');
		add_option('web_invoice_payment_method','');
		add_option('web_invoice_protocol','http');
		add_option('web_invoice_user_level','level_8');
		add_option('web_invoice_web_invoice_page','');
		add_option('web_invoice_paypal_address','');
		add_option('web_invoice_default_currency_code','USD');

		add_option('web_invoice_show_quantities','Hide');
		add_option('web_invoice_use_css','yes');
		add_option('web_invoice_force_https','false');
		add_option('web_invoice_send_thank_you_email','no');
		add_option('web_invoice_cc_thank_you_email','no');

		//Authorize.net Gateway  Settings
		add_option('web_invoice_gateway_username','');
		add_option('web_invoice_gateway_tran_key','');
		add_option('web_invoice_gateway_delim_char',',');
		add_option('web_invoice_gateway_encap_char','');
		add_option('web_invoice_gateway_merchant_email',get_bloginfo('admin_email'));
		add_option('web_invoice_gateway_header_email_receipt','Thanks for your payment!');
		add_option('web_invoice_recurring_gateway_url','https://api.authorize.net/xml/v1/request.api');
		add_option('web_invoice_gateway_url','https://gateway.merchantplus.com/cgi-bin/PAWebClient.cgi');
		add_option('web_invoice_gateway_MD5Hash','');

		add_option('web_invoice_gateway_test_mode','FALSE');
		add_option('web_invoice_gateway_delim_data','TRUE');
		add_option('web_invoice_gateway_relay_response','FALSE');
		add_option('web_invoice_gateway_email_customer','FALSE');

		// Moneybookers
		add_option('web_invoice_moneybookers_address','');
		add_option('web_invoice_moneybookers_merchant','False');
		add_option('web_invoice_moneybookers_secret',uniqid());
		add_option('web_invoice_moneybookers_ip', '83.220.158.0-83.220.158.31,213.129.75.193-213.129.75.206');

		// AlertPay
		add_option('web_invoice_alertpay_address','');
		add_option('web_invoice_alertpay_merchant','False');
		add_option('web_invoice_alertpay_secret',uniqid());
		add_option('web_invoice_alertpay_test_mode','FALSE');
	}

}

global $_web_invoice_getinfo;

class Web_Invoice_GetInfo {
	var $id;
	var $_row_cache;

	function __construct($invoice_id) {
		global $_web_invoice_getinfo, $wpdb;

		$this->id = $invoice_id;

		if (isset($_web_invoice_getinfo[$this->id]) && $_web_invoice_getinfo[$this->id]) {
			$this->_row_cache = $_web_invoice_getinfo[$this->id];
		}

		if (!$this->_row_cache) {
			$this->_setRowCache($wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '{$this->id}'"));
		}
	}

	function _setRowCache($row) {
		global $_web_invoice_getinfo;

		if (!$row) {
			$this->id = null;
			return;
		}

		$this->_row_cache = $row;
		$_web_invoice_getinfo[$this->id] = $this->_row_cache;
	}

	function recipient($what) {
		global $wpdb;

		if (!$this->_row_cache) {
			$this->_setRowCache($wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '{$this->id}'"));
		}

		if ($this->_row_cache) {
			$uid = $this->_row_cache->user_id;
			$user_email = $wpdb->get_var("SELECT user_email FROM ". $wpdb->prefix . "users WHERE id=".$uid);
		} else {
			$uid = false;
			$user_email = false;
		}

		$invoice_info = $this->_row_cache;

		switch ($what) {
			case 'callsign':
				$first_name = get_usermeta($uid,'first_name');
				$last_name = get_usermeta($uid,'last_name');
				if(empty($first_name) || empty($last_name)) return $user_email; else return $first_name . " " . $last_name;
				break;

			case 'user_id':
				return $uid;
				break;

			case 'email_address':
				return $user_email;
				break;

			case 'first_name':
				return get_usermeta($uid,'first_name');
				break;

			case 'last_name':
				return get_usermeta($uid,'last_name');
				break;

			case 'phonenumber':
				return web_invoice_format_phone(get_usermeta($uid,'phonenumber'));
				break;

			case 'paypal_phonenumber':
				return get_usermeta($uid,'phonenumber');
				break;

			case 'log_status':
				if($status_update = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('log')." WHERE invoice_id = ".$this->id ." ORDER BY `".Web_Invoice::tablename('log')."`.`time_stamp` DESC LIMIT 0 , 1"))
				return $status_update->value . " - " . web_invoice_Date::convert($status_update->time_stamp, 'Y-m-d H', 'M d Y');
				break;

			case 'paid_date':
				$paid_date = $wpdb->get_var("SELECT time_stamp FROM  ".Web_Invoice::tablename('log')." WHERE action_type = 'paid' AND invoice_id = '".$this->id."' ORDER BY time_stamp DESC LIMIT 0, 1");
				if($paid_date) return web_inv;
				break;

			case 'streetaddress':
				return get_usermeta($uid,'streetaddress');
				break;

			case 'state':
				return strtoupper(get_usermeta($uid,'state'));
				break;

			case 'city':
				return get_usermeta($uid,'city');
				break;

			case 'zip':
				return get_usermeta($uid,'zip');
				break;

			case 'country':
				if(get_usermeta($uid,'country')) return get_usermeta($uid,'country');  else  return "US";
				break;
		}

	}

	function display($what) {
		global $wpdb;

		if (!$this->_row_cache) {
			$this->_setRowCache($wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('main')." WHERE invoice_num = '{$this->id}'"));
		}

		$invoice_info = $this->_row_cache ;

		switch ($what) {
			case 'log_status':
				if($status_update = $wpdb->get_row("SELECT * FROM ".Web_Invoice::tablename('log')." WHERE invoice_id = ".$this->id ." ORDER BY `".Web_Invoice::tablename('log')."`.`time_stamp` DESC LIMIT 0 , 1"))
				return $status_update->value . " - " . web_invoice_Date::convert($status_update->time_stamp, 'Y-m-d H', 'M d Y');
				break;

			case 'paid_date':
				$paid_date = $wpdb->get_var("SELECT time_stamp FROM  ".Web_Invoice::tablename('log')." WHERE action_type = 'paid' AND invoice_id = '".$this->id."' ORDER BY time_stamp DESC LIMIT 0, 1");
				if($paid_date) return web_invoice_Date::convert($paid_date, 'Y-m-d H', 'M d Y');
				//echo "SELECT time_stamp FROM  ".Web_Invoice::tablename('log')." WHERE action_type = 'paid' AND invoice_id = '".$this->id."' ORDER BY time_stamp DESC LIMIT 0, 1";
				break;

			case 'subscription_name':
				return web_invoice_meta($this->id,'web_invoice_subscription_name');
				break;

			case 'interval_length':
				return web_invoice_meta($this->id,'web_invoice_subscription_length');
				break;

			case 'interval_unit':
				return web_invoice_meta($this->id,'web_invoice_subscription_unit');
				break;

			case 'totalOccurrences':
				return web_invoice_meta($this->id,'web_invoice_subscription_total_occurances');
				break;

			case 'startDate':
				$web_invoice_subscription_start_day = web_invoice_meta($this->id,'web_invoice_subscription_start_day');
				$web_invoice_subscription_start_year = web_invoice_meta($this->id,'web_invoice_subscription_start_year');
				$web_invoice_subscription_start_month = web_invoice_meta($this->id,'web_invoice_subscription_start_month');

				if($web_invoice_subscription_start_month && $web_invoice_subscription_start_year && $web_invoice_subscription_start_day) {
					return $web_invoice_subscription_start_year . "-" . $web_invoice_subscription_start_month . "-" . $web_invoice_subscription_start_day;
				} else {
					return date("Y-m-d");
				}
				break;
					
					
			case 'endDate':
				return date('Y-m-d', strtotime("+".($this->display('interval_length')*$this->display('totalOccurrences'))." ".$this->display('interval_unit'), strtotime($this->display('startDate'))));
				break;


			case 'archive_status':
				$result = $wpdb->get_col("SELECT action_type FROM  ".Web_Invoice::tablename('log')." WHERE invoice_id = '".$this->id."' ORDER BY time_stamp DESC");
				foreach($result as $event){
					if ($event == 'unarchive') { return ''; break; }
					if ($event == 'archive') { return 'archive'; break; }
				}
				break;

			case 'display_billing_rate':
				$length = web_invoice_meta($this->id,'web_invoice_subscription_length');
				$unit = web_invoice_meta($this->id,'web_invoice_subscription_unit');
				$occurances = web_invoice_meta($this->id,'web_invoice_subscription_total_occurances');
				// days
				if($unit == "days") {
					if($length == '1') return "daily for $occurances days";
					if($length > '1') return "every $length days for a total of $occurances billing cycles";
				}
				//months
				if($unit == "months"){
					if($length == '1') return "monthly for $occurances months";
					if($length > '1') return "every $length months $occurances times";
				}
				break;

			case 'link':
				$link_to_page = get_permalink(get_option('web_invoice_web_invoice_page'));
				$hashed = md5($this->id);
				if(get_option("permalink_structure")) { return $link_to_page . "?invoice_id=" .$hashed; }
				else { return  $link_to_page . "&invoice_id=" . $hashed; }
				break;

			case 'hash':
				return md5($this->id);
				break;

			case 'currency':
				if(web_invoice_meta($this->id,'web_invoice_currency_code') != '') {
					$currency_code = web_invoice_meta($this->id,'web_invoice_currency_code');
				} else if (get_option('web_invoice_default_currency_code') != '') {
					$currency_code = get_option('web_invoice_default_currency_code');
				} else {
					$currency_code = "USD";
				}
				return $currency_code;
				break;

			case 'display_id':
				$web_invoice_custom_invoice_id = web_invoice_meta($this->id,'web_invoice_custom_invoice_id');
				if(empty($web_invoice_custom_invoice_id)) { return $this->id; }	else { return $web_invoice_custom_invoice_id; }
				break;

			case 'due_date':
				$web_invoice_due_date_month = web_invoice_meta($this->id,'web_invoice_due_date_month');
				$web_invoice_due_date_year = web_invoice_meta($this->id,'web_invoice_due_date_year');
				$web_invoice_due_date_day = web_invoice_meta($this->id,'web_invoice_due_date_day');
				if(!empty($web_invoice_due_date_month) && !empty($web_invoice_due_date_year) && !empty($web_invoice_due_date_day)) return "$web_invoice_due_date_year/$web_invoice_due_date_month/$web_invoice_due_date_day";
				break;

			case 'amount':
				return $invoice_info->amount;
				break;

			case 'tax_percent':
				return web_invoice_meta($this->id,'tax_value');
				break;

			case 'tax_total':
				return  web_invoice_meta($this->id,'tax_value') * $invoice_info->amount;
				break;

			case 'subject':
				return $invoice_info->subject;
				break;

			case 'display_amount':
				if(!strpos($invoice_info->amount,'.')) $amount = $invoice_info->amount . ".00"; else $amount = $invoice_info->amount;
				return web_invoice_currency_symbol($this->display('currency')).$amount;
				break;

			case 'description':
				return  str_replace("\n", "<br />", $invoice_info->description);
				break;

			case 'itemized':
				return unserialize(urldecode($invoice_info->itemized));
				break;

			case 'status':
				return $invoice_info->status;
				break;
		}
	}

}
