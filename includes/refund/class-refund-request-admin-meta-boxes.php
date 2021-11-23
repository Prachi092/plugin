<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Refund Request Admin Meta Boxes.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

/**
 * WC Vendors - Stripe Commissions & Gateway Refund Request Admin Meta Boxes.
 *
 * @since 2.0.0
 */
class WCV_SC_Refund_Request_Admin_Meta_Boxes {
	/**
	 * Constructor.
	 *
	 * @since  2.0.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  2.0.0
	 */
	public function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 35 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 40 );
	}

	/**
	 * Removes Metaboxs
	 */
	public function remove_meta_boxes() {
		remove_meta_box( 'commentstatusdiv', 'wcv_refund_request', 'normal' );
		remove_meta_box( 'woocommerce-order-data', 'wcv_refund_request', 'normal' );
		remove_meta_box( 'woocommerce-order-actions', 'wcv_refund_request', 'normal' );
		remove_meta_box( 'woocommerce-order-downloads', 'wcv_refund_request', 'normal' );
		// remove_meta_box( 'woocommerce-order-items', 'wcv_refund_request', 'normal' );
	}

	/**
	 * Add WC Meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box( 'wcvendors-refund-request-data', _x( 'Refund Request Data', 'meta box title', 'wc-vendors-gateway-stripe-connect' ), 'WCV_SC_Meta_Box_Refund_Request_Data::output', 'wcv_refund_request', 'normal', 'high' );
	}
}
