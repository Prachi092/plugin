<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Refund Request.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC Vendors - Stripe Commissions & Gateway Refund Request.
 *
 * @since 2.0.0
 */
class WCV_SC_Refund_Request extends WC_Abstract_Order {

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'wcv-refund-request';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'wcv_refund_request';

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'reason'    => '',
		'vendor_id' => '',
	);

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @var bool|array
	 */
	protected $status_transition = false;

	/**
	 * Save data to the database.
	 *
	 * @since 3.0.0
	 * @return int order ID
	 */
	public function save() {
		parent::save();
		$this->status_transition();
	}

	/**
	 * Handle the status transition.
	 */
	protected function status_transition() {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( $status_transition ) {
			try {
				do_action( 'wcv_refund_request_status_' . $status_transition['to'], $this->get_id(), $this );

				if ( ! empty( $status_transition['from'] ) && $status_transition['to'] != $status_transition['from'] ) {
					/* translators: 1: old order status 2: new order status */
					$transition_note = sprintf( __( 'Refund request status changed from %1$s to %2$s.', 'wc-vendors-gateway-stripe-connect' ), WCV_SC_Refund_Helpers::get_refund_request_status_name( $status_transition['from'] ), WCV_SC_Refund_Helpers::get_refund_request_status_name( $status_transition['to'] ) );

					do_action( 'wcv_refund_request_status_' . $status_transition['from'] . '_to_' . $status_transition['to'], $this->get_id(), $this );
				} else {
					/* translators: %s: new order status */
					$transition_note = sprintf( __( 'Refund request status set to %s.', 'wc-vendors-gateway-stripe-connect' ), wc_get_order_status_name( $status_transition['to'] ) );
				}

				// Note the transition occurred.
				$this->add_order_note( trim( $status_transition['note'] . ' ' . $transition_note ) );

				do_action( 'wcv_refund_request_status_changed', $this->get_id(), $status_transition['from'], $status_transition['to'], $this );
			} catch ( Exception $e ) {
				$logger = wc_get_logger();
				$logger->error(
					sprintf( 'Status transition of order #%d errored!', $this->get_id() ),
					array(
						'order' => $this,
						'error' => $e,
					)
				);
				$this->add_order_note( __( 'Error during status transition.', 'wc-vendors-gateway-stripe-connect' ) . ' ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Set refund reason.
	 *
	 * @param string $value Value to set.
	 * @throws WC_Data_Exception Exception if the amount is invalid.
	 */
	public function set_reason( $value ) {
		$this->set_prop( 'reason', $value );
	}

	/**
	 * Set vendor ID.
	 *
	 * @param string $value Value to set.
	 * @throws WC_Data_Exception Exception if the amount is invalid.
	 */
	public function set_vendor_id( $value ) {
		$this->set_prop( 'vendor_id', $value );
	}

	/**
	 * Set order status.
	 *
	 * @param string $new_status    Status to change the order to. No internal wc- prefix is required.
	 * @param string $note          Optional note to add.
	 * @return array details of change
	 */
	public function set_status( $new_status, $note = '' ) {
		$old_status = $this->get_status();
		$new_status = 'wc-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;

		// If setting the status, ensure it's set to a valid status.
		if ( true === $this->object_read ) {
			// Only allow valid new status.
			if ( ! in_array( 'wc-' . $new_status, $this->get_valid_statuses(), true ) && 'trash' !== $new_status ) {
				$new_status = 'rf-in-review';
			}

			// If the old status is set but unknown (e.g. draft) assume its pending for action usage.
			if ( $old_status && ! in_array( 'wc-' . $old_status, $this->get_valid_statuses(), true ) && 'trash' !== $old_status ) {
				$old_status = 'rf-in-review';
			}
		}

		$this->set_prop( 'status', $new_status );

		$this->status_transition = array(
			'from' => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $old_status,
			'to'   => $new_status,
			'note' => $note,
		);

		return array(
			'from' => $old_status,
			'to'   => $new_status,
		);
	}

	/**
	 * Get internal type (post type.)
	 *
	 * @return string
	 */
	public function get_type() {
		return 'wcv_refund_request';
	}

	/**
	 * Return the order statuses without wc- internal prefix.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		$status = $this->get_prop( 'status', $context );

		if ( empty( $status ) ) {
			// In view context, return the default status if no status has been set.
			$status = apply_filters( 'wcv_sc_refund_request_default_status', 'rf-in-review' );
		}
		return $status;
	}

	/**
	 * Return zero because we can refund the refund request.
	 */
	public function get_qty_refunded_for_item( $item_id, $item_type = 'line_item' ) {
		return 0;
	}

	public function get_total_refunded_for_item( $item_id, $item_type = 'line_item' ) {
		return 0;
	}

	public function get_tax_refunded_for_item( $item_id, $item_type = 'line_item' ) {
		return 0;
	}

	public function get_total_tax_refunded_by_rate_id( $rate_id ) {
		return 0;
	}

	public function get_total_shipping_refunded() {
		return 0;
	}

	public function get_total_refunded() {
		return 0;
	}

	public function get_refunds() {
		return [];
	}

	/**
	 * Get refund  request reason.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int|float
	 */
	public function get_reason( $context = 'view' ) {
		return $this->get_prop( 'reason', $context );
	}

	/**
	 * Get vendor id of this request.
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return int|float
	 */
	public function get_vendor_id( $context = 'view' ) {
		return $this->get_prop( 'vendor_id', $context );
	}

	/**
	 * Get all valid statuses for this order
	 *
	 * @return array Internal status keys e.g. 'wc-processing'
	 */
	protected function get_valid_statuses() {
		return array_keys( WCV_SC_Refund_Helpers::get_refund_request_statuses() );
	}

	public function get_edit_url() {
		return apply_filters( 'wcv_get_edit_refund_request_url', get_admin_url( null, 'post.php?post=' . $this->get_id() . '&action=edit' ), $this );
	}

	public function get_item_count_refunded() {
		return 0;
	}
	/**
	 * We don't allow editting refund request.
	 */
	public function is_editable() {
		return false;
	}

	public function is_download_permitted() {
		return false;
	}

	public function is_paid() {
		return true;
	}

	/*
	|--------------------------------------------------------------------------
	| Order notes.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Adds a note (comment) to the order. Order must exist.
	 *
	 * @param  string $note              Note to add.
	 * @param  int    $is_customer_note  Is this a note for the customer?.
	 * @param  bool   $added_by_user     Was the note added by a user?.
	 * @return int                       Comment ID.
	 */
	public function add_order_note( $note, $is_customer_note = 0, $added_by_user = false ) {
		if ( ! $this->get_id() ) {
			return 0;
		}

		if ( is_user_logged_in() && current_user_can( 'edit_shop_order', $this->get_id() ) && $added_by_user ) {
			$user                 = get_user_by( 'id', get_current_user_id() );
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;
		} else {
			$comment_author        = __( 'WCVendors', 'wc-vendors-gateway-stripe-connect' );
			$comment_author_email  = strtolower( __( 'wc-vendors-gateway-stripe-connect', 'wc-vendors-gateway-stripe-connect' ) ) . '@';
			$comment_author_email .= isset( $_SERVER['HTTP_HOST'] ) ? str_replace( 'www.', '', sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : 'noreply.com'; // WPCS: input var ok.
			$comment_author_email  = sanitize_email( $comment_author_email );
		}
		$commentdata = apply_filters(
			'woocommerce_new_order_note_data',
			array(
				'comment_post_ID'      => $this->get_id(),
				'comment_author'       => $comment_author,
				'comment_author_email' => $comment_author_email,
				'comment_author_url'   => '',
				'comment_content'      => $note,
				'comment_agent'        => 'WCVendors',
				'comment_type'         => 'order_note',
				'comment_parent'       => 0,
				'comment_approved'     => 1,
			),
			array(
				'order_id' => $this->get_id(),
			)
		);

		$comment_id = wp_insert_comment( $commentdata );

		return $comment_id;
	}
}
