<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Refund Helpers.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

/**
 * WC Vendors - Stripe Commissions & Gateway Refund Helpers.
 *
 * @since 2.0.0
 */
class WCV_SC_Refund_Helpers {

	/**
	 * Get refund requests by vendor for specific order.
	 *
	 * @todo using post_author to improve the performance of this method.
	 */
	public static function get_refund_request_for_order( $order_id, $vendor_id = 0, $status = 'wc-rf-in-review' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		if ( ! $vendor_id ) {
			$vendor_id = get_current_user_id();
		}

		if ( ! WCV_Vendors::is_vendor( $vendor_id ) ) {
			return false;
		}

		$return = array();

		$refund_requests = wc_get_orders(
			array(
				'type'   => 'wcv_refund_request',
				'parent' => $order_id,
				'limit'  => -1,
				'status' => $status,
			)
		);

		foreach ( $refund_requests as $request ) {
			if ( $vendor_id == $request->get_vendor_id() ) {
				$return[] = $request;
			}
		}

		return $return;
	}

	/**
	 * Get all statuses of refund request.
	 *
	 * @return array
	 */
	public static function get_refund_request_statuses() {
		return apply_filters(
			'wcv_refund_request_statuses',
			array(
				'wc-rf-in-review' => __( 'In review', 'wc-vendors-gateway-stripe-connect' ),
				'wc-rf-approved'  => __( 'Approved', 'wc-vendors-gateway-stripe-connect' ),
				'wc-rf-rejected'  => __( 'Rejected', 'wc-vendors-gateway-stripe-connect' ),
				'wc-rf-cancelled' => __( 'Cancelled', 'wc-vendors-gateway-stripe-connect' ),
				'wc-rf-succeeded' => __( 'Succeeded', 'wc-vendors-gateway-stripe-connect' ),
				'wc-rf-failed'    => __( 'Failed', 'wc-vendors-gateway-stripe-connect' ),
				'trash'           => __( 'In trash', 'wc-vendors-gateway-stripe-connect' ),
			)
		);
	}

	/**
	 * Get status name.
	 *
	 * @param string $status Status ID.
	 *
	 * @return string Status nice name.
	 */
	public static function get_refund_request_status_name( $status ) {
		if ( substr( $status, 0, 3 ) !== 'wc-' && $status !== 'trash' ) {
			$status = 'wc-' . $status;
		}
		$statuses = self::get_refund_request_statuses();
		if ( isset( $statuses[ $status ] ) ) {
			return $statuses[ $status ];
		}
		return '';
	}

