<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Intent Controller.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

/**
 * WC Vendors - Stripe Commissions & Gateway Intent Controller.
 *
 * @since 2.0.0
 */
class WCV_SC_Intent_Controller {
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
		add_action( 'wc_ajax_wcv_sc_confirm_intent', array( $this, 'confirm_intent' ) );
	}

	public function confirm_intent() {
		global $woocommerce;

		$_REQUEST['wc_sc_confirm_intent_nonce'] = sanitize_text_field( wp_unslash( wp_create_nonce( 'wc_sc_confirm_intent' ) ) );

		$gateway = $this->get_gateway();

		try {
			$order = $this->get_order_from_request();
		} catch ( WCV_SC_Exception $e ) {
			/* translators: Error message text */
			$message = sprintf( __( 'Payment verification error: %s', 'woocommerce-gateway-stripe' ), $e->getLocalizedMessage() );
			wc_add_notice( esc_html( $message ), 'error' );

			$redirect_url = $woocommerce->cart->is_empty()
				? get_permalink( woocommerce_get_page_id( 'shop' ) )
				: wc_get_checkout_url();

			$this->handle_error( $e, $redirect_url );
		}

		try {
			$gateway->confirm_intent_after_checkout( $order );

			if ( ! isset( $_GET['is_ajax'] ) ) {
				$redirect_url = isset( $_GET['redirect_to'] ) // wpcs: csrf ok.
					? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) // wpcs: csrf ok.
					: $gateway->get_return_url( $order );

				wp_safe_redirect( $redirect_url );
			}

			exit;
		} catch ( WCV_SC_Exception $e ) {
			$this->handle_error( $e, $gateway->get_return_url( $order ) );
		}
	}

	protected function get_gateway() {
		if ( ! isset( $this->gateway ) ) {
			if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) ) {
				$class_name = 'WCV_SC_Gateway_Stripe_Connect_Subs';
			} else {
				$class_name = 'WCV_SC_Gateway_Stripe_Connect';
			}

			$this->gateway = new $class_name();
		}

		return $this->gateway;
	}

	/**
	 * @since  2.0.0
	 */
	protected function get_order_from_request() {
		if (
			isset( $_GET['confirm_pi_nonce'] )
			&& ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['confirm_pi_nonce'] ) ), 'wc_stripe_confirm_pi' )
			&&
			isset( $_REQUEST['wc_sc_confirm_intent_nonce'] )
			&& ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['wc_sc_confirm_intent_nonce'] ) ), 'wc_sc_confirm_intent' )
		) {
			throw new WCV_SC_Exception( 'missing-nonce', __( 'CSRF verification failed.', 'wc-vendors-gateway-stripe-connect' ) );
		}

		// Load the order ID.
		$order_id = null;
		if ( isset( $_GET['order'] ) && absint( $_GET['order'] ) ) {
			$order_id = absint( $_GET['order'] );
		}

		// Retrieve the order.
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			throw new WCV_SC_Exception( 'missing-order', __( 'Missing order ID for payment confirmation', 'wc-vendors-gateway-stripe-connect' ) );
		}

		return $order;
	}

	protected function handle_error( $e, $redirect_url ) {
		$message = sprintf( 'PaymentIntent verification exception: %s', $e->getLocalizedMessage() );
		WCV_SC_Logger::log( $message );

		if ( isset( $_GET['is_ajax'] ) ) {
			exit;
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}
}
