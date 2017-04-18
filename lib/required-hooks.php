<?php
/**
 * This adds a menu item to the Exchange menu pointing to the WP All [post_type] table
 *
 * @since 1.0.0
 *
 * @return void
 */

add_action( 'it_exchange_register_gateways', function ( ITE_Gateways $gateways ) {
	require_once __DIR__ . '/class.gateway.php';
	require_once __DIR__ . '/handlers/class.purchase.php';

	$dir = IT_Exchange::$dir . '/core-addons/transaction-methods/offline-payments/handlers';

	if ( class_exists( 'ITE_Cancel_Subscription_Request' ) ) {
		require_once $dir . '/class.cancel-subscription.php';
	}

	if ( class_exists( 'ITE_Pause_Subscription_Request' ) ) {
		require_once $dir . '/class.pause-subscription.php';
	}

	if ( class_exists( 'ITE_Resume_Subscription_Request' ) ) {
		require_once $dir . '/class.resume-subscription.php';
	}

	$gateways::register( new ITE_Manual_Purchases_Gateway() );
} );

/**
 * Register the capability to allow manual purchases.
 */
function it_exchange_register_manual_purchases_capability() {
	wp_roles()->get_role( 'administrator' )->add_cap( 'it_make_manual_purchase' );
}

add_action( 'init', 'it_exchange_register_manual_purchases_capability' );

/**
 * Mark this transaction method as okay to manually change transactions
 *
 * @since 1.0.8
 */
add_filter( 'it_exchange_manual-purchases_transaction_status_can_be_manually_changed', '__return_true' );

/**
 * Returns status options
 *
 * @since 1.0.8
 * @return array
 */
function it_exchange_manual_purchases_get_default_status_options() {
	return array(
		'pending'   => _x( 'Pending', 'Transaction Status', 'LION' ),
		'Completed' => _x( 'Paid', 'Transaction Status', 'LION' ),
		'refunded'  => _x( 'Refunded', 'Transaction Status', 'LION' ),
		'voided'    => _x( 'Voided', 'Transaction Status', 'LION' ),
	);
}

add_filter( 'it_exchange_get_status_options_for_manual-purchases_transaction', 'it_exchange_manual_purchases_get_default_status_options' );

/**
 * Gets the interpretted transaction status from valid transaction statuses
 *
 * @since 1.0.8
 *
 * @param string $status the string of the stripe transaction
 *
 * @return string translaction transaction status
 */
function it_exchange_manual_purchases_addon_transaction_status_label( $status ) {

	switch ( $status ) {
		case 'Completed':
		case 'succeeded':
		case 'paid':
			return __( 'Paid', 'LION' );
			break;
		case 'refunded':
			return __( 'Refunded', 'LION' );
			break;
		case 'pending':
			return __( 'Pending', 'LION' );
			break;
		case 'voided':
			return __( 'Voided', 'LION' );
			break;
		default:
			return __( 'Unknown', 'LION' );
	}

}

add_filter( 'it_exchange_transaction_status_label_manual-purchases', 'it_exchange_manual_purchases_addon_transaction_status_label' );

/**
 * Adds iThemes Exchange User row action to users.php row actions
 *
 * @since 1.0.0
 *
 * @param string[] $actions
 * @param \WP_User $user_object
 *
 * @return array
 */
function it_exchange_manual_purchases_addon_user_row_actions( $actions, $user_object ) {
	$url   = admin_url( 'admin.php?page=it-exchange-add-manual-purchase' );
	$url   = esc_url( add_query_arg( 'customer', $user_object->ID, $url ) );
	$label = __( 'Add Product(s)', 'LION' );

	$actions['it_exchange_manual_purchase'] = "<a href=\"{$url}\" class=\"it-exchange-add-manual-purchase-for-user\">{$label}</a>";

	return $actions;
}

add_filter( 'user_row_actions', 'it_exchange_manual_purchases_addon_user_row_actions', 10, 2 );

