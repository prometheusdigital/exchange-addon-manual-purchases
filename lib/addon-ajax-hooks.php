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
	die( html_entity_decode( it_exchange_format_price( $price ), ENT_QUOTES, 'UTF-8' ) );
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
    
    die( html_entity_decode( it_exchange_format_price( it_exchange_convert_from_database_number( $price ) ), ENT_QUOTES, 'UTF-8' ) );
}
add_action( 'wp_ajax_it-exchange-manual-purchases-format-price', 'it_exchange_manual_purchases_format_price' );

/**
 * Ajax called from Thickbox to show the User's Add Product Screen.
 *
 * @since 1.0.0
*/
function it_exchange_manual_purchase_for_user_print_add_products_screen() {	
	global $title, $hook_suffix, $current_screen, $wp_locale, $pagenow, $wp_version,
	$update_title, $total_update_count, $parent_file, $current_screen;
	
	// Catch plugins that include admin-header.php before admin.php completes.
	if ( empty( $current_screen ) )
		set_current_screen();
		
	$error_message = '';
	$default = array(
		'userid'       => empty( $_GET['userid'] ) ? '' : $_GET['userid'],
		'product_type' => '',
		'search'       => '',
		'product_ids'  => array(),
		'total'        => '',
		'description'  => '',
	);
	
	if ( !empty( $_POST['search_submit'] ) ) {
		$post = array(
			'userid'       => empty( $_POST['userid'] )              ? ''      : $_POST['userid'],
			'product_type' => empty( $_POST['product_type_filter'] ) ? ''      : $_POST['product_type_filter'],
			'search'       => empty( $_POST['search'] )              ? ''      : $_POST['search'],
			'product_ids'  => empty( $_POST['product_ids'] )         ? array() : $_POST['product_ids'],
			'total'        => empty( $_POST['total'] )               ? ''      : $_POST['total'],
			'description'  => empty( $_POST['description'] )         ? ''      : $_POST['description'],
		);
		$default = wp_parse_args( $post, $default );
	} else if ( !empty( $_POST ) ) {
		$post = array(
			'userid'       => empty( $_POST['userid'] )              ? ''      : $_POST['userid'],
			'product_type' => empty( $_POST['product_type_filter'] ) ? ''      : $_POST['product_type_filter'],
			'search'       => empty( $_POST['search'] )              ? ''      : $_POST['search'],
			'product_ids'  => empty( $_POST['product_ids'] )         ? array() : $_POST['product_ids'],
			'total'        => empty( $_POST['total'] )               ? ''      : $_POST['total'],
			'description'  => empty( $_POST['description'] )         ? ''      : $_POST['description'],
		);
	
		if ( check_admin_referer( 'it-exchange-manual-purchase-add-payment', 'it-exchange-manual-purchase-add-payment-nonce' ) ) {
		
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
									$products[$key]['product_id'] = (int) $product_id;
									$products[$key]['count'] = 1;
									$description[] = $products[$key]['product_name'];
								}
							}
							
							if ( empty( $error_message ) ) {
								$description = apply_filters( 'it_exchange_get_cart_description', join( ', ', $description ), $description );

								// Package it up and send it to the transaction method add-on
								$total = empty( $post['total'] ) ? 0 : it_exchange_convert_to_database_number( $post['total'] );
								$transaction_object = new stdClass();
								$transaction_object->cart_id                = it_exchange_create_cart_id();
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
	
								$transaction_id = it_exchange_manual_purchases_addon_process_transaction( $user_id, $transaction_object );
								if ( !empty( $post['description'] ) )
									update_post_meta( $transaction_id, '_it_exchange_transaction_manual_purchase_description', $post['description'] );

								$transaction_url = add_query_arg( array( 'action' => 'edit', 'post' => $transaction_id ), admin_url( 'post.php' ) );
								$customer_data_url = add_query_arg( array( 'user_id' => $user_id, 'it_exchange_customer_data' => 1 ), admin_url( 'user-edit.php' ) );
								$status_message = sprintf( __( 'Successfully added Manual Purchase. <a href="%s" target="_top">View Transaction</a> | <a href="%s" target="_top">View Customer Data</a>', 'LION' ), esc_url( $transaction_url ), esc_url( $customer_data_url ) );
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
	
	wp_user_settings();
	@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));
	_wp_admin_html_begin();
	?>
	<title><?php _e( 'Add Manual Purchase', 'LION' ); ?></title>
	<script type="text/javascript">
		var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	</script>
	<?php
	wp_enqueue_style( 'colors' );
	wp_enqueue_style( 'ie' );
	wp_enqueue_script( 'utils' );
	
	$hook_suffix = 'user.php-ithemes-manual-purchase-thickbox';
	do_action( 'admin_enqueue_scripts', $hook_suffix );
	do_action( "admin_print_styles-$hook_suffix" );
	do_action( 'admin_print_styles' );
	do_action( "admin_print_scripts-$hook_suffix" );
	do_action( 'admin_print_scripts' );
	do_action( "admin_head-$hook_suffix" );
	do_action( 'admin_head' );
	
	$admin_body_class = preg_replace('/[^a-z0-9_-]+/i', '-', $hook_suffix);
	
	if ( get_user_setting('mfold') == 'f' )
		$admin_body_class .= ' folded';
	
	if ( !get_user_setting('unfold') )
		$admin_body_class .= ' auto-fold';
	
	if ( is_rtl() )
		$admin_body_class .= ' rtl';
	
	if ( $current_screen->post_type )
		$admin_body_class .= ' post-type-' . $current_screen->post_type;
	
	if ( $current_screen->taxonomy )
		$admin_body_class .= ' taxonomy-' . $current_screen->taxonomy;
	
	$admin_body_class .= ' branch-' . str_replace( array( '.', ',' ), '-', floatval( $wp_version ) );
	$admin_body_class .= ' version-' . str_replace( '.', '-', preg_replace( '/^([.0-9]+).*/', '$1', $wp_version ) );
	$admin_body_class .= ' admin-color-' . sanitize_html_class( get_user_option( 'admin_color' ), 'fresh' );
	$admin_body_class .= ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );
	
	if ( wp_is_mobile() )
		$admin_body_class .= ' mobile';
	
	if ( is_multisite() )
		$admin_body_class .= ' multisite';
	
	if ( is_network_admin() )
		$admin_body_class .= ' network-admin';
	
	$admin_body_class .= ' no-customize-support no-svg';
	?>
	</head>
	<body class="wp-admin wp-core-ui no-js <?php echo apply_filters( 'admin_body_class', '' ) . " $admin_body_class"; ?>">
		<div class="wrap">
			<?php
			screen_icon( 'it-exchange-manual-purchases' );
			?>
			<h2><?php _e( 'Add Manual Purchase', 'LION' ); ?></h2>
			<form id="it-exchange-manual-purchase-add-payment" name="it-exchange-manual-purchase-add-payment" method="post">
			<div class="it-exchange-add-manual-purchase">
				<input id="it-exchange-manual-purchase-userid" type="hidden" value="<?php echo $default['userid']; ?>" name="userid" />
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
					<div class="clear"></div>
					<?php echo it_exchange_manual_purchases_product_listing( $default ); ?>
					<div class="clear"></div>
					<label for="it-exchange-add-manual-purchase-total-paid"><?Php _e( 'Total Paid', 'LION' ); ?></label><input id="it-exchange-add-manual-purchase-total-paid" type="text" value="<?php echo $default['total']; ?>" name="total" />
					<div id="it-exchange-add-manual-purchase-description-div" class="field">
						<label for="it-exchange-add-manual-purchase-description"><?php _e( 'Purchase Note', 'LION' ); ?></label>
						<textarea id="it-exchange-add-manual-purchase-description" name="description"><?php esc_attr_e( $default['description'] ); ?></textarea>
					</div>
					<div class="field it-exchange-add-manual-purchase-submit">
						<?php submit_button( 'Submit', 'primary large' ); ?>
						<input onclick="self.parent.tb_remove();return false" type="submit" value="Cancel" class="button button-large" id="cancel" name="cancel">
						<?php wp_nonce_field( 'it-exchange-manual-purchase-add-payment', 'it-exchange-manual-purchase-add-payment-nonce' ); ?>
					</div>
				</div>
			</div>
			</form>
		</div>
		
	<?php
	do_action( 'admin_footer', '' );
	do_action( 'admin_print_footer_scripts' );
	do_action( 'admin_footer-' . $hook_suffix );
	
	// get_site_option() won't exist when auto upgrading from <= 2.7
	if ( function_exists( 'get_site_option' ) ) {
		if ( false === get_site_option( 'can_compress_scripts' ) )
			compression_test();
	}
	?>
	
	<div class="clear"></div></div><!-- wpwrap -->
	<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
	</body>
	</html>
	
	<?php
	die();
}
add_action( 'wp_ajax_it-exchange-add-manual-purchase-for-user', 'it_exchange_manual_purchase_for_user_print_add_products_screen' );

function it_exchange_manual_purchases_ajax_filter_products() {
	$response = '';
	if ( isset( $_POST['filter'] ) ) {
		$args = array(
			'product_type' => $_POST['filter'],
		);
		$response = it_exchange_manual_purchases_product_listing( $args );
	}
	die( $response );
}
add_action( 'wp_ajax_it-exchange-manual-purchases-filter-products', 'it_exchange_manual_purchases_ajax_filter_products' );

function it_exchange_manual_purchases_ajax_search_products() {
	$response = '';
	if ( isset( $_POST['search'] ) ) {
		$args = array(
			'search' => $_POST['search'],
		);
		$response = it_exchange_manual_purchases_product_listing( $args );
	}
	die( $response );
}
add_action( 'wp_ajax_it-exchange-manual-purchases-search-products', 'it_exchange_manual_purchases_ajax_search_products' );