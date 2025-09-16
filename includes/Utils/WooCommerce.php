<?php

namespace OpenProfile\WordpressFactPod\Utils;

class WooCommerce
{
    /**
     * Returns an array of top-level WooCommerce product categories
     * Each category includes: id, name, url, description, slug
     */
    public static function getTopLevelCategories(): array
    {
        $categories = [];

        if (class_exists('WooCommerce') && \taxonomy_exists('product_cat')) {
            $terms = \get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => true, // only categories with products
                'parent'     => 0,    // top-level only
            ]);

            if (!\is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $link = \get_term_link($term);

                    $categories[] = [
                        'id'          => $term->term_id,
                        'name'        => $term->name,
                        'url'         => !\is_wp_error($link) ? $link : '',
                        'description' => $term->description ?? '',
                        'slug'        => $term->slug ?? '',
                    ];
                }
            }
        }

        return $categories;
    }

    public static function getShopUrl(string $baseUrl): string
    {
        // Determine Shop URL if WooCommerce is active
        $shopUrl = $baseUrl . '/shop/';

        if (function_exists('wc_get_page_permalink')) {
            $shopLink = \wc_get_page_permalink('shop');
            if (!empty($shopLink) && !\is_wp_error($shopLink)) {
                $shopUrl = $shopLink;
            }
        }

        return $shopUrl;
    }
}
