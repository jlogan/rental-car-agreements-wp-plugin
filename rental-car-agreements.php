<?php
/**
 * Plugin Name: Rental Car Agreements
 * Plugin URI: https://brogrammersagency.com
 * Description: A lightweight rental car inventory and booking agreement system for WordPress.
 * Version: 1.0.0
 * Author: Brogrammers Agency
 * Author URI: https://brogrammersagency.com
 * License: GPL v2 or later
 * Text Domain: rental-car-agreements
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define Constants
define( 'RCA_VERSION', '1.0.0' );
define( 'RCA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RCA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include Core Files
require_once RCA_PLUGIN_DIR . 'includes/helpers.php';
require_once RCA_PLUGIN_DIR . 'includes/class-cpt-vehicles.php';
require_once RCA_PLUGIN_DIR . 'includes/class-cpt-bookings.php';
require_once RCA_PLUGIN_DIR . 'includes/class-settings-page.php';
require_once RCA_PLUGIN_DIR . 'includes/class-frontend.php';
require_once RCA_PLUGIN_DIR . 'includes/class-agreement-template.php';

/**
 * Main Plugin Class
 */
class Rental_Car_Agreements {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Initialize Classes
		new RCA_CPT_Vehicles();
		new RCA_CPT_Bookings();
		new RCA_Settings_Page();
		new RCA_Frontend();
		new RCA_Agreement_Template();

		// Enqueue Assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'rca-style', RCA_PLUGIN_URL . 'assets/css/style.css', array(), RCA_VERSION );
		wp_enqueue_script( 'rca-script', RCA_PLUGIN_URL . 'assets/js/frontend.js', array(), RCA_VERSION, true );
		
		wp_localize_script( 'rca-script', 'rca_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		));
	}
}

// Initialize Plugin
add_action( 'plugins_loaded', array( 'Rental_Car_Agreements', 'get_instance' ) );

// Activation Hook
register_activation_hook( __FILE__, 'rca_activate_plugin' );
function rca_activate_plugin() {
	// Trigger CPT registration immediately for flush rewrite rules
	require_once RCA_PLUGIN_DIR . 'includes/class-cpt-vehicles.php';
	$vehicles = new RCA_CPT_Vehicles();
	$vehicles->register_post_type();
	
	flush_rewrite_rules();
}

