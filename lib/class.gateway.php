<?php
/**
 * Manual Purchases Gateway.
 *
 * @author      Iron Bound Designs
 * @since       2.0.0
 * @copyright   2017 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITE_Manual_Purchases_Gateway
 */
class ITE_Manual_Purchases_Gateway extends ITE_Gateway {
	/** @var ITE_Gateway_Request_Handler[] */
	private $handlers = array();

	/**
	 * ITE_Gateway_Offline_Payments constructor.
	 */
	public function __construct() {

		parent::__construct();

		$this->handlers[] = new ITE_Manual_Purchases_Purchase_Request_Handler( $this, new ITE_Gateway_Request_Factory() );

		if ( class_exists( 'ITE_Cancel_Subscription_Request' ) ) {
			$this->handlers[] = new ITE_Offline_Payments_Cancel_Subscription_Handler();
		}

		if ( class_exists( 'ITE_Pause_Subscription_Request' ) ) {
			$this->handlers[] = new ITE_Offline_Payments_Pause_Subscription_Handler();
		}

		if ( class_exists( 'ITE_Resume_Subscription_Request' ) ) {
			$this->handlers[] = new ITE_Offline_Payments_Resume_Subscription_Handler();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get_name() {
		return __( 'Manual Purchases', 'LION' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_slug() {
		return 'manual-purchases';
	}

	/**
	 * @inheritDoc
	 */
	public function get_addon() {
		return it_exchange_get_addon( 'manual-purchases' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_handlers() {
		return $this->handlers;
	}

	/**
	 * @inheritDoc
	 */
	public function get_payment_button_label() {
		return __( 'Manual Purchase', 'LION' );
	}

	/**
	 * @inheritDoc
	 */
	public function is_sandbox_mode() { return false; }

	/**
	 * @inheritDoc
	 */
	public function get_webhook_param() { return ''; }

	/**
	 * @inheritDoc
	 */
	public function get_settings_fields() { return array(); }

	/**
	 * @inheritDoc
	 */
	public function get_settings_name() { return ''; }

	/**
	 * @inheritDoc
	 */
	public function supports_feature( ITE_Optionally_Supported_Feature $feature ) {

		switch ( $feature->get_feature_slug() ) {
			case 'recurring-payments':
			case 'one-time-fee':
				return true;
		}

		return parent::supports_feature( $feature );
	}

	/**
	 * @inheritDoc
	 */
	public function supports_feature_and_detail( ITE_Optionally_Supported_Feature $feature, $slug, $detail ) {

		switch ( $feature->get_feature_slug() ) {
			case 'one-time-fee':
				switch ( $slug ) {
					case 'discount':
						return true;
					default:
						return false;
				}
			case 'recurring-payments':
				switch ( $slug ) {
					case 'auto-renew':
					case 'profile':
					case 'trial':
					case 'trial-profile':
					case 'max-occurrences':
						return true;
					default:
						return false;
				}
		}

		return parent::supports_feature( $feature );
	}
}