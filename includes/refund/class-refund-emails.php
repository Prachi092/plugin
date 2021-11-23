<?php
class WCV_SC_Refund_Emails {
	private $template_base = '';

	public function __construct() {
		$this->template_base = WCV_SC_PLUGIN_PATH . '/templates/';

		add_filter( 'woocommerce_email_classes', array( $this, 'add_refund_emails' ) );
		add_filter( 'woocommerce_email_actions', array( $this, 'register_email_actions' ) );

		add_action( 'wcv_email_refund_request_details', array( $this, 'request_details_template' ), 10, 4 );

		remove_action( 'woocommerce_email_after_order_table', 'WC_Subscriptions_Order::add_sub_info_email', 15, 3 );
	}

	public function add_refund_emails( $email_classes ) {

		// add the email class to the list of email classes that WooCommerce loads
		$email_classes['WCV_Admin_New_Refund_Request_Email']       = new WCV_Admin_New_Refund_Request_Email();
		$email_classes['WCV_Vendor_Refund_Request_Approved_Email'] = new WCV_Vendor_Refund_Request_Approved_Email();
		$email_classes['WCV_Vendor_Refund_Request_Rejected_Email'] = new WCV_Vendor_Refund_Request_Rejected_Email();

		return $email_classes;
	}

	public function register_email_actions( $email_actions ) {
		$email_actions[] = 'wcv_new_refund_request';
		$email_actions[] = 'wcv_refund_request_status_rf-in-review_to_rf-approved';
		$email_actions[] = 'wcv_refund_request_status_rf-in-review_to_rf-rejected';

		return $email_actions;
	}

	public function request_details_template( $request, $sent_to_admin = false, $plain_text = false, $email = '' ) {
		if ( $plain_text ) {
			wc_get_template(
				'emails/refund/plain/request-details.php',
				array(
					'request'       => $request,
					'sent_to_admin' => $sent_to_admin,
					'plain_text'    => $plain_text,
					'email'         => $email,
				),
				'woocommerce',
				$this->template_base
			);
		} else {
			wc_get_template(
				'emails/refund/request-details.php',
				array(
					'request'       => $request,
					'sent_to_admin' => $sent_to_admin,
					'plain_text'    => $plain_text,
					'email'         => $email,
				),
				'woocommerce',
				$this->template_base
			);
		}
	}
}
