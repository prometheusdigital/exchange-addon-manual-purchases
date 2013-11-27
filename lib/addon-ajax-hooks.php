<?php

/**
 * Ajax called to calculate the current Total of the producats manually selected.
 *
 * @since 1.0.0
*/
function it_exchange_manual_purchases_add_payment_page_add_product() {
	$price = 0;
	if ( !empty( $_POST['product_ids'] ) ) {
		$product_ids = $_POST['product_ids'];
		foreach( $product_ids as $product_id ) {
			$pid = $product_id['value'];
			$product = it_exchange_get_product( $pid );
			if ( it_exchange_product_supports_feature( $pid, 'base-price' )
				&& it_exchange_product_has_feature( $pid, 'base-price' ) ) {
				$price += it_exchange_get_product_feature( $pid, 'base-price' );
			}
		}
	}
	die( it_exchange_format_price( $price ) );
}
add_action( 'wp_ajax_it-exchange-manual-purchases-addon-add-payment-page-select-product', 'it_exchange_manual_purchases_add_payment_page_add_product' );

/**
 * Ajax called to format the total price box
 *
 * @since 1.0.0
*/
function it_exchange_manual_purchases_format_price() {
    $price = 0;
    if ( isset( $_POST['price'] ) )
        $price = it_exchange_convert_to_database_number( $_POST['price'] );
    
    die( it_exchange_format_price( it_exchange_convert_from_database_number( $price ) ) );
}
add_action( 'wp_ajax_it-exchange-manual-purchases-format-price', 'it_exchange_manual_purchases_format_price' );

