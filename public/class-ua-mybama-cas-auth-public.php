<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://webtide.ua.edu
 * @since      1.0.0
 *
 * @package    UA_myBama_CAS_Auth
 * @subpackage UA_myBama_CAS_Auth/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    UA_myBama_CAS_Auth
 * @subpackage UA_myBama_CAS_Auth/public
 * @author     Rachel Carden <rmcarden@ur.ua.edu>
 */
class UA_myBama_CAS_Auth_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $ua_mybama_cas_auth_id    The ID of this plugin.
	 */
	private $ua_mybama_cas_auth_id;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $ua_mybama_cas_auth_id	The ID of the plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $ua_mybama_cas_auth_id, $version ) {

		$this->ua_mybama_cas_auth_id = $ua_mybama_cas_auth_id;
		$this->version = $version;

	}
	
	/**
	 * We have to initialize the client first thing since
	 * we have to do so before header output.
	 *
	 * Had to set this up because otherwise I'll get 
	 * "Cannot send session cache limiter - headers already sent"
	 * errors when trying to check during the 'the_content'
	 * filter since that's usually run in the middle of the page.
	 *
	 * @since   1.0.0
	 */
	public function initialize_client() {
		global $ua_mybama_cas_auth;
		
		// Only do for front end
		if ( is_admin() )
			return;
			
		// Initialize the client
		$ua_mybama_cas_auth->initialize_client();
		
	}
	
	/**
	 * Adds CSS file(s) to the login page.
	 *
	 * @since   1.0.0
	 */
	public function add_login_css() {
		global $ua_mybama_cas_auth;
		
		// Only add if single sign-on is enabled
		if ( ! $ua_mybama_cas_auth->is_single_sign_on() )
			return;
			
		// Make sure the client is initialized
		if ( ! $ua_mybama_cas_auth->initialize_client() )
			return;
			
		// Get the setting
		$add_button = $ua_mybama_cas_auth->get_setting( 'sso_add_mybama_button_to_login_form' );
		
		// Only add if the setting is enabled
		if ( isset( $add_button ) && ! $add_button )
			return;
		
		// Enqueue the login stylesheet
		wp_enqueue_style( "{$this->ua_mybama_cas_auth_id}-login", plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/ua-mybama-cas-auth-login.css', array(), $this->version, 'all' );
		
		// Get the setting
		$hide_wp_login_form = $ua_mybama_cas_auth->get_setting( 'sso_hide_wordpress_login_form' );
		
		// Only add if the setting is enabled
		if ( isset( $hide_wp_login_form ) && ! $hide_wp_login_form )
			return;
			
		// Enqueue the stylesheet that hides the WordPress login elements
		wp_enqueue_style( "{$this->ua_mybama_cas_auth_id}-hide-login-form", plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/ua-mybama-cas-auth-hide-login-form.css', array(), $this->version, 'all' );
		
	}
	
	/**
	 * Allows us to filter the "Lost your password?"
	 * URL to link to the myBama reset password form
	 * if SSO is enabled and the login form is hidden
	 * (per user settings) and your only option is to
	 * login through myBama.
	 *
	 * @since   1.0.0
	 * @param 	string - $lostpassword_url The lost password page URL.
	 * @param 	string - $redirect         The path to redirect to on login.
	 */
	public function filter_lostpassword_url( $lostpassword_url, $redirect ) {
		global $ua_mybama_cas_auth;
		
		// Only filter if single sign-on is enabled
		if ( ! $ua_mybama_cas_auth->is_single_sign_on() )
			return $lostpassword_url;
			
		// Make sure the client is initialized
		if ( ! $ua_mybama_cas_auth->initialize_client() )
			return $lostpassword_url;
			
		// Get the setting
		$hide_wp_login_form = $ua_mybama_cas_auth->get_setting( 'sso_hide_wordpress_login_form' );
		
		// Only filter if the setting is enabled
		if ( isset( $hide_wp_login_form ) && ! $hide_wp_login_form )
			return $lostpassword_url;
		
		// Return the link to the myBama lost password form
		return 'http://oit.ua.edu/oit/services/it-service-desk/mybama-account-info/mybama-account-setup/';
	
	}
	
	/**
	 * Adds "Login through myBama" button to login form.
	 *
	 * @since   1.0.0
	 */
	public function add_mybama_button_to_login_form() {
		global $ua_mybama_cas_auth;
		
		// Only add if single sign-on is enabled
		if ( ! $ua_mybama_cas_auth->is_single_sign_on() )
			return;
			
		// Make sure the client is initialized
		if ( ! $ua_mybama_cas_auth->initialize_client() )
			return;
			
		// Get the setting
		$add_button = $ua_mybama_cas_auth->get_setting( 'sso_add_mybama_button_to_login_form' );
		
		// Only add if the setting is enabled
		if ( isset( $add_button ) && ! $add_button )
			return;
			
		// Get the login URL
		if ( $login_url = $ua_mybama_cas_auth->get_login_url() ) {
			
			?><a id="ua-mybama-cas-auth-login-form-mybama-button" class="button button-large button-secondary" href="<?php echo $login_url; ?>">Login through myBama</a><?php
			
		}
		
	}
	
	/**
	 * Checks to see if the current post requires myBama authentication
	 * in order to view the page.
	 *
	 * @since   1.0.0
	 * @param	WP object - the WordPress environment setup
	 */
	public function check_if_post_requires_mybama_authentication_for_page( $wp ) {
		global $ua_mybama_cas_auth, $post;
			
		// Only do for front end
		if ( is_admin() )
			return;
		
		// Make sure we're viewing a single post and have a post ID
		if ( ! is_singular() || ( is_singular() && ! ( isset( $post ) && isset( $post->ID ) && $post->ID > 0 ) ) )
			return;
		
		// Does this page require authentication?
		if ( ! ( $requires_authentication = get_post_meta( $post->ID, '_requires_mybama_authentication', true ) ) )
			return;
	
		// If it doesn't require authentication for the entire page, get out of here. We're not needed.
		if ( ! ( isset( $requires_authentication ) && 'yes_for_page' == $requires_authentication ) )
			return;
		
		// It does, so force authentication
		$ua_mybama_cas_auth->force_authentication();
		
	}
	
	/**
	 * Checks to see if the current post requires WordPress authentication
	 * in order to view the page.
	 *
	 * @since   1.0.0
	 * @param	WP object - the WordPress environment setup
	 */
	public function check_if_post_requires_wordpress_authentication_for_page( $wp ) {
		global $post;
		
		// Only do for front end
		if ( is_admin() )
			return;
		
		// Make sure we're viewing a single post and have a post ID
		if ( ! is_singular() || ( is_singular() && ! ( isset( $post ) && isset( $post->ID ) && $post->ID > 0 ) ) )
			return;
		
		// Does this page require authentication?
		if ( ! ( $requires_authentication = get_post_meta( $post->ID, '_requires_wordpress_authentication', true ) ) )
			return;
	
		// If it doesn't require authentication for the entire page, get out of here. We're not needed.
		if ( ! ( isset( $requires_authentication ) && 'yes_for_page' == $requires_authentication ) )
			return;
		
		// If user isn't logged in, so force authentication
		if ( ! is_user_logged_in() )
			auth_redirect();
		
	}
	
	/**
	 * Checks to see if the current post requires myBama authentication
	 * in order to view the content.
	 *
	 * @since   1.0.0
	 * @param	string - the content we're filtering and possibly hiding
	 */
	public function check_if_post_requires_mybama_authentication_for_content( $content ) {
		global $ua_mybama_cas_auth, $post;
		
		// Only do for front end
		if ( is_admin() )
			return;
			
		// Make sure we have a post ID
		if ( ! ( isset( $post ) && isset( $post->ID ) && $post->ID > 0 ) )
			return $content;
		
		// Does this page require authentication?
		if ( ! ( $requires_authentication = get_post_meta( $post->ID, '_requires_mybama_authentication', true ) ) )
			return $content;
			
		// If it doesn't require authentication for the content, get out of here. We're not needed.
		if ( ! ( isset( $requires_authentication ) && 'yes_for_content' == $requires_authentication ) )
			return $content;
			
		// If authenticated, then we're all good
		if ( $ua_mybama_cas_auth->is_authenticated() )
			return $content;
			
		// @TODO setup message with link to login and run it through a filter.
		return 'This content requires myBama authentication.';
		
	}
	
	/**
	 * Checks to see if the current post requires WordPress authentication
	 * in order to view the content.
	 *
	 * @since   1.0.0
	 * @param	string - the content we're filtering and possibly hiding
	 */
	public function check_if_post_requires_wordpress_authentication_for_content( $content ) {
		global $post;
		
		// Only do for front end
		if ( is_admin() )
			return;
			
		// Make sure we have a post ID
		if ( ! ( isset( $post ) && isset( $post->ID ) && $post->ID > 0 ) )
			return $content;
		
		// Does this page require authentication?
		if ( ! ( $requires_authentication = get_post_meta( $post->ID, '_requires_wordpress_authentication', true ) ) )
			return $content;
			
		// If it doesn't require authentication for the content, get out of here. We're not needed.
		if ( ! ( isset( $requires_authentication ) && 'yes_for_content' == $requires_authentication ) )
			return $content;
			
		// If authenticated, then we're all good
		if ( is_user_logged_in() )
			return $content;
			
		// @TODO setup message with link to login and run it through a filter.
		return 'This content requires WordPress authentication.';		
		
	}
	
	/**
	 * Allows us to filter excerpts.
	 *
	 * @since   1.0.0
	 * @param 	string - $excerpt - The excerpt we're filtering
	 */
	public function filter_the_excerpt( $excerpt ) {
		
		// @TODO setup custom search excerpt? - work with 'no_with_custom_excerpt' post meta
		
		return $excerpt;
		
	}
	
	/**
	 * Allows us to filter any queries.
	 *
	 * @since   1.0.0
	 * @param 	array - $clauses The list of clauses for the query
	 * @param	WP_Query - $query - The WP_Query instance (passed by reference)
	 */
	public function filter_posts_clauses( $clauses, $query ) {
		global $ua_mybama_cas_auth, $wpdb;
		
		// Not in the admin
		if ( is_admin() )
			return $clauses;
		
		// If we're running a search
		if ( $query->is_search() ) {
			
			// Are we authenticated?
			$is_authenticated = $ua_mybama_cas_auth->is_authenticated();
			
			// Are we logged in?
			$is_logged_in = is_user_logged_in();
			
			// GROUP BY post ID to clear up duplicates
			if ( ! $is_authenticated || ! $is_logged_in )
				$clauses[ 'groupby' ] = "{$wpdb->posts}.ID";
			
			// If not authenticated, then we need to run the authenticated check
			if ( ! $is_authenticated ) {
				
				// LEFT JOIN to get post meta
				$clauses[ 'join' ] .= " LEFT JOIN {$wpdb->postmeta} requires_mybama ON requires_mybama.post_ID = {$wpdb->posts}.ID AND requires_mybama.meta_key = '_requires_mybama_authentication'";
			
				$clauses[ 'join' ] .= " LEFT JOIN {$wpdb->postmeta} requires_mybama_search ON requires_mybama_search.post_ID = {$wpdb->posts}.ID AND requires_mybama_search.meta_key = '_requires_mybama_authentication_wp_search_results'";
				
				// Set up the WHERE
				// If myBama authentication is required, then it checks the "required for search" setting
				// If myBama authentication is required for search, it's removed - default is required so check for no
				$clauses[ 'where' ] .= " AND IF ( requires_mybama.meta_value IS NOT NULL AND requires_mybama.meta_value LIKE 'yes%', 
					IF ( requires_mybama_search.meta_value IS NOT NULL AND requires_mybama_search.meta_value LIKE 'no%', true, false ),
					true )";
				
			}
			
			// If not logged in, then we need to run the authenticated check
			if ( ! $is_logged_in ) {
			
				// LEFT JOIN to get post meta
				$clauses[ 'join' ] .= " LEFT JOIN {$wpdb->postmeta} requires_wp ON requires_wp.post_ID = {$wpdb->posts}.ID AND requires_wp.meta_key = '_requires_wordpress_authentication'";
			
				$clauses[ 'join' ] .= " LEFT JOIN {$wpdb->postmeta} requires_wp_search ON requires_wp_search.post_ID = {$wpdb->posts}.ID AND requires_wp_search.meta_key = '_requires_wordpress_authentication_wp_search_results'";
				
				// Set up the WHERE
				// If WordPress authentication is required, then it checks the "required for search" setting
				// If WordPress authentication is required for search, it's removed - default is required so check for no
				$clauses[ 'where' ] .= " AND IF ( requires_wp.meta_value IS NOT NULL AND requires_wp.meta_value LIKE 'yes%', 
					IF ( requires_wp_search.meta_value IS NOT NULL AND requires_wp_search.meta_value LIKE 'no%', true, false ),
					true )";
				
			}
			
		}
		
		return $clauses;
		
	}
	
	/**
	 * Adds custom query variables to the query.
	 *
	 * @since   1.0.0
	 * @param	array - the query variables that are already set
	 */
	public function add_query_vars( $vars ) {
		//$vars[] = 'get_file';
		return $vars;
	}
	
	/**
	 * Checks if someone is trying to access a
	 * media file and whether or not they have
	 * access
	 *
	 * @TODO get this to work with media_authentication setting
	 *
	 * @since   1.0.0
	 * @param	WP object - the current WordPress environment instance
	 
	public function check_media_files_to_restrict_access( &$wp ) {
		global $ua_mybama_cas_auth, $wpdb;
		
		return;
		
		// Only do for front end
		if ( is_admin() )
			return;
		
		// Make sure we have query vars
		if ( ! isset( $wp->query_vars ) )
			return;
			
		// Define the query vars
		$query_vars = &$wp->query_vars;
		
		// Make sure 'get_file' exists
		if ( ! ( $get_file = isset( $query_vars[ 'get_file' ] ) ? $query_vars[ 'get_file' ] : NULL ) )
			return;
			
		// wp_get_attachment_url() first checks post meta so that's what we're gonna do
		$file_id = $wpdb->get_var( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = '{$get_file}'" );
		
		// If there's still no file ID, wp_get_attachment_url() then checks the guid in the posts table
		if ( ! $file_id ) {
			
			// Get the current URL
			$current_url = $ua_mybama_cas_auth->get_current_url();
		
			// Remove query parameters from the URL
			$current_url = preg_replace( '/\?.*$/i', '', $current_url );
		
			// Check the guid in the posts table
			$file_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid LIKE '$current_url'" );
			
		}
		
		// If there's no file ID found, set a 404 error and get out of here
		if( ! $file_id ) {
			
			$query_vars[ 'error' ] = '404';
			return;
		
		}
		
		// Check to make sure the file post and URL checks out
		if ( ! ( ( $file_post = get_post( $file_id ) )
			&& ( $file_url = get_attached_file( $file_id ) )
			&& file_exists( $file_url ) ) ) {
			
			// If it does not, set a 404 error and get out of here
			$query_vars[ 'error' ] = '404';
			return;
			
		}
		
		// Will be true if user can access file
		$user_can_access_file = false;
		
		// Get the media authentication settings
		$media_authentication = $ua_mybama_cas_auth->get_setting( 'media_authentication' );
		
		// If there's no media authentication, then the user can access the file
		if ( ! $media_authentication )
			$user_can_access_file = true;
			
		// If the user cannot access the file...
		if ( ! $user_can_access_file ) {
			
			// Set a 404 error and get out of here
			$query_vars[ 'error' ] = '404';
			return;
			
		}
			
		*//*// Get the current URL
		$current_url = $ua_mybama_cas_auth->get_current_url();
		
		echo "<pre>";
		print_r( $current_url );
		echo "</pre>";
		
		echo "<pre>";
		print_r( $file_post );
		echo "</pre>";
			
		echo "<pre>";
		print_r( $query_vars );
		echo "</pre>";
		
		echo "file url:<pre>";
		print_r( $file_url );
		echo "</pre>";
		
		echo "basename<pre>";
		print_r( basename( $file_url ) );
		echo "</pre>";
		
		exit;
		
		// If the user can access, simulate the header
		header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/octet-stream' ); //$file_post->post_mime_type
        header( 'Content-Disposition: attachment; filename="' . basename( $file_url ) . '"' );
        header( 'Content-Transfer-Encoding: binary' );
        header('Connection: Keep-Alive');
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . filesize( $file_url ) );

        ob_clean();
        //flush();
        readfile( $file_url );
        exit;
		
	}*/
	
}