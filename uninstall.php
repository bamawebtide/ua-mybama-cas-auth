<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://webtide.ua.edu
 * @since      1.0.0
 *
 * @package    UA_myBama_CAS_Auth
 */

global $wpdb;

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options
$wpdb->delete( $wpdb->options, array( 'option_name' => 'ua_mybama_cas_auth_settings' ) );

// Delete post meta
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_requires_mybama_authentication' ) );
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_requires_wordpress_authentication' ) );
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_requires_mybama_authentication_wp_search_results' ) );
$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_requires_wordpress_authentication_wp_search_results' ) );

// If multisite, delete individual site options and postmeta for each site
if ( $blogs = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM {$wpdb->blogs} ORDER BY blog_id", NULL ) ) ) {

	foreach ( $blogs as $this_blog_id ) {

		// Set blog id so $wpdb will know which table to tweak
		$wpdb->set_blog_id( $this_blog_id );

		// Delete site options for each site
		$wpdb->delete( $wpdb->options, array( 'option_name' => 'ua_mybama_cas_auth_settings' ) );

		// Delete post meta for each site
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_requires_mybama_authentication' ) );
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_requires_wordpress_authentication' ) );
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_requires_mybama_authentication_wp_search_results' ) );
		$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_requires_wordpress_authentication_wp_search_results' ) );

	}

}