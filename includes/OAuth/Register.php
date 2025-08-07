<?php

namespace OpenProfile\WordpressFactPod\OAuth;

class Register
{
    public function __construct(string $privateKey, string $encryptionKey) {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route(
            'openprofile/oauth',
            '/register',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'register' ),
                'permission_callback' => '__return_true',
            )
        );
    }
}