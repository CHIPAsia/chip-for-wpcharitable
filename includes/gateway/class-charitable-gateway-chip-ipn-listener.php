<?php
if (!class_exists('Charitable_Gateway_Chip_IPN_Listener')) {

    class Charitable_Gateway_Chip_IPN_Listener
    {
        const QUERY_VAR = 'chip_charitable_id';

        function __construct()
        {
            error_log('Construct in Listener');

            if (isset($_GET['chip_charitable_id'])) {
                $this->listen();
            } else {
                error_log('Missing parameter chip_charitable_id');
            }
        }

        /**
         * Build the callback URL
         */
        public static function get_listener_url($donation)
        {
            // In callback url return the donation id
            return add_query_arg(self::QUERY_VAR, $donation->ID, site_url('/')); // Example: /?chip_charitable_id=123
        }

        private function listen()
        {

            // Get the donation ID
            if ( !isset($_GET['chip_charitable_id']) ) {
                error_log('No donation ID parameter is set');
            }

            // Set donation ID
            if ( empty($get_donation_id = intval($_GET['chip_charitable_id'])) ) {
                error_log('donation ID parameter is empty');
            }

            // Get Input
            if ( empty($content = file_get_contents('php://input')) ) {
                error_log('No input received');
            }


            // Check X-Signature is set
            if ( !isset($_SERVER['HTTP_X_SIGNATURE']) ) {
                error_log('No X Signature received from headers');
            } else {
                error_log('X-Signature:' . $_SERVER['HTTP_X_SIGNATURE']);
            }

            // Get Public Key
            $settings = get_option('charitable_settings');
            $public_key = $settings['gateways_chip']['public_key'];

            // 
            if ( \openssl_verify( $content,  \base64_decode($_SERVER['HTTP_X_SIGNATURE']), $public_key, 'sha256WithRSAEncryption' ) != 1 ) {
                \header( 'Forbidden', true, 403 );
                error_log('Invalid X Signature');
            } else {
                error_log('X-Signature is valid');
            }

            

            // die();

            $this->update_order_status();
            error_log('Successful Callback');
            // wp_die('Successful Callback');
        }

        private function update_order_status()
        {
            $gateway = new Charitable_Gateway_Chip();
            $keys = $gateway->get_keys();

            /*
             * Support for Advance Chip for WP Charitable Plugin
             */

            // Handle callback to update order status
        }
    }

}