<?php

/**
 * Plugin Name: CHIP for WP Charitable
 * Requires Plugins: charitable
 * Plugin URI: https://github.com/CHIPAsia/chip-for-wpcharitable
 * Description: CHIP - Digital Finance Platform
 * Version: 1.0.0
 * Requires PHP: 7.1
 * Copyright: © 2024 CHIP
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * 
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
	new Charitable_Chip( __FILE__ );
}

add_action( 'plugins_loaded', 'charitable_chip_load', 1 );
