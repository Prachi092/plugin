<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Refund.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

/**
 * WC Vendors - Stripe Commissions & Gateway Refund.
 *
 * @since 2.0.0
 */
class WCV_SC_Refund {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.0.0
	 *
	 * @var   WC_Vendors_Stripe_Commissions_Gateway
	 */
	protected $plugin = null;

	private $services = array();

	/**
	 * Constructor.
	 *
	 * @since  2.0.0
	 *
	 * @param  WC_Vendors_Stripe_Commissions_Gateway $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		if ( ! class_exists( 'WCVendors_Pro' ) ) {
			return;
		}
		$this->hooks();
		$this->include_classes();
		$this->init_classes();
	}

	private function include_classes() {
		require_once 'class-refund-emails.php';
		require_once 'class-refund-helpers.php';
		require_once 'class-refund-request-admin-meta-boxes.php';
		require_once 'class-refund-request-admin-post-type.php';
		require_once 'class-refund-request-controller.php';
		require_once 'class-refund-request-dashboard.php';
	}

	private function init_classes() {
		$this->services['refund_emails']                   = new WCV_SC_Refund_Emails();
		$this->services['refund_request_controller']       = new WCV_SC_Refund_Request_Controller();
		$this->services['refund_request_admin_meta_boxes'] = new WCV_SC_Refund_Request_Admin_Meta_Boxes();
		$this->services['refund_request_admin_post_type']  = new WCV_SC_Refund_Request_Admin_Post_Type();
		$this->services['refund_helpers']                  = new WCV_SC_Refund_Helpers();
		$this->services['refund_request_dashboard']        = new WCV_SC_Refund_Request_Dashboard();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  2.0.0
	 */
	public function hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'wcv_order_row_actions', array( $this, 'add_refund_action' ), 10, 2 );
		add_action( 'wcv_orders_add_new_row', array( $this, 'add_refund_template' ) );
		add_action( 'wcv_refund_request_status_changed', array( $this, 'create_refund' ), 10, 4 );
	}

	/**
	 * Load the scripts on the front end
	 */
	public function enqueue_scripts() {

		global $post;

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return; }

		// Only load the styles on the dashboard pages on the front end
		if ( has_shortcode( $post->post_content, 'wcv_shop_settings' ) || has_shortcode( $post->post_content, 'wcv_pro_dashboard' ) ) {
			wp_enqueue_script( 'wc-vendors-stripe-connect-refund', plugins_url( 'assets/js/stripe-connect-refund.js', WCV_SC_MAIN_FILE ), array( 'jquery', 'parsley', 'wcv-frontend-general' ), WCV_SC_VERSION, true );
			wp_enqueue_style( 'wc-vendors-stripe-connect-refund', plugins_url( 'assets/css/stripe-connect-refund.css', WCV_SC_MAIN_FILE ), array(), WCV_SC_VERSION );
			wp_localize_script(
				'wc-vendors-stripe-connect-refund',
				'WCVSCRefund',
				array(
					'i18n_invalid_amount'         => __( 'This amount must be less or equal to %s.', 'wc-vendors-gateway-stripe-connect' ),
					'i18n_do_refund'              => __( 'Are you sure you wish to process this refund? This action cannot be undone.', 'wc-vendors-gateway-stripe-connect' ),
					'order_item_nonce'            => wp_create_nonce( 'order-item' ),
					'cancel_request_nonce'        => wp_create_nonce( 'cancel-request' ),
					'dashboard_request_url'       => WCVendors_Pro_Dashboard::get_dashboard_page_url( 'wcv_refund_request' ),
					'i18n_confirm_cancel_request' => __( 'Are you sure you wish to cancel this refund request?', 'wc-vendors-gateway-stripe-connect' ),
				)
			);
		}
	}

	/**
	 * Load the scripts on the admin side.
	 */
	public function admin_enqueue_scripts() {
			wp_enqueue_style( 'wc-vendors-stripe-connect-refund-admin', plugins_url( 'assets/css/stripe-connect-refund-admin.css', WCV_SC_MAIN_FILE ), array(), WCV_SC_VERSION );
	}

	/**
	 * Add refund action to actions array of order table.
	 *
	 * @params array $actions  Actions array.
	 * @params int   $order_id Order ID.
	 *
	 * @return array
	 */
	public function add_refund_action( $actions, $order_id ) {
		$order = wc_get_order( $order_id );

		if ( 'stripe-connect' !== $order->get_payment_method() ) {
			return $actions;
		}

		if ( ! WCV_SC_Refund_Helpers::can_order_be_refunded( $order_id ) ) {
			return $actions;
		}

		if ( ! get_user_meta( get_current_user_id(), '_stripe_connect_user_id', true ) ) {
			return $actions;
		}

		$actions['refund'] = array(
			'label'  => __( 'Refund', 'wc-vendors-gateway-stripe-connect' ),
			'url'    => '#',
			'custom' => array(
				'id' => 'open-order-refund-modal-' . $order_id,
			),
		);

		return $actions;
	}

	public function add_refund_template( $row ) {
		$order_id = $row->ID;
		$order    = wc_get_order( $order_id );

		if ( 'stripe-connect' !== $order->get_payment_method() ) {
			return;
		}

		if ( ! WCV_SC_Refund_Helpers::can_order_be_refunded( $order_id ) ) {
			return;
		}

		$line_items          = WCV_SC_Refund_Helpers::get_line_items_for_vendor( $order );
		$shipping            = WCV_SC_Refund_Helpers::get_shipping_items_for_vendor( $order );
		$refund_data         = WCV_SC_Refund_Helpers::get_refund_data( $order_id );
		$already_refunded    = $refund_data['already_refunded'];
		$available_to_refund = $refund_data['available_to_refund'];

		$refund_amount           = '<span class="wc-order-refund-amount">' . wc_price( 0, array( 'currency' => $order->get_currency() ) ) . '</span>';
		$formatted_zero_price    = number_format( 0, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
		$formatted_refund_amount = str_replace( $formatted_zero_price, sprintf( '<span class="js-refund-amount">%s</span>', $formatted_zero_price ), $refund_amount );

		ob_start();

		wc_get_template(
			'refund.php',
			array(
				'order'               => $order,
				'line_items'          => $line_items,
				'shipping'            => $shipping,
				'already_refunded'    => $already_refunded,
				'available_to_refund' => $available_to_refund,
				'refund_amount'       => $formatted_refund_amount,
			),
			'wc-vendors/dashboard/',
			WCV_SC_PLUGIN_PATH . '/templates/dashboard/'
		);

		$row->action_after .= ob_get_clean();
	}

	/**
	 * Create a new order refund programmatically.
	 * Returns a new refund object on success which can then be used to add additional data.
	 *
	 * @throws Exception Throws exceptions when fail to create, but returns WP_Error instead.
	 * @param int $request_id Refund request id.
	 * @return WC_Order_Refund|WP_Error
	 */
	public function create_refund( $request_id, $from, $to, $request ) {

		if ( 'rf-in-review' != $from || 'rf-approved' != $to ) {
			return;
		}

		try {
			$order_id  = $request->get_parent_id();
			$order     = wc_get_order( $order_id );
			$vendor_id = $request->get_vendor_id();
			$refund    = false;

			if ( ! $order ) {
				throw new Exception( __( 'Invalid order.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			if ( WCV_SC_Refund_Helpers::can_order_be_refunded( $order_id ) ) {
				throw new Exception( __( 'Order can not be refunded', 'wc-vendors-gateway-stripe-connect' ) );
			}

			$refund_data         = WCV_SC_Refund_Helpers::get_refund_data( $order_id, $vendor_id );
			$available_to_refund = $refund_data['available_to_refund'];
			$refund_item_count   = 0;

			if ( $available_to_refund < $request->get_total() ) {
				throw new Exception( __( 'Invalid refund amount.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			$refund = new WC_Order_Refund();
			$refund->set_currency( $order->get_currency() );
			$refund->set_amount( $request->get_total() );
			$refund->set_parent_id( $order_id );
			$refund->set_refunded_by( $request->get_vendor_id() );
			$refund->set_prices_include_tax( $order->get_prices_include_tax() );

			if ( $request->get_reason() ) {
				$refund->set_reason( $request->get_reason() );
			}

			$request_items = $request->get_items( array( 'line_item', 'fee', 'shipping' ) );
			$refund_items  = array();
			foreach ( $request_items as $item_id => $item ) {
				$taxes            = $item->get_taxes();
				$original_item_id = $item->get_meta( '_refunded_item_id' );

				$refund_items[ $original_item_id ]['qty']          = $item->get_quantity();
				$refund_items[ $original_item_id ]['refund_total'] = $item->get_total();
				$refund_items[ $original_item_id ]['refund_tax']   = $taxes['total'];
			}

			// Negative line items.
			if ( count( $refund_items ) > 0 ) {
				$items = $order->get_items( array( 'line_item', 'fee', 'shipping' ) );

				foreach ( $items as $item_id => $item ) {
					if ( ! isset( $refund_items[ $item_id ] ) ) {
						continue;
					}

					$qty          = isset( $refund_items[ $item_id ]['qty'] ) ? $refund_items[ $item_id ]['qty'] : 0;
					$refund_total = $refund_items[ $item_id ]['refund_total'];
					$refund_tax   = isset( $refund_items[ $item_id ]['refund_tax'] ) ? array_filter( (array) $refund_items[ $item_id ]['refund_tax'] ) : array();

					if ( empty( $qty ) && empty( $refund_total ) && empty( $refund_items[ $item_id ]['refund_tax'] ) ) {
						continue;
					}

					$class         = get_class( $item );
					$refunded_item = new $class( $item );
					$refunded_item->set_id( 0 );
					$refunded_item->add_meta_data( '_refunded_item_id', $item_id, true );
					$refunded_item->set_total( wc_format_refund_total( $refund_total ) );
					$refunded_item->set_taxes(
						array(
							'total'    => array_map( 'wc_format_refund_total', $refund_tax ),
							'subtotal' => array_map( 'wc_format_refund_total', $refund_tax ),
						)
					);

					if ( is_callable( array( $refunded_item, 'set_subtotal' ) ) ) {
						$refunded_item->set_subtotal( wc_format_refund_total( $refund_total ) );
					}

					if ( is_callable( array( $refunded_item, 'set_quantity' ) ) ) {
						$refunded_item->set_quantity( $qty * -1 );
					}

					$refund->add_item( $refunded_item );
					$refund_item_count += $qty;
				}
			}

			$refund->update_taxes();
			$refund->calculate_totals( false );
			$refund->set_total( $request->get_total() * -1 );

			if ( $refund->save() ) {
				$result = $this->refund_payment( $order, $vendor_id, $refund );

				if ( is_wp_error( $result ) ) {
					$refund->delete();
					$refund_message = sprintf( __( 'Refund failed! Reason: %s', 'wc-vendors-gateway-stripe-connect' ), $result->get_error_message() );
					$request->add_order_note( $refund_message );
					$request->set_status( 'rf-failed' );
					$request->save();
					return $result;
				}

				$refund_message = ( isset( $result['captured'] ) && 'yes' === $result['captured'] ) ? sprintf( __( 'Refunded %1$s - Refund ID: %2$s - Reason: %3$s', 'wc-vendors-gateway-stripe-connect' ), $request->get_total(), $result['refund_id'], $request->get_reason() ) : __( 'Pre-Authorization Released', 'wc-vendors-gateway-stripe-connect' );
				$request->add_order_note( $refund_message );

				$refund->set_refunded_payment( true );
				$refund->save();
				$request->set_status( 'rf-succeeded' );
				$request->save();

				if ( $order->get_remaining_refund_amount() > 0 ) {
					do_action( 'woocommerce_order_partially_refunded', $order->get_id(), $refund->get_id() );
				} else {
					do_action( 'woocommerce_order_fully_refunded', $order->get_id(), $refund->get_id() );

					$parent_status = apply_filters( 'woocommerce_order_fully_refunded_status', 'refunded', $order->get_id(), $refund->get_id() );

					if ( $parent_status ) {
						$order->update_status( $parent_status );
					}
				}
			}
		} catch ( Exception $e ) {
			if ( isset( $refund ) && is_a( $refund, 'WC_Order_Refund' ) ) {
				wp_delete_post( $refund->get_id(), true );
			}
			return new WP_Error( 'error', $e->getMessage() );
		}

		return $refund;
	}

	/**
	 * Try to refund the payment for an order via the gateway.
	 *
	 * @since 3.0.0
	 *
	 * @throws Exception Throws exceptions when fail to refund, but returns WP_Error instead.
	 *
	 * @param WC_Order        $order     Order instance.
	 * @param int             $vendor_id Vendor ID.
	 * @param WC_Order_Refund $amount    Amount to refund.
	 *
	 * @return bool|WP_Error
	 */
	public function refund_payment( $order, $vendor_id, $refund ) {
		try {
			if ( ! is_a( $order, 'WC_Order' ) ) {
				throw new Exception( __( 'Invalid order.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			$payment_method = $order->get_payment_method();
			if ( 'stripe-connect' !== $payment_method ) {
				throw new Exception( __( 'Invalid payment method.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			$gateway_controller = WC_Payment_Gateways::instance();
			$all_gateways       = $gateway_controller->payment_gateways();
			$gateway            = isset( $all_gateways[ $payment_method ] ) ? $all_gateways[ $payment_method ] : false;

			if ( ! $gateway ) {
				throw new Exception( __( 'The payment gateway for this order does not exist.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			$result = $gateway->process_vendor_refund( $order, $vendor_id, $refund );

			if ( ! $result ) {
				throw new Exception( __( 'An error occurred while attempting to create the refund using the payment gateway API.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			return $result;

		} catch ( Exception $e ) {
			return new WP_Error( 'error', $e->getMessage() );
		}
	}

}
