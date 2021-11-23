<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Vendor Dashboard.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

use Stripe\Stripe;
use Stripe\OAuth;

class WCV_SC_Vendor_Dashboard {
	/**
	 * Parent plugin class.
	 *
	 * @since 2.0.0
	 *
	 * @var   WC_Vendors_Stripe_Commissions_Gateway
	 */
	protected $plugin = null;

	/**
	 * Test mode
	 */
	protected $testmode;

	/**
	 * The client Id;
	 */
	protected $client_id;


	/**
	 * The secret key
	 */
	protected $secret_key;

	/**
	 * Publishable key
	 */
	protected $publishable_key;

	/**
	 * Stripe connect theme
	 */
	protected $connect_button_theme;

	/**
	 * Payout Method
	 */
	protected $payout_method;

	/**
	 * Payout Schedule
	 */
	protected $payout_schedule;

	/**
	 * Weekly Payouts
	 */
	protected $weekly_anchor;

	/**
	 * Montly Payouts
	 */
	protected $monthly_anchor;


	/**
	 * Constructor.
	 *
	 * @since  2.0.0
	 *
	 * @param  WC_Vendors_Stripe_Commissions_Gateway $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin                  = $plugin;
		$this->stripe_connect_settings = get_option( 'woocommerce_stripe-connect_settings', array() );

		if ( ! empty( $this->stripe_connect_settings ) ) {
			$this->testmode             = wc_string_to_bool( $this->stripe_connect_settings['testmode'] );
			$this->secret_key           = $this->testmode ? $this->stripe_connect_settings['test_secret_key'] : $this->stripe_connect_settings['secret_key'];
			$this->publishable_key      = $this->testmode ? $this->stripe_connect_settings['test_publishable_key'] : $this->stripe_connect_settings['publishable_key'];
			$this->client_id            = $this->testmode ? $this->stripe_connect_settings['test_client_id'] : $this->stripe_connect_settings['client_id'];
			$this->connect_button_theme = $this->stripe_connect_settings['connect_button_theme'];
			$this->redirect_uri_page_id = $this->stripe_connect_settings['redirect_uri'];
			$this->redirect_uri         = home_url(); // $this->redirect_uri_page_id ? get_permalink( $this->redirect_uri_page_id ) : '';
			$this->payout_method 		= $this->stripe_connect_settings['payout_method'] ? $this->stripe_connect_settings['payout_method'] : '';
			$this->payout_schedule 		= $this->stripe_connect_settings['payout_schedule'] ? $this->stripe_connect_settings['payout_schedule'] : '';
			$this->weekly_anchor      	= $this->stripe_connect_settings['weekly_anchor'] ? $this->stripe_connect_settings['weekly_anchor'] : '';
			$this->monthly_anchor 		= $this->stripe_connect_settings['monthly_anchor'] ? $this->stripe_connect_settings['monthly_anchor'] : '';

			if ( $this->redirect_uri_page_id ) {
				$this->redirect_uri = get_permalink( $this->redirect_uri_page_id );
			} elseif ( get_option( 'woocommerce_myaccount_page_id' ) ) {
				$this->redirect_uri = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
			}

			Stripe::setApiKey( $this->secret_key );
			// Set the Stripe API version.
			Stripe::setApiVersion( WCV_SC_STRIPE_API_VER );
			Stripe::setClientId( $this->client_id );
		}

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
		// Hook into dashboard
		add_action( 'wcvendors_settings_after_paypal', array( $this, 'stripe_connect_vendor' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'connect_scripts' ) );
		add_action( 'parse_request', array( $this, 'check_stripe_connect' ), 15 );

		// Disable add new product if vendor is not connected.
		add_filter( 'wcv_product_table_lock_new_products', array( $this, 'product_table_lock_new_products' ) );
		add_filter( 'wcv_dashboard_quick_links', array( $this, 'remove_add_product_from_quick_links' ) );
		add_action( 'template_redirect', array( $this, 'new_product_redirect' ) );
		add_action( 'template_redirect', array( $this, 'product_edit_redirect' ) );
		add_action( 'wcv_pro_after_dashboard_nav', array( $this, 'connect_notice' ) );

		// Free dashboard
		add_filter( 'wcv_dashboard_nav_items', array( $this, 'remove_add_product_from_dashboard_nav' ) );
		add_action( 'wcvendors_before_dashboard', array( $this, 'free_dashboard_notice' ) );
	}

	/**
	 * Load the stripe connect scripts on the front end
	 */
	public function connect_scripts() {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return; }

