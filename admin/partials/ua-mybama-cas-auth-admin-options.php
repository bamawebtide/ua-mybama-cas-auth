<?php

/**
 * Provides the view for the options page.
 *
 * @link       https://webtide.ua.edu
 * @since      1.0.0
 *
 * @package    UA_myBama_CAS_Auth
 * @subpackage UA_myBama_CAS_Auth/admin/partials
 */
 
?><div id="ua-mybama-cas-auth-options" class="wrap options">
	
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
	
	<form method="post" action="options.php"><?php
		
		// Handle the settings
		settings_fields( 'ua_mybama_cas_auth_settings' );
		
		?><div id="poststuff">
		
			<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
				<div id="post-body-content">
					
					<div id="postbox-container-1" class="postbox-container"><?php
						
						// Print side meta boxes
						do_meta_boxes( $this->options_page_hook, 'side', array() );
						
					?></div> <!-- #postbox-container-1 -->
					
					<div id="postbox-container-2" class="postbox-container"><?php
						
						// Print normal meta boxes
						do_meta_boxes( $this->options_page_hook, 'normal', array() );
						
						// Print advanced meta boxes
						do_meta_boxes( $this->options_page_hook, 'advanced', array() );
						
						// Print the submit button
						submit_button( 'Save Your Changes', 'primary', 'save_ua_mybama_cas_auth_settings', false, array( 'id' => 'save-ua-mybama-cas-auth-settings' ) );
									
					?></div> <!-- #postbox-container-2 -->
					
				</div> <!-- #post-body-content -->
			</div> <!-- #post-body -->	
			
		</div> <!-- #poststuff -->
		
	</form>
	
</div> <!-- .wrap -->