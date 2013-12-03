jQuery(document).ready(function($) {
    // Enable Autocomplete on WordPress Username/ID
    $('#it-exchange-manual-purchase-existing-userid').selectToAutocomplete();
    
    $( '.it-exchange-add-pruchase-product' ).on( 'change', function() {
		var data = {
			'action': 'it-exchange-manual-purchases-addon-add-payment-page-select-product',
			'product_ids': $( '.it-exchange-add-pruchase-product:checked' ).serializeArray(),
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
    
    $( '#it-exchange-add-manual-purchase-total-paid' ).live( 'focusout', function( event ) {
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
	});
});