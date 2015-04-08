(function( $ ) {
	'use strict';
	
	// When the window is loaded...
	$( window ).load(function() {

        // Other fields error handling depends on the test mode field
        var $test_mode_checkbox = $( '#ua-mybama-cas-auth-settings-enable-test-mode' );

        // These fields are required when test mode is enabled
        var $required_in_test_mode = $( '.ua-mybama-cas-auth-required-for-test-mode' );

        // These fields are NOT required when test mode is enabled
        var $not_required_in_test_mode = $( '.ua-mybama-cas-auth-not-required-for-test-mode' );

        // Check the settings in relation to the test mode checkbox right off the bath
        ua_mybama_cas_auth_check_test_mode_settings();

        // Check the settings in relation to the test mode checkbox when the test mode value has changed
        $test_mode_checkbox.on( 'change click', function() {
            ua_mybama_cas_auth_check_test_mode_settings();
        })

        // Check the settings in relation to the test mode checkbox when the required fields change
        $required_in_test_mode.on( 'change', function() {
            ua_mybama_cas_auth_check_test_mode_settings();
        });

        // Check the settings in relation to the test mode checkbox when the not required fields change
        $not_required_in_test_mode.on( 'change', function() {
            ua_mybama_cas_auth_check_test_mode_settings();
        });

        // Check the settings in relation to the test mode checkbox
        function ua_mybama_cas_auth_check_test_mode_settings() {

            // If test mode is enabled...
            if ( $test_mode_checkbox.prop( 'checked' ) ) {

                // Remove errors from those who are not dependent on test mode
                $not_required_in_test_mode.removeClass( 'error' );

                // Handle errors for those dependent on test mode depending on if they have a value
                $required_in_test_mode.each( function() {
                   if ( $( this ).val() != '' ) {
                       $( this ).removeClass( 'error' );
                   } else {
                       $( this ).addClass( 'error' );
                   }
                });

            } else {

                // Remove errors from those who are dependent on test mode
                $required_in_test_mode.removeClass( 'error' );

                // Handle errors for those not dependent on test mode depending on if they have a value
                $not_required_in_test_mode.each( function() {
                    if ( $( this ).val() != '' ) {
                        $( this ).removeClass( 'error' );
                    } else {
                        $( this ).addClass( 'error' );
                    }
                });

            }

        }
		
	});

})( jQuery );
