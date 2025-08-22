<?php

namespace OpenProfile\WordpressFactPod\OAuth;

use OpenProfile\WordpressFactPod\OAuth\Repositories\ClientRepository;
use OpenProfile\WordpressFactPod\Utils\AbstractRepository;
use WP_REST_Request;
use WP_Error;
use WP_REST_Response;
use Exception;

class Register extends AbstractRepository
{
    private ClientRepository $clientRepository;
    
    public function getTable(): string
    {
        return $this->clientRepository->getTable();
    }

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
        $this->clientRepository = new ClientRepository();
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'openprofile/oauth',
            '/register',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'register' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'name'         => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function($param) {
                            return !empty($param);
                        },
                    ),
                    'redirect_uri' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                        'validate_callback' => array($this, 'validate_redirect_uri'),
                    ),
                ),
            )
        );
    }

    public function validate_redirect_uri($redirectUri): WP_Error|true
    {
        // First check if it's a valid URL
        if (filter_var($redirectUri, FILTER_VALIDATE_URL) === false) {
            return new WP_Error(
                'invalid_redirect_uri',
                'The redirect URI must be a valid URL',
                array('status' => 400)
            );
        }
        
        // Extract domain from the redirect URI
        $domain = parse_url($redirectUri, PHP_URL_HOST);
        if (!$domain) {
            return new WP_Error(
                'invalid_redirect_uri',
                'Could not extract domain from the redirect URI',
                array('status' => 400)
            );
        }
        
        // Check if domain already exists using the ClientRepository
        if ($this->clientRepository->domainExists($domain)) {
            return new WP_Error(
                'domain_exists',
                'A client with this domain already exists',
                array('status' => 400)
            );
        }
        
        return true;
    }

    /**
     * Register a new OAuth client
     *
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function register(WP_REST_Request $request): WP_Error|WP_REST_Response
    {
        try {
            $clientId = wp_generate_uuid4();
            $clientSecretRaw = wp_generate_password(32, false);
            $clientSecret = password_hash($clientSecretRaw, PASSWORD_BCRYPT);
            
            $name = $request->get_param('name');
            $redirectUri = $request->get_param('redirect_uri');

            $this->clientRepository->createClient($clientId, $clientSecret, $name, $redirectUri);
            
            return new WP_REST_Response(
                array(
                    'client_id'     => $clientId,
                    'client_secret' => $clientSecretRaw,
                    'name'          => $name,
                    'redirect_uri'  => $redirectUri,
                    'grant_types'   => ClientRepository::GRAND_TYPES,
                ),
                201
            );
        } catch (Exception $e) {
            return new WP_Error(
                'client_registration_failed',
                'Failed to register client: ' . $e->getMessage(),
                array('status' => 500)
            );
        }
    }
}