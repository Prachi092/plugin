<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Vendors - Stripe Commissions & Gateway Exception.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 *
 * Based on class WC_Stripe_Exception from WooCommerce Stripe plugin - https://wordpress.org/plugins/woocommerce-gateway-stripe/
 *
 */
class WCV_SC_Exception extends Exception {

	/** @var string sanitized/localized error message */
	protected $localized_message;

	/**
	 * Setup exception
	 *
	 * @since 4.0.2
	 * @param string $error_message Full response
	 * @param string $localized_message user-friendly translated error message
	 */
	public function __construct( $error_message = '', $localized_message = '' ) {
		$this->localized_message = $localized_message;
		parent::__construct( $error_message );
	}

	/**
	 * Returns the localized message.
	 *
	 * @since 4.0.2
	 * @return string
	 */
	public function getLocalizedMessage() {
		return $this->localized_message;
	}
}
