<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Users.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

/**
 * WC Vendors - Stripe Commissions & Gateway Users.
 *
 * @since 2.0.0
 */
class WCV_SC_Users {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.0.0
	 *
	 * @var   WC_Vendors_Stripe_Commissions_Gateway
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  2.0.0
	 *
	 * @param  WC_Vendors_Stripe_Commissions_Gateway $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  2.0.0
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_filter( 'manage_users_columns', [ $this, 'register_connect_status_column' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'connect_status_column_content' ], 10, 3 );
		add_action( 'show_user_profile', [ $this, 'connect_status_profile_fields' ], 99 );
		add_action( 'edit_user_profile', [ $this, 'connect_status_profile_fields' ], 99 );
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'stripe-connect-admin', WCV_SC_PLUGIN_URL . '/assets/css/stripe-connect-admin.css', [], WCV_SC_VERSION );
	}

	public function register_connect_status_column( $columns ) {
		return array_merge(
			$columns,
			[ 'connect_status' => __( 'Connect status', 'wc-vendors-gateway-stripe-connect' ) ]
		);
	}

	public function connect_status_column_content( $output, $column_name, $user_id ) {
		if ( 'connect_status' != $column_name ) {
			return $output;
		}

		if ( ! WCV_Vendors::is_vendor( $user_id ) ) {
			return '';
		}

		return $this->get_connect_status_html( $user_id );
	}

	public function get_connect_status_html( $user_id ) {
		$stripe_connect_id = get_user_meta( $user_id, '_stripe_connect_user_id' );

		if ( $stripe_connect_id ) {
			return sprintf(
				'<span class="connected">%s</span>',
				__( 'Connected', 'wc-vendors-gateway-stripe-connect' )
			);
		}

		return sprintf(
			'<span class="not-connected">%s</span>',
			__( 'Not-Connected', 'wc-vendors-gateway-stripe-connect' )
		);
	}

	public function connect_status_profile_fields( $user ) {
		if ( ! WCV_Vendors::is_vendor( $user->ID ) ) {
			return;
		}
		?>
	<table class="form-table">
		<tr>
			<th><label><?php esc_html_e( 'Stripe connect status', 'wc-vendors-gateway-stripe-connect' ); ?></label></th>
			<td class="connect-status"><?php echo $this->get_connect_status_html( $user->ID ); ?></td>
		</tr>
	</table>
		<?php
	}
}