/**
 * Ajax called from Thickbox to show the User's Add Product Screen.
 *
 * @since 1.0.0
*/
function it_exchange_manual_purchase_for_user_print_add_products_screen() {
	$default = array(
		'userid'      => empty( $_GET['userid'] ) ? '' : $_GET['userid'],
		'product_ids' => array(),
		'total'       => '',
	);

	if ( !empty( $_POST ) ) {
		$post = array(
			'userid'      => empty( $_POST['userid'] )      ? ''      : $_POST['userid'],
			'product_ids' => empty( $_POST['product_ids'] ) ? array() : $_POST['product_ids'],
			'total'       => empty( $_POST['total'] )       ? ''      : $_POST['total'],
		);
	
		if ( check_admin_referer( 'it-exchange-manual-purchase' ) ) {
		
			if ( !empty( $post['product_ids'] ) ) {
				if ( !empty( $post['userid'] ) ) {
					$user_id = $post['userid'];		
									
					if ( !is_wp_error( $user_id ) ) {
						
						$user = get_user_by( 'id', $user_id  );	
						
						if ( !empty( $user ) ) {
							// Grab default currency
							$settings = it_exchange_get_option( 'settings_general' );
							$currency = $settings['default-currency'];
							$description = array();
	
							foreach ( $post['product_ids'] as $product_id ) {
								if ( ! $product = it_exchange_get_product( $product_id ) ) {
									$error_message = sprintf( __( 'No Product Found - Product ID: %s', 'LION' ), $product_id );
									continue;
								}
									
								$itemized_data = apply_filters( 'it_exchange_add_itemized_data_to_cart_product', array(), $product_id );
								if ( ! is_serialized( $itemized_data ) )
									$itemized_data = maybe_serialize( $itemized_data );
								$key = md5( $itemized_data );
								
								$products[$key]['product_base_price'] = it_exchange_get_product_feature( $product_id, 'base-price' );
								$products[$key]['product_subtotal'] = $products[$key]['product_base_price']; //need to add count
								$products[$key]['product_name'] = get_the_title( $product_id );
								$products[$key]['product_id'] = $product_id;
								$description[] = $products[$key]['product_name'];
							}
							
							if ( empty( $error_message ) ) {
								$description = apply_filters( 'it_exchange_get_cart_description', join( ', ', $description ), $description );

								// Package it up and send it to the transaction method add-on
								$total = empty( $post['total'] ) ? 0 : it_exchange_convert_to_database_number( $post['total'] );
								$transaction_object = new stdClass();
								$transaction_object->total                  = number_format( it_exchange_convert_from_database_number( $total ), 2, '.', '' );
								$transaction_object->currency               = $currency;
								$transaction_object->description            = $description;
								$transaction_object->products               = $products;
								//$transaction_object->coupons                = it_exchange_get_applied_coupons();
								//$transaction_object->coupons_total_discount = it_exchange_get_total_coupons_discount( 'cart', array( 'format_price' => false ));
							
								// Tack on Shipping and Billing address
								//$transaction_object->shipping_address       = it_exchange_get_cart_shipping_address();
								//$transaction_object->billing_address        = apply_filters( 'it_exchange_billing_address_purchase_requirement_enabled', false ) ? it_exchange_get_cart_billing_address() : false;
							
								// Shipping Method and total
								//$transaction_object->shipping_method        = it_exchange_get_cart_shipping_method();
								//$transaction_object->shipping_method_multi  = it_exchange_get_cart_data( 'multiple-shipping-methods' );
								//$transaction_object->shipping_total         = it_exchange_convert_to_database_number( it_exchange_get_cart_shipping_cost( false, false ) );
	
								$payment_id = it_exchange_manual_purchases_addon_process_transaction( $user_id, $transaction_object );

								$url = add_query_arg( array( 'action' => 'edit', 'post' => $payment_id ), admin_url( 'post.php' ) );
								$status_message = sprintf( __( 'Successfully added Manual Purchase. <a href="%s">View Transaction</a>', 'LION' ), $url );
							}
						} else {
							$error_message = __( 'No user found.', 'LION' );
						}
					} else {
						$error_message = $user_id->get_error_message();
					}
				
				} else {
					$error_message = __( 'You must select an existing user to use this page.', 'LION' );
				}
				
			} else {
				$error_message = __( 'You must select products to create a manual purchase.', 'LION' );
			}
		} else {
			$error_message = __( 'Error validating security token. Please try again.', 'LION' );
		}
		
		if ( ! empty ( $status_message ) )
			ITUtility::show_status_message( $status_message );
			
		if ( ! empty( $error_message ) ) {
			ITUtility::show_error_message( $error_message );
			$default = wp_parse_args( $post, $default );
		}	
	}
	?>
	<div class="wrap">
		<?php
		screen_icon( 'it-exchange-manual-purchases' );
		?>
		<h2><?php _e( 'Add Manual Purchase', 'LION' ); ?></h2>
		<form id="manual-purchase-add-payment" name="manual-purchase-add-payment" method="post">
		<div class="it-exchange-add-manual-purchase">
			<input id="it-exchange-manual-purchase-existing-userid" type="hidden" value="<?php echo $default['userid']; ?>" name="userid" />
			<div class="it-exchange-add-manual-purchase-product-options">
				<h3><?php _e( 'Select Products', 'LION' ); ?></h3>
				<?php
				$args = array(
					'product_type' => !empty( $_GET['product_type'] ) ? $_GET['product_type'] : '',
				);
				$products = it_exchange_get_products( $args );
				
				foreach( $products as $product ) {
					$checked = checked( in_array( $product->ID, $default['product_ids'] ), true, false );
					echo '<p>';
					echo '<input id="it-exchange-add-purchase-product-' . $product->ID . '" class="it-exchange-add-pruchase-product" type="checkbox" value="' . $product->ID . '" name="product_ids[]" ' . $checked . '/>';
					echo '<label for="it-exchange-add-purchase-product-' . $product->ID . '" >' . $product->post_title . '</label>';
					echo '</p>';
				}
				?>
				<label for="it-exchange-add-manual-purchase-total-paid"><?Php _e( 'Total Paid', 'LION' ); ?></label><input id="it-exchange-add-manual-purchase-total-paid" type="text" value="<?php echo $default['total']; ?>" name="total" />
				<div class="field">
					<?php
					submit_button( 'Cancel', 'large', 'cancel' );
					submit_button( 'Submit', 'primary large' );
					wp_nonce_field( 'it-exchange-manual-purchase' );
					?>
				</div>
			</div>
		</div>
		</form>
	</div>
	<?php
	die();
}
add_action( 'wp_ajax_it-exchange-add-manual-purchase-for-user', 'it_exchange_manual_purchase_for_user_print_add_products_screen' );