<?php
/*
 * Plugin Name: ExchangeWP - Manual Purchases Add-on
 * Version: 1.3.3
 * Description: Adds manual purchases functionality to ExchangeWP
 * Plugin URI: https://exchangewp.com/downloads/manual-purchases/
 * Author: ExchangeWP
 * Author URI: https://exchangewp.com
 * ExchangeWP Package: exchange-addon-manual-purchases

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
	$versions         = get_option( 'it-exchange-versions', false );
	$current_version  = empty( $versions['current'] ) ? false: $versions['current'];

	if ( version_compare( $current_version, '1.7.3', '>=' ) ) {
		$options = array(
			'name'              => __( 'Manual Purchases', 'LION' ),
			'description'       => __( 'Add transactions to your customers, manually.', 'LION' ),
			'author'            => 'ExchangeWP',
			'author_url'        => 'https://exchangewp.com/downloads/manual-purchases/',
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
	} else {
		add_action( 'admin_notices', 'it_exchange_add_manual_purchases_nag' );
	}

}
add_action( 'it_exchange_register_addons', 'it_exchange_register_manual_purchases_addon' );

/**
 * Adds the Manual Purchases nag if not on the correct version of iThemes Exchange
 *
 * @since 1.0.0
 * @return void
*/
function it_exchange_add_manual_purchases_nag() {
	?>
	<div id="it-exchange-manual-purchases-nag" class="it-exchange-nag">
		<?php
		printf( __( 'To use the Manual Purchases add-on for iThemes Exchange, you must be using iThemes Exchange version 1.7.3 or higher. <a href="%s">Please update now</a>.', 'LION' ), admin_url( 'update-core.php' ) );
		?>
	</div>
	<script type="text/javascript">
		jQuery( document ).ready( function() {
			if ( jQuery( '.wrap > h2' ).length == '1' ) {
				jQuery("#it-exchange-manual-purchases-nag").insertAfter( '.wrap > h2' ).addClass( 'after-h2' );
			}
		});
	</script>
    <?php
}

/**
 * Loads the translation data for WordPress
 *
 * @uses load_plugin_textdomain()
 * @since 1.0.0
 * @return void
*/
function it_exchange_manual_purchases_set_textdomain() {
	load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__  ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'it_exchange_manual_purchases_set_textdomain' );

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 * @return void
*/
function ithemes_exchange_addon_it_exchange_manual_purchases_set_textdomain_updater_register( $updater ) {
	    $updater->register( 'exchange-addon-manual-purchases', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_exchange_addon_it_exchange_manual_purchases_set_textdomain_updater_register' );
// require( dirname( __FILE__ ) . '/lib/updater/load.php' );


function it_exchange_manual_purchases_tran_create_posts_capabilities( $cap ) {
	return 'edit_posts';
}
add_filter( 'it_exchange_tran_create_posts_capabilities', 'it_exchange_manual_purchases_tran_create_posts_capabilities' );

if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) )  {
	require_once 'EDD_SL_Plugin_Updater.php';
}

function exchange_manual_purchases_plugin_updater() {

	// retrieve our license key from the DB
	// this is going to have to be pulled from a seralized array to get the actual key.
	// $license_key = trim( get_option( 'exchange_manual_purchases_license_key' ) );
	$exchangewp_manual_purchases_options = get_option( 'it-storage-exchange_addon_manual_purchases' );
	$license_key = $exchangewp_manual_purchases_options['manual_purchases_license'];

	// setup the updater
	$edd_updater = new EDD_SL_Plugin_Updater( 'https://exchangewp.com', __FILE__, array(
			'version' 		=> '1.2.2', 				// current version number
			'license' 		=> $license_key, 		// license key (used get_option above to retrieve from DB)
			'item_name' 	=> 'manual-purchases', 	  // name of this plugin
			'author' 	  	=> 'ExchangeWP',    // author of this plugin
			'url'       	=> home_url(),
			'wp_override' => true,
			'beta'		  	=> false
		)
	);
	// var_dump($edd_updater);
	// die();

}

add_action( 'admin_init', 'exchange_manual_purchases_plugin_updater', 0 );
