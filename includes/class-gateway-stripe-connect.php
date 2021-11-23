<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Gateway Stripe Connect.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 *
 * Based on class WC_Gateway_Stripe from WooCommerce Stripe plugin - https://wordpress.org/plugins/woocommerce-gateway-stripe/
 */
class WCV_SC_Gateway_Stripe_Connect extends WCV_SC_Payment_Gateway {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.0.0
	 *
	 * @var   WC_Vendors_Stripe_Commissions_Gateway
	 */
	protected $plugin = null;

	/**
	 * WC_Logger Logger instance
	 */
	protected $log = false;

		/**
		 * The delay between retries.
		 *
		 * @var int
		 */
	public $retry_interval;

	/**
	 * Should we capture Credit cards
	 *
	 * @var bool
	 */
	public $capture;

	/**
	 * Checkout enabled
	 *
	 * @var bool
	 */
	public $stripe_checkout;

	/**
	 * The type of checkout to use
	 *
	 * @var string
	 * @version 2.1
	 * @since   2.1
	 */
	public $checkout_type;

	/**
	 * Stripe Checkout description.
	 *
	 * @var string
	 */
	public $stripe_checkout_description;

	/**
	 * Require 3D Secure enabled
	 *
	 * @var bool
	 */
	public $three_d_secure;

	/**
	 * Credit card image
	 *
	 * @var string
	 */
	public $stripe_checkout_image;

	/**
	 * Should we store the users credit cards?
	 *
	 * @var bool
	 */
	public $saved_cards;

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
	 * Stripe connect client ID.
	 *
	 * @var string
	 */
	public $client_id;

	/**
	 * Do we accept Payment Request?
	 *
	 * @var bool
	 */
	public $payment_request;

	/**
	 * Is test mode active?
	 *
	 * @var bool
	 */
	public $testmode;

	/**
	 * Inline CC form styling
	 *
	 * @var string
	 */
	public $inline_cc_form;

	/**
	 * Pre Orders Object
	 *
	 * @var object
	 */
	public $pre_orders;

