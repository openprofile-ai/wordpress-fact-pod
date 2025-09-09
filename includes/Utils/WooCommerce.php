<?php

namespace OpenProfile\WordpressFactPod\Utils;

class WooCommerce
{
    /**
     * Returns an array of top-level WooCommerce product categories
     */
    public static function getTopLevelCategories(): array
    {
        $categories = [];

        if (class_exists('WooCommerce') && \taxonomy_exists('product_cat')) {
            $terms = \get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => false, // set true to include only categories with products
                'parent'     => 0,     // top-level only
            ]);

            if (!\is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[] = [
                        'id'   => $term->term_id,
                        'name' => $term->name,
                    ];
                }
            }
        }

        return $categories;
    }
}