/**
 * Adds iThemes Exchange Purchase button to User's Product View
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_manual_purchases_admin_user_products() {
	if ( empty( $_GET['user_id'] ) ) {
		$user_id = get_current_user_id();
	} else {
		$user_id = absint( $_GET['user_id'] );
	}

	$url   = admin_url( 'admin.php?page=it-exchange-add-manual-purchase' );
	$url   = esc_url( add_query_arg( 'customer', $user_id, $url ) );
	$label = __( 'Add Product(s)', 'LION' );

	echo "<p><a href='$url' class='button button-primary it-exchange-add-manual-purchase-for-user'>{$label}</a>";
}

add_action( 'it-exchange-after-admin-user-products', 'it_exchange_manual_purchases_admin_user_products' );

/**
 * Enqueues Membership scripts to WordPress Dashboard
 *
 * @since 1.0.0
 */
function it_exchange_manual_purchases_addon_admin_wp_enqueue_scripts() {

	if ( ! it_exchange_is_manual_purchases_page() ) {
		return;
	}

	wp_enqueue_script( 'it-exchange-manual-purchases-app', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/app.js', array(
		'it-exchange-rest',
		'it-exchange-select2',
        'jquery-select-to-autocomplete',
	) );
	wp_enqueue_style( 'it-exchange-select2' );
	wp_enqueue_style( 'dashicons' );

	it_exchange_add_inline_script(
		'it-exchange-rest',
		include IT_Exchange::$dir . '/lib/assets/templates/checkout.html'
	);

	it_exchange_add_inline_script(
		'it-exchange-rest',
		include IT_Exchange::$dir . '/lib/assets/templates/token-selector.html'
	);

	it_exchange_add_inline_script(
		'it-exchange-rest',
		include IT_Exchange::$dir . '/lib/assets/templates/visual-cc.html'
	);
	it_exchange_add_inline_script(
		'it-exchange-rest',
		include IT_Exchange::$dir . '/lib/assets/templates/address-form.html'
	);

	it_exchange_add_inline_script( 'it-exchange-manual-purchases-app', include __DIR__ . '/js/templates/Cart.html' );
	it_exchange_add_inline_script( 'it-exchange-manual-purchases-app', include __DIR__ . '/js/templates/CustomerSelect.html' );
	it_exchange_add_inline_script( 'it-exchange-manual-purchases-app', include __DIR__ . '/js/templates/ProductSelect.html' );
	it_exchange_add_inline_script( 'it-exchange-manual-purchases-app', include __DIR__ . '/js/templates/AddressSelect.html' );
	it_exchange_add_inline_script( 'it-exchange-manual-purchases-app', include __DIR__ . '/js/templates/Purchase.html' );
	it_exchange_add_inline_script( 'it-exchange-manual-purchases-app', include __DIR__ . '/js/templates/ShippingMethod.html' );
	it_exchange_add_inline_script( 'it-exchange-manual-purchases-app', include __DIR__ . '/js/templates/ModifyTotals.html' );

	it_exchange_preload_schemas( array(
		'payment-token',
		'customer',
		'cart',
		'cart-item-product',
		'cart-item-coupon',
		'cart-purchase',
		'address',
	) );

	$localize = array(
		'l10n' => array(
			'customerSelect' => array(
				'summaryLabel'       => __( 'Select a Customer', 'LION' ),
				'existing'           => __( 'Existing Customer', 'LION' ),
				'new'                => __( 'New Customer', 'LION' ),
				'searchInstructions' => __( 'Search by username or email address', 'LION' ),
				'select'             => _x( 'Select', 'Click to select the customer for this order.', 'LION' ),
				'username'           => __( 'Username', 'LION' ),
				'email'              => __( 'Email', 'LION' ),
				'firstName'          => __( 'First Name', 'LION' ),
				'lastName'           => __( 'Last Name', 'LION' ),
				'add'                => _x( 'Add', 'Create a new customer from the given fields.', 'LION' ),
			),
			'productSelect'  => array(
				'summaryLabel'       => __( 'Select Products', 'LION' ),
				'searchInstructions' => __( 'Search by product name', 'LION' ),
				'continue'           => _x( 'Continue', 'Continue to the next step of the transaction.', 'LION' ),
				'add'                => _x( 'Add', 'Add the product to the cart.', 'LION' ),
				'added'              => _x( 'Added', 'This product is already in the cart.', 'LION' ),
				'increase'           => __( 'Increase Quantity', 'LION' ),
				'decrease'           => __( 'Decrease Quantity', 'LION' ),
			),
			'addressSelect'  => array(
				'lastUsed' => __( 'Last Used:', 'LION' ),
				'billing'  => __( 'Billing Address', 'LION' ),
				'shipping' => __( 'Shipping Address', 'LION' ),
				'never'    => _x( 'Never', 'Address that was never used.', 'LION' ),
			),
			'purchase'       => array(
				'summaryLabel' => __( 'Payment', 'LION' ),
				'viewDetails'  => __( 'View Details', 'LION' ),
				'purchaseNote' => __( 'Purchase Note', 'LION' ),
			),
			'shippingMethod' => array(
				'summaryLabel' => __( 'Shipping Method', 'LION' ),
				'continue'     => _x( 'Continue', 'Continue to next step after selecting a shipping method.', 'LION' ),
			)
		)
	);

	if ( isset( $_GET['customer'] ) && $customer = it_exchange_get_customer( $_GET['customer'] ) ) {
		$cart   = it_exchange_create_cart_and_session( $customer, false );
		$caster = new \iThemes\Exchange\REST\Middleware\Empty_Attribute_Caster();

		$serializer       = new \iThemes\Exchange\REST\Route\v1\Cart\Serializer();
		$localize['cart'] = $caster->cast( $serializer->serialize( $cart ), $serializer->get_schema() );

		$serializer           = new \iThemes\Exchange\REST\Route\v1\Customer\Serializer();
		$localize['customer'] = $serializer->serialize( $customer, 'view' );
	}

	wp_localize_script( 'it-exchange-manual-purchases-app', 'manualPurchases', $localize );
}

