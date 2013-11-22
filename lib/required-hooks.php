<?php

/**
 * This adds a menu item to the Exchange menu pointing to the WP All [post_type] table
 *
 * @since 1.0.0
 *
 * @return void
*/

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
		$func = 'it_exchange_manual_purchases_print_add_purchase_screen';
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

function it_exchange_manual_purchases_print_add_purchase_screen() {
	// Setup add / edit variables
	$form_action = add_query_arg( array( 'page' => 'it-exchange-add-manual-purchase' ), get_admin_url() . 'admin.php' );

	$errors = it_exchange_get_messages( 'error' );
	if ( ! empty( $errors ) ) {
		foreach( $errors as $error ) {
			ITUtility::show_error_message( $error );
		}
	} else if ( ! empty( $_GET['added'] ) ) {
		ITUtility::show_status_message( __( 'Purchase Added', 'LION' ) );
	}

	$form_values  = empty( $values ) ? ITForm::get_post_data() : $values;
	$form_values  = ! empty( $errors ) ? ITForm::get_post_data() : $form_values;
	$form         = new ITForm( $form_values, array( 'prefix' => 'it-exchange-manual-purchase' ) );
	$form_options = array(
		'id'      => apply_filters( 'it-exchange-manual-purchase_form_id', 'it-exchange-manual-purchase' ),
		'enctype' => apply_filters( 'it-exchange-manual-purchase_enctype', false ),
		'action'  => $form_action,
	);
	?>
	<div class="wrap">
		<?php
		screen_icon( 'it-exchange-payments' );
		echo '<h2>' . __( 'Add Manual Purchase', 'LION' ) . '</h2>';
		$form->start_form( $form_options, 'it-exchange-manual-purchase-add-edit-coupon' );
		?>
		<h1>This is where the magic happens... p.s. I hate you glenn</h1>
		<?php
		$form->end_form();
		?>
	</div>
	<?php
}