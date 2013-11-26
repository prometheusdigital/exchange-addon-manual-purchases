<?php

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

function it_exchange_manual_purchases_format_price() {
    $price = 0;
    if ( isset( $_POST['price'] ) )
        $price = it_exchange_convert_to_database_number( $_POST['price'] );
    
    die( it_exchange_format_price( it_exchange_convert_from_database_number( $price ) ) );
}
add_action( 'wp_ajax_it-exchange-manual-purchases-format-price', 'it_exchange_manual_purchases_format_price' );