<?php

/**
 * Plugin Name: CHIP for WP Charitable
 * Plugin URI: https://github.com/CHIPAsia/chip-for-wpcharitable
 * Description: CHIP - Digital Finance Platform
 * Version: 1.0.0
 * Requires PHP: 7.1
 * Copyright: © 2024 CHIP
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load plugin class, but only if Charitable is found and activated.
 * 
 */
function charitable_chip_load() {
	require_once( 'includes/class-charitable-chip.php' );

	$has_dependencies = true;

	/* Check for Charitable */
	if ( ! class_exists( 'Charitable' ) ) {

		if ( ! class_exists( 'Charitable_Extension_Activation' ) ) {

			require_once 'includes/class-charitable-extension-activation.php';
		}

		$activation = new Charitable_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
		$activation = $activation->run();

		$has_dependencies = false;
	} else {

		new Charitable_Chip( __FILE__ );
	}
}

add_action( 'plugins_loaded', 'charitable_chip_load', 1 );

/*
 *  Remove Record created by this plugin
 */
register_uninstall_hook( __FILE__, 'charitable_chip_uninstall' );
function charitable_chip_uninstall() {
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'chip_charitable_bill_id_%'" );
}
