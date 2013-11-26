<?php

function it_exchange_manual_purchase_print_add_payment_screen() {
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
		screen_icon( 'it-exchange-manual-purchases' );
		?>
		<h2><?php _e( 'Add Manual Purchase', 'LION' ); ?></h2>
		<div class="it-exchange-add-manual-purchase">
			<div class="it-exchange-add-manual-purchase-user-options">
				<div class="it-exchange-add-manual-purchase-user-option-existing">
				<h3><?php _e( 'Select an Existing Customer', 'LION' ); ?></h3>
					<div class="it-exchange-manual-purchase-existing-username">
					<label for="it-exchange-manual-purchase-existing-username"><?Php _e( 'Username', 'LION' ); ?></label>
					<?php 
						$args = array(
							'fields' => array( 'ID', 'user_login' )
						);
						$users = get_users( $args );
						
						echo '';
						echo '<select id="it-exchange-manual-purchase-existing-username" name="it-exchange-manual-purchase-existing-username">';
						echo '<option value></option>';
						foreach( $users as $user ) {
							$user->ID = (int) $user->ID;
							echo '<option value="' . $user->ID . '">' . esc_html( $user->user_login ) . '</option>';
						}
						echo '</select>';
					?>
					</div>
				</div>
				<div class="it-exchange-add-manual-purchase-user-option-or">
				<h4><?php _e( 'OR', 'LION' ); ?></h4>
				</div>
				<div class="it-exchange-add-manual-purchase-user-option-add-new">
				<h3><?php _e( 'Add a New Customer', 'LION' ); ?></h3>
					<label for="it-exchange-manual-purchase-new-username"><?Php _e( 'Username', 'LION' ); ?></label><input id="it-exchange-manual-purchase-new-username" type="text" value="" name="it-exchange-manual-purchase-new-username" />
					<label for="it-exchange-manual-purcahse-new-email"><?php _e( 'Email', 'LION' ); ?></label><input id="it-exchange-manual-purcahse-new-email" type="text" value="" name="it-exchange-manual-purcahse-new-email" />
				</div>
			</div>
			<div class="it-exchange-add-manual-purcahse-product-options">
				<h3><?php _e( 'Select Products', 'LION' ); ?></h3>
				<?php
				$args = array(
					'product_type' => !empty( $_GET['product_type'] ) ? $_GET['product_type'] : '',
				);
				$products = it_exchange_get_products( $args );
				
				foreach( $products as $product ) {
					echo '<p>';
					echo '<input id="it-exchange-add-purchase-product-' . $product->ID . '" class="it-exchange-add-pruchase-product" type="checkbox" value="' . $product->ID . '" name="it-exchange-manual-purchase-products[]" />';
					echo '<label for="it-exchange-add-purchase-product-' . $product->ID . '" >' . $product->post_title . '</label>';
					echo '</p>';
				}
				?>
				<label for="it-exchange-add-manual-purchase-total-paid"><?Php _e( 'Total Paid', 'LION' ); ?></label><input id="it-exchange-add-manual-purchase-total-paid" type="text" value="" name="it-exchange-add-manual-purchase-total-paid" />
				<div class="field">
					<input type="submit" id="cancel" name="cancel" value="Cancel" class="button-large button">										<input type="submit" id="submit" name="submit" value="Save" class="button-large button-primary button">
				</div>
			</div>
		</div>
	</div>
	<?php
	
}