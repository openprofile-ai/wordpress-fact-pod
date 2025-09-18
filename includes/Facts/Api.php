<?php

namespace OpenProfile\WordpressFactPod\Facts;

use OpenProfile\WordpressFactPod\Facts\Repositories\FactsRepository;
use OpenProfile\WordpressFactPod\Utils\Http;
use OpenProfile\WordpressFactPod\Utils\WooCommerce;
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
            'openprofile',
            '/facts',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_user_facts_by_category'],
                'permission_callback' => [$this, 'permission_check'],
                'args'                => [
                    'category' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );
    }

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

        $categoryName = $this->resolveCategoryDisplayName($facts, $category);

        $listItems = [];
        $position = 1;
        foreach ($facts as $fact) {
            $listItems[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'item'     => [
                    '@type'           => 'Order',
                    '@id'             => $fact->getOrderItemUrl(),
                    'orderDate'       => $fact->getOrderDate(),
                    'totalPrice'      => (float) $fact->getPrice(),
                    'priceCurrency'   => $fact->getPriceCurrency(),
                    'seller'          => [
                        '@type' => 'Organization',
                        'name'  => get_bloginfo('name') ?: 'openprofile',
                    ],
                    'orderedItem'     => [
                        '@type'          => 'Product',
                        '@id'            => $fact->getProductIdUrl(),
                        'name'           => $fact->getProductName(),
                        'category'       => $fact->getCategoryName(),
                        'additionalType' => $fact->getCategoryUrl(),
                    ],
                ],
            ];
        }

        $collection = [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => 'Purchases - ' . $categoryName,
            'itemListElement' => $listItems,
        ];

        return new WP_REST_Response($collection, 200);
    }

    private function resolveCategoryDisplayName(array $facts, string $requested): string
    {
        foreach ($facts as $fact) {
            if (method_exists($fact, 'getCategoryName')) {
                $name = $fact->getCategoryName();
                if ($name !== '') {
                    return $name;
                }
            }
        }

        if (class_exists(WooCommerce::class) && WooCommerce::isActive()) {
            $term = WooCommerce::resolveCategoryTerm($requested);
            if ($term instanceof \WP_Term && !empty($term->name)) {
                return $term->name;
            }
        }

        return ucwords(str_replace(['-', '_'], ' ', $requested));
    }
}
