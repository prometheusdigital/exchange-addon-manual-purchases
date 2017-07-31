<?php
/**
 * Exchange will build your add-on's settings page for you and link to it from our add-on
 * screen. You are free to link from it elsewhere as well if you'd like... or to not use our API
 * at all. This file has all the functions related to registering the page, printing the form, and saving
 * the options. This includes the wizard settings. Additionally, we use the Exchange storage API to
 * save / retreive options. Add-ons are not required to do this.
*/

/**
 * This is the function registered in the options array when it_exchange_register_addon was called for ManualPurchases
 *
 * It tells Exchange where to find the settings page
 *
 * @return void
*/
function it_exchange_manual_purchases_addon_settings_callback() {
    $IT_Exchange_ManualPurchases_Add_On = new IT_Exchange_ManualPurchases_Add_On();
    $IT_Exchange_ManualPurchases_Add_On->print_settings_page();
}

/**
 * Outputs wizard settings for ManualPurchases
 *
 * Exchange allows add-ons to add a small amount of settings to the wizard.
 * You can add these settings to the wizard by hooking into the following action:
 * - it_exchange_print_[addon-slug]_wizard_settings
 * Exchange exspects you to print your fields here.
 *
 * @since 1.0.0
 * @todo make this better, probably
 * @param object $form Current IT Form object
 * @return void
*/
function it_exchange_print_manual_purchase_wizard_settings( $form ) {
    $IT_Exchange_ManualPurchases_Add_On = new IT_Exchange_ManualPurchases_Add_On();
    $settings = it_exchange_get_option( 'addon_manual_purchase', true );
    $form_values = ITUtility::merge_defaults( ITForm::get_post_data(), $settings );
    $hide_if_js =  it_exchange_is_addon_enabled( 'manual_purchase' ) ? '' : 'hide-if-js';
    ?>
    <div class="field manual_purchase-wizard <?php echo $hide_if_js; ?>">
    <?php if ( empty( $hide_if_js ) ) { ?>
        <input class="enable-manual_purchase" type="hidden" name="it-exchange-transaction-methods[]" value="manual_purchase" />
    <?php } ?>
    <?php $IT_Exchange_ManualPurchases_Add_On->get_form_table( $form, $form_values ); ?>
    </div>
    <?php
}
add_action( 'it_exchange_print_manual_purchase_wizard_settings', 'it_exchange_print_manual_purchase_wizard_settings' );

/**
 * Saves ManualPurchases settings when the Wizard is saved
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_save_manual_purchase_wizard_settings( $errors ) {
    if ( ! empty( $errors ) )
        return $errors;

    $IT_Exchange_ManualPurchases_Add_On = new IT_Exchange_ManualPurchases_Add_On();

}

/**
 * Class for ManualPurchases
 * @since 1.0.0
*/
class IT_Exchange_ManualPurchases_Add_On {

    /**
     * @var boolean $_is_admin true or false
     * @since 1.0.0
    */
    var $_is_admin;

    /**
     * @var string $_current_page Current $_GET['page'] value
     * @since 1.0.0
    */
    var $_current_page;

    /**
     * @var string $_current_add_on Current $_GET['add-on-settings'] value
     * @since 1.0.0
    */
    var $_current_add_on;

    /**
     * @var string $status_message will be displayed if not empty
     * @since 1.0.0
    */
    var $status_message;

    /**
     * @var string $error_message will be displayed if not empty
     * @since 1.0.0
    */
    var $error_message;

    /**
     * Set up the class
     *
     * @since 1.0.0
    */
    function __construct() {
        $this->_is_admin       = is_admin();
        $this->_current_page   = empty( $_GET['page'] ) ? false : $_GET['page'];
        $this->_current_add_on = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];

