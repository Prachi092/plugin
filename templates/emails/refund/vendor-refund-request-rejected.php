<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( __( 'Requested #%d has been rejected. There are more detail bellow for your reference.', 'wc-vendors-gateway-stripe-connect' ), $request->get_id() ); ?></p>

<?php do_action( 'wcv_email_refund_request_details', $request, $sent_to_admin, $plain_text, $email ); ?>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
