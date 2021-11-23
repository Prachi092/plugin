<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Refund Request Controller.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

/**
 * WC Vendors - Stripe Commissions & Gateway Refund Request Controller.
 *
 * @since 2.0.0
 */
class WCV_SC_Refund_Request_Controller {
	/**
	 * Constructor.
	 *
	 * @since  2.0.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  2.0.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_post_statuses' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 60 );
		add_filter( 'woocommerce_data_stores', array( $this, 'add_data_stores' ) );
		add_action( 'add_meta_boxes', __CLASS__ . '::remove_subscription_actions' );
		add_action( 'wp_ajax_wcvendors_create_refund_request', array( $this, 'create_refund_request' ) );
		add_action( 'wp_ajax_nopriv_wcvendors_create_refund_request', array( $this, 'create_refund_request' ) );
		add_action( 'wp_ajax_wcvendors_cancel_refund_request', array( $this, 'cancel_refund_request' ) );
		add_action( 'wp_ajax_nopriv_wcvendors_cancel_refund_request', array( $this, 'cancel_refund_request' ) );
	}

	/**
	 * Register Refund Request type.
	 */
	public function register_post_types() {
		wc_register_order_type(
			'wcv_refund_request',
			apply_filters(
				'wcv_register_post_type_refund_request',
				array(
					'labels'                            => array(
						'name'                  => __( 'Refund Requests', 'wc-vendors-gateway-stripe-connect' ),
						'singular_name'         => _x( 'Refund Request', 'shop_request post type singular name', 'wc-vendors-gateway-stripe-connect' ),
						'add_new'               => __( 'Add request', 'wc-vendors-gateway-stripe-connect' ),
						'add_new_item'          => __( 'Add new request', 'wc-vendors-gateway-stripe-connect' ),
						'edit'                  => __( 'Edit', 'wc-vendors-gateway-stripe-connect' ),
						'edit_item'             => __( 'Edit request', 'wc-vendors-gateway-stripe-connect' ),
						'new_item'              => __( 'New request', 'wc-vendors-gateway-stripe-connect' ),
						'view_item'             => __( 'View request', 'wc-vendors-gateway-stripe-connect' ),
						'search_items'          => __( 'Search requests', 'wc-vendors-gateway-stripe-connect' ),
						'not_found'             => __( 'No requests found', 'wc-vendors-gateway-stripe-connect' ),
						'not_found_in_trash'    => __( 'No requests found in trash', 'wc-vendors-gateway-stripe-connect' ),
						'parent'                => __( 'Parent requests', 'wc-vendors-gateway-stripe-connect' ),
						'menu_name'             => _x( 'Refund Requests', 'Admin menu name', 'wc-vendors-gateway-stripe-connect' ),
						'filter_items_list'     => __( 'Filter requests', 'wc-vendors-gateway-stripe-connect' ),
						'items_list_navigation' => __( 'Refund Requests navigation', 'wc-vendors-gateway-stripe-connect' ),
						'items_list'            => __( 'Refund Requests list', 'wc-vendors-gateway-stripe-connect' ),
					),
					'description'                       => __( 'This is where store requests are stored.', 'wc-vendors-gateway-stripe-connect' ),
					'public'                            => false,
					'show_ui'                           => true,
					'capability_type'                   => 'shop_order',
					'capabilities'                      => array(
						'create_posts' => 'do_not_allow',
					),
					'map_meta_cap'                      => true,
					'publicly_queryable'                => false,
					'exclude_from_search'               => true,
					'show_in_menu'                      => false,
					'hierarchical'                      => false,
					'show_in_nav_menus'                 => false,
					'rewrite'                           => false,
					'query_var'                         => false,
					'supports'                          => array( 'title', 'comments' ),
					'has_archive'                       => false,

					// wc_register_order_type() params.
					'exclude_from_subscriptions_screen' => false,
					'add_order_meta_boxes'              => true,
					'exclude_from_order_count'          => true,
					'exclude_from_order_views'          => true,
					'exclude_from_order_reports'        => true,
					'exclude_from_order_sales_reports'  => true,
					'class_name'                        => 'WCV_SC_Refund_Request',
				)
			)
		);
	}

