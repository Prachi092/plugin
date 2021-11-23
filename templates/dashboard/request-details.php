<?php
/**
 * The template for displaying the request details
 *
 * @package     WCVendors_WC_Subscriptions
 * @subpackage  templates/dashboard
 * @version     2.0.0
 * @since       2.0.0
 */

if ( wc_tax_enabled() && wc_string_to_bool( get_option( 'wcvendors_vendor_give_taxes', 'no' ) ) ) {
	$order_taxes      = $order->get_taxes();
	$tax_classes      = WC_Tax::get_tax_classes();
	$classes_options  = wc_get_product_tax_class_options();
	$show_tax_columns = count( $order_taxes ) === 1;
}
?>

<div class="wcv-shade wcv-fade">

	<div id="refund-request-details-modal-<?php echo $order_id; ?>" class="wcv-modal wcv-fade wcv-refund-request-modal"
		 data-trigger="#open-refund-request-details-modal-<?php echo $order_id; ?>" data-width="80%"
		 data-height="90%" data-reveal aria-labelledby="modalTitle-<?php echo $order_id; ?>" aria-hidden="true"
		 role="dialog">

		<div class="modal-header">
			<button class="modal-close wcv-dismiss">
				<svg class="wcv-icon wcv-icon-sm"><use xlink:href="<?php echo WCV_PRO_PUBLIC_ASSETS_URL; ?>svg/wcv-icons.svg#wcv-icon-times"></use></svg>
			</button>
			<h3 id="modal-title"><?php echo sprintf( __( 'Refund request #%d Details', 'wc-vendors-gateway-stripe-connect' ), $order_id ); ?> <?php
			if ( $order ) :
				?>
				- <?php echo date_i18n( wc_date_format(), strtotime( $order->get_date_created() ) ); ?><?php endif; ?></h3>
		</div>

		<div class="modal-body wcv-refund-request-details" id="modalContent">
			<div class="wcv-request-details wcv-cols-group wcv-horizontal-gutters" style="margin-bottom: 2em;">
				<div class="all-100">
					<h4><?php _e( 'Details', 'wc-vendors-gateway-stripe-connect' ); ?></h4>
				</div>
				<div class="all-100 medium-50 large-50 xlarge-50">
					<div class="form-field form-field-wide wcv-request-parent-order">
						<?php
						echo esc_html__( 'Parent order: ', 'wc-vendors-gateway-stripe-connect' );
						echo '&nbsp;';
						printf(
							'<a href="%s">#%s</a>',
							get_edit_post_link( $order->get_parent_id() ),
							esc_html( $order->get_parent_id() )
						);
						?>
					</div>
					<div class="form-field form-field-wide wcv-request-status">
						<?php
						echo esc_html__( 'Status:', 'wc-vendors-gateway-stripe-connect' );
						echo '&nbsp;';
						printf(
							'<span>%s</span>',
							WCV_SC_Refund_Helpers::get_refund_request_status_name( $order->get_status() )
						);
						?>
					</div>
				</div>

				<div class="all-100 medium-50 large-50 xlarge-50">
					<div class="form-field form-field-wide wcv-request-reason">
						<?php
						echo esc_html__( 'Reason:', 'wc-vendors-gateway-stripe-connect' );
						echo '&nbsp;';
						printf(
							'<span>%s</span>',
							$order->get_reason()
						);
						?>
					</div>
				</div>
			</div>

			<div class="wcv-request-items wcv-cols-group wcv-horizontal-gutters">
				<div class="all-100">
					<h4><?php _e( 'Order Items', 'wc-vendors-gateway-stripe-connect' ); ?></h4>

					<table cellpadding="0" cellspacing="0" class="wcv-table wcv-order-table">
						<thead>
						<tr>
							<th style="width: 100px;"></th>
							<th><?php _e( 'Item', 'wc-vendors-gateway-stripe-connect' ); ?></th>
							<th><?php _e( 'Cost', 'wc-vendors-gateway-stripe-connect' ); ?></th>
							<th><?php _e( 'Qty', 'wc-vendors-gateway-stripe-connect' ); ?></th>
							<th><?php _e( 'Total', 'wc-vendors-gateway-stripe-connect' ); ?></th>
							<?php
							if ( ! empty( $order_taxes ) ) :
								foreach ( $order_taxes as $tax_id => $tax_item ) :
									$tax_class      = wc_get_tax_class_by_tax_id( $tax_item['rate_id'] );
									$tax_class_name = isset( $classes_options[ $tax_class ] ) ? $classes_options[ $tax_class ] : __( 'Tax', 'wc-vendors-gateway-stripe-connect' );
									$column_label   = ! empty( $tax_item['label'] ) ? $tax_item['label'] : __( 'Tax', 'wc-vendors-gateway-stripe-connect' );
									/* translators: %1$s: tax item name %2$s: tax class name  */
									$column_tip = sprintf( esc_html__( '%1$s (%2$s)', 'wc-vendors-gateway-stripe-connect' ), $tax_item['name'], $tax_class_name );
									?>
									<th class="line_tax tips" data-tip="<?php echo esc_attr( $column_tip ); ?>">
										<?php echo esc_attr( $column_label ); ?>
										<input type="hidden" class="order-tax-id" name="order_taxes[<?php echo esc_attr( $tax_id ); ?>]" value="<?php echo esc_attr( $tax_item['rate_id'] ); ?>">
										<a class="delete-order-tax" href="#" data-rate_id="<?php echo esc_attr( $tax_id ); ?>"></a>
									</th>
									<?php
								endforeach;
							endif;
							?>
						</tr>
						</thead>
						<tbody class="order_line_items">
							<?php foreach ( $line_items as $item_id => $item ) : ?>
								<?php include 'html-order-item.php'; ?>
							<?php endforeach; ?>
						</tbody>

						<tbody class="order_shipping_line_items">
							<?php foreach ( $shipping as $item_id => $item ) : ?>
								<?php include 'html-order-shipping.php'; ?>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>

			<div class="wc-order-data-row wc-order-totals-items wc-order-items-editable">
				<table class="wc-order-totals">
					<?php if ( 0 < $order->get_total_discount() ) : ?>
						<tr>
							<td class="label"><?php esc_html_e( 'Discount:', 'woocommerce' ); ?></td>
							<td width="1%"></td>
							<td class="total">
								<?php echo wc_price( $order->get_total_discount(), array( 'currency' => $order->get_currency() ) ); // WPCS: XSS ok. ?>
							</td>
						</tr>
					<?php endif; ?>

					<?php do_action( 'wcv_refund_request_admin_order_totals_after_discount', $order->get_id() ); ?>

					<?php if ( $order->get_shipping_methods() ) : ?>
						<tr>
							<td class="label"><?php esc_html_e( 'Shipping:', 'woocommerce' ); ?></td>
							<td width="1%"></td>
							<td class="total">
								<?php
								$refunded = $order->get_total_shipping_refunded();
								if ( $refunded > 0 ) {
									echo '<del>' . wp_strip_all_tags( wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ) ) . '</del> <ins>' . wc_price( $order->get_shipping_total() - $refunded, array( 'currency' => $order->get_currency() ) ) . '</ins>'; // WPCS: XSS ok.
								} else {
									echo wc_price( $order->get_shipping_total(), array( 'currency' => $order->get_currency() ) ); // WPCS: XSS ok.
								}
								?>
							</td>
						</tr>
					<?php endif; ?>

					<?php do_action( 'wcv_refund_request_admin_order_totals_after_shipping', $order->get_id() ); ?>

					<?php if ( wc_tax_enabled() ) : ?>
						<?php foreach ( $order->get_tax_totals() as $code => $tax_total ) : ?>
							<tr>
								<td class="label"><?php echo esc_html( $tax_total->label ); ?>:</td>
								<td width="1%"></td>
								<td class="total">
									<?php
									$refunded = $order->get_total_tax_refunded_by_rate_id( $tax_total->rate_id );
									if ( $refunded > 0 ) {
										echo '<del>' . wp_strip_all_tags( $tax_total->formatted_amount ) . '</del> <ins>' . wc_price( WC_Tax::round( $tax_total->amount, wc_get_price_decimals() ) - WC_Tax::round( $refunded, wc_get_price_decimals() ), array( 'currency' => $order->get_currency() ) ) . '</ins>'; // WPCS: XSS ok.
									} else {
										echo wp_kses_post( $tax_total->formatted_amount );
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>

					<?php do_action( 'wcv_refund_request_admin_order_totals_after_tax', $order->get_id() ); ?>

					<tr>
						<td class="label"><?php esc_html_e( 'Total', 'woocommerce' ); ?>:</td>
						<td width="1%"></td>
						<td class="total">
							<?php echo $order->get_formatted_order_total(); // WPCS: XSS ok. ?>
						</td>
					</tr>

					<?php do_action( 'wcv_refund_request_admin_order_totals_after_total', $order->get_id() ); ?>

					<?php if ( $order->get_total_refunded() ) : ?>
						<tr>
							<td class="label refunded-total"><?php esc_html_e( 'Refunded', 'woocommerce' ); ?>:</td>
							<td width="1%"></td>
							<td class="total refunded-total">-<?php echo wc_price( $order->get_total_refunded(), array( 'currency' => $order->get_currency() ) ); // WPCS: XSS ok. ?></td>
						</tr>
					<?php endif; ?>

					<?php do_action( 'wcv_refund_request_admin_order_totals_after_refunded', $order->get_id() ); ?>

				</table>
				<div class="clear"></div>
				<div class="refund-actions">
				<?php if ( 'rf-in-review' == $order->get_status() ) : ?>
					<button type="button" class="button cancel-request" data-request-id="<?php echo esc_attr( $order->get_id() ); ?>"><?php esc_html_e( 'Cancel this request', 'wc-vendors-gateway-stripe-connect' ); ?></button>
				<?php endif; ?>
				</div>
			</div>

		</div>

	</div>

</div>
