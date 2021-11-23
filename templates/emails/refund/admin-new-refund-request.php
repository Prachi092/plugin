<?php
/**
 * Admin new refund request email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-new-order.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( __( '%1$s has requested a refund for order #%2$d. There are more detail bellow for your reference.', 'wc-vendors-gateway-stripe-connect' ), WCV_Vendors::get_vendor_shop_name( $vendor_id ), $request->get_parent_id() ); ?></p>

<?php do_action( 'wcv_email_refund_request_details', $request, $sent_to_admin, $plain_text, $email ); ?>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
