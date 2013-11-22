<?php
/*
 * Plugin Name: iThemes Exchange - Manual Purchases Add-on
 * Version: 1.0.0
 * Description: Adds manual purchases functionality to iThemes Exchange
 * Plugin URI: http://ithemes.com/exchange/manual-purchases/
 * Author: iThemes
 * Author URI: http://ithemes.com
 * iThemes Package: exchange-addon-manual-purchases
 
 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 *
*/

/**
 * This registers our plugin as a customer pricing addon
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_register_manual_purchases_addon() {
	$options = array(
		'name'              => __( 'Manual Purchases', 'LION' ),
		'description'       => __( 'Add transactions to your customers, manually.', 'LION' ),
		'author'            => 'iThemes',
		'author_url'        => 'http://ithemes.com/exchange/manual-purchases/',
		'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/manualpurchases50px.png' ),
		'file'              => dirname( __FILE__ ) . '/init.php',
		'category'          => 'transaction-method',
		'basename'          => plugin_basename( __FILE__ ),
		'labels'      => array(
			'singular_name' => __( 'Manual Purchases', 'LION' ),
		),
		'settings-callback' => 'it_exchange_manual_purchases_settings_callback',
	);
	it_exchange_register_addon( 'manual-purchases', $options );
}
add_action( 'it_exchange_register_addons', 'it_exchange_register_manual_purchases_addon' );

/**
 * Loads the translation data for WordPress
 *
 * @uses load_plugin_textdomain()
 * @since 1.0.0
 * @return void
*/
/*
function it_exchange_manual_purchases_set_textdomain() {
	load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__  ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'it_exchange_manual_purchases_set_textdomain' );
*/

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 * @return void
*/
/*
function ithemes_exchange_addon_it_exchange_manual_purchases_set_textdomain_updater_register( $updater ) { 
	    $updater->register( 'exchange-addon-manual-purchases', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_exchange_addon_it_exchange_manual_purchases_set_textdomain_updater_register' );
require( dirname( __FILE__ ) . '/lib/updater/load.php' );
*/


function it_exchange_manual_purchases_tran_create_posts_capabilities( $cap ) {
	return 'edit_posts';
}
add_filter( 'it_exchange_tran_create_posts_capabilities', 'it_exchange_manual_purchases_tran_create_posts_capabilities' );