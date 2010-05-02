<?php
/*
 Created by S H Mohanjith
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

class Web_Invoice_Paypal {

	var $invoice;

	var $ip;

	var $pay_to_email;
	var $pay_from_email;
	var $transaction_id;

	var $status;
	var $amount;
	var $currency;

	var $recurring_payment_type;
	var $recurring_payment_id;

	function Web_Invoice_Moneybookers($invoice_id) {
		$this->invoice = new Web_Invoice_GetInfo($invoice_id);
	}

	function _logFailure($ref) {
		web_invoice_update_log($this->invoice->id,'pp_ipn_fail',"Failed PayPal IPN request from {$this->ip}. REF: {$ref}. Serialized object ".serialize($this));
	}

	function _logSuccess($ref) {
		web_invoice_update_log($this->invoice->id,'pp_ipn_success',"Successful PayPal IPN request from {$this->ip}. REF: {$ref}");
	}
	
	function processRequest($ip, $request) {

		$this->ip = $ip;

		$this->pay_to_email = $request['receiver_email'];
		$this->pay_from_email = $request['payer_email'];
		$this->transaction_id = $request['txn_id'];

		$this->status = $request['payment_status'];
		$this->amount = $request['amount'];
		$this->currency = $request['currency_code'];

		if (isset($request['subscr_id'])) {
			$this->recurring_payment_id = $request['subscr_id'];
		}

		if (!$this->invoice->id) {
			$this->_logFailure('Invoice not found');

			header('HTTP/1.0 404 Not Found');
			header('Content-type: text/plain; charset=UTF-8');
			print 'Invoice not found';
			exit(0);
		}

		if (($this->currency != web_invoice_meta($this->invoice->id, 'web_invoice_currency_code'))) {
			$this->_logFailure('Invalid currency');

			header('HTTP/1.0 400 Bad Request');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were not expecting you. REF: MB0';
			exit(0);
		}
		if (($this->amount != $this->invoice->display('amount'))) {
			$this->_logFailure('Invalid amount');

			header('HTTP/1.0 400 Bad Request');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were not expecting you. REF: MB1';
			exit(0);
		}
		if (($this->pay_to_email != get_option('web_invoice_moneybookers_address')) && ($this->pay_to_email != get_option('web_invoice_moneybookers_recurring_address'))) {
			$this->_logFailure('Invalid pay_to_email');

			header('HTTP/1.0 400 Bad Request');
			header('Content-type: text/plain; charset=UTF-8');
			print 'We were not expecting you. REF: MB2';
			exit(0);
		}

		if ($this->status != 2) {
			if ($this->status == -2) {
				$this->_logSuccess('Payment failed (status)');
			}
			if ($this->status == -1) {
				$this->_logSuccess('Payment cancelled (status)');
			}
			if ($this->status == 0) {
				$this->_logSuccess('Payment pending (status)');
			}

			header('HTTP/1.0 200 OK');
			header('Content-type: text/plain; charset=UTF-8');
			print 'Thank you very much for letting us know. REF: Pending';
			exit(0);
		}

		$this->_logSuccess('Paid');

		web_invoice_mark_as_paid($this->invoice->id);

		header('HTTP/1.0 200 OK');
		header('Content-type: text/plain; charset=UTF-8');
		print 'Thank you very much for letting us know';
		exit(0);
	}
}


