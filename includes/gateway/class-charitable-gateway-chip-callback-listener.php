<?php
if ( ! class_exists( 'Charitable_Gateway_Chip_Callback_Listener' ) ) {

	class Charitable_Gateway_Chip_Callback_Listener {
		const QUERY_CC_ID = 'chip_charitable_id';
		const QUERY_DONATION_KEY = 'donation_key';

		function __construct() {
			error_log( 'Construct in Listener' );

			// Check if chip_charitable_id is set
			if ( isset( $_GET[ self::QUERY_CC_ID ] ) ) {
				$donation = new Charitable_Donation( $_GET[ self::QUERY_CC_ID ] );

				// Check if donation exist
				if ( is_null( $donation->donation_id ) ) {
					error_log( 'Donation with ID ' . $_GET['donation_id'] . ' does not exist, exiting callback' );
					return;
				}

				// Check status of donation
				if ( $donation->post_status == 'charitable-completed' ) {
					error_log( 'Donation with ID ' . $donation->ID . ' status is PAID, exiting callback' );
					return;
				} else {
					error_log( 'Donation with ID ' . $donation->ID . ' status is ' . $donation->post_status );
				}

				// The response ?donation_receipt=1&donation_id=240&donation_key=58a0989046bdeebded682090a9**
				// donation_key exist and verify
				if ( isset( $_GET['donation_key'] ) ) {
					if ( $donation->get_donation_key() != $_GET[ self::QUERY_DONATION_KEY ] ) {
						error_log( 'Missmatch donation key: ' . $_GET['donation_key'] );
						return;
					} else {
						error_log( 'Donation Key is ' . $_GET['donation_key'] );
					}
				}

				// Listen for callback
				$this->listen();
			} else {
				error_log( 'Parameter chip_charitable_id is not set, callback exit' );
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
				error_log( 'No donation key parameter is set' );
				exit;
			}

			// Set donation ID
			if ( empty( $donation_id = intval( $_GET[ self::QUERY_CC_ID ] ) ) ) {
				error_log( 'Donation ID parameter is empty' );
				exit;
			} else {
				// Get Input
				if ( empty( $content = file_get_contents( 'php://input' ) ) ) {
					error_log( 'No input received for donation ID: ' . $donation_id );
					exit;
				}
			}

			// Check X-Signature is set
			if ( ! isset( $_SERVER['HTTP_X_SIGNATURE'] ) ) {
				error_log( 'No X Signature received from headers for donation ID: ' . $donation_id );
				exit;
			}

			// Get Public Key
			$settings = get_option( 'charitable_settings' );
			$public_key = $settings['gateways_chip']['public_key'];

			$verification_result = openssl_verify(
				$content,
				base64_decode( $_SERVER['HTTP_X_SIGNATURE'] ),
				$public_key,
				'sha256WithRSAEncryption'
			);

			error_log( 'X-Signature ' . $_SERVER['HTTP_X_SIGNATURE'] );
			error_log( 'X-Signature status: ' . $verification_result );

			// Verify X-Signature
			if ( openssl_verify( $content, base64_decode( $_SERVER['HTTP_X_SIGNATURE'] ), $public_key, 'sha256WithRSAEncryption' ) != 1 ) {
				header( 'Forbidden', true, 403 );
				error_log( 'Invalid X Signature for donation ID: ' . $donation_id );
				exit;
			} else {
				error_log( 'Verified X-Signature' );
			}

			// Lock row
			$this->get_lock( $donation_id );

			$this->update_order_status( $donation_id, $content );
			error_log( 'Successful Callback for donation_id: ' . $donation_id );
		}

		private function update_order_status( $donation_id, $content ) {
			error_log( 'In update_order_status callback' );
			$donation = new Charitable_Donation( $donation_id );

			// Check status of donation
			if ( $donation->post_status == 'charitable-completed' ) {
				error_log( 'Donation with ID ' . $donation->ID . ' status is PAID' );
				return;
			}

			// json_decode $content (string)
			$content = json_decode( $content, true );

			error_log( 'Updating donation status to PAID ' );
			// $gateway = new Charitable_Gateway_Chip();
			// $keys = $gateway->get_keys();
			$payment_method = $content['transaction_data']['payment_method'];
			$transaction_id = $content['id'];

			// Update donation log
			$message = sprintf(
				__( 'CHIP Transaction ID: %s and Payment Method: %s', 'charitable-chip' ),
				$transaction_id,
				$payment_method
			);
			self::update_donation_log( $donation, $message );

			// Update donation status to complete
			$donation->update_status( 'charitable-completed' );

			// Log 
			error_log( 'Donation ID' . $donation_id . 'status successfully updated to PAID ' );

			// Release lock
			$this->release_lock( $donation_id );

			return;
		}

		/**
		 * Get lock row
		 */
		public function get_lock( $donation_id ) {
			$GLOBALS['wpdb']->get_results( "SELECT GET_LOCK('charitable_chip_payment_$donation_id', 15);" );
		}


		/** 
		 * Release lock row
		 */
		public function release_lock( $donation_id ) {
			$GLOBALS['wpdb']->get_results( "SELECT RELEASE_LOCK('charitable_chip_payment_$donation_id');" );
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