		// Only load the styles on the dashboard pages on the front end
		if ( has_shortcode( $post->post_content, 'wcv_shop_settings' ) || has_shortcode( $post->post_content, 'wcv_pro_dashboard' ) ) {
			wp_register_style( 'stripe_connect_styles', plugins_url( 'assets/css/stripe-connect.css', WCV_SC_MAIN_FILE ), array(), WCV_SC_VERSION );
			wp_enqueue_style( 'stripe_connect_styles' );
		}
	}

	/**
	 * Provide an interface for the vendors to connect their stripe accounts
	 */
	public function stripe_connect_vendor() {
		try {
			$stripe_connect_access_key = get_user_meta( get_current_user_id(), '_stripe_connect_access_key', true );
			$stripe_connect_user_id    = get_user_meta( get_current_user_id(), '_stripe_connect_user_id', true );
			$button_theme_css          = 'stripe-connect ';

			$vendor_note = '';
			if ( WCV_SC_Helper::use_charges_transfers() ) {
				$platform_account_country_country = WCV_SC_Helper::get_stripe_account_country();
				if ( $platform_account_country_country == 'US' ) {
					$vendor_note = __( 'Your Stripe account must be in US to connect to this marketplace', 'wc-vendors-gateway-stripe-connect' );
				} else {
					$vendor_note = __( 'Your Stripe account must be in Europe to connect to this marketplace', 'wc-vendors-gateway-stripe-connect' );
				}
			}

			$args = array(
				'scope' => 'read_write',
				'state' => get_current_user_id(),
			);
			if ( $this->redirect_uri_page_id ) {
				$args['redirect_uri'] = $this->redirect_uri;
			}

			$connect_url = OAuth::authorizeUrl( $args );

			switch ( $this->connect_button_theme ) {
				case 'blue_border':
					$button_theme_css .= ' border';
					break;
				case 'light_blue':
					$button_theme_css .= 'light-blue';
					break;
				case 'light_blue_border':
					$button_theme_css .= 'light-blue border';
					break;
				default:
					// default blue
					$button_theme_css = 'stripe-connect';
					break;
			}

			include_once apply_filters( 'wcv_sc_stripe_connect_vendor_template', dirname( __FILE__ ) . '/frontend/html-vendor-stripe-connect.php' );
		} catch ( Exception $e ) {
			printf(
				'<strong>%s</strong>',
				__( 'Can not connect to Stripe because this marketplace uses invalid Stripe API keys.', 'wc-vendors-gateway-stripe-connect' )
			);
			printf(
				'<pre>%s: %s</pre>',
				__( 'Error code', 'wc-vendors-gateway-stripe-connect' ),
				$e->getMessage()
			);
		}
	}

	/**
	 * Check the Stripe Redirect
	 */
	public function check_stripe_connect() {
		$message_type = 'success';

		// Connect the vendor to Stripe
		if ( isset( $_GET['scope'] ) && ! empty( $_GET['scope'] ) && isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
			$code = $_GET['code'];

			try {
				$oauth = OAuth::token(
					array(
						'grant_type' => 'authorization_code',
						'code'       => $code,
					)
				);

				if ( WCV_SC_Helper::use_charges_transfers() ) {
					$platform_account_country = WCV_SC_Helper::get_stripe_account_country();
					$connected_account        = \Stripe\Account::retrieve( $oauth->stripe_user_id );

					if ( 'US' == strtoupper( $platform_account_country ) ) {
						if ( 'US' != strtoupper( $connected_account->country ) ) {
							wc_add_notice( __( 'Your Stripe country account must be in the US to connect to this marketplace.', 'wc-vendors-gateway-stripe-connect' ), 'error' );
							wp_safe_redirect( $this->redirect_uri );
							exit;
						}
					} else {
						if (
							'US' == strtoupper( $connected_account->country )
							 || ! WCV_SC_Helper::is_eiglible_for_separate_charges_transfers( $connected_account->country )
						) {
							wc_add_notice( __( 'Your Stripe country account must be in the Europe to connect to this marketplace.', 'wc-vendors-gateway-stripe-connect' ), 'error' );
							wp_safe_redirect( $this->redirect_uri );
							exit;
						}
					}
				}

				// Get the user_id passed back from Stripe this is added as a part of the authorizeURL
				$user_id = $_GET['state'];
				// Add the stripe connect data to the user meta
				update_user_meta( $user_id, '_stripe_connect_access_key', $oauth->access_token );
				update_user_meta( $user_id, '_stripe_connect_user_id', $oauth->stripe_user_id );
				update_user_meta( $user_id, '_stripe_connect_refresh_token', $oauth->refresh_token );
				update_user_meta( $user_id, '_stripe_connect_stripe_publishable_key', $oauth->stripe_publishable_key );
				update_user_meta( $user_id, '_stripe_connect_method', $this->payout_method );
				if ( $this->payout_method == 'automaticsehedule' ) {
                    if ( $this->payout_schedule == 'monthly' ) {
                        $stripe_interval = ['interval' => 'monthly','monthly_anchor' => $this->monthly_anchor];
                    }elseif ( $this->payout_schedule == 'weekly' ) {
                        $stripe_interval = ['interval' => 'weekly','weekly_anchor' => $this->weekly_anchor];
                    }else {
                        $stripe_interval = ['interval' => 'daily'];
                    }
                } else if($this->payout_method == 'manual') {
                    $stripe_interval = ['interval' => 'manual'];
                }
				
				// update user account with stripe payouts
                if ($this->payout_method == 'automaticsehedule' || $this->payout_method == 'manual' ) {
                    $stripe_payout_method = \Stripe\Account::update( $oauth->stripe_user_id,['settings' =>['payouts' =>['schedule' => $stripe_interval]]]);
                }

				wc_add_notice( __( 'Your account has been connected to Stripe.', 'wc-vendors-gateway-stripe-connect' ), 'success' );
				wp_safe_redirect( $this->redirect_uri );
				exit;
			} catch ( \Stripe\Exception\OAuth\OAuthErrorException $e ) {
				wc_add_notice( $e->getMessage(), 'error' );
				WCV_SC_Logger::log( sprintf( __( 'Stripe Connect Error: %s', 'wc-vendors-gateway-stripe-connect' ), $e->getMessage() ) );
			}
		}

		// De Authorize the vendor from Stripe
		if ( isset( $_GET['deauth'] ) ) {

			// Deauthorization request
			$stripe_account_id = $_GET['deauth'];

			try {
				OAuth::deauthorize(
					array(
						'stripe_user_id' => $stripe_account_id,
					)
				);

				// Remove the stripe data from the user meta
				delete_user_meta( get_current_user_id(), '_stripe_connect_access_key' );
				delete_user_meta( get_current_user_id(), '_stripe_connect_user_id' );
				delete_user_meta( get_current_user_id(), '_stripe_connect_refresh_token' );
				delete_user_meta( get_current_user_id(), '_stripe_connect_stripe_publishable_key' );

				wc_add_notice( __( 'Your account has been disconnected from Stripe', 'wc-vendors-gateway-stripe-connect' ), 'notice' );
				wp_safe_redirect( $this->redirect_uri );
				exit;
			} catch ( \Stripe\Exception\OAuth\OAuthErrorException $e ) {
				$force_disconnect = sprintf( __( '<a href="%s">Force disconnect?</a>', 'wc-vendors-gateway-stripe-connect' ), add_query_arg( 'force_deauth', '1', $this->redirect_uri ) );
				wc_add_notice( $e->getMessage() . ' ' . $force_disconnect, 'error' );
				WCV_SC_Logger::log( sprintf( __( 'Stripe DeAuthorize Error: %s', 'wc-vendors-gateway-stripe-connect' ), $e->getMessage() ) );
			}
		}

		// De Authorize the vendor from Stripe
		if ( isset( $_GET['force_deauth'] ) ) {

			// Remove the stripe data from the user meta
			delete_user_meta( get_current_user_id(), '_stripe_connect_access_key' );
			delete_user_meta( get_current_user_id(), '_stripe_connect_user_id' );
			delete_user_meta( get_current_user_id(), '_stripe_connect_refresh_token' );
			delete_user_meta( get_current_user_id(), '_stripe_connect_stripe_publishable_key' );

			wc_add_notice( __( 'Your account has been disconnected from Stripe', 'wc-vendors-gateway-stripe-connect' ), 'notice' );
			wp_safe_redirect( $this->redirect_uri );
			exit;
		}

		// Error from the response display the error
		if ( isset( $_GET['error'] ) ) {
			// The user was redirect back from the OAuth form with an error.
			$error             = $_GET['error'];
			$error_description = $_GET['error_description'];
			$message           = 'Error: code=' . htmlspecialchars( $error, ENT_QUOTES ) . ', description=' . htmlspecialchars( $error_description, ENT_QUOTES ) . "\n";
			$message_type      = 'error';

			wc_add_notice( $message, $message_type );
			wp_safe_redirect( $this->redirect_uri );
			exit;
		}
	}

	/**
	 * Check if current page is a dashboard page.
	 *
	 * @param string $screen
	 *
	 * @return bool
	 */
	public function is_current_dashboard_page( $screen = '' ) {
		if ( ! class_exists( 'WCVendors_Pro_Dashboard' ) ) {
			return false;
		}
		global $wp;
		$current_page   = untrailingslashit( home_url( add_query_arg( array(), $wp->request ) ) );
		$dashboard_page = untrailingslashit( WCVendors_Pro_Dashboard::get_dashboard_page_url( $screen ) );

		return $current_page === $dashboard_page;
	}

	/**
	 * Check if current page is edit product page.
	 *
	 * @return bool
	 */
	public function is_current_edit_page() {
		if ( ! class_exists( 'WCVendors_Pro_Dashboard' ) ) {
			return false;
		}
		global $wp;
		$current_page   = untrailingslashit( home_url( add_query_arg( array(), $wp->request ) ) );
		$dashboard_page = trailingslashit( WCVendors_Pro_Dashboard::get_dashboard_page_url( 'product/edit' ) );

		if ( ! strpos( $current_page, 'edit' ) ) {
			return false;
		}

		$product_id = str_replace( $dashboard_page, '', $current_page );

		if ( is_numeric( $product_id ) ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Prevent user add new product if he hasn't connected.
	 *
	 * @param $current
	 *
	 * @return bool
	 */
	public function product_table_lock_new_products( $current ) {
		if ( ! WCV_SC_Helper::vendor_can_add_new_product() ) {
			return true;
		}

		return $current;
	}

	/**
	 * Redirect to dashboard page if vendors haven't connected their Stripe account.
	 */
	public function new_product_redirect() {
		if ( ! class_exists( 'WCVendors_Pro_Dashboard' ) ) {
			return;
		}

		if ( WCV_SC_Helper::vendor_can_add_new_product()
			 || ! $this->is_current_dashboard_page( 'product/edit' ) ) {
			return;
		}
		wp_safe_redirect( WCVendors_Pro_Dashboard::get_dashboard_page_url( 'product' ) );
		exit;
	}

	public function product_edit_redirect() {
		if ( ! class_exists( 'WCVendors_Pro_Dashboard' ) ) {
			return;
		}

		if ( WCV_SC_Helper::vendor_can_edit_product()
			 || ! $this->is_current_edit_page() ) {
			return;
		}
		wp_safe_redirect( WCVendors_Pro_Dashboard::get_dashboard_page_url( 'product' ) );
		exit;
	}

	/**
	 * Remove add new product link from quick links array if vendors haven't connected their Stripe account.
	 *
	 * @param $links
	 *
	 * @todo replace unset with array_filter.
	 *
	 * @return array
	 */
	public function remove_add_product_from_quick_links( $links ) {
		if ( ! WCV_SC_Helper::vendor_can_add_new_product() ) {
			unset( $links['product'] );
		}

		return $links;
	}

	public function remove_add_product_from_dashboard_nav( $links ) {
		if ( ! WCV_SC_Helper::vendor_can_add_new_product() ) {
			unset( $links['submit_link'] );
		}

		return $links;
	}

	/**
	 * Show a notice tell vendor to connect to Stripe to add new product.
	 */
	public function connect_notice() {
		if ( ! defined( 'WCV_PRO_ABSPATH_TEMPLATES' ) ) {
			return;
		}

		if ( WCV_SC_Helper::vendor_can_add_new_product() ) {
			return;
		}

		if ( WCV_SC_Helper::vendor_can_edit_product() ) {
			wc_get_template(
				'dashboard-notice.php',
				array(
					'vendor_dashboard_notice' => sprintf(
						__( 'You need to <a href="%s">connect your Stripe account</a> to add products.', 'wc-vendors-gateway-stripe-connect' ),
						WCVendors_Pro_Dashboard::get_dashboard_page_url( 'settings#payment' )
					),
					'notice_type'             => 'error',
				),
				'wc-vendors/dashboard/',
				WCV_PRO_ABSPATH_TEMPLATES . 'dashboard/'
			);
		} else {
			wc_get_template(
				'dashboard-notice.php',
				array(
					'vendor_dashboard_notice' => sprintf(
						__( 'You need to <a href="%s">connect your Stripe account</a> to add or edit products.', 'wc-vendors-gateway-stripe-connect' ),
						WCVendors_Pro_Dashboard::get_dashboard_page_url( 'settings#payment' )
					),
					'notice_type'             => 'error',
				),
				'wc-vendors/dashboard/',
				WCV_PRO_ABSPATH_TEMPLATES . 'dashboard/'
			);
		}
	}

	/**
	 * Show a notice tell vendor to connect to Stripe to add new product.
	 */
	public function free_dashboard_notice() {
		if ( WCV_SC_Helper::vendor_can_add_new_product() ) {
			return;
		}

		wc_add_notice(
			sprintf(
				__( 'You need to <a href="%s">connect your Stripe account</a> before adding products.', 'wc-vendors-gateway-stripe-connect' ),
				get_permalink( get_option( 'wcvendors_shop_settings_page_id' ) )
			),
			'error'
		);
	}
}
