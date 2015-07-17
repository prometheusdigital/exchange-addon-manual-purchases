<?php
/**
 * This adds a menu item to the Exchange menu pointing to the WP All [post_type] table
 *
 * @since 1.0.0
 *
 * @return void
*/


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
 * @return void
*/
function it_exchange_manual_purchases_get_default_status_options() {
	$options = array(
		'pending'   => _x( 'Pending', 'Transaction Status', 'LION' ),
		'Completed' => _x( 'Paid', 'Transaction Status', 'LION' ),
		'refunded'  => _x( 'Refunded', 'Transaction Status', 'LION' ),
		'voided'    => _x( 'Voided', 'Transaction Status', 'LION' ),
	);
	return $options;
}
add_filter( 'it_exchange_get_status_options_for_manual-purchases_transaction', 'it_exchange_manual_purchases_get_default_status_options' );

/**
 * Gets the interpretted transaction status from valid transaction statuses
 *
 * @since 1.0.8 
 *
 * @param string $status the string of the stripe transaction
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
 * @return void
*/
function it_exchange_manual_purchases_addon_user_row_actions( $actions, $user_object ) {
	add_thickbox();
	$args = array(
		'action'    => 'it-exchange-add-manual-purchase-for-user',
		'userid'    => $user_object->ID,
		'TB_iframe' => 'true',
		'width'     => '800',
		'height'    => '600',
	);
	$url = add_query_arg( $args, get_admin_url() . 'admin-ajax.php' ); 
	$actions['it_exchange_manual_purchase'] = '<a href="' . esc_url( $url ) . '" class="thickbox it-exchange-add-manual-purchase-for-user">' . __( 'Add Product(s)', 'LION' ) . '</a>';

	return $actions;
}
add_filter( 'user_row_actions', 'it_exchange_manual_purchases_addon_user_row_actions', 10, 2 );

/**
 * Adds iThemes Exchange Purchase button to User's Product View
 *
 * @since 1.0.0
 * @return void
*/
function it_exchange_manual_purchases_admin_user_products() {
	if ( empty( $_GET['user_id'] ) )
	    $user_id = get_current_user_id();
	else
	    $user_id = $_GET['user_id'];
	    
	if ( !empty( $user_id ) ) {
		add_thickbox();
		$args = array(
			'action'    => 'it-exchange-add-manual-purchase-for-user',
			'userid'    => $user_id,
			'TB_iframe' => 'true',
			'width'     => '800',
			'height'    => '600',
		);
		$url = add_query_arg( $args, get_admin_url() . 'admin-ajax.php' ); 
		$output = '<p><a href="' . esc_url( $url ) . '" class="button button-primary button-large thickbox it-exchange-add-manual-purchase-for-user">' . __( 'Add Product(s)', 'LION' ) . '</a></p>';
	
		echo $output;
	}
}
add_action( 'it-exchange-after-admin-user-products', 'it_exchange_manual_purchases_admin_user_products' );

/**
 * Enqueues Membership scripts to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix WordPress passed variable
 * @return void
*/
function it_exchange_manual_purchases_addon_admin_wp_enqueue_scripts( $hook_suffix ) {
	if ( ( !empty( $_GET ) && !empty( $_GET['page'] ) && 'it-exchange-add-manual-purchase' === $_GET['page'] ) 
		|| 'users.php' == $hook_suffix ) {
		wp_enqueue_script( 'it-exchange-manual-purchases-addon-add-manual-purchase', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/add-manual-purchase.js', array( 'jquery-select-to-autocomplete' ) );
	} else if ( 'user.php-ithemes-manual-purchase-thickbox' === $hook_suffix ) {
		wp_enqueue_script( 'it-exchange-manual-purchases-addon-add-manual-purchase', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/add-manual-purchase.js', array( 'jquery-select-to-autocomplete' ) );
	}
}
add_action( 'admin_enqueue_scripts', 'it_exchange_manual_purchases_addon_admin_wp_enqueue_scripts' );

/**
 * Enqueues Membership styles to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_manual_purchases_addon_admin_wp_enqueue_styles() {	
	global $hook_suffix;
	if ( ( !empty( $_GET ) && !empty( $_GET['page'] ) && 'it-exchange-add-manual-purchase' === $_GET['page'] ) 
		|| 'users.php' == $hook_suffix ) {
		wp_enqueue_style( 'it-exchange-manual-purchases-addon-add-manual-purchase', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-manual-purchase.css' );
	} else if ( 'user.php-ithemes-manual-purchase-thickbox' === $hook_suffix ) {
		wp_enqueue_style( 'it-exchange-manual-purchases-addon-add-manual-purchase', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-manual-purchase.css' );
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
	$post_id   = empty( $_GET['post'] ) ? false : $_GET['post'];
	$action    = empty( $_GET['action'] ) ? false : $_GET['action'];

	if ( ! $pagenow || 'post-new.php' != $pagenow )
		return;

	// Redirect for add new screen
	if ( 'post-new.php' == $pagenow && 'it_exchange_tran' == $post_type ) {
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
	if ( ! empty( $_GET['page'] ) && 'it-exchange-add-manual-purchase' == $_GET['page'] ) {
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
		foreach( $GLOBALS['submenu']['it-exchange'] as $key => $sub ) {
			if ( 'it-exchange-add-manual-purchase' == $sub[2] ) {
				// Remove the extra coupons submenu item
				unset( $GLOBALS['submenu']['it-exchange'][$key] );
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
 * @param boolean $cleared passed in through WP filter. Ignored here.
 * @param object $transaction
 * @return boolean
*/
function it_exchange_manual_purchases_transaction_is_cleared_for_delivery( $cleared, $transaction ) {
	$valid_stati = array( 'Completed' );
	return in_array( it_exchange_get_transaction_status( $transaction ), $valid_stati );
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
	if ( empty( $_POST['it-exchange-manual-purchase-add-payment-nonce'] ) )
		return;
	
	if ( !empty( $_POST['cancel'] ) ) {
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
	global $post;
	$transaction = it_exchange_get_transaction( $post );
	if ( 'manual-purchases' === $transaction->transaction_method ) {
	?>
	<div class="it-exchange-manual-purchase-note clearfix spacing-wrapper">
		<div class="it-exchange-manual-purchase-note-label">
			<?php _e( 'Manual Purchase Note', 'LION' ); ?>
		</div>
		<div class="it-exchange-manual-purchase-note-description">
			<?php echo $transaction->get_transaction_meta( 'manual_purchase_description', true ); ?>
		</div>
	</div>
	<?php
	}
}
add_action( 'it_exchange_after_payment_details', 'it_exchange_manual_purchases_after_payment_details' );
