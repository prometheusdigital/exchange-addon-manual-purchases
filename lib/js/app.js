/**
 *
 * @param {Object} config
 * @param {Object} config.l10n
 * @param {Object} config.l10n.customerSelect
 * @param {Object} config.l10n.productSelect
 * @param {Object} config.l10n.addressSelect
 * @param {Object} config.l10n.purchase
 * @param {Object} config.l10n.shippingMethod
 */
(function ( $, Backbone, _, wp, api, common, config ) {

	var app = window.ManualPurchasesApp = Object.create( {
		steps   : null,
		cartView: null,

		start: function () {

			this.steps = new Steps();
			this.steps.inject( '.add-new-payment-container' );

			$( window ).unload( function () {
				if ( app.steps.cart ) {
					app.steps.cart.destroy( { async: false } );
				}
			} );
		}
	} );

	var productItemCache = {};

	var Steps = api.View.extend( {

		tagName: 'ul',

		steps      : [],
		currentStep: 0,

		cart: null,

		initialize: function () {

			var cart;

			if ( config.cart ) {
				cart = new api.Models.Cart( config.cart );
				cart._customer = new api.Models.Customer( config.customer );

				app.cartView = new CartView( { model: cart } );
				app.cartView.inject( '.cart-summary-container' );

				this.cart = cart;
			} else {
				this.steps.push( new CustomerSelectView() );
			}

			this.steps.push( new ProductSelectView( { collection: new api.Collections.Products() } ) );
			this.steps.push( new AddressSelectView( { type: 'billing' } ) );
			this.steps.push( new AddressSelectView( { type: 'shipping' } ) );
			this.steps.push( new ShippingMethodView() );
			this.steps.push( new ModifyTotalsView() );
			this.steps.push( new PurchaseView() );

			for ( var i = 0; i < this.steps.length; i++ ) {
				this.views.add( this.steps[i] );

				if ( cart && this.steps[i].setCart ) {
					this.steps[i].setCart( cart );
				}

				this.registerStepCompleteListener( this.steps[i], i );

				this.listenTo( this.steps[i], 'exchange.cartUpdated', (function ( cart ) {
					this.cart = cart;

					for ( var i = 0; i < this.steps.length; i++ ) {
						if ( this.steps[i].setCart ) {
							this.steps[i].setCart( cart );
						}
					}

					if ( !app.cartView ) {
						app.cartView = new CartView( { model: cart } );
						app.cartView.inject( '.cart-summary-container' );
					}
				}).bind( this ) );
			}

			if ( this.steps[0].showStep ) {
				this.steps[0].showStep();
			} else {
				this.steps[0].$el.removeClass( 'hidden' );
				this.steps[0].$( 'details' ).prop( 'open', 'open' );
			}

			if ( this.steps[1].preFetch ) {
				this.steps[1].preFetch();
			}
		},

		registerStepCompleteListener: function ( step, stepIndex ) {

			this.listenTo( step, 'exchange.stepComplete', (function ( stepIndex ) {
				return (function () {

					this.steps[stepIndex].$( 'details' ).prop( 'open', false );

					var next = this.steps[stepIndex + 1];

					if ( !next ) {
						return;
					}

					if ( next.validFor && !next.validFor( this.cart ) ) {
						next.trigger( 'exchange.stepComplete' );
					} else {

						if ( next.showStep ) {
							next.showStep();
						} else {
							next.$el.removeClass( 'hidden' );
							next.$( 'details' ).prop( 'open', 'open' );
						}
					}

					if ( this.steps[stepIndex + 2] && this.steps[stepIndex + 2].preFetch ) {
						this.steps[stepIndex + 2].preFetch();
					}
				}).bind( this );
			}).bind( this )( stepIndex ) );

			this.listenTo( step, 'exchange.purchaseFinished', function ( transaction ) {
				app.cartView.onPurchase( transaction );
			} );
		}
	} );

	/**
	 * CartView.
	 *
	 * @property {itExchange.api.Models.Cart} model
	 */
	var CartView = api.View.extend( {

		template: wp.template( 'mp-cart' ),

		initialize: function ( options ) {
			this.views.add( '.customer-container', new CartCustomerView( { model: this.model.customer() } ) );
			this.views.add( '.checkout-container', new api.Views.Checkout( {
				model: this.model,

				includePurchaseMethods: false,
			} ) );

			this.model.on( 'change:items', this.toggleCheckoutContainer, this );

			this.model.on( 'change', this.renderAddresses, this );

			api.View.prototype.initialize.apply( this, options );
		},

		render: function () {
			this.$el.html( this.template() );
			this.views.render();

			this.toggleCheckoutContainer();
		},

		toggleCheckoutContainer: function () {

			if ( this.model.allItems().length < 1 ) {
				this.$( '.checkout-container' ).hide();
			} else {
				this.$( '.checkout-container' ).show();
			}
		},

		renderAddresses: function ( cart ) {

			if ( !cart.hasChanged( 'billing_address' ) && !cart.hasChanged( 'shipping_address' ) ) {
				return;
			}

			if ( !_.isObject( cart.get( 'billing_address' ) ) && !_.isObject( cart.get( 'shipping_address' ) ) ) {
				return;
			}

			this.views.unset( '.address-container' ).render();

			_.forEach( ['billing', 'shipping'], (function ( type ) {

				var address = cart.get( type + '_address' );

				if ( _.isEmpty( address ) ) {
					return;
				}

				var model;

				if ( _.isNumber( address ) ) {
					model = new api.Models.Address( { id: address } );
				} else {
					model = new api.Models.Address( address );
				}

				if ( !_.isEmpty( address ) && !_.isNumber( address ) ) {
					this.views.add( '.address-container', new CartAddressView( {
						model: model,
						type : type,
					} ) )
				}

			} ).bind( this ) );
		},

		onPurchase: function ( transaction ) {

			var href = transaction.getLinkUrl( 'edit' );
			this.$( '.cart-receipt' ).html(
				$( '<a></a>' )
					.attr( 'href', href )
					.addClass( 'button' )
					.addClass( 'button-primary' )
					.text( config.l10n.purchase.viewDetails )
			);
		}
	} );

	var CartCustomerView = api.View.extend( {

		template      : wp.template( 'mp-customer' ),
		animateRemoval: true,

		render: function () {
			this.$el.html( this.template( this.model.toJSON() ) );
		}
	} );

	var CartAddressView = api.View.extend( {
		template      : wp.template( 'mp-address' ),
		type          : 'billing',
		animateRemoval: true,

		initialize: function ( options ) {
			if ( typeof options.type !== 'undefined' ) {
				this.type = options.type;
			}

			api.View.prototype.initialize.call( this, options );
		},

		render: function () {

			var attr = {
				type            : this.type,
				typeLabel       : config.l10n.addressSelect[this.type],
				addressFormatted: this.model.formatted()
			};

			this.$el.html( this.template( attr ) );
		}
	} );

	var CustomerSelectView = api.View.extend( {

		tagName : 'li',
		template: wp.template( 'customer-select' ),

		events: {
			'change input[name="customerType"]': 'toggleCustomerType',
			'click .select-customer'           : 'customerSelected',
			'click .add-customer'              : 'createCustomer'
		},

		password: '',

		toggleCustomerType: function () {
			var type = this.$( 'input[name="customerType"]:checked' ).val();

			if ( type === 'existing' ) {
				this.$( '.existing-customer' ).removeClass( 'hidden' );
				this.$( '.new-customer' ).addClass( 'hidden' );
			} else {
				wp.ajax.post( 'generate-password' ).done( (function ( data ) { this.password = data; }).bind( this ) );
				this.$( '.existing-customer' ).addClass( 'hidden' );
				this.$( '.new-customer' ).removeClass( 'hidden' );
			}
		},

		customerSelected: function ( e ) {
			var id = this.$( '.customer-search' ).val();
			var button = this.$( e.currentTarget ).prop( 'disabled', true );

			this.triggerStepComplete( new api.Models.Customer( { id: id } ), button );
		},

		/**
		 * Create the user in WordPress.
		 *
		 * @since 2.0.0
		 *
		 * @param {Event} e
		 */
		createCustomer: function ( e ) {

			var button = this.$( e.currentTarget ).prop( 'disabled', true );

			if ( !this.password.length ) {
				wp.ajax.post( 'generate-password' ).done( (function ( data ) {
					this.password = data;
					this.createCustomer( e );
				}).bind( this ) );

				return;
			}

			var data = {
				username  : this.$( 'input[name="username"]' ).val(),
				email     : this.$( 'input[name="email"]' ).val(),
				first_name: this.$( 'input[name="firstName"]' ).val(),
				last_name : this.$( 'input[name="lastName"]' ).val(),
				password  : this.password,
			};

			$.ajax( {
				method    : 'POST',
				url       : common.config.wpRestUrl + '/users',
				data      : data,
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', common.config.restNonce )
				},
			} ).done( (function ( response ) {
				this.triggerStepComplete( new api.Models.Customer( { id: response.id } ), button );
			}).bind( this ) ).fail( function ( xhr ) {
				console.log( arguments );
				button.prop( 'disabled', false );
				alert( common.getErrorFromXhr( xhr ) );
			} );
		},

		triggerStepComplete: function ( customer, button ) {

			api.createCart( '', {
				customer: customer.id,
				is_main : false,
				embed   : true,
			} ).done( (function ( cart ) {
				button.prop( 'disabled', false );
				this.trigger( 'exchange.cartUpdated', cart );
				this.trigger( 'exchange.stepComplete' );
			} ).bind( this ) ).fail( function ( xhr ) {
				button.prop( 'disabled', false );
				alert( common.getErrorFromXhr( xhr ) );
			} );
		},

		render: function () {

			this.$el.html( this.template( config.l10n.customerSelect ) ).addClass( 'hidden' );

			this.$( '.existing-customer, .new-customer' ).addClass( 'hidden' );

			this.$( '.customer-search' ).select2( {
				ajax              : {
					url     : 'https://www.exchange.dev/wp-json/wp/v2/users',
					dataType: 'json',
					//delay   : 250,

					beforeSend: function ( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', common.config.restNonce );
					},

					data: function ( params ) {
						return {
							search: params.term,
							//page  : params.page,
						};
					},

					processResults: function ( response ) {

						var results = [];

						for ( var i = 0; i < response.length; i++ ) {
							results[i] = {
								id  : response[i].id,
								text: response[i].name
							}
						}

						return {
							results: results
						}
					},
				},
				minimumInputLength: 3,
			} );
		}
	} );

	var ProductSelectView = api.View.extend( {
		tagName : 'li',
		template: wp.template( 'product-select' ),

		cart: null,

		events: {
			'keyup input[type="search"]': 'onSearchKeyUp',
			'click .continue'           : 'onContinueClick',
		},

		initialize: function ( options ) {
			this.listenTo( this.collection, 'reset', this.renderSearchResults );
			this.$el.addClass( 'hidden' );

			api.View.prototype.initialize.call( this, options );
		},

		onSearchKeyUp: _.debounce( function () {

			this.$( '.spinner' ).addClass( 'is-active' );

			var search = this.$( 'input[type="search"]' ).val();

			if ( search.length < 3 ) {
				return;
			}

			this.collection.addFilter( 'search', search );
			this.collection.doFilter( [], { local: false, reset: true, embed: true } );
		}, 100 ),

		setCart: function ( cart ) {
			this.cart = cart;

			this.cart.on( 'change:items', this.toggleContinueButton, this );
		},

		render: function () {
			var attr = {
				summaryLabel      : config.l10n.productSelect.summaryLabel,
				searchInstructions: config.l10n.productSelect.searchInstructions,
				continue          : config.l10n.productSelect.continue,
			};
			this.$el.html( this.template( attr ) );
			this.$( '.spinner' ).css( 'position', 'absolute' );

			this.renderSearchResults();
		},

		renderSearchResults: function () {
			this.views.unset( '.product-search-results' );
			this.collection.forEach( (function ( model ) {
				this.addProductView( model, { silent: true } );
			}).bind( this ) );
			this.views.render();
			this.$( '.spinner' ).removeClass( 'is-active' );
		},

		addProductView: function ( model, options ) {

			var view = new ProductOptionView( {
				model: model,
				cart : this.cart
			} );

			this.views.add( '.product-search-results', view, options );
		},

		toggleContinueButton: function () {

			if ( this.cart.get( 'items' ).length ) {
				this.$( '.continue' ).removeClass( 'hidden' );
			} else {
				this.$( '.continue' ).addClass( 'hidden' );
			}
		},

		onContinueClick: function () {
			this.trigger( 'exchange.stepComplete' );
		}
	} );

	var ProductOptionView = api.View.extend( {

		tagName : 'li',
		template: wp.template( 'product-option' ),

		cart: null,

		/** @property {itExchange.api.Models.CartItem} */
		lineItem: null,

		events: {
			'click .product-option--triggers-add button'               : 'onAddClick',
			'click .product-option--triggers-modify .decrease-quantity': 'onDecrease',
			'click .product-option--triggers-modify .increase-quantity': 'onIncrease',
		},

		initialize: function ( options ) {
			this.cart = options.cart;

			if ( productItemCache[this.model.id] ) {
				this.lineItem = productItemCache[this.model.id];
			}

			api.View.prototype.initialize.call( this, options );
		},

		onAddClick: function ( e ) {
			var button = this.$( e.currentTarget ).prop( 'disabled', true );

			this.lineItem = this.cart.products().create( { product: this.model.id }, {
				wait   : true,
				success: (function () {
					button.prop( 'disabled', false );
					this.renderTriggers();
					this.toggleTriggers();
				}).bind( this )
			} );

			productItemCache[this.model.id] = this.lineItem;
		},

		onDecrease: function ( e ) {

			var button = this.$( e.currentTarget ).prop( 'disabled', true );

			if ( this.lineItem.get( 'quantity.selected' ) === 1 ) {
				this.lineItem.destroy( {
					wait   : true,
					success: (function () {
						button.prop( 'disabled', false );
						this.lineItem = null;
						this.toggleTriggers();
						delete productItemCache[this.model.id];
					}).bind( this ),
					error  : function ( xhr ) {
						button.prop( 'disabled', false );
						alert( common.getErrorFromXhr( xhr ) );
					},
				} )
			} else {
				this.lineItem.set( 'quantity.selected', this.lineItem.get( 'quantity.selected' ) - 1 );
				this.lineItem.save( {}, {
					success: (function () {
						button.prop( 'disabled', false );
						this.renderTriggers();
					}).bind( this ),
					error  : function ( xhr ) {
						button.prop( 'disabled', false );
						alert( common.getErrorFromXhr( xhr ) );
					},
				} )
			}
		},

		onIncrease: function ( e ) {

			var button = this.$( e.currentTarget ).prop( 'disabled', true );

			this.lineItem.set( 'quantity.selected', this.lineItem.get( 'quantity.selected' ) + 1 );
			this.lineItem.save( {}, {
				success: (function () {
					button.prop( 'disabled', false );
					this.renderTriggers();
				}).bind( this ),
				error  : function ( xhr ) {
					button.prop( 'disabled', false );
					alert( common.getErrorFromXhr( xhr ) );
				},
			} );
		},

		toggleTriggers: function () {
			this.$( '.product-option--triggers-add' ).toggle();
			this.$( '.product-option--triggers-modify' ).toggle();
		},

		render: function () {
			var attr = _.clone( this.model.toJSON() );
			attr.priceFormatted = common.formatPrice( attr.price );
			attr.add = config.l10n.productSelect.add;
			attr.added = config.l10n.productSelect.added;
			attr.increase = config.l10n.productSelect.increase;
			attr.decrease = config.l10n.productSelect.decrease;

			var featured = this.model.featuredMedia();

			if ( featured ) {
				var details = featured.details( 'thumbnail' );

				if ( details ) {
					attr.image = details.source_url;
				}
			}

			this.$el.html( this.template( attr ) );

			if ( this.lineItem ) {
				this.$( '.product-option--triggers-modify' ).show();
				this.$( '.product-option--triggers-add' ).hide();
				this.renderTriggers();
			} else {
				this.$( '.product-option--triggers-modify' ).hide();
				this.$( '.product-option--triggers-add' ).show();
			}

			return this;
		},

		renderTriggers: function () {

			if ( !this.lineItem ) {
				return this;
			}

			var max = this.lineItem.get( 'quantity.max' ), quantity = this.lineItem.get( 'quantity.selected' );

			if ( ( max !== '' && quantity >= max ) || !this.lineItem.get( 'quantity.editable' ) ) {
				this.$( '.product-option--triggers-modify .increase-quantity' ).css( { visibility: 'hidden' } );
			} else {
				this.$( '.product-option--triggers-modify .increase-quantity, .product-option--triggers-modify .decrease-quantity' ).css( { visibility: 'visible' } );
			}

			return this;
		}
	} );

	var AddressSelectView = api.View.extend( {

		tagName : 'li',
		template: wp.template( 'address-select' ),

		type: 'billing',
		cart: null,

		initialize: function ( options ) {

			if ( typeof options.type !== 'undefined' ) {
				this.type = options.type;
			}

			api.View.prototype.initialize.call( this, options );
		},

		setCart: function ( cart ) {

			this.cart = cart;

			var addresses = cart.customer().addresses();
			addresses.context = 'edit';
			addresses.fetch();

			addresses.on( 'add', this.addAddress, this );
			this.addAddresses( addresses );

			return this;
		},

		showStep: function () {

			this.$el.removeClass( 'hidden' );
			this.$( 'details' ).prop( 'open', 'open' );

			AddressSelectView.dataSets.done( (function ( countries, states ) {
				this.views.add( '.address-options-container', new NewAddressOption( {
					collection: this.cart.customer().addresses(),
					countries : countries,
					states    : states,
				} ) );
			}).bind( this ) );
		},

		preFetch: function () {

			if ( AddressSelectView.dataSets ) {
				return;
			}

			var defCountries = $.Deferred(), defStates = $.Deferred();

			$.get( common.getRestUrl( 'datasets/countries' ), function ( response ) {
				if ( !response.data ) {
					return defCountries.reject();
				}

				var html = '';

				for ( var countryCode in response.data ) {
					if ( response.data.hasOwnProperty( countryCode ) ) {
						html += '<option value="' + countryCode + '">' + response.data[countryCode] + '</option>';
					}
				}

				defCountries.resolve( html );
			} );

			$.get( common.getRestUrl( 'datasets/states', { country: 'all' } ), function ( response ) {

				if ( !response.data ) {
					return defStates.reject();
				}

				var countries = response.data;
				var statesHtml = {};

				for ( var countryCode in countries ) {
					var html = '';

					if ( countries.hasOwnProperty( countryCode ) ) {
						var states = countries[countryCode];

						for ( var stateCode in states ) {
							if ( states.hasOwnProperty( stateCode ) ) {
								html += '<option value="' + stateCode + '">' + states[stateCode] + '</option>';
							}
						}

						statesHtml[countryCode] = html;
					}
				}

				defStates.resolve( statesHtml );
			} );

			AddressSelectView.dataSets = $.when( defCountries, defStates );
		},

		validFor: function ( cart ) {
			return this.type === 'billing' || cart.get( 'requires_shipping' );
		},

		addAddresses: function ( collection ) {
			collection.forEach( this.addAddress, this );
		},

		addAddress: function ( address ) {

			var options = {};

			var views = this.views.get( '.address-options-container' );

			if ( views && views.length > 2 ) {
				options.at = views.length - 1;
			}

			var view = new AddressOptionView( {
				model: address,
				type : this.type
			} );
			this.views.add( '.address-options-container', view, options );

			this.listenTo( view, 'exchange.addressSelected', this.onAddressSelected );

			if ( address.isNew() ) {
				this.listenToOnce( address, 'sync', function () {
					view.$( 'input[type="radio"]' ).prop( 'checked', true );
					this.onAddressSelected( address );
				} );
			}
		},

		onAddressSelected: function ( address ) {

			if ( !address ) {
				return;
			}

			var current = this.cart.get( this.type + '_address' );

			if ( current && current.id == address.id ) {
				this.trigger( 'exchange.stepComplete' );
				return;
			}

			var toSave = {};
			toSave[this.type + '_address'] = address.toJSON();

			this.cart.save( toSave, {
				success: (function () {
					this.trigger( 'exchange.stepComplete' );
				}).bind( this )
			} );
		},

		render: function () {

			var attr = { type: this.type };
			attr.summaryLabel = config.l10n.addressSelect[this.type];

			this.$el.html( this.template( attr ) );
			this.$el.addClass( 'hidden' );
		}
	} );

	var AddressOptionView = api.View.extend( {

		tagName  : 'li',
		className: 'address-option',
		template : wp.template( 'address-option' ),
		events   : {
			'change input': 'onSelected'
		},

		type: '',

		initialize: function ( options ) {
			this.type = options.type;

			if ( this.model.get( 'type' ) === 'both' || this.model.get( 'type' ) === this.type ) {
				this.$el.addClass( 'primary' );
			}

			api.View.prototype.initialize.call( this, options );
		},

		onSelected: function () {

			var id = this.$( 'input:checked' ).val();

			if ( id == this.model.id ) {
				this.trigger( 'exchange.addressSelected', this.model );
			}
		},

		render: function () {

			var attr = this.model.toJSON();
			attr.type = this.type;
			attr.addressFormatted = common.formatAddress( attr );
			attr.lastUsedLabel = config.l10n.addressSelect.lastUsed;

			if ( attr.last_used && attr.last_used.length ) {
				attr.lastUsedFormatted = common.formatDate( attr.last_used, false );
			} else {
				attr.lastUsedFormatted = config.l10n.addressSelect.never;
			}

			this.$el.html( this.template( attr ) );
		}
	} );

	var NewAddressOption = api.View.extend( {

		tagName  : 'li',
		className: 'address-option new-address-option',
		template : wp.template( 'address-new-option' ),

		events: {
			'click .add-new-address': 'onAddClick'
		},

		countries: '',
		states   : {},
		form     : null,

		initialize: function ( options ) {
			this.countries = options.countries;
			this.states = options.states;

			api.View.prototype.initialize.call( this, options );
		},

		onAddClick: function () {

			if ( this.form ) {
				this.stopListening( this.form );
			}

			this.$el.addClass( 'new-address-option-editing' );

			this.$( '.add-new-address' ).hide();

			this.form = new api.Views.AddressForm( {
				collection: this.collection,
				countries : this.countries,
				states    : this.states,
			} );
			this.views.set( '.add-new-address-form', this.form );

			this.listenTo( this.form, 'exchange.closedFromCreate', this.onCreate );
			this.listenTo( this.form, 'exchange.closedFromCancel', this.onCancel );
		},

		onCancel: function () {
			this.closeForm();
		},

		onCreate: function ( a ) {
			this.closeForm();
		},

		closeForm: function () {
			this.stopListening( this.form );
			this.form = null;
			this.views.unset( '.add-new-address-form' );
			this.$( '.add-new-address-form' ).html( '' );
			this.$el.removeClass( 'new-address-option-editing' );
			this.$( '.add-new-address' ).show();
		},

		render: function () {
			this.$el.html( this.template() );
		}

	} );

	/**
	 * @property {itExchange.api.Models.Shipping} shipping
	 */
	var ShippingMethodView = api.View.extend( {
		tagName : 'li',
		template: wp.template( 'shipping-method' ),

		events: {
			'change .shipping-cart-wide-container input[type="radio"]': 'onCartWideSelected',
			'click .shipping-continue'                                : 'onContinue',
		},

		cart    : null,
		shipping: null,

		setCart: function ( cart ) {
			this.cart = cart;

			this.listenTo( this.shipping = this.cart.shipping(), 'change', this.renderMethods );
		},

		preFetch: function () {
			this.shipping.fetch();
		},

		validFor: function ( cart ) {
			return !!cart.get( 'requires_shipping' );
		},

		renderMethods: function () {
			this.views.unset( '.shipping-cart-wide-container' );
			this.$( '.shipping-cart-wide-container' ).html( '' );
			_.each( this.shipping.cartWideMethods(), function ( method ) {
				this.views.add( '.shipping-cart-wide-container', new CartWideShippingMethodOption( { method: method } ) );
			}, this );
		},

		onCartWideSelected: function () {

			var selected = this.getCartWide();

			this.views.unset( '.shipping-per-item-container' );
			this.$( '.shipping-per-item-container' ).html( '' );

			if ( selected === 'multiple-methods' ) {

				var allPerItemMethods = this.shipping.allPerItemMethods();

				for ( var i = 0; i < allPerItemMethods.length; i++ ) {
					this.views.add( '.shipping-per-item-container', new PerItemShippingMethodOptions(
						allPerItemMethods[i]
					) );
				}

				this.$( '.shipping-continue' ).removeClass( 'hidden' );
			} else {
				this.$( '.shipping-continue' ).removeClass( 'hidden' );
			}
		},

		getCartWide: function () {
			return this.$( '.shipping-cart-wide-container input[type="radio"]:checked' ).val();
		},

		onContinue: function ( e ) {

			var selected = this.getCartWide();

			if ( selected !== 'multiple-methods' ) {
				this.shipping.setCartWideMethod( selected );
			} else {
				this.shipping.setCartWideMethod( 'multiple-methods' );

				var perItemViews = this.views.get( '.shipping-per-item-container' ), perItemView, $select;

				for ( var i = 0; i < perItemViews.length; i++ ) {
					perItemView = perItemViews[i];
					$select = perItemView.$( 'select' );

					if ( $select.length ) {
						this.shipping.setPerItemMethod( perItemView.item, $select.val() );
					}
				}
			}

			var btn = this.$( e.currentTarget ).prop( 'disabled', true );

			return this.shipping.save( null, {
				success: (function () {
					this.trigger( 'exchange.stepComplete' );
					btn.prop( 'disabled', false );
				}).bind( this ),
				error  : function ( xhr ) {
					alert( common.getErrorFromXhr( xhr ) );
					btn.prop( 'disabled', false );
				}
			} );
		},

		render: function () {
			this.$el.html( this.template( config.l10n.shippingMethod ) ).addClass( 'hidden' );
		}
	} );

	/**
	 * @property {itExchange.api.Structs.ShippingMethod} method
	 */
	var CartWideShippingMethodOption = api.View.extend( {
		template: wp.template( 'cart-wide-shipping-method-option' ),

		method: null,

		initialize: function ( options ) {
			this.method = options.method;

			api.View.prototype.initialize.call( this, options );
		},

		render: function () {
			this.$el.html( this.template( this.method.toJSON() ) );

			if ( this.method.selected ) {
				this.$( 'input' ).prop( 'checked', true );
			} else {
				this.$( 'input' ).prop( 'checked', false );
			}
		},
	} );

	var PerItemShippingMethodOptions = api.View.extend( {
		template: wp.template( 'per-item-shipping-method-options' ),

		item   : null,
		methods: [],

		initialize: function ( options ) {
			this.item = options.item;
			this.methods = options.methods;

			api.View.prototype.initialize.call( this, options );
		},

		render: function () {
			this.$el.html( this.template( { itemName: this.item.get( 'name' ) } ) );

			if ( this.methods.length === 1 ) {
				this.$( 'label' ).append( '<span>' + this.methods[0].label + '</span>' );
			} else {

				var $select = $( '<select></select>' ), method;

				for ( var i = 0; i < this.methods.length; i++ ) {
					method = this.methods[i];
					$select.append(
						$( '<option></option>' )
							.val( method.id )
							.text( method.label + ' - ' + method.totalFormatted() )
					);
				}

				this.$( 'label' ).append( $select );
			}
		}
	} );

	var ModifyTotalsView = api.View.extend( {

		className: 'modify-totals-view',
		tagName  : 'li',
		template : wp.template( 'modify-totals' ),

		events: {
			'click .add-coupon'   : 'onAddCoupon',
			'click .remove-coupon': 'onRemoveCoupon',
			'click .continue'     : 'onContinue'
		},

		cart      : null,
		couponItem: null,

		setCart: function ( cart ) {
			this.cart = cart;
		},

		onAddCoupon: function ( e ) {
			var $addBtn = $( e.currentTarget ).prop( 'disabled', true ),
				$code = this.$( 'input[name="couponCode"]' ).prop( 'disabled', true ),
				$removeBtn = this.$( '.remove-coupon' );

			var code = $code.val();

			if ( code.length ) {
				this.couponItem = this.cart.coupons().create( { coupon: code }, {
					wait   : true,
					success: function () {
						$addBtn.prop( 'disabled', false ).hide();
						$removeBtn.show();
					}
				} );
			}
		},

		onRemoveCoupon: function ( e ) {
			var $removeBtn = $( e.currentTarget ).prop( 'disabled', true ),
				$code = this.$( 'input[name="couponCode"]' ),
				$addBtn = this.$( '.add-coupon' );

			if ( this.couponItem ) {
				this.couponItem.destroy( {
					wait   : true,
					success: (function () {
						this.couponItem = null;
						$removeBtn.prop( 'disabled', false ).hide();
						$addBtn.show();
						$code.prop( 'disabled', false ).text( '' );
					}).bind( this ),
				} );
			}
		},

		onContinue: function () {
			this.trigger( 'exchange.stepComplete' );
		},

		render: function () {
			this.$el.html( this.template() );
			this.$el.addClass( 'hidden' );
		}
	} );

	var PurchaseView = api.View.extend( {
		tagName : 'li',
		template: wp.template( 'purchase' ),

		cart: null,

		events: {
			'focusout textarea[name="purchaseNote"]'           : 'onPurchaseNote',
			'click it-exchange-checkout-purchase-method-button': 'onMethodSelected',
		},

		setCart: function ( cart ) {
			this.cart = cart;

			var methods = cart.purchaseMethods();
			this.listenTo( methods, 'reset', function () {
				this.views.unset( '.purchase-methods-container' );
				this.views.render();
				methods.forEach( this.addPurchaseMethodView, this );
			} );
		},

		preFetch: function () {
			this.listenTo( this.cart, 'change', function () {
				this.cart.purchaseMethods().fetch( { reset: true } );
			} );
		},

		render: function () {
			this.$el.html( this.template( {
				summaryLabel: config.l10n.purchase.summaryLabel,
				save        : config.l10n.purchase.save,
				purchaseNote: config.l10n.purchase.purchaseNote,
			} ) );
			this.$el.addClass( 'hidden' );
		},

		onPurchaseNote: function () {

			var $note = this.$( 'textarea[name="purchaseNote"]' );
			$note.prop( 'disabled', true );
			this.cart.set( 'meta.manual_purchase_note', $note.val() );
			this.cart.save( null, {
				success: (function () {
					$note.prop( 'disabled', false );
					$note.val( this.cart.get( 'meta.manual_purchase_note' ) )
				}).bind( this )
			} );
		},

		addPurchaseMethodView: function ( purchaseMethodModel ) {
			var view = new api.Views.PurchaseMethod( {
				model     : purchaseMethodModel,
				cart      : this.cart,
				addView   : (this._addAdditionalDetailsView).bind( this ),
				removeView: (this._removeAdditionalDetailsView).bind( this ),
			} );
			this.views.add( '.purchase-methods-container', view );
			this.listenTo( view, 'exchange.cancelPurchaseMethod', function () {
				this.$( '.purchase-methods-container' ).show();
			} );

			this.listenTo( view, 'exchange.purchaseFinished', this.onPurchase );
		},

		onMethodSelected: function () {
			this.$( '.purchase-methods-container' ).hide();
		},

		onPurchase: function ( transaction ) {
			this.trigger( 'exchange.stepComplete' );
			this.trigger( 'exchange.purchaseFinished', transaction );
		},

		_addAdditionalDetailsView: function ( view ) {
			this.views.add( '.additional-purchase-info-container', view );
		},

		_removeAdditionalDetailsView: function ( view ) {
			this.views.unset( '.additional-purchase-info-container', view );
		},
	} );

	$( function () {
		api.loadCart();
		app.start();
	} );
})( jQuery, window.Backbone, window._, window.wp, window.itExchange.api, window.itExchange.common, window.manualPurchases );