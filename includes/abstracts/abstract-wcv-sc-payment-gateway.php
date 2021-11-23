<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Files.FileName

use Stripe\Source;
use Stripe\PaymentIntent;
use Stripe\Transfer;
use Stripe\BalanceTransaction;
use Stripe\ApplicationFee;

/**
 * Abstract class that will be inherited by all payment methods.
 *
 * @extends WCV_SC_Payment_Gateway
 *
 * @since 2.0.0
 */
abstract class WCV_SC_Payment_Gateway extends WC_Payment_Gateway_CC {

	/**
	 * Displays the save to account checkbox.
	 *
	 * @since 2.0.0
	 */
	public function save_payment_method_checkbox() {
		printf(
			'<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
				<input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" style="width:auto;" />
				<label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label>
			</p>',
			esc_attr( $this->id ),
			esc_html( apply_filters( 'wcv_sc_save_to_account_text', __( 'Save payment information to my account for future purchases.', 'wc-vendors-gateway-stripe-connect' ) ) )
		);
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
			'invalid_request_error' === $error->type &&
			preg_match( '/No such customer/i', $error->message )
		);
	}

	/**
	 * Checks to see if request is invalid and that
	 * they are worth retrying.
	 *
	 * @since 2.0.0
	 * @param array $error
	 */
	public function is_retryable_error( $error ) {
		return (
			'invalid_request_error' === $error->type ||
			'idempotency_error' === $error->type ||
			'rate_limit_error' === $error->type ||
			'api_connection_error' === $error->type ||
			'api_error' === $error->type
		);
	}

	/**
	 * Checks to see if error is of invalid request
	 * error and it is no such source.
	 *
	 * @since 2.0.0
	 * @param array $error
	 */
	public function is_no_such_source_error( $error ) {
		return (
			$error &&
			'invalid_request_error' === $error->type &&
			preg_match( '/No such source/i', $error->message )
		);
	}

	/**
	 * Checks to see if error is of invalid request
	 * error and it is no such source linked to customer.
	 *
	 * @since 2.0.0
	 * @param array $error
	 */
	public function is_no_linked_source_error( $error ) {
		return (
			$error &&
			'invalid_request_error' === $error->type &&
			preg_match( '/does not have a linked source with ID/i', $error->message )
		);
	}

	/**
	 * Checks to see if error is of same idempotency key
	 * error due to retries with different parameters.
	 *
	 * @since 2.0.0
	 * @param array $error
	 */
	public function is_same_idempotency_error( $error ) {
		return (
			$error &&
			'idempotency_error' === $error->type &&
			preg_match( '/Keys for idempotent requests can only be used with the same parameters they were first used with./i', $error->message )
		);
	}

	/**
	 * Check to see if we need to update the idempotency
	 * key to be different from previous charge request.
	 *
	 * @since  2.0.0
	 * @param  object $source_object
	 * @param  object $error
	 * @return bool
	 */
	public function need_update_idempotency_key( $source_object, $error ) {
		return (
		$error &&
		1 < $this->retry_interval &&
		! empty( $source_object ) &&
		'chargeable' === $source_object->status &&
		self::is_same_idempotency_error( $error )
		);
	}

	/**
	 * Check if we need to make gateways available.
	 *
	 * @since 2.0.0
	 */
	public function is_available() {
		if ( 'yes' === $this->enabled ) {
			if ( ! $this->secret_key || ! $this->publishable_key ) {
				return false;
			}
			return true;
		}

		return parent::is_available();
	}

	/**
	 * Checks if we need to process pre orders when
	 * pre orders is in the cart.
	 *
	 * @since  2.0.0
	 * @param  int $order_id
	 * @return bool
	 */
	public function maybe_process_pre_orders( $order_id ) {
		return (
		WCV_SC_Helper::is_pre_orders_exists() &&
		$this->pre_orders->is_pre_order( $order_id ) &&
		WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id ) &&
		! is_wc_endpoint_url( 'order-pay' )
		);
	}

	/**
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication).
	 *
	 * @since 2.0.0
	 */
	public function add_admin_notice( $slug, $class, $message, $dismissible = false ) {
		$this->notices[ $slug ] = array(
			'class'       => $class,
			'message'     => $message,
			'dismissible' => $dismissible,
		);
	}

	/**
	 * All payment icons that work with Stripe. Some icons references
	 * WC core icons.
	 *
	 * @since  2.0.0
	 * @return array
	 */
	public function payment_icons() {
		return apply_filters(
			'wcv_sc_payment_icons',
			array(
				'visa'       => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/visa.svg" class="stripe-visa-icon stripe-icon" alt="Visa" />',
				'amex'       => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/amex.svg" class="stripe-amex-icon stripe-icon" alt="American Express" />',
				'mastercard' => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/mastercard.svg" class="stripe-mastercard-icon stripe-icon" alt="Mastercard" />',
				'discover'   => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/discover.svg" class="stripe-discover-icon stripe-icon" alt="Discover" />',
				'diners'     => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/diners.svg" class="stripe-diners-icon stripe-icon" alt="Diners" />',
				'jcb'        => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/jcb.svg" class="stripe-jcb-icon stripe-icon" alt="JCB" />',
				'alipay'     => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/alipay.svg" class="stripe-alipay-icon stripe-icon" alt="Alipay" />',
				'wechat'     => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/wechat.svg" class="stripe-wechat-icon stripe-icon" alt="Wechat Pay" />',
				'bancontact' => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/bancontact.svg" class="stripe-bancontact-icon stripe-icon" alt="Bancontact" />',
				'ideal'      => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/ideal.svg" class="stripe-ideal-icon stripe-icon" alt="iDeal" />',
				'p24'        => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/p24.svg" class="stripe-p24-icon stripe-icon" alt="P24" />',
				'giropay'    => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/giropay.svg" class="stripe-giropay-icon stripe-icon" alt="Giropay" />',
				'eps'        => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/eps.svg" class="stripe-eps-icon stripe-icon" alt="EPS" />',
				'multibanco' => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/multibanco.svg" class="stripe-multibanco-icon stripe-icon" alt="Multibanco" />',
				'sofort'     => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/sofort.svg" class="stripe-sofort-icon stripe-icon" alt="SOFORT" />',
				'sepa'       => '<img src="' . WCV_SC_PLUGIN_URL . '/assets/images/sepa.svg" class="stripe-sepa-icon stripe-icon" alt="SEPA" />',
			)
		);
	}

	/**
	 * Validates that the order meets the minimum order amount
	 * set by Stripe.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 * @param   object $order
	 */
	public function validate_minimum_order_amount( $order ) {
		if ( $order->get_total() * 100 < WCV_SC_Helper::get_minimum_amount() ) {
			/* translators: 1) dollar amount */
			throw new WCV_SC_Exception( 'Did not meet minimum amount', sprintf( __( 'Sorry, the minimum allowed order total is %1$s to use this payment method.', 'wc-vendors-gateway-stripe-connect' ), wc_price( WCV_SC_Helper::get_minimum_amount() / 100 ) ) );
		}
	}

	/**
	 * Gets the transaction URL linked to Stripe dashboard.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public function get_transaction_url( $order ) {
		if ( $this->testmode ) {
			$this->view_transaction_url = 'https://dashboard.stripe.com/test/payments/%s';
		} else {
			$this->view_transaction_url = 'https://dashboard.stripe.com/payments/%s';
		}

		return parent::get_transaction_url( $order );
	}

	/**
	 * Gets the saved customer id if exists.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public function get_stripe_customer_id( $order ) {
		$customer = get_user_meta( $order->get_customer_id(), '_wcv_sc_customer_id', true );

		if ( empty( $customer ) ) {
			// Try to get it via the order.
			return $order->get_meta( '_wcv_sc_customer_id', true );
		} else {
			return $customer;
		}

		return false;
	}

	/**
	 * Builds the return URL from redirects.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 * @param   object $order
	 * @param   int    $id    Stripe session id.
	 */
	public function get_stripe_return_url( $order = null, $id = null ) {
		if ( is_object( $order ) ) {
			if ( empty( $id ) ) {
				$id = uniqid();
			}

			$order_id = $order->get_id();

			$args = array(
				'utm_nooverride' => '1',
				'order_id'       => $order_id,
			);

			return esc_url_raw( add_query_arg( $args, $this->get_return_url( $order ) ) );
		}

		return esc_url_raw( add_query_arg( array( 'utm_nooverride' => '1' ), $this->get_return_url() ) );
	}

	/**
	 * Is $order_id a subscription?
	 *
	 * @param  int $order_id
	 * @return boolean
	 */
	public function has_subscription( $order_id ) {
		return ( function_exists( 'wcs_order_contains_subscription' ) && ( wcs_order_contains_subscription( $order_id ) || wcs_is_subscription( $order_id ) || wcs_order_contains_renewal( $order_id ) ) );
	}

	/**
	 * Create the connected payments - This is for direct charges
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 * @param   WC_Order $order
	 * @param   object   $prepared_source
	 * @return  object
	 */
	public function generate_payment( $order, $prepared_source, $retry, $force_save_source, $previous_error ) {
		if ( WCV_SC_Helper::use_charges_transfers() ) {
			WCV_SC_Logger::log( __( 'Processing payment using Separate Charges and Transfers.', 'wc-vendors-gateway-stripe-connect' ) );
			return $this->generate_charges_transfers_payment( $order, $prepared_source, $retry, $force_save_source, $previous_error );
		}

		WCV_SC_Logger::log( __( 'Processing payment using Direct Charges.', 'wc-vendors-gateway-stripe-connect' ) );
		return $this->generate_direct_charges_payment( $order, $prepared_source );
	}

	public function prepare_common_payment_request_data( $order, $prepared_source ) {
		$request = array(
			'confirmation_method'  => 'manual',
			'confirm'              => true,
			'payment_method_types' => array( 'card' ),
			'currency'             => strtolower( $order->get_currency() ),
			'description'          => sprintf(
				__( '%1$s - Order %2$s', 'wc-vendors-gateway-stripe-connect' ),
				wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				$order->get_order_number()
			),
		);

		$settings             = get_option( 'woocommerce_stripe-connect_settings', array() );
		$statement_descriptor = ! empty( $settings['statement_descriptor'] ) ? str_replace( "'", '', $settings['statement_descriptor'] ) : '';

		// Customer information
		$billing_email      = $order->get_billing_email();
		$billing_first_name = $order->get_billing_first_name();
		$billing_last_name  = $order->get_billing_last_name();

		if ( ! empty( $billing_email ) && apply_filters( 'wcv_sc_send_stripe_receipt', false ) ) {
			$request['receipt_email'] = $billing_email;
		}

		if ( ! empty( $statement_descriptor ) ) {
			$request['statement_descriptor'] = WCV_SC_Helper::clean_statement_descriptor( $statement_descriptor );
		}

		// Meta data for the charge request
		$metadata = array(
			__( 'customer_name', 'wc-vendors-gateway-stripe-connect' ) => sanitize_text_field( $billing_first_name ) . ' ' . sanitize_text_field( $billing_last_name ),
			__( 'customer_email', 'wc-vendors-gateway-stripe-connect' ) => sanitize_email( $billing_email ),
			'order_id' => $order->get_order_number(),
		);

		if ( $this->has_subscription( $order->get_id() ) ) {
			$metadata += array(
				'payment_type' => 'recurring',
				'site_url'     => esc_url( get_site_url() ),
			);
		}

		$request['metadata'] = apply_filters( 'wcv_sc_payment_metadata', $metadata, $order, $prepared_source );

		return $request;
	}

	public function generate_direct_charges_payment( $order, $prepared_source ) {
		$request       = $this->prepare_common_payment_request_data( $order, $prepared_source );
		$vendors_paid  = array();
		$charges       = array();
		
		$coupons         = $order->get_items( 'coupon' );
		$commission_type = get_option( 'wcvendors_commission_type', '' );
		$commission_rate = get_option( 'wcvendors_vendor_commission_rate', '' );

		// Process the vendor charges first
		$vendors_due = WCV_Vendors::get_vendor_dues_from_order( $order, false );
		foreach ( $vendors_due as $vendor_id => $products ) {
			if ( $vendor_id == 1 ) {
				continue;
			}

			$connected_account_id = get_user_meta( $vendor_id, '_stripe_connect_user_id', true );

			// If they haven't connected to stripe do nothing
			if ( empty( $connected_account_id ) ) {
				continue;
			}

			$product_admin_fee = 0;
			$total_admin_fee   = 0;
			$total             = 0;

			// Get the totals
			foreach ( $products as $key => $product ) {
				$product_owner     = WCVendors_Pro_Vendor_Controller::get_vendor_from_object( $product['product_id'] );
				$product_admin_fee = $vendors_due[1][ $key ]['total'];
				$add_admin_fee     = ( 'percent' != $commission_type && 100 > $commission_rate ) ? true : false;

				if ( empty( $coupons ) || $add_admin_fee ) {
					$total_admin_fee += $product_admin_fee;
				}
				$total += ( $product['total'] + $product_admin_fee );
				unset( $vendors_due[1][ $key ], $vendors_due[ $vendor_id ][ $key ] );
				$vendors_paid[ $vendor_id ][] = array(
					'product_id' => $product['product_id'],
					'order_id'   => $order->get_id(),
				);				
			}

			$cart_fees = $this->get_cart_fees();
			$total_admin_fee += $cart_fees;

			/**
			 * Using shared customers a new source needs to be created and attached to the connected account
			 *
			 * This is for Direct charges only.
			 *
			 * @see https://stripe.com/docs/sources/connect#shared-card-sources
			 */
			$token_data = array(
				'original_source' => $prepared_source->source,
				'usage'           => 'single_use',
			);

			if ( $prepared_source->customer ) {
				$token_data['customer'] = $prepared_source->customer;
				$token_data['usage'] = 'reusable';
			}

			$token = Source::create(
				$token_data,
				array( 'stripe_account' => $connected_account_id )
			);

			$request['amount']                 = WCV_SC_Helper::get_stripe_amount( $total, $request['currency'] );
			$request['application_fee_amount'] = WCV_SC_Helper::get_stripe_amount( $total_admin_fee, $request['currency'] );

			if ( $request['amount'] <= 0 ) {
				continue;
			}

			if ( $request['application_fee_amount'] <= 0 ) {
				unset( $request['application_fee_amount'] );
			}

			$request['source']        = $token;
			$connected_stripe_account = array( 'stripe_account' => $connected_account_id );
			$request                  = apply_filters( 'wcv_stripe_order_data', $request );
			$intent                   = PaymentIntent::create( $request, $connected_stripe_account );

			$this->handle_direct_charges_intent_error( $intent, $order );

			// Only record payment if payment intent is completed.
			if ( 'succeeded' == $intent->status ) {
				$charges[ $vendor_id ] = end( $intent->charges->data );
				do_action( 'wcv_stripe_change_id_generated', $order->get_id(), $charges[ $vendor_id ]->id, $charges[ $vendor_id ] );
			}
		}

		/**
		 * Process any remaining admin charges
		 */
		$total = 0;

		foreach ( $vendors_due as $vendor_id => $products ) {
			foreach ( $products as $product_id => $product ) {
				$total += $product['total'];
				unset( $vendors_due[ $vendor_id ][ $product_id ] );
			}
		}

		// Remove the application fee
		unset( $request['application_fee_amount'] );

		// Update the source and customer to the original customer linked to the platform account.
		$request['source'] = $prepared_source->source;
		$request['amount'] = WCV_SC_Helper::get_stripe_amount( $total, $request['currency'] );

		if ( $prepared_source->customer ) {
			$request['customer'] = $prepared_source->customer;
		}

		$request = apply_filters( 'wcv_stripe_intent_data', $request );

		if ( ! empty( $request['amount'] ) ) {
			$intent = PaymentIntent::create( $request );
			$this->handle_direct_charges_intent_error( $intent, $order );

			if ( 'succeeded' == $intent->status ) {
				$charges[1] = end( $intent->charges->data );
			}
		}

		$this->log_commission( $vendors_paid );

		return (object) array(
			'charges'      => $charges,
			'vendors_paid' => $vendors_paid,
		);
	}

	public function handle_direct_charges_intent_error( $intent, $order ) {
		if ( ! empty( $intent->error ) ) {
			$this->maybe_remove_non_existent_customer( $intent->error, $order );
			$this->throw_localized_message( $intent, $order );
		}

		if ( 'requires_action' == $intent->status ) {
			throw new WCV_SC_Exception(
				'unsupported_card',
				__( '3D Secure card is not supported', 'wc-vendors-gateway-stripe-connect' )
			);
		}
	}

	/**
	 * Store extra meta data for an order from Stripe.
	 *
	 * @param $response
	 * @param $order WC_Order
	 *
	 * @return mixed
	 * @throws WCV_SC_Exception
	 */
	public function process_response( $response, $order ) {
		WCV_SC_Logger::log( 'Received response from Stripe: ' . print_r( $response, true ) );

		$order_id       = $order->get_id();
		$_vendor_orders = WCV_Vendors::get_vendor_orders( $order_id );
		$captured       = array();
		$statuses       = array();
		$transaction_id = array();
		$vendor_orders  = array();

		// Map vendor order with vendor id.
		foreach ( $_vendor_orders as $vendor_order ) {
			$vendor_id                   = $vendor_order->get_meta( '_vendor_id' );
			$vendor_orders[ $vendor_id ] = $vendor_order->get_id();
		}

		foreach ( (array) $response->charges as $vendor_id => $charge ) {
			if ( ! is_object( $charge ) ) {
				continue;
			}
			// Update transaction id for vendor order.
			if ( 1 !== $vendor_id ) {
				$vendor_order = new WC_Order_Vendor( $vendor_orders[ $vendor_id ] );
				$vendor_order->set_transaction_id( $charge->id );
				if ( $charge->application_fee ) {
					$vendor_order->update_meta_data(
						'_wcv_sc_application_fee_id',
						$charge->application_fee
					);
				}
				$vendor_order->save();
			}
			$captured[]       = ( isset( $charge->captured ) && $charge->captured ) ? 'yes' : 'no';
			$statuses[]       = $charge->status;
			$transaction_id[] = $charge->id;
			WCV_SC_Logger::log( '$charge->balance_transaction: ' . print_r( $charge->balance_transaction, true ) );
			WCV_SC_Logger::log( '$vendor_id: ' . print_r( $vendor_id, true ) );
			$this->update_fees( $order, $charge->balance_transaction, $vendor_id );
		}

		$captured       = array_unique( $captured );
		$statuses       = array_unique( $statuses );
		$transaction_id = implode( ',', $transaction_id );

		if ( in_array( 'yes', $captured ) ) {
			$captured = 'yes';
		} else {
			$captured = 'no';
		}

		$status = 'pending';
		if ( in_array( 'succeeded', $statuses ) ) {
			$status = 'succeeded';
		} elseif ( in_array( 'failed', $statuses ) ) {
			$status = 'failed';
		}

		if ( 'yes' === $captured ) {
			$order->update_meta_data( '_stripe_charge_captured', $captured );

			/**
			 * Charge can be captured but in a pending state. Payment methods
			 * that are asynchronous may take couple days to clear. Webhook will
			 * take care of the status changes.
			 */
			if ( 'pending' === $status ) {
				$order_stock_reduced = $order->get_meta( '_order_stock_reduced', true );

				if ( ! $order_stock_reduced ) {
					wc_reduce_stock_levels( $order_id );
				}

				$order->set_transaction_id( $transaction_id );
				/* translators: transaction id */
				$order->update_status( 'on-hold', sprintf( __( 'Stripe charge awaiting payment: %s.', 'wc-vendors-gateway-stripe-connect' ), $transaction_id ) );
			}

			if ( 'succeeded' === $status ) {
				$order->payment_complete( $transaction_id );
				$order->update_meta_data(
					'_wcv_sc_charge_type',
					WCV_SC_Helper::use_charges_transfers() ? 'charge_transfer' : 'direct_charge'
				);

				/* translators: transaction id */
				$message = sprintf( __( 'Stripe charge complete (Charge ID: %s)', 'wc-vendors-gateway-stripe-connect' ), $transaction_id );
				$order->add_order_note( $message );
				WCV_SC_Logger::log( $message );

				if ( WCV_SC_Helper::use_charges_transfers() ) {
					try {
						$this->maybe_transfer_commission( $order );
					} catch ( Exception $e ) {
						WCV_SC_Logger::log( 'Error: ' . $e->getMessage() );
					}
				} else {
					$this->mark_commission_paid( $order );
				}
			}

			if ( 'failed' === $status ) {
				$localized_message = __( 'Payment processing failed. Please retry.', 'wc-vendors-gateway-stripe-connect' );
				$order->add_order_note( $localized_message );
				throw new WCV_SC_Exception( print_r( $response, true ), $localized_message );
			}
		} else {
			$order->set_transaction_id( $transaction_id );

			if ( $order->has_status( array( 'pending', 'failed' ) ) ) {
				wc_reduce_stock_levels( $order_id );
			}

			/* translators: transaction id */
			$order->update_status( 'on-hold', sprintf( __( 'Stripe charge authorized (Charge ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'wc-vendors-gateway-stripe-connect' ), $transaction_id ) );
		}

		if ( is_callable( array( $order, 'save' ) ) ) {
			$order->save();
		}

		do_action( 'wcv_sc_process_response', $response, $order );

		return $response;
	}

	public function get_vendors_products_from_order( $order ) {
		$vendors_due     = WCV_Vendors::get_vendor_dues_from_order( $order, false );
		$vendor_products = array();

		foreach ( $vendors_due as $vendor_id => $products ) {
			if ( $vendor_id == 1 ) {
				continue;
			}

			$connected_account_id = get_user_meta( $vendor_id, '_stripe_connect_user_id', true );

			if ( ! $connected_account_id ) {
				continue;
			}

			$vendor_products[ $vendor_id ] = array_map(
				function( $product ) {
					return $product['product_id'];
				},
				$products
			);
		}

		return $vendor_products;
	}

	public function mark_commission_paid( $order ) {
		$vendors_products = $this->get_vendors_products_from_order( $order );

		foreach ( $vendors_products as $vendor_id => $product_ids ) {
			foreach ( $product_ids as $product_id ) {
				WCV_Commission::set_vendor_product_commission_paid( $vendor_id, $product_id, $order->get_id() );
				WCV_SC_Logger::log( sprintf( 'Marked product #%s in order #%s of vendor #%s paid', $product_id, $order->get_id(), $vendor_id ) );
			}
		}
	}

	/**
	 * ATM, we transfer the fund instantly after the payment is complete.
	 * This will be changed in the future release.
	 *
	 * @version 2.1.0
	 * 
	 * @param $order WC_Order
	 */
	public function maybe_transfer_commission( $order ) {
		$stripe_fee        = WCV_SC_Helper::get_stripe_fee( $order );
		$total             = $order->get_total();
		$currency          = $order->get_currency();
		$vendor_dues       = WCV_Vendors::get_vendor_dues_from_order( $order, false );
		$_order_data       = array();
		$vendor_order_data = array();
		$transaction_id    = $order->get_transaction_id();

		foreach ( $vendor_dues as $vendor_id => $order_items ) {
			if ( $vendor_id == 1 ) {
				continue;
			}

			$vendor_order_data[ $vendor_id ] = array(
				'total'       => 0,
				'commission'  => 0,
				'product_ids' => array(),
			);

			foreach ( $order_items as $item_id => $item ) {
				$_order_data[ $item_id ] = array(
					'vendor_id'  => $item['vendor_id'],
					'product_id' => $item['product_id'],
					'total'      => $item['total'],
					'commission' => $item['total'],
				);
			}
		}

		foreach ( $vendor_dues[1] as $item_id => $item ) {
			if ( ! isset( $_order_data[ $item_id ] ) ) {
				continue;
			}
			$_order_data[ $item_id ]['total'] += $item['commission'];
		}

		foreach ( $_order_data as $data ) {
			$vendor_order_data[ $data['vendor_id'] ]['total']        += $data['total'];
			$vendor_order_data[ $data['vendor_id'] ]['commission']   += $data['commission'];
			$vendor_order_data[ $data['vendor_id'] ]['product_ids'][] = $data['product_id'];
		}

		foreach ( $vendor_order_data as $vendor_id => $data ) {
			$vendor_order         = $this->get_vendor_order_from_parent_order( $order, $vendor_id );
			$connected_account_id = get_user_meta( $vendor_id, '_stripe_connect_user_id', true );
			if ( ! $connected_account_id ) {
				continue;
			}

			$amount = WCV_SC_Helper::get_stripe_amount( $data['commission'], $currency );
			$settings = get_option( 'woocommerce_stripe-connect_settings', array() );
			if ( wc_string_to_bool( $settings['stripe_splitting_fee'] ) && wc_string_to_bool( $settings['enable_charges_transfers'] ) ) {
				$vendor_order_stripe_fee = (float) $stripe_fee * (float) $data['total'] / (float) $total;
				$vendor_order_stripe_fee = round( $vendor_order_stripe_fee );
				$amount                  = WCV_SC_Helper::get_stripe_amount( $data['commission'], $currency ) - $vendor_order_stripe_fee;
			}

			if ( $amount <= 0 ) {
				continue;
			}

			$transfer_group = $this->get_transfer_group( $order->get_id() );

			$request = array(
				'amount'             => $amount,
				'currency'           => $currency,
				'source_transaction' => $transaction_id,
				'destination'        => $connected_account_id,
				'transfer_group'     => $transfer_group,
				'description'        => sprintf( __('Transfer for order #%1$s - %2$s'), $order->get_id(), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ), 
			);

			$transfer = Transfer::create( $request );

			$transfer_data = array(
				'id'         => $transfer->id,
				'amount'     => $transfer->amount,
				'commission' => $data['commission'],
				'total'      => $data['total'],
				'source_transaction' => $transaction_id,
			);
			WCV_SC_Helper::update_stripe_transfer_data( $vendor_order, $transfer_data );

			WCV_SC_Helper::update_stripe_fee( $vendor_order, $vendor_order_stripe_fee );
			WCV_SC_Helper::update_stripe_net( $vendor_order, $amount );

			array_map(
				function( $product_id ) use ( $vendor_id, $order ) {
					WCV_Commission::set_vendor_product_commission_paid( $vendor_id, $product_id, $order->get_id() );
				},
				$data['product_ids']
			);

			if ( is_callable( array( $vendor_order, 'save' ) ) ) {
				$vendor_order->save();
			}
		}
	}

	/**
	 * Sends the failed order email to admin.
	 *
	 * @since   3.1.0
	 * @version 2.0.0
	 * @param   int $order_id
	 */
	public function send_failed_order_email( $order_id ) {
		$emails = WC()->mailer()->get_emails();
		if ( ! empty( $emails ) && ! empty( $order_id ) ) {
			$emails['WC_Email_Failed_Order']->trigger( $order_id );
		}
	}

	/**
	 * Get source object by source id.
	 *
	 * @since 2.0.0
	 * @param string $source_id The source ID to get source object for.
	 */
	public function get_source_object( $source_id = '' ) {
		if ( empty( $source_id ) ) {
			return '';
		}

		$source_object = Source::retrieve( $source_id );

		return $source_object;
	}

	/**
	 * Checks if card is a prepaid card.
	 *
	 * @since  2.0.0
	 * @param  object $source_object
	 * @return bool
	 */
	public function is_prepaid_card( $source_object ) {
		return ( $source_object && 'token' === $source_object->object && 'prepaid' === $source_object->card->funding );
	}

	/**
	 * Checks if source is of legacy type card.
	 *
	 * @since  2.0.0
	 * @param  string $source_id
	 * @return bool
	 */
	public function is_type_legacy_card( $source_id ) {
		return ( preg_match( '/^card_/', $source_id ) );
	}

	/**
	 * Checks if payment is via saved payment source.
	 *
	 * @since  2.0.0
	 * @return bool
	 */
	public function is_using_saved_payment_method() {
		$payment_method = isset( $_POST['payment_method'] ) ? wc_clean( $_POST['payment_method'] ) : 'stripe-connect';

		return ( isset( $_POST[ 'wc-' . $payment_method . '-payment-token' ] ) && 'new' !== $_POST[ 'wc-' . $payment_method . '-payment-token' ] );
	}

	/**
	 * Get payment source. This can be a new token/source or existing WC token.
	 * If user is logged in and/or has WC account, create an account on Stripe.
	 * This way we can attribute the payment to the user to better fight fraud.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 * @param   string $user_id
	 * @param   bool   $force_save_source Should we force save payment source.
	 *
	 * @throws Exception When card was not added or for and invalid card.
	 * @return object
	 */
	public function prepare_source( $user_id, $force_save_source = false, $customer = array() ) {
		if ( 0 != $user_id || empty( $customer ) ) {
			$customer      = new WCV_SC_Customer( $user_id );
		}
		$force_save_source = apply_filters( 'wcv_sc_force_save_source', $force_save_source, $customer );
		$source_object     = '';
		$source_id         = '';
		$wc_token_id       = false;
		$payment_method    = isset( $_POST['payment_method'] ) ? wc_clean( $_POST['payment_method'] ) : 'stripe-connect';
		$is_token          = false;

		// New CC info was entered and we have a new source to process.
		if ( ! empty( $_POST['stripe_connect_source'] ) ) {
			$source_object = self::get_source_object( wc_clean( $_POST['stripe_connect_source'] ) );
			$source_id     = $source_object->id;

			// This checks to see if customer opted to save the payment method to file.
			$maybe_saved_card = isset( $_POST[ 'wc-' . $payment_method . '-new-payment-method' ] ) && ! empty( $_POST[ 'wc-' . $payment_method . '-new-payment-method' ] );

			/**
			 * Save the card to the users my-account page if they have selected to save the card
			 */
			if ( ( $user_id && $this->saved_cards && $maybe_saved_card && 'reusable' === $source_object->usage ) || $force_save_source ) {
				$stripe_response = $customer->add_source( $source_object->id );
			}
		} elseif ( $this->is_using_saved_payment_method() ) {
			// Use an existing token, and then process the payment.
			$wc_token_id = wc_clean( $_POST[ 'wc-' . $payment_method . '-payment-token' ] );
			$wc_token    = WC_Payment_Tokens::get( $wc_token_id );

			if ( ! $wc_token || $wc_token->get_user_id() !== get_current_user_id() ) {
				WC()->session->set( 'refresh_totals', true );
				throw new WCV_SC_Exception( 'Invalid payment method', __( 'Invalid payment method. Please input a new card number.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			$source_id = $wc_token->get_token();

			if ( $this->is_type_legacy_card( $source_id ) ) {
				$is_token = true;
			}
		} elseif ( isset( $_POST['stripe_connect_token'] ) && 'new' !== $_POST['stripe_connect_token'] ) {
			$stripe_connect_token = wc_clean( $_POST['stripe_connect_token'] );
			$maybe_saved_card     = isset( $_POST[ 'wc-' . $payment_method . '-new-payment-method' ] ) && ! empty( $_POST[ 'wc-' . $payment_method . '-new-payment-method' ] );

			// This is true if the user wants to store the card to their account.
			if ( ( $user_id && $this->saved_cards && $maybe_saved_card ) || $force_save_source ) {
				$stripe_response = $customer->add_source( $stripe_connect_token );
			} else {
				$source_id    = $stripe_connect_token;
				$is_token     = true;
			}
		}

		$customer_id = $customer->get_id() ? $customer->get_id() : false;

		if ( empty( $source_object ) && ! $is_token ) {
			$source_object = self::get_source_object( $source_id );
		}

		// Attach the source to the customer.
		if ( $customer_id && $source_id ) {
			$customer_source = '';
			try {
				$customer_source = \Stripe\Customer::retrieveSource( $customer_id, $source_id );
			} catch ( Exception $e ) {
				WCV_SC_Logger::log( 'Customer Source Error: ' . $e->getMessage() );
			}

			if ( ! $customer_source ) {
				$customer_source = \Stripe\Customer::createSource(
					$customer_id,
					array(
						'source' => $source_id
					)
				);

				$source_object = $customer_source;
			}
		}

		return (object) array(
			'token_id'      => $wc_token_id,
			'customer'      => $customer_id,
			'source'        => $source_id,
			'source_object' => $source_object,
		);
	}

	/**
	 * Get payment source from an order. This could be used in the future for
	 * a subscription as an example, therefore using the current user ID would
	 * not work - the customer won't be logged in :)
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 * @param   object $order
	 * @return  object
	 */
	public function prepare_order_source( $order = null ) {
		$stripe_customer = new WCV_SC_Customer();
		$stripe_source   = false;
		$token_id        = false;
		$source_object   = false;

		if ( $order ) {
			$order_id = $order->get_id();

			$stripe_customer_id = get_post_meta( $order_id, '_wcv_sc_customer_id', true );

			if ( empty( $stripe_customer_id ) ) {
				$stripe_customer_id = get_user_option( '_stripe_customer_id', $order->get_customer_id() );
			}

			if ( $stripe_customer_id ) {
				$stripe_customer->set_id( $stripe_customer_id );
			}

			$source_id = $order->get_meta( '_stripe_source_id', true );

			if ( $source_id ) {
				$stripe_source = $source_id;
				$source_object = self::get_source_object( $source_id );
			} elseif ( apply_filters( 'wcv_sc_use_default_customer_source', true ) ) {
				$stripe_source = '';
			}
		}

		return (object) array(
			'token_id'      => $token_id,
			'customer'      => $stripe_customer ? $stripe_customer->get_id() : false,
			'source'        => $stripe_source,
			'source_object' => $source_object,
		);
	}

	/**
	 * Save source to order.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 * @param   WC_Order $order  For to which the source applies.
	 * @param   stdClass $source Source information.
	 */
	public function save_source_to_order( $order, $source ) {

		// Store source in the order.
		if ( $source->customer ) {
			$order->update_meta_data( '_wcv_sc_customer_id', $source->customer );
		}

		if ( $source->source ) {
			$order->update_meta_data( '_stripe_source_id', $source->source );
		}

		if ( is_callable( array( $order, 'save' ) ) ) {
			$order->save();
		}
	}

	/**
	 * Updates Stripe fees/net.
	 * e.g usage would be after a refund.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 * @param   object $order                  The order object
	 * @param   int    $balance_transaction_id
	 */
	public function update_fees( $order, $balance_transaction_id, $vendor_id = '' ) {
		$order_id     = $order->get_id();
		$vendor_order = $this->get_vendor_order_from_parent_order( $order, $vendor_id );

		$stripe_account_id = $vendor_id ? get_user_meta( $vendor_id, '_stripe_connect_user_id', true ) : '';

		if ( $stripe_account_id ) {
			$balance_transaction = BalanceTransaction::retrieve(
				$balance_transaction_id,
				array( 'stripe_account' => $stripe_account_id )
			);
		} else {
			$balance_transaction = BalanceTransaction::retrieve( $balance_transaction_id );
		}

		if ( empty( $balance_transaction->error ) ) {
			if ( isset( $balance_transaction ) && isset( $balance_transaction->fee ) ) {
				$fee_refund = 0;

				// We need only Stripe fee, not application fee.
				if ( ! empty( $balance_transaction->net ) && ! empty( $balance_transaction->fee_details ) ) {
					$fees = array_filter(
						$balance_transaction->fee_details,
						function( $item ) {
							return 'application_fee' != $item['type'];
						}
					);

					$fees       = wp_list_pluck( $fees, 'amount' );
					$fee_refund = array_sum( $fees );
				}

				// Fees and Net needs to both come from Stripe to be accurate as the returned
				// values are in the local currency of the Stripe account, not from WC.
				$net_refund = ! empty( $balance_transaction->net ) ? $balance_transaction->net : 0;

				// Current data fee & net.
				$fee_current = WCV_SC_Helper::get_stripe_fee( $order );
				$net_current = WCV_SC_Helper::get_stripe_net( $order );

				// Calculation.
				$fee = (int) $fee_current + (int) $fee_refund;
				$net = (int) $net_current + (int) $net_refund;

				WCV_SC_Helper::update_stripe_fee( $order, $fee );
				WCV_SC_Helper::update_stripe_net( $order, $net );

				$currency = ! empty( $balance_transaction->currency ) ? strtoupper( $balance_transaction->currency ) : null;
				WCV_SC_Helper::update_stripe_currency( $order, $currency );

				if ( is_callable( array( $order, 'save' ) ) ) {
					$order->save();
				}

				if ( $stripe_account_id ) {
					$this->update_fees_for_vendor_order( $vendor_order, $balance_transaction );
				}
			}
		} else {
			WCV_SC_Logger::log( "Unable to update fees/net meta for order: {$order_id}" );
		}
	}

	public function update_fees_for_vendor_order( $vendor_order, $balance_transaction ) {
		if ( ! $vendor_order ) {
			return;
		}

		$fee_refund = ! empty( $balance_transaction->fee ) ? $balance_transaction->fee : 0;
		$net_refund = ! empty( $balance_transaction->net ) ? $balance_transaction->net : 0;

		// Current data fee & net.
		$fee_current = WCV_SC_Helper::get_stripe_fee( $vendor_order );
		$net_current = WCV_SC_Helper::get_stripe_net( $vendor_order );

		// Calculation.
		$fee = (int) $fee_current + (int) $fee_refund;
		$net = (int) $net_current + (int) $net_refund;

		WCV_SC_Helper::update_stripe_fee( $vendor_order, $fee );
		WCV_SC_Helper::update_stripe_net( $vendor_order, $net );

		$currency = ! empty( $balance_transaction->currency ) ? strtoupper( $balance_transaction->currency ) : null;
		WCV_SC_Helper::update_stripe_currency( $vendor_order, $currency );

		if ( is_callable( array( $vendor_order, 'save' ) ) ) {
			$vendor_order->save();
		}
	}

	/**
	 * Refund a charge.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 * @param   int   $order_id
	 * @param   float $amount
	 * @return  bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order             = wc_get_order( $order_id );
		$vendor_id         = $this->get_vendor_from_parent_order( $order );
		$stripe_account_id = get_user_meta( $vendor_id, '_stripe_connect_user_id', true );

		if (
			! $vendor_id
			|| ( WCV_Vendors::is_vendor( $vendor_id ) && $stripe_account_id )
		) {
			return new WP_Error( 'invalid', __( 'This order can not be refunded by marketplace admin.', 'wc-vendors-gateway-stripe-connect' ) );
		}

		$request = array();

		$request['reason'] = 'requested_by_customer';

		$order_currency = $order->get_currency();
		$captured       = $order->get_meta( '_stripe_charge_captured', true );

		if ( ! is_null( $amount ) ) {
			$request['amount'] = WCV_SC_Helper::get_stripe_amount( $amount, $order_currency );
		}

		// If order is only authorized, don't pass amount.
		if ( 'yes' !== $captured ) {
			unset( $request['amount'] );
		}

		if ( $reason ) {
			$request['metadata'] = array(
				'reason' => $reason,
			);
		}

		$request['charge'] = $order->get_transaction_id();

		WCV_SC_Logger::log( "Info: Beginning refund for order {$order->get_transaction_id()} for the amount of {$amount}" );

		$request = apply_filters( 'wcv_sc_refund_request', $request, $order );

		if ( $stripe_account_id ) {
			$response = \Stripe\Refund::create( $request, array( 'stripe_account' => $stripe_account_id ) );
		} else {
			$response = \Stripe\Refund::create( $request );
		}

		if ( ! empty( $response->error ) ) {
			WCV_SC_Logger::log( 'Error: ' . $response->error->message );
			WCV_SC_Logger::log( print_r( $response, true ) );

			return $response;
		} elseif ( ! empty( $response->id ) ) {
			$order->update_meta_data( '_stripe_refund_id', $response->id );

			$amount = wc_price( $response->amount / 100 );

			if ( in_array( strtolower( $order->get_currency() ), WCV_SC_Helper::no_decimal_currencies() ) ) {
				$amount = wc_price( $response->amount );
			}

			if ( isset( $response->balance_transaction ) ) {
				$this->update_fees( $order, $response->balance_transaction, $vendor_id );
			}
			/* translators: 1) dollar amount 2) transaction id 3) refund message */
			$refund_message = ( isset( $captured ) && 'yes' === $captured ) ? sprintf( __( 'Refunded %1$s - Refund ID: %2$s - Reason: %3$s', 'wc-vendors-gateway-stripe-connect' ), $amount, $response->id, $reason ) : __( 'Pre-Authorization Released', 'wc-vendors-gateway-stripe-connect' );

			$order->add_order_note( $refund_message );
			WCV_SC_Logger::log( 'Success: ' . html_entity_decode( wp_strip_all_tags( $refund_message ) ) );

			return true;
		}
	}

	/**
	 * Refund a charge for vendor.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 *
	 * @param WC_Order        $order     Order instance.
	 * @param int             $vendor_id Vendor ID.
	 * @param WC_Order_Refund $refund    Amount to refund.
	 *
	 * @todo Check for connected status of vendor when the order is paid. If not, mark the refund failed.
	 *
	 * @return  mixed
	 */
	public function process_vendor_refund( $order, $vendor_id, $refund ) {
		try {
			if ( ! $order || ! $order->get_transaction_id() || ! $vendor_id ) {
				return false;
			}

			$stripe_account_id = get_user_meta( $vendor_id, '_stripe_connect_user_id', true );
			if ( ! $stripe_account_id ) {
				return new WP_Error( 'invalid', __( 'The vendor is not a connected vendor.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			$vendor_order = $this->get_vendor_order_from_parent_order( $order, $vendor_id );
			if ( ! $vendor_order ) {
				return new WP_Error( 'invalid', __( 'Can not find the original vendor order to refund.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			$charge_type = $order->get_meta( '_wcv_sc_charge_type' );
			if ( ! $charge_type ) {
				return new WP_Error( 'invalid', __( 'This order was processed with WC Vendors Stripe Connect v2.0.2 or earlier. Try manual refund instead.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			if ( 'charge_transfer' === $charge_type ) {
				$transaction_id = $order->get_transaction_id();
			} else {
				$transaction_id = $vendor_order->get_transaction_id();
			}

			if ( ! $transaction_id ) {
				return new WP_Error( 'invalid', __( 'Could not find the transaction id of order.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			$order_currency = $order->get_currency();
			$captured       = $order->get_meta( '_stripe_charge_captured', true );
			$request        = array(
				'amount' => WCV_SC_Helper::get_stripe_amount( $refund->get_amount(), $order_currency ),
				'reason' => 'requested_by_customer',
			);

			if ( 'yes' !== $captured ) { // If order is only authorized, don't pass amount.
				unset( $request['amount'] );
			} else { // Try reverse transfer or refund application fee before doing the refund
				$this->reverse_transfer_or_refund_application_fee( $refund, $vendor_order, $vendor_id, $charge_type );
			}

			if ( $refund->get_reason() ) {
				$request['metadata'] = array(
					'reason' => $refund->get_reason(),
				);
			}

			$request['charge'] = $transaction_id;

			WCV_SC_Logger::log( "Info: Beginning refund for order {$transaction_id} for the amount of {$refund->get_amount()}" );

			$request = apply_filters( 'wcv_sc_vendor_refund_request', $request, $order, $vendor_id );

			if ( 'charge_transfer' !== $charge_type ) {
				$response = \Stripe\Refund::create( $request, array( 'stripe_account' => $stripe_account_id ) );
			} else {
				$response = \Stripe\Refund::create( $request );
			}

			WCV_SC_Logger::log( 'Refund request: ' . print_r( $request, true ) );
			WCV_SC_Logger::log( 'Refund response: ' . print_r( $response, true ) );

			$order_stripe_refund = $order->get_meta( '_stripe_refund_id' );
			if ( ! $order_stripe_refund ) {
				$order_stripe_refund = array();
			}
			$order_stripe_refund[ $vendor_id ][] = $response->id;
			$order->update_meta_data( '_stripe_refund_id', $order_stripe_refund );

			$amount = wc_price( $response->amount / 100 );

			if ( in_array( strtolower( $order->get_currency() ), WCV_SC_Helper::no_decimal_currencies() ) ) {
				$amount = wc_price( $response->amount );
			}

			if ( isset( $response->balance_transaction ) ) {
				if ( WCV_SC_Helper::use_charges_transfers() ) {
					$this->update_fees( $order, $response->balance_transaction );
				} else {
					$this->update_fees( $order, $response->balance_transaction, $vendor_id );
				}
			}

			/* translators: 1) dollar amount 2) transaction id 3) refund message */
			$refund_message = ( isset( $captured ) && 'yes' === $captured ) ? sprintf( __( 'Refunded %1$s - Refund ID: %2$s - Reason: %3$s', 'wc-vendors-gateway-stripe-connect' ), $amount, $response->id, $refund->get_reason() ) : __( 'Pre-Authorization Released', 'wc-vendors-gateway-stripe-connect' );

			$order->add_order_note( $refund_message );
			WCV_SC_Logger::log( 'Success: ' . html_entity_decode( wp_strip_all_tags( $refund_message ) ) );

			$this->mark_commission_refund( $order, $vendor_id, $refund );

			return array(
				'captured'  => $captured,
				'refund_id' => $response->id,
			);
		} catch ( Exception $e ) {
			WCV_SC_Logger::log( $e->getMessage() );
		}
	}

	/**
	 * Calculate the amount of application to refund or the amount to reverse from connected account.
	 *
	 * @param WC_Order_Refund $refund
	 * @param WC_Order_Vendor $vendor_order
	 * @param int             $vendor_id
	 * @param string          $charge_type
	 */
	public function reverse_transfer_or_refund_application_fee( $refund, $vendor_order, $vendor_id, $charge_type ) {
		$lines = $this->get_commission_data_for_refund( $refund, $vendor_order, $vendor_id );

		if ( 'charge_transfer' === $charge_type ) {
			WCV_SC_Logger::log( 'Info: Try to reverse transfers before refunding to the customer.' );
			$amount_to_reverse = 0;
			foreach ( $lines as $line ) {
				$amount_to_reverse += $line['refund']['line'] * $line['commission']['total_due'] / $line['total'];

				if ( $line['commission']['total_shipping'] ) {
					$amount_to_reverse += $line['refund']['shipping'];
				}

				if ( $line['commission']['tax'] ) {
					$amount_to_reverse += $line['refund']['tax'];
				}
			}
			$this->reverse_transfer( $vendor_order, $amount_to_reverse );
		} elseif ( 'direct_charge' === $charge_type && $vendor_order->get_meta( '_wcv_sc_application_fee_id' ) ) {
			WCV_SC_Logger::log( 'Info: Try to refund application fee before refunding to the customer.' );
			$fee_to_refund = 0;

			foreach ( $lines as $line ) {
				$admin_commission = $line['total'] - $line['commission']['total_due'];
				$fee_to_refund   += $line['refund']['line'] * $admin_commission / $line['total'];

				if ( ! $line['commission']['total_shipping'] ) {
					$fee_to_refund += $line['refund']['shipping'];
				}

				if ( ! $line['commission']['tax'] ) {
					$fee_to_refund += $line['refund']['tax'];
				}
			}

			if ( ! $fee_to_refund ) {
				WCV_SC_Logger::log( sprintf( 'There is no application fee for order: #%s.', $vendor_order->get_id() ) );
			} else {
				$application_fee_refund = ApplicationFee::createRefund( $vendor_order->get_meta( '_wcv_sc_application_fee_id' ), array( 'amount' => WCV_SC_Helper::get_stripe_amount( $fee_to_refund ) ) );
				WCV_SC_Logger::log( print_r( $application_fee_refund, true ) );
			}
		}
	}

	public function reverse_transfer( $vendor_order, $amount ) {
		$transfer_data = WCV_SC_Helper::get_stripe_transfer_data( $vendor_order );

		if ( ! $transfer_data['id'] ) {
			return;
		}

		return Transfer::createReversal(
			$transfer_data['id'],
			array( 'amount' => WCV_SC_Helper::get_stripe_amount( $amount ) )
		);
	}

	/**
	 * @param WC_Order        $order
	 * @param int             $vendor_id
	 * @param WC_Order_Refund $refund
	 */
	public function mark_commission_refund( $order, $vendor_id, $refund ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pv_commission';

		$vendor_order = $this->get_vendor_order_from_parent_order( $order, $vendor_id );
		$lines        = $this->get_commission_data_for_refund( $refund, $vendor_order, $vendor_id );
		foreach ( $lines as $product_id => $line ) {
			// Full refund
			if ( array_sum( $line['commission'] ) <= array_sum( $line['refund'] ) ) {
				$query = "UPDATE `{$table_name}` SET `status` = 'refund' WHERE `order_id` = %d AND `vendor_id` = %d AND `product_id` = %d";
				$wpdb->query( $wpdb->prepare( $query, $order->get_id(), $vendor_id, $product_id ) );
			} else {
				$query = "UPDATE `{$table_name}` SET `status` = 'partial-refund', `total_due` = %d, `total_shipping` = %d, `tax` = %d WHERE `order_id` = %d AND `vendor_id` = %d AND `product_id` = %d";
				$wpdb->query(
					$wpdb->prepare(
						$query,
						$line['commission']['total_due'] - $line['refund']['line'] * $line['commission']['total_due'] / $line['total'],
						$line['commission']['total_shipping'] ? $line['commission']['total_shipping'] - $line['refund']['shipping'] : 0,
						$line['commission']['tax'] ? $line['commission']['tax'] - $line['refund']['tax'] : 0,
						$order->get_id(),
						$vendor_id,
						$product_id
					)
				);
			}
		}
	}

	/**
	 * Check if order can by refund by using WooCommerce > Orders interface.
	 * We support refund only for orders match one of following criterias:
	 * - Order that has items from only one connected vendors.
	 * - Order that has items from non-connected vendors only.
	 *
	 * @param mixed $order   Order object or ID.
	 * @param mixed $amount Amount to refund.
	 *
	 * @return bool
	 */
	public function can_order_refund( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order );
		}

		$transaction_id = $order->get_transaction_id();

		if ( $transaction_id && strpos( $transaction_id, ',' ) === false ) {
			return true;
		}

		return false;
	}

	/**
	 * Get vendor ID from an order.
	 * This is for order that has only one vendor.
	 *
	 * @param mixed $order Order object or ID.
	 *
	 * @return string
	 */
	public function get_vendor_from_parent_order( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $this->can_order_refund( $order ) ) {
			return false;
		}

		$vendors = array();
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
			$vendors[]  = WCV_Vendors::get_vendor_from_product( $product_id );
		}

		if ( empty( $vendors ) || count( $vendors ) > 1 ) {
			return false;
		}

		return reset( $vendors );
	}

	public function get_vendor_order_from_parent_order( $order, $vendor_id ) {
		if (
			! is_a( $order, 'WC_Order' )
			|| ! $vendor_id
			|| $vendor_id == 1
		) {
			return false;
		}

		$vendor_orders = WCV_Vendors::get_vendor_orders( $order->get_id() );

		foreach ( $vendor_orders as $vendor_order ) {
			if ( $vendor_id == $vendor_order->get_meta( '_vendor_id' ) ) {
				return $vendor_order;
			}
		}
	}

	/**
	 * Add payment method via account screen.
	 * We don't store the token locally, but to the Stripe platform.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 */
	public function add_payment_method() {
		$error     = false;
		$error_msg = __( 'There was a problem adding the payment method.', 'wc-vendors-gateway-stripe-connect' );

		if ( empty( $_POST['stripe_connect_source'] ) && empty( $_POST['stripe_connect_token'] ) || ! is_user_logged_in() ) {
			$error = true;
		}

		$stripe_customer = new WCV_SC_Customer( get_current_user_id() );

		$source_id = ! empty( $_POST['stripe_connect_source'] ) ? wc_clean( $_POST['stripe_connect_source'] ) : '';

		$source_object = self::get_source_object( $source_id );

		if ( isset( $source_object ) ) {
			if ( ! empty( $source_object->error ) ) {
				$error = true;
			}

			$source_id = $source_object->id;
		} elseif ( isset( $_POST['stripe_connect_token'] ) ) {
			$source_id = wc_clean( $_POST['stripe_connect_token'] );
		}

		$stripe_customer->add_source( $source_id );

		if ( $error ) {
			wc_add_notice( $error_msg, 'error' );
			WCV_SC_Logger::log( 'Add payment method Error: ' . $error_msg );
			return;
		}

		do_action( 'wcv_sc_add_payment_method_' . $_POST['payment_method'] . '_success', $source_id, $source_object );

		return array(
			'result'   => 'success',
			'redirect' => wc_get_endpoint_url( 'payment-methods' ),
		);
	}

	/**
	 * Gets the locale with normalization that only Stripe accepts.
	 *
	 * @since  2.0.0
	 * @return string $locale
	 */
	public function get_locale() {
		$locale = get_locale();

		/*
		* Stripe expects Norwegian to only be passed NO.
		* But WP has different dialects.
		*/
		if ( 'NO' === substr( $locale, 3, 2 ) ) {
			$locale = 'no';
		} else {
			$locale = substr( get_locale(), 0, 2 );
		}

		return $locale;
	}

	/**
	 * Change the idempotency key so charge can
	 * process order as a different transaction.
	 *
	 * @since 2.0.0
	 * @param string $idempotency_key
	 * @param array  $request
	 */
	public function change_idempotency_key( $idempotency_key, $request ) {
		$customer = ! empty( $request['customer'] ) ? $request['customer'] : '';
		$source   = ! empty( $request['source'] ) ? $request['source'] : $customer;
		$count    = $this->retry_interval;

		return $request['metadata']['order_id'] . '-' . $count . '-' . $source;
	}

	/**
	 * Checks if request is the original to prevent double processing
	 * on WC side. The original-request header and request-id header
	 * needs to be the same to mean its the original request.
	 *
	 * @since 2.0.0
	 * @param array $headers
	 */
	public function is_original_request( $headers ) {
		if ( $headers['original-request'] === $headers['request-id'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Log the commissions as paid for the vendor
	 *
	 * @since 2.0.0
	 * @param array $vendors_paid
	 */
	public function log_commission( $vendors_paid ) {
		if ( ! class_exists( 'WCV_Commission' ) ) {
			return;
		}

		foreach ( $vendors_paid as $vendor_id => $products ) {
			foreach ( $products as $product ) {
				WCV_Commission::set_vendor_product_commission_paid(
					$vendor_id,
					$product['product_id'],
					$product['order_id']
				);
			}
		}
	}

	/**
	 * Create the connected payments using Separate Charges and Transfers.
	 *
	 * @since   2.0.0
	 * @version 2.0.0
	 * @param   WC_Order $order
	 * @param   object   $prepared_source
	 * @return  array
	 */
	public function generate_charges_transfers_payment( $order, $prepared_source, $retry, $force_save_source, $previous_error ) {
		$intent = $this->get_intent_from_order( $order );

		if ( ! $intent ) {
			$intent = $this->create_intent( $order, $prepared_source );
		}

		if ( ! empty( $intent->error ) ) {
			$this->maybe_remove_non_existent_customer( $intent->error, $order );

			// We want to retry.
			if ( $this->is_retryable_error( $intent->error ) ) {
				return $this->retry_after_error( $intent, $order, $retry, $force_save_source, $previous_error );
			}

			$this->throw_localized_message( $intent, $order );
		}

		if ( 'requires_payment_method' == $intent->status ) {
			$intent->confirm(
				array(
					'source' => $prepared_source->source,
				)
			);
		}

		if ( 'requires_action' == $intent->status ) {
			return array(
				'result'        => 'success',
				'redirect'      => $this->get_return_url( $order ),
				'intent_secret' => $intent->client_secret,
			);
		}

		$charges = array(
			1 => end( $intent->charges->data ),
		);

		return (object) array(
			'charges' => $charges,
		);
	}

	public function get_intent_from_order( $order ) {
		$intent_id = $order->get_meta( '_stripe_intent_id' );

		if ( ! $intent_id ) {
			return false;
		}

		return PaymentIntent::retrieve( $intent_id );
	}

	protected function create_intent( $order, $prepared_source ) {
		$request = $this->prepare_common_payment_request_data( $order, $prepared_source );
		$request = array_merge(
			$request,
			array(
				'amount'         => WCV_SC_Helper::get_stripe_amount( $order->get_total(), $request['currency'] ),
				'source'         => $prepared_source->source,
				'transfer_group' => $this->get_transfer_group( $order->get_id() ),
			)
		);

		if ( $prepared_source->customer ) {
			$request['customer'] = $prepared_source->customer;
		}

		$intent   = PaymentIntent::create( $request );
		$order_id = $order->get_id();

		WCV_SC_Logger::log( "Stripe PaymentIntent $intent->id initiated for order $order_id : " . print_r( $intent, true ) );

		// Save the intent ID to the order.
		$this->save_intent_to_order( $order, $intent );

		return $intent;
	}

	/**
	 * Customer param wrong? The user may have been deleted on stripe's end. Remove customer_id. Can be retried without.
	 *
	 * @since 4.2.0
	 * @param object   $error The error that was returned from Stripe's API.
	 * @param WC_Order $order The order those payment is being processed.
	 * @return bool           A flag that indicates that the customer does not exist and should be removed.
	 */
	public function maybe_remove_non_existent_customer( $error, $order ) {
		if ( ! $this->is_no_such_customer_error( $error ) ) {
			return false;
		}

		delete_user_meta( $order->get_customer_id(), '_wcv_sc_customer_id' );
		$order->delete_meta_data( '_wcv_sc_customer_id' );
		$order->save();

		return true;
	}

	/**
	 * Retries the payment process once an error occured.
	 *
	 * @since 4.2.0
	 * @param object   $response          The response from the Stripe API.
	 * @param WC_Order $order             An order that is being paid for.
	 * @param bool     $retry             A flag that indicates whether another retry should be attempted.
	 * @param bool     $force_save_source Force save the payment source.
	 * @param mixed    $previous_error Any error message from previous request.
	 * @throws WCV_SC_Exception        If the payment is not accepted.
	 * @return array|void
	 */
	public function retry_after_error( $response, $order, $retry, $force_save_source, $previous_error ) {
		if ( ! $retry ) {
			$localized_message = __( 'Sorry, we are unable to process your payment at this time. Please retry later.', 'wc-vendors-gateway-stripe-connect' );
			$order->add_order_note( $localized_message );
			throw new WCV_SC_Exception( print_r( $response, true ), $localized_message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.
		}

		// Don't do anymore retries after this.
		if ( 5 <= $this->retry_interval ) {
			return $this->process_payment( $order->get_id(), false, $force_save_source, $response->error, $previous_error );
		}

		sleep( $this->retry_interval );
		$this->retry_interval++;

		return $this->process_payment( $order->get_id(), true, $force_save_source, $response->error, $previous_error );
	}

	/**
	 * Generates a localized message for an error, adds it as a note and throws it.
	 *
	 * @since  2.0.0
	 * @param  stdClass $response  The response from the Stripe API.
	 * @param  WC_Order $order     The order to add a note to.
	 * @throws WCV_SC_Exception An exception with the right message.
	 */
	public function throw_localized_message( $response, $order ) {
		$localized_messages = WCV_SC_Helper::get_localized_messages();

		if ( 'card_error' === $response->error->type ) {
			$localized_message = isset( $localized_messages[ $response->error->code ] ) ? $localized_messages[ $response->error->code ] : $response->error->message;
		} else {
			$localized_message = isset( $localized_messages[ $response->error->type ] ) ? $localized_messages[ $response->error->type ] : $response->error->message;
		}

		$order->add_order_note( $localized_message );

		throw new WCV_SC_Exception( print_r( $response, true ), $localized_message );
	}

	/**
	 * Saves intent to order.
	 *
	 * @since 2.0.0
	 * @param WC_Order $order For to which the source applies.
	 * @param stdClass $intent Payment intent information.
	 */
	public function save_intent_to_order( $order, $intent ) {
		$order->update_meta_data( '_stripe_intent_id', $intent->id );

		if ( is_callable( array( $order, 'save' ) ) ) {
			$order->save();
		}
	}

	public function get_transfer_group( $order_id ) {
		$domain = get_site_url( null, '', '' );
		$domain = str_replace( array( 'https://', 'http://' ), '', $domain );
		$domain = str_replace( array( '/', '.' ), '-', $domain );

		return apply_filters(
			'wcv_sc_get_transfer_group',
			$domain . '-' . $order_id,
			$order_id
		);
	}

	/**
	 * @param WC_Order_Refund $refund
	 * @param WC_Order_Vendor $vendor_order
	 * @param int             $vendor_id
	 *
	 * @return array
	 */
	public function get_commission_data_for_refund( $refund, $vendor_order, $vendor_id ) {
		global $wpdb;
		$table_name   = $wpdb->prefix . 'pv_commission';
		$lines        = array();
		$_commissions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT product_id, total_due, total_shipping, tax FROM `{$table_name}` WHERE `order_id` = %d AND `vendor_id` = %d",
				$refund->get_parent_id(),
				$vendor_id
			)
		);

		foreach ( $_commissions as $commission ) {
			$lines[ $commission->product_id ]['commission'] = $commission = array_map(
				function ( $item ) {
					return abs( $item );
				},
				(array) $commission
			);
		}

		$tax_refund       = $refund->get_total_tax();
		$lines_tax_refund = 0;
		foreach ( $refund->get_items() as $item ) {
			$lines[ $item->get_product_id() ]['refund'] = array(
				'line'     => abs( $item->get_total() ),
				'shipping' => abs( $refund->get_shipping_total() / count( $refund->get_items() ) ),
				'tax'      => abs( $item->get_total_tax() ),
			);
			$lines_tax_refund                          += abs( $item->get_total_tax() );
		}

		if ( $lines_tax_refund < $tax_refund ) {
			$remain = $tax_refund - $lines_tax_refund;
			foreach ( $lines as $line ) {
				$lines['refund']['tax'] += $remain / count( $refund->get_items() );
			}
		}

		foreach ( $vendor_order->get_items() as $item ) {
			$total                                     = ( 'no' === get_option( 'wcvendors_commission_coupon_action' ) ) ? $item->get_subtotal() : $item->get_total();
			$lines[ $item->get_product_id() ]['total'] = abs( $total );
		}

		return $lines;
	}

	/**
	 * Get all fees from cart.
	 *
	 * @return void
	 * @version 2.0.9
	 * @since   2.0.9
	 */
	public function get_cart_fees() {
		$fees = 0;

		if ( version_compare( WC_VERSION, '3.2', '<' ) ) {
			$cart_fees = WC()->cart->fees;
		} else {
			$cart_fees = WC()->cart->get_fees();
		}

		// Sum the the fees.
		foreach ( $cart_fees as $key => $fee ) {
			$fees += $fee->amount;
		}

		return apply_filters( 'wcv_sc_get_cart_fees', $fees );
	}

	/**
	 * Get owner details.
	 *
	 * @version 2.1.0
	 * @param object $order
	 * @return object $details
	 */
	public function get_owner_details( $order ) {
		$billing_first_name = $order->get_billing_first_name();
		$billing_last_name  = $order->get_billing_last_name();

		$details = [];

		$name  = $billing_first_name . ' ' . $billing_last_name;
		$email = $order->get_billing_email();
		$phone = $order->get_billing_phone();

		if ( ! empty( $phone ) ) {
			$details['phone'] = $phone;
		}

		if ( ! empty( $name ) ) {
			$details['name'] = $name;
		}

		if ( ! empty( $email ) ) {
			$details['email'] = $email;
		}

		$details['address']['line1']       = $order->get_billing_address_1();
		$details['address']['line2']       = $order->get_billing_address_2();
		$details['address']['state']       = $order->get_billing_state();
		$details['address']['city']        = $order->get_billing_city();
		$details['address']['postal_code'] = $order->get_billing_postcode();
		$details['address']['country']     = $order->get_billing_country();

		return (object) apply_filters( 'wcv_sc_stripe_owner_details', $details, $order );
	}

	/**
	 * Generate the request for the payment.
	 *
	 * @since 2.1.0
	 */
	public function generate_payment_request( $order, $prepared_source ) {
		$settings              = get_option( 'woocommerce_stripe-connect_settings', array() );
		$statement_descriptor  = ! empty( $settings['statement_descriptor'] ) ? str_replace( "'", '', $settings['statement_descriptor'] ) : '';
		$capture               = ! empty( $settings['capture'] ) && 'yes' === $settings['capture'] ? true : false;
		$post_data             = [];
		$post_data['currency'] = strtolower( $order->get_currency() );
		$post_data['amount']   = WCV_SC_Helper::get_stripe_amount( $order->get_total(), $post_data['currency'] );
		/* translators: 1) blog name 2) order number */
		$post_data['description'] = sprintf( __( '%1$s - Order %2$s', 'wc-vendors-gateway-stripe-connect' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $order->get_order_number() );
		$billing_email            = $order->get_billing_email();
		$billing_first_name       = $order->get_billing_first_name();
		$billing_last_name        = $order->get_billing_last_name();

		if ( ! empty( $billing_email ) && apply_filters( 'wcv_sc_send_stripe_receipt', false ) ) {
			$post_data['receipt_email'] = $billing_email;
		}

		switch ( $order->get_payment_method() ) {
			case 'stripe':
				if ( ! empty( $statement_descriptor ) ) {
					$post_data['statement_descriptor'] = WCV_SC_Helper::clean_statement_descriptor( $statement_descriptor );
				}

				$post_data['capture'] = $capture ? 'true' : 'false';
				break;
			case 'stripe_sepa':
				if ( ! empty( $statement_descriptor ) ) {
					$post_data['statement_descriptor'] = WCV_SC_Helper::clean_statement_descriptor( $statement_descriptor );
				}
				break;
		}

		if ( method_exists( $order, 'get_shipping_postcode' ) && ! empty( $order->get_shipping_postcode() ) ) {
			$post_data['shipping'] = [
				'name'    => trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() ),
				'address' => [
					'line1'       => $order->get_shipping_address_1(),
					'line2'       => $order->get_shipping_address_2(),
					'city'        => $order->get_shipping_city(),
					'country'     => $order->get_shipping_country(),
					'postal_code' => $order->get_shipping_postcode(),
					'state'       => $order->get_shipping_state(),
				],
			];
		}

		$post_data['expand[]'] = 'balance_transaction';

		$metadata = [
			__( 'customer_name', 'wc-vendors-gateway-stripe-connect' ) => sanitize_text_field( $billing_first_name ) . ' ' . sanitize_text_field( $billing_last_name ),
			__( 'customer_email', 'wc-vendors-gateway-stripe-connect' ) => sanitize_email( $billing_email ),
			'order_id' => $order->get_order_number(),
			'site_url' => esc_url( get_site_url() ),
		];

		if ( $this->has_subscription( $order->get_id() ) ) {
			$metadata += [
				'payment_type' => 'recurring',
			];
		}

		$post_data['metadata'] = apply_filters( 'wcv_sc_payment_metadata', $metadata, $order, $prepared_source );

		if ( $prepared_source->customer ) {
			$post_data['customer'] = $prepared_source->customer;
		}

		if ( $prepared_source->source ) {
			$post_data['source'] = $prepared_source->source;
		}

		return apply_filters( 'wcv_sc_generate_payment_request', $post_data, $order, $prepared_source );
	}

	/**
	 * Create the level 3 data array to send to Stripe when making a purchase.
	 *
	 * @since 2.1.0
	 * @param WC_Order $order The order that is being paid for.
	 * @return array          The level 3 data to send to Stripe.
	 */
	public function get_level3_data_from_order( $order ) {
		// Get the order items. Don't need their keys, only their values.
		// Order item IDs are used as keys in the original order items array.
		$order_items = array_values( $order->get_items( [ 'line_item', 'fee' ] ) );
		$currency    = $order->get_currency();

		$stripe_line_items = array_map(
			function( $item ) use ( $currency ) {
				if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
					$product_id = $item->get_variation_id()
						? $item->get_variation_id()
						: $item->get_product_id();
					$subtotal   = $item->get_subtotal();
				} else {
					$product_id = substr( sanitize_title( $item->get_name() ), 0, 12 );
					$subtotal   = $item->get_total();
				}
				$product_description = substr( $item->get_name(), 0, 26 );
				$quantity            = $item->get_quantity();
				$unit_cost           = WCV_SC_Helper::get_stripe_amount( ( $subtotal / $quantity ), $currency );
				$tax_amount          = WCV_SC_Helper::get_stripe_amount( $item->get_total_tax(), $currency );
				$discount_amount     = WCV_SC_Helper::get_stripe_amount( $subtotal - $item->get_total(), $currency );

				return (object) [
					'product_code'        => (string) $product_id, // Up to 12 characters that uniquely identify the product.
					'product_description' => $product_description, // Up to 26 characters long describing the product.
					'unit_cost'           => $unit_cost, // Cost of the product, in cents, as a non-negative integer.
					'quantity'            => $quantity, // The number of items of this type sold, as a non-negative integer.
					'tax_amount'          => $tax_amount, // The amount of tax this item had added to it, in cents, as a non-negative integer.
					'discount_amount'     => $discount_amount, // The amount an item was discounted—if there was a sale,for example, as a non-negative integer.
				];
			},
			$order_items
		);

		$level3_data = [
			'merchant_reference' => $order->get_id(), // An alphanumeric string of up to  characters in length. This unique value is assigned by the merchant to identify the order. Also known as an “Order ID”.
			'shipping_amount'    => WCV_SC_Helper::get_stripe_amount( (float) $order->get_shipping_total() + (float) $order->get_shipping_tax(), $currency ), // The shipping cost, in cents, as a non-negative integer.
			'line_items'         => $stripe_line_items,
		];

		// The customer’s U.S. shipping ZIP code.
		$shipping_address_zip = $order->get_shipping_postcode();
		if ( $this->is_valid_us_zip_code( $shipping_address_zip ) ) {
			$level3_data['shipping_address_zip'] = $shipping_address_zip;
		}

		// The merchant’s U.S. shipping ZIP code.
		$store_postcode = get_option( 'woocommerce_store_postcode' );
		if ( $this->is_valid_us_zip_code( $store_postcode ) ) {
			$level3_data['shipping_from_zip'] = $store_postcode;
		}

		return $level3_data;
	}
}
