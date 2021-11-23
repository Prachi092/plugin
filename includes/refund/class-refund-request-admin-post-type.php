<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Refund Request Admin Post Type.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

/**
 * WC Vendors - Stripe Commissions & Gateway Refund Request Admin Post Type.
 *
 * @since 2.0.0
 */
class WCV_SC_Refund_Request_Admin_Post_Type {
	/**
	 * The value to use for the 'post__in' query param when no results should be returned.
	 *
	 * We can't use an empty array, because WP returns all posts when post__in is an empty
	 * array. Source: https://core.trac.wordpress.org/ticket/28099
	 *
	 * This would ideally be a private CONST but visibility modifiers are only allowed for
	 * class constants in PHP >= 7.1.
	 *
	 * @var    array
	 */
	private static $post__in_none = array( 0 );

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
		// Order/filter.
		add_filter( 'request', array( $this, 'request_query' ) );

		// List table.
		add_filter( 'manage_edit-wcv_refund_request_columns', array( $this, 'wcv_refund_request_columns' ) );
		add_action( 'manage_wcv_refund_request_posts_custom_column', array( $this, 'render_wcv_refund_request_columns' ), 2 );
		add_action( 'list_table_primary_column', array( $this, 'list_table_primary_column' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );

		// Bulk actions.
		add_action( 'load-edit.php', array( $this, 'parse_bulk_actions' ) );
		add_filter( 'bulk_actions-edit-wcv_refund_request', array( $this, 'define_bulk_actions' ) );
	}

	/**
	 * Filters and sorting handler
	 *
	 * @param  array $vars
	 *
	 * @return array
	 */
	public function request_query( $vars ) {
		global $typenow;
		global $wp_post_statuses;

		if ( 'wcv_refund_request' !== $typenow ) {
			return $vars;
		}

		// If we've using the 'none' flag for the post__in query var, there's no need to apply other query filters, as we're going to return no subscriptions anyway
		if ( isset( $vars['post__in'] ) && self::$post__in_none === $vars['post__in'] ) {
			return $vars;
		}

		// Status.
		if ( empty( $vars['post_status'] ) ) {
			$post_statuses = WCV_SC_Refund_Helpers::get_refund_request_statuses();

			foreach ( $post_statuses as $status => $value ) {
				if ( isset( $wp_post_statuses[ $status ] ) && false === $wp_post_statuses[ $status ]->show_in_admin_all_list ) {
					unset( $post_statuses[ $status ] );
				}
			}

			$vars['post_status'] = array_keys( $post_statuses );
		}

		return $vars;
	}

	/**
	 * Define custom columns for refund reequest
	 *
	 * Column names that have a corresponding `WC_Order` column use the `order_` prefix here
	 * to take advantage of core WooCommerce assets, like JS/CSS.
	 *
	 * @return array
	 */
	public function wcv_refund_request_columns() {
		return array(
			'cb'      => '<input type="checkbox" />',
			'status'  => __( 'Status', 'wc-vendors-gateway-stripe-connect' ),
			'request' => __( 'Refund Request', 'wc-vendors-gateway-stripe-connect' ),
			'vendor'  => __( 'Vendor', 'wc-vendors-gateway-stripe-connect' ),
			'amount'  => __( 'Amount', 'wc-vendors-gateway-stripe-connect' ),
			'reason'  => __( 'Reason', 'wc-vendors-gateway-stripe-connect' ),
			'date'    => __( 'Date', 'wc-vendors-gateway-stripe-connect' ),
		);
	}

	/**
	 * Output custom columns
	 *
	 * @version 1.0.2
	 *
	 * @param  string $column
	 */
	public function render_wcv_refund_request_columns( $column ) {
		global $post, $wp_list_table;

		$refund_request = new WCV_SC_Refund_Request( $post->ID );
		$column_content = '';

		switch ( $column ) {
			case 'status':
				// The status label
				$column_content = sprintf(
					'<mark class="%1$s tips" data-tip="%2$s">%2$s</mark>',
					sanitize_title( $refund_request->get_status() ),
					WCV_SC_Refund_Helpers::get_refund_request_status_name( $refund_request->get_status() )
				);

				break;
			case 'request':
				$column_content = sprintf(
					'<a href="%s"><strong>#%s</strong></a> %s <a href="%s">%s #%s</a>',
					get_edit_post_link( $post->ID ),
					$post->ID,
					__( 'for', 'wc-vendors-gateway-stripe-connect' ),
					get_edit_post_link( $refund_request->get_parent_id() ),
					__( 'Order', 'wc-vendors-gateway-stripe-connect' ),
					$refund_request->get_parent_id()
				);
				break;
			case 'vendor':
				if ( $refund_request->get_vendor_id() ) {
					$user_info      = get_userdata( $refund_request->get_vendor_id() );
					$column_content = sprintf(
						'<a href="%s">%s</a>',
						get_edit_user_link( $user_info->ID ),
						$user_info->display_name
					);
				}
				break;
			case 'amount':
				$column_content = wc_price( $refund_request->get_total() );
				break;
			case 'reason':
				$column_content = $refund_request->get_reason();
				break;
		}

		echo wp_kses(
			apply_filters( 'wcv_refund_request_list_table_column_content', $column_content, $refund_request, $column ),
			array(
				'a'      => array(
					'class'    => array(),
					'href'     => array(),
					'data-tip' => array(),
					'title'    => array(),
				),
				'time'   => array(
					'class' => array(),
					'title' => array(),
				),
				'mark'   => array(
					'class'    => array(),
					'data-tip' => array(),
				),
				'small'  => array( 'class' => array() ),
				'table'  => array(
					'class'       => array(),
					'cellspacing' => array(),
					'cellpadding' => array(),
				),
				'tr'     => array( 'class' => array() ),
				'td'     => array( 'class' => array() ),
				'div'    => array(
					'class'    => array(),
					'data-tip' => array(),
				),
				'br'     => array(),
				'strong' => array(),
				'span'   => array(
					'class'    => array(),
					'data-tip' => array(),
				),
				'p'      => array( 'class' => array() ),
				'button' => array(
					'type'  => array(),
					'class' => array(),
				),
			)
		);
	}

