<?php
if ( ! class_exists( 'Charitable_Gateway_Chip_Callback_Listener' ) ) {

	class Charitable_Gateway_Chip_Callback_Listener {
		const QUERY_CC_ID = 'chip_charitable_id';
		const QUERY_DONATION_KEY = 'donation_key';

		function __construct() {

			// Check if chip_charitable_id is set
			if ( isset( $_GET[ self::QUERY_CC_ID ] ) ) {
				$donation = new Charitable_Donation( $_GET[ self::QUERY_CC_ID ] );

				// Check if donation exist
				if ( is_null( $donation->donation_id ) ) {
					return;
				}

				// Check status of donation
				if ( $donation->post_status == 'charitable-completed' ) {
					return;
				}

				// The response ?donation_receipt=1&donation_id=240&donation_key=58a0989046bdeebded682090a9**
				// donation_key exist and verify
				if ( isset( $_GET['donation_key'] ) ) {
					if ( $donation->get_donation_key() != $_GET[ self::QUERY_DONATION_KEY ] ) {
						return;
					}
				}

				// Listen for callback
				$this->listen();
			} else {
				return;
			}
		}

		/**
		 * Build the callback URL
		 */
		public static function get_listener_url( $donation ) {
			// In callback url return the donation id and donation_key
			return add_query_arg( array(
				self::QUERY_CC_ID => $donation->ID,
				self::QUERY_DONATION_KEY => $donation->get_donation_key(),
			), site_url( '/' ) );
		}

		private function listen() {
			// Check for donation_key
			if ( ! isset( $_GET[ self::QUERY_DONATION_KEY ] ) ) {
				exit;
			}

			// Set donation ID
			if ( empty( $donation_id = intval( $_GET[ self::QUERY_CC_ID ] ) ) ) {
				exit;
			} else {
				// Get Input
				if ( empty( $content = file_get_contents( 'php://input' ) ) ) {
					exit;
				}
			}

			// json_decode $purchase (string)
			$purchase = json_decode( $content, true );

			// Check X-Signature is set
			if ( ! isset( $_SERVER['HTTP_X_SIGNATURE'] ) ) {
				exit;
			}

			// Get Public Key
			$settings = get_option( 'charitable_settings' );
			$public_key = $settings['gateways_chip']['public_key'];

			// Verify X-Signature
			if ( openssl_verify( $content, base64_decode( $_SERVER['HTTP_X_SIGNATURE'] ), $public_key, 'sha256WithRSAEncryption' ) != 1 ) {
				header( 'Forbidden', true, 403 );
				exit;
			}

			// Lock row
			$this->lock( $donation_id );

			// Remove cache of donation
			wp_cache_delete( $donation_id, 'charitable_donation' );

			$donation = new Charitable_Donation( $donation_id );

			// Check status of donation completed
			if ( $donation->post_status == 'charitable-completed' ) {
				return;
			}

			// If purchase status is paid
			if ( $purchase['status'] == 'paid' ) {
				// Update order status
				$this->update_order_status( $donation_id, $purchase );
			}
		}

		private function update_order_status( $donation_id, $purchase ) {
			$donation = new Charitable_Donation( $donation_id );

			// $gateway = new Charitable_Gateway_Chip();
			// $keys = $gateway->get_keys();
			$payment_method = $purchase['transaction_data']['payment_method'];
			$transaction_id = $purchase['id'];

			// Update donation log
			$message = sprintf(
        // translators: 1: CHIP Purchase ID, 2: Payment method name. E.g.: FPX, MPGS 
				__( 'CHIP Transaction ID: %1$s and Payment Method: %2$s', 'chip-for-wpcharitable' ),
				$transaction_id,
				$payment_method
			);
			self::update_donation_log( $donation, $message );

			// Update donation status to complete
			$donation->update_status( 'charitable-completed' );

			return;
		}

		/**
		 *  Lock row
		 */
		public function lock( $donation_id ) {
			$GLOBALS['wpdb']->get_results( "SELECT GET_LOCK('charitable_chip_payment_$donation_id', 5);" );
		}

		/**
		 * Update the donation's log. 		 
		 */
		public static function update_donation_log( $donation, $message ) {
			if ( version_compare( charitable()->get_version(), '1.4.0', '<' ) ) {
				return Charitable_Donation::update_donation_log( $donation->ID, $message );
			}

			return $donation->update_donation_log( $message );
		}
	}
}
