<?php

class Web_Invoice_PayflowPro
{
	protected $login;
	protected $transkey;
	protected $params   = array();
	protected $results  = array();

	protected $approved = false;
	protected $declined = false;
	protected $error    = true;

	protected $fields;
	protected $response;

	static $instances = 0;
	static $version = '57.0';
	
	public function __construct()
	{
		if (self::$instances == 0)
		{
			if (get_option("web_invoice_pfp_env") == 'live') {
				$this->url = 'https://api-3t.paypal.com/nvp';
			} else {
				$this->url = 'https://api-3t.sandbox.paypal.com/nvp';
			}
			
			$this->params['METHOD']         = "doDirectPayment";
			$this->params['TRXTYPE']        = "S";
			$this->params['PAYMENTACTION']  = "Sale";
			$this->params['VERSION']        = self::$version;
			if (get_option('web_invoice_pfp_authentication')=='3token') {
				$this->params['PARTNER']        = stripslashes(get_option("web_invoice_pfp_partner"));
				$this->params['USER']           = stripslashes(get_option("web_invoice_pfp_username"));
				$this->params['PWD']            = stripslashes(get_option("web_invoice_pfp_password"));
				$this->params['SIGNATURE']      = stripslashes(get_option("web_invoice_pfp_signature"));
			} else {
				$this->params['SUBJECT']        = stripslashes(get_option("web_invoice_pfp_3rdparty_email"));
			}
			
			$this->params['TENDER']         = "C";
			
			self::$instances++;
		}
		else
		{
			return false;
		}
	}

	public function transaction($cardnum)
	{
		$this->params['ACCT']  = trim($cardnum);
		$this->params['CREDITCARDTYPE'] = $this->guessCcType();
	}
	
	public function guessCcType() {
		$numLength = strlen($this->params['ACCT']);
		$number = $this->params['ACCT'];
		if ($numLength > 10)
		{
			if((substr($number, 0, 1) == '4') && (($numLength == 13)||($numLength==16))) { return 'Visa'; }
			else if((substr($number, 0, 1) == '5' && ((substr($number, 1, 1) >= '1') && (substr($number, 1, 1) <= '5'))) && ($numLength==16)) { return 'MasterCard'; }
			else if(substr($number, 0, 4) == "6011" && ($numLength==16)) 	{ return 'Discover'; }
			else if((substr($number, 0, 1) == '3' && ((substr($number, 1, 1) == '4') || (substr($number, 1, 1) == '7'))) && ($numLength==15)) { return 'Amex'; }
			else { return ''; }
	
		}
	}

	public function process($retries = 1)
	{
		$this->_prepareParameters();
		$ch = curl_init($this->url);

		$count = 0;
		while ($count < $retries)
		{

			//required for GoDaddy
			if(get_option('web_invoice_using_godaddy') == 'yes') {
				curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
				curl_setopt ($ch, CURLOPT_PROXY,"http://proxy.shr.secureserver.net:3128");
				curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt ($ch, CURLOPT_TIMEOUT, 120);
			}
			//required for GoDaddy

			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, rtrim($this->fields, "& "));
			$this->response = curl_exec($ch);

			$this->parseResults();


			if ($this->getResultResponseFull() == "Approved")
			{	
				$this->approved = true;
				$this->declined = false;
				$this->error    = false;
				break;
			}
			else if ($this->getResultResponseFull() == "Declined")
			{
				$this->approved = false;
				$this->declined = true;
				$this->error    = false;
				break;
			}
			$count++;
		}

		curl_close($ch);
	}

	function parseResults()
	{
		$results = explode('&', $this->response);
		
		foreach ($results as $result) {
			list($k, $v) = explode('=', $result);
			$this->results[$k] = urldecode($v);
		}
	}

	public function setParameter($param, $value)
	{
		$param                = trim($param);
		$value                = trim($value);
		$this->params[$param] = $value;
	}

	public function setTransactionType($type)
	{
		$this->params['TRXTYPE'] = strtoupper(trim($type));
	}

	private function _prepareParameters()
	{
		foreach($this->params as $key => $value)
		{
			$this->fields .= "$key=" . urlencode($value) . "&";
		}
	}

	public function getGatewayResponse()
	{
		return $this->results['RESULT'];
	}

	public function getResultResponseFull()
	{
		switch ($this->results['ACK']) {
			case "Success":
				return "Approved";
			case 12:
				return "Declined";
			case 13 || 126:
				return "Deferred";
			default: 
				return "Error"; 
		}
	}

	public function isApproved()
	{
		return $this->approved;
	}

	public function isDeclined()
	{
		return $this->declined;
	}

	public function isError()
	{
		return $this->error;
	}

	public function getResponseText()
	{
		return $this->results['ACK'];
	}
	
	public function getResponseCode()
	{
		return $this->results['L_ERRORCODE0'];
	}

	public function getAuthCode()
	{
		return $this->results['CORRELATIONID'];
	}

	public function getAVSResponse()
	{
		return $this->results['AVSCODE'];
	}
	
	public function getTransactionID()
	{
		return $this->results['TRANSACTIONID'];
	}
}


class Web_Invoice_PayflowProRecurring extends Web_Invoice_PayflowPro {

	static $version = '50.0';
	
	public function __construct()
	{
		if (self::$instances < 2)
		{
			if (get_option("web_invoice_pfp_env") == 'live') {
				$this->url = 'https://api-3t.paypal.com/nvp';
			} else {
				$this->url = 'https://api-3t.sandbox.paypal.com/nvp';
			}

			$this->params['METHOD']         = "CreateRecurringPaymentsProfile";
			$this->params['TRXTYPE']        = "R";
			$this->params['PAYMENTACTION']  = "Sale";
			$this->params['VERSION']        = self::$version;
			if (get_option('web_invoice_pfp_authentication')=='3token') {
				$this->params['PARTNER']    = stripslashes(get_option("web_invoice_pfp_partner"));
				$this->params['USER']       = stripslashes(get_option("web_invoice_pfp_username"));
				$this->params['PWD']        = stripslashes(get_option("web_invoice_pfp_password"));
				$this->params['SIGNATURE']  = stripslashes(get_option("web_invoice_pfp_signature"));
			} else {
				$this->params['SUBJECT']    = stripslashes(get_option("web_invoice_pfp_3rdparty_email"));
			}
			$this->params['TENDER']         = "C";
			
			self::$instances++;
		}
		else
		{
			return false;
		}
	}
	
	public function getTransactionID() {
		return $this->results['PROFILEID'];
	}
	
	public function getSubscriberID() {
		return $this->results['PROFILEID'];
	}
	
	public function createAccount() {
		return $this->process();
	}
	
	public function isSuccessful() {
		return $this->isApproved();
	}
}
