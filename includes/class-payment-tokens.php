<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Payment Tokens.
 *
 *
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */
class WCV_SC_Payment_Tokens {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.0.0
	 *
	 * @var   WC_Vendors_Stripe_Commissions_Gateway
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  2.0.0
	 *
	 * @param  WC_Vendors_Stripe_Commissions_Gateway $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  2.0.0
	 */
	public function hooks() {

		add_filter( 'woocommerce_get_customer_payment_tokens', array( $this, 'woocommerce_get_customer_payment_tokens' ), 10, 3 );
		add_action( 'woocommerce_payment_token_deleted', array( $this, 'woocommerce_payment_token_deleted' ), 10, 2 );
		add_action( 'woocommerce_payment_token_set_default', array( $this, 'woocommerce_payment_token_set_default' ) );
	}

	/**
	 * Gets saved tokens from API if they don't already exist in WooCommerce.
	 *
	 * @since 2.0.0
	 * @param array $tokens
	 * @return array
	 */
	public function woocommerce_get_customer_payment_tokens( $tokens = array(), $customer_id, $gateway_id ) {

		if ( is_user_logged_in() && class_exists( 'WC_Payment_Token_CC' ) ) {

			$stored_tokens = array();

			foreach ( $tokens as $token ) {
				$stored_tokens[] = $token->get_token();
			}

			if ( 'stripe-connect' === $gateway_id ) {
				$stripe_customer = new WCV_SC_Customer( $customer_id );
				$stripe_sources  = $stripe_customer->get_sources();

				foreach ( $stripe_sources as $source ) {
					if ( isset( $source->type ) && 'card' === $source->type ) {
						if ( ! in_array( $source->id, $stored_tokens ) ) {
							$token = new WC_Payment_Token_CC();
							$token->set_token( $source->id );
							$token->set_gateway_id( 'stripe-connect' );

							if ( 'source' === $source->object && 'card' === $source->type ) {
								$token->set_card_type( strtolower( $source->card->brand ) );
								$token->set_last4( $source->card->last4 );
								$token->set_expiry_month( $source->card->exp_month );
								$token->set_expiry_year( $source->card->exp_year );
							}

							$token->set_user_id( $customer_id );
							$token->save();
							$tokens[ $token->get_id() ] = $token;
						}
					} else {
						if ( ! in_array( $source->id, $stored_tokens ) && 'card' === $source->object ) {
							$token = new WC_Payment_Token_CC();
							$token->set_token( $source->id );
							$token->set_gateway_id( 'stripe-connect' );
							$token->set_card_type( strtolower( $source->brand ) );
							$token->set_last4( $source->last4 );
							$token->set_expiry_month( $source->exp_month );
							$token->set_expiry_year( $source->exp_year );
							$token->set_user_id( $customer_id );
							$token->save();
							$tokens[ $token->get_id() ] = $token;
						}
					}
				}
			}
		}

		return $tokens;
	}

	/**
	 * Delete token from Stripe.
	 *
	 * @since 2.0.0
	 */
	public function woocommerce_payment_token_deleted( $token_id, $token ) {
		if ( 'stripe-connect' === $token->get_gateway_id() ) {
			$stripe_customer = new WCV_SC_Customer( get_current_user_id() );
			$stripe_customer->delete_source( $token->get_token() );
		}
	}

	/**
	 * Set as default in Stripe.
	 *
	 * @since 2.0.0
	 */
	public function woocommerce_payment_token_set_default( $token_id ) {
		$token = WC_Payment_Tokens::get( $token_id );

		if ( 'stripe-connect' === $token->get_gateway_id()  ) {
			$stripe_customer = new WCV_SC_Customer( get_current_user_id() );
			$stripe_customer->set_default_source( $token->get_token() );
		}
	}

}
