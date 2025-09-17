<?php

namespace OpenProfile\WordpressFactPod\Facts;

use OpenProfile\WordpressFactPod\Facts\Repositories\FactsRepository;
use OpenProfile\WordpressFactPod\Utils\Http;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Api
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        register_rest_route(
            'openprofile/facts',
            '/user/(?P<category>[a-zA-Z0-9_-]+)',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_user_facts_by_category'],
                'permission_callback' => [$this, 'permission_check'],
                'args'                => [
                    'category' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_key',
                    ],
                ],
            ]
        );
    }

    /**
     * Validate Bearer token and resolve user.
     * Return true to allow, or WP_Error to deny (REST API will use status).
     */
    public function permission_check(WP_REST_Request $request): bool|WP_Error
    {
        $user = Http::authenticate($request);
        if ($user instanceof \WP_User) {
            $request->set_param('_wpfp_user_id', $user->ID);
            return true;
        }
        return $user; // WP_Error
    }

    public function get_user_facts_by_category(WP_REST_Request $request): WP_REST_Response
    {
        $category = (string) $request->get_param('category');
        $userId   = (int) ($request->get_param('_wpfp_user_id') ?? get_current_user_id());

        $repo  = new FactsRepository();
        $facts = $repo->getByCategory($userId, $category);

        return new WP_REST_Response($facts, 200);
    }
}
