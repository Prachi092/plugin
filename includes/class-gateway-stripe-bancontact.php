<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Stripe\Source;

/**
 * WC Vendors - Class that handles Bancontact payment method.
 *
 * @since 2.1.0
 */
class WCV_SC_Gateway_Stripe_Bancontact extends WCV_SC_Payment_Gateway {

	const ID = 'stripe-bancontact';

	/**
	 * Notices (array)
	 *
	 * @var array
	 */
	public $notices = [];

	/**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Alternate credit card statement name
	 *
	 * @var bool
	 */
	public $statement_descriptor;

	/**
	 * API access secret key
	 *
	 * @var string
	 */
	public $secret_key;

	/**
	 * Api access publishable key
	 *
	 * @var string
	 */
	public $publishable_key;

	/**
	 * Should we store the users credit cards?
	 *
	 * @var bool
	 */
	public $saved_cards;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id           = self::ID;
		$this->method_title = __( 'Stripe Bancontact', 'wc-vendors-gateway-stripe-connect' );
		/* translators: link */
		$this->method_description = sprintf( __( 'All other general Stripe settings can be adjusted <a href="%s">here</a>.', 'wc-vendors-gateway-stripe-connect' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=stripe' ) );
		$this->supports           = [
			'products',
			'refunds',
		];

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		$main_settings              = get_option( 'woocommerce_stripe-connect_settings', array() );
		$this->title                = $this->get_option( 'title' );
		$this->description          = $this->get_option( 'description' );
		$this->enabled              = $this->get_option( 'enabled' );
		$this->testmode             = ( ! empty( $main_settings['testmode'] ) && 'yes' === $main_settings['testmode'] ) ? true : false;
		$this->saved_cards          = ( ! empty( $main_settings['saved_cards'] ) && 'yes' === $main_settings['saved_cards'] ) ? true : false;
		$this->publishable_key      = ! empty( $main_settings['publishable_key'] ) ? $main_settings['publishable_key'] : '';
		$this->secret_key           = ! empty( $main_settings['secret_key'] ) ? $main_settings['secret_key'] : '';
		$this->statement_descriptor = ! empty( $main_settings['statement_descriptor'] ) ? $main_settings['statement_descriptor'] : '';

		if ( $this->testmode ) {
			$this->publishable_key = ! empty( $main_settings['test_publishable_key'] ) ? $main_settings['test_publishable_key'] : '';
			$this->secret_key      = ! empty( $main_settings['test_secret_key'] ) ? $main_settings['test_secret_key'] : '';
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] );
	}

	/**
	 * Returns all supported currencies for this payment method.
	 *
	 * @since 2.1.0
	 * 
	 * @return array
	 */
	public function get_supported_currency() {
		return apply_filters(
			'wcv_sc_stripe_bancontact_supported_currencies',
			[
				'EUR',
			]
		);
	}