	/**
	 * Check if order can be refunded..
	 * We need to pass the vendor id because current the vendor class is
	 * incomplete.
	 * In the next version of WC Vendors, when we utilize the sub order system
	 * completetly, we will need to pass only the vendor order id, not parent id
	 * and vendor id like what we do now.
	 *
	 * @param int $order_id  WooCommerce order id.
	 * @param int $vendor_id ID of vendor to check.
	 *
	 * @return bool
	 */
	public static function can_order_be_refunded( $order_id, $vendor_id = 0 ) {
		if ( ! $vendor_id ) {
			$vendor_id = get_current_user_id();
		}

		if ( 'yes' === WCV_SC_Helper::get_settings( 'disable_refund' ) ) {
			return false;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}

		if ( ! empty( self::get_refund_request_for_order( $order_id, $vendor_id ) ) ) {
			return false;
		}

		$refund_data         = self::get_refund_data( $order_id, $vendor_id );
		$available_to_refund = $refund_data['available_to_refund'];

		if ( $available_to_refund <= 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Get amount refunded and available to refund of the order for vendor.
	 * We need to pass the vendor id because current the vendor class is
	 * incomplete.
	 * In the next version of WC Vendors, when we utilize the sub order system
	 * completetly, we will need to pass only the vendor order id, not parent id
	 * and vendor id like what we do now.
	 *
	 * @param int $order_id  WooCommerce order id.
	 * @param int $vendor_id ID of vendor to check.
	 *
	 * @return array
	 */
	public static function get_refund_data( $order_id, $vendor_id = 0 ) {
		if ( ! $vendor_id ) {
			$vendor_id = get_current_user_id();
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array(
				'already_refunded'    => 0,
				'available_to_refund' => 0,
			);
		}

		$already_refunded    = 0;
		$available_to_refund = 0;
		$parent_line_items   = self::get_line_items_for_vendor( $order, $vendor_id );
		$parent_shipping     = self::get_shipping_items_for_vendor( $order, $vendor_id );

		foreach ( $parent_line_items + $parent_shipping as $item ) {
			if ( is_a( $item, 'WC_Order_Item_Shipping' ) ) {
				$refunded = $order->get_total_refunded_for_item( $item->get_id(), 'shipping' );
			} else {
				$refunded = $order->get_total_refunded_for_item( $item->get_id() );
			}
			$already_refunded    += $refunded;
			$available_to_refund += ( $item->get_total() - $refunded );

			$tax_data = ( wc_tax_enabled() && wc_string_to_bool( get_option( 'wcvendors_vendor_give_taxes', 'no' ) ) ) ? $item->get_taxes() : false;
			if ( $tax_data ) {
				foreach ( $order->get_taxes() as $tax_item ) {
					$tax_item_id    = $tax_item->get_rate_id();
					$tax_item_total = isset( $tax_data['total'][ $tax_item_id ] ) ? $tax_data['total'][ $tax_item_id ] : '';

					if ( ! $tax_item_total ) {
						continue;
					}

					$tax_refunded         = $order->get_tax_refunded_for_item( $item->get_id(), $tax_item_id );
					$already_refunded    += $tax_refunded;
					$available_to_refund += ( $tax_item_total - $tax_refunded );
				}
			}
		}

		return array(
			'already_refunded'    => $already_refunded,
			'available_to_refund' => $available_to_refund,
		);
	}

	/**
	 * Get line items for current vendor.
	 */
	public static function get_line_items_for_vendor( $order, $vendor_id = 0 ) {
		$line_items    = array();
		$give_shipping = wc_string_to_bool( get_option( 'wcvendors_vendor_give_shipping', 'no' ) );

		if ( ! $vendor_id ) {
			$vendor_id = get_current_user_id();
		}

		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			if ( $vendor_id == get_post_field( 'post_author', $product_id ) ) {
				$line_items[ $item->get_id() ] = $item;
			}
		}

		return $line_items;
	}

	/**
	 * Get line items for current vendor.
	 */
	public static function get_shipping_items_for_vendor( $order, $vendor_id = 0 ) {
		$give_shipping = wc_string_to_bool( get_option( 'wcvendors_vendor_give_shipping', 'no' ) );

		if ( ! $vendor_id ) {
			$vendor_id = get_current_user_id();
		}

		if ( ! $give_shipping ) {
			return array();
		}

		$shipping_products = self::get_shipping_products( $order );
		$vendor_shipping   = array();
		$shipping_items    = array();
		$line_items        = self::get_line_items_for_vendor( $order, $vendor_id );

		foreach ( $line_items as $item ) {
			foreach ( $shipping_products as $id => $products ) {
				if ( in_array( $item->get_name(), $products ) ) {
					$vendor_shipping[] = $id;
				}
			}
		}

		$vendor_shipping = array_unique( $vendor_shipping );

		foreach ( $vendor_shipping as $shipping_item_id ) {
			$shipping_items[ $shipping_item_id ] = new WC_Order_Item_Shipping( $shipping_item_id );
		}

		return $shipping_items;
	}

	/**
	 * Map order item shipping id with product name.
	 */
	public static function get_shipping_products( $order ) {
		$map = array();
		foreach ( $order->get_items( 'shipping' ) as $shipping ) {
			$items         = $shipping->get_meta( 'Items', true );
			$is_refundable = ! empty( $items );
			if ( ! apply_filters( 'wcv_sc_is_refundable_shipping_line', $is_refundable, $shipping ) ) {
				continue;
			}
			foreach ( explode( ',', $items ) as $item ) {
				$item_name = explode( '&times;', $item );

				$map[ $shipping->get_id() ][] = trim( $item_name[0] );
			}
		}
		return $map;
	}
}
