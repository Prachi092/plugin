# WC Vendors - Stripe Commissions & Gateway #
**Contributors:**      Jamie Madden (jamie@wcvendors.com)
**Donate link:**       https://www.wcvendors.com/product/wc-vendors-stripe-connect
**Tags:**
**Requires at least:** 5.3.0
**Tested up to:**      5.8.2
**Stable tag:**        2.1.0
**License:**           GPLv2
**License URI:**       http://www.gnu.org/licenses/gpl-2.0.html

## Description ##

Take credit card payments and payout your vendor commissions with Stripe Connect.

## Installation ##

### Manual Installation ###

1. Upload the entire `/wc-vendors-gateway-stripe-connect` directory to the `/wp-content/plugins/` directory.
2. Activate WC Vendors - Stripe Commissions & Gateway through the 'Plugins' menu in WordPress.

## Frequently Asked Questions ##

1. What Stripe account types are supported?

## Screenshots ##


## Changelog ##

### Version 2.1.0 - 21st July 2021 ###

* Updated: Make all shipping lines with vendor items refundable by default
* Updated: Option for stripe fee split for vendors on separate transfers payments #74
* Updated: Tested WC Versions
* Fixed: Can not reusable source #99
* Fixed: Two text domains #101
* Fixed: Can not reusable source #99
* Fixed: While using WC Vendors Marketplace with WC Vendors Stripe Connect, "Click here to disconnect your Stripe account" link in WP dashboard does not work #91
* Fixed: Stripe checkout shows even when not selected payment method 
* Fixed: Order level fees not being captured during payment #87
* Fixed: Incorrect text domain
* Fixed: Incorrect stripe fee for vendors on separate transfers payments #74
* Fixed: CSRF Verification fails 3d Secure with Separate Charges & Transfers and generating accounts
* Fixed: Incorrect stripe fee for vendors on separate transfers payments #74

### Version 2.0.8 - 17th June 2020 ###

* Fixed: Direct charges error with more than 1 vendor in cart for new Stripe accounts #70

### Version 2.0.7 - 9th April 2020 ###

* Added: Transfer description #63
* Added: Stripe API Version to api calls
* Added: CSS id to custom page
* Added: Source transaction to Transfer args
* Fixed: Separate Charges and Transfers automatic commission transfer #64
* Fixed: Force Disconnect White screen #48
* Fixed: Redirect url
* Updated: text
* Updated: Plugin prefix

### Version 2.0.6 - 21th December 2019 ###

* Fixed: Can not continue checkout if entering wrong CVC #61
* Fixed: Saving card issue for logged in customer.
* Fixed: Duplicated email classes declaration.

### Version 2.0.5 - 12th December 2019 ###
* Fixed: Anonymous checkout.

### Version 2.0.4 - 7th December 2019 ###

* Fixed: Attached to the customer beforehand #13 (#58)
* Fixed: Order Actions in WP-Admin empty #55 (#56)

### Version 2.0.3 - 6th December 2019 ###

* Added: Setting to disable refund #43.
* Added: Allow using Stripe Default redirect URI.
* Fixed: Commission recording issue.
* Fixed: Force disconnect redirects to incorrect dashboard #34.
* Fixed: Direct Charge: Refund failed if the commission is set to 100% #39.
* Updated: Improving the vendor refund process #42.
* Updated: Improving plugin log.
* Updated: Remove the webhook setting.

### Version 2.0.2 - 4th October 2019 ###

* Fixed: Checkout error when the commission is disabled.

### Version 2.0.1 - 27th September 2019 ### 

* Added: Displaying exact redirect URI in setting page.
* Fixed: Log doesn't work.
* Fixed: Can't switch to live mode.
* Fixed: Connect error when the marketplace use Separate Charges and Transfers.

### 2.0.0 ###

* Complete re-write


### 1.0.5 - 14th March 2017 ###

* Added: WooCommerce 3.0 compatibility
* Added: Support for non decimal currencies #17
* Added: Process_payment filters and actions #16
* Added: French translations

### 1.0.4 - 23rd August 2016 ###

* Added: Logging system with option to enable or disable
* Added: Filter to change where disconnect stripe url goes
* Fixed: Mark vendors paid for multiple products
* Fixed: Charges in Stripe are higher than in Woocommerce #15

### 1.0.3 - 22nd March 2016 ###

* Added: Default language file for translations with text domain wcv_stripe_connect
* Fixed: Marks all commissions as "paid" #10
* Fixed: Changed how stripe initialises #11
* Fixed: Commissions Paid to Admin wrong #12
* Fixed: Stripe's Saved Card info not displaying correctly #9
* Fixed: Check commission paid on per product basis
* Fixed: Added checks to only fire when required

### 1.0.2 - 21st September 2015 ###

 * Added: da_DK Danish Translation (Thanks Bjorn) #5
 * Updated: Credit card icons on checkout page will now only show credit cards that your country accepts (Thanks Bjorn) #6

### 1.0.1 - 31st August 2015 ###

* Now supports all 135 currencies that Stripe supports #4

### 1.0.0 - 6th July 2015 ###

* Initial release

## Upgrade Notice ##

### 2.0.0 ###

This is a complete re-write, you will need to re-add your test and live credentials and ensure all settings are as you require them. There are new payment methods and options in this release.
