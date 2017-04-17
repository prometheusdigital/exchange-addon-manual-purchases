<?php
/**
 * iThemes Exchange Customer Purchases Add-on
 * @package exchange-addon-customer-pricing
 * @since 1.0.0
*/
	
/**
 * The following file contains utility functions specific to our customer pricing add-on
 * If you're building your own addon, it's likely that you will
 * need to do similar things.
*/
require_once __DIR__ . '/lib/addon-functions.php';

/**
 * Exchange Add-ons require several hooks in order to work properly. 
 * We've placed them all in one file to help add-on devs identify them more easily
*/
require_once __DIR__ . '/lib/required-hooks.php';

/**
 * We decided to place all AJAX hooked functions into this file, just for ease of use
*/
require_once __DIR__ . '/lib/addon-ajax-hooks.php';