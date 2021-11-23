<fieldset>

<?php if ( $this->description ) : ?>
	<p><?php echo $this->description; ?>
		<?php if ( $this->testmode == 'yes' ) : ?>
			<?php _e( 'TEST MODE ENABLED. In test mode, you can use the card number 4242424242424242 with any CVC and a valid expiration date.', 'wc-vendors-gateway-stripe-connect' ); ?>
		<?php endif; ?></p>
<?php endif; ?>

<?php if ( is_user_logged_in() && ( $this->saved_cards ) ) : ?>
	<p class="form-row form-row-wide">

		<a class="button" style="float:right;" href="<?php echo get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ); ?>#saved-cards"><?php _e( 'Manage cards', 'wc-vendors-gateway-stripe-connect' ); ?></a>

		<?php foreach ( $credit_cards as $i => $credit_card ) : ?>
			<input type="radio" id="stripe_card_<?php echo $i; ?>" name="stripe_customer_id" style="width:auto;" value="<?php echo $i; ?>" />
			<label style="display:inline;" for="stripe_card_<?php echo $i; ?>"><?php _e( 'Card ending with', 'wc-vendors-gateway-stripe-connect' ); ?> <?php echo $credit_card['active_card']; ?> (<?php echo $credit_card['exp_month'] . '/' . $credit_card['exp_year'] ?>)</label><br />
		<?php endforeach; ?>

		<input type="radio" id="new" name="stripe_customer_id" style="width:auto;" <?php checked( 1, 1 ) ?> value="new" /> <label style="display:inline;" for="new"><?php _e( 'Use a new credit card', 'wc-vendors-gateway-stripe-connect' ); ?></label>

	</p>
	<div class="clear"></div>
<?php endif; ?>

<div class="stripe_new_card">

	<?php if ( $this->stripe_checkout ) : ?>
		<a id="stripe_payment_button" class="button" href="#"
			data-description=""
			data-amount="<?php echo WC()->cart->total * $this->multiplier; ?>"
			data-name="<?php echo sprintf( __( '%s', 'woocommerce' ), get_bloginfo( 'name' ) ); ?>"
			data-label="<?php _e( 'Confirm and Pay', 'woocommerce' ); ?>"
			data-currency="<?php echo strtoupper( get_woocommerce_currency() ); ?>"
			><?php _e( 'Enter payment details', 'woocommerce' ); ?></a>
	<?php else : ?>
		<p class="form-row form-row-wide">
			<label for="stripe_card_number"><?php _e( "Credit Card number", 'wc-vendors-gateway-stripe-connect' ); ?> <span class="required">*</span></label>
			<input type="number" autocomplete="off" id="stripe_card_number" class="input-text card-number" />
		</p>
		<div class="clear"></div>
		<p class="form-row form-row-first">
			<label for="cc-expire-month"><?php _e( "Expiration date", 'wc-vendors-gateway-stripe-connect' ) ?> <span class="required">*</span></label>
			<select id="cc-expire-month" class="woocommerce-select woocommerce-cc-month card-expiry-month">
				<option value=""><?php _e( 'Month', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="01"><?php _e( 'January', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="02"><?php _e( 'February', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="03"><?php _e( 'March', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="04"><?php _e( 'April', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="05"><?php _e( 'May', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="06"><?php _e( 'June', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="07"><?php _e( 'July', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="08"><?php _e( 'August', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="09"><?php _e( 'September', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="10"><?php _e( 'October', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="11"><?php _e( 'November', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<option value="12"><?php _e( 'December', 'wc-vendors-gateway-stripe-connect' ) ?></option>
			</select>

			<select id="cc-expire-year" class="woocommerce-select woocommerce-cc-year card-expiry-year">
				<option value=""><?php _e( 'Year', 'wc-vendors-gateway-stripe-connect' ) ?></option>
				<?php
					for ( $i = date( 'y' ); $i <= date( 'y' ) + 15; $i++ ) printf( '<option value="20%u">20%u</option>', $i, $i );
				?>
			</select>
		</p>
		<p class="form-row form-row-last">
			<label for="stripe_card_csc"><?php _e( "Card security code", 'wc-vendors-gateway-stripe-connect' ) ?> <span class="required">*</span></label>
			<input type="text" id="stripe_card_csc" maxlength="4" style="width:4em;" autocomplete="off" class="input-text card-cvc" />
			<span class="help stripe_card_csc_description"></span>
		</p>
		<div class="clear"></div>
	<?php endif; ?>

</div>

</fieldset>
