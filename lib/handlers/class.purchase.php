<?php
/**
 * Manual Purchases purchase Request Handler.
 *
 * @since   2.0.0
 * @license GPLv2
 */

/**
 * Class ITE_Manual_Purchases_Purchase_Request_Handler
 */
class ITE_Manual_Purchases_Purchase_Request_Handler extends ITE_Purchase_Request_Handler {

	/**
	 * @inheritDoc
	 *
	 * @param ITE_Gateway_Purchase_Request $request
	 */
	public function handle( $request ) {

		if ( ! static::can_handle( $request::get_name() ) ) {
			throw new InvalidArgumentException();
		}

		if ( ! wp_verify_nonce( $request->get_nonce(), $this->get_nonce_action() ) ) {
			$request->get_cart()->get_feedback()->add_error(
				__( 'Purchase failed. Unable to verify security token.', 'it-l10n-ithemes-exchange' )
			);

			return null;
		}

		$method_id = it_exchange_manual_purchases_addon_transaction_uniqid();

		$txn_id = it_exchange_add_transaction(
			'manual-purchases',
			$method_id,
			'Completed',
			$request->get_cart()
		);

		if ( ! $txn_id ) {
			return null;
		}

		return it_exchange_get_transaction( $txn_id );
	}

	/**
	 * @inheritDoc
	 */
	public function can_handle_cart( ITE_Cart $cart ) {

		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return false;
		}

		if ( ! current_user_can( 'it_make_manual_purchase' ) ) {
			return false;
		}

		return parent::can_handle_cart( $cart );
	}

}