	/**
	 * Sets post table primary column.
	 *
	 * @since 1.0.2
	 *
	 * @param string $default
	 * @param string $screen_id
	 * @return string
	 */
	public function list_table_primary_column( $default, $screen_id ) {

		if ( 'edit-wcv_refund_request' == $screen_id ) {
			$default = 'request';
		}

		return $default;
	}

	/**
	 * We don't need quick edit here.
	 *
	 * @since 1.0.2
	 *
	 * @param array  $actions
	 * @param object $post
	 * @return array
	 */
	public function post_row_actions( $actions, $post ) {

		if ( 'wcv_refund_request' != $post->post_type ) {
			return $actions;
		}

		$status      = get_post_status( $post );
		$new_actions = [];
		$action_url  = add_query_arg(
			array(
				'post'     => $post->ID,
				'_wpnonce' => wp_create_nonce( 'bulk-posts' ),
			)
		);

		if ( isset( $_REQUEST['status'] ) ) {
			$action_url = add_query_arg( array( 'status' => $_REQUEST['status'] ), $action_url );
		}

		if ( $status === 'wc-rf-in-review' ) {

			$new_actions['rf-approved'] = sprintf( '<a href="%s">%s</a>', add_query_arg( 'action', 'rf-approved', $action_url ), __( 'Approve', 'wc-vendors-gateway-stripe-connect' ) );
			$new_actions['rf-rejected'] = sprintf( '<a href="%s">%s</a>', add_query_arg( 'action', 'rf-rejected', $action_url ), __( 'Reject', 'wc-vendors-gateway-stripe-connect' ) );
		}

		if ( $status != 'trash' && isset( $actions['trash'] ) ) {
			$new_actions['trash'] = $actions['trash'];
		}
		return $new_actions;
	}

	/**
	 * Deals with bulk actions. The style is similar to what WooCommerce is doing. Extensions will have to define their
	 * own logic by copying the concept behind this method.
	 */
	public function parse_bulk_actions() {

		if ( ! isset( $_REQUEST['post_type'] ) || 'wcv_refund_request' !== $_REQUEST['post_type'] || ! isset( $_REQUEST['post'] ) ) {
			return;
		}

		$action = '';

		if ( isset( $_REQUEST['action'] ) && - 1 != $_REQUEST['action'] ) {
			$action = $_REQUEST['action'];
		} elseif ( isset( $_REQUEST['action2'] ) && - 1 != $_REQUEST['action2'] ) {
			$action = $_REQUEST['action2'];
		}

		$changed     = 0;
		$request_ids = array_map( 'absint', (array) $_REQUEST['post'] );

		switch ( $action ) {
			case 'rf-approved':
			case 'rf-rejected':
				$new_status = $action;
				break;
			default:
				return;
		}

		$report_action = 'marked_' . $new_status;

		$sendback_args = array(
			'post_type'    => 'wcv_refund_request',
			$report_action => true,
			'ids'          => join( ',', $request_ids ),
			'error_count'  => 0,
		);

		foreach ( $request_ids as $request_id ) {
			$request = new WCV_SC_Refund_Request( $request_id );
			if ( 'rf-in-review' != $request->get_status() ) {
				continue;
			}
			try {
				$request->set_status( $new_status );
				$request->save();
			} catch ( Exception $e ) {
				$sendback_args['error'] = urlencode( $e->getMessage() );
				$sendback_args['error_count'] ++;
			}
		}

		$sendback_args['changed'] = $changed;
		$sendback                 = add_query_arg( $sendback_args, wp_get_referer() ? wp_get_referer() : '' );
		wp_safe_redirect( esc_url_raw( $sendback ) );

		exit();
	}

	/**
	 * Define bulk actions.
	 *
	 * @param array $actions Existing actions.
	 * @return array
	 */
	public function define_bulk_actions( $actions ) {
		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		$actions['rf-approved'] = __( 'Approve', 'wc-vendors-gateway-stripe-connect' );
		$actions['rf-rejected'] = __( 'Reject', 'wc-vendors-gateway-stripe-connect' );

		return $actions;
	}
}
