<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Logger.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 *
 * Based on class WC_Stripe_Exception from WooCommerce Stripe plugin - https://wordpress.org/plugins/woocommerce-gateway-stripe/
 */
class WCV_SC_Logger {
	public static $logger;
	const WC_LOG_FILENAME = 'wc-vendors-gateway-stripe-connect';

	/**
	 * Utilize WC logger class
	 *
	 * @since 2.0.0
	 * @version 2.0.0
	 */
	public static function log( $message, $start_time = null, $end_time = null ) {
		if ( ! class_exists( 'WC_Logger' ) ) {
			return;
		}

		if ( apply_filters( 'wcv_wc_logging', true, $message ) ) {
			if ( empty( self::$logger ) ) {
				self::$logger = wc_get_logger();
			}

			$settings = get_option( 'woocommerce_stripe-connect_settings' );

			if ( empty( $settings ) || ! isset( $settings['enable_logging'] ) || 'yes' !== $settings['enable_logging'] ) {
				return;
			}

			if ( ! is_null( $start_time ) ) {

				$formatted_start_time = date_i18n( get_option( 'date_format' ) . ' g:ia', $start_time );
				$end_time             = is_null( $end_time ) ? current_time( 'timestamp' ) : $end_time;
				$formatted_end_time   = date_i18n( get_option( 'date_format' ) . ' g:ia', $end_time );
				$elapsed_time         = round( abs( $end_time - $start_time ) / 60, 2 );

				$log_entry  = "\n" . '====Stripe Version: ' . WCV_SC_VERSION . '====' . "\n";
				$log_entry .= '====Start Log ' . $formatted_start_time . '====' . "\n" . $message . "\n";
				$log_entry .= '====End Log ' . $formatted_end_time . ' (' . $elapsed_time . ')====' . "\n\n";

			} else {
				$log_entry  = "\n" . '====Stripe Version: ' . WCV_SC_VERSION . '====' . "\n";
				$log_entry .= '====Start Log====' . "\n" . $message . "\n" . '====End Log====' . "\n\n";

			}

			self::$logger->debug( $log_entry, array( 'source' => self::WC_LOG_FILENAME ) );
		}
	}
}