	/**
	 * Constructor.
	 *
	 * @since  2.0.0
	 *
	 * @param  WC_Vendors_Stripe_Commissions_Gateway $plugin Main plugin object.
	 */
	public function __construct() {
		$this->id                 = 'stripe-connect';
		$this->method_title       = __( 'Stripe Connect', 'wc-vendors-gateway-stripe-connect' );
		$this->has_fields         = true;
		$this->retry_interval     = 1;
		$this->supports           = array(
			'products',
			'refunds',
			'tokenization',
			'add_payment_method',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'multiple_subscriptions',
		);
		$this->method_description = sprintf( __( 'Stripe Connect works by adding payment fields on the checkout and then sending the details to Stripe for verification and then payment split with vendors.  <a href="%1$s" target="_blank">Sign up</a> for a Stripe account, and <a href="%2$s" target="_blank">connect your application</a>.', 'wc-vendors-gateway-stripe-connect' ), 'https://dashboard.stripe.com/register', 'https://dashboard.stripe.com/account/apikeys' );

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values
		$this->title                       = $this->get_option( 'title' );
		$this->description                 = $this->get_option( 'description' );
		$this->enabled                     = $this->get_option( 'enabled' );
		$this->testmode                    = wc_string_to_bool( $this->get_option( 'testmode' ) );
		$this->inline_cc_form              = 'yes' === $this->get_option( 'inline_cc_form' );
		$this->capture                     = 'yes' === $this->get_option( 'capture', 'yes' );
		$this->statement_descriptor        = WCV_SC_Helper::clean_statement_descriptor( $this->get_option( 'statement_descriptor' ) );
		$this->three_d_secure              = 'yes' === $this->get_option( 'three_d_secure' );
		$this->stripe_checkout             = 'yes' === $this->get_option( 'stripe_checkout' );
		$this->checkout_type               = $this->get_option( 'stripe_checkout_type', 'modal' );
		$this->stripe_checkout_image       = $this->get_option( 'stripe_checkout_image', '' );
		$this->stripe_checkout_description = $this->get_option( 'stripe_checkout_description' );
		$this->saved_cards                 = 'yes' === $this->get_option( 'saved_cards' );
		$this->secret_key                  = WCV_SC_Connect_API::get_secret_key();
		$this->publishable_key             = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
		$this->client_id                   = $this->testmode ? $this->get_option( 'test_client_id' ) : $this->get_option( 'client_id' );
		$this->payment_request             = 'yes' === $this->get_option( 'payment_request', 'yes' );

		$this->has_fields         = $this->checkout_type == 'hosted' ? false : true;

		if ( $this->stripe_checkout ) {
			$this->order_button_text = __( 'Continue to payment', 'wc-vendors-gateway-stripe-connect' );
		}

		\Stripe\Stripe::setApiKey( $this->secret_key );
		// Set the Stripe API version.
		\Stripe\Stripe::setApiVersion( WCV_SC_STRIPE_API_VER );

		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'display_order_fee' ) );

		add_action( 'woocommerce_receipt_stripe-connect', array( $this, 'stripe_checkout_receipt_page' ) );
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'stripe_checkout_return_handler' ) );

		add_filter( 'woocommerce_payment_successful_result', array( $this, 'modify_successful_payment_result' ), 99999, 2 );

		add_action( 'woocommerce_api_wcv_stripe_connect', array( $this, 'process_webhook' ) );
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = require dirname( __FILE__ ) . '/admin/stripe-connect-settings.php';
	}

	/**
	 * Checks if keys are set.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function are_keys_set() {
		if ( empty( $this->secret_key ) || empty( $this->publishable_key ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if gateway should be available to use.
	 *
	 * @since 2.0.0
	 */
	public function is_available() {
		if ( is_add_payment_method_page() && ! $this->saved_cards ) {
			return false;
		}

		if ( ! $this->client_id ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		// Fields not required for Stripe Checkout
		if ( $this->stripe_checkout && 'hosted' == $this->checkout_type ) {
			return;
		}

		global $wp;
		$user                 = wp_get_current_user();
		$display_tokenization = $this->supports( 'tokenization' ) && is_checkout() && $this->saved_cards;
		$total                = WC()->cart->total;
		$user_email           = '';
		$description          = $this->get_description();
		$description          = ! empty( $description ) ? $description : '';
		$firstname            = '';
		$lastname             = '';

		// If paying from order, we need to get total from order not cart.
		if ( isset( $_GET['pay_for_order'] ) && ! empty( $_GET['key'] ) ) {
			$order      = wc_get_order( wc_get_order_id_by_order_key( wc_clean( $_GET['key'] ) ) );
			$total      = $order->get_total();
			$user_email = $order->get_billing_email();
		} else {
			if ( $user->ID ) {
				$user_email = get_user_meta( $user->ID, 'billing_email', true );
				$user_email = $user_email ? $user_email : $user->user_email;
			}
		}

		if ( is_add_payment_method_page() ) {
			$pay_button_text = __( 'Add Card', 'wc-vendors-gateway-stripe-connect' );
			$total           = '';
			$firstname       = $user->user_firstname;
			$lastname        = $user->user_lastname;
		} elseif ( function_exists( 'wcs_order_contains_subscription' ) && isset( $_GET['change_payment_method'] ) ) {
			$pay_button_text = __( 'Change Payment Method', 'wc-vendors-gateway-stripe-connect' );
			$total           = '';
		} else {
			$pay_button_text = '';
		}

		ob_start();

		echo '<div
			id="stripe-connect-payment-data"
			data-checkout-session-id="' . esc_attr( $session->id ) . '"
			data-checkout-line-items="' . htmlspecialchars( json_encode( $line_items ), ENT_QUOTES, 'UTF-8' ) . '"
			data-panel-label="' . esc_attr( $pay_button_text ) . '"
			data-description="' . esc_attr( wp_strip_all_tags( $this->stripe_checkout_description ) ) . '"
			data-email="' . esc_attr( $user_email ) . '"
			data-verify-zip="' . esc_attr( apply_filters( 'wcv_sc_checkout_verify_zip', false ) ? 'true' : 'false' ) . '"
			data-billing-address="' . esc_attr( apply_filters( 'wcv_sc_checkout_require_billing_address', false ) ? 'true' : 'false' ) . '"
			data-shipping-address="' . esc_attr( apply_filters( 'wcv_sc_checkout_require_shipping_address', false ) ? 'true' : 'false' ) . '"
			data-amount="' . esc_attr( WCV_SC_Helper::get_stripe_amount( $total ) ) . '"
			data-name="' . esc_attr( $this->statement_descriptor ) . '"
			data-full-name="' . esc_attr( $firstname . ' ' . $lastname ) . '"
			data-currency="' . esc_attr( strtolower( get_woocommerce_currency() ) ) . '"
			data-image="' . esc_attr( $this->stripe_checkout_image ) . '"
			data-locale="' . esc_attr( apply_filters( 'wcv_sc_checkout_locale', $this->get_locale() ) ) . '"
			data-three-d-secure="' . esc_attr( $this->three_d_secure ? 'true' : 'false' ) . '"
			data-allow-remember-me="' . esc_attr( apply_filters( 'wcv_sc_allow_remember_me', true ) ? 'true' : 'false' ) . '">';

		if ( $this->testmode ) {
			/* translators: link to Stripe testing page */
			$description .= ' ' . sprintf( __( 'TEST MODE ENABLED. In test mode, you can use the card number 4242424242424242 with any CVC and a valid expiration date or check the <a href="%s" target="_blank">Testing Stripe documentation</a> for more card numbers.', 'wc-vendors-gateway-stripe-connect' ), 'https://stripe.com/docs/testing' );
		}

		$description = trim( $description );

		echo apply_filters( 'wcv_sc_description', wpautop( wp_kses_post( $description ) ), $this->id );

		if ( $display_tokenization ) {
			$this->tokenization_script();
			if ( 'hosted' !== $this->checkout_type ) {
				$this->saved_payment_methods();
			}
		}

		if ( ! $this->stripe_checkout ) {
			$this->elements_form();
		}

		if ( apply_filters( 'wcv_sc_display_save_payment_method_checkbox', $display_tokenization ) && ! is_add_payment_method_page() && ! isset( $_GET['change_payment_method'] ) ) {
			if ( ! $this->stripe_checkout ) {
				$this->save_payment_method_checkbox();
			} elseif ( $this->stripe_checkout && isset( $_GET['pay_for_order'] ) && ! empty( $_GET['key'] ) ) {
				$this->save_payment_method_checkbox();
			}
		}

		do_action( 'wcv_sc_cards_payment_fields', $this->id );

		echo '</div>';

		ob_end_flush();
	}

	/**
	 * Get stripe checkout cancel url
	 *
	 * @return string
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public function get_stripe_cancel_endpoint() {
		$cancel_endpoint = WCV_SC_Helper::get_cancel_endpoint();
		return apply_filters( 'wcv_sc_cancel_url', $cancel_endpoint );
	}

	/**
	 * Get vendor products from cart
	 *
	 * @param int $vendor_id Optional Vendor ID to get products for.
	 * @return array
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public function get_vendor_products_from_cart( $vendor_id = null ) {
		$vendor_products = WCV_SC_Helper::get_vendor_products_from_cart( $vendor_id );
		return apply_filters( 'wcv_sc_vendor_products', $vendor_products, $vendor_id );
	}

	/**
	 * Renders the Stripe elements form.
	 *
	 * @since 2.0.0
	 * @version 2.0.0
	 */
	public function elements_form() {
		include_once dirname( __FILE__ ) . '/frontend/html-form-elements.php';
	}

	/**
	 * Load admin scripts.
	 *
	 * @since 2.0.0
	 * @version 2.0.0
	 */
	public function admin_scripts() {
		if ( 'woocommerce_page_wc-settings' !== get_current_screen()->id ) {
			return;
		}

		wp_register_script( 'wc-vendors-gateway-stripe-connect_admin', plugins_url( 'assets/js/stripe-connect-admin.js', WCV_SC_MAIN_FILE ), array(), WCV_SC_VERSION, true );

		$admin_params = apply_filters(
			'wcv_sc_admin_scripts_params',
			array(
				'keys_match_error' => __( 'The secret key cannot be the same as the publishable key, please check your settings', 'wc-vendors-gateway-stripe-connect' ),
			)
		);

		wp_localize_script( 'wc-vendors-gateway-stripe-connect_admin', 'wcv_stripe_admin_args', $admin_params );
		wp_enqueue_script( 'wc-vendors-gateway-stripe-connect_admin' );
	}

	/**
	 * Payment_scripts function.
	 *
	 * Outputs scripts used for stripe payment
	 *
	 * @since 2.0.0
	 * @version 2.0.0
	 */
	public function payment_scripts() {
		if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) && ! is_add_payment_method_page() && ! isset( $_GET['change_payment_method'] ) ) {
			return;
		}

		// If Stripe Connect is not enabled bail.
		if ( 'no' === $this->enabled ) {
			return;
		}

		// If keys are not set bail.
		if ( ! $this->are_keys_set() ) {
			WCV_SC_Logger::log( 'Keys are not set correctly.' );
			return;
		}

		// If no SSL bail.
		if ( ! $this->testmode && ! is_ssl() ) {
			WCV_SC_Logger::log( 'Stripe live mode requires SSL.' );
		}

		// Load the styles
		wp_register_style( 'stripe_connect_checkout', plugins_url( 'assets/css/stripe-connect-checkout.css', WCV_SC_MAIN_FILE ), array(), WCV_SC_VERSION );
		wp_enqueue_style( 'stripe_connect_checkout' );

		// Stripe Checkout		
		wp_register_script( 'stripe', 'https://js.stripe.com/v3/', '', '3.0', true );

		if ( 'hosted' === $this->checkout_type ) {
			wp_register_script( 'wcv_sc_stripe_hosted_checkout', plugins_url( 'assets/js/stripe-hosted-checkout.js', WCV_SC_MAIN_FILE ), array( 'jquery-payment', 'stripe' ), WCV_SC_VERSION, true );
		} else {
			wp_register_script( 'stripe_checkout', 'https://checkout.stripe.com/checkout.js', '', WCV_SC_VERSION, true );
			wp_register_script( 'wcv_sc_stripe_checkout', plugins_url( 'assets/js/stripe-checkout.js', WCV_SC_MAIN_FILE ), array( 'jquery-payment', 'stripe' ), WCV_SC_VERSION, true );
		}

		$stripe_params = array(
			'key'                  => $this->publishable_key,
			'i18n_terms'           => __( 'Please accept the terms and conditions first', 'wc-vendors-gateway-stripe-connect' ),
			'i18n_required_fields' => __( 'Please fill in required checkout fields first', 'wc-vendors-gateway-stripe-connect' ),
		);

		// If we're on the pay page we need to pass stripe.js the address of the order.
		if ( isset( $_GET['pay_for_order'] ) && 'true' === $_GET['pay_for_order'] ) {
			$order_id = wc_get_order_id_by_order_key( urldecode( $_GET['key'] ) );
			$order    = wc_get_order( $order_id );

			if ( is_a( $order, 'WC_Order' ) ) {
				$stripe_params['billing_first_name'] = $order->get_billing_first_name();
				$stripe_params['billing_last_name']  = $order->get_billing_last_name();
				$stripe_params['billing_address_1']  = $order->get_billing_address_1();
				$stripe_params['billing_address_2']  = $order->get_billing_address_2();
				$stripe_params['billing_state']      = $order->get_billing_state();
				$stripe_params['billing_city']       = $order->get_billing_city();
				$stripe_params['billing_postcode']   = $order->get_billing_postcode();
				$stripe_params['billing_country']    = $order->get_billing_country();
			}
		}

		// $stripe_params['inline_cc_form']                          = $this->inline_cc_form ? 'yes' : 'no';
		$stripe_params['stripe_checkout_require_billing_address'] = apply_filters( 'wcv_sc_checkout_require_billing_address', false ) ? 'yes' : 'no';
		$stripe_params['is_checkout']                             = ( is_checkout() && empty( $_GET['pay_for_order'] ) ) ? 'yes' : 'no';
		$stripe_params['return_url']                              = $this->get_stripe_return_url();
		$stripe_params['ajaxurl']                                 = WC_AJAX::get_endpoint( '%%endpoint%%' );
		$stripe_params['stripe_nonce']                            = wp_create_nonce( '_wcv_sc_nonce' );
		$stripe_params['statement_descriptor']                    = $this->statement_descriptor;
		$stripe_params['elements_options']                        = apply_filters( 'wcv_sc_elements_options', array() );
		$stripe_params['is_stripe_checkout']                      = $this->stripe_checkout ? 'yes' : 'no';
		$stripe_params['checkout_type']                           = $this->checkout_type;
		$stripe_params['is_change_payment_page']                  = isset( $_GET['change_payment_method'] ) ? 'yes' : 'no';
		$stripe_params['is_add_payment_page']                     = is_wc_endpoint_url( 'add-payment-method' ) ? 'yes' : 'no';
		$stripe_params['is_pay_for_order_page']                   = is_wc_endpoint_url( 'order-pay' ) ? 'yes' : 'no';
		$stripe_params['elements_styling']                        = apply_filters( 'wcv_sc_elements_styling', false );
		$stripe_params['elements_classes']                        = apply_filters( 'wcv_sc_elements_classes', false );

		// merge localized messages to be use in JS
		$stripe_params = array_merge( $stripe_params, WCV_SC_Helper::get_localized_messages() );		

		$this->tokenization_script();
		if ( 'modal' === $this->checkout_type && $this->stripe_checkout ) {
			wp_enqueue_script( 'stripe_checkout' );
			wp_enqueue_script( 'wcv_sc_stripe_checkout' );
			wp_localize_script( 'wcv_sc_stripe_checkout', 'wcv_sc_params', apply_filters( 'wcv_sc_params', $stripe_params ) );
		} elseif ( 'hosted' === $this->checkout_type && $this->stripe_checkout ) {			
			wp_enqueue_script( 'wcv_sc_stripe_hosted_checkout' );

			$stripe_params['wcv_sc_ajax_url']         = apply_filters( 'wcv_sc_ajax_endpoint', WCV_SC_Ajax::get_endpoint() );
			$stripe_params['create_session_nonce']    = wp_create_nonce( 'create_checkout_session' );
			$stripe_params['required_fields_message'] = __( 'Please fill in all the required checkout fields.', 'wc-vendors-gateway-stripe-connect' );
			$stripe_params['error_message_prefix']    = __( 'Error:', 'wc-vendors-gateway-stripe-connect' );
			wp_localize_script( 'wcv_sc_stripe_hosted_checkout', 'wcv_sc_params', apply_filters( 'wcv_sc_params', $stripe_params ) );
		}
	}

	/**
	 * Process the payment
	 *
	 * @since 2.0.0
	 * @param int  $order_id Reference.
	 * @param bool $retry Should we retry on fail.
	 * @param bool $force_save_source Force save the payment source.
	 * @param mix  $previous_error Any error message from previous request.
	 *
	 * @throws WCV_SC_Exception If payment will not be accepted.
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id, $retry = true, $force_save_source = false, $previous_error = false ) {
		if ( $this->stripe_checkout && 'hosted' != $this->checkout_type ) {
			return;
		}
		
		try {
			$order = wc_get_order( $order_id );

			// This comes from the create account checkbox in the checkout page.
			$create_account = ! empty( $_POST['createaccount'] ) ? true : false;

			if ( $create_account || '' == get_user_meta( $order->get_customer_id(), '_wcv_sc_customer_id', true ) ) {
				$new_stripe_customer = new WCV_SC_Customer( $order->get_customer_id() );
				$new_stripe_customer->create_customer();
			}

			$prepared_source = $this->prepare_source( get_current_user_id(), $force_save_source, $new_stripe_customer );

			if ( empty( $prepared_source->source ) ) {
				$localized_message = __( 'Payment processing failed. Please retry.', 'wc-vendors-gateway-stripe-connect' );
				throw new WCV_SC_Exception( print_r( $prepared_source, true ), $localized_message );
			}

			$this->save_source_to_order( $order, $prepared_source );

			// Result from Stripe API request.
			$response = null;

			if ( $order->get_total() > 0 ) {

				// This will throw exception if not valid.
				$this->validate_minimum_order_amount( $order );

				WCV_SC_Logger::log( "Info: Begin processing payment for order $order_id for the amount of {$order->get_total()}" );

				/*
				 If we're doing a retry and source is chargeable, we need to pass
				 * a different idempotency key and retry for success.
				 */
				if ( $this->need_update_idempotency_key( $prepared_source->source_object, $previous_error ) ) {
					add_filter( 'wcv_sc_idempotency_key', array( $this, 'change_idempotency_key' ), 10, 2 );
				}

				// Process stripe connect payments here ..
				$response = $this->generate_payment( $order, $prepared_source, $retry, $force_save_source, $previous_error );
				if ( is_array( $response ) && isset( $response['intent_secret'] ) ) {
					return $response;
				}

				// Check if the customer has been deleted on stripes end
				// Check if the card has been deleted on stripes end
				// retry payment if need to.
				do_action( 'wc_gateway_stripe_process_payment', $response, $order );

				$this->process_response( $response, $order );
			} else {
				$order->payment_complete();
			}

			// Remove cart.
			WC()->cart->empty_cart();

			// Return thank you page redirect.
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);
		} catch ( \Stripe\Exception\AuthenticationException $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
			WCV_SC_Logger::log( 'Error: ' . $e->getMessage() );

			do_action( 'wc_gateway_stripe_api_autheni_error', $e, $order );

			/* translators: error message */
			$order->update_status( 'failed' );

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		} catch ( WCV_SC_Exception $e ) {
			wc_add_notice( $e->getLocalizedMessage(), 'error' );
			WCV_SC_Logger::log( 'Error: ' . $e->getMessage() );

			do_action( 'wc_gateway_stripe_process_payment_error', $e, $order );

			/* translators: error message */
			$order->update_status( 'failed' );

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}	

	/**
	 * Process the webhook
	 *
	 * @return void
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public function process_webhook() {
		$event = $this->check_webhook_event();

		if ( null === $event ) {
			WCV_SC_Logger::log( __( 'Webhook does not have a valid event.', 'wc-vendors-gateway-stripe-connect' ) );
			http_response_code(400);
			exit;
		}

		WCV_SC_Logger::log( sprintf( __( 'Start processing webhook event %s', 'wc-vendors-gateway-stripe-connect' ), esc_attr( $event->id ) ) );
	
		$session = $event->data->object;
		
		// TODO: Handle the different event types and update orders accordingly.
		switch ( $event->type ) {
			case 'payment_intent.created':
				WCV_SC_Logger::log( __( 'Payment intent created', 'wc-vendors-gateway-stripe-connect' ) );				
				break;
			case 'customer.created':
				$this->handle_customer_created( $event->data );
				break;
			case 'charge.succeeded':
				$this->handle_charge_succeeded( $event->data );
				break;
			case 'checkout.session.completed':
			case 'checkout.session.async_payment_succeeded':
				WCV_SC_Logger::log( sprintf( __( 'Handle Stripe webhook event: %s', 'wc-vendors-gateway-stripe-connect' ), esc_attr( $event->type ) ) );
				$this->handle_completed_checkout( $session );
				break;
			case 'payment_intent.succeeded':
				WCV_SC_Logger::log( __( 'Payment intent succeeded', 'wc-vendors-gateway-stripe-connect' ) );
				$this->handle_payment_intent_succeeded( $event->data->object );
				break;
			case 'checkout.session.async_payment_failed':
				WCV_SC_Logger::log( sprintf( __( 'Handle Stripe webhook event: %s', 'wc-vendors-gateway-stripe-connect' ), 'checkout.session.async_payment_succeeded' ) );						
				$order->update_status( 'failed' );
				break;
		}
		
		http_response_code(200);
	}

	/**
	 * Check webhook event
	 *
	 * @return object|null
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public function check_webhook_event() {
		$payload        = @file_get_contents('php://input');
		$sig_header     = $_SERVER['HTTP_STRIPE_SIGNATURE'];
		$event          = null;
		$webhook_secret = $this->get_option( 'webhook_secret', '' );
		$ip_address     = wp_unslash( sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) );

		WCV_SC_Logger::log( __( 'Start processing Stripe webhook.', 'wc-vendors-gateway-stripe-connect' ) );

		if ( ! WCV_SC_Helper::verify_webhook_source( $ip_address ) && ! $this->testmode ) {
			$unknown_source_message = __( 'Cannot process request because it comes from an unknown source.', 'wc-vendors-gateway-stripe-connect' );
			WCV_SC_Logger::log( $unknown_source_message );
			wp_die( $unknown_source_message );
		}

		if ( ! isset( $sig_header ) ) {
			wp_die( __( 'This endpoint is for processing webhooks and should not be accessed directly.', 'wc-vendors-gateway-stripe-connect' ) );
		}

		// Verify webhook signature and extract the event.
		// See https://stripe.com/docs/webhooks/signatures for more information.
		try {
			$event = \Stripe\Webhook::constructEvent(
				$payload, $sig_header, $webhook_secret
			);

			return $event;
		} catch ( \UnexpectedValueException $e ) {
			WCV_SC_Logger::log( __( 'Unexpected value encountered for webhook secret. Please double check your webhook secret and update your settings.', 'wc-vendors-gateway-stripe-connect' ) );
			return $event;
		} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
			WCV_SC_Logger::log( __( 'The webhook verification code used has expired. Please obtain a new webhook verification from Stripe and update your settings.', 'wc-vendors-gateway-stripe-connect' ) );
			return $event;
		}

		return $event;
	}

	/**
	 * Handle completed checkout session
	 *
	 * @param  object $session The completed checkout session object.
	 * @return void
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public function handle_completed_checkout( $session ) {
		WCV_SC_Logger::log( sprintf( __( 'Process webhook event for checkout session %s', 'wc-vendors-gateway-stripe-connect' ), esc_attr( $session->id ) ) );

		$order = WCV_SC_Helper::get_order_by_session_id( $session->id );

		if ( ! $order ) {
			WCV_SC_Logger::log( sprintf( __( 'No order to process for the checkout session: %s', 'wc-vendors-gateway-stripe-connect' ), esc_attr( $session->id ) ) );
			return;
		}

		switch ( $session->payment_status ) {
			case 'paid':
				$order->payment_complete();
				$order->add_order_note( __('Payment completed through Stripe Checkout', 'wc-vendors-gateway-stripe-connect' ) );
				WCV_SC_Logger::log( sprintf( __( 'Payment complete for order: %s', 'wc-vendors-gateway-stripe-connect' ), esc_attr( $order->get_id() ) ) );
				break;
			case 'no_payment_required':
				$order->update_status( 'pending' );
				WCV_SC_Logger::log( sprintf( __( 'No payment required for order: #%s', 'wc-vendors-gateway-stripe-connect' ), esc_attr( $order->get_id() ) ) );
			case 'unpaid':
			default:
				$order->update_status( 'on-hold', __( 'Awaiting payment', 'wc-vendors-gateway-stripe-connect' ) );
				WCV_SC_Logger::log( sprintf( __( 'Order unpaid #%s not paid', 'wc-vendors-gateway-stripe-connect' ), esc_attr( $order->get_id() ) ) );
				break;
		}

		WCV_SC_Logger::log( sprintf( __( 'Done processing completed checkout for order #%s', 'wc-vendors-gateway-stripe-connect' ), esc_attr( $order->get_id() ) ) );
		$order->save();
	}

	/**
	 * Update user data from Stripe customer
	 *
	 * @param object $customer The Stripe Customer object.
	 * @return void
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public function handle_customer_created( $customer ) {
		if ( ! is_user_logged_in(  ) ) {
			return;
		}

		$user_id = get_current_user_id(  );

		if ( ! $user_id ) {
			return;
		}

		$stripe_customer    = new WCV_SC_Customer( $user_id );
		$stripe_customer_id = $stripe_customer->get_id();

		if ( ! $stripe_customer_id || ! is_string( $stripe_customer_id ) ) {
			$stripe_customer->set_id( $customer->id );			
			update_user_meta( $user_id, '_wcv_sc_customer_id', $customer->id );
			WCV_SC_Logger::log( sprintf( __( 'Updated stripe customer ID for user: %d'), $user_id ) );
		}
	}

	/**
	 * Handle charge succeeded
	 *
	 * @param object $charge The Stripe\Charge object
	 * @return void
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public function handle_charge_succeeded( $charge ) {
		$order = WCV_SC_Helper::get_order_by_charge_id( $charge->id );

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		WCV_SC_Logger::log( sprintf( __( 'Customer successfully charged for order: #%d', 'wc-vendors-gateway-stripe-connect' ), $order->get_id() ) );
		
		$intent = $this->get_intent_from_order( $order );

		$this->process_intent( $intent, $order );
	}

	/**
	 * Handle payment intent succeeded
	 *
	 * @param object $intent The payment intent
	 * @return void
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public function handle_payment_intent_succeeded( $intent ) {
		$order = WCV_SC_Helper::get_order_by_intent_id( $intent->id );

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		$intent = $this->get_intent_from_order( $order );

		$this->process_intent( $intent, $order );
	}

	/**
	 * Checks if we need to redirect for Stripe Checkout.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function maybe_redirect_stripe_checkout() {
		$is_payment_request = ( isset( $_POST ) && isset( $_POST['payment_request_type'] ) );

		return (
			$this->stripe_checkout &&
			! isset( $_POST['stripe_connect_checkout_order'] ) &&
			! $this->is_using_saved_payment_method() &&
			! is_wc_endpoint_url( 'order-pay' ) &&
			! $is_payment_request
		);
	}


	/**
	 * Handles the return from processing the payment.
	 *
	 * @since 4.1.0
	 */
	public function stripe_checkout_return_handler() {
		if ( ! $this->stripe_checkout ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['stripe_checkout_process_nonce'], 'stripe-checkout-process' ) ) {
			return;
		}

		$order_id = wc_clean( $_POST['order_id'] );
		$order    = wc_get_order( $order_id );

		do_action( 'wcv_sc_checkout_return_handler', $order );

		if ( WCV_SC_Helper::is_pre_orders_exists() && $this->pre_orders->is_pre_order( $order_id ) && WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id ) ) {
			$result = $this->pre_orders->process_pre_order( $order_id );
		} else {
			$result = $this->process_payment( $order_id );
		}

		if ( 'success' === $result['result'] ) {
			wp_redirect( $result['redirect'] );
			exit;
		}

		// Redirects back to pay order page.
		wp_safe_redirect( $order->get_checkout_payment_url( true ) );
		exit;
	}

	/**
	 * Output the available redirect pages for the connect request
	 */
	public function get_redirect_pages() {
		$pages       = get_pages();
		$pages_array = array( 0 => __( 'Default Stripe redirect URI', 'wc-vendors-gateway-stripe-connect' ) );
		$get_pages   = get_pages( 'hide_empty=0' );

		foreach ( $get_pages as $page ) {
			$pages_array[ $page->ID ] = esc_attr( $page->post_title );
		}

		return apply_filters( 'wcv_sc_redirect_uri_pages', $pages_array );
	}

	/**
	 * Adds an error message wrapper to each saved method.
	 *
	 * @since 4.2.0
	 * @param WC_Payment_Token $token Payment Token.
	 * @return string                 Generated payment method HTML
	 */
	public function get_saved_payment_method_option_html( $token ) {
		$html          = parent::get_saved_payment_method_option_html( $token );
		$error_wrapper = '<div class="stripe-connect-source-errors" role="alert"></div>';

		return preg_replace( '~</(\w+)>\s*$~', "$error_wrapper</$1>", $html );
	}

	/**
	 * Attached to `woocommerce_payment_successful_result` with a late priority,
	 * this method will combine the "naturally" generated redirect URL from
	 * WooCommerce and a payment intent secret into a hash, which contains both
	 * the secret, and a proper URL, which will confirm whether the intent succeeded.
	 *
	 * @since 2.0.0
	 * @param array $result   The result from `process_payment`.
	 * @param int   $order_id The ID of the order which is being paid for.
	 * @return array
	 */
	public function modify_successful_payment_result( $result, $order_id ) {
		// Only redirects with intents need to be modified.
		if ( ! isset( $result['intent_secret'] ) ) {
			return $result;
		}

		// Put the final thank you page redirect into the verification URL.
		$verification_url = add_query_arg(
			array(
				'order'            => $order_id,
				'confirm_pi_nonce' => wp_create_nonce( 'wc_stripe_confirm_pi' ),
				'redirect_to'      => rawurlencode( $result['redirect'] ),
			),
			WC_AJAX::get_endpoint( 'wcv_sc_confirm_intent' )
		);

		// Combine into a hash.
		$redirect = sprintf( '#confirm-pi-%s:%s', $result['intent_secret'], rawurlencode( $verification_url ) );

		return array(
			'result'   => 'success',
			'redirect' => $redirect,
		);
	}

	/**
	 * Confirm intent after checkout
	 *
	 * @param WC_Order $order The order to process after checkout.
	 * @return void
	 * @version 2.1.0
	 * @since   2.0.0
	 */
	public function confirm_intent_after_checkout( $order ) {
		$payment_method = $order->get_payment_method();
		if ( $payment_method !== $this->id ) {
			// If this is not the payment method, an intent would not be available.
			return;
		}

		$intent = $this->get_intent_from_order( $order );
		if ( ! $intent ) {
			// No intent, redirect to the order received page for further actions.
			return;
		}

		// A webhook might have modified or locked the order while the intent was retreived. This ensures we are reading the right status.
		clean_post_cache( $order->get_id() );
		$order = wc_get_order( $order->get_id() );

		if ( 'pending' !== $order->get_status() && 'failed' !== $order->get_status() ) {
			// If payment has already been completed, this function is redundant.
			return;
		}

		$intent->confirm();

		$this->process_intent( $intent, $order );
	}

	/**
	 * Process a payment intent.
	 *
	 * @param object $intent The Stripe payment intent object.
	 * @param WC_Order $order The order to process.
	 * @return void
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	public function process_intent( $intent, $order ) {
		if ( $intent->status == 'succeeded' ) {
			$charges = [
				1 => end( $intent->charges->data ),
			];
	
			$response = (object) [
				'charges' => $charges,
			];

			$order = WCV_SC_Helper::get_order_by_intent_id( $intent->id );
			$this->process_response( $response, $order );

			$this->maybe_transfer_commission( $order );
		}
	}

	/**
	 * Displays the Stripe fee
	 *
	 * @since 4.1.0
	 *
	 * @param int $order_id The ID of the order.
	 */
	public function display_order_fee( $order_id ) {
		if ( apply_filters( 'wcv_sc_hide_display_order_fee', false, $order_id ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		$currency = WCV_SC_Helper::get_stripe_currency( $order );
		$fee      = WCV_SC_Helper::format_amount_to_display( WCV_SC_Helper::get_stripe_fee( $order ), $currency );

		if ( ! $fee || ! $currency ) {
			return;
		}

		?>

		<tr>
			<td class="label stripe-fee">
				<?php echo wc_help_tip( __( 'This represents the fee Stripe collects for the transaction.', 'wc-vendors-gateway-stripe-connect' ) ); // wpcs: xss ok. ?>
				<?php esc_html_e( 'Stripe Fee:', 'wc-vendors-gateway-stripe-connect' ); ?>
			</td>
			<td width="1%"></td>
			<td class="total">
				-&nbsp;<?php echo wc_price( $fee, array( 'currency' => $currency ) ); // wpcs: xss ok. ?>
			</td>
		</tr>

		<?php
	}
}
