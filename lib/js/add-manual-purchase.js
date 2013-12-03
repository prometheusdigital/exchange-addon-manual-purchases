jQuery(document).ready(function($) {
    // Enable Autocomplete on WordPress Username/ID
    $('#it-exchange-manual-purchase-existing-userid').selectToAutocomplete();
    
    $( '.it-exchange-add-purchase-product' ).on( 'click', function() {
		var product_id = $( this ).data( 'product-id' );
		$( this ).toggleClass( 'it-exchange-add-purchase-product-selected' );
		if ( $( this ).hasClass( 'it-exchange-add-purchase-product-selected' ) )
			$( '#it-exchange-add-purchase-product-' + product_id, this ).val( product_id );
		else
			$( '#it-exchange-add-purchase-product-' + product_id, this ).val( '' );
			
		var data = {
			'action': 'it-exchange-manual-purchases-addon-add-payment-page-select-product',
			'product_ids': $( 'input[name=product_ids\\[\\]]' ).serializeArray(),
		}
		console.log( data );
		if ( 0 !== data['product_ids'].length ) {
			$.post( ajaxurl, data, function( response ) {
				console.log( response );
				$( '#it-exchange-add-manual-purchase-total-paid' ).val( response );
			});
		} else {
			$( '#it-exchange-add-manual-purchase-total-paid' ).val( '' );
		}
    });
    
    $( '#it-exchange-add-manual-purchase-total-paid' ).on( 'focusout', function( event ) {
		var this_obj = this;
		var data = {
			'action': 'it-exchange-manual-purchases-format-price',
			'price':    $( this ).val(),
		}
		console.log( data['price'] );
		$.post( ajaxurl, data, function( response ) {
			console.log( response );
			if ( '' != response ) {
				$( this_obj ).val( response );
			}
		});
	});
	
	$( '.it-exchange-add-manual-purchase-user-option-add-new .set-password' ).on( 'click', function() {
		$( this ).toggleClass( 'hidden' );
		$( '.it-exchange-add-manual-purchase-user-option-add-new .random-password' ).toggleClass( 'hidden' );
		$( '.it-exchange-add-manual-purchase-user-option-add-new .show-password-fields' ).toggleClass( 'hidden' );
	});
	
	$( '.it-exchange-add-manual-purchase-user-option-add-new .random-password' ).on( 'click', function() {
		$( this ).toggleClass( 'hidden' );
		$( '.it-exchange-add-manual-purchase-user-option-add-new .set-password' ).toggleClass( 'hidden' );
		$( '.it-exchange-add-manual-purchase-user-option-add-new .show-password-fields' ).toggleClass( 'hidden' );
		$( '.it-exchange-add-manual-purchase-user-option-add-new #it-exchange-manual-purchase-new-password1' ).val( '' );
		$( '.it-exchange-add-manual-purchase-user-option-add-new #it-exchange-manual-purchase-new-password2' ).val( '' );
	});
});