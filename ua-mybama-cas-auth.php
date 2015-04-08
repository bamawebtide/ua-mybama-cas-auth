<?php

/**
 * @link              https://webtide.ua.edu
 * @since             1.0
 * @package           UA_myBama_CAS_Auth
 *
 * @wordpress-plugin
 * Plugin Name:       University of Alabama MyBama CAS Authentication
 * Plugin URI:        https://webtide.ua.edu
 * Description:       Contains all the functionality needed to setup myBama authentication (and single sign-on) for your WordPress site via a CAS server.
 * Version:           1.0
 * Author:            Rachel Carden, WebTide
 * Author URI:        https://webtide.ua.edu
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ua-mybama-cas-auth
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ua-mybama-cas-auth-activator.php
 */
function activate_ua_mybama_cas_auth() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ua-mybama-cas-auth-activator.php';
	UA_myBama_CAS_Auth_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ua-mybama-cas-auth-deactivator.php
 */
function deactivate_ua_mybama_cas_auth() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ua-mybama-cas-auth-deactivator.php';
	UA_myBama_CAS_Auth_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ua_mybama_cas_auth' );
register_deactivation_hook( __FILE__, 'deactivate_ua_mybama_cas_auth' );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ua-mybama-cas-auth.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0
 */
function run_ua_mybama_cas_auth() {
	global $ua_mybama_cas_auth;
	
	$ua_mybama_cas_auth = new UA_myBama_CAS_Auth();
	$ua_mybama_cas_auth->run();

}
run_ua_mybama_cas_auth();

/**
 * Returns boolean on whether the current
 * user is authenticated.
 *
 * @since    1.0
 */
function is_ua_mybama_cas_authenticated() {
	global $ua_mybama_cas_auth;
	
	// Only true if they're authenticated through myBama
	return isset( $ua_mybama_cas_auth ) && $ua_mybama_cas_auth->is_authenticated();
	
}