	/**
	 * Register statuses for refund request.
	 */
	public function register_post_statuses() {
		$statuses = apply_filters(
			'wcv_refund_request_register_statuses',
			[
				'wc-rf-in-review' => [
					'label'       => __( 'In review', 'wc-vendors-gateway-stripe-connect' ),
					'label_count' => _n_noop( 'In review <span class="count">(%s)</span>', 'In review <span class="count">(%s)</span>', 'wc-vendors-gateway-stripe-connect' ),
				],
				'wc-rf-approved'  => [
					'label'       => __( 'Approved', 'wc-vendors-gateway-stripe-connect' ),
					'label_count' => _n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'wc-vendors-gateway-stripe-connect' ),
				],
				'wc-rf-rejected'  => [
					'label'       => __( 'Rejected', 'wc-vendors-gateway-stripe-connect' ),
					'label_count' => _n_noop( 'Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>', 'wc-vendors-gateway-stripe-connect' ),
				],
				'wc-rf-cancelled' => [
					'label'       => __( 'Cancelled', 'wc-vendors-gateway-stripe-connect' ),
					'label_count' => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'wc-vendors-gateway-stripe-connect' ),
				],
				'wc-rf-succeeded' => [
					'label'       => __( 'Succeeded', 'wc-vendors-gateway-stripe-connect' ),
					'label_count' => _n_noop( 'Succeeded <span class="count">(%s)</span>', 'Succeeded <span class="count">(%s)</span>', 'wc-vendors-gateway-stripe-connect' ),
				],
				'wc-rf-failed'    => [
					'label'       => __( 'Failed', 'wc-vendors-gateway-stripe-connect' ),
					'label_count' => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'wc-vendors-gateway-stripe-connect' ),
				],
			]
		);

		foreach ( $statuses as $status => $args ) {
			register_post_status(
				$status,
				wp_parse_args(
					$args,
					[
						'public'                    => false,
						'exclude_from_search'       => false,
						'show_in_admin_all_list'    => true,
						'show_in_admin_status_list' => true,
						'post_type'                 => [ 'wcv_refund_request' ],
					]
				)
			);
		}
	}

	/**
	 * Add submenu to WC Vendors admin menu.
	 */
	public function admin_menu() {
		$all_statuses_count         = wp_count_posts( 'wcv_refund_request' );
		$pending_request_count      = $all_statuses_count->{'wc-rf-in-review'};
		$pending_request_count_html = '';

		if ( $pending_request_count ) {
			$pending_request_count_html = sprintf(
				'<span class="request-in-review-count count-%1$s">%1$s</span>',
				$pending_request_count
			);
		}

		add_submenu_page(
			'wc-vendors',
			__( 'Refund requests', 'wc-vendors-gateway-stripe-connect' ),
			sprintf( __( 'Refund requests %s', 'wc-vendors-gateway-stripe-connect' ), $pending_request_count_html ),
			'manage_woocommerce',
			'edit.php?post_type=wcv_refund_request'
		);
	}

	/**
	 * Add data store to store our refund request.
	 */
	public function add_data_stores( $data_stores ) {
		// Our custom data stores.
		$data_stores['wcv-refund-request'] = 'WCV_SC_Refund_Request_Data_Store_CPT';

		return $data_stores;
	}

	/**
	 * Remove subscriptions actions to prevent fatal error.
	 */
	public static function remove_subscription_actions() {
		global $post;
		$order = wc_get_order( $post->ID );
		if ( is_a( $order, 'WCV_SC_Refund_Request' ) ) {
			remove_filter( 'woocommerce_order_actions', 'WCS_Admin_Meta_Boxes::add_subscription_actions', 10, 1 );
		}
	}

	/**
	 * Create refund request via ajax.
	 */
	public function create_refund_request() {
		ob_start();

		check_ajax_referer( 'order-item', 'security' );

		$vendor_id = get_current_user_id();

		if ( ! WCV_Vendors::is_vendor( $vendor_id ) ) {
			wp_die( __( 'Cheating uh?', 'wc-vendors-gateway-stripe-connect' ), 403 );
		}

		$order_id               = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$refund_amount          = isset( $_POST['refund_amount'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['refund_amount'] ) ), wc_get_price_decimals() ) : 0;
		$refunded_amount        = isset( $_POST['refunded_amount'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['refunded_amount'] ) ), wc_get_price_decimals() ) : 0;
		$refund_reason          = isset( $_POST['refund_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['refund_reason'] ) ) : '';
		$line_item_qtys         = isset( $_POST['line_item_qtys'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_qtys'] ) ), true ) : array();
		$line_item_totals       = isset( $_POST['line_item_totals'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_totals'] ) ), true ) : array();
		$line_item_tax_totals   = isset( $_POST['line_item_tax_totals'] ) ? json_decode( sanitize_text_field( wp_unslash( $_POST['line_item_tax_totals'] ) ), true ) : array();
		$api_refund             = isset( $_POST['api_refund'] ) && 'true' === $_POST['api_refund'];
		$restock_refunded_items = isset( $_POST['restock_refunded_items'] ) && 'true' === $_POST['restock_refunded_items'];
		$refund_request         = false;
		$response               = array();

		if ( ! WCV_SC_Refund_Helpers::can_order_be_refunded( $order_id, $vendor_id ) ) {
			wp_die( __( 'Cheating uh?', 'wc-vendors-gateway-stripe-connect' ), 403 );
		}

		try {
			$order       = wc_get_order( $order_id );
			$order_items = $order->get_items();
			$max_refund  = wc_format_decimal( $order->get_total() - $order->get_total_refunded(), wc_get_price_decimals() );
			$refund_data = WCV_SC_Refund_Helpers::get_refund_data( $order_id );

			if ( ! $refund_amount || $max_refund < $refund_amount || $refund_amount <= 0 ) {
				throw new Exception( __( 'Invalid refund amount', 'wc-vendors-gateway-stripe-connect' ) );
			}

			if ( 0 < $refunded_amount && $refund_data['already_refunded'] < $refunded_amount ) {
				throw new Exception( __( 'Error processing refund. Please try again.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			if ( $refund_amount > $refund_data['available_to_refund'] ) {
				throw new Exception(
					printf(
						__( 'Error processing refund. Please try to refund an amount less than or equal to %1$s%2$s', 'wc-vendors-gateway-stripe-connect' ),
						esc_attr( html_entity_decode( get_woocommerce_currency_symbol() ) ),
						esc_attr( wc_format_decimal( $refund_data['available_to_refund'], wc_get_price_decimals() ) )
					)
				);
			}

			// Prepare line items which we are refunding.
			$line_items = array();
			$item_ids   = array_unique( array_merge( array_keys( $line_item_qtys ), array_keys( $line_item_totals ) ) );

			foreach ( $item_ids as $item_id ) {
				$line_items[ $item_id ] = array(
					'qty'          => 0,
					'refund_total' => 0,
					'refund_tax'   => array(),
				);
			}
			foreach ( $line_item_qtys as $item_id => $qty ) {
				$line_items[ $item_id ]['qty'] = max( $qty, 0 );
			}
			foreach ( $line_item_totals as $item_id => $total ) {
				$line_items[ $item_id ]['refund_total'] = wc_format_decimal( $total );
			}
			foreach ( $line_item_tax_totals as $item_id => $tax_totals ) {
				$line_items[ $item_id ]['refund_tax'] = array_filter( array_map( 'wc_format_decimal', $tax_totals ) );
			}

			// Create the refund request object.

			$available_to_refund = $refund_data['available_to_refund'];

			$refund_request = new WCV_SC_Refund_Request();

			if ( 0 > $refund_amount || $refund_amount > $available_to_refund ) {
				throw new Exception( __( 'Invalid refund amount.', 'wc-vendors-gateway-stripe-connect' ) );
			}

			$refund_request->set_currency( $order->get_currency() );
			$refund_request->set_parent_id( $order_id );
			$refund_request->set_vendor_id( $vendor_id );

			if ( $refund_reason ) {
				$refund_request->set_reason( $refund_reason );
			}

			if ( count( $line_items ) > 0 ) {
				// @todo Support fee.
				$items = $order->get_items( array( 'line_item', 'shipping' ) );

				foreach ( $items as $item_id => $item ) {
					if ( ! isset( $line_items[ $item_id ] ) ) {
						continue;
					}

					$qty          = isset( $line_items[ $item_id ]['qty'] ) ? $line_items[ $item_id ]['qty'] : 0;
					$refund_total = $line_items[ $item_id ]['refund_total'];
					$refund_tax   = isset( $line_items[ $item_id ]['refund_tax'] ) ? array_filter( (array) $line_items[ $item_id ]['refund_tax'] ) : array();

					if ( empty( $qty ) && empty( $refund_total ) && empty( $line_items[ $item_id ]['refund_tax'] ) ) {
						continue;
					}

					$class         = get_class( $item );
					$refunded_item = new $class( $item );
					$refunded_item->set_id( 0 );
					$refunded_item->add_meta_data( '_refunded_item_id', $item_id, true );
					$refunded_item->set_total( $refund_total );
					$refunded_item->set_taxes(
						array(
							'total'    => array_map( 'wc_format_decimal', $refund_tax ),
							'subtotal' => array_map( 'wc_format_decimal', $refund_tax ),
						)
					);

					if ( is_callable( array( $refunded_item, 'set_subtotal' ) ) ) {
						$refunded_item->set_subtotal( $refund_total );
					}

					if ( is_callable( array( $refunded_item, 'set_quantity' ) ) ) {
						$refunded_item->set_quantity( $qty );
					}

					$refund_request->add_item( $refunded_item );
				}
			}

			$refund_request->update_taxes();
			$refund_request->calculate_totals( false );
			$refund_request->set_total( $refund_amount );

			if ( is_wp_error( $refund_request ) ) {
				throw new Exception( $refund_request->get_error_message() );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

		do_action( 'wcv_new_refund_request', $refund_request );

		// wp_send_json_success must be outside the try block not to break phpunit tests.
		wp_send_json_success( $response );
	}

	/**
	 * Cancel refund request via ajax.
	 */
	public function cancel_refund_request() {
		ob_start();

		check_ajax_referer( 'cancel-request', 'security' );

		$vendor_id  = get_current_user_id();
		$request_id = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;

		if ( ! $request_id || ! WCV_Vendors::is_vendor( $vendor_id ) ) {
			wp_die( __( 'Cheating uh?', 'wc-vendors-gateway-stripe-connect' ), 403 );
		}

		try {
			$request = new WCV_SC_Refund_Request( $request_id );
			if ( $request->get_vendor_id() != $vendor_id ) {
				wp_die( __( 'Cheating uh?', 'wc-vendors-gateway-stripe-connect' ), 403 );
			}

			$request->set_status( 'rf-cancelled' );
			$request->save();

			if ( is_wp_error( $request ) ) {
				throw new Exception( $request->get_error_message() );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

		// wp_send_json_success must be outside the try block not to break phpunit tests.
		wp_send_json_success( array() );
	}
}
