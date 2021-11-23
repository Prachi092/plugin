<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Customer.
 *
 * Customers are created on the platform account so that they can be shared between connected accounts.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 *
 * Based on class WC_Stripe_Customer from WooCommerce Stripe plugin - https://wordpress.org/plugins/woocommerce-gateway-stripe/
 */

use Stripe\Customer;

class WCV_SC_Customer {

	/**
	 * Stripe customer ID
	 *
	 * @var string
	 */
	private $id = '';

	/**
	 * WP User ID
	 *
	 * @var integer
	 */
	private $user_id = 0;

	/**
	 * Data from API
	 *
	 * @var array
	 */
	private $customer_data = array();


	/**
	 * Stripe customer object
	 */
	private $stripe_customer;

	/**
	 * Constructor
	 *
	 * @param int $user_id The WP user ID
	 */
	public function __construct( $user_id = 0 ) {

		// Use the platform account for storing customers.
		\Stripe\Stripe::setApiKey( WCV_SC_Connect_API::get_secret_key() );
		// Set the Stripe API version.
		\Stripe\Stripe::setApiVersion( WCV_SC_STRIPE_API_VER );

		if ( $user_id ) {
			$this->set_user_id( $user_id );
			$this->set_id( get_user_meta( $user_id, '_wcv_sc_customer_id', true ) );

			// Set the stripe user object
			if ( $this->get_id() ) {
				$this->set_stripe_customer();
			}
		}
	}

	/**
	 * Get Stripe customer ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set Stripe customer ID.
	 *
	 * @param [type] $id [description]
	 */
	public function set_id( $id ) {
		$this->id = wc_clean( $id );
	}

	/**
	 * User ID in WordPress.
	 *
	 * @return int
	 */
	public function get_user_id() {
		return absint( $this->user_id );
	}

	/**
	 * Set User ID used by WordPress.
	 *
	 * @param int $user_id
	 */
	public function set_user_id( $user_id ) {
		$this->user_id = absint( $user_id );
	}

	/**
	 * Get user object.
	 *
	 * @return WP_User
	 */
	protected function get_user() {
		return $this->get_user_id() ? get_user_by( 'id', $this->get_user_id() ) : false;
	}

	/**
	 * Get the Stripe customer object
	 */
	public function get_stripe_customer() {
		return $this->stripe_customer;
	}

	/**
	 * Set the Stripe customer object
	 */
	public function set_stripe_customer() {
		try {
			$customer = \Stripe\Customer::retrieve( $this->get_id() );

			if ( isset( $customer['deleted'] ) && $customer['deleted'] ) {
				delete_user_meta( $this->user_id, '_wcv_sc_customer_id' );
			} else {
				$this->stripe_customer = $customer;
			}
		} catch ( \Stripe\Exception\UnexpectedValueException $e ) {
			$this->stripe_customer = null;
		} catch ( \Stripe\Exception\BadMethodCallException $e ) {
			if ( $this->is_no_such_customer_error( $e ) ) {
				delete_user_meta( $this->user_id, '_wcv_sc_customer_id' );
			}
		}
	}

	/**
	 * Store data from the Stripe API about this customer
	 */
	public function set_customer_data( $data ) {
		$this->customer_data = $data;
	}

	/**
	 * Create a customer in Stripe.
	 *
	 * @param array $args
	 */
	public function create_customer( $args = array() ) {
		$billing_email = isset( $_POST['billing_email'] ) ? filter_var( $_POST['billing_email'], FILTER_SANITIZE_EMAIL ) : '';
		$user          = $this->get_user();

		if ( $user ) {
			$billing_first_name = get_user_meta( $user->ID, 'billing_first_name', true );
			$billing_last_name  = get_user_meta( $user->ID, 'billing_last_name', true );

			// If billing first name does not exists try the user first name.
			if ( empty( $billing_first_name ) ) {
				$billing_first_name = get_user_meta( $user->ID, 'first_name', true );
			}

			// If billing last name does not exists try the user last name.
			if ( empty( $billing_last_name ) ) {
				$billing_last_name = get_user_meta( $user->ID, 'last_name', true );
			}

			$description = __( 'Name', 'wc-vendors-gateway-stripe-connect' ) . ': ' . $billing_first_name . ' ' . $billing_last_name . ' ' . __( 'Username', 'wc-vendors-gateway-stripe-connect' ) . ': ' . $user->user_login;

			$defaults = array(
				'email'       => $user->user_email,
				'description' => $description,
			);
		} else {
			$defaults = array(
				'email'       => $billing_email,
				'description' => '',
			);
		}

		$metadata = array();

		$defaults['metadata'] = apply_filters( 'wcv_sc_customer_metadata', $metadata, $user );

		$customer_args = wp_parse_args( $args, $defaults );
		$customer      = Customer::create( $customer_args );

		$this->set_id( $customer->id );
		$this->clear_cache();
		$this->set_customer_data( $customer );

		if ( $this->get_user_id() ) {
			update_user_meta( $this->get_user_id(), '_wcv_sc_customer_id', $customer->id );
		}

		do_action( 'wcv_sc_add_customer', $args, $customer );

		return $customer->id;
	}

