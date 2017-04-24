<?php
/*
 * Plugin Name: iThemes Exchange - Manual Purchases Add-on
 * Version: 2.0.0
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
 * Load the Manual Purchases plugin.
 *
 * @since 2.0.0
 */
function it_exchange_load_manual_purchases() {
	if ( ! function_exists( 'it_exchange_load_deprecated' ) || it_exchange_load_deprecated() ) {
		require_once dirname( __FILE__ ) . '/deprecated/exchange-addon-manual-purchases.php';
	} else {
		require_once dirname( __FILE__ ) . '/plugin.php';
	}
}

add_action( 'plugins_loaded', 'it_exchange_load_manual_purchases' );

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 *
 * @return void
 */
function ithemes_exchange_addon_it_exchange_manual_purchases_set_textdomain_updater_register( $updater ) {
	$updater->register( 'exchange-addon-manual-purchases', __FILE__ );
}

add_action( 'ithemes_updater_register', 'ithemes_exchange_addon_it_exchange_manual_purchases_set_textdomain_updater_register' );
require( dirname( __FILE__ ) . '/lib/updater/load.php' );
