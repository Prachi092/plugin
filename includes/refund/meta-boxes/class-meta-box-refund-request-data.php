<?php
/**
 * WC Vendors - Stripe Commissions & Gateway Meta Box Refund Request Data.
 *
 * @since   2.0.0
 * @package WC_Vendors_Stripe_Commissions_Gateway
 */

/**
 * WC Vendors - Stripe Commissions & Gateway Meta Box Refund Request Data.
 *
 * @since 2.0.0
 */
class WCV_SC_Meta_Box_Refund_Request_Data {

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		global $post;

		$refund_request = new WCV_SC_Refund_Request( $post->ID );

		wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
		?>
		<style type="text/css">
			#post-body-content, #titlediv, #misc-publishing-actions, #minor-publishing-actions, #visibility {
				display: none
			}
		</style>
		<div class="panel-wrap woocommerce">
			<input name="post_title" type="hidden"
				   value="<?php echo empty( $post->post_title ) ? esc_attr( get_post_type_object( $post->post_type )->labels->singular_name ) : esc_attr( $post->post_title ); ?>"/>
			<input name="post_status" type="hidden"
				   value="<?php echo esc_attr( 'wc-' . $refund_request->get_status() ); ?>"/>
			<div id="order_data" class="panel">

				<h2>
					<?php
					// translators: placeholder is the ID of the subscription.
					printf( esc_html_x( 'Refund request #%s', 'edit membership header', 'wc-vendors-gateway-stripe-connect' ), esc_html( $refund_request->get_id() ) );
					?>
				</h2>

				<div class="order_data_column_container">
					<div class="order_data_column">

						<p class="form-field form-field-wide wcv-section-title">
							<strong><?php echo esc_html__( 'Details', 'wc-vendors-gateway-stripe-connect' ); ?></strong>
						</p>

						<p class="form-field form-field-wide wcv-request-date-created">
							<?php
							echo esc_html__( 'Date created:', 'wc-vendors-gateway-stripe-connect' );
							echo '&nbsp;';
							echo $refund_request->get_date_created()->date( wc_date_format() );
							?>
						</p>

						<p class="form-field form-field-wide wcv-request-parent-order">
							<?php
							echo esc_html__( 'Parent order: ', 'wc-vendors-gateway-stripe-connect' );
							echo '&nbsp;';
							printf(
								'<a href="%s">#%s</a>',
								get_edit_post_link( $refund_request->get_parent_id() ),
								esc_html( $refund_request->get_parent_id() )
							);
							?>
						</p>

						<p class="form-field form-field-wide wcv-request-vendor">
							<?php
							echo esc_html__( 'Requested by:', 'wc-vendors-gateway-stripe-connect' );
							echo '&nbsp;';
							if ( $refund_request->get_vendor_id() ) {
								$user = get_user_by( 'id', $refund_request->get_vendor_id() );
								printf(
									'<a href="%s">%s</a>',
									esc_url( add_query_arg( 'user_id', $user->ID, admin_url( 'user-edit.php' ) ) ),
									esc_html( $user->display_name )
								);
							}
							?>
						</p>

						<p class="form-field form-field-wide wcv-request-reason">
							<?php
							echo esc_html__( 'Reason:', 'wc-vendors-gateway-stripe-connect' );
							echo '&nbsp;';
							printf(
								'<span>%s</span>',
								$refund_request->get_reason()
							);
							?>
						</p>

					</div>

					<div class="order_data_column">

						<p class="form-field form-field-wide wcv-section-title">
							<strong><?php echo esc_html__( 'Actions', 'wc-vendors-gateway-stripe-connect' ); ?></strong>
						</p>

						<p class="form-field form-field-wide wcv-request-status">
							<?php
							echo esc_html__( 'Status:', 'wc-vendors-gateway-stripe-connect' );
							echo '&nbsp;';
							printf(
								'<span>%s</span>',
								WCV_SC_Refund_Helpers::get_refund_request_status_name( $refund_request->get_status() )
							);
							?>
						</p>

						<?php if ( 'rf-in-review' == $refund_request->get_status() ) : ?>
							<?php
							$action_url = add_query_arg(
								array(
									'post'      => $post->ID,
									'_wpnonce'  => wp_create_nonce( 'bulk-posts' ),
									'post_type' => 'wcv_refund_request',
								),
								admin_url( 'edit.php' )
							);
							?>
						<p class="form-field form-field-wide wcv-request-actions">
							<a href="<?php printf( '%s&amp;action=rf-approved', $action_url ); ?>" class="button button-primary approve"><?php echo esc_html__( 'Approve', 'wc-vendors-gateway-stripe-connect' ); ?></a>
							<a href="<?php printf( '%s&amp;action=rf-rejected', $action_url ); ?>" class="button reject"><?php echo esc_html__( 'Reject', 'wc-vendors-gateway-stripe-connect' ); ?></a>
						</p>
						<?php endif; ?>

						<p class="form-field form-field-wide wcv-request-delete">
							<?php if ( 'rf-in-review' == $refund_request->get_status() ) : ?>
								<span><?php echo esc_html__( 'Or', 'wc-vendors-gateway-stripe-connect' ); ?></span>
							<?php endif; ?>
							<a href="<?php echo get_delete_post_link( $post->ID ); ?>" class="delete"><?php echo esc_html__( 'Move to Trash', 'wc-vendors-gateway-stripe-connect' ); ?></a>
						</p>

					</div>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}
}
