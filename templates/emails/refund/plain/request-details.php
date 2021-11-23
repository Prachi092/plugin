<?php
/**
 * Order details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_email_before_order_table', $request, $sent_to_admin, $plain_text, $email );

echo wp_kses_post( wc_strtoupper( sprintf( __( '[Request #%1$s] (%2$s)', 'wc-vendors-gateway-stripe-connect' ), $request->get_id(), wc_format_datetime( $request->get_date_created() ) ) ) ) . "\n";

echo "\n" . wc_get_email_order_items(
	$request,
	array( // WPCS: XSS ok.
		'show_sku'      => false,
		'show_image'    => false,
		'image_size'    => array( 32, 32 ),
		'plain_text'    => true,
		'sent_to_admin' => $sent_to_admin,
	)
);

echo "==========\n\n";

$totals = $order->get_order_item_totals();

if ( $totals ) {
	foreach ( $totals as $total ) {
		echo wp_kses_post( $total['label'] . "\t " . $total['value'] ) . "\n";
	}
}

if ( $request->get_reason() ) {
	echo esc_html__( 'Reason:', 'wc-vendors-gateway-stripe-connect' ) . "\t " . wp_kses_post( wptexturize( $request->get_reason() ) ) . "\n";
}

if ( $sent_to_admin ) {
	/* translators: %s: Order link. */
	echo "\n" . sprintf( esc_html__( 'View request: %s', 'wc-vendors-gateway-stripe-connect' ), esc_url( $request->get_edit_url() ) ) . "\n";
}

do_action( 'woocommerce_email_after_order_table', $request, $sent_to_admin, $plain_text, $email );
