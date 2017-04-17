<?php
/**
 * Load the manual purchases add-on.
 *
 * @since   2.0.0
 * @license GPLv2
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
		'labels'            => array(
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
 * @uses  load_plugin_textdomain()
 * @since 1.0.0
 * @return void
 */
function it_exchange_manual_purchases_set_textdomain() {
	load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}

it_exchange_manual_purchases_set_textdomain();