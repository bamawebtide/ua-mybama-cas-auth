<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       https://webtide.ua.edu
 * @since      1.0.0
 *
 * @package    UA_myBama_CAS_Auth
 * @subpackage UA_myBama_CAS_Auth/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    UA_myBama_CAS_Auth
 * @subpackage UA_myBama_CAS_Auth/includes
 * @author     Rachel Carden <rmcarden@ur.ua.edu>
 */
class UA_myBama_CAS_Auth {

	/**
	 * The loader that's responsible for maintaining
	 * and registering all hooks that power the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      UA_myBama_CAS_Auth_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_id    The string used to uniquely identify this plugin.
	 */
	protected $plugin_id;

	/**
	 * The path to the plugin's main file.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_file    The path to the plugin's main file.
	 */
	protected $plugin_file;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;
	
	/**
	 * The plugin's custom user settings
	 * pulled from the options table.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $settings    The plugin's custom user settings
	 */
	private $settings;

	/**
	 * Boolean value that is set to true
	 * if test mode is enabled.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      boolean    $in_test_mode    Will be true if test mode is enabled
	 */
	protected $in_test_mode;

	/**
	 * Boolean value that is set to true
	 * if single sign-on is enabled.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      boolean    $is_single_sign_on    Will be true if single sign-on is enabled
	 */
	protected $is_single_sign_on;

	/**
	 * Boolean value that is set to true if
	 * the CAS client has been initialized.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      boolean    $client_is_initialized    Will be true if client has been initialized
	 */
	protected $client_is_initialized;
	
	/**
	 * Boolean value that is set to true if
	 * the user failed a new authentication attempt.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      boolean    $failed_new_authentication    Will be true if user failed new authentication attempt
	 */
	private $failed_new_authentication;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		// Set some plugin information
		$this->plugin_id = 'ua-mybama-cas-auth';
		$this->plugin_file = 'ua-mybama-cas-auth/ua-mybama-cas-auth.php';
		$this->version = '1.0.0';

		// Register/load some plugin stuff
		$this->load_dependencies();
		$this->set_locale();
		
