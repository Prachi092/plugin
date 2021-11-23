<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Display the Stripe elements credit card form
 * 
 * @version 2.0.0
 */
?>

<fieldset id="wcv-<?php echo esc_attr( $this->id ); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
	<?php do_action( 'wcvendors_credit_card_form_start', $this->id ); ?>

	<?php if ( $this->inline_cc_form ) { ?>
		<label for="card-element">
			<?php esc_html_e( 'Credit or debit card', 'wc-vendors-gateway-stripe-connect' ); ?>
		</label>

		<div id="stripe-connect-card-element" class="wcv-stripe-connect-elements-field">
		<!-- a Stripe Element will be inserted here. -->
		</div>
	<?php } else { ?>
		<div class="form-row form-row-wide">
			<label for="stripe-connect-card-element"><?php esc_html_e( 'Card Number', 'wc-vendors-gateway-stripe-connect' ); ?> <span class="required">*</span></label>
			<div class="stripe-card-group">
				<div id="stripe-connect-card-element" class="wcv-stripe-connect-elements-field">
				<!-- a Stripe Element will be inserted here. -->
				</div>

				<i class="stripe-credit-card-brand stripe-card-brand" alt="Credit Card"></i>
			</div>
		</div>

		<div class="form-row form-row-first">
			<label for="stripe-connect-exp-element"><?php esc_html_e( 'Expiry Date', 'wc-vendors-gateway-stripe-connect' ); ?> <span class="required">*</span></label>

			<div id="stripe-connect-exp-element" class="wcv-stripe-connect-elements-field">
			<!-- a Stripe Element will be inserted here. -->
			</div>
		</div>

		<div class="form-row form-row-last">
			<label for="stripe-connect-cvc-element"><?php esc_html_e( 'Card Code (CVC)', 'wc-vendors-gateway-stripe-connect' ); ?> <span class="required">*</span></label>
		<div id="stripe-connect-cvc-element" class="wcv-stripe-connect-elements-field">
		<!-- a Stripe Element will be inserted here. -->
		</div>
		</div>
		<div class="clear"></div>
	<?php } ?>

	<!-- Used to display form errors -->
	<div class="stripe-connect-source-errors" role="alert"></div>
	<br />
	<?php do_action( 'wcvendors_credit_card_form_end', $this->id ); ?>
	<div class="clear"></div>
</fieldset>
