<?php

/**
 * CHIP Gateway class
 *
 * @version     1.0.0
 * @package     Charitable/Classes/Charitable_Gateway_Chip
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Charitable_Gateway_Chip' ) ) {
	/**
	 * CHIP Gateway
	 *
	 * @since       1.0.0
	 */
	class Charitable_Gateway_Chip extends Charitable_Gateway {

		/**
		 * @var     string
		 */
		const ID = 'chip';

		/**
		 * Instantiate the gateway class, defining its key values.
		 *
		 * @access  public
		 * @since   1.0.0
		 */
		public function __construct() {
			$this->name = apply_filters( 'charitable_gateway_chip_name', __( 'CHIP', 'chip-for-wpcharitable' ) );

			$this->defaults = array(
				'label' => __( 'CHIP', 'chip-for-wpcharitable' ),
			);

			$this->supports = array(
				'1.3.0',
			);

			/**
			 * Needed for backwards compatibility with Charitable < 1.3
			 */
			$this->credit_card_form = false;
		}

		/**
		 * Returns the current gateway's ID.
		 *
		 * @return  string
		 * @access  public
		 * @static
		 * @since   1.0.3
		 */
		public static function get_gateway_id() {
			return self::ID;
		}

		/**
		 * Register gateway settings.
		 *
		 * @param   array $settings
		 * @return  array
		 * @access  public
		 * @since   1.0.0
		 */
		public function gateway_settings( $settings ) {
			// Checking for MYR - Need to fix change currency button
			if ( 'MYR' != charitable_get_option( 'currency', 'MYR' ) ) {
				$settings['currency_notice'] = array(
					'type' => 'notice',
					'content' => $this->get_currency_notice(),
					'priority' => 1,
					'notice_type' => 'error',
				);
			}

			// Checking if CHIP default gateway
			if ( 'chip' != charitable_get_option( 'default_gateway' ) ) {
				$settings['default_gateway_notice'] = array(
					'type' => 'notice',
					'content' => $this->get_default_gateway_notice(),
					'priority' => 2,
					'notice_type' => 'error'
				);
			}

			$settings['brand_id'] = array(
				'type' => 'text',
				'title' => __( 'Brand ID', 'chip-for-wpcharitable' ),
				'priority' => 6,
				'help' => 'Enter your CHIP Brand ID. Get this key from Developers >> Brands',
				'required' => true,
			);

			$settings['secret_key'] = array(
				'type' => 'text',
				'title' => __( 'Secret Key', 'chip-for-wpcharitable' ),
				'priority' => 6,
				'help' => 'Enter your CHIP Secret Key. Get this key from Developer >> Keys',
				'required' => true,
			);

			$settings['public_key'] = array(
				'type' => 'textarea',
				'title' => __( 'Public Key', 'chip-for-wpcharitable' ),
				'priority' => 6,
				'help' => 'Public Key will be generated once Brand ID and Secret Key are set',
			);

			// Get the available payment methods
			// if (isset(charitable_get_option('gateways_chip')['secret_key']) && isset(charitable_get_option('gateways_chip')['brand_id'])) {
			//     $chip = new Chip_Charitable_API([
			//         charitable_get_option('gateways_chip')['secret_key'],
			//         charitable_get_option('gateways_chip')['brand_id']
			//     ]);

			//     $response = $chip->payment_methods('MYR');

			//     // Checking response
			//     if (array_key_exists("available_payment_methods",$response)) {
			//         $available_payment_methods = $response['available_payment_methods'];
			//     } else {
			//         $available_payment_methods = [];
			//     }
			// } else {
			//     $available_payment_methods = [];
			// }

			$settings['payment_method_whitelist'] = array(
				'type' => 'multi-checkbox',
				'title' => __( 'Payment Method Whitelist', 'chip-for-wpcharitable' ),
				'priority' => 6,
				'help' => 'Set payment method whitelist separated by comma. Acceptable value: fpx, fpx_b2b1, mastercard, maestro, visa, razer_atome, razer_grabpay, razer_maybankqr, razer_shopeepay, razer_tng, duitnow_qr. Leave blank if unsure.',
				'default' => [ 'fpx' ],
				// 'default' => $available_payment_methods,
				'options' => array(
					'fpx' => __( 'FPX', 'fpx' ),
					'fpx_b2b1' => __( 'FPX B2B', 'fpx_b2b1' ),
					'mastercard' => __( 'Mastercard', 'mastercard' ),
					'maestro' => __( 'Maestro', 'maestro' ),
					'visa' => __( 'Visa', 'visa' ),
					'razer_atome' => __( 'Razer Atome', 'razer_atome' ),
					'razer_grabpay' => __( 'Razer GrabPay', 'razer_grabpay' ),
					'razer_maybankqr' => __( 'Razer MaybankQR', 'razer_maybankqr' ),
					'razer_shopeepay' => __( 'Razer ShopeePay', 'razer_shopeepay' ),
					'razer_tng' => __( 'Razer TnG', 'razer_tng' ),
					'duitnow_qr' => __( 'DuitNow QR', 'duitnow_qr' ),
				),
				// 'required'  => true,
			);

			$settings['due_strict'] = array(
				'type' => 'radio',
				'title' => __( 'Due Strict', 'chip-for-wpcharitable' ),
				'priority' => 6,
				'help' => 'Enforce due strict payment timeframe to block payment after due strict timing is passed',
				'default' => 0,
				'options' => array(
					'1' => __( 'Yes', 'charitable' ),
					'0' => __( 'No', 'charitable' ),
				),
				// 'required'  => true,
			);

			$settings['due_strict_timing'] = array(
				'type' => 'number',
				'default' => 60,
				'title' => __( 'Due Strict Timing', 'chip-for-wpcharitable' ),
				'priority' => 6,
				'help' => 'Due strict timing in minutes. Default value is: 60.',
			);

			$settings['purchase_send_receipt'] = array(
				'type' => 'radio',
				'title' => __( 'Purchase Send Receipt', 'chip-for-wpcharitable' ),
				'priority' => 6,
				'help' => 'Select Yes to ask CHIP to send receipt upon successful payment. If activated, CHIP will send purchase receipt upon payment completion.',
				'default' => 0,
				'options' => array(
					'1' => __( 'Yes', 'charitable' ),
					'0' => __( 'No', 'charitable' ),
				),
				// 'required'  => true,
			);

			// $settings['email_fallback'] = array(
			//     'type'      => 'email',
			//     'title'     => __('Email Fallback', 'chip-for-wpcharitable'),
			//     'priority'  => 6,
			//     'help'      => 'When email address is not requested to the customer, use this email address.',
			// );


			return $settings;
		}

		/**
		 * Return cancel_url
		 */
		private function get_cancel_url( $donation ) {
			$cancel_url = charitable_get_permalink( 'donation_cancel_page', array( 'donation_id' => $donation->ID ) );

			if ( ! $cancel_url ) {
				$cancel_url = esc_url( add_query_arg(
					array( 'donation_id' => $donation->ID, 'cancel' => true ),
					wp_get_referer()
				) );
			}

			return $cancel_url;
		}

		/**
		 * Return donation page url
		 */
		private function get_donation_page_url( $donation ) {
		}

		/**
		 * Return the keys to use.
		 *
		 * @return  string[]
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_keys() {
			$keys = [ 
				'brand_id' => trim( $this->get_value( 'brand_id' ) ),
				'secret_key' => trim( $this->get_value( 'secret_key' ) )
			];

			return $keys;
		}

		/**
		 * Process the donation with CHIP.
		 *
		 * @param   Charitable_Donation $donation
		 * @return  void
		 * @access  public
		 * @static
		 * @since   1.0.0
		 */
		public static function redirect_to_processing_legacy( $donation_id ) {
			wp_safe_redirect(
				charitable_get_permalink(
					'donation_processing_page',
					array(
						'donation_id' => $donation_id
					)
				)
			);

			exit();
		}

		/**
		 * Process the donation with CHIP
		 */
		public static function process_donation( $content, Charitable_Donation $donation ) {

			// new Charitable_Gateway_Chip
			$gateway = new Charitable_Gateway_Chip();

			// Check for currency MYR
			if ( charitable_get_option( 'currency' ) != "MYR" ) {
				$method_data['errors'][] = "CHIP only accept payments in Ringgit Malaysia (RM)";
			} else {
				$method_data = $gateway->createRequest( $donation, $gateway );
			}

			// If method_data['action] set
			if ( isset( $method_data['action'] ) ) {
				echo $content;
				echo ( "<form method='get'
	               action='" . $method_data['action'] . "'	id='chip-form'></form>
                   <script type='text/javascript'>        
                       function charitable_submit_chip_form() {
                            var form = document.getElementById('chip-form');
                            form.submit();
	                   }
	
	                   window.onload = charitable_submit_chip_form();
	               </script>" );
			} else if ( isset( $method_data['errors'] ) ) {
				echo ( "<div class='charitable-notice charitable-form-errors'>Error:" );
				foreach ( $method_data['errors'] as $e ) {
					echo ( "<div>" . $e . "</div>" );
				}
				echo ( "<a href=" . $gateway->getCancelUrl( $donation ) . ">Go Back</a></div>" );
			}

			$content = ob_get_clean();
			return $content;
		}

		/**
		 * Create request for donation and gateway API
		 */
		private function createRequest( $donation, $gateway ) {

			/*
			 * Support for Advance CHIP for WP Charitable Plugin
			 */
			$campaign_donations = $donation->get_campaign_donations();

			foreach ( $campaign_donations as $key => $value ) {
				if ( ! empty( $value->campaign_id ) ) {
					$post_id = $value->campaign_id;
					$campaign_name = $value->campaign_name;
					$post = get_post( (int) $post_id );
					$campaign = new Charitable_Campaign( $post );
					break;
				}
			}

			$donor = $donation->get_donor();

			$first_name = $donor->get_donor_meta( 'first_name' );
			$last_name = $donor->get_donor_meta( 'last_name' );
			$name = $first_name . ' ' . $last_name;

			$email = $donor->get_donor_meta( 'email' );
			$mobile = $donor->get_donor_meta( 'phone' ) ?? '';
			$amount = $donation->get_total_donation_amount( true );

			$product_info = html_entity_decode( $donation->get_campaigns_donated_to(), ENT_COMPAT, 'UTF-8' );
			$donation_key = $donation->get_donation_key();
			$payment_gateway = $donation->get_gateway();

			$keys = $gateway->get_keys();

			$brand_id = apply_filters( 'chip_for_wp_charitable_brand_id', $keys['brand_id'], $post, $campaign );
			$secret_key = apply_filters( 'chip_for_wp_charitable_secret_key', $keys['secret_key'], $post, $campaign );
			$callback_url = Charitable_Gateway_Chip_Callback_Listener::get_listener_url( $donation );

			// Response URL
			$success_url = charitable_get_permalink( 'donation_receipt_page', array( 'donation_id' => $donation->ID ) );
			$success_url = add_query_arg( 'donation_key', $donation_key, $success_url );
			$cancel_url = charitable_get_permalink( 'donation_cancellation', array( 'donation_id' => $donation->ID ) );


			// Set due
			if ( isset( charitable_get_option( 'gateways_chip' )['due_strict'] ) ) {
				if ( charitable_get_option( 'gateways_chip' )['due_strict'] == 1 ) {
					$due_strict = true;
				}
			} else {
				$due_strict = false;
			}

			// Set purchase send receipt 
			if ( isset( charitable_get_option( 'gateways_chip' )['purchase_send_receipt'] ) ) {
				$purchase_send_receipt = (bool) ( charitable_get_option( 'gateways_chip' )['purchase_send_receipt'] );
			} else {
				$purchase_send_receipt = false;
			}

			// Email fallback
			// if (empty($email)) {
			//     // Check email fallback
			//     if (isset(charitable_get_option('gateways_chip')['email_fallback'])) {
			//         $email = charitable_get_option('gateways_chip')['email_fallback'];
			//     }
			// } else {
			//     $message = sprintf(
			//         __(
			//             'Error caused by empty email . Response data: %s',
			//             'chip-for-wpcharitable'), json_encode($_REQUEST));
			//     self::update_donation_log($donation, $message);
			//     $donation->update_status('charitable-failed');
			// }


			// Set Params
			$purchase_params = array(
				'client' => array(
					'email' => $email,
					'full_name' => $name
					// 'phone'
				),
				'success_redirect' => $success_url, // the donation receipt url page
				// 'failure_redirect' => '',
				'cancel_redirect' => $cancel_url, // donation cancel page
				'success_callback' => $callback_url, // callback 
				// 'success_callback' => 'https://webhook.site/4fb2208a-e322-457b-8335-e074832760de', // testing purpose
				'creator_agent' => 'WP Charitable',
				'reference' => $donation->ID,
				// 'client_id'        => $client['id'],
				'platform' => 'api', // 'charitable'
				'send_receipt' => $purchase_send_receipt, // charitable_get_option('gateways_chip')['purchase_send_receipt'] == 1,
				// 'due'              => time() + (abs( (int)$params['dueStrictTiming'] ) * 60),
				'brand_id' => $brand_id,
				'purchase' => array(
					//   'timezone'   => $params['purchaseTimeZone'],
					'currency' => 'MYR',
					'due_strict' => $due_strict,
					'products' => array( [ 
						'name' => substr( $campaign_name, 0, 256 ),
						'price' => round( $amount * 100 ),
					] ),
				),
			);

			// Set due timing
			if ( $due_strict ) {
				if ( ! empty( charitable_get_option( 'gateways_chip' )['due_strict_timing'] ) ) {
					$purchase_params['due'] = time() + abs( (int) charitable_get_option( 'gateways_chip' )['due_strict_timing'] ) * 60;
				}
			}

			// Set payment method whitelist
			if ( isset( charitable_get_option( 'gateways_chip' )['payment_method_whitelist'] ) ) {
				$payment_method_whitelist = charitable_get_option( 'gateways_chip' )['payment_method_whitelist'];

				$diff = array_diff( $payment_method_whitelist, [ 'fpx', 'fpx_b2b1', 'mastercard', 'maestro', 'visa', 'razer_atome', 'razer_grabpay', 'razer_maybankqr', 'razer_shopeepay', 'razer_tng', 'duitnow_qr' ] );
				if ( empty( $diff ) ) {
					$purchase_params['payment_method_whitelist'] = $payment_method_whitelist;
				}
			}

			// Check first if brand ID and secret key configured
			$credentials = array(
				$secret_key,
				$brand_id
			);
			$chip = new Chip_Charitable_API( $credentials );

			$response = $chip->create_payment( $purchase_params );

			// Check if have response from CHIP
			if ( isset( $response['id'] ) ) {
				$method_data['action'] = $response['checkout_url'];

				// Set Charitable CHIP Gateway transaction ID
				$donation->set_gateway_transaction_id( $response['id'] );
				self::update_donation_log( $donation, "Checkout Link: " . print_r( $response['checkout_url'], true ) );
				// update_option('chip_charitable_bill_id_' . $bill_id, $donation->ID, false);
			}

			return $method_data;
		}

		/**
		 * Get cancel URL 
		 */
		private function getCancelUrl( $donation ) {
			$cancel_url = charitable_get_permalink(
				'donation_cancel_page',
				array(
					'donation_id' => $donation->ID
				)
			);

			if ( ! $cancel_url ) {
				$cancel_url = esc_url(
					add_query_arg(
						array(
							'donation_id' => $donation->ID,
							'cancel' => true
						),
						wp_get_referer()
					)
				);
			}
			return $cancel_url;
		}

		/**
		 * Update Charitable Donation Log 
		 */
		public static function update_donation_log( $donation, $message ) {
			// Check the version
			if ( version_compare( charitable()->get_version(), '1.4.0', '<' ) ) {
				return Charitable_Donation::update_donation_log( $donation->ID, $message );
			}

			return $donation->update_donation_log( $message );
		}

		/**
		 *
		 * @param   Charitable_Donation $donation
		 * @return  void
		 * @access  public
		 * @static
		 * @since   1.0.0
		 */
		public static function process_response( Charitable_Donation $donation ) {

			// Check if the donation status is complete
			if ( get_post_status( $donation->ID ) == 'charitable-completed' ) {
				return;
			}

			// Set the variables
			$donation_key = $_REQUEST['donation_key'];
			$transaction_id = $donation->get_gateway_transaction_id();

			// Check if empty
			$secret_key = charitable_get_option( 'gateways_chip' )['secret_key'];
			$brand_id = charitable_get_option( 'gateways_chip' )['brand_id'];

			$credentials = array(
				$secret_key,
				$brand_id
			);

			// CHIP API (Checking for transaction status)
			$gateway = new Charitable_Gateway_Chip();
			$chip = new Chip_Charitable_API( $credentials );
			$response = $chip->get_payment( $transaction_id );    // check if transaction_id exist

			// Check if CHIP transaction is paid
			if ( isset( $response ) ) {
				// Check if donation ID same
				$donation_id = $response['reference'];
				$amount = $response['purchase']['total'];
				$payment_method = $response['transaction_data']['payment_method'];

				// Check if donation_id from reference same
				if ( $donation_id != $donation->ID ) {
					return;
				}

				if ( $response['status'] == 'paid' ) {
					// Verify for donation_key
					if ( $donation_key != $donation->get_donation_key() ) {

						// Set donation to failed
						$message = sprintf(
							__(
								'The donation key in the response does not match the donation. Response data: %s',
								'chip-for-wpcharitable'
							),
							json_encode( $_REQUEST )
						);
						self::update_donation_log( $donation, $message );
						$donation->update_status( 'charitable-failed' );
						return;
					}

					// Verify the amount of donation with API
					if ( $amount != ( $donation->get_total_donation_amount() * 100 ) ) {

						$message = sprintf(
							__(
								'The amount in the response does not match the expected donation amount. Response data: %s',
								'chip-for-wpcharitable'
							),
							json_encode( $_REQUEST )
						);
						self::update_donation_log( $donation, $message );
						$donation->update_status( 'charitable-failed' );
						return;
					}

					// Donation status is paid
					$message = sprintf(
						__( 'CHIP Transaction ID: %s and Payment Method: %s', 'chip-for-wpcharitable' ),
						$transaction_id,
						$payment_method
					);
					self::update_donation_log( $donation, $message );

					// Lock the row
					self::get_lock( $donation_id );

					// Update donation status to complete
					$donation->update_status( 'charitable-completed' );

					// Release the lock
					self::release_lock( $donation_id );

					return;
				} else {
					$message = sprintf(
						__(
							'Unfortunately, your donation was declined by our payment gateway.
                            <br><b>Donation Number:</b> %s
                            <br><b>Transaction ID:</b> %s',
							'chip-for-wpcharitable'
						),
						$donation->ID,
						$transaction_id,
					);
					self::update_donation_log( $donation, $message );
					$donation->update_status( 'charitable-failed' );

					$message .= "<br><br><a href=" . $gateway->getCancelURL( $donation ) . ">Go Back</a></div>";
					die( __( $message, 'charitable' ) );
					return;
				}
			} else {
				$message = sprintf(
					__(
						'Error. Response data: %s',
						'chip-for-wpcharitable'
					),
					json_encode( $_REQUEST )
				);
				self::update_donation_log( $donation, $message );
				$donation->update_status( 'charitable-failed' );
				return;
			}

			// return;
		}

		/**
		 * Get the Gateway Notice
		 */
		public function get_default_gateway_notice() {
			ob_start();

			printf(
				__( 'CHIP is not set as default payment gateway. %sSet as Default%s', 'chip-for-wpcharitable' ),
				'<a href="#" class="button" data-change-default-gateway>',
				'</a>'
			) ?>

			<script>
				(function ($) {
					$('[data-change-default-gateway]').on('click', function () {
						var $this = $(this);

						$.ajax({
							type: "POST",
							data: {
								action: 'charitable_change_gateway_to_chip',
								_nonce: "<?php echo wp_create_nonce( 'chip_gateway_change' ) ?>"
							},

							url: ajaxurl,
							success: function (response) {
								console.log(response);
								if (response.success) {
									$this.parents('.notice').first().slideUp();
								}
							},
							error: function (response) {
								console.log(response);
							}
						});
					})
				})(jQuery);
			</script>

			<?php
			return ob_get_clean();
		}

		/**
		 * Change the default gateway to CHIP
		 */
		public function change_gateway_to_chip() {
			if ( ! wp_verify_nonce( $_REQUEST['_nonce'], 'chip_gateway_change' ) ) {
				wp_send_json_error();
			}

			$settings = get_option( 'charitable_settings' );
			$settings['default_gateway'] = "chip";
			$updated = update_option( 'charitable_settings', $settings );

			wp_send_json( array( 'success' => $updated ) );
			wp_die();
		}

		/**
		 * Return the HTML for the currency notice.
		 *
		 * @return  string
		 * @access  public
		 * @since   1.0.0
		 */
		public function get_currency_notice() {
			ob_start();

			?>
			<?php
			printf(
				__( 'CHIP only accepts payments in Malaysian Ringgit. %sChange Now%s', 'chip-for-wpcharitable' ),
				'<a href="#" class="button" data-change-currency-to-myr>',
				'</a>'
			)

				?>
			<script>
				(function ($) {
					$('[data-change-currency-to-myr]').on('click', function () {
						var $this = $(this);

						$.ajax({
							type: "POST",
							data: {
								action: 'charitable_change_currency_to_myr',
								_nonce: "<?php echo wp_create_nonce( 'chip_currency_change' ) ?>"
							},
							url: ajaxurl,
							success: function (response) {
								console.log(response);

								if (response.success) {
									$this.parents('.notice').first().slideUp();
								}
							},
							error: function (response) {
								console.log(response);
							}
						});
					})
				})(jQuery);
			</script>
			<?php
			return ob_get_clean();
		}

		/**
		 * Change the currency to MYR.
		 *
		 * @return  void
		 * @access  public
		 * @static
		 * @since   1.0.0
		 */
		public static function change_currency_to_myr() {
			if ( ! wp_verify_nonce( $_REQUEST['_nonce'], 'chip_currency_change' ) ) {
				wp_send_json_error();
			}

			$settings = get_option( 'charitable_settings' );
			$settings['currency'] = 'MYR';
			$updated = update_option( 'charitable_settings', $settings );

			wp_send_json( array( 'success' => $updated ) );
			wp_die();
		}
		/*
		 * Listen to CHIP Callback & Return
		 */

		public static function callback_listener() {
			new Charitable_Gateway_Chip_Callback_Listener;
		}

		/**
		 * Set email field as required
		 */
		public static function set_email_field_required( $fields ) {
			$fields['email']['required'] = true;
			return $fields;
		}

		/**
		 * Get lock row
		 */
		public static function get_lock( $donation_id ) {
			$GLOBALS['wpdb']->get_results( "SELECT GET_LOCK('charitable_chip_payment_$donation_id', 15);" );
		}


		/** 
		 * Release lock row
		 */
		public static function release_lock( $donation_id ) {
			$GLOBALS['wpdb']->get_results( "SELECT RELEASE_LOCK('charitable_chip_payment_$donation_id');" );
		}
	}
} // End class_exists check
