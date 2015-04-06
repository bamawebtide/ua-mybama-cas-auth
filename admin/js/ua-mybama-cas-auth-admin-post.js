(function( $ ) {
	'use strict';
	
	// When the window is loaded...
	$( window ).load(function() {
		
		// These are the fieldsets for the authorization questions
		var $auth_restrict_sets = $( '.ua-mybama-cas-auth-restrict-view' );

        // Check each set
        $auth_restrict_sets.each( function() {

            // Check the set
            $( this ).check_ua_mybama_cas_auth_restrict_view();

            // Take action when the selects are changed
            $( this ).find( 'select' ).on( 'change', function() {

                // Check the set
                $( this ).closest( '.ua-mybama-cas-auth-restrict-view' ).check_ua_mybama_cas_auth_restrict_view();

            });

        });
		
	});

    // Is invoked by the fieldset
    jQuery.fn.check_ua_mybama_cas_auth_restrict_view = function() {

        // Is invoked by the fieldset
        var $the_fieldset = $( this );

        // Take action depending on the select value
        switch( $the_fieldset.find( 'select' ).val() ) {

             case 'yes_for_page':
             case 'yes_for_content':

                 // Make sure the fieldset wrapper knows we have two
                 $the_fieldset.closest( '.ua-mybama-cas-auth-side-by-side-fieldsets' ).addClass( 'two' );

                 // Show the neighbor search fields
                 $the_fieldset.siblings( '.ua-mybama-cas-auth-remove-from-search' ).slideDown();

                 break;

             default:

                 // Make sure the fieldset wrapper knows we only have one
                 $the_fieldset.closest( '.ua-mybama-cas-auth-side-by-side-fieldsets' ).removeClass( 'two' );

                 // Hide the neighbor search fields
                 $the_fieldset.siblings( '.ua-mybama-cas-auth-remove-from-search' ).slideUp();

                 break;

         }

    }

})( jQuery );
