<?php
/**
 * Plugin Name: WC Vendors Stripe Connect
 * Plugin URI:  https://www.wcvendors.com/product/wc-vendors-stripe-connect/
 * Description: Take credit card payments and payout your vendor commissions with Stripe Connect
 * Version:     2.1.0
 * Author:      WC Vendors
 * Author URI:  https://wcvendors.com
 * Donate link: https://www.wcvendors.com/product/wc-vendors-stripe-connect/
 * License:     GPLv2
 * Text Domain: wc-vendors-gateway-stripe-connect
 * Domain Path: /languages
 *
 * @link    https://www.wcvendors.com/product/wc-vendors-stripe-connect/
 *
 * Requires at least:    5.3.0 
 * Tested up to:         5.8.2
 * WC requires at least: 4.0
 * WC tested up to:      5.9.0
 */

/**
 * Copyright (c) 2019 Jamie Madden (jamie@wcvendors.com) (email : support@wcvendors.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


// Use composer autoload.
require 'vendor/autoload.php';

define( 'WCV_SC_VERSION', '2.1.0' );
define( 'WCV_SC_MIN_PHP_VER', '5.6.0' );
define( 'WCV_SC_MIN_WC_VER', '3.0.0' );
define( 'WCV_SC_MAIN_FILE', __FILE__ );
define( 'WCV_SC_STRIPE_API_VER', '2020-03-02' );
define( 'WCV_SC_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WCV_SC_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

/**
 * Main initiation class.
 *
 * @since  2.0.0
 */
final class WC_Vendors_Stripe_Connect_Gateway {

	/**
	 * Current version2.0.7
	 *
	 * @var    string
	 * @since  2.0.0
	 */
	const VERSION = WCV_SC_VERSION;

	/**
	 * URL of plugin directory.
	 *
	 * @var    string
	 * @since  2.0.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @var    string
	 * @since  2.0.0
	 */
	protected $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var    string
	 * @since  2.0.0
	 */
	protected $basename = '';

	/**
	 * Detailed activation error messages.
	 *
	 * @var    array
	 * @since  2.0.0
	 */
	protected $activation_errors = array();

	/**
	 * Singleton instance of plugin.
	 *
	 * @var    WC_Vendors_Stripe_Connect_Gateway
	 * @since  2.0.0
	 */
	protected static $single_instance = null;

	/**
	 * Instance of WCV_SC_Gateway_Stripe_Connect
	 *
	 * @since2.0.0
	 * @var WCV_SC_Gateway_Stripe_Connect
	 */
	protected $gateway_stripe_connect;

	/**
	 * Instance of WCV_SC_Woocommerce_Myaccount
	 *
	 * @since2.0.0
	 * @var WCV_SC_WooCommerce_Myaccount
	 */
	protected $woocommerce_myaccount;

	/**
	 * Instance of WCV_SC_Logger
	 *
	 * @since2.0.0
	 * @var WCV_SC_Logger
	 */
	protected $logger;

	/**
	 * Instance of WCV_SC_Helper
	 *
	 * @since2.0.0
	 * @var WCV_SC_Helper
	 */
	protected $helper;

	/**
	 * Instance of WCV_SC_Exception
	 *
	 * @since2.0.0
	 * @var WCV_SC_Exception
	 */
	protected $exception;

	/**
	 * Instance of WCV_SC_Vendor_Dashboard
	 *
	 * @since2.0.0
	 * @var WCV_SC_Vendor_Dashboard
	 */
	protected $vendor_dashboard;

	/**
	 * Instance of WCV_SC_Payment_Tokens
	 *
	 * @since2.0.0
	 * @var WCV_SC_Payment_Tokens
	 */
	protected $payment_tokens;

	/**
	 * Instance of WCV_SC_Refund
	 *
	 * @since2.0.0
	 * @var WCV_SC_Refund
	 */
	protected $refund;

	/**
	 * Instance of WCV_SC_Intent_Controller
	 *
	 * @since2.0.0
	 * @var WCV_SC_Intent_Controller
	 */
	protected $intent_controller;

	/**
	 * Instance of WCV_SC_Users
	 *
	 * @since2.0.0
	 * @var WCV_SC_Users
	 */
	protected $users;

