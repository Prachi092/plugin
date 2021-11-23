<?php
/**
 * The template for displaying the order details
 *
 * Override this template by copying it to yourtheme/wc-vendors/dashboard/order
 *
 * @package WC_Vendors_Stripe_Connect_Gateway
 * @version 2.0.0
 */

$payment_gateway = wc_get_payment_gateway_by_order( $order );

if ( wc_tax_enabled() && wc_string_to_bool( get_option( 'wcvendors_vendor_give_taxes', 'no' ) ) ) {
	$order_taxes      = $order->get_taxes();
	$tax_classes      = WC_Tax::get_tax_classes();
	$classes_options  = wc_get_product_tax_class_options();
	$show_tax_columns = count( $order_taxes ) === 1;
}
?>

<div class="wcv-shade wcv-fade">

	<div id="order-refund-modal-<?php echo $order->get_id(); ?>" class="wcv-refund-modal wcv-modal wcv-fade"
		 data-trigger="#open-order-refund-modal-<?php echo $order->get_id(); ?>" data-width="80%" data-height="90%"
		 data-reveal aria-labelledby="modalTitle-<?php echo $order->get_id(); ?>" aria-hidden="true" role="dialog">

		<div class="modal-header">
			<button class="modal-close wcv-dismiss">
				<svg class="wcv-icon wcv-icon-sm">
					<use xlink:href="<?php echo WCV_PRO_PUBLIC_ASSETS_URL; ?>svg/wcv-icons.svg#wcv-icon-times"></use>
				</svg>
			</button>
			<h3 id="modal-title"><?php echo sprintf( __( 'Refund Order #%d', 'wc-vendors-gateway-stripe-connect' ), $order->get_order_number() ); ?>
				- <?php echo $order->get_date_created()->date_i18n( wc_date_format() ); ?></h3>
		</div>

		<div class="modal-body wcv-order-details" id="modalContent">

		<form data-parsley-validate data-parsley-trigger="focusin focusout" class="wcvendors-refund refund-order-<?php echo esc_attr( $order->get_id() ); ?>" id="wcvendors-refund-order-<?php echo esc_attr( $order->get_id() ); ?>" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">

				<?php do_action( 'wcvendors_order_before_items_detail' ); ?>

				<div class=" wcv-order-items-details wcv-cols-group wcv-horizontal-gutters">

					<div class="all-100">

						<h4><?php _e( 'Order Items', 'wc-vendors-gateway-stripe-connect' ); ?></h4>

						<table cellpadding="0" cellspacing="0" class="wcv-table wcv-order-table">

							<thead>
								<tr>
									<th class="item" colspan="2"><?php _e( 'Item', 'wc-vendors-gateway-stripe-connect' ); ?></th>
									<th class="item_cost"><?php _e( 'Cost', 'wc-vendors-gateway-stripe-connect' ); ?></th>
									<th class="quantity"><?php _e( 'Qty', 'wc-vendors-gateway-stripe-connect' ); ?></th>
									<th class="line_cost"><?php _e( 'Total', 'wc-vendors-gateway-stripe-connect' ); ?></th>
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

					<?php if ( 0 < $order->get_total() - $order->get_total_refunded() || 0 < absint( $order->get_item_count() - $order->get_item_count_refunded() ) ) : ?>
						<table class="wc-order-totals">
							<?php if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) : ?>
								<tr>
									<td class="label"><label for="restock_refunded_items"><?php esc_html_e( 'Restock refunded items', 'wc-vendors-gateway-stripe-connect' ); ?>:</label></td>
									<td class="total"><input type="checkbox" id="restock_refunded_items" name="restock_refunded_items" <?php checked( apply_filters( 'woocommerce_restock_refunded_items', true ) ); ?> /></td>
								</tr>
							<?php endif; ?>
							<tr>
								<td class="label"><?php esc_html_e( 'Amount already refunded', 'wc-vendors-gateway-stripe-connect' ); ?>:</td>
								<td class="total">-<?php echo wc_price( $already_refunded, array( 'currency' => $order->get_currency() ) ); // WPCS: XSS ok. ?></td>
							</tr>
							<tr>
								<td class="label"><?php esc_html_e( 'Total available to refund', 'wc-vendors-gateway-stripe-connect' ); ?>:</td>
								<td class="total"><?php echo wc_price( $available_to_refund, array( 'currency' => $order->get_currency() ) ); // WPCS: XSS ok. ?></td>
							</tr>
							<tr>
								<td class="label">
									<label for="refund_amount">
										<?php echo wc_help_tip( __( 'Refund the line items above. This will show the total amount to be refunded', 'wc-vendors-gateway-stripe-connect' ) ); ?>
										<?php esc_html_e( 'Refund amount', 'wc-vendors-gateway-stripe-connect' ); ?>:
									</label>
								</td>
								<td class="total">
								<input type="text" id="refund_amount" name="refund_amount" class="wc_input_price" data-max="<?php echo esc_attr( $available_to_refund ); ?>"
									<?php
									if ( wc_tax_enabled() ) {
										// If taxes are enabled, using this refund amount can cause issues due to taxes not being refunded also.
										// The refunds should be added to the line items, not the order as a whole.
										echo 'readonly';
									}
									?>
									/>
									<div class="clear"></div>
								</td>
							</tr>
							<tr>
								<td class="label">
									<label for="refund_reason">
										<?php echo wc_help_tip( __( 'Note: the refund reason will be visible by the customer.', 'wc-vendors-gateway-stripe-connect' ) ); ?>
										<?php esc_html_e( 'Reason for refund (optional):', 'wc-vendors-gateway-stripe-connect' ); ?>
									</label>
								</td>
								<td class="total">
									<input type="text" id="refund_reason" name="refund_reason" />
									<div class="clear"></div>
								</td>
							</tr>
						</table>
						<div class="clear"></div>
						<div class="refund-actions">
							<?php
							$gateway_name = false !== $payment_gateway ? ( ! empty( $payment_gateway->method_title ) ? $payment_gateway->method_title : $payment_gateway->get_title() ) : __( 'Payment gateway', 'wc-vendors-gateway-stripe-connect' );

							if ( false !== $payment_gateway && $payment_gateway->can_refund_order( $order ) ) {
								/* translators: refund amount, gateway name */
								echo '<button type="button" class="button button-primary do-api-refund">' . sprintf( esc_html__( 'Refund %1$s via %2$s', 'wc-vendors-gateway-stripe-connect' ), wp_kses_post( $refund_amount ), esc_html( $gateway_name ) ) . '</button>';
							}
							?>
							<?php /* translators: refund amount  */ ?>
							<button type="button" class="button cancel-action modal-close wcv-dismiss"><?php esc_html_e( 'Cancel', 'wc-vendors-gateway-stripe-connect' ); ?></button>
							<input type="hidden" id="refunded_amount" name="refunded_amount" value="<?php echo esc_attr( $already_refunded ); ?>" />
							<div class="clear"></div>
						</div>
					<?php endif; ?>

					</div>

				</div>

				<hr/>

				<?php do_action( 'wcvendors_order_after_items_detail' ); ?>

			</form>

		</div><!-- #modalContent -->

	</div>

</div>
