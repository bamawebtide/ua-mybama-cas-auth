<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       https://webtide.ua.edu
 * @since      1.0.0
 *
 * @package    UA_myBama_CAS_Auth
 * @subpackage UA_myBama_CAS_Auth/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    UA_myBama_CAS_Auth
 * @subpackage UA_myBama_CAS_Auth/admin
 * @author     Rachel Carden <rmcarden@ur.ua.edu>
 */
class UA_myBama_CAS_Auth_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_id    The ID of this plugin.
	 */
	private $plugin_id;

	/**
	 * The path of the plugin's main file.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_file     The path of the plugin's main file.
	 */
	private $plugin_file;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;
	
	/**
	 * The hook of the options page, which is different from the slug.
	 * 
	 * The hook is the key that WordPress uses for the page while the slug
	 * is the "pretty" identifer in the URL.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $options_page_hook    The options page hook.
	 */
	private $options_page_hook;
	
	/**
	 * The slug of the options page, which is different from the hook.
	 * 
	 * The hook is the key that WordPress uses for the page while the slug
	 * is the "pretty" identifer in the URL.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $options_page_slug    The options page slug.
	 */
	private $options_page_slug;
	
	/**
	 * Will be true if we added meta boxes to the current admin screen.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      boolean    $added_meta_boxes    Whether or not we have added meta boxes
	 */
	private $added_meta_boxes;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_id		The ID of this plugin.
	 * @var      string    $plugin_file		            The path of the plugin's main file.
	 * @var      string    $version                     The version of this plugin.
	 */
	public function __construct( $plugin_id, $plugin_file, $version ) {

		// Set some data
		$this->plugin_id = $plugin_id;
		$this->plugin_file = $plugin_file;
		$this->version = $version;

	}
	
	/**
	 * Displays any needed admin notices.
	 *
	 * @since    1.0.0
	 */
	public function display_admin_notices() {
		global $ua_mybama_cas_auth, $current_screen;
		
		// We need to show a warning if they have not entered host settings

		// Only show on the settings page
		if ( ! ( isset( $current_screen ) && isset( $current_screen->id ) && isset( $this->options_page_slug ) && $current_screen->id == "settings_page_{$this->options_page_slug}" ) )
			return;
		
		// What host and context are we using?
		$cas_host = $ua_mybama_cas_auth->get_cas_host();
		$cas_context = $ua_mybama_cas_auth->get_cas_context();
		
		// If no host or context, show error message
		if ( ! $cas_host || ! $cas_context ) {
		
			?><div class="error" style="margin:10px 0;"><p><?php

				// Display the notice
				_e( 'The MyBama CAS Authentication plugin cannot work until you provide all CAS server settings.', $this->plugin_id );
				
			?></p></div><?php
				
		}
	
	}
	
	/**
	 * Register the plugin's settings/options.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		
		// Holds the plugin's settings
		register_setting( 'ua_mybama_cas_auth_settings', 'ua_mybama_cas_auth_settings', array( $this, 'sanitize_settings' ) );
		
	}
	
	/**
	 * Sanitizes the 'ua_mybama_cas_auth_settings' option.
	 *
	 * @since   1.0.0
	 * @param 	array - $settings - the settings that are being sanitized
	 * @return	array - sanitized $settings
	 */
	public function sanitize_settings( $settings ) {
		global $ua_mybama_cas_auth;
		
		// Get the default settings
		$default_settings = $ua_mybama_cas_auth->get_default_settings();
		
		// If any whitelists or blacklists are set...
		foreach( array( 'mybama_username_whitelist', 'mybama_username_blacklist', 'wordpress_login_whitelist', 'wordpress_login_blacklist' ) as $settings_key ) {
			
			// If we have a valid value...
			if ( isset( $settings[ $settings_key ] )
				&& is_string( $settings[ $settings_key ] )
				&& ! empty( $settings[ $settings_key ] ) ) {
				
				// Make sure its trimmed
				if ( $settings[ $settings_key ] = trim( $settings[ $settings_key ] ) ) {
				
					// Explode into an array, separated by new lines
					$settings[ $settings_key ] = explode( "\n", $settings[ $settings_key ] );
					
					// Make sure all the usernames are trimmed
					$settings[ $settings_key ] = array_map( 'trim', $settings[ $settings_key ] );
					
					// Get rid of duplicates
					$settings[ $settings_key ] = array_unique( $settings[ $settings_key ] );
					
					// Alphabetize the list
					sort( $settings[ $settings_key ] );
					
				}
				
				// If empty, set to NULL
				if ( empty( $settings[ $settings_key ] ) )
					$settings[ $settings_key ] = NULL;
				
			}
			
		}
		
		// Parse the settings with the default settings
		return wp_parse_args( $settings, $default_settings );
		
	}
	
	/**
	 * Add custom admin columns.
	 *
	 * @since   1.0.0
	 * @param	array - the original columns info
	 * @param	string - the post type that we're viewing
	 */
	public function add_admin_columns( $posts_columns, $post_type = 'page' ) {
		global $ua_mybama_cas_auth;
		
		// First we need our settings
		extract( $ua_mybama_cas_auth->get_settings(), EXTR_OVERWRITE );
		
		// Will be true if we should add either column
		$add_mybama_auth_column = false;
		$add_wordpress_auth_column = false;
		
		// We only need to add our custom admin column if this setting is enabled
		if ( isset( $enable_post_mybama_authentication_setting ) && $enable_post_mybama_authentication_setting ) {
			
			// Then we only need to add our custom admin column if the post type is designated
			if ( isset( $post_mybama_authentication_setting_post_types ) && is_array( $post_mybama_authentication_setting_post_types ) && in_array( $post_type, $post_mybama_authentication_setting_post_types ) ) {
				
				// Add the column
				$add_mybama_auth_column = true;
				
			}
			
		}
		
		// We only need to add our custom admin column if this setting is enabled
		if ( isset( $sso_enable_post_wordpress_authentication_setting ) && $sso_enable_post_wordpress_authentication_setting ) {
			
			// Then we only need to add our custom admin column if the post type is designated
			if ( isset( $sso_post_wordpress_authentication_setting_post_types ) && is_array( $sso_post_wordpress_authentication_setting_post_types ) && in_array( $post_type, $sso_post_wordpress_authentication_setting_post_types ) ) {
				
				// Add the column
				$add_wordpress_auth_column = true;
				
			}
			
		}
		
		// If either column should be added
		if ( $add_mybama_auth_column || $add_wordpress_auth_column ) {
			
			// Create a new array of columns
			$new_posts_columns = array();
			
			// Loop through each current column
			foreach( $posts_columns as $key => $label ) {
				
				// Add to new array
				$new_posts_columns[ $key ] = $label;
				
				// Add our columns after the title
				if ( 'title' == $key ) {
					
					// Add the myBama column
					if ( $add_mybama_auth_column )
						$new_posts_columns[ 'requires_mybama_authentication' ] = 'Requires myBama Authentication';
						
					// Add the WordPress column
					if ( $add_wordpress_auth_column )
						$new_posts_columns[ 'requires_wordpress_authentication' ] = 'Requires WordPress Authentication';
						
				}
					
			}
		
			return $new_posts_columns;
			
		}
		
		return $posts_columns;
		
	}
	
	/**
	 * Populate our custom admin columns.
	 *
	 * @since   1.0.0
	 * @param	string - the name, or index, of the column
	 * @param	integer - the ID of the post data we need
	 */
	public function manage_admin_columns( $column_name, $post_id ) {

		// Get post type
		$post_type = get_post_type( $post_id );

		// Get post type info
		$post_type_object = get_post_type_object( $post_type );

		// Get the singular label
		$post_type_singular_label = isset( $post_type_object->labels ) && isset( $post_type_object->labels->singular_name ) && ! empty( $post_type_object->labels->singular_name ) ? $post_type_object->labels->singular_name : $post_type;

		switch( $column_name ) {
			
			// Print myBama column
			case 'requires_mybama_authentication':
			
				// Get post meta
				$requires_authentication = get_post_meta( $post_id, '_requires_mybama_authentication', true );
				
				// Print the value
				switch( $requires_authentication ) {
					
					case 'yes_for_page':
						echo 'Yes, for the entire page';
						break;
						
					case 'yes_for_content':
						echo 'Yes, in order to view the ' . strtolower( $post_type_singular_label ) . ' content.';
						break;
						
					default:
						echo 'No';
						break;
						
				}
				
				break;
			
			// Print WordPress column
			case 'requires_wordpress_authentication':
			
				// Get post meta
				$requires_authentication = get_post_meta( $post_id, '_requires_wordpress_authentication', true );
				
				// Print the value
				switch( $requires_authentication ) {
					
					case 'yes_for_page':
						echo 'Yes, for the entire page';
						break;
						
					case 'yes_for_content':
						echo 'Yes, in order to view the' . strtolower( $post_type_singular_label ) . 'content.';
						break;
						
					default:
						echo 'No';
						break;
						
				}
				
				break;
				
		}
		
	}
		
	/**
	 * Add meta boxes to the "Edit Post" screens.
	 *
	 * @since   1.0.0
	 * @param	string - the post type that's being edited
	 * @param	object - information about the post that's being edited
	 */
	public function add_meta_boxes( $post_type, $post ) {
		global $ua_mybama_cas_auth;
		
		// First we need our settings
		extract( $ua_mybama_cas_auth->get_settings(), EXTR_OVERWRITE );
		
		// We only need to add the myBama meta box if this setting is enabled
		if ( isset( $enable_post_mybama_authentication_setting ) && $enable_post_mybama_authentication_setting ) {
			
			// Then we only need to add this meta box if the post type is designated
			if ( isset( $post_mybama_authentication_setting_post_types ) && is_array( $post_mybama_authentication_setting_post_types ) && in_array( $post_type, $post_mybama_authentication_setting_post_types ) ) {
				
				// Add the meta box!
				add_meta_box( 'ua-mybama-cas-auth-mybama-cas-authentication', 'MyBama CAS Authentication', array( $this, 'print_meta_boxes' ), $post_type, 'ua-mybama-cas-auth-after-title', 'high', 'mybama-cas-authentication' );
				
			}
			
		}
		
		// We only need to add the WordPress meta box if this setting is enabled
		if ( isset( $sso_enable_post_wordpress_authentication_setting ) && $sso_enable_post_wordpress_authentication_setting ) {
			
			// Then we only need to add this meta box if the post type is designated
			if ( isset( $sso_post_wordpress_authentication_setting_post_types ) && is_array( $sso_post_wordpress_authentication_setting_post_types ) && in_array( $post_type, $sso_post_wordpress_authentication_setting_post_types ) ) {
				
				// Add the meta box!
				add_meta_box( 'ua-mybama-cas-auth-wordpress-authentication', 'WordPress Authentication', array( $this, 'print_meta_boxes' ), $post_type, 'ua-mybama-cas-auth-after-title', 'high', 'wordpress-authentication' );
				
			}
			
		}
		
		// We added boxes!
		$this->added_meta_boxes = true;
		
	}
	
	/**
	 * "Do"/print our meta boxes after the post title.
	 *
	 * @since   1.0.0
	 * @param	array - $post - information about the post that's being edited
	 */
	public function do_meta_boxes_after_title( $post ) {
		
		// Render the meta boxes
		do_meta_boxes( get_current_screen(), 'ua-mybama-cas-auth-after-title', $post );
		
	}
	
	/**
	 * Print the edit post meta boxes.
	 *
	 * @since   1.0.0
	 * @param	array - $post - information about the post that's being edited
	 * @param	array - $metabox - information about the metabox
	 */
	public function print_meta_boxes( $post, $metabox ) {

		// Get post type info
		$post_type_object = get_post_type_object( $post->post_type );

		// Get the singular label
		$post_type_singular_label = isset( $post_type_object->labels ) && isset( $post_type_object->labels->singular_name ) && ! empty( $post_type_object->labels->singular_name ) ? $post_type_object->labels->singular_name : $post->post_type;
		
		switch( $metabox_id = $metabox[ 'args' ] ) {
			
			//! myBama CAS Authentication Meta Box
			case 'mybama-cas-authentication':
				
				// Get the saved meta
				$requires_authentication = get_post_meta( $post->ID, '_requires_mybama_authentication', true );
				$requires_authentication_wp_search_results = get_post_meta( $post->ID, '_requires_mybama_authentication_wp_search_results', true );
				
				// Add nonce for verification
				wp_nonce_field( 'setting_requires_mybama_authentication_field', 'setting_requires_mybama_authentication_nonce' );
			
				?><div class="ua-mybama-cas-auth-side-by-side-fieldsets<?php echo ! ( ! isset( $requires_authentication ) || empty( $requires_authentication ) || 'no' == $requires_authentication ) ? ' two' : NULL; ?>">
					<fieldset class="ua-mybama-cas-auth-restrict-view">
						<legend class="screen-reader-text">
							<span>Do you want to require MyBama authentication in order to view this content?</span>
						</legend>
						<p><strong>Do you want to require MyBama authentication in order to view this content?</strong></p>
						<select name="ua_mybama_cas_auth[_requires_mybama_authentication]">
							<option value="yes_for_page"<?php selected( isset( $requires_authentication ) && 'yes_for_page' == $requires_authentication ); ?>>Yes, require myBama authentication before they can load the page</option>
							<option value="yes_for_content"<?php selected( isset( $requires_authentication ) && 'yes_for_content' == $requires_authentication ); ?>>Yes, but only require myBama authentication to view the <?php echo strtolower( $post_type_singular_label ); ?> content</option>
							<option value="no"<?php selected( ! isset( $requires_authentication ) || empty( $requires_authentication ) || 'no' == $requires_authentication ); ?>>No, do not require myBama authentication</option>
						</select>
					</fieldset>

					<fieldset class="ua-mybama-cas-auth-remove-from-search<?php echo ! isset( $requires_authentication ) || empty( $requires_authentication ) || 'no' == $requires_authentication ? ' hide' : NULL; ?>">
						<legend class="screen-reader-text">
							<span>Do you want to remove this content from WordPress search results if the user is not myBama authenticated?</span>
						</legend>
						<p><strong>Do you want to remove this content from WordPress search results if the user is not myBama authenticated?</strong></p>
						<select name="ua_mybama_cas_auth[_requires_mybama_authentication_wp_search_results]">
							<option value="yes"<?php selected( ! isset( $requires_authentication_wp_search_results ) || empty( $requires_authentication_wp_search_results ) || ( isset( $requires_authentication_wp_search_results ) && 'yes' == $requires_authentication_wp_search_results ) ); ?>>Yes, remove this post from WordPress search results</option>
							<?php /*<option value="no_with_custom_excerpt"<?php selected( isset( $requires_authentication_wp_search_results ) && 'no_with_custom_excerpt' == $requires_authentication_wp_search_results ); ?>>No, but display a custom search result excerpt for users who have not been authenticated through myBama</option>*/ ?>
							<option value="no"<?php selected( isset( $requires_authentication_wp_search_results ) && 'no' == $requires_authentication_wp_search_results ); ?>>No, do not remove from WordPress search results</option>
						</select>
					</fieldset>
				</div><?php
			
				break;
				
			//! WordPress Authentication Meta Box
			case 'wordpress-authentication':
				
				// Get the saved meta
				$requires_authentication = get_post_meta( $post->ID, '_requires_wordpress_authentication', true );
				$requires_authentication_wp_search_results = get_post_meta( $post->ID, '_requires_wordpress_authentication_wp_search_results', true );
				
				// Add nonce for verification
				wp_nonce_field( 'setting_requires_wordpress_authentication_field', 'setting_requires_wordpress_authentication_nonce' );
			
				?><div class="ua-mybama-cas-auth-side-by-side-fieldsets<?php echo ! ( ! isset( $requires_authentication ) || empty( $requires_authentication ) || 'no' == $requires_authentication ) ? ' two' : NULL; ?>">
					<fieldset class="ua-mybama-cas-auth-restrict-view">
						<legend class="screen-reader-text">
							<span>Do you want to require WordPress authentication in order to view this content?</span>
						</legend>
						<p><strong>Do you want to require WordPress authentication in order to view this content?</strong></p>
						<select name="ua_mybama_cas_auth[_requires_wordpress_authentication]">
							<option value="yes_for_page"<?php selected( isset( $requires_authentication ) && 'yes_for_page' == $requires_authentication ); ?>>Yes, require WordPress authentication before they can load the page</option>
							<option value="yes_for_content"<?php selected( isset( $requires_authentication ) && 'yes_for_content' == $requires_authentication ); ?>>Yes, but only require WordPres authentication to view the <?php echo strtolower( $post_type_singular_label ); ?> content</option>
							<option value="no"<?php selected( ! isset( $requires_authentication ) || empty( $requires_authentication ) || 'no' == $requires_authentication ); ?>>No, do not require WordPress authentication</option>
						</select>
					</fieldset>

					<fieldset class="ua-mybama-cas-auth-remove-from-search<?php echo ! isset( $requires_authentication ) || empty( $requires_authentication ) || 'no' == $requires_authentication ? ' hide' : NULL; ?>">
						<legend class="screen-reader-text">
							<span>Do you want to remove this content from WordPress search results if the user is not logged into WordPress?</span>
						</legend>
						<p><strong>Do you want to remove this content from WordPress search results if the user is not logged into WordPress?</strong></p>
						<select name="ua_mybama_cas_auth[_requires_wordpress_authentication_wp_search_results]">
							<option value="yes"<?php selected( ! isset( $requires_authentication_wp_search_results ) || empty( $requires_authentication_wp_search_results ) || ( isset( $requires_authentication_wp_search_results ) && 'yes' == $requires_authentication_wp_search_results ) ); ?>>Yes, remove this post from WordPress search results</option>
							<?php /*<option value="no_with_custom_excerpt"<?php selected( isset( $requires_authentication_wp_search_results ) && 'no_with_custom_excerpt' == $requires_authentication_wp_search_results ); ?>>No, but display a custom search result excerpt for users who have not logged into WordPress</option>*/ ?>
							<option value="no"<?php selected( isset( $requires_authentication_wp_search_results ) && 'no' == $requires_authentication_wp_search_results ); ?>>No, do not remove from WordPress search results</option>
						</select>
					</fieldset>
				</div><?php
			
				break;
				
		}
		
	}
	
	/**
	 * Runs when a post is saved. Will save post meta.
	 *
	 * @since    1.0.0
	 */
	public function save_post( $post_id, $post, $update ) {
		
		// Pointless if $_POST is empty (this happens on bulk edit)
		if ( empty( $_POST ) )
			return $post_id;
			
		// Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;
			
		// Don't save for revisions
		if ( isset( $post->post_type ) && $post->post_type == 'revision' )
			return $post_id;
			
		// Verify our myBama authentication nonce
		if ( isset( $_POST[ 'setting_requires_mybama_authentication_nonce' ] )
			&& wp_verify_nonce( $_POST[ 'setting_requires_mybama_authentication_nonce' ], 'setting_requires_mybama_authentication_field' ) ) {
				
			// Save/update our post meta
			if ( isset( $_POST[ 'ua_mybama_cas_auth' ] )
				&& isset( $_POST[ 'ua_mybama_cas_auth' ][ '_requires_mybama_authentication' ] ) ) {
				
				update_post_meta( $post->ID, '_requires_mybama_authentication', $_POST[ 'ua_mybama_cas_auth' ][ '_requires_mybama_authentication' ]);
			
			}
			
			// Save/update our post meta
			if ( isset( $_POST[ 'ua_mybama_cas_auth' ] )
				&& isset( $_POST[ 'ua_mybama_cas_auth' ][ '_requires_mybama_authentication_wp_search_results' ] ) ) {
				
				update_post_meta( $post->ID, '_requires_mybama_authentication_wp_search_results', $_POST[ 'ua_mybama_cas_auth' ][ '_requires_mybama_authentication_wp_search_results' ]);
			
			}
			
		}
		
		// Verify our WordPress authentication nonce
		if ( isset( $_POST[ 'setting_requires_wordpress_authentication_nonce' ] )
			&& wp_verify_nonce( $_POST[ 'setting_requires_wordpress_authentication_nonce' ], 'setting_requires_wordpress_authentication_field' ) ) {
				
			// Save/update our post meta
			if ( isset( $_POST[ 'ua_mybama_cas_auth' ] )
				&& isset( $_POST[ 'ua_mybama_cas_auth' ][ '_requires_wordpress_authentication' ] ) ) {
				
				update_post_meta( $post->ID, '_requires_wordpress_authentication', $_POST[ 'ua_mybama_cas_auth' ][ '_requires_wordpress_authentication' ]);
			
			}
			
			// Save/update our post meta
			if ( isset( $_POST[ 'ua_mybama_cas_auth' ] )
				&& isset( $_POST[ 'ua_mybama_cas_auth' ][ '_requires_wordpress_authentication_wp_search_results' ] ) ) {
				
				update_post_meta( $post->ID, '_requires_wordpress_authentication_wp_search_results', $_POST[ 'ua_mybama_cas_auth' ][ '_requires_wordpress_authentication_wp_search_results' ]);
			
			}
			
		}
		
	}
	
	/**
	 * Add the plugin's options page.
	 *
	 * @since    1.0.0
	 */
	public function add_options_page() {
		
		// What is the slug/"pretty" URL ID for our options page?
		$this->options_page_slug = $this->plugin_id;
		
		// Add the plugin's options page
		$this->options_page_hook = add_options_page( 'University of Alabama MyBama CAS Authentication', 'MyBama CAS Authentication', 'manage_options', $this->options_page_slug, array( $this, 'print_options_page' ) );
		
	}
	
	/**
	 * Print the plugin's options page.
	 *
	 * @since    1.0.0
	 */
	public function print_options_page() {
		
		// Require the admin options partial
		require_once plugin_dir_path( __FILE__ ) . 'partials/ua-mybama-cas-auth-admin-options.php';
		
	}
	
	/**
	 * Add the plugin options meta boxes.
	 *
	 * @since    1.0.0
	 */
	public function add_options_meta_boxes() {
		
		// About this Plugin
		add_meta_box( 'ua-mybama-cas-auth-about', 'About this Plugin', array( $this, 'print_options_meta_boxes' ), $this->options_page_hook, 'side', 'core', 'about' );
		
		// Save Changes
		add_meta_box( 'ua-mybama-cas-auth-save-changes', 'Save Changes', array( $this, 'print_options_meta_boxes' ), $this->options_page_hook, 'side', 'core', 'save_changes' );

		// Getting Started
		add_meta_box( 'ua-mybama-cas-auth-getting-started', 'Getting Started', array( $this, 'print_options_meta_boxes' ), $this->options_page_hook, 'normal', 'core', 'getting_started' );

		// Host Settings
		add_meta_box( 'ua-mybama-cas-auth-host-settings', 'Host Settings', array( $this, 'print_options_meta_boxes' ), $this->options_page_hook, 'normal', 'core', 'host_settings' );
		
		// Content Settings
		add_meta_box( 'ua-mybama-cas-auth-content-settings', 'Content Settings', array( $this, 'print_options_meta_boxes' ), $this->options_page_hook, 'normal', 'core', 'content_settings' );
		
		// User Settings
		add_meta_box( 'ua-mybama-cas-auth-user-settings', 'User Settings', array( $this, 'print_options_meta_boxes' ), $this->options_page_hook, 'normal', 'core', 'user_settings' );
		
		// Single Sign-On Settings
		add_meta_box( 'ua-mybama-cas-auth-single-sign-on-settings', 'Single Sign-On Settings', array( $this, 'print_options_meta_boxes' ), $this->options_page_hook, 'normal', 'core', 'single_sign_on_settings' );
		
		// Shortcodes
		add_meta_box( 'ua-mybama-cas-auth-shortcodes', 'Shortcodes', array( $this, 'print_options_meta_boxes' ), $this->options_page_hook, 'normal', 'core', 'shortcodes' );
		
	}
	
	/**
	 * Print the plugin options meta boxes.
	 *
	 * @since   1.0.0
	 * @param	array - $post - information about the current post, which is empty because there is no current post on a settings page
	 * @param	array - $metabox - information about the metabox
	 */
	public function print_options_meta_boxes( $post, $metabox ) {
		global $ua_mybama_cas_auth;
		
		// Get/extract the settings
		extract( $ua_mybama_cas_auth->get_settings(), EXTR_OVERWRITE );

		switch( $metabox_id = $metabox[ 'args' ] ) {

			//! About
			case 'about':
			
				// Print the plugin name (with link to site)
				?><p><strong><a href="https://webtide.ua.edu/" target="_blank">University of Alabama MyBama CAS Authentication</a></strong></p><?php
					
				// Print the plugin version and author (with link to site)
				?><p><strong>Version:</strong> <?php echo $this->version; ?><br />
                <strong>Author:</strong> <a href="mailto:rmcarden@ur.ua.edu" target="_blank">Rachel Carden</a></p><?php
                
				break;
				
			//! Save Changes
			case 'save_changes':
			
				echo submit_button( 'Save Your Changes', 'primary', 'save_ua_mybama_cas_auth_settings', false, array( 'id' => 'save-ua-mybama-cas-auth-settings-mb' ) );
				
				break;

			//! Getting Started
			case 'getting_started':

				?><p>Before you can use this plugin, you must first request access to the CAS server. In order to request access, submit a ticket to the <a href="http://oit.ua.edu/oit/services/it-service-desk/" target="_blank">OIT service desk</a>. Once you have gained access, you will be given the host address and context information for the production and test CAS servers. If you need assistance, please feel free to email the plugin's author: <a href="mailto:rmcarden@ur.ua.edu">Rachel Carden</a>.</p><?php

				break;

			//! Host Settings
			case 'host_settings':

				?><fieldset id="enable-test-mode-setting">
					<legend class="screen-reader-text"><span>Enable test mode</span></legend>
					<p class="ua-mybama-cas-auth-field"><label><input id="ua-mybama-cas-auth-settings-enable-test-mode" name="ua_mybama_cas_auth_settings[enable_test_mode]" type="checkbox" value="1"<?php checked( isset( $enable_test_mode ) && $enable_test_mode ); ?> /> Enable test mode</label></p>
					<p class="description">If set, and information for your test CAS server is provided, the client will connect to your test CAS server instead of your production CAS server.</p>
				</fieldset>
				
				<table class="form-table">
					<tbody>
						<tr>
							<td class="ua-mybama-cas-auth-host-fields">
								<h3>Your Production CAS Server</h3>
								<table class="form-table">
									<tbody>
										<tr>
											<th class="ua-mybama-cas-auth-field-label" scope="row">
												<label for="cas_production_host_address">Host address</label>
											</th>
											<td class="ua-mybama-cas-auth-field">
												<fieldset>
													<legend class="screen-reader-text">
														<span>What is the address of your production CAS server?</span>
													</legend>
													<input name="ua_mybama_cas_auth_settings[cas_production_host_address]" type="text" id="cas_production_host_address" value="<?php echo isset( $cas_production_host_address ) && ! empty( $cas_production_host_address ) ? $cas_production_host_address : NULL; ?>" class="regular-text ua-mybama-cas-auth-not-required-for-test-mode<?php echo ! $enable_test_mode && ! $cas_production_host_address ? ' error' : NULL; ?>" />
													<p class="description">In a few words, explain what this site is about.</p>
												</fieldset>
											</td>
										</tr>
										<tr>
											<th class="ua-mybama-cas-auth-field-label" scope="row">
												<label for="cas_production_context">Context</label>
											</th>
											<td class="ua-mybama-cas-auth-field">
												<fieldset>
													<legend class="screen-reader-text">
														<span>What context are you using on your production CAS server?</span>
													</legend>
													<input name="ua_mybama_cas_auth_settings[cas_production_context]" type="text" id="cas_production_context" value="<?php echo isset( $cas_production_context ) && ! empty( $cas_production_context ) ? $cas_production_context : NULL; ?>" class="regular-text ua-mybama-cas-auth-not-required-for-test-mode<?php echo ! $enable_test_mode && ! $cas_production_context ? ' error' : NULL; ?>" />
													<p class="description">In a few words, explain what this site is about.</p>
												</fieldset>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
							<td class="ua-mybama-cas-auth-host-fields">
								<h3>Your Test CAS Server</h3>
								<table class="form-table">
									<tbody>
										<tr>
											<th class="ua-mybama-cas-auth-field-label" scope="row">
												<label for="cas_test_host_address">Host address</label>
											</th>
											<td class="ua-mybama-cas-auth-field">
												<fieldset>
													<legend class="screen-reader-text">
														<span>What is the address of your test CAS server?</span>
													</legend>
													<input name="ua_mybama_cas_auth_settings[cas_test_host_address]" type="text" id="cas_test_host_address" value="<?php echo isset( $cas_test_host_address ) && ! empty( $cas_test_host_address ) ? $cas_test_host_address : NULL; ?>" class="regular-text ua-mybama-cas-auth-required-for-test-mode<?php echo $enable_test_mode && ! $cas_test_host_address ? ' error' : NULL; ?>" />
													<p class="description">In a few words, explain what this site is about.</p>
												</fieldset>
											</td>
										</tr>
										<tr>
											<th class="ua-mybama-cas-auth-field-label" scope="row">
												<label for="cas_test_context">Context</label>
											</th>
											<td class="ua-mybama-cas-auth-field">
												<fieldset>
													<legend class="screen-reader-text">
														<span>What context are you using on your test CAS server?</span>
													</legend>
													<input name="ua_mybama_cas_auth_settings[cas_test_context]" type="text" id="cas_test_context" value="<?php echo isset( $cas_test_context ) && ! empty( $cas_test_context ) ? $cas_test_context : NULL; ?>" class="regular-text ua-mybama-cas-auth-required-for-test-mode<?php echo $enable_test_mode && ! $cas_test_context ? ' error' : NULL; ?>" />
													<p class="description">In a few words, explain what this site is about.</p>
												</fieldset>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table><?php
			
				break;
				
			//! Content Settings
			case 'content_settings':
			
				?><table class="form-table">
					<tbody>
						<tr>
							<th scope="row">Restrict Access to Content through myBama Login</th>
							<td>
								
								<h4 class="ua-mybama-cas-auth-field-label">Do you want to add a setting to your edit screen that allows you to require myBama authentication in order to view your content?</h4>
				
								<fieldset>
									<legend class="screen-reader-text">
										<span>Do you want to add a setting to your edit screen that allows you to require myBama authentication in order to view your content?</span>
									</legend>
									<p class="description">If enabled, this will add a meta box to your post's edit screen that will allow you to disable/enable authentication for each individual post.</p>
									<p class="ua-mybama-cas-auth-field indent">
										<label><input name="ua_mybama_cas_auth_settings[enable_post_mybama_authentication_setting]" type="radio" value="1" class="tog"<?php checked( isset( $enable_post_mybama_authentication_setting ) && $enable_post_mybama_authentication_setting ); ?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label><input name="ua_mybama_cas_auth_settings[enable_post_mybama_authentication_setting]" type="radio" value="0" class="tog"<?php checked( ! isset( $enable_post_mybama_authentication_setting ) || ! $enable_post_mybama_authentication_setting ); ?> /> No</label>
									</p>
								</fieldset><?php
							
								// Get post types
								$public_post_types = get_post_types( array( 'public' => true ), 'objects' );
									
								// @TODO set it up so this row only shows up if they selected yes above
								?><h4 class="ua-mybama-cas-auth-field-label">Which post types would you like to restrict access to?</h4>
								
								<fieldset>
									<legend class="screen-reader-text"><span>Which post types would you like to restrict access to?</span></legend><?php
										
									if ( ! $public_post_types ) {
										
										?><p class="description">There are no public post types to choose from.</p><?php
											
									} else {
										
										// Keep track of which one we're printing
										$post_type_index = 0;
										
										?><p class="ua-mybama-cas-auth-field checkboxes indent"><?php
										
											foreach( $public_post_types as $post_type_key => $post_type_object ) {
												
												?><label><input name="ua_mybama_cas_auth_settings[post_mybama_authentication_setting_post_types][]" type="checkbox" value="<?php echo $post_type_key; ?>"<?php checked( isset( $post_mybama_authentication_setting_post_types ) && is_array( $post_mybama_authentication_setting_post_types ) && in_array( $post_type_key, $post_mybama_authentication_setting_post_types ) ); ?> /> <?php echo $post_type_object->label; ?></label><?php
													
												$post_type_index++;
													
											}
										
										?></p><?php
										
									}
									
								?></fieldset>
								
							</td>
						</tr>
					</tbody>
				</table><?php
				
				break;
				
			//! User Settings
			case 'user_settings':
			
				?><table class="form-table">
					<tbody>
						<tr>
							<th scope="row">Limit myBama Logins</th>
							<td>
								
								<h4 class="ua-mybama-cas-auth-field-label">Do you want to restrict who can login through myBama?</h4>
								
								<p class="description">The whitelist and blacklist settings allow you to customize which users will be allowed to login, or not login, to your system through myBama. If you do not want to restrict login access through myBama, leave the settings blank. <strong>The blacklist will take priority over the whitelist.</strong></p>
								
								<table class="form-table">
									<tbody>
										<tr>
											<td class="white-list header-instructions">
												<h3>myBama Username Whitelist</h3>
												<p class="description">This setting allows you to create a whitelist of myBama usernames that <span class="white-list-color"><strong>ARE ALLOWED</strong></span> to login through myBama. If any usernames are defined, they will be the only usernames that are allowed. <strong>Leave this field blank to allow all usernames to login.</strong></p>
											</td>
											<td class="black-list header-instructions">
												<h3>myBama Username Blacklist</h3>
												<p class="description">This setting allows you to create a blacklist of myBama usernames that are <span class="black-list-color"><strong>NOT ALLOWED</strong></span> to login through myBama. <strong>The blacklist will take priority over the whitelist.</strong></p>
											</td>
										</tr>
										<tr>
											<td class="white-list textarea">
												<fieldset>
													<legend class="screen-reader-text">
														<span>myBama Whitelist</span>
													</legend>
													<textarea name="ua_mybama_cas_auth_settings[mybama_username_whitelist]" class="large-text code" rows="10"><?php
														
														// Convert from array to list
														if ( isset( $mybama_username_whitelist )
															&& ! empty( $mybama_username_whitelist )
															&& is_array( $mybama_username_whitelist ) ) {
																
															echo implode( "\n", $mybama_username_whitelist );
															
														}
														
													?></textarea>
													<p class="description instructions"><strong>Place one username per line.</strong></p>
												</fieldset>
											</td>
											<td class="black-list textarea">
												<fieldset>
													<legend class="screen-reader-text">
														<span>myBama Blacklist</span>
													</legend>
													<textarea name="ua_mybama_cas_auth_settings[mybama_username_blacklist]" class="large-text code" rows="10"><?php
														
														// Convert from array to list
														if ( isset( $mybama_username_blacklist )
															&& ! empty( $mybama_username_blacklist )
															&& is_array( $mybama_username_blacklist ) ) {
																
															echo implode( "\n", $mybama_username_blacklist );
															
														}
														
													?></textarea>
													<p class="description instructions"><strong>Place one username per line.</strong></p>
												</fieldset>
											</td>
										</tr>
									</tbody>
								</table>
								
							</td>
						</tr>
					</tbody>
				</table><?php
			
				break;
				
			//! Single Sign-On Settings
			case 'single_sign_on_settings':
			
				?><table class="form-table">
					<tbody>
						<tr>
							<th scope="row">Enable Single Sign-On</th>
							<td>
								
								<h4 class="ua-mybama-cas-auth-field-label">Do you want to enable single sign-on through myBama authentication?</h4>
				
								<fieldset>
									<legend class="screen-reader-text">
										<span>Do you want to enable single sign-on through myBama authentication?</span>
									</legend>
									<p class="description">If enabled, this will tie myBama authentication with your WordPress user profiles and log the user into WordPress when they authenticate themselves through myBama.</p>
									<p class="description"><span class="red"><strong>The unique identifier will be the user's myBama user name and their WordPress user name.</strong></span> In other words, once the user has been authenticated through myBama, it will attempt to find a user profile whose user name matches their myBama user name. By default, if a matching WordPress user does not exist, it will create a new WordPress user profile with their myBama information.</p>
									<p class="ua-mybama-cas-auth-field indent">
										<label><input name="ua_mybama_cas_auth_settings[enable_single_sign_on]" type="radio" value="1" class="tog"<?php checked( isset( $enable_single_sign_on ) && $enable_single_sign_on ); ?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label><input name="ua_mybama_cas_auth_settings[enable_single_sign_on]" type="radio" value="0" class="tog"<?php checked( ! isset( $enable_single_sign_on ) || ! $enable_single_sign_on ); ?> /> No</label>
									</p>
								</fieldset>
								
							</td>
						</tr>
						<tr>
							<th scope="row">WordPress Login Form</th>
							<td>
								
								<h4 class="ua-mybama-cas-auth-field-label">Do you want to add a "Login through myBama" button to the WordPress login form?</h4>
				
								<fieldset>
									<legend class="screen-reader-text">
										<span>Do you want to add a "Login through myBama" button to the WordPress login form?</span>
									</legend>
									<p class="description">If enabled, it will add a button to the WordPress login form, allowing your users the option to login through myBama instead of WordPress.</p>
									<p class="ua-mybama-cas-auth-field indent">
										<label><input name="ua_mybama_cas_auth_settings[sso_add_mybama_button_to_login_form]" type="radio" value="1" class="tog"<?php checked( ! ( isset( $sso_add_mybama_button_to_login_form ) && ! $sso_add_mybama_button_to_login_form ) ); ?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label><input name="ua_mybama_cas_auth_settings[sso_add_mybama_button_to_login_form]" type="radio" value="0" class="tog"<?php checked( isset( $sso_add_mybama_button_to_login_form ) && ! $sso_add_mybama_button_to_login_form ); ?> /> No</label>
									</p>
								</fieldset>
								
								<h4 class="ua-mybama-cas-auth-field-label">Do you want to hide the rest of the WordPress login form so users can only login through myBama (and not through WordPress)?</h4>
				
								<fieldset>
									<legend class="screen-reader-text">
										<span>Do you want to hide the rest of the WordPress login form so users can only login through myBama (and not through WordPress)?</span>
									</legend>
									<p class="description">If enabled, this will hide the WordPress username and password field on the login form so your users will only be able to see/use the "Login through myBama" button.</p>
									<p class="ua-mybama-cas-auth-field indent">
										<label><input name="ua_mybama_cas_auth_settings[sso_hide_wordpress_login_form]" type="radio" value="1" class="tog"<?php checked( ! ( isset( $sso_hide_wordpress_login_form ) && ! $sso_hide_wordpress_login_form ) ); ?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label><input name="ua_mybama_cas_auth_settings[sso_hide_wordpress_login_form]" type="radio" value="0" class="tog"<?php checked( ! isset( $sso_hide_wordpress_login_form ) || ( isset( $sso_hide_wordpress_login_form ) && ! $sso_hide_wordpress_login_form ) ); ?> /> No</label>
									</p>
								</fieldset>
								
							</td>
						</tr>
						<tr>
							<th scope="row">WordPress User Profiles</th>
							<td>
								
								<h4 class="ua-mybama-cas-auth-field-label">If a matching WordPress user profile DOES exist, do you want to automatically match/update their WordPress user data with their myBama user data?</h4>
				
								<fieldset>
									<legend class="screen-reader-text">
										<span>If a matching WordPress user profile DOES exist, do you want to automatically match/update their WordPress user data with their myBama user data?</span>
									</legend>
									<p class="description">If disabled, this will allow you, or the user, to edit/customize their WordPress user data and have it be different from their myBama user data. If enabled, it will update their WordPress user data to match their myBama user data every time they authenticate through myBama.</p>
									<p class="ua-mybama-cas-auth-field indent">
										<label><input name="ua_mybama_cas_auth_settings[sso_match_user_data]" type="radio" value="1" class="tog"<?php checked( ! ( isset( $sso_match_user_data ) && ! $sso_match_user_data ) ); ?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label><input name="ua_mybama_cas_auth_settings[sso_match_user_data]" type="radio" value="0" class="tog"<?php checked( isset( $sso_match_user_data ) && ! $sso_match_user_data ); ?> /> No</label>
									</p>
								</fieldset>
								
								<h4 class="ua-mybama-cas-auth-field-label">If a matching WordPress user profile does not exist, do you want to create one automatically?</h4>
								
								<fieldset>
									<legend class="screen-reader-text">
										<span>If a matching WordPress user profile does not exist, do you want to create one automatically?</span>
									</legend>
									<p class="ua-mybama-cas-auth-field indent">
										<label><input name="ua_mybama_cas_auth_settings[sso_create_matching_profile]" type="radio" value="1" class="tog"<?php checked( ! ( isset( $sso_create_matching_profile ) && ! $sso_create_matching_profile ) ); ?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label><input name="ua_mybama_cas_auth_settings[sso_create_matching_profile]" type="radio" value="0" class="tog"<?php checked( isset( $sso_create_matching_profile ) && ! $sso_create_matching_profile ); ?> /> No</label>
									</p>
								</fieldset>
								
								<h4 class="ua-mybama-cas-auth-field-label">If a new user is created, what user role do you want to assign them?</h4>
				
								<fieldset>
									<legend class="screen-reader-text">
										<span>If a new user is created, what user role do you want to assign them?</span>
									</legend>
									<p class="ua-mybama-cas-auth-field indent">
										<select name="ua_mybama_cas_auth_settings[sso_matching_profile_user_role]" id="sso_matching_profile_user_role">
											<?php wp_dropdown_roles( isset( $sso_matching_profile_user_role ) ? $sso_matching_profile_user_role : 'subscriber' ); ?>
										</select>
									</p>
								</fieldset>
								
							</td>
						</tr>
						<tr>
							<th scope="row">Restrict Access to Content through WordPress Login</th>
							<td>
								
								<h4 class="ua-mybama-cas-auth-field-label">Do you want to add a setting to your edit screen that allows you to require a WordPress login in order to view your content?</h4>
				
								<fieldset>
									<legend class="screen-reader-text">
										<span>Do you want to add a setting to your edit screen that allows you to require a WordPress login in order to view your content?</span>
									</legend>
									<p class="description">If enabled, this will add a meta box to your post's edit screen that will allow you to disable/enable authentication for each individual post.</p>
									<p class="ua-mybama-cas-auth-field indent">
										<label><input name="ua_mybama_cas_auth_settings[sso_enable_post_wordpress_authentication_setting]" type="radio" value="1" class="tog"<?php checked( isset( $sso_enable_post_wordpress_authentication_setting ) && $sso_enable_post_wordpress_authentication_setting ); ?> /> Yes</label>&nbsp;&nbsp;&nbsp;&nbsp;<label><input name="ua_mybama_cas_auth_settings[sso_enable_post_wordpress_authentication_setting]" type="radio" value="0" class="tog"<?php checked( ! isset( $sso_enable_post_wordpress_authentication_setting ) || ! $sso_enable_post_wordpress_authentication_setting ); ?> /> No</label>
									</p>
								</fieldset><?php
							
								// Get post types
								$public_post_types = get_post_types( array( 'public' => true ), 'objects' );
									
								// @TODO set it up so this row only shows up if they selected yes above
								?><h4 class="ua-mybama-cas-auth-field-label">Which post types would you like to restrict access to?</h4>
								
								<fieldset>
									<legend class="screen-reader-text"><span>Which post types would you like to restrict access to?</span></legend><?php
										
									if ( ! $public_post_types ) {
										
										?><p class="description">There are no public post types to choose from.</p><?php
											
									} else {
										
										// Keep track of which one we're printing
										$post_type_index = 0;
										
										?><p class="ua-mybama-cas-auth-field checkboxes indent"><?php
										
											foreach( $public_post_types as $post_type_key => $post_type_object ) {
												
												?><label><input name="ua_mybama_cas_auth_settings[sso_post_wordpress_authentication_setting_post_types][]" type="checkbox" value="<?php echo $post_type_key; ?>"<?php checked( isset( $sso_post_wordpress_authentication_setting_post_types ) && is_array( $sso_post_wordpress_authentication_setting_post_types ) && in_array( $post_type_key, $sso_post_wordpress_authentication_setting_post_types ) ); ?> /> <?php echo $post_type_object->label; ?></label><?php
													
												$post_type_index++;
													
											}
										
										?></p><?php
										
									}
									
								?></fieldset>
								
							</td>
						</tr>
						<tr>
							<th scope="row">Limit WordPress Logins</th>
							<td>
								
								<h4 class="ua-mybama-cas-auth-field-label">Do you want to restrict who can login as a WordPress user?</h4>
								
								<p class="description">The whitelist and blacklist settings allow you to customize which users will be allowed to login, or not login, as WordPress users after they have been authenticated through myBama. If you do not want to restrict WordPress login access, leave the settings blank. <strong>The blacklist will take priority over the whitelist.</strong></p>
								
								<table class="form-table">
									<tbody>
										<tr>
											<td class="white-list header-instructions">
												<h3>WordPress Login Whitelist</h3>
												<p class="description">This setting allows you to create a whitelist of myBama usernames that <span class="white-list-color"><strong>ARE ALLOWED</strong></span> to login to WordPress after they have been authenticated by myBama. If any usernames are defined, they will be the only usernames that are allowed. <strong>Leave this field blank to allow all usernames to login to WordPress.</strong></p>
											</td>
											<td class="black-list header-instructions">
												<h3>WordPress Login Blacklist</h3>
												<p class="description">This setting allows you to create a blacklist of myBama usernames that are <span class="black-list-color"><strong>NOT ALLOWED</strong></span> to login to WordPress after they have been authenticated by myBama. <strong>The blacklist will take priority over the whitelist.</strong></p>
											</td>
										</tr>
										<tr>
											<td class="white-list textarea">
												<fieldset>
													<legend class="screen-reader-text">
														<span>WordPress Login Whitelist</span>
													</legend>
													<textarea name="ua_mybama_cas_auth_settings[wordpress_login_whitelist]" class="large-text code" rows="10"><?php
														
														// Convert from array to list
														if ( isset( $wordpress_login_whitelist )
															&& ! empty( $wordpress_login_whitelist )
															&& is_array( $wordpress_login_whitelist ) ) {
																
															echo implode( "\n", $wordpress_login_whitelist );
															
														}
														
													?></textarea>
													<p class="description instructions"><strong>Place one username per line.</strong></p>
												</fieldset>
											</td>
											<td class="black-list textarea">
												<fieldset>
													<legend class="screen-reader-text">
														<span>WordPress Login Blacklist</span>
													</legend>
													<textarea name="ua_mybama_cas_auth_settings[wordpress_login_blacklist]" class="large-text code" rows="10"><?php
														
														// Convert from array to list
														if ( isset( $wordpress_login_blacklist )
															&& ! empty( $wordpress_login_blacklist )
															&& is_array( $wordpress_login_blacklist ) ) {
																
															echo implode( "\n", $wordpress_login_blacklist );
															
														}
														
													?></textarea>
													<p class="description instructions"><strong>Place one username per line.</strong></p>
												</fieldset>
											</td>
										</tr>
									</tbody>
								</table>
								
							</td>
						</tr>
					</tbody>
				</table><?php
					
				break;
				
			//! Shortcodes
			case 'shortcodes':
			
				?><table class="form-table">
					<tbody>
						<tr>
							<th scope="row">[require_mybama_auth]</th>
							<td>
								
								<p>Wrap any content in the <strong>[require_mybama_auth]</strong> shortcode to hide the content unless the user is authenticated/logged in through myBama.</p>
								
								<p>These are the shortcode's default settings:</p>
								
								<ul class="ua-mybama-cas-auth-shortcode-settings">
									<li><strong>show_logged_in_message:</strong> false</li>
									<li><strong>need_to_login_message:</strong> 'This content requires you to login through myBama.'
										<ul>
											<li><em>You can also <a href="http://codex.wordpress.org/Function_Reference/add_filter" target="_blank">filter the message</a> by hooking into the 'ua_mybama_cas_auth_need_to_mybama_login_shortcode_message' filter, which has 3 parameters: $need_to_login_message (the original message), $args (the shortcode arguments), and $login_url (the myBama login url).</em></li>
										</ul>
									</li>
								</ul>
								
							</td>
						</tr>
						<tr>
							<th scope="row">[require_wp_login]</th>
							<td>
								
								<p>Wrap any content in the <strong>[require_wp_login]</strong> shortcode to hide the content unless the user is logged in through WordPress.</p>
								
								<p>These are the shortcode's default settings:</p>
								
								<ul class="ua-mybama-cas-auth-shortcode-settings">
									<li><strong>show_logged_in_message:</strong> false</li>
									<li><strong>show_need_to_login_message:</strong> true</li>
									<li><strong>need_to_login_message:</strong> 'This content requires you to login.'
										<ul>
											<li><em>You can also <a href="http://codex.wordpress.org/Function_Reference/add_filter" target="_blank">filter the message</a> by hooking into the 'ua_mybama_cas_auth_need_to_wordpress_login_shortcode_message' filter, which has 3 parameters: $need_to_login_message (the original message), $args (the shortcode arguments), and $login_url (the WordPress login url).</em></li>
										</ul>
									</li>
									<li><strong>current_user_can:</strong> false
										<ul>
											<li><em>You can pass user capabilities, separated by commas, to restrict access via current_user_can().</em></li>
										</ul>
									</li>
									<li><strong>show_current_user_cant_message:</strong> true</li>
									<li><strong>current_user_cant_message:</strong> 'You do not have permission to view this content.'
										<ul>
											<li><em>You can also <a href="http://codex.wordpress.org/Function_Reference/add_filter" target="_blank">filter the message</a> by hooking into the 'ua_mybama_cas_auth_current_user_cant_shortcode_message' filter, which has 2 parameters: $current_user_cant_message (the original message) and $args (the shortcode arguments).</em></li>
										</ul>
									</li>
								</ul>
								
							</td>
						</tr>
					</tbody>
				</table><?php
				
				break;
				
		}
		
	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles( $hook_suffix ) {

		switch( $hook_suffix ) {
			
			// Add styles to our options page
			case $this->options_page_hook:
			
				// Enqueue the styles for our options page
				wp_enqueue_style( "{$this->plugin_id}-options", plugin_dir_url( __FILE__ ) . 'css/ua-mybama-cas-auth-admin-options.css', array(), $this->version, 'all' );

				// Enqueue the script for our options page
				wp_enqueue_script( "{$this->plugin_id}-options", plugin_dir_url( __FILE__ ) . 'js/ua-mybama-cas-auth-admin-options.js', array( 'jquery' ), $this->version, false );
				
				break;
			
			// Add styles to the "Edit Post" screen
			case 'post.php':
			case 'post-new.php':
			
				// Only add the styles if we added meta boxes
				if ( ! $this->added_meta_boxes )
					return;
			
				// Enqueue our styles for post pages
				wp_enqueue_style( "{$this->plugin_id}-post", plugin_dir_url( __FILE__ ) . 'css/ua-mybama-cas-auth-admin-post.css', array(), $this->version, 'all' );
				
				// Enqueue our scripts for post pages
				wp_enqueue_script( "{$this->plugin_id}-post", plugin_dir_url( __FILE__ ) . 'js/ua-mybama-cas-auth-admin-post.js', array( 'jquery' ), $this->version, false );
				
				break;
				
		}

	}
	
	/**
	 * Adds a settings link to the plugins page.
	 * 
	 * @since 	1.0.0
	 * @param	$actions - an array of plugin action links
	 * @param 	$plugin_file - path to the plugin file
	 * @param	$context - The plugin context. Defaults are 'All', 'Active', 'Inactive', 'Recently Activated', 'Upgrade', 'Must-Use', 'Drop-ins', 'Search'
	 * @return 	array - the links info after it has been filtered	 
	 */
	public function add_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {
		
		// Add link to our settings page
		$actions[ 'settings' ] = '<a href="' . add_query_arg( array( 'page' => $this->options_page_slug ), admin_url( 'options-general.php' ) ) . '" title="' . esc_attr__( 'Visit this plugin\'s settings page', $this->plugin_id ) . '">' . __( 'Settings' , $this->plugin_id ) . '</a>';
		
		return $actions;
		
	}

}