	/**
	 * Checks to see if error is of invalid request
	 * error and it is no such customer.
	 *
	 * @since 2.0.0
	 * @param array $error
	 */
	public function is_no_such_customer_error( $error ) {
		return (
			$error &&
			'invalid_request_error' === $error->jsonBody['error']['type'] &&
			preg_match( '/No such customer/i', $error->jsonBody['error']['message'] )
		);
	}

	/**
	 * Add a source for this stripe customer.
	 *
	 * @param string $source_id
	 * @param bool   $retry
	 * @return WP_Error|int
	 */
	public function add_source( $source_id, $retry = true ) {
		if ( ! $this->get_id() ) {
			$this->set_id( $this->create_customer() );
			$this->set_stripe_customer();
		}

		try {

			// Add the source to the customer on the stripe side
			$stripe_response = $this->stripe_customer->sources->create( array( 'source' => $source_id ) );

			$wc_token = false;

			if ( empty( $stripe_response->id ) ) {
				return new WP_Error( 'error', __( 'Unable to add payment source.', 'woocommerce-gateway-stripe' ) );
			}

			// Add token to WooCommerce.
			if ( $this->get_user_id() && class_exists( 'WC_Payment_Token_CC' ) ) {
				$wc_token = new WC_Payment_Token_CC();
				$wc_token->set_token( $stripe_response->id );
				$wc_token->set_gateway_id( 'stripe-connect' );
				$wc_token->set_card_type( strtolower( $stripe_response->card->brand ) );
				$wc_token->set_last4( $stripe_response->card->last4 );
				$wc_token->set_expiry_month( $stripe_response->card->exp_month );
				$wc_token->set_expiry_year( $stripe_response->card->exp_year );
				$wc_token->set_user_id( $this->get_user_id() );
				$wc_token->save();
			}

			$this->clear_cache();
			do_action( 'wcv_sc_add_source', $this->get_id(), $wc_token, $stripe_response, $source_id );

			return $stripe_response->id;
		} catch ( \Stripe\Exception\BadMethodCallException $e ) {
			if ( $this->is_no_such_customer_error( $e ) ) {
				delete_user_meta( $this->get_user_id(), '_wcv_sc_customer_id' );
				$this->create_customer();
				return $this->add_source( $source_id, false );
			}
		} catch ( WCV_SC_Exception $e ) {
			$error = sprintf( __( 'Error: %s', 'wc-vendors-gateway-stripe-connect' ), $e->getLocalizedMessage() );
			wc_add_notice( $error, 'error' );
			WCV_SC_Logger::log( $error );
		}
	}

	/**
	 * Get a customers saved sources using their Stripe ID.
	 *
	 * @param  string $customer_id
	 * @return array
	 */
	public function get_sources() {
		if ( ! $this->get_id() ) {
			return array();
		}

		$sources = get_transient( 'stripe_connect_sources_' . $this->get_id() );

		if ( empty( $sources ) ) {
			$sources = $this->stripe_customer->sources;
		}

		return empty( $sources ) ? array() : $sources;
	}

	/**
	 * Delete a source from stripe.
	 *
	 * @param string $source_id
	 */
	public function delete_source( $source_id ) {
		if ( ! $this->get_id() || ! $this->stripe_customer ) {
			return false;
		}

		try {
			$consumed_source = $this->stripe_customer->sources->retrieve( $source_id )->detach();
			$this->clear_cache();

			do_action( 'wcv_sc_delete_source', $this->get_id(), $consumed_source );

			return true;
		} catch ( \Stripe\Exception\UnexpectedValueException $e ) {
			return false;
		} catch ( WCV_SC_Exception $e ) {
			return false;
		}
	}

	/**
	 * Set default source in Stripe
	 *
	 * @param string $source_id
	 */
	public function set_default_source( $source_id ) {
		if ( ! $this->get_id() || ! $this->stripe_customer ) {
			return false;
		}

		try {
			$this->stripe_customer->default_source = $source_id;
			$this->stripe_customer->save();

			$this->clear_cache();

			do_action( 'wcv_sc_set_default_source', $this->get_id(), $this->stripe_customer );

			return true;
		} catch ( WCV_SC_Exception $e ) {
			return false;
		}
	}

	/**
	 * Deletes caches for this users cards.
	 */
	public function clear_cache() {
		delete_transient( 'stripe_connect_sources_' . $this->get_id() );
		$this->customer_data = array();
	}

}
