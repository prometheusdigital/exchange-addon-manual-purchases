jQuery(document).ready(function($) {
    // Enable Autocomplete on WordPress Username/ID
    $('#it-exchange-manual-purchase-existing-userid').selectToAutocomplete();
    
    $( '.it-exchange-add-manual-purchase-product-options' ).on( 'click', '.it-exchange-add-purchase-product', function() {
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
		if ( 0 !== data['product_ids'].length ) {
			$.post( ajaxurl, data, function( response ) {
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
		$.post( ajaxurl, data, function( response ) {
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
	
	$( '#filter_submit' ).on( 'click', function( event ) {
		event.preventDefault();
		var data = {
			'action': 'it-exchange-manual-purchases-filter-products',
			'filter':  $( '#product-type-filter option:selected' ).val(),
		}
		$.post( ajaxurl, data, function( response ) {
			if ( '' != response ) {
				$( '#it-exchange-add-purchase-product-list' ).replaceWith( response );
			}
		});
	});
	
	$( '#product-search' ).on( 'keypress', function( event ) {
		 var code = event.keyCode || event.which;
		if( 13 === code ) {
			event.preventDefault();
			var data = {
				'action': 'it-exchange-manual-purchases-search-products',
				'search':  $( this ).val(),
			}
			$.post( ajaxurl, data, function( response ) {
				if ( '' != response ) {
					$( '#it-exchange-add-purchase-product-list' ).replaceWith( response );
				}
			});
		}
	});
	
	$( '#search_submit' ).on( 'click', function( event ) {
		event.preventDefault();
		var data = {
			'action': 'it-exchange-manual-purchases-search-products',
			'search':  $( '#product-search' ).val(),
		}
		$.post( ajaxurl, data, function( response ) {
			if ( '' != response ) {
				$( '#it-exchange-add-purchase-product-list' ).replaceWith( response );
			}
		});
	});
	
	$( '.it-exchange-user-option' ).on( 'change', function( event ) {
		event.preventDefault();
		$( '.it-exchange-add-manual-purchase-user-option-existing' ).toggleClass( 'hidden' );
		$( '.it-exchange-add-manual-purchase-user-option-add-new' ).toggleClass( 'hidden' );
	});
});
