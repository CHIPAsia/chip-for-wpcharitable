<?php

/**
 * Charitable CHIP Gateway Hooks.
 *
 * Action/filter hooks used for handling payments through the CHIP gateway.
 *
 * @package     Charitable Chip/Hooks/Gateway
 * @version     1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/**
 * Process the donation.
 *
 * @see     Charitable_Gateway_Chip::process_donation()
 */
// add_filter('charitable_process_donation_chip', array('Charitable_Gateway_Chip', 'redirect_to_processing'), 10, 3);
if (-1 == version_compare(charitable()->get_version(), '1.3.0')) {
    /** 
     * This is for backwards-compatibility. Charitable before 1.3 used on action hook, not a filter.
     * 
     * @see     Charitable_Gateway_Chip::redirect_to_processing_legacy()
     */
    // add_action( 'charitable_process_donation_chip', array( 'Charitable_Gateway_Chip', 'redirect_to_processing_legacy' ) );
    error_log('Calling redirect_to_processing_legacy()');
} else {
    add_filter('charitable_process_donation_chip', array('Charitable_Gateway_Chip', 'redirect_to_processing'), 10, 2);
}

/**
 * Make the "email" field required in the donation form since CHIP requires it.
 *
 * @see Charitable_Gateway_Chip::set_email_field_required()
 */
add_filter('charitable_donation_form_user_fields', array('Charitable_Gateway_Chip', 'set_email_field_required'));

/**
 * Handle public key
 */
add_action('update_option_charitable_settings', array('Charitable_Chip', 'store_public_key'), 10, 2);

/**
 * Render the CHIP donation processing page content.
 *
 * This is the page that users are redirected to after filling out the donation form.
 * It automatically redirects them to CHIP's website.
 *
 * @see Charitable_Gateway_Chip::process_donation()
 */
add_filter('charitable_processing_donation_chip', array('Charitable_Gateway_Chip', 'process_donation'), 10, 2);

/**
 * Check the response from CHIP after the donor has completed payment.
 *
 * @see Charitable_Gateway_Chip::process_response()
 */
add_action('charitable_donation_receipt_page', array('Charitable_Gateway_Chip', 'process_response'));

/**
 * Change the currency to MYR.
 *
 * @see Charitable_Gateway_Chip::change_currency_to_myr()
 */
add_action('wp_ajax_charitable_change_currency_to_myr', array('Charitable_Gateway_Chip', 'change_currency_to_myr'));

/**
 * IPN listener.
 *
 * @see     charitable_ipn_listener()
 */
add_action('init', array('Charitable_Gateway_Chip', 'callback_listener'));


/**
 * Change the default gateway to CHIP
 *
 * @see Charitable_Gateway_Chip::change_gateway_to_chip())
 */
add_action('wp_ajax_charitable_change_gateway_to_chip', array('Charitable_Gateway_Chip', 'change_gateway_to_chip'));
