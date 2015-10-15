(function( $ ) {
    'use strict';

    // When the window is loaded...
    $( window ).load(function() {

        // Will need to show form if they want to login with WordPress
        var $login_through_wp_button = $( '#ua-mybama-cas-auth-login-through-wp-button' );
        if ( $login_through_wp_button.length > 0 ) {

            // When the button is clicked, show the form
            $login_through_wp_button.on( 'click', function( $event ) {
                $event.preventDefault();

                // Add a class to the form so we can change the styles
                $( '#loginform').addClass( 'login-through-wp' );

                // Hide the button
                $login_through_wp_button.hide();

                // Hide the "Login Through myBama" button
                $( '#ua-mybama-cas-auth-login-through-mybama-button' ).hide();

                // Add "Login Through myBama" to the nav below
                $( '#nav' ).append( '<a id="ua-mybama-cas-auth-login-through-wp-nav" href="' + ua_mybama_cas_auth.login_mybama_url + '">Login Through myBama</a>' );

            });

        }

    });

})( jQuery );
