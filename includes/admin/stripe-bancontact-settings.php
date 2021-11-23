<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters(
	'wcv_sc_bancontact_settings',
	[
		'geo_target'  => [
			'description' => __( 'Customer Geography: Belgium', 'wc-vendors-gateway-stripe-connect' ),
			'type'        => 'title',
		],
		'guide'       => [
			'description' => __( '<a href="https://stripe.com/payments/payment-methods-guide#bancontact" target="_blank">Payment Method Guide</a>', 'wc-vendors-gateway-stripe-connect' ),
			'type'        => 'title',
		],
		'activation'  => [
			'description' => __( 'Must be activated from your Stripe Dashboard Settings <a href="https://dashboard.stripe.com/account/payments/settings" target="_blank">here</a>', 'wc-vendors-gateway-stripe-connect' ),
			'type'        => 'title',
		],
		'enabled'     => [
			'title'       => __( 'Enable/Disable', 'wc-vendors-gateway-stripe-connect' ),
			'label'       => __( 'Enable Stripe Bancontact', 'wc-vendors-gateway-stripe-connect' ),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no',
		],
		'title'       => [
			'title'       => __( 'Title', 'wc-vendors-gateway-stripe-connect' ),
			'type'        => 'text',
			'description' => __( 'This controls the title which the user sees during checkout.', 'wc-vendors-gateway-stripe-connect' ),
			'default'     => __( 'Bancontact', 'wc-vendors-gateway-stripe-connect' ),
			'desc_tip'    => true,
		],
		'description' => [
			'title'       => __( 'Description', 'wc-vendors-gateway-stripe-connect' ),
			'type'        => 'text',
			'description' => __( 'This controls the description which the user sees during checkout.', 'wc-vendors-gateway-stripe-connect' ),
			'default'     => __( 'You will be redirected to Bancontact.', 'wc-vendors-gateway-stripe-connect' ),
			'desc_tip'    => true,
		],
	]
);