add_action( 'admin_enqueue_scripts', 'it_exchange_manual_purchases_addon_admin_wp_enqueue_scripts' );

add_filter( 'it_exchange_preload_cart_item_types', function ( $preload ) {

	if ( it_exchange_is_manual_purchases_page() ) {
		$preload = true;
	}

	return $preload;
} );

/**
 * Enqueues Membership styles to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_manual_purchases_addon_admin_wp_enqueue_styles() {

	if ( it_exchange_is_manual_purchases_page() ) {
		wp_register_style( 'it-exchange-public-css', IT_Exchange::$url . '/lib/assets/styles/exchange.css' );
		wp_enqueue_style(
			'it-exchange-manual-purchases-addon-add-manual-purchase',
			ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-manual-purchase.css',
			array( 'it-exchange-select2', 'it-exchange-public-css', 'dashicons', 'it-exchange-autocomplete-style' )
		);
	}
}

add_action( 'admin_print_styles', 'it_exchange_manual_purchases_addon_admin_wp_enqueue_styles' );

/**
 * Redirects admin users away from core add post type screens for payments to our custom one.
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_manual_purchases_redirect_core_add_edit_screens() {
	$pagenow   = empty( $GLOBALS['pagenow'] ) ? false : $GLOBALS['pagenow'];
	$post_type = empty( $_GET['post_type'] ) ? false : $_GET['post_type'];

	if ( 'post-new.php' !== $pagenow ) {
		return;
	}

	// Redirect for add new screen
	if ( 'post-new.php' === $pagenow && 'it_exchange_tran' === $post_type ) {
		wp_safe_redirect( esc_url_raw( add_query_arg( array( 'page' => 'it-exchange-add-manual-purchase' ), get_admin_url() . 'admin.php' ) ) );
		die();
	}
}

add_action( 'admin_init', 'it_exchange_manual_purchases_redirect_core_add_edit_screens' );

/**
 * Register our pages as an exchange pages so that exchange CSS class is applied to admin body
 *
 * @since 1.0.0
 *
 * @param array $pages existing pages
 *
 * @return array
 */
function it_exchange_manual_purchases_register_exchange_admin_page( $pages ) {
	$pages[] = 'it-exchange-add-manual-purchase';

	return $pages;
}

add_filter( 'it_exchange_admin_pages', 'it_exchange_manual_purchases_register_exchange_admin_page' );

