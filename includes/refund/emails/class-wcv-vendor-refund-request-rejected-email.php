<?php

class WCV_Vendor_Refund_Request_Rejected_Email extends WC_Email {

	public function __construct() {
		$this->id          = 'vendor_refund_request_rejected';
		$this->title       = __( 'Refund request rejected', 'wc-vendors-gateway-stripe-connect' );
		$this->description = __( 'This email notify vendors when their requests are rejected.', 'wc-vendors-gateway-stripe-connect' );

		// Default settings.
		$this->heading = __( 'Refund request has been rejected.', 'wc-vendors-gateway-stripe-connect' );
		$this->subject = __( 'Refund request has been rejected', 'wc-vendors-gateway-stripe-connect' );

		$this->template_html  = 'emails/refund/vendor-refund-request-rejected.php';
		$this->template_plain = 'emails/refund/plain/vendor-refund-request-rejected.php';
		$this->template_base  = WCV_SC_PLUGIN_PATH . '/templates/';

		add_action( 'wcv_refund_request_status_rf-in-review_to_rf-rejected_notification', array( $this, 'trigger' ), 10, 2 );

		parent::__construct();
	}

	public function trigger( $request_id, $request ) {
		$this->object                           = $request;
		$this->placeholders['{request_date}']   = wc_format_datetime( $this->object->get_date_created() );
		$this->placeholders['{request_number}'] = $this->object->get_id();
		$this->placeholders['{order_number}']   = $this->object->get_parent_id();
		$this->vendor_id                        = $this->object->get_vendor_id();

		$vendor = get_userdata( $this->vendor_id );

		$this->recipient = $vendor->user_email;

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
	}


	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'request'       => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => false,
				'email'         => $this,
				'vendor_id'     => $this->vendor_id,
			),
			'woocommerce',
			$this->template_base
		);
	}

	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'request'       => $this->object,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => true,
				'plain_text'    => false,
				'email'         => $this,
				'vendor_id'     => $this->vendor_id,
			),
			'woocommerce',
			$this->template_base
		);
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'wc-vendors-gateway-stripe-connect' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'wc-vendors-gateway-stripe-connect' ),
				'default' => 'yes',
			),
			'subject'    => array(
				'title'       => __( 'Subject', 'wc-vendors-gateway-stripe-connect' ),
				'type'        => 'text',
				'desc_tip'    => true,
				/* translators: %s: list of placeholders */
				'description' => sprintf( __( 'Available placeholders: %s', 'wc-vendors-gateway-stripe-connect' ), '<code>{site_title}, {request_date}, {requets_number}, {order_number}</code>' ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'    => array(
				'title'       => __( 'Email heading', 'wc-vendors-gateway-stripe-connect' ),
				'type'        => 'text',
				'desc_tip'    => true,
				/* translators: %s: list of placeholders */
				'description' => sprintf( __( 'Available placeholders: %s', 'wc-vendors-gateway-stripe-connect' ), '<code>{site_title}, {request_date}, {requets_number}, {order_number}</code>' ),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'wc-vendors-gateway-stripe-connect' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'wc-vendors-gateway-stripe-connect' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}
}
