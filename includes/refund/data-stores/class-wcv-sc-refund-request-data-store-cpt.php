<?php
/**
 * WC Vendors Stripe Connect Refund Request Data Store.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Connect_Gateway
 */

/**
 * WC Vendors Stripe Connect Refund Request Data Store.
 *
 * @since 2.0.0
 */
class WCV_SC_Refund_Request_Data_Store_CPT extends Abstract_WC_Order_Data_Store_CPT implements WC_Object_Data_Store_Interface {

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_refund_reason',
	);

	/**
	 * Method to create a new order in the database.
	 *
	 * @param WC_Order $order Order object.
	 */
	public function create( &$order ) {
		parent::create( $order );
		wp_update_post(
			[
				'ID'          => $order->get_id(),
				'post_author' => $order->get_vendor_id(),
			]
		);
	}

	/**
	 * Method to read an order from the database.
	 *
	 * @param WC_Data $order Order object.
	 *
	 * @throws Exception If passed order is invalid.
	 */
	public function read( &$order ) {
		parent::read( $order );
		$post_object = get_post( $order->get_id() );
		$order->set_props(
			[
				'vendor_id' => $post_object->post_author,
			]
		);
	}

	/**
	 * Read refund request data.
	 *
	 * @param WC_Order $request Refund request object.
	 * @param object   $post_object Post object.
	 * @since 3.0.0
	 */
	protected function read_order_data( &$request, $post_object ) {
		parent::read_order_data( $request, $post_object );

		$id = $request->get_id();
		$request->set_props(
			array(
				'reason' => metadata_exists( 'post', $id, '_refund_reason' ) ? get_post_meta( $id, '_refund_reason', true ) : $post_object->post_excerpt,
			)
		);
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the WC_Order class.
	 *
	 * @param WC_Order $request Refund request object.
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$request ) {
		parent::update_post_meta( $request );

		$updated_props     = array();
		$meta_key_to_props = array(
			'_refund_reason' => 'reason',
		);

		$props_to_update = $this->get_props_to_update( $request, $meta_key_to_props );
		foreach ( $props_to_update as $meta_key => $prop ) {
			$value = $request->{"get_$prop"}( 'edit' );
			update_post_meta( $request->get_id(), $meta_key, $value );
			$updated_props[] = $prop;
		}
	}
}
