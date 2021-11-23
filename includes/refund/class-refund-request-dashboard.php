<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Refund Request Dashboard.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

/**
 * WC Vendors - Stripe Commissions & Gateway Refund Request Dashboard.
 *
 * @since 2.0.0
 */
class WCV_SC_Refund_Request_Dashboard {
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
		// Add menu item
		add_filter( 'wcv_pro_dashboard_urls', array( $this, 'add_nav_item' ) );

		// Table display
		add_filter( 'wcvendors_pro_table_columns_wcv_refund_request', array( $this, 'table_columns' ) );
		add_filter( 'wcvendors_pro_table_rows_wcv_refund_request', array( $this, 'table_rows' ), 10, 2 );
		add_filter( 'wcvendors_pro_table_action_column_wcv_refund_request', array( $this, 'table_action_column' ) );
		add_filter( 'wcvendors_pro_table_no_data_notice_wcv_refund_request', array( $this, 'no_request_found_text' ) );
	}

	/**
	 * Hook into the navigation to create a new page for our post typel
	 *
	 * @since 1.0.0
	 * @return $pages the dashboard pages array with the extra tab
	 */
	public function add_nav_item( $pages ) {
		if ( 'yes' === WCV_SC_Helper::get_settings( 'disable_refund' ) ) {
			return $pages;
		}

		$pages['wcv_refund_request'] = array(
			'slug'    => 'wcv_refund_request',
			'id'      => 'wcv_refund_request',
			'label'   => __( 'Refund Requests', 'wc-vendors-gateway-stripe-connect' ),
			'actions' => array(),
		);

		return $pages;
	}

	/**
	 *  Update Table columns for display of our post type
	 *
	 * @since    1.0.0
	 * @param    array $columns  array passed via filter
	 */
	public function table_columns( $columns ) {
		$columns = array(
			'request' => __( 'Request', 'wc-vendors-gateway-stripe-connect' ),
			'status'  => __( 'Status', 'wc-vendors-gateway-stripe-connect' ),
			'order'   => __( 'Parent order', 'wc-vendors-gateway-stripe-connect' ),
			'amount'  => __( 'Amount', 'wc-vendors-gateway-stripe-connect' ),
			'reason'  => __( 'Reason', 'wc-vendors-gateway-stripe-connect' ),
			'date'    => __( 'Date', 'wc-vendors-gateway-stripe-connect' ),
		);

		return apply_filters( 'wcv_refund_request_table_columns', $columns );
	}

	/**
	 *  Manipulate the table data
	 *
	 * @since    1.0.0
	 * @param    array $rows           array of wp_post objects passed by the filter
	 * @param    mixed $result_object  the wp_query object
	 * @return   array  $new_rows       array of stdClass objects passed back to the filter
	 * @todo remove useless condition.
	 */
	public function table_rows( $rows, $result_object ) {
		$this->max_num_pages = $result_object->max_num_pages;
		$new_rows            = array();

		foreach ( $rows as $row ) {
			if ( $row->post_type !== 'wcv_refund_request' ) {
				continue;
			}

			$request = new WCV_SC_Refund_Request( $row->ID );

			if ( $request ) {
				$new_row = new stdClass();

				$new_row->ID      = $request->get_id();
				$new_row->request = '#' . $request->get_id();
				$new_row->status  = WCV_SC_Refund_Helpers::get_refund_request_status_name( $request->get_status() );
				$new_row->order   = sprintf( __( 'Order #%d', 'wc-vendors-gateway-stripe-connect' ), $request->get_parent_id() );
				$new_row->amount  = wc_price( $request->get_total() );
				$new_row->reason  = $request->get_reason();
				$new_row->date    = $request->get_date_created()->date_i18n( wc_date_format() );

				$actions = array(
					'view_details' => array(
						'label'  => __( 'View detail', 'wcv-wcs-subscription' ),
						'url'    => '#',
						'custom' => array( 'id' => 'open-refund-request-details-modal-' . $row->ID ),
					),
				);

				$new_row_actions = apply_filters( 'wcv_refund_request_row-actions_' . $row->ID, $actions );

				$new_row->row_actions  = $new_row_actions;
				$new_row->action_after = $this->load_details_template( $request );

				$new_rows[] = $new_row;
			}
		}

		return $new_rows;
	}

	/**
	 * Get the detail modal template.
	 *
	 * @param  WCV_SC_Refund_Request $request Refund request object.
	 * @return void
	 */
	public function load_details_template( $request ) {
		ob_start();

		wc_get_template(
			'request-details.php',
			array(
				'order'      => $request,
				'order_id'   => $request->get_id(),
				'line_items' => $request->get_items(),
				'shipping'   => $request->get_items( 'shipping' ),
			),
			'wc-vendors/dashboard/',
			WCV_SC_PLUGIN_PATH . '/templates/dashboard/'
		);

		return ob_get_clean();
	}

	/**
	 *  Change the column that actions are displayed in
	 *
	 * @since    1.0.0
	 * @param    string $column         column passed from filter
	 * @return   string $new_column new column passed back to filter
	 */
	public function table_action_column( $column ) {
		$new_column = 'status';

		return apply_filters( 'wcv_refund_request_table_action_column', $new_column );
	}

	public function no_request_found_text() {
		return __( 'No request found.', 'wc-vendors-gateway-stripe-connect' );
	}
}
