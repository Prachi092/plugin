<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Stripe connect template
 *
 * @version 2.0.0
 */
?>

<div id="stripe-connect-vendor" class="wcv_stripe_connect_container">

<?php if ( ! $stripe_connect_access_key || ! $stripe_connect_user_id ) : ?>

<p><strong><?php _e( 'Connect with Stripe', 'wc-vendors-gateway-stripe-connect' ); ?></strong><br />
	<?php _e( 'Your account is not yet connected with Stripe.', 'wc-vendors-gateway-stripe-connect' ); ?><br />
	<?php echo $vendor_note; ?><br />
<a href="<?php echo $connect_url; ?>" class="<?php echo $button_theme_css; ?>"><span><?php _e( 'Connect with Stripe', 'wc-vendors-gateway-stripe-connect' ); ?></span></a></p>

<?php else : ?>

<p><strong><?php _e( 'Your account is currently connected to Stripe.', 'wc-vendors-gateway-stripe-connect' ); ?></strong><br />
<a href="<?php echo esc_url( get_permalink( get_option( 'wcvendors_shop_settings_page_id' ) ) ); ?>?deauth=<?php echo $stripe_connect_user_id; ?>" class="<?php echo $button_theme_css; ?>"><span><?php _e( 'Click here to disconnect your Stripe account.', 'wc-vendors-gateway-stripe-connect' ); ?></span></a>
</p>

<?php endif; ?>

</div>
