<?php

/**
 * Displays the Manual Purchases - Add Payment screen
 *
 * @since 1.0.0
 * @todo switch out wp_insert_user with it_exchange_create_customer
*/
function it_exchange_manual_purchase_print_add_payment_screen() {
	$default = array(
		'userid'      => empty( $_GET['userid'] ) ? '' : $_GET['userid'],
		'username'    => '',
		'email'       => '',
		'firstname'   => '',
		'lastname'    => '',
		'product_ids' => array(),
		'total'       => '',
	);

	if ( !empty( $_POST ) ) {
		$post = array(
			'userid'      => empty( $_POST['userid'] )      ? ''      : $_POST['userid'],
			'username'    => empty( $_POST['username'] )    ? ''      : $_POST['username'],
			'email'       => empty( $_POST['email'] )       ? ''      : $_POST['email'],
			'firstname'   => empty( $_POST['firstname'] )   ? ''      : $_POST['firstname'],
			'lastname'    => empty( $_POST['lastname'] )    ? ''      : $_POST['lastname'],
			'product_ids' => empty( $_POST['product_ids'] ) ? array() : $_POST['product_ids'],
			'total'       => empty( $_POST['total'] )       ? ''      : $_POST['total'],
		);
	
		if ( check_admin_referer( 'it-exchange-manual-purchase' ) ) {
		
			if ( !empty( $post['product_ids'] ) ) {
				if ( !empty( $post['userid'] ) || ( !empty( $post['username'] ) && !empty( $post['email'] ) ) ) {
					if ( empty( $post['userid'] ) ) {
						$args = array(
							'user_login' => $post['username'],
							'user_email' => $post['email'],
							'user_pass'  => wp_generate_password(),
							'first_name' => $post['firstname'],
							'last_name'  => $post['lastname'],
						);
						$user_id = wp_insert_user( $args );
					} else {
						$user_id = $post['userid'];
					}
						
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
					$error_message = __( 'You must select an existing user or create a new one.', 'LION' );
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
			<div class="it-exchange-add-manual-purchase-user-options">
				<div class="it-exchange-add-manual-purchase-user-option-existing">
				<h3><?php _e( 'Select an Existing Customer', 'LION' ); ?></h3>
					<div class="field field it-exchange-manual-purchase-existing-userid">
					<label for="it-exchange-manual-purchase-existing-username"><?Php _e( 'Username', 'LION' ); ?></label>
					<?php 
						$args = array(
							'fields' => array( 'ID', 'user_login' )
						);
						$users = get_users( $args );
						
						echo '<select id="it-exchange-manual-purchase-existing-userid" name="userid">';
						echo '<option value></option>';
						foreach( $users as $user ) {
							$user->ID = (int) $user->ID;
							$selected = selected( $default['userid'], $user->ID, false );
							echo '<option value="' . $user->ID . '" ' . $selected . '>' . esc_html( $user->user_login ) . '</option>';
						}
						echo '</select>';
					?>
					</div>
				</div>
				<div class="it-exchange-add-manual-purchase-user-option-or">
				<h4><?php _e( 'OR', 'LION' ); ?></h4>
				</div>
				<div class="it-exchange-add-manual-purchase-user-option-add-new">
				<h3><?php _e( 'Add a New Customer', 'LION' ); ?></h3>
					<div class="field">
						<label for="it-exchange-manual-purchase-new-username"><?Php _e( 'Username', 'LION' ); ?></label><input id="it-exchange-manual-purchase-new-username" type="text" value="<?php echo $default['username']; ?>" name="username" />
					</div>
					<div class="field">
						<label for="it-exchange-manual-purchase-new-email"><?php _e( 'Email', 'LION' ); ?></label><input id="it-exchange-manual-purchase-new-email" type="text" value="<?php echo $default['email']; ?>" name="email" />
					</div>
					<div class="field">
						<label for="it-exchange-manual-purchase-new-first-name"><?php _e( 'First Name', 'LION' ); ?></label><input id="it-exchange-manual-purchase-new-first-name" type="text" value="<?php echo $default['firstname']; ?>" name="firstname" />
					</div>
					<div class="field">
						<label for="it-exchange-manual-purchase-new-last-name"><?php _e( 'Last Name', 'LION' ); ?></label><input id="it-exchange-manual-purchase-new-last-name" type="text" value="<?php echo $default['lastname']; ?>" name="lastname" />
					</div>
				</div>
			</div>
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
}

/**
 * This proccesses a Manual Purchase transaction.
 *
 * @since 1.0.0
 *
 * @param string $status passed by WP filter.
 * @param object $transaction_object The transaction object
*/
function it_exchange_manual_purchases_addon_process_transaction( $customer_id, $transaction_object ) {
	$uniqid = it_exchange_manual_purchases_addon_transaction_uniqid();
	
	return it_exchange_add_transaction( 'manual-purchases', $uniqid, 'Completed', $customer_id, $transaction_object );
}

/**
 * This proccesses a Manual Purchase transaction.
 *
 * @since 1.0.0
 *
 * @param string $status passed by WP filter.
 * @param object $transaction_object The transaction object
*/
function it_exchange_manual_purchases_addon_transaction_uniqid() {
	$uniqid = uniqid( '', true );

	if( !it_exchange_manual_purchases_addon_verify_unique_uniqid( $uniqid ) )
		$uniqid = it_exchange_manual_purchases_addon_verify_unique_uniqid();

	return $uniqid;
}

/**
 * Verifies if Unique ID is actually Unique
 *
 * @since 1.0.0
 *
 * @param string $unique id
 * @return boolean true if it is, false otherwise
*/
function it_exchange_manual_purchases_addon_verify_unique_uniqid( $uniqid ) {
	if ( !empty( $uniqid ) ) { //verify we get a valid 32 character md5 hash

		$args = array(
			'post_type' => 'it_exchange_tran',
			'meta_query' => array(
				array(
					'key' => '_it_exchange_transaction_method',
					'value' => 'manual-purchase',
				),
				array(
					'key' => '_it_exchange_transaction_method_id',
					'value' => $uniqid ,
				),
			),
		);

		$query = new WP_Query( $args );

		return ( !empty( $query ) );
	}

	return false;
}