	/**
	 * Instance of WCV_SC_Ajax
	 *
	 * @var object
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	protected $ajax;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since   2.0.0
	 * @return  WC_Vendors_Stripe_Connect_Gateway A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin.
	 *
	 * @since  2.0.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  2.0.0
	 */
	public function plugin_classes() {
		$this->woocommerce_myaccount = new WCV_SC_WooCommerce_Myaccount( $this );
		$this->logger                = new WCV_SC_Logger( $this );
		$this->helper                = new WCV_SC_Helper( $this );
		$this->vendor_dashboard      = new WCV_SC_Vendor_Dashboard( $this );
		$this->payment_tokens        = new WCV_SC_Payment_Tokens( $this );
		$this->refund                = new WCV_SC_Refund( $this );
		$this->intent_controller     = new WCV_SC_Intent_Controller( $this );
		$this->users                 = new WCV_SC_Users( $this );
		$this->ajax                  = new WCV_SC_Ajax();
	} // END OF PLUGIN CLASSES FUNCTION

	/**
	 * Add hooks and filters.
	 * Priority needs to be
	 * < 10 for CPT_Core,
	 * < 5 for Taxonomy_Core,
	 * and 0 for Widgets because widgets_init runs at init priority 1.
	 *
	 * @since  2.0.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_stripe_gateway' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_filter( 'woocommerce_debug_tools', array( $this, 'debug_tools' ) );
	}

	/**
	 * Activate the plugin.
	 *
	 * @since  2.0.0
	 */
	public function _activate() {
		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		WCV_SC_Ajax::add_endpoint();

		// Make sure any rewrite functionality has been loaded.
		flush_rewrite_rules();
	}

	/**
	 * Deactivate the plugin.
	 * Uninstall routines should be in uninstall.php.
	 *
	 * @since  2.0.0
	 */
	public function _deactivate() {
		// Add deactivation cleanup functionality here.
	}

	/**
	 * Init hooks
	 *
	 * @since   2.0.0
	 * @version 2.1.0
	 */
	public function init() {

		// Bail early if requirements aren't met.
		if ( ! $this->check_requirements() ) {
			return;
		}

		require_once dirname( __FILE__ ) . '/includes/class-order-handler.php';

		// Load translated strings for plugin.
		load_plugin_textdomain( 'wc-vendors-gateway-stripe-connect', false, dirname( $this->basename ) . '/languages/' );

		// Initialize plugin classes.
		$this->plugin_classes();

		$this->set_app_info();
	}

	/**
	 * Add the gateway to WooCommerce
	 *
	 * @version 2.0.7
	 */
	public function add_stripe_gateway( $methods ) {
		if ( class_exists( 'WC_Subscriptions_Order' ) && function_exists( 'wcs_create_renewal_order' ) ) {
			$methods[] = 'WCV_SC_Gateway_Stripe_Connect_Subs';
		} else {
			$methods[] = 'WCV_SC_Gateway_Stripe_Connect';
		}
		
		if ( class_exists( 'WCV_SC_Gateway_Stripe_Bancontact' ) ) {
			$methods[] = 'WCV_SC_Gateway_Stripe_Bancontact';
		}
		return $methods;
	}

	/**
	 * Adds plugin action links.
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="admin.php?page=wc-settings&tab=checkout&section=stripe-connect">' . esc_html__( 'Settings', 'wc-vendors-gateway-stripe-connect' ) . '</a>',
			'<a href="https://docs.wcvendors.com/document/stripe/">' . esc_html__( 'Docs', 'wc-vendors-gateway-stripe-connect' ) . '</a>',
			'<a href="https://www.wcvendors.com/submit-ticket/">' . esc_html__( 'Support', 'wc-vendors-gateway-stripe-connect' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  2.0.0
	 *
	 * @return boolean True if requirements met, false if not.
	 */
	public function check_requirements() {

		// Bail early if plugin meets requirements.
		if ( $this->meets_requirements() ) {
			return true;
		}

		// Add a dashboard notice.
		add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

		// Deactivate our plugin.
		add_action( 'admin_init', array( $this, 'deactivate_me' ) );

		// Didn't meet the requirements.
		return false;
	}

	/**
	 * Deactivates this plugin, hook this function on admin_init.
	 *
	 * @since  2.0.0
	 */
	public function deactivate_me() {

		// We do a check for deactivate_plugins before calling it, to protect
		// any developers from accidentally calling it too early and breaking things.
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Check that all plugin requirements are met.
	 *
	 * @since  2.0.0
	 *
	 * @return boolean True if requirements are met.
	 */
	public function meets_requirements() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->activation_errors = __( 'WooCommerce is required to be installed and activated.', 'wc-vendors-gateway-stripe-connect' );
			return false;
		}

		if ( ! class_exists( 'WC_Vendors' ) ) {
			$this->activation_errors = __( 'WC Vendors Marketplace is required to be installed and activated.', 'wc-vendors-gateway-stripe-connect' );
			return false;
		}

		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met.
	 *
	 * @since  2.0.0
	 */
	public function requirements_not_met_notice() {

		// Compile default message.
		$default_message = sprintf( __( 'WC Vendors - Stripe Commissions & Gateway is missing requirements and has been <a href="%s">deactivated</a>. Please make sure all requirements are available.', 'wc-vendors-gateway-stripe-connect' ), admin_url( 'plugins.php' ) );

		// Default details to null.
		$details = null;

		// Add details if any exist.
		if ( $this->activation_errors && is_array( $this->activation_errors ) ) {
			$details = '<small>' . implode( '</small><br /><small>', $this->activation_errors ) . '</small>';
		}

		// Output errors.
		?>
		<div id="message" class="error">
			<p><?php echo wp_kses_post( $default_message ); ?></p>
			<?php echo wp_kses_post( $details ); ?>
		</div>
		<?php
	}

	public function debug_tools( $tools ) {
		$tools['clear-stripe-connect-cache'] = array(
			'name'     => __( 'Clear Stripe Connect cache', 'wc-vendors-gateway-stripe-connect' ),
			'desc'     => __( 'This will delete Stripe Account country cache.', 'wc-vendors-gateway-stripe-connect' ),
			'button'   => __( 'Clear cache', 'wc-vendors-gateway-stripe-connect' ),
			'callback' => array( $this, 'clear_cache_action' ),
		);
		return $tools;
	}

	public function clear_cache_action() {
		delete_transient( 'wcv_stripe_account_country' );
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  2.0.0
	 *
	 * @param  string $field Field to get.
	 * @throws Exception     Throws an exception if the field is invalid.
	 * @return mixed         Value of the field.
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
			case 'woocommerce_myaccount':
			case 'logger':
			case 'helper':
			case 'vendor_dashboard':
			case 'payment_tokens':
			case 'refund':
			case 'intent_controller':
			case 'users':
				return $this->$field;
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}

	/**
	 * Logging method.
	 *
	 * @param string $message
	 */
	public function log( $message ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = new WC_Logger();
			}
			self::$log->add( 'wc-vendors-gateway-stripe-connect', $message );
		}
	} // log()

	/**
	 * Set Stripe App Info.
	 */
	public function set_app_info() {
		\Stripe\Stripe::setAppInfo(
			'WC Vendors Stripe Connect',
			WCV_SC_VERSION,
			'https://www.wcvendors.com/',
			'pp_partner_FqQaPHQhWLktTM'
		);
	}
}

/**
 * Include the update and support system
 */
if ( ! class_exists( 'WC_Software_License_Client' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/lib/class-wc-software-license-client.php';
}
function wcv_sc_license() {
	return WC_Software_License_Client::get_instance(
		'https://wcvendors.com/',
		'https://www.wcvendors.com/product/stripe-commissions-gateway',
		WCV_SC_VERSION,
		'wc-vendors-gateway-stripe-connect',
		__FILE__,
		'Stripe Commissions & Gateway',
		'wc-vendors-gateway-stripe-connect'
	);
} // wcv_sc_license()
wcv_sc_license();

/**
 * Grab the WC_Vendors_Stripe_Connect_Gateway object and return it.
 * Wrapper for WC_Vendors_Stripe_Connect_Gateway::get_instance().
 *
 * @since  2.0.0
 * @return WC_Vendors_Stripe_Connect_Gateway  Singleton instance of plugin class.
 */
function wc_vendors_gateway_stripe_connect() {
	return WC_Vendors_Stripe_Connect_Gateway::get_instance();
}

function wcv_sc_load_plugin_or_display_notice() {
	if ( class_exists( 'WC_Gateway_Stripe_Connect' ) ) {
		add_action( 'admin_notices', 'wcv_sc_admin_notices_v1' );
	} else {
		wc_vendors_gateway_stripe_connect()->hooks();
	}
}

function wcv_sc_admin_notices_v1() {
	?>
	<div class="notice notice-error">
		<p><?php _e( 'Old version found! Please remove WC Vendors - Stripe Commissions & Gateway to use the new version.', 'wc-vendors-gateway-stripe-connect' ); ?></p>
	</div>
	<?php
}

// Kick it off.
add_action( 'plugins_loaded', 'wcv_sc_load_plugin_or_display_notice' );

// Activation and deactivation.
register_activation_hook( __FILE__, array( wc_vendors_gateway_stripe_connect(), '_activate' ) );
register_deactivation_hook( __FILE__, array( wc_vendors_gateway_stripe_connect(), '_deactivate' ) );
