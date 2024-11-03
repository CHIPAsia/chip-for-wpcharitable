<?php
/**
 * CHIP Gateway class
 *
 * @version     1.0.0
 * @package     Charitable/Classes/Charitable_Gateway_Chip
 */
// Exit if accessed directly.
if (!defined('ABSPATH')) 
{
    exit;
}

if (!class_exists('Charitable_Gateway_Chip')) 
{
    /**
     * CHIP Gateway
     *
     * @since       1.0.0
     */
    class Charitable_Gateway_Chip extends Charitable_Gateway
    {

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
        public function __construct()
        {
            $this->name = apply_filters('charitable_gateway_chip_name', __('CHIP', 'charitable-chip'));

            $this->defaults = array(
                'label' => __('CHIP', 'charitable-chip'),
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
        public static function get_gateway_id()
        {
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
        public function gateway_settings($settings)
        {
            // Checking for MYR - Need to fix change currency button
            if ('MYR' != charitable_get_option('currency', 'MYR')) {
                $settings['currency_notice'] = array(
                    'type'          => 'notice',
                    'content'       => $this->get_currency_notice(),
                    'priority'      => 1,
                    'notice_type'   => 'error',
                );
            }

            // Checking if CHIP default gateway
            if ('chip' != charitable_get_option('default_gateway')) 
			{
            	$settings['default_gateway_notice'] = array(
                	'type' 			=> 'notice',
                    'content' 		=> $this->get_default_gateway_notice(),
                    'priority' 		=> 2,
                    'notice_type' 	=> 'error'
                );
            }

            $settings['brand_id'] = array(
                'type'      => 'text',
                'title'     => __('Brand ID', 'charitable-chip'),
                'priority'  => 6,
                'help'      => 'Enter your CHIP Brand ID. Get this key from Developers >> Brands',
                'required'  => true,
            );

            $settings['secret_key'] = array(
                'type'      => 'text',
                'title'     => __('Secret Key', 'charitable-chip'),
                'priority'  => 6,
                'help'      => 'Enter your CHIP Secret Key. Get this key from Developer >> Keys',
                'required'  => true,
            );

            $settings['public_key'] = array(
                'type'      => 'textarea',
                'title'     => __('Public Key', 'charitable-chip'),
                'priority'  => 6,
                'help'      => 'Public Key will be generated once Brand ID and Secret Key are set',
            );

            return $settings;
        }

        /**
         * Return cancel_url
         */
        private function get_cancel_url($donation)
        {
            $cancel_url = charitable_get_permalink('donation_cancel_page', array('donation_id' => $donation->ID));

            if (! $cancel_url) 
			{
            	$cancel_url = esc_url(add_query_arg(
				array('donation_id' => $donation->ID, 'cancel' => true), wp_get_referer()));
            }
            
			return $cancel_url;
        }

        /**
         * Return donation page url
         */
        private function get_donation_page_url($donation)
        {
            
        }

        /**
         * Return the keys to use.
         *
         * @return  string[]
         * @access  public
         * @since   1.0.0
         */
        public function get_keys()
        {
            $keys = [
                'brand_id' => trim($this->get_value('brand_id')),
                'secret_key' => trim($this->get_value('secret_key'))
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
        public static function redirect_to_processing_legacy($donation_id)
        {
            wp_safe_redirect(
                charitable_get_permalink('donation_processing_page',
                    array(
                        'donation_id' => $donation_id
                    )));
            
            exit();
        }
        // public static function redirect_to_processing($return, $donation_id)
        // {

       
        // }

        /**
         * Process the donation with CHIP
         */
        public static function process_donation($content, Charitable_Donation $donation)
        {
            error_log('Inside process_donation function');
            // new Charitable_Gateway_Chip
            $gateway = new Charitable_Gateway_Chip();

            // Check for currency MYR
            if (charitable_get_option('currency') != "MYR") {
                $method_data['errors'][] = "CHIP only accept payments in Ringgit Malaysia (RM)";
            } else {
                $method_data = $gateway->createRequest($donation, $gateway);
            }

            // If method_data['action] set
            if (isset($method_data['action'])) {
                echo $content;
                echo ("<form method='get'
	               action='" . $method_data['action'] . "'	id='chip-form'></form>
                   <script type='text/javascript'>        
                       function charitable_submit_chip_form() {
                            var form = document.getElementById('chip-form');
                            form.submit();
	                   }
	
	                   window.onload = charitable_submit_chip_form();
	               </script>");
            } else if (isset($method_data['errors'])) {
                echo ("<div class='charitable-notice charitable-form-errors'>Error:");
                foreach ($method_data['errors'] as $e) {
                    echo ("<div>" . $e . "</div>");
                }
                echo ("<a href=" . $gateway->getCancelUrl($donation) . ">Go Back</a></div>");
            }

            $content = ob_get_clean();
            return $content;

        }

        /**
         * Create request for donation and gateway API
         */
        private function createRequest($donation, $gateway)
        {

            /*
             * Support for Advance CHIP for WP Charitable Plugin
             */
            $campaign_donations = $donation->get_campaign_donations();
            
            foreach ($campaign_donations as $key => $value) {
                if (!empty($value->campaign_id)) {
                    $post_id = $value->campaign_id;
                    $campaign_name = $value->campaign_name;
                    $post = get_post((int) $post_id);
                    $campaign = new Charitable_Campaign($post);
                    break;
                }
            }

            $donor = $donation->get_donor();

            $first_name = $donor->get_donor_meta('first_name');
            $last_name = $donor->get_donor_meta('last_name');
            $name = $first_name . ' ' . $last_name;

            $email = $donor->get_donor_meta('email');
            $mobile = $donor->get_donor_meta('phone') ?? '';
            $amount = $donation->get_total_donation_amount(true);

            $product_info 	= html_entity_decode($donation->get_campaigns_donated_to(), ENT_COMPAT, 'UTF-8' );
            $donation_key	= $donation->get_donation_key();
            $payment_gateway =  $donation->get_gateway();

            $keys = $gateway->get_keys();

            $brand_id = apply_filters('chip_for_wp_charitable_brand_id', $keys['brand_id'], $post, $campaign);
            $secret_key = apply_filters('chip_for_wp_charitable_secret_key', $keys['secret_key'], $post, $campaign);
            $ipn_url = Charitable_Gateway_Chip_IPN_Listener::get_listener_url($donation);


            error_log('Brand ID: ' . $brand_id);
            // Response URL
            // $cancel_url = charitable_get_permalink('donation_cancel_page', array('donation_id' => $donation->ID));
            // $response_url	= get_option( 'siteurl' ).'/donation-receipt/'.$donation->ID.'';
            $success_url = charitable_get_permalink('donation_receipt_page',array('donation_id' => $donation->ID));
            $success_url = add_query_arg('donation_key', $donation_key, $success_url);
            // $campaign_donation_url = charitable_get_permalink( 'campaign_donation_page', array( 'campaign_id' => $campaign->ID));
            // $campaign_url = charitable_get_permalink( 'campaign_donation', array( 'campaign_id' => $campaign->ID));
            $cancel_url = charitable_get_permalink('donation_cancellation', array('donation_id' => $donation->ID));

            // Params
            $purchase_params = array(
                'client' => array(
                    'email' => $email,
                    'full_name' => $name
                    // 'phone'
                ),
                'success_redirect' => $success_url, // the donation receipt url page
                // 'failure_redirect' => '',
                'cancel_redirect' => $cancel_url, // donation cancel page
                // 'success_callback' => $ipn_url, // ipn callback 
                'success_callback' => 'https://webhook.site/4fb2208a-e322-457b-8335-e074832760de', // testing purpose
                'creator_agent'    => 'WP Charitable',
                'reference'        => $donation->ID, 
                // 'client_id'        => $client['id'],
                'platform'         => 'api', // 'charitable'
                // 'send_receipt'     => $params['purchaseSendReceipt'] == 'on',
                // 'due'              => time() + (abs( (int)$params['dueStrictTiming'] ) * 60),
                'brand_id'         => $brand_id,
                'purchase'         => array(
                //   'timezone'   => $params['purchaseTimeZone'],
                'currency'   => 'MYR',
                //   'due_strict' => $params['dueStrict'] == 'on',
                'products'   => array([
                    'name'     => substr($campaign_name, 0, 256),
                    'price'    => round($amount * 100),
                ]),
                ),
            );
            

            // Check first if brand ID and secret key configured
            $credentials = array(
                $secret_key,
                $brand_id
            );
            $chip = new Chip($credentials);

            // error_log('Private Key: ' . $chip->display());

            // error_log(var_dump($chip->create_payment($purchase_params)));

            $response = $chip->create_payment($purchase_params);


            // Check if success or fail
            // $bill_url = $response['checkout_url'];
            // $bill_id = $response['id'];

            // error_log('Bill ID: ' . $bill_id);

            // Set the gateway trxn id
            // $donation->set_gateway_transaction_id($bill_id);

            // Check if have response from CHIP
            if (isset($response['id'])) {
                $method_data['action'] = $response['checkout_url'];
                $donation->set_gateway_transaction_id($response['id']);
                self::update_donation_log($donation, "Checkout Link: " . print_r($response['checkout_url'], true));
                // $donation->update_donation_log("Checkout Link: " . print_r($response['checkout_url']));
                // update_option('chip_charitable_bill_id_' . $bill_id, $donation->ID, false);
            }

            return $method_data;
        }

        /**
         * Get cancel URL 
         */
        private function getCancelUrl($donation)
        {
            $cancel_url = charitable_get_permalink('donation_cancel_page',
                array(
                    'donation_id' => $donation->ID
                ));
            
            if (! $cancel_url) {
                $cancel_url = esc_url(
                    add_query_arg(
                        array(
                            'donation_id' => $donation->ID,
                            'cancel' => true
                        ), wp_get_referer()));
            }
            return $cancel_url;
        }

        /**
         * Update Charitable Donation Log
         */
        // public static function update_donation_log($donation, $message)
        // {
        //     // Check Charitable version

        //     return $donation->update_donation_log($message);
        // }

        /**
         * Update the donation's log. 		 
         */
        public static function update_donation_log($donation, $message)
        {
            if (version_compare(charitable()->get_version(), '1.4.0', '<')) {
                return Charitable_Donation::update_donation_log($donation->ID, $message);
            }

            return $donation->update_donation_log($message);
        }

        /**
         *
         * @param   Charitable_Donation $donation
         * @return  void
         * @access  public
         * @static
         * @since   1.0.0
         */
        public static function process_response(Charitable_Donation $donation)
        {
            error_log('Running process_response() function');
            return;
        }

        /**
         * Get the Gateway Notice
         */
        public function get_default_gateway_notice()
        {
            ob_start();
                  
            printf(__('CHIP is not set as default payment gateway. %sSet as Default%s', 'charitable-chip'),
                '<a href="#" class="button" data-change-default-gateway>', '</a>')?>

			<script>
			(function($)
			{
				$('[data-change-default-gateway]').on('click', function() 
				{
					var $this = $(this);
			
					$.ajax({
								type: "POST",
								data: {
								action: 'charitable_change_gateway_to_chip', 
								_nonce: "<?php echo wp_create_nonce( 'chip_gateway_change' ) ?>"
							},
						
					url: ajaxurl,
					success: function(response) 
					{
						console.log(response);
						if (response.success)
						{
							$this.parents('.notice').first().slideUp();
						}            
					}, 
					error: function(response) 
					{
						console.log(response);
					}
				});
				})
			})( jQuery );
			</script>
			
			<?php
            return ob_get_clean();
        }

        /**
         * Change the default gateway to CHIP
         */
        public function change_gateway_to_chip()
        {
            if (!wp_verify_nonce($_REQUEST['_nonce'], 'chip_gateway_change')) 
			{
                wp_send_json_error();
            }
            
            $settings = get_option('charitable_settings');
            $settings['default_gateway'] = "chip";
            $updated = update_option('charitable_settings', $settings);
            
            wp_send_json(array('success' => $updated));
            wp_die();
        }

        /**
         * Return the HTML for the currency notice.
         *
         * @return  string
         * @access  public
         * @since   1.0.0
         */
        public function get_currency_notice()
        {
            ob_start();

            ?>        
            <?php
            printf(__('CHIP only accepts payments in Malaysian Ringgit. %sChange Now%s', 'charitable-chip'), '<a href="#" class="button" data-change-currency-to-myr>', '</a>'
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
                                _nonce: "<?php echo wp_create_nonce('chip_currency_change') ?>"
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
        public static function change_currency_to_myr()
        {
            if (!wp_verify_nonce($_REQUEST['_nonce'], 'chip_currency_change')) {
                wp_send_json_error();
            }

            $settings = get_option('charitable_settings');
            $settings['currency'] = 'MYR';
            $updated = update_option('charitable_settings', $settings);

            wp_send_json(array('success' => $updated));
            wp_die();
        }
        /*
         * Listen to CHIP Callback & Return
         */

        public static function ipn_listener()
        {
            new Charitable_Gateway_Chip_IPN_Listener;
        }
        /*
         * Add option to hide some element that not required by CHIP API
         */

        public static function add_chip_fields($field)
        {
            $general_fields = array(
                'chip_section_pages' => array(
                    'title' => __('CHIP for WP Charitable', 'charitable'),
                    'type' => 'heading',
                    'priority' => 50,
                ),
                'chip_full_name' => array(
                    'title' => __('Replace First & Last Name with Full Name', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Use Malaysian Style Naming',
                    'priority' => 60,
                ),
                'chip_rem_add' => array(
                    'title' => __('Remove Address 1 & 2 Field', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Remove Address Field on Payment',
                    'priority' => 70,
                ),
                'chip_rem_city' => array(
                    'title' => __('Remove City Field', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Remove City Field on Payment',
                    'priority' => 80,
                ),
                'chip_rem_state' => array(
                    'title' => __('Remove State Field', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Remove State Field on Payment',
                    'priority' => 90,
                ),
                'chip_rem_postcode' => array(
                    'title' => __('Remove Postcode Field', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Remove Postcode Field on Payment',
                    'priority' => 100,
                ),
                'chip_rem_country' => array(
                    'title' => __('Remove Country Field', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Remove Country Field on Payment',
                    'priority' => 110,
                ),
                'chip_mak_phone' => array(
                    'title' => __('Phone Required', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Make Phone Fields Mandatory to be set',
                    'priority' => 120,
                ),
                'chip_unr_email' => array(
                    'title' => __('Unrequire Email', 'charitable'),
                    'type' => 'checkbox',
                    'help' => 'Make Email Fields Optional to be set. NOT RECOMMENDED',
                    'priority' => 120,
                ),
            );
            $field = array_merge($field, $general_fields);
            return $field;
        }

        public static function remove_unrequired_fields($fields)
        {

            $full_name = charitable_get_option('chip_full_name', false);
            $address = charitable_get_option('chip_rem_add', false);
            $city = charitable_get_option('chip_rem_city', false);
            $state = charitable_get_option('chip_rem_state', false);
            $postcode = charitable_get_option('chip_rem_postcode', false);
            $country = charitable_get_option('chip_rem_country', false);
            $phone = charitable_get_option('chip_mak_phone', false);
            $email = charitable_get_option('chip_unr_email', false);

            if ($full_name) {
                unset($fields['last_name']);
                $fields['first_name']['label'] = __('Name', 'charitable');
            }

            if ($address) {
                unset($fields['address']);
                unset($fields['address_2']);
            }

            if ($city) {
                unset($fields['city']);
            }
            if ($state) {
                unset($fields['state']);
            }
            if ($postcode) {
                unset($fields['postcode']);
            }
            if ($country) {
                unset($fields['country']);
            }

            if ($phone) {
                $fields['phone']['required'] = true;
            }

            if ($email) {
                $fields['email']['required'] = false;
            }

            return $fields;
        }
    }

} // End class_exists check
