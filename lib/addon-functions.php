<?php

/**
 * Displays the Manual Purchases - Add Payment screen
 *
 * @since 1.0.0
 * @todo switch out wp_insert_user with it_exchange_create_customer
*/
function it_exchange_manual_purchase_print_add_payment_screen() {
	$error_message = '';
	
	$default = array(
		'user_option'  => 'existing',
		'userid'       => empty( $_GET['userid'] ) ? '' : $_GET['userid'],
		'user_login'   => '',
		'email'        => '',
		'first_name'   => '',
		'last_name'    => '',
		'product_type' => '',
		'search'       => '',
		'product_ids'  => array(),
		'total'        => '',
		'description'  => '',
	);
	
	if ( !empty( $_POST['search_submit'] ) ) {
		$post = array(
			'user_option'  => empty( $_POST['user_option'] )         ? 'existing' : $_POST['user_option'],
			'userid'       => empty( $_POST['userid'] )              ? ''         : $_POST['userid'],
			'user_login'   => empty( $_POST['user_login'] )          ? ''         : $_POST['user_login'],
			'email'        => empty( $_POST['email'] )               ? ''         : $_POST['email'],
			'first_name'   => empty( $_POST['first_name'] )          ? ''         : $_POST['first_name'],
			'last_name'    => empty( $_POST['last_name'] )           ? ''         : $_POST['last_name'],
			'product_type' => empty( $_POST['product_type_filter'] ) ? ''         : $_POST['product_type_filter'],
			'search'       => empty( $_POST['search'] )              ? ''         : $_POST['search'],
			'product_ids'  => empty( $_POST['product_ids'] )         ? array()    : $_POST['product_ids'],
			'total'        => empty( $_POST['total'] )               ? ''         : $_POST['total'],
			'description'  => empty( $_POST['description'] )         ? ''         : $_POST['description'],
		);
		$default = wp_parse_args( $post, $default );
	} else if ( !empty( $_POST['submit'] ) ) {
		$post = array(
			'user_option'  => empty( $_POST['user_option'] )         ? 'existing' : $_POST['user_option'],
			'userid'       => empty( $_POST['userid'] )              ? ''         : $_POST['userid'],
			'user_login'   => empty( $_POST['user_login'] )          ? ''         : $_POST['user_login'],
			'email'        => empty( $_POST['email'] )               ? ''         : $_POST['email'],
			'first_name'   => empty( $_POST['first_name'] )          ? ''         : $_POST['first_name'],
			'last_name'    => empty( $_POST['last_name'] )           ? ''         : $_POST['last_name'],
			'product_type' => empty( $_POST['product_type_filter'] ) ? ''         : $_POST['product_type_filter'],
			'search'       => empty( $_POST['search'] )              ? ''         : $_POST['search'],
			'product_ids'  => empty( $_POST['product_ids'] )         ? array()    : $_POST['product_ids'],
			'total'        => empty( $_POST['total'] )               ? ''         : $_POST['total'],
			'description'  => empty( $_POST['description'] )         ? ''         : $_POST['description'],
		);
	
		if ( check_admin_referer( 'it-exchange-manual-purchase-add-payment', 'it-exchange-manual-purchase-add-payment-nonce' ) ) {
		
			if ( !empty( $post['product_ids'] ) ) {
				if ( !empty( $post['userid'] ) || ( !empty( $post['user_login'] ) && !empty( $post['email'] ) ) ) {
					if ( empty( $post['userid'] ) ) {
						if ( empty( $_POST['pass1'] ) && empty( $_POST['pass2'] ) ) {
							$_POST['pass1'] = wp_generate_password();
							$_POST['pass2'] = $_POST['pass1'];
							$_POST['send_password'] = true;
						}
						$user_id = it_exchange_register_user();
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
								if ( !empty( $product_id ) ) {
									if ( ! $product = it_exchange_get_product( $product_id ) ) {
										$error_message = sprintf( __( 'No Product Found - Product ID: %s', 'LION' ), $product_id );
										continue;
									}
										
									$itemized_data = apply_filters( 'it_exchange_add_itemized_data_to_cart_product', array(), $product_id );
									if ( ! is_serialized( $itemized_data ) )
										$itemized_data = maybe_serialize( $itemized_data );
									$key = $product_id . '-' . md5( $itemized_data );
									
									$products[$key]['product_base_price'] = it_exchange_get_product_feature( $product_id, 'base-price' );
									$products[$key]['product_subtotal'] = $products[$key]['product_base_price']; //need to add count
									$products[$key]['product_name'] = get_the_title( $product_id );
									$products[$key]['product_id'] = $product_id;
									$products[$key]['count'] = 1;
									$description[] = $products[$key]['product_name'];
								}
							}
							
							if ( empty( $error_message ) ) {
								$description = apply_filters( 'it_exchange_get_cart_description', join( ', ', $description ), $description );

								// Package it up and send it to the transaction method add-on
								$total = empty( $post['total'] ) ? 0 : it_exchange_convert_to_database_number( $post['total'] );
								$transaction_object = new stdClass();
								$transaction_object->total                  = number_format( it_exchange_convert_from_database_number( $total ), 2, '.', '' );
								$transaction_object->currency               = $currency;
								$transaction_object->description            = implode( ',', $description );
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
	
								$transaction_id = it_exchange_manual_purchases_addon_process_transaction( $user_id, $transaction_object );
								if ( !empty( $post['description'] ) )
									update_post_meta( $transaction_id, '_it_exchange_transaction_manual_purchase_description', $post['description'] );

								$transaction_url = add_query_arg( array( 'action' => 'edit', 'post' => $transaction_id ), admin_url( 'post.php' ) );
								$customer_data_url = add_query_arg( array( 'user_id' => $user_id, 'it_exchange_customer_data' => 1 ), admin_url( 'user-edit.php' ) );
								$status_message = sprintf( __( 'Successfully added Manual Purchase. <a href="%s">View Transaction</a> | <a href="%s">View Customer Data</a>', 'LION' ), $transaction_url, $customer_data_url );
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
		<form id="it-exchange-manual-purchase-add-payment" name="it-exchange-manual-purchase-add-payment" method="post">
		<div class="it-exchange-add-manual-purchase">
			<div class="it-exchange-add-manual-purchase-user-options">
				<div class="invoice-field-container invoice-field-container-client-type">
					<h3><?php _e( 'New or Existing Customer', 'LION' ); ?></h3>
					<label for="it-exchange-user-option-existing"><input type="radio" id="it-exchange-user-option-existing" class="it-exchange-user-option" name="user_option" value="existing" <?php checked( 'existing', $default['user_option'] ); ?> />&nbsp;<?php _e( 'Existing Customer', 'LION' ); ?></label>
					<label for="it-exchange-user-option-new"><input type="radio" id="it-exchange-user-option-new" class="it-exchange-user-option" name="user_option" value="new" <?php checked( 'new', $default['user_option'] ); ?> />&nbsp;<?php _e( 'New Customer', 'LION' ); ?></label>
				</div>
				<?php
				if ( 'existing' === $default['user_option'] ) {
					$existing_hidden = '';
					$new_hidden = 'hidden';
				} else {
					$existing_hidden = 'hidden';
					$new_hidden = '';
				}
				?>
				<div class="it-exchange-add-manual-purchase-user-option-existing <?php echo $existing_hidden; ?>">
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
				<div class="it-exchange-add-manual-purchase-user-option-add-new <?php echo $new_hidden; ?>">
					<div class="field">
						<label for="it-exchange-manual-purchase-new-username"><?Php _e( 'Username', 'LION' ); ?></label><input id="it-exchange-manual-purchase-new-username" type="text" value="<?php echo $default['user_login']; ?>" name="user_login" />
					</div>
					<div class="field">
						<label for="it-exchange-manual-purchase-new-email"><?php _e( 'Email', 'LION' ); ?></label><input id="it-exchange-manual-purchase-new-email" type="text" value="<?php echo $default['email']; ?>" name="email" />
					</div>
					<div class="field">
						<label for="it-exchange-manual-purchase-new-first-name"><?php _e( 'First Name', 'LION' ); ?></label><input id="it-exchange-manual-purchase-new-first-name" type="text" value="<?php echo $default['first_name']; ?>" name="first_name" />
					</div>
					<div class="field">
						<label for="it-exchange-manual-purchase-new-last-name"><?php _e( 'Last Name', 'LION' ); ?></label><input id="it-exchange-manual-purchase-new-last-name" type="text" value="<?php echo $default['last_name']; ?>" name="last_name" />
					</div>
					<div class="field set-password">
						<a href="#"><?php _e( 'Set password manually.', 'LION' ); ?></a>
					</div>
					<div class="field hidden random-password">
						<a href="#"><?php _e( 'Let WordPress generate a random password.', 'LION' ); ?></a>
					</div>
					<div class="hidden show-password-fields">
					<div class="field">
						<label for="it-exchange-manual-purchase-new-password1"><?php _e( 'Password', 'LION' ); ?></label><input id="it-exchange-manual-purchase-new-password1" type="password" value="" name="pass1" />
					<div class="field">
					</div>
						<label for="it-exchange-manual-purchase-new-password2"><?php _e( 'Repeat Password', 'LION' ); ?></label><input id="it-exchange-manual-purchase-new-password2" type="password" value="" name="pass2" />
					</div>
					</div>
				</div>
			</div>
			<div class="it-exchange-add-manual-purchase-product-options">
				<h3><?php _e( 'Select Products', 'LION' ); ?></h3>
				<div class="it-exchange-add-manual-purchase-product-filter-search clearfix">
					<?php
					$product_types = it_exchange_get_enabled_addons( array( 'category' => 'product-type' ) );
					if ( is_array( $product_types ) && count( $product_types ) > 1 ) {
					?>
					<div id="select-product-type-filter">
						<select id="product-type-filter" name="product_type_filter">
							<option value=""><?php _e( 'View All Product Types', 'LION' ); ?></option>
							<?php
							foreach ( $product_types as $slug => $params ) {
								echo '<option value="' . esc_attr( $slug ) . '" ' . checked( $slug, $default['product_type'], true ) . '>' . esc_attr( $params['name'] ) . '</option>';
							}
							?>
						</select>
						<?php submit_button( __( 'Filter', 'LION' ), 'secondary', 'filter_submit' ); ?>
					</div>
					<?php 
					}
					?>
					<div id="select-product-search">
						<input type="text" name="product-search" id="product-search" value="<?php echo $default['search']; ?>" />
						<?php submit_button( __( 'Search Products', 'LION' ), 'secondary', 'search_submit' ); ?>
					</div>
				</div>
				<?php echo it_exchange_manual_purchases_product_listing( $default ); ?>
				<div class="clear"></div>
				<label for="it-exchange-add-manual-purchase-total-paid"><?Php _e( 'Total Paid', 'LION' ); ?></label><input id="it-exchange-add-manual-purchase-total-paid" type="text" value="<?php echo $default['total']; ?>" name="total" />
				<div id="it-exchange-add-manual-purchase-description-div" class="field">
					<label for="it-exchange-add-manual-purchase-description"><?php _e( 'Purchase Note', 'LION' ); ?></label>
					<textarea id="it-exchange-add-manual-purchase-description" name="description"><?php esc_attr_e( $default['description'] ); ?></textarea>
				</div>
				<div class="field it-exchange-add-manual-purchase-submit">
					<?php
					submit_button( 'Submit', 'primary large' );
					submit_button( 'Cancel', 'large', 'cancel' );
					wp_nonce_field( 'it-exchange-manual-purchase-add-payment', 'it-exchange-manual-purchase-add-payment-nonce' );
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

function it_exchange_manual_purchases_product_listing( $args ) {
	$default = array(
		'product_type' => '',
		'search'       => '',
		'product_ids'  => array(),
	);
	$default = wp_parse_args( $args, $default );

	$args = array(
		'product_type'   => !empty( $default['product_type'] ) ? $default['product_type'] : '',
		'posts_per_page' => -1,
		'show_hidden'    => true,
	);
	
	if ( !empty( $default['search'] ) )
		$args['s'] = $default['search'];
				
	$products = it_exchange_get_products( $args );
	
	$output = '<div id="it-exchange-add-purchase-product-list">';
	foreach( $products as $product ) {
		$img_output = '';
							
		// Give other add-ons the ability to skip specific products
		if ( false === apply_filters( 'it_exchange_manual_purchases_addon_include_product_in_select', true, $product ) )
			continue;

		if ( it_exchange_product_supports_feature( $product->ID, 'product-images' )
				&& it_exchange_product_has_feature( $product->ID, 'product-images' ) ) {

			$product_images = it_exchange_get_product_feature( $product->ID, 'product-images' );
			$img_src = wp_get_attachment_thumb_url( $product_images[0] );

			ob_start();
			?>
			<div class="it-exchange-feature-image-<?php echo $product->ID; ?> it-exchange-featured-image">
				<img alt="" src="<?php echo $img_src ?>" />
			</div>
			<?php
			$img_output = ob_get_clean();

		}
		/*
		<li data-toggle="digital-downloads-product-type-wizard" product-type="digital-downloads-product-type" class="product-option digital-downloads-product-type-product-option selected">
			<div class="option-spacer">
				<img alt="Digital Downloads" src="http://lew.internal.ithemes.com/wp-content/plugins/ithemes-exchange/core-addons/product-types/digital-downloads/images/wizard-downloads.png">
				<span class="product-name">Digital Downloads</span>
			</div>
			<input type="hidden" value="digital-downloads-product-type" name="it-exchange-product-types[]" class="enable-digital-downloads-product-type">
		</li>
		*/
		if ( in_array( $product->ID, $default['product_ids'] ) ) {
			$select = 'it-exchange-add-purchase-product-selected';
			$value = $product->ID;
		} else {
			$select = '';
			$value = '';
		}
		
		$output .= '<div class="it-exchange-add-purchase-product ' . $select .'" data-product-id="' . $product->ID . '">';
		$output .= $img_output;
		$output .= '<span class="product-name">' . apply_filters( 'it_exchange_manual_purchases_addon_selected_product_title', $product->post_title, $product ) . '</span>';
		$output .= '<input id="it-exchange-add-purchase-product-' . $product->ID . '" type="hidden" value="' . $value . '" name="product_ids[]" />';
		$output .= '</div>';
	}
	$output .= '</div>';
	
	return $output;
}
