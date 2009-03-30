=== Web Invoice - Invoicing and billing for WordPress ===
Contributors: mohanjith
Donate link: http://mohanjith.com/wordpress
Tags: bill, moneybookers, alertpay, paypal, invoice, pay, online payment, send invoice, bill clients, authorize.net, credit cards, recurring billing, ARB
Requires at least: 2.6
Tested up to: 2.7.1
Stable tag: trunk

Web-Invoice lets you create and send web invoices and setup recurring billing for your clients.

== Description ==

Web Invoice lets WordPress blog owners send itemized invoices to their clients. Ideal for web developers, SEO consultants, general contractors, or anyone with a WordPress blog and clients to bill. The plugin ties into WP's user management database to keep track of your clients and their information.

Once an invoice is created from the WP admin section, an email with a brief description and a unique link is sent to client. Clients follow the link to your blog's special invoice page, view their invoice, and pay their bill using Moneybookers or PayPal. The control panel is very user-friendly and intuitive.

Credit card payments may be accepted via Authorize.net, MerchantPlus' NaviGate, Moneybookers, or PayPal account.  For recurring billing we have integrated Authorize.net's ARB API that will allow you to setup payment schedules along with invoices.

Some features:

* Create invoices from the WordPress control panel
* Prefill customer information using the WordPress user list
* Send invoice notifications to customers with a secured link back to the web invoice
* Accept credit card payment via Authorize.net or MerchantPlus NaviGate
* Moneybookers, AlertPay or PayPal available if you don't have a credit card processing account
* Setup recurring billing using Authorize.net's ARB (Automatic Recurring Billing) feature or Moneybookers
* Force web invoice pages to be viewed in SSL mode
* Archive old invoices
* Easily use old invoices as templates for new ones
* Dynamic and intuitive user interface
* Automatically mark invoices paid via Moneybookers as paid (Requires merchant status)
* Automatically mark invoices paid via AlertPay as paid (Requires business status)
* Split gateway support (Your client is given the option of choosing the preferred gateway from
  the list of gateways you support). e.g PayPal and Moneybookers
* Interfaces are internationalized

Would you like to see this plugin in other languages? Please show your interest in
the [Web Invoice community forum](http://mohanjith.com/forum/). You could also help us
translate this plugin to your language.

If you like this plugin please give it a good rating, and consider saying thanks or making a donation.

This is a fork of [WP-Invoice](http://wordpress.org/extend/plugins/wp-invoice/), however now lot of things have changed since.

== Installation ==

1. Upload `web-invoice` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Follow set-up steps on main 'Web Invoice' page
1. To create your first invoice navigate to 'Web Invoice' -> 'New Invoice', and select the user who will be the recipient

Please see the [Web Invoice plugin home page](http://mohanjith.com/wordpress/web-invoice.html) for details.

== Frequently Asked Questions ==

Please visit the [Web Invoice community forum](http://mohanjith.com/forum/) for suggestions and help.

== Screenshots ==

1. Invoice Overview
1. New Invoice Creation
1. Client Email Preview
1. Frontend Example

== Change Log ==

**Version 1.6.1**

* Show unit price when displaying quantity. Change cost to the product of quantity and unti price
* Use css label missing

**Version 1.6.0**

* Moneybookers recurring billing support

**Version 1.5.4**

* Currency symbol shown as html entity in the mailed invoice
* MB API interop fix

**Version 1.5.3**

* Fixed issue with street address, phone number and country of clients
  being reset every upgrade.
* Bug fixes (Due date)

**Version 1.5.2**

* Fixed display issue with MB

**Version 1.5.1**

* Added translations for en, en_US, en_GB
* Fixed issue with Moneybookers when there are more than 5 items
  and itemized details are sent to MB.
* Fixed issue with negative quantity or price in any payment
  processor

**Version 1.5.0**

* Internationalization

**Version 1.4.0**

* Split Gateway support

**Version 1.3.0**

* Add support for AlertPay
* Support AlertPay IPN (Similar to PayPal IPN)

**Version 1.2.4**

* Corrected typo (Reciept => Receipt)

**Version 1.2.3**

* Moneybookers API bug fixes (Using POST instead of GET)

**Version 1.2.2**

* Better debugging for Moneybookers API
* Send reminders
* Bug fixes from 1.2.1

**Version 1.2.1**

* Bug fixes from 1.2.0

**Version 1.2.0**

* Support Moneybookers API (Similar to PayPal IPN)

**Version 1.1.2**

* Made compatible with PHP4

**Version 1.1.1**

* Made compatible with PHP4
* When the invoice doesn't save, the MySQL error code is given along with
  other information.
* Bug fixes from 1.1.0

**Version 1.1.0**

* Using SQL to find the invoice id from the md5 hash
* Improved SQL queries for efficiency
* Halved number of queries

**Version 1.0.0**

* Initial release
