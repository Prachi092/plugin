<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Woocommerce Myaccount.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

class WCV_SC_WooCommerce_Myaccount {
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
		if ( WCV_SC_Helper::is_configured() ) {
			$this->hooks();
		}
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  2.0.0
	 */
	public function hooks() {
		\Stripe\Stripe::setApiKey( WCV_SC_Connect_API::get_secret_key() );
		// Set the Stripe API version.
		\Stripe\Stripe::setApiVersion( WCV_SC_STRIPE_API_VER );

		add_action( 'admin_menu', array( $this, 'admin_hide_add_new_product' ) );
		add_filter( 'user_has_cap', array( $this, 'admin_disable_edit_product' ), 10, 3 );
	}

	/**
	 * Hide add new product links in admin side.
	 *
	 * @todo move this method to properly class.
	 */
	public function admin_hide_add_new_product() {
		if ( WCV_SC_Helper::vendor_can_add_new_product() ) {
			return;
		}

		global $submenu;
		unset( $submenu['edit.php?post_type=product'][10] );

		// Hide link on listing page
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'product' ) {
			echo '<style type="text/css">h1.wp-heading-inline + .page-title-action { display:none; }</style>';
		}
	}

	/**
	 * Disable edit product if vendor is not connected to Stripe.
	 *
	 * @param $allcaps
	 * @param $cap
	 * @param $args
	 *
	 * @return mixed
	 */
	public function admin_disable_edit_product( $allcaps, $cap, $args ) {
		if ( WCV_SC_Helper::vendor_can_edit_product( $args[1] ) ) {
			return $allcaps;
		}

		// Bail out if we're not asking about a post:
		if ( 'edit_post' != $args[0] ) {
			return $allcaps;
		}

		// Bail out for users who can already edit others posts:
		if ( isset( $allcaps['edit_others_posts'] ) && $allcaps['edit_others_posts'] ) {
			return $allcaps;
		}

		$allcaps[ $cap[0] ] = false;

		return $allcaps;
	}
}