	/**
	 * Checks to see if all criteria is met before showing payment method.
	 *
	 * @since 2.1.0
	 * 
	 * @return bool
	 */
	public function is_available() {
		if ( ! in_array( get_woocommerce_currency(), $this->get_supported_currency() ) ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Get_icon function.
	 *
	 * @since 2.1.0
	 * 
	 * @return string
	 */
	public function get_icon() {
		$icons = $this->payment_icons();

		$icons_str = '';

		$icons_str .= isset( $icons['bancontact'] ) ? $icons['bancontact'] : '';

		return apply_filters( 'wcv_sc_gateway_icon', $icons_str, $this->id );
	}

	/**
	 * Outputs scripts used for stripe payment
	 * 
	 * @since 2.1.0
	 */
	public function payment_scripts() {
		if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) && ! is_add_payment_method_page() ) {
			return;
		}

		wp_enqueue_style( 'wcv_sc_stripe_styles' );
		wp_enqueue_script( 'wcv_sc_woocommerce_stripe' );
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 * 
	 * @since 2.1.0
	 */
	public function init_form_fields() {
		$this->form_fields = require WCV_SC_PLUGIN_PATH . '/includes/admin/stripe-bancontact-settings.php';
	}

	/**
	 * Payment form on checkout page
	 * 
	 * @since 2.1.0
	 */
	public function payment_fields() {
		global $wp;
		$user        = wp_get_current_user();
		$total       = WC()->cart->total;
		$description = $this->get_description();

		// If paying from order, we need to get total from order not cart.
		if ( isset( $_GET['pay_for_order'] ) && ! empty( $_GET['key'] ) ) {
			$order = wc_get_order( wc_clean( $wp->query_vars['order-pay'] ) );
			$total = $order->get_total();
		}

		if ( is_add_payment_method_page() ) {
			$pay_button_text = __( 'Add Payment', 'wc-vendors-gateway-stripe-connect' );
			$total           = '';
		} else {
			$pay_button_text = '';
		}

		echo '<div
			id="stripe-bancontact-payment-data"
			data-amount="' . esc_attr( WCV_SC_Helper::get_stripe_amount( $total ) ) . '"
			data-currency="' . esc_attr( strtolower( get_woocommerce_currency() ) ) . '">';

		if ( $description ) {
			echo apply_filters( 'wcv_sc_stripe_description', wpautop( wp_kses_post( $description ) ), $this->id );
		}

		echo '</div>';
	}

	/**
	 * Creates the source for charge.
	 *
	 * @since 2.1.0
	 * 
	 * @param object $order
	 * @return mixed
	 */
	public function create_source( $order ) {
		$currency                = $order->get_currency();
		$return_url              = $this->get_stripe_return_url( $order );
		$post_data               = [];
		$post_data['amount']     = WCV_SC_Helper::get_stripe_amount( $order->get_total(), $currency );
		$post_data['currency']   = strtolower( $currency );
		$post_data['type']       = 'bancontact';
		$post_data['owner']      = (array) $this->get_owner_details( $order );
		$post_data['redirect']   = [ 'return_url' => $return_url ];
		$post_data['bancontact'] = [ 'preferred_language' => $this->get_locale() ];

		if ( ! empty( $this->statement_descriptor ) ) {
			$post_data['statement_descriptor'] = WCV_SC_Helper::clean_statement_descriptor( $this->statement_descriptor );
		}

		WCV_SC_Logger::log( 'Info: Begin creating Bancontact source' );

		return Source::create( apply_filters( 'wcv_sc_stripe_bancontact_source', $post_data, $order ) );
	}

	/**
	 * Process the payment
	 *
	 * @param int  $order_id Reference.
	 * @param bool $retry Should we retry on fail.
	 * @param bool $force_save_source Force payment source to be saved.
	 *
	 * @throws Exception If payment will not be accepted.
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id, $retry = true, $force_save_source = false ) {
		try {
			$order = wc_get_order( $order_id );

			// This will throw exception if not valid.
			$this->validate_minimum_order_amount( $order );

			// This comes from the create account checkbox in the checkout page.
			$create_account = ! empty( $_POST['createaccount'] ) ? true : false;

			if ( $create_account ) {
				$new_customer_id     = $order->get_customer_id();
				$new_stripe_customer = new WCV_SC_Customer( $new_customer_id );
				$new_stripe_customer->create_customer();
			}

			$response = $this->create_source( $order );

			if ( ! empty( $response->error ) ) {
				$order->add_order_note( $response->error->message );

				throw new WCV_SC_Exception( print_r( $response, true ), $response->error->message );
			}

			$order->update_meta_data( '_stripe_source_id', $response->id );
			$order->save();

			WCV_SC_Logger::log( 'Info: Redirecting to Bancontact...' );

			return [
				'result'   => 'success',
				'redirect' => esc_url_raw( $response->redirect->url ),
			];
		} catch ( WCV_SC_Exception $e ) {
			wc_add_notice( $e->getLocalizedMessage(), 'error' );
			WCV_SC_Logger::log( 'Error: ' . $e->getMessage() );

			do_action( 'wc_gateway_stripe_process_payment_error', $e, $order );

			if ( $order->has_status(
				apply_filters(
					'wc_stripe_allowed_payment_processing_statuses',
					[ 'pending', 'failed' ]
				)
			) ) {
				$this->send_failed_order_email( $order_id );
			}

			return [
				'result'   => 'fail',
				'redirect' => '',
			];
		}
	}
}