/**
 * This adds a menu item to the Exchange menu pointing to the WP All [post_type] table
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_manual_purchases_add_menu_item() {
	if ( it_exchange_is_manual_purchases_page() ) {
		$slug = 'it-exchange-add-manual-purchase';
		$func = 'it_exchange_manual_purchase_print_add_payment_screen';
		add_submenu_page( 'it-exchange', __( 'Add Manual Purchase', 'LION' ), __( 'Add Manual Purchase', 'LION' ), 'manage_options', $slug, $func );
	}
}

add_action( 'admin_menu', 'it_exchange_manual_purchases_add_menu_item' );

/**
 * Remove Custom Add Manual Purchase link from submenu
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_manual_purchases_remove_submenu_links() {
	if ( ! empty( $GLOBALS['submenu']['it-exchange'] ) ) {
		foreach ( $GLOBALS['submenu']['it-exchange'] as $key => $sub ) {
			if ( 'it-exchange-add-manual-purchase' === $sub[2] ) {
				// Remove the extra coupons submenu item
				unset( $GLOBALS['submenu']['it-exchange'][ $key ] );
				// Mark the primary coupons submenu item as current
				$GLOBALS['submenu_file'] = 'edit.php?post_type=it_exchange_tran';
			}
		}
	}
}

add_action( 'admin_head', 'it_exchange_manual_purchases_remove_submenu_links' );

/**
 * Returns a boolean. Is this transaction a status that warrants delivery of any products attached to it?
 *
 * @since 1.0.0
 *
 * @param boolean                 $cleared passed in through WP filter. Ignored here.
 * @param IT_Exchange_Transaction $transaction
 *
 * @return boolean
 */
function it_exchange_manual_purchases_transaction_is_cleared_for_delivery( $cleared, $transaction ) {
	return it_exchange_get_default_transaction_status( $transaction ) === 'Completed';
}

add_filter( 'it_exchange_manual-purchases_transaction_is_cleared_for_delivery', 'it_exchange_manual_purchases_transaction_is_cleared_for_delivery', 10, 2 );

/**
 * Process Manual Purchase Requests
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_manual_purchases_request() {
	if ( empty( $_POST['it-exchange-manual-purchase-add-payment-nonce'] ) ) {
		return;
	}

	if ( ! empty( $_POST['cancel'] ) ) {
		wp_safe_redirect( esc_url_raw( add_query_arg( array( 'post_type' => 'it_exchange_tran' ), 'edit.php' ) ) );
		die();
	}
}

add_action( 'admin_init', 'it_exchange_manual_purchases_request' );

/**
 * Display purchase notes for manual purchases
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_manual_purchases_after_payment_details() {
	$transaction = it_exchange_get_transaction( $GLOBALS['post'] );

	if ( ! $transaction->cart()->has_meta( 'manual_purchase_note' ) ) {
		return;
	}

	?>
    <div class="it-exchange-manual-purchase-note clearfix spacing-wrapper">
        <div class="it-exchange-manual-purchase-note-label">
			<?php _e( 'Manual Purchase Note', 'LION' ); ?>
        </div>
        <div class="it-exchange-manual-purchase-note-description">
			<?php echo esc_html( $transaction->cart()->get_meta( 'manual_purchase_note' ) ); ?>
        </div>
    </div>
	<?php
}

add_action( 'it_exchange_after_payment_details', 'it_exchange_manual_purchases_after_payment_details' );

/**
 * Register the cart meta for saving a manual purchases note.
 *
 * @since 2.0.0
 */
function it_exchange_register_manual_purchase_note_meta() {

	$dm = new IT_Exchange_Deprecated_Meta( 'post' );
	$dm->add(
		'_it_exchange_transaction_manual_purchase_description',
		'_it_exchange_cart_manual_purchase_note',
		'2.0.0'
	);

	$meta = new ITE_Cart_Meta( 'manual_purchase_note', array(
		'show_in_rest'     => true,
		'editable_in_rest' => function ( \iThemes\Exchange\REST\Auth\AuthScope $scope ) {
			return $scope->can( 'it_make_manual_purchase' );
		},
		'schema'           => array(
			'type'        => 'string',
			'description' => __( 'Add a note to manual purchases.', 'LION' )
		)
	) );

	ITE_Cart_Meta_Registry::register( $meta );
}

add_action( 'init', 'it_exchange_register_manual_purchase_note_meta' );

/**
 * Override the capability to show the Add New payment button.
 *
 * @since 1.0.0
 *
 * @return string
 */
function it_exchange_manual_purchases_tran_create_posts_capabilities() {
	return 'it_make_manual_purchase';
}

add_filter( 'it_exchange_tran_create_posts_capabilities', 'it_exchange_manual_purchases_tran_create_posts_capabilities' );