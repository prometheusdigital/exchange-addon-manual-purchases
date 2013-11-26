<?php

/**
 * This adds a menu item to the Exchange menu pointing to the WP All [post_type] table
 *
 * @since 1.0.0
 *
 * @return void
*/

/**
 * Enqueues Membership scripts to WordPress Dashboard
 *
 * @since 1.0.0
 *
 * @param string $hook_suffix WordPress passed variable
 * @return void
*/
function it_exchange_manual_purchases_addon_admin_wp_enqueue_scripts( $hook_suffix ) {
	if ( !empty( $_GET ) && !empty( $_GET['page'] ) && 'it-exchange-add-manual-purchase' === $_GET['page'] ) {
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
	if ( isset( $_GET ) && !empty( $_GET['page'] )  && 'it-exchange-add-manual-purchase' !== $_GET['page'] ) {
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
		wp_safe_redirect( add_query_arg( array( 'page' => 'it-exchange-add-manual-purchase' ), get_admin_url() . 'admin.php' ) );
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
		add_submenu_page( 'it-exchange', __( 'Add Manual Purchase', 'LION' ), __( 'Add Manual Purchase', 'LION' ), 'update_plugins', $slug, $func );
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