		// Define stuff
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_common_hooks();
		$this->define_shortcodes();

	}
	
	/**
	 * Get the authenticated username.
	 *
	 * @since    1.0.0
	 */
	public function get_username() {
		
		// Make sure they're authenticated
		if ( ! $this->is_authenticated() )
			return false;
		
		// If no user, return false
		return ( $user = phpCAS::getUser() ) ? $user : false;
			
	}
	
	/**
	 * Get a specific user attribute.
	 *
	 * @since    1.0.0
	 */
	public function get_user_attribute( $attribute ) {
		
		// First we need the attributes
		if ( ! ( $user_attributes = $this->get_user_attributes() ) )
			return false;
		
		// If set, return the attribute
		return isset( $user_attributes[ $attribute ] ) ? $user_attributes[ $attribute ] : false;
			
	}
	
	/**
	 * Get all of the authenticated user's attributes.
	 *
	 * @since    1.0.0
	 */
	public function get_user_attributes() {
		
		// Make sure they're authenticated
		if ( ! $this->is_authenticated() )
			return false;
			
		// If no user attributes, return false
		return ( $user_attributes = phpCAS::getAttributes() ) ? $user_attributes : false;
			
	}
	
	/**
	 * If single sign-on is enabled, it checks to see
	 * if a user is newly authenticated and, if so,
	 * logs them into WordPress.
	 *
	 * @since   1.0.0
	 */
	public function check_for_new_authentication() {
		
		// Only do for front end
		if ( is_admin() )
			return;
			
		// Get our "attempt to authenticate" timestamp value
		// Only need to run this check if we have a cookie value
		if ( isset( $_COOKIE[ 'ua_mybama_cas_auth_attempting_authentication' ] )
			&& ( $attempt_authentication_start = intval( $_COOKIE[ 'ua_mybama_cas_auth_attempting_authentication' ] ) )
			&& $attempt_authentication_start > 0 ) {
			
			// If the authentication attempt started within the last 5 minutes
			if ( $attempt_authentication_start >= ( time() - 300 ) ) {
				
				// If a new user is authenticated, run them through a few tests...
				if ( $this->is_authenticated() ) {
					
					// Important to go ahead and delete the cookie because we might get redirected below
					setcookie( 'ua_mybama_cas_auth_attempting_authentication', ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, true, true );
					
					// What's the user name
					$username = $this->get_username();
					
					// Return true to this filter to stop new authentication and log them out
					// This allows you to run "after the fact" authentication with user info to decide if they should gain access
					$this->failed_new_authentication = apply_filters( 'ua_mybama_cas_auth_fail_new_authentication', false, $username, $this->get_user_attributes() );
					
					// If they fail after the fact authentication, log them out
					if ( $this->failed_new_authentication ) {
						
						// Log them out
						if ( $this->is_single_sign_on() )
							wp_logout();
						else
							$this->logout();
							
					}
					
					// Check to see if we have whitelist and/or blacklist settings
					$mybama_username_whitelist = $this->get_mybama_username_whitelist();
					$mybama_username_blacklist = $this->get_mybama_username_blacklist();
					
					// If there is a blacklist, and they're on it...
					if ( isset( $mybama_username_blacklist )
						&& is_array( $mybama_username_blacklist )
						&& ! empty( $mybama_username_blacklist )
						&& in_array( $username, $mybama_username_blacklist ) ) {
							
						// @TODO we need an error system
						
						// Log them out
						if ( $this->is_single_sign_on() )
							wp_logout();
						else
							$this->logout();
						
					// If there is a whitelist, and they're NOT on it...
					} else if ( isset( $mybama_username_whitelist )
						&& is_array( $mybama_username_whitelist )
						&& ! empty( $mybama_username_whitelist )
						&& ! in_array( $username, $mybama_username_whitelist ) ) {
							
						// @TODO we need an error system
						
						// Log them out
						if ( $this->is_single_sign_on() )
							wp_logout();
						else
							$this->logout();
				
					// They're good...
					} else {
					
						// If single sign-on is enabled...
						if ( $this->is_single_sign_on() ) {
							
							// Try to sign them into WordPress
							if ( $this->sign_user_into_wordpress() ) {
							
								// If the sign on was successful, we need to redirect so the page shows they're logged in
								
								// Create our redirect URL
								$redirect_url = NULL;
								
								// Is a "redirect_to" set already?
								if ( isset( $_GET[ 'redirect_to' ] ) ) {
									
									$redirect_url = $_GET[ 'redirect_to' ];
								
								// Otherwise, set the current URL
								} else {
								
									// Create redirect URL
									$redirect_url = $this->get_current_url();
									
								}
									
								// If we have a redirect URL...
								if ( $redirect_url ) {
									
									// Make sure the 'ua-mybama-cas-auth-login' is removed
									$redirect_url = add_query_arg( 'ua-mybama-cas-auth-login', NULL, $redirect_url );
									
									// Redirect!
									wp_redirect( $redirect_url );
									exit;
									
								}
							
							// Make sure they're logged out everywhere
							// @TODO fix?
							// THIS DIDN'T WORK BECAUSE THIS DOESN'T NECESSARILY MEAN TO LOG THEM OUT OF CAS, JUST NOT INTO WORDPRESS
							} /*else {
								
								// @TODO add a message telling the user whats going on
						
								// Log them out of WordPress (which logs them out of CAS)
								// wp_logout();
								
							}*/
							
						}
						
					}
					
				}
				
			// If our option is invalid...
			} else {
				
				// @TODO add a message telling the user whats going on
				
				// Log them out
				if ( $this->is_single_sign_on() )
					wp_logout();
				else
					$this->logout();
				
			}
			
		}
		
	}
	
	/**
	 * Defines the IS_MYBAMA_AUTHENTICATED constant.
	 *
	 * @since   1.0.0
	 */
	public function define_is_mybama_authenticated() {
		
		define( 'IS_MYBAMA_AUTHENTICATED', $this->is_authenticated() );
	
	}
	
	/**
	 * Attempts to sign the authenticated user
	 * into WordPress by their myBama user name.
	 *
	 * @since   1.0.0
	 * @return	boolean - whether the login was successful or not
	 */
	public function sign_user_into_wordpress() {
		
		// Make sure single sign-on is enabled
		if ( ! $this->is_single_sign_on() )
			return false;
		
		// Make sure they are authenticated
		if ( ! $this->is_authenticated() )
			return false;
			
		// Make sure we have a user
		if ( ! ( $username = $this->get_username() ) )
			return false;
			
		// Check to see if we have whitelist and/or blacklist settings
		$wp_login_whitelist = $this->get_wordpress_login_whitelist();
		$wp_login_blacklist = $this->get_wordpress_login_blacklist();
		
		// If there is a blacklist, and they're on it...
		if ( isset( $wp_login_blacklist )
			&& is_array( $wp_login_blacklist )
			&& ! empty( $wp_login_blacklist )
			&& in_array( $username, $wp_login_blacklist ) ) {
				
			// Do not log them in to WordPress!
			return false;
			
		// If there is a whitelist, and they're NOT on it...
		} else if ( isset( $wp_login_whitelist )
			&& is_array( $wp_login_whitelist )
			&& ! empty( $wp_login_whitelist )
			&& ! in_array( $username, $wp_login_whitelist ) ) {
				
			// Do not log them in to WordPress!
			return false;
			
		}
		
		// If they have a matching WordPress user account...
		if ( ( $wordpress_user = get_user_by( 'login', $username ) )
			&& is_a( $wordpress_user, 'WP_User' ) ) {
				
			// Do we have permission to update their WP data to match myBama data?
			$match_user_data = $this->get_setting( 'sso_match_user_data' );
			
			// If we can update their data...
			if ( ! ( isset( $match_user_data ) && ! $match_user_data ) ) {
				
				// Create user data to update
				$user_data = array();
				
				// If we have an email, add it
				if ( $user_email = $this->get_user_attribute( 'email' ) )
					$user_data[ 'user_email' ] = $user_email;
					
				// If we have a first name, add it - set as nickname too
				if ( $user_first_name = $this->get_user_attribute( 'firstname' ) ) {
					
					// Set first name and nickname
					$user_data[ 'first_name' ] = $user_first_name;
					$user_data[ 'nickname' ] = $user_first_name;
					
				}
				
				// If we have a last name, add it
				if ( $user_last_name = $this->get_user_attribute( 'lastname' ) ) {
					
					// Set last name]
					$user_data[ 'last_name' ] = $user_last_name;
					
				}
					
				// Create display name
				$user_display_name = isset( $user_first_name ) && ! empty( $user_first_name ) ? $user_first_name : NULL;
				
				// If we have a last name, add it
				if ( isset( $user_last_name ) && ! empty( $user_last_name ) )
					$user_display_name .= " {$user_last_name}";
					
				// If we have a display name, add it
				if ( isset( $user_display_name ) && ! empty( $user_display_name ) ) {
					
					// Set display name
					$user_data[ 'display_name' ] = $user_display_name;
					
				}
				
				// Filter the user data
				$user_data = apply_filters( 'ua_mybama_cas_auth_sso_update_user_data', $user_data, $wordpress_user->ID );
				
				// Add the user ID
				$user_data[ 'ID' ] = $wordpress_user->ID;
				
				// Update their user data	
				wp_update_user( $user_data );
				
			}
			
			// Log them in!
			$current_user_sign_on = wp_signon( array(
				'user_login'	=> isset( $wordpress_user->data ) && isset( $wordpress_user->data->user_login ) ? $wordpress_user->data->user_login : NULL,
				'user_password' => isset( $wordpress_user->data ) && isset( $wordpress_user->data->user_pass ) ? $wordpress_user->data->user_pass : NULL,
				));
			
			// If the result is a WP_User then we're good to go
			if ( is_a( $current_user_sign_on, 'WP_User' ) ) {
				
				// Allow others to run actions here - false represents its not a new user
				do_action( 'ua_mybama_cas_auth_sso_user_logged_in', $current_user_sign_on, false );
				
				return true;
				
			} 
			
			return false;
		
		} else {
			
			// Check the settings to see if we can create a new profile - default is yet
			$create_matching_profile = $this->get_setting( 'sso_create_matching_profile' );
			
			// If we can't, then get out of here
			if ( isset( $create_matching_profile ) && ! $create_matching_profile )
				return false;

			// Otherwise, set up a new account
			
			// What user role should be assigned?
			$user_role = $this->get_setting( 'sso_matching_profile_user_role' );
				
			// Create a password
			$new_user_password = wp_generate_password( 30, true, true ); // Don't want to set as myBama password
			
			// Create user data
			$user_data = array(
				'user_login'	=> $username,
				'user_pass'		=> $new_user_password,
				'user_email'	=> $this->get_user_attribute( 'email' ),
				'role'			=> isset( $user_role ) && ! empty( $user_role ) ? $user_role : NULL,
				);
				
			// If we have a first name, add it - set as nickname too
			if ( $user_first_name = $this->get_user_attribute( 'firstname' ) ) {
				
				// Set first name and nickname
				$user_data[ 'first_name' ] = $user_first_name;
				$user_data[ 'nickname' ] = $user_first_name;
				
			}
			
			// If we have a last name, add it
			if ( $user_last_name = $this->get_user_attribute( 'lastname' ) )
				$user_data[ 'last_name' ] = $user_last_name;
				
			// Create display name
			$user_display_name = isset( $user_first_name ) && ! empty( $user_first_name ) ? $user_first_name : NULL;
					
			// If we have a last name, add it
			if ( isset( $user_last_name ) && ! empty( $user_last_name ) )
				$user_display_name .= " {$user_last_name}";
				
			// Add display name
			$user_data[ 'display_name' ] = isset( $user_display_name ) && ! empty( $user_display_name ) ? $user_display_name : $username;
				
			// Create the user
			if ( ( $new_user_id = wp_insert_user( $user_data ) )
				&& ! is_wp_error( $new_user_id )
				&& $new_user_id > 0 ) {
					
				// Log them in!
				$new_user_sign_on = wp_signon( array(
					'user_login'	=> $username,
					'user_password' => $new_user_password,
					));
			
				// If the result is a WP_User then we're good to go
				if ( is_a( $new_user_sign_on, 'WP_User' ) ) {
					
					// Allow others to run actions here - true represents its a new user
					do_action( 'ua_mybama_cas_auth_sso_user_logged_in', $current_user_sign_on, true );
				
					return true;
					
				} 
				
				return false;
				
			}
			
		}
		
		return false;
		
	}
	
	/**
	 * Is run on the 'authenticate' filter
	 * which allows us to override the
	 * wp_authenticate_username_password() function
	 * which checks the autheniticy of the user name
	 * and password.
	 *
	 * We do our own myBama authentication and
	 * return true to allow WordPress's authentication
	 * to pass and let the user login.
	 *
	 * @since   1.0.0
	 * @param 	WP_User|WP_Error|null	$user     		WP_User or WP_Error object from a previous callback. Default is null.
	 * @param	string                	$wp_username 	Username for authentication
	 * @param	string                	$wp_password	Password for authentication
	 * @return	WP_User|WP_Error 						WP_User on success, WP_Error on failure
	 */
	public function authenticate_wp_username_password( $authenticate, $wp_username, $wp_password ) {
		
		// Only do for front end
		if ( is_admin() )
			return;
			
		// Make sure single sign-on is enabled
		if ( ! $this->is_single_sign_on() )
			return $authenticate;
			
		// Make sure they are authenticated
		if ( ! $this->is_authenticated() )
			return $authenticate;
			
		// Make sure we have a user
		if ( ! ( $mybama_username = $this->get_username() ) )
			return $authenticate;
			
		// If user names match, and we have the user, then approve the authentication
		if ( $mybama_username === $wp_username
			&& ( $wp_user = get_user_by( 'login', $wp_username ) )
			&& is_a( $wp_user, 'WP_User' ) ) {
				
			return $wp_user;
			
		}

		return $authenticate;
		
	}
	
	/**
	 * Returns the login URL.
	 *
	 * @since   1.0.0
	 */
	public function get_login_url() {
		
		// Build login URL
		$login_url = add_query_arg( 'ua-mybama-cas-auth-login', 'true', get_bloginfo( 'url' ) );
		
		// Define the "redirect_to"
		$redirect_to = NULL;
		
		// Is a "redirect_to" set already?
		if ( isset( $_GET[ 'redirect_to' ] ) && ! empty( $_GET[ 'redirect_to' ] ) ) {
			
			$redirect_to = $_GET[ 'redirect_to' ];
		
		// Otherwise set the current URL
		} else if ( $current_url = $this->get_current_url() ) {
			
			// Set the "redirect_to" to the current URL
			$redirect_to = $current_url;
			
		}
		
		// Add redirect_to" to login URL
		if ( $redirect_to ) {
			
			// Remove 'interim-login' parameter from redirect_to, if it exists
			$redirect_to = add_query_arg( 'interim-login', NULL, $redirect_to );
			
			// Remove login error parameter, if it exists
			$redirect_to = add_query_arg( apply_filters( 'ua_mybama_cas_auth_failed_new_authentication_query_arg', 'login-error' ), NULL, $redirect_to );
			
			// Add redirect_to to login url
			$login_url = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $login_url );
			
		}
		
		// Remove 'interim-login' parameter from login URL, if it exists
		$login_url = add_query_arg( 'interim-login', NULL, $login_url );
		
		// Remove login error parameter, if it exists
		$login_url = add_query_arg( apply_filters( 'ua_mybama_cas_auth_failed_new_authentication_query_arg', 'login-error' ), NULL, $login_url );
		
		return $login_url;
		
	}
	
	/**
	 * Returns the logout URL.
	 *
	 * @since   1.0.0
	 */
	public function get_logout_url() {
		
		// Add the logout parameter
		$logout_url = add_query_arg( 'ua-mybama-cas-auth-logout', 'true', get_bloginfo( 'url' ) );
		
		// Add the redirect to the current page
		$logout_url = add_query_arg( 'redirect_to', urlencode( $this->get_current_url() ), $logout_url );
		
		return $logout_url;
		
	}
	
	/**
	 * Returns the current URL.
	 *
	 * @since   1.0.0
	 */
	public function get_current_url() {
		
		// Build the current URL
		$current_url = ! ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] == 'on' ) ? ( 'http://' . $_SERVER[ 'SERVER_NAME' ] ) : ( 'https://' . $_SERVER[ 'SERVER_NAME' ] );
		
		// Add on the request path
		$current_url .= isset( $_SERVER[ 'REQUEST_URI' ] ) ? $_SERVER[ 'REQUEST_URI' ] : NULL;
			
		return $current_url;
		
	}
	
	/**
	 * Checks to see if a user is wanting to
	 * login via the 'ua-mybama-cas-auth-login'
	 * parameter.
	 *
	 * @since   1.0.0
	 */
	public function check_for_user_requested_login() {
		
		// Only do for front end
		if ( is_admin() )
			return;
		
		// If the user is requesting to sign via the 'ua-mybama-cas-auth-login' parameter...
		if ( isset( $_GET ) && isset( $_GET[ 'ua-mybama-cas-auth-login' ] )
			&& strcasecmp( 'true', $_GET[ 'ua-mybama-cas-auth-login' ] ) == 0 ) {
				
			// If the user is already authenticated...
			if ( $this->is_authenticated() ) {
				
				// If single sign-on is enabled, try to sign them into WordPress
				if ( $this->is_single_sign_on() )
					$this->sign_user_into_wordpress();
				
				// Create our redirect URL
				$redirect_url = NULL;
				
				// Is a "redirect_to" set already?
				if ( isset( $_GET[ 'redirect_to' ] ) ) {
					
					$redirect_url = $_GET[ 'redirect_to' ];
				
				// Otherwise, set the current URL
				} else {
				
					// Create redirect URL
					$redirect_url = $this->get_current_url();
					
				}
					
				// If we have a redirect URL...
				if ( $redirect_url ) {
					
					// If redirect URL = the login page, then redirect to the admin
					if ( $redirect_url == wp_login_url() ) {
						
						$redirect_url = admin_url();
						
					}
					
					// Make sure the 'ua-mybama-cas-auth-login' is removed
					$redirect_url = add_query_arg( 'ua-mybama-cas-auth-login', NULL, $redirect_url );
					
					// Redirect!
					wp_redirect( $redirect_url );
					exit;
					
				}
				
			// Otherwise, authenticate	
			} else {
			
				// Force CAS authentication
				$this->force_authentication();
				
			}
				
		}
		
	}
	
	/**
	 * Checks to see if a user is wanting to
	 * logout via the 'ua-mybama-cas-auth-logout'
	 * parameter.
	 *
	 * @since   1.0.0
	 */
	public function check_for_user_requested_logout() {
		
		// Only do for front end
		if ( is_admin() )
			return;
		
		// If the user is requesting to logout via the 'ua-mybama-cas-auth-logout' parameter...
		if ( isset( $_GET ) && isset( $_GET[ 'ua-mybama-cas-auth-logout' ] )
			&& strcasecmp( 'true', $_GET[ 'ua-mybama-cas-auth-logout' ] ) == 0 ) {
				
			// If single sign-on is enabled
			if ( $this->is_single_sign_on() ) {
				
				// Log them out of WordPress (which logs them out of CAS)
				wp_logout();
				
			// Otherwise, only log them out of CAS
			} else {
			
				// Log them out!
				$this->logout();
				
			}
				
		}
		
	}
	
	/**
	 * Logout the authenticated user out of CAS.
	 *
	 * If SSO is enabled, use wp_logout() instead
	 * of this function because using this will cause
	 * a loop. Running wp_logout() will log the user
	 * out of WordPress and myBama.
	 *
	 * @since   1.0.0
	 * @param	$redirect_url - where you want to send
	 *				the user after they logout. By default
	 *				it sends them to the current page.
	 */
	function logout( $redirect_url = NULL ) {
		
		// Make sure the client is initialized
		if ( ! $this->initialize_client() )
			return;
		
		// If a redirect URL wasn't passed...
		if ( ! ( isset( $redirect_url ) && ! empty( $redirect_url ) ) ) {
			
			// Create our redirect URL
			$redirect_url = NULL;
			
			// Is a "redirect_to" set already?
			if ( isset( $_GET[ 'redirect_to' ] ) ) {
				
				$redirect_url = $_GET[ 'redirect_to' ];
			
			// Otherwise, set the current URL
			} else {
			
				// Create redirect URL
				$redirect_url = $this->get_current_url();
				
			}
				
			// Remove our logout query
			$redirect_url = add_query_arg( 'ua-mybama-cas-auth-logout', NULL, $redirect_url );
			
			// Remove WordPress logout action parameters
			$redirect_url = add_query_arg( array( 'action' => NULL, '_wpnonce' => NULL ), $redirect_url );
				
		}
		
		// If we failed a new authentication, add a parameter
		if ( isset( $this->failed_new_authentication ) && $this->failed_new_authentication )
			$redirect_url = add_query_arg( apply_filters( 'ua_mybama_cas_auth_failed_new_authentication_query_arg', 'login-error' ), 1, $redirect_url );
			
		// Only need to logout if authenticated
		if ( $this->is_authenticated() ) {
			
			// If we have a redirect	
			if ( isset( $redirect_url ) && ! empty( $redirect_url ) )
				phpCAS::logoutWithRedirectService( $redirect_url );
			else
				phpCAS::logout();
			
		}
		
		// If we have a redirect...
		if ( isset( $redirect_url ) && ! empty( $redirect_url ) ) {
			
			wp_redirect( $redirect_url );
			exit;
			
		}
		
		// @TODO do we need this?
		//phpCAS::handleLogoutRequests();
		
	}
	
	/**
	 * Lets you know if the user is authenticated.
	 *
	 * @since    1.0.0
	 */
	public function is_authenticated() {
		
		// Make sure the client is initialized
		if ( $this->initialize_client() )
			return phpCAS::isAuthenticated();
		
		return false;
		
	}
	
	/**
	 * Forces the user to authenticate themselves.
	 *
	 * @since    1.0.0
	 */
	public function force_authentication() {
		
		// No need if they're already authenticated
		if ( $this->is_authenticated() )
			return;
			
		// Make sure the client is initialized
		if ( $this->initialize_client() ) {
		
			// Set a cookie (that will expire in 5 minutes) that will hold the timestamp for when we started to attempt authentication
			setcookie( 'ua_mybama_cas_auth_attempting_authentication', time(), time() + 300, COOKIEPATH, COOKIE_DOMAIN, true );
			
			// Force CAS authentication
			phpCAS::forceAuthentication();
			
		}
		
	}
	
	/**
	 * Initializes the CAS client.
	 *
	 * @since    1.0.0
	 */
	public function initialize_client() {
		
		// No need to run if the client has already been setup
		if ( isset( $this->client_is_initialized ) && $this->client_is_initialized === true )
			return true;
		
		// Extract our settings
		extract( $this->get_settings(), EXTR_OVERWRITE );
		
		// Load the CAS library
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/CAS.php';
		
		// Are we in test mode?
		$in_test_mode = $this->is_in_test_mode();
		
		// What host and context are we using?
		$cas_host = $this->get_cas_host();
		$cas_context = $this->get_cas_context();
		
		// @TODO setup/test errors for if we don't have server info or if it doesn't work
		if ( ! $cas_host || ! $cas_context )
			return false;
		
		// Initialize phpCAS
		phpCAS::client( SAML_VERSION_1_1, $cas_host, 443, $cas_context );
		
		// For quick testing you can disable SSL validation of the CAS server.
		// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
		// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
		if ( $in_test_mode ) {
			
			phpCAS::setNoCasServerValidation();
		
		// For production use set the CA certificate that is the issuer of the cert
		// on the CAS server and uncomment the line below	
		} else {
			
			phpCAS::setCasServerCACert( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/ua-cas.pem' );
			
		}
		
		// Consider the client initialized!
		$this->client_is_initialized = true;
		
		return true;
		
	}
	
	/**
	 * Returns the status of single sign-on.
	 *
	 * Will return true if single sign-on is enabled.
	 *
	 * @since    1.0.0
	 */
	public function is_single_sign_on() {
		
		// No need if it's already set
		if ( isset( $this->is_single_sign_on ) )
			return $this->is_single_sign_on;
			
		// Extract our settings
		extract( $this->get_settings(), EXTR_OVERWRITE );
		
		// Set the status
		$this->is_single_sign_on = isset( $enable_single_sign_on ) && $enable_single_sign_on;
		
		return $this->is_single_sign_on;
		
	}
	
	/**
	 * Returns the value of the test mode setting.
	 *
	 * Will return true if test mode is enabled.
	 *
	 * @since    1.0.0
	 */
	public function is_in_test_mode() {
		
		// No need if it's already set
		if ( isset( $this->in_test_mode ) )
			return $this->in_test_mode;
			
		// Extract our settings
		extract( $this->get_settings(), EXTR_OVERWRITE );
		
		// Set the status
		$this->in_test_mode = isset( $enable_test_mode ) && $enable_test_mode;
		
		return $this->in_test_mode;
		
	}
	
	/**
	 * Get the array of myBama usernames that
	 * are on the whitelist and are allowed to
	 * login to WordPress after they have been
	 * authenticated by myBama.
	 *
	 * @since    1.0.0
	 * @return	array|false - the array of whitelist usernames or false if there are none
	 */
	public function get_wordpress_login_whitelist() {
		
		// Get the setting
		if ( $login_whitelist = $this->get_setting( 'wordpress_login_whitelist' ) ) {
			
			// If it's an array then we're good
			if ( is_array( $login_whitelist ) ) {
				
				return $login_whitelist;
			
			// Otherwise, handle a string
			} else if ( is_string( $login_whitelist ) ) {
			
				// Explode into an array, separated by new lines
				$login_whitelist = explode( "\n", $login_whitelist );
				
				// Make sure all the usernames are trimmed
				$login_whitelist = array_map( 'trim', $login_whitelist );
				
				return $login_whitelist;
				
			}
			
		}
			
		return false;
		
	}
	
	/**
	 * Get the array of myBama usernames that
	 * are on the blacklist and are NOT allowed to
	 * login to WordPress after they have been
	 * authenticated by myBama.
	 *
	 * @since    1.0.0
	 * @return	array|false - the array of blacklist usernames or false if there are none
	 */
	public function get_wordpress_login_blacklist() {
		
		// Get the setting
		if ( $login_blacklist = $this->get_setting( 'wordpress_login_blacklist' ) ) {
			
			// If it's an array then we're good
			if ( is_array( $login_blacklist ) ) {
				
				return $login_blacklist;
			
			// Otherwise, handle a string
			} else if ( is_string( $login_blacklist ) ) {
			
				// Explode into an array, separated by new lines
				$login_blacklist = explode( "\n", $login_blacklist );
				
				// Make sure all the usernames are trimmed
				$login_blacklist = array_map( 'trim', $login_blacklist );
				
				return $login_blacklist;
				
			}
			
		}
			
		return false;
		
	}
	
	/**
	 * Get the array of myBama usernames that
	 * are on the whitelist and are allowed to
	 * login via myBama.
	 *
	 * @since    1.0.0
	 * @return	array|false - the array of whitelist usernames or false if there are none
	 */
	public function get_mybama_username_whitelist() {
		
		// Get the setting
		if ( $username_whitelist = $this->get_setting( 'mybama_username_whitelist' ) ) {
			
			// If it's an array then we're good
			if ( is_array( $username_whitelist ) ) {
				
				return $username_whitelist;
			
			// Otherwise, handle a string
			} else if ( is_string( $username_whitelist ) ) {
				
				// Explode into an array, separated by new lines
				$username_whitelist = explode( "\n", $username_whitelist );
				
				// Make sure all the usernames are trimmed
				$username_whitelist = array_map( 'trim', $username_whitelist );
				
				return $username_whitelist;
				
			}
			
		}
			
		return false;
		
	}
	
	/**
	 * Get the array of myBama usernames that
	 * are on the blacklist and are NOT allowed to
	 * login via myBama.
	 *
	 * @since    1.0.0
	 * @return	array|false - the array of blacklist usernames or false if there are none
	 */
	public function get_mybama_username_blacklist() {
		
		// Get the setting
		if ( $username_blacklist = $this->get_setting( 'mybama_username_blacklist' ) ) {
			
			// If it's an array then we're good
			if ( is_array( $username_blacklist ) ) {
				
				return $username_blacklist;
			
			// Otherwise, handle a string
			} else if ( is_string( $username_blacklist ) ) {
			
				// Explode into an array, separated by new lines
				$username_blacklist = explode( "\n", $username_blacklist );
				
				// Make sure all the usernames are trimmed
				$username_blacklist = array_map( 'trim', $username_blacklist );
				
				return $username_blacklist;
				
			}
			
		}
			
		return false;
		
	}
	
	/**
	 * Get a specific plugin setting.
	 *
	 * @since    1.0.0
	 *
	 * @param	string	$key - 	the setting key
	 * @return	string|NULL		the setting value or NULL if doenst exist
	 */
	public function get_setting( $key ) {
		
		// Make sure we have settings
		if ( ! ( $settings = $this->get_settings() ) )
			return NULL;
			
		// Make sure this key exists
		if ( array_key_exists( $key, $settings ) )
			return $settings[ $key ];
			
		return NULL;
		
	}
	
	/**
	 * Get the plugin's settings.
	 *
	 * @since    1.0.0
	 */
	public function get_settings() {
		
		// No need if they're already set
		if ( isset( $this->settings ) && ! empty( $this->settings ) )
			return $this->settings;
		
		// Get the saved settings
		$saved_settings = get_option( 'ua_mybama_cas_auth_settings', array() );
		
		// Get the default settings
		$default_settings = $this->get_default_settings();
				
		// parsed saved settings with defaults
		$this->settings = wp_parse_args( $saved_settings, $default_settings );
		
		return $this->settings;
		
	}
	
	/**
	 * Get the plugin's default settings.
	 *
	 * @since    1.0.0
	 */
	public function get_default_settings() {
		
		return array(
			'enable_test_mode' => false,
			'enable_post_mybama_authentication_setting' => false,
			'post_mybama_authentication_setting_post_types' => false,
			'mybama_username_whitelist' => false,
			'mybama_username_blacklist' => false,
			'enable_single_sign_on' => false,
			'sso_add_mybama_button_to_login_form' => true,
			'sso_hide_wordpress_login_form'	=> false,
			'sso_match_user_data' => true,
			'sso_create_matching_profile' => true,
			'sso_matching_profile_user_role' => false,
			'sso_enable_post_wordpress_authentication_setting' => false,
			'sso_post_wordpress_authentication_setting_post_types' => false,
			'wordpress_login_whitelist' => false,
			'wordpress_login_blacklist' => false,
			'cas_production_host_address' => false,
			'cas_production_context' => false,
			'cas_test_host_address' => false,
			'cas_test_context' => false,
		);
		
	}
	
	
	
	/**
	 * Will retrieve the correct CAS host from the settings.
	 *
	 * @since    1.0.0
	 */
	public function get_cas_host() {
		
		// Are we in test mode?
		$in_test_mode = $this->is_in_test_mode();
		
		// If we're in test mode and we have test info...
		if ( $in_test_mode
			&& ( $cas_test_host_address = $this->get_setting( 'cas_test_host_address' ) )
			&& ! empty( $cas_test_host_address ) ) {
			
			return $cas_test_host_address;
		
		// Otherwise, get production info...	
		} else if ( ( $cas_production_host_address = $this->get_setting( 'cas_production_host_address' ) )
			&& ! empty( $cas_production_host_address ) ) {
			
			return $cas_production_host_address;
			
		}
		
	}
	
	/**
	 * Will retrieve the correct CAS context from the settings.
	 *
	 * @since    1.0.0
	 */
	public function get_cas_context() {
		
		// Are we in test mode?
		$in_test_mode = $this->is_in_test_mode();
		
		// If we're in test mode and we have test info...
		if ( $in_test_mode
			&& ( $cas_test_context = $this->get_setting( 'cas_test_context' ) )
			&& ! empty( $cas_test_context ) ) {
			
			return $cas_test_context;
		
		// Otherwise, get production info...	
		} else if ( ( $cas_production_context = $this->get_setting( 'cas_production_context' ) )
			&& ! empty( $cas_production_context ) ) {
			
			return $cas_production_context;
			
		}
		
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - UA_myBama_CAS_Auth_Loader. Orchestrates the hooks of the plugin.
	 * - UA_myBama_CAS_Auth_i18n. Defines internationalization functionality.
	 * - UA_myBama_CAS_Auth_Admin. Defines all hooks for the dashboard.
	 * - UA_myBama_CAS_Auth_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ua-mybama-cas-auth-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ua-mybama-cas-auth-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the Dashboard.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ua-mybama-cas-auth-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ua-mybama-cas-auth-public.php';

		/**
		 * The class responsible for managing all of the plugin's shortcodes.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ua-mybama-cas-auth-shortcodes.php';

		$this->loader = new UA_myBama_CAS_Auth_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the UA_myBama_CAS_Auth_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new UA_myBama_CAS_Auth_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_id() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new UA_myBama_CAS_Auth_Admin( $this->get_plugin_id(), $this->get_plugin_file(), $this->get_version() );
		
		// Display admin notices
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'display_admin_notices' );
		
		// Register the plugin's settings
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		
		// Add the plugin options page
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_options_page' );
		
		// Add meta boxes to the options page
		$this->loader->add_action( 'admin_head-settings_page_ua-mybama-cas-auth', $plugin_admin, 'add_options_meta_boxes' );
		
		// Add meta boxes to the "Edit Post" screens
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_boxes', 100, 2 );
		
		// Do meta boxes after the post title
		$this->loader->add_action( 'edit_form_after_title', $plugin_admin, 'do_meta_boxes_after_title' );
		
		// Add custom admin columns
		$this->loader->add_filter( 'manage_pages_columns', $plugin_admin, 'add_admin_columns', 20 );
		$this->loader->add_filter( 'manage_posts_columns', $plugin_admin, 'add_admin_columns', 20, 2 );
		
		// Populate custom admin columns
		$this->loader->add_filter( 'manage_pages_custom_column', $plugin_admin, 'manage_admin_columns', 20, 2 );
		$this->loader->add_filter( 'manage_posts_custom_column', $plugin_admin, 'manage_admin_columns', 20, 2 );
		
		// Enqueue admin styles
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 100 );
		
		// Save post meta
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_post', 10, 3 );
		
		// Add plugin action links
		$this->loader->add_filter( 'plugin_action_links_ua-mybama-cas-auth/ua-mybama-cas-auth.php', $plugin_admin, 'add_plugin_action_links', 10, 4 );

		// Check for the plugin update
		$this->loader->add_filter( 'site_transient_update_plugins', $plugin_admin, 'check_for_plugin_update', 10 );

		// Display the update changelog
		$this->loader->add_action( 'install_plugins_pre_plugin-information', $plugin_admin, 'display_changelog', 0 );

	}

	/**
	 * Register all of the hooks related to the
	 * public-facing functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new UA_myBama_CAS_Auth_Public( $this->get_plugin_id(), $this->get_version() );
		
		// Initialize the client first thing - since we have to do so before header output
		$this->loader->add_action( 'plugins_loaded', $plugin_public, 'initialize_client', 0 );
		
		// Will check for posts that require authentication
		$this->loader->add_action( 'wp', $plugin_public, 'check_if_post_requires_mybama_authentication_for_page', 0 );
		$this->loader->add_action( 'wp', $plugin_public, 'check_if_post_requires_wordpress_authentication_for_page', 0 );
		
		$this->loader->add_action( 'the_content', $plugin_public, 'check_if_post_requires_mybama_authentication_for_content', 0 );
		$this->loader->add_action( 'the_content', $plugin_public, 'check_if_post_requires_wordpress_authentication_for_content', 0 );
		
		// Filter the excerpt
		// @TODO setup custom search excerpt? - work with 'no_with_custom_excerpt' post meta
		//$this->loader->add_filter( 'get_the_excerpt', $plugin_public, 'filter_the_excerpt', 1000 );
		
		// Filter any public queries
		$this->loader->add_filter( 'posts_clauses', $plugin_public, 'filter_posts_clauses', 1000, 2 );
		
		// Adds CSS file to the login page
		$this->loader->add_action( 'login_head', $plugin_public, 'add_login_css', 10 );
		
		// Filter the lost password URL
		$this->loader->add_filter( 'lostpassword_url', $plugin_public, 'filter_lostpassword_url', 10, 2 );
			
		// Adds "Login through myBama" button to login form
		$this->loader->add_action( 'login_form', $plugin_public, 'add_mybama_button_to_login_form', 10 );

	}
	
	/**
	 * Register all of the hooks that can run
	 * in the admin or in the public-facing part
	 * of the site and aren't defined in the
	 * admin or public class.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_common_hooks() {
		
		// Fires after someone is logged out of WordPress
		$this->loader->add_action( 'wp_logout', $this, 'logout', 0 );
		
		// Checks to see if a user is requesting to login or logout
		$this->loader->add_action( 'plugins_loaded', $this, 'check_for_user_requested_login', 1 );
		$this->loader->add_action( 'plugins_loaded', $this, 'check_for_user_requested_logout', 2 );
		$this->loader->add_action( 'plugins_loaded', $this, 'check_for_new_authentication', 3 );
		
		// Defines the IS_MYBAMA_AUTHENTICATED constant
		$this->loader->add_action( 'plugins_loaded', $this, 'define_is_mybama_authenticated', 4 );
		
		// Filters what authenticates the user to allow them to log in
		$this->loader->add_action( 'authenticate', $this, 'authenticate_wp_username_password', 100, 3 );
		
	}
	
	/**
	 * Register all of the plugin's shortcodes.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_shortcodes() {

		$plugin_shortcodes = new UA_myBama_CAS_Auth_Shortcodes( $this->get_plugin_id(), $this->get_version() );
		
		// Add [require_mybama_auth] shortcode
		$this->loader->add_shortcode( 'require_mybama_auth', $plugin_shortcodes, 'require_mybama_auth' );
		
		// Add [require_wp_login] shortcode
		$this->loader->add_shortcode( 'require_wp_login', $plugin_shortcodes, 'require_wp_login' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The ID of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The ID of the plugin.
	 */
	public function get_plugin_id() {
		return $this->plugin_id;
	}

	/**
	 * The path to the plugin's main file.
	 *
	 * @since     1.0.0
	 * @return    string    The path to the plugin's main file.
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    UA_myBama_CAS_Auth_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}