        if ( ! empty( $_POST ) && $this->_is_admin && 'it-exchange-addons' == $this->_current_page && 'manual_purchase' == $this->_current_add_on ) {
            add_action( 'it_exchange_save_add_on_settings_manual_purchase', array( $this, 'save_settings' ) );
            do_action( 'it_exchange_save_add_on_settings_manual_purchase' );
        }
    }

    /**
     * Prints settings page
     *
     * @since 1.0.0
    */
    function print_settings_page() {
        $settings = it_exchange_get_option( 'addon_manual_purchase', true );
        $form_values  = empty( $this->error_message ) ? $settings : ITForm::get_post_data();
        $form_options = array(
            'id'      => apply_filters( 'it_exchange_add_on_manual_purchase', 'it-exchange-add-on-manual_purchase-settings' ),
            'enctype' => apply_filters( 'it_exchange_add_on_manual_purchase_settings_form_enctype', false ),
            'action'  => 'admin.php?page=it-exchange-addons&add-on-settings=manual_purchase',
        );
        $form         = new ITForm( $form_values, array( 'prefix' => 'it-exchange-add-on-manual_purchase' ) );

        if ( ! empty ( $this->status_message ) )
            ITUtility::show_status_message( $this->status_message );
        if ( ! empty( $this->error_message ) )
            ITUtility::show_error_message( $this->error_message );

        ?>
        <div class="wrap">
            <?php screen_icon( 'it-exchange' ); ?>
            <h2><?php _e( 'ManualPurchases Settings', 'LION' ); ?></h2>

            <?php do_action( 'it_exchange_manual-purchase_settings_page_top' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_top' ); ?>
            <?php $form->start_form( $form_options, 'it-exchange-manual_purchase-settings' ); ?>
                <?php do_action( 'it_exchange_manual_purchase_settings_form_top' ); ?>
                <?php $this->get_form_table( $form, $form_values ); ?>
                <?php do_action( 'it_exchange_manual_purchase_settings_form_bottom' ); ?>
                <p class="submit">
                    <?php $form->add_submit( 'submit', array( 'value' => __( 'Save Changes', 'LION' ), 'class' => 'button button-primary button-large' ) ); ?>
                </p>
            <?php $form->end_form(); ?>
            <?php do_action( 'it_exchange_manual_purchase_settings_page_bottom' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
        </div>
        <?php
    }

    /**
     * Builds Settings Form Table
	 *
	 * @param ITForm $form
	 * @param array $settings
     *
     * @since 1.0.0
     */
    function get_form_table( $form, $settings = array() ) {
        $settings = it_exchange_get_option( 'addon_manual_purchases', true );

        if ( !empty( $settings ) ) {
            foreach ( $settings as $key => $var ) {
                $form->set_option( $key, $var );
			}
		}

        if ( ! empty( $_GET['page'] ) && 'it-exchange-setup' == $_GET['page'] ) : ?>
            <h3><?php _e( 'ManualPurchases', 'LION' ); ?></h3>
        <?php endif; ?>
        <div class="it-exchange-addon-settings it-exchange-manual_purchase-addon-settings">
            <p>
                <?php _e( 'To get ManualPurchases set up for use with Exchange, you\'ll need to add the following information from your ManualPurchases account.', 'LION' ); ?>
            </p>
            <p>
                <?php _e( 'Don\'t have a ManualPurchases account yet?', 'LION' ); ?> <a href="http://www.manual_purchase.com/" target="_blank"><?php _e( 'Go set one up here', 'LION' ); ?></a>.
            </p>
            <h4>License Key</h4>

            <?php
                $exchangewp_manual_purchase_options = get_option( 'it-storage-exchange_addon_manual_purchase' );
                $license = $exchangewp_manual_purchase_options['manual_purchase_license'];
                // var_dump($license);
                $exstatus = trim( get_option( 'exchange_manual_purchase_license_status' ) );
                // var_dump($exstatus);
             ?>
            <p>
              <label class="description" for="exchange_manual_purchase_license_key"><?php _e('Enter your license key'); ?></label>
              <!-- <input id="manual_purchase_license" name="it-exchange-add-on-manual_purchase-manual_purchase_license" type="text" value="<?php #esc_attr_e( $license ); ?>" /> -->
              <?php $form->add_text_box( 'manual_purchase_license' ); ?>
              <span>
                <?php if( $exstatus !== false && $exstatus == 'valid' ) { ?>
    							<span style="color:green;"><?php _e('active'); ?></span>
    							<?php wp_nonce_field( 'exchange_manual_purchase_nonce', 'exchange_manual_purchase_nonce' ); ?>
    							<input type="submit" class="button-secondary" name="exchange_manual_purchase_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
    						<?php } else {
    							wp_nonce_field( 'exchange_manual_purchase_nonce', 'exchange_manual_purchase_nonce' ); ?>
    							<input type="submit" class="button-secondary" name="exchange_manual_purchase_license_activate" value="<?php _e('Activate License'); ?>"/>
    						<?php } ?>
              </span>
            </p>
        <?php
    }

    /**
     * Save settings
     *
     * @since 1.0.0
     * @return void
    */
    function save_settings() {
        $defaults = it_exchange_get_option( 'addon_manual_purchase' );
        $new_values = wp_parse_args( ITForm::get_post_data(), $defaults );

        // Check nonce
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-manual_purchase-settings' ) ) {
            $this->error_message = __( 'Error. Please try again', 'LION' );
            return;
        }

        $errors = apply_filters( 'it_exchange_add_on_manual_purchase_validate_settings', $this->get_form_errors( $new_values ), $new_values );
        if ( ! $errors && it_exchange_save_option( 'addon_manual_purchase', $new_values ) ) {
            ITUtility::show_status_message( __( 'Settings saved.', 'LION' ) );
        } else if ( $errors ) {
            $errors = implode( '<br />', $errors );
            $this->error_message = $errors;
        } else {
            $this->status_message = __( 'Settings not saved.', 'LION' );
        }

        // This is for all things licensing check
        // listen for our activate button to be clicked
      	if( isset( $_POST['exchange_manual_purchase_license_activate'] ) ) {

      		// run a quick security check
      	 	if( ! check_admin_referer( 'exchange_manual_purchase_nonce', 'exchange_manual_purchase_nonce' ) )
      			return; // get out if we didn't click the Activate button

      		// retrieve the license from the database
      		// $license = trim( get_option( 'exchange_manual_purchase_license_key' ) );
          $exchangewp_manual_purchase_options = get_option( 'it-storage-exchange_addon_manual_purchase' );
          $license = trim( $exchangewp_manual_purchase_options['manual_purchase_license'] );

      		// data to send in our API request
      		$api_params = array(
      			'edd_action' => 'activate_license',
      			'license'    => $license,
      			'item_name'  => urlencode( 'manual_purchase' ), // the name of our product in EDD
      			'url'        => home_url()
      		);

      		// Call the custom API.
      		$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

      		// make sure the response came back okay
      		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

      			if ( is_wp_error( $response ) ) {
      				$message = $response->get_error_message();
      			} else {
      				$message = __( 'An error occurred, please try again.' );
      			}

      		} else {

      			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

      			if ( false === $license_data->success ) {

      				switch( $license_data->error ) {

      					case 'expired' :

      						$message = sprintf(
      							__( 'Your license key expired on %s.' ),
      							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
      						);
      						break;

      					case 'revoked' :

      						$message = __( 'Your license key has been disabled.' );
      						break;

      					case 'missing' :

      						$message = __( 'Invalid license.' );
      						break;

      					case 'invalid' :
      					case 'site_inactive' :

      						$message = __( 'Your license is not active for this URL.' );
      						break;

      					case 'item_name_mismatch' :

      						$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), 'manual_purchase' );
      						break;

      					case 'no_activations_left':

      						$message = __( 'Your license key has reached its activation limit.' );
      						break;

      					default :

      						$message = __( 'An error occurred, please try again.' );
      						break;
      				}

      			}

      		}

      		// Check if anything passed on a message constituting a failure
      		if ( ! empty( $message ) ) {
      			$base_url = admin_url( 'admin.php?page=it-exchange-addons&add-on-settings=' . 'manual_purchase' );
      			// $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

      			// wp_redirect( $base_url );
      			// exit();
            return;
      		}

      		//$license_data->license will be either "valid" or "invalid"
      		update_option( 'exchange_manual_purchase_license_status', $license_data->license );
      		// wp_redirect( admin_url( 'admin.php?page=it-exchange-addons&add-on-settings=' . 'manual_purchase' ) );
      		// exit();
          return;
      	}

        // deactivate here
        // listen for our activate button to be clicked
      	if( isset( $_POST['exchange_manual_purchase_license_deactivate'] ) ) {

      		// run a quick security check
      	 	if( ! check_admin_referer( 'exchange_manual_purchase_nonce', 'exchange_manual_purchase_nonce' ) )
      			return; // get out if we didn't click the Activate button

      		// retrieve the license from the database
      		// $license = trim( get_option( 'exchange_manual_purchase_license_key' ) );

          $exchangewp_manual_purchase_options = get_option( 'it-storage-exchange_addon_manual_purchase' );
          $license = $exchangewp_manual_purchase_options['manual_purchase_license'];


      		// data to send in our API request
      		$api_params = array(
      			'edd_action' => 'deactivate_license',
      			'license'    => $license,
      			'item_name'  => urlencode( 'manual_purchase' ), // the name of our product in EDD
      			'url'        => home_url()
      		);
      		// Call the custom API.
      		$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

      		// make sure the response came back okay
      		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

      			if ( is_wp_error( $response ) ) {
      				$message = $response->get_error_message();
      			} else {
      				$message = __( 'An error occurred, please try again.' );
      			}

      			$base_url = admin_url( 'admin.php?page=it-exchange-addons&add-on-settings=' . 'manual_purchase' );
      			$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

      			// wp_redirect( $redirect );
      			// exit();
            return;
      		}

      		// decode the license data
      		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
      		// $license_data->license will be either "deactivated" or "failed"
      		if( $license_data->license == 'deactivated' ) {
      			delete_option( 'exchange_manual_purchase_license_status' );
      		}

      		// wp_redirect( admin_url( 'admin.php?page=it-exchange-addons&add-on-settings=' . 'manual_purchase' ) );
      		// exit();
          return;

      	}
    }

    /**
     * Save wizard settings
     *
     * @since 1.0.0
     * @return void|array Void or Error message array
    */
    function save_wizard_settings() {
        if ( empty( $_REQUEST['it_exchange_settings-wizard-submitted'] ) )
            return;

		$defaults = it_exchange_manual_purchase_addon_default_settings( array() );

        $manual_purchase_settings = array(
			'manual_purchase_sale_method' => $defaults[ 'manual_purchase_sale_method' ],
			'manual_purchase_purchase_button_label' => $defaults[ 'manual_purchase_purchase_button_label' ]
		);

        // Fields to save
        $fields = array_keys( $defaults );

        $default_wizard_manual_purchase_settings = apply_filters( 'default_wizard_manual_purchase_settings', $fields );

        foreach( $default_wizard_manual_purchase_settings as $var ) {
            if ( isset( $_REQUEST['it_exchange_settings-' . $var] ) ) {
                $manual_purchase_settings[$var] = $_REQUEST['it_exchange_settings-' . $var];
            }
        }

        $settings = wp_parse_args( $manual_purchase_settings, it_exchange_get_option( 'addon_manual_purchase' ) );

        if ( $error_msg = $this->get_form_errors( $settings ) ) {

            return $error_msg;

        } else {
            it_exchange_save_option( 'addon_manual_purchase', $settings );
            $this->status_message = __( 'Settings Saved.', 'LION' );
        }

        return;
    }


}
