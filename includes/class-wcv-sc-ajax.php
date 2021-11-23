<?php
/**
 * Custom ajax endpoints handler
 *
 * @package    WCV_Gateway_Stripe_Connect_V2
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax handler class
 *
 * @version 2.1.0
 * @since   2.1.0
 */
class WCV_SC_Ajax {
	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since   2.1.0
	 * @version 2.1.0
	 */
	private static $instance = null;

	/**
	 * Get single instance.
	 *
	 * @since   2.1.0
	 * @version 2.1.0
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since   2.1.0
	 * @version 2.1.0
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'add_endpoint' ) );
		add_action( 'template_redirect', array( $this, 'do_ajax' ), 0 );

		add_action( 'wcv_sc_ajax_create_checkout_session', array( $this, 'create_checkout_session') );
	}

	/**
	 * Add custom endpoint for frontend Ajax requests.
	 *
	 * @since   2.1.0
	 * @version 2.1.0
	 */
	public static function add_endpoint() {
		add_rewrite_tag( '%wcv-sc-ajax%', '([^/]*)' );
		add_rewrite_rule( 'wcv-sc-ajax/([^/]*)/?', 'index.php?wcv-sc-ajax=$matches[1]', 'top' );
		add_rewrite_rule( 'index.php/wcv-sc-ajax/([^/]*)/?', 'index.php?wcv-sc-ajax=$matches[1]', 'top' );
	}

	/**
	 * Handle ajax requests
	 *
	 * @return void
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public function do_ajax() {
		global $wp_query;

		if ( isset( $_REQUEST['wcv-sc-ajax'] ) && ! empty( $_REQUEST['wcv-sc-ajax'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Input is used safely.
			$wp_query->set( 'wcv-sc-ajax', sanitize_text_field( wp_unslash( $_REQUEST['wcv-sc-ajax'] ) ) );
		}

		$action = $wp_query->get( 'wcv-sc-ajax' );

		if ( ! $action ) {
			return;
		}

		// Since his is an ajax endpoint, set is_home to false.
		$wp_query->is_home = false;

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		/**
		 * Handle ajax requests
		 *
		 * Fires non authenticated Ajax actions for logged-out users.
		 *
		 * @version 2.1.0
		 * @since   2.1.0
		*/
		do_action( 'wcv_sc_ajax_' . sanitize_text_field( $action ) );
		wp_die();
	}

	/**
	 * Get Ajax Endpoint.
	 *
	 * @since   2.1.0
	 * @version 2.1.0
	 *
	 * @param  string $request      Optional endpoint.
	 * @return string
	 */
	public static function get_endpoint( $request = '%%endpoint%%' ) {
		if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {
			$endpoint = trailingslashit( home_url( '/index.php/wcv-sc-ajax/' . $request . '/', 'relative' ) );
		} elseif ( get_option( 'permalink_structure' ) ) {
			$endpoint = trailingslashit( home_url( '/wcv-sc-ajax/' . $request . '/', 'relative' ) );
		} else {
			$endpoint = add_query_arg( 'wcv-sc-ajax', $request, home_url( '/', 'relative' ) );
		}

		return esc_url_raw( $endpoint );
	}

	/**
	 * Create checkout session from ajax request.
	 *
	 * @return void
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public function create_checkout_session() {
		global $wp_query;

		$action = $wp_query->get( 'wcv-sc-ajax' );

		if ( 'create_checkout_session' != $action ) {
			return;
		}

		if (
			! isset( $_REQUEST['nonce'] ) ||
			isset( $_REQUEST['nonce'] ) &&
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'create_checkout_session' )
		) {
			wp_send_json_error(
				array(
					'error'   => true,
					'message' => __( 'We failed to process your order. Please refresh the page and try again.', 'wc-vendors-gateway-stripe-connect')
				)
			);
		}

		$require_customer_login = apply_filters( 'wcvendors_stripe_connect_require_logged_in_customer', true );

		if ( $require_customer_login && ! is_user_logged_in() && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			wp_send_json_error(
				array(
					'error' => true,
					'message' => __( 'You must be logged in to purchase a subscription. Please login to complete your order.', 'wc-vendors-gateway-stripe-connect' )
				),
			);
		}

		$_checkout_data = isset( $_REQUEST['checkout_data'] ) ? wp_unslash( $_REQUEST['checkout_data'] ) : array();
		$checkout_data  = wp_parse_args( $_checkout_data );
		$checkout_data  = apply_filters( 'wcv_sc_checkout_data', $checkout_data );

		$checkout = WC_Checkout::instance();
		$order_id = $checkout->create_order( $checkout_data );

		do_action( 'wcvendors_stripe_connect_checkout_order_created', $order_id, $checkout_data );

		$session = WCV_SC_Helper::create_session( $order_id );

		do_action( 'wcv_sc_checkout_session_created', $session );

		if ( is_object( $session ) && $session->id ) {
			wp_send_json_success( $session );
		}

		wp_send_json_error(
			array(
				'error' => true,
				'message' => __( 'Failed to create checkout session. Please reload the page and try again.', 'wc-vendors-gateway-stripe-connect' )
			),
		);
	}
}

WCV_SC_Ajax::instance();
