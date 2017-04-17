<?php

/**
 * Ajax called to calculate the current Total of the producats manually selected.
 *
 * @since 1.0.0
*/
function it_exchange_manual_purchases_add_payment_page_add_product() {

	_deprecated_function( __FUNCTION__, '2.0.0' );

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

	_deprecated_function( __FUNCTION__, '2.0.0' );

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

    _deprecated_function( __FUNCTION__, '2.0.0' );
    die();
}

function it_exchange_manual_purchases_ajax_filter_products() {

    _deprecated_function( __FUNCTION__, '2.0.0' );

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

    _deprecated_function( __FUNCTION__, '2.0.0' );

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