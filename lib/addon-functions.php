<?php

/**
 * Is this the manual purchases page.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function it_exchange_is_manual_purchases_page() {
	return ! empty( $_GET['page'] ) && 'it-exchange-add-manual-purchase' === $_GET['page'];
}

/**
 * Displays the Manual Purchases - Add Payment screen
 *
 * @since 1.0.0
 * @todo  switch out wp_insert_user with it_exchange_create_customer
 */
function it_exchange_manual_purchase_print_add_payment_screen() {

	?>
    <div class="wrap">

        <h2><?php _e( 'Add Manual Purchase', 'LION' ); ?></h2>

        <div class="manual-purchases-wrap">
            <div class="add-new-payment-container"></div>
            <div class="cart-summary-container"></div>
        </div>
    </div>
	<?php
}

/**
 * This proccesses a Manual Purchase transaction.
 *
 * @since 1.0.0
 *
 * @param int      $customer_id
 * @param stdClass $transaction_object The transaction object
 *
 * @return int|false
 */
function it_exchange_manual_purchases_addon_process_transaction( $customer_id, $transaction_object ) {
	$uniqid = it_exchange_manual_purchases_addon_transaction_uniqid();

	return it_exchange_add_transaction( 'manual-purchases', $uniqid, 'Completed', $customer_id, $transaction_object );
}

/**
 * Generate a unique method ID for manual purchases.
 *
 * @since 1.0.0
 *
 * @return string
 */
function it_exchange_manual_purchases_addon_transaction_uniqid() {
	$uniqid = uniqid( '', true );

	if ( ! it_exchange_manual_purchases_addon_verify_unique_uniqid( $uniqid ) ) {
		$uniqid = it_exchange_manual_purchases_addon_transaction_uniqid();
	}

	return $uniqid;
}

/**
 * Verifies if Unique ID is actually Unique
 *
 * @since 1.0.0
 *
 * @param string $uniqid
 *
 * @return boolean true if it is, false otherwise
 */
function it_exchange_manual_purchases_addon_verify_unique_uniqid( $uniqid ) {
	return ! it_exchange_get_transaction_by_method_id( 'manual-purchases', $uniqid );
}

function it_exchange_manual_purchases_product_listing( $args ) {

	_deprecated_function( __FUNCTION__, '2.0.0' );

	$default = array(
		'product_type' => '',
		'search'       => '',
		'product_ids'  => array(),
	);
	$default = wp_parse_args( $args, $default );

	$args = array(
		'product_type'   => ! empty( $default['product_type'] ) ? $default['product_type'] : '',
		'posts_per_page' => - 1,
		'show_hidden'    => true,
	);

	if ( ! empty( $default['search'] ) ) {
		$args['s'] = $default['search'];
	}

	$products = it_exchange_get_products( $args );

	$output = '<div id="it-exchange-add-purchase-product-list">';
	foreach ( $products as $product ) {
		$img_output = '';

		// Give other add-ons the ability to skip specific products
		if ( false === apply_filters( 'it_exchange_manual_purchases_addon_include_product_in_select', true, $product ) ) {
			continue;
		}

		if ( it_exchange_product_supports_feature( $product->ID, 'product-images' )
		     && it_exchange_product_has_feature( $product->ID, 'product-images' )
		) {

			$product_images = it_exchange_get_product_feature( $product->ID, 'product-images' );
			$img_src        = wp_get_attachment_thumb_url( $product_images[0] );

			ob_start();
			?>
            <div class="it-exchange-feature-image-<?php echo $product->ID; ?> it-exchange-featured-image">
                <img alt="" src="<?php echo $img_src ?>"/>
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
			$value  = $product->ID;
		} else {
			$select = '';
			$value  = '';
		}

		$output .= '<div class="it-exchange-add-purchase-product ' . $select . '" data-product-id="' . $product->ID . '">';
		$output .= $img_output;
		$output .= '<span class="product-name">' . apply_filters( 'it_exchange_manual_purchases_addon_selected_product_title', $product->post_title, $product ) . '</span>';
		$output .= '<input id="it-exchange-add-purchase-product-' . $product->ID . '" type="hidden" value="' . $value . '" name="product_ids[]" />';
		$output .= '</div>';
	}
	$output .= '</div>';

	return $output;
}
