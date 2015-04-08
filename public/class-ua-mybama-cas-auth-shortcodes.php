<?php

/**
 * Contains all of the plugin's shortcodes.
 *
 * @link       https://webtide.ua.edu
 * @since      1.0
 *
 * @package    UA_myBama_CAS_Auth
 * @subpackage UA_myBama_CAS_Auth/public
 */

/**
 * Contains all of the plugin's shortcodes.
 *
 * @package    UA_myBama_CAS_Auth
 * @subpackage UA_myBama_CAS_Auth/public
 * @author     Rachel Carden <rmcarden@ur.ua.edu>
 */
class UA_myBama_CAS_Auth_Shortcodes {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0
	 * @access   private
	 * @var      string    $plugin_id    The ID of this plugin.
	 */
	private $plugin_id;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0
	 * @var      string    $plugin_id	The ID of the plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_id, $version ) {

		$this->plugin_id = $plugin_id;
		$this->version = $version;

	}
	
	/**
	 * Shortcode that only shows content if the user is authenticated.
	 *
	 * @since    1.0
	 * @var      array      $args					        The argument parameters passed to the shortcode
	 * @var      string     $shortcode_content	            Content that was passed within the shortcode declaration.
	 * @return   string     the built shortcode content
	 */
	public function require_mybama_auth( $args, $shortcode_content = NULL ) {
		global $ua_mybama_cas_auth;
		
		// Parse the args
		$defaults = array(
			'show_logged_in_message'=> false,
			'need_to_login_message'	=> 'This content requires you to login through myBama.',
		);
		extract( wp_parse_args( $args, $defaults ), EXTR_OVERWRITE );
		
		// Build the content
		$content = NULL;
		
		// Return a message letting them know they need to be authenticated
		if ( ! $ua_mybama_cas_auth->is_authenticated() ) {
			
			// Get login URL
			$login_url = $ua_mybama_cas_auth->get_login_url();
			
			// Filter the "need to login" message
			$need_to_login_message = apply_filters( 'ua_mybama_cas_auth_need_to_mybama_login_shortcode_message', '<p>' . $need_to_login_message . ' <a href="' . $login_url . '">Login</a></p>', $args, $login_url );
			
			// Add the message
			if ( ! empty( $need_to_login_message ) )
				$content .= '<div class="ua-mybama-cas-auth-need-to-login-message">' . $need_to_login_message . '</div>';
			
		} else {
			
			// If we're showing the logged in message
			if ( isset( $show_logged_in_message ) && ( 1 == $show_logged_in_message || strcasecmp( 'true', $show_logged_in_message ) == 0 ) ) {
				
				// Get the user name
				$session_user = $ua_mybama_cas_auth->get_username();
		
				// Get user attributes
				$session_user_firstname = $ua_mybama_cas_auth->get_user_attribute( 'firstname' );
				$session_user_lastname = $ua_mybama_cas_auth->get_user_attribute( 'lastname' );
				
				// Get logout URL
				$logout_url = $ua_mybama_cas_auth->get_logout_url();
				
				// @TODO add default styles?
				$content .= '<div class="ua-mybama-cas-auth-logged-in">You are logged in as ';
				
					if ( ! empty( $session_user_firstname ) ) {
						
						// Print first name
						$content .= $session_user_firstname;
						
						// Print last name
						if ( ! empty( $session_user_lastname ) )
							$content .= " {$session_user_lastname}";
							
						$content .= " ({$session_user})";
							
					} else {
					
						// Otherwise, just print the user name
						$content .= $session_user;
						
					}
						
				$content .= '. <a href="' . $logout_url . '">Logout</a></div>';
				
			}
			
			$content .= do_shortcode( $shortcode_content );
			
		}
		
		return $content;
	
	}
	
	/**
	 * Shortcode that only shows content if the user is logged into WordPress.
	 *
	 * @since    1.0
	 * @var      array    $args					The argument parameters passed to the shortcode
	 * @var      string   $shortcode_content	Content that was passed within the shortcode declaration.
	 */
	public function require_wp_login( $args, $shortcode_content = NULL ) {
		
		// Parse the args
		$defaults = array(
			'show_logged_in_message'		=> false,
			'show_need_to_login_message'	=> true,
			'need_to_login_message'			=> 'This content requires you to login.',
			'current_user_can'				=> false,
			'show_current_user_cant_message'=> true,
			'current_user_cant_message'		=> 'You do not have permission to view this content.',
		);
		
		// Update the args
		$args = wp_parse_args( $args, $defaults );
		
		// Extract the args
		extract( $args, EXTR_OVERWRITE );
		
		// Build the content
		$content = NULL;
		
		// Return a message letting them know they need to be logged in
		if ( ! is_user_logged_in() ) {
			
			// If we're supposed to show the "need to login" message
			if ( isset( $show_need_to_login_message )
				&& ( 1 == $show_need_to_login_message || strcasecmp( 'true', $show_need_to_login_message ) == 0 ) ) {
				
				// Get login URL
				$login_url = wp_login_url();
			
				// Filter the "need to login" message
				$need_to_login_message = apply_filters( 'ua_mybama_cas_auth_need_to_wordpress_login_shortcode_message', '<p>' . $need_to_login_message . ' <a href="' . $login_url . '">Login</a></p>', $args, $login_url );
				
				// Add the message
				if ( ! empty( $need_to_login_message ) )
					$content .= '<div class="ua-mybama-cas-auth-need-to-login-message">' . $need_to_login_message . '</div>';
				
			}
		
		// If the user is logged in...
		} else {
			
			// If they passed user capabilities, then convert to array and test
			if ( isset( $current_user_can ) && ! empty( $current_user_can ) ) {
				
				// Separate user capabilities by comma
				$current_user_can = explode( ',', $current_user_can );
				
				// Test each user capability
				if ( ! empty( $current_user_can ) && is_array( $current_user_can ) ) {
					
					// Will be false if the current user didn't pass a capability test
					$user_passed_cap_test = true;
					
					// Test each user capability
					foreach( $current_user_can as $user_cap ) {
						
						// If the user doesn't pass a cap test, then there's no point in continuing the tests
						if ( ! current_user_can( $user_cap ) ) {
							$user_passed_cap_test = false;
							break;
						}
						
					}
					
					// This means we can't show them the content
					if ( ! $user_passed_cap_test ) {
						
						// If we're supposed to show the "current user cant" message
						if ( isset( $show_current_user_cant_message )
							&& ( 1 == $show_current_user_cant_message || strcasecmp( 'true', $show_current_user_cant_message ) == 0 ) ) {
				
							// Filter the "current user cant" message
							$current_user_cant_message = apply_filters( 'ua_mybama_cas_auth_current_user_cant_shortcode_message', '<p>' . $current_user_cant_message . '</p>', $args );
				
							// Add the message
							if ( ! empty( $current_user_cant_message ) )
								$content .= '<div class="ua-mybama-cas-auth-current-user-cant-message">' . $current_user_cant_message . '</div>';
							
						}
						
						return $content;					
						
					}
					
				}
				
			}
			
			// If we're showing the logged in message
			if ( isset( $show_logged_in_message ) && ( 1 == $show_logged_in_message || strcasecmp( 'true', $show_logged_in_message ) == 0 ) ) {
				
				// Get the current user
				$current_user = wp_get_current_user();
				
				// Get logout URL
				$logout_url = wp_logout_url();
				
				// @TODO add default styles?
				$content .= '<div class="ua-mybama-cas-auth-logged-in">You are logged in as ' . $current_user->display_name . '. <a href="' . $logout_url . '">Logout</a></div>';
				
			}
			
			$content .= do_shortcode( $shortcode_content );
			
		}
		
		return $content;
	
	}

}