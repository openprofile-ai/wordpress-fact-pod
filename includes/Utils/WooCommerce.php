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
                'taxonomy' => 'product_cat',
                'hide_empty' => true,
                'parent' => 0,
            ]);

            if (!\is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $link = \get_term_link($term);

                    $categories[] = [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'url' => !\is_wp_error($link) ? $link : '',
                        'description' => $term->description ?? '',
                        'slug' => $term->slug ?? '',
                    ];
                }
            }
        }

        return $categories;
    }

    public static function getShopUrl(string $baseUrl): string
    {
        $shopUrl = $baseUrl . '/shop/';

        if (function_exists('wc_get_page_permalink')) {
            $shopLink = \wc_get_page_permalink('shop');
            if (!empty($shopLink) && !\is_wp_error($shopLink)) {
                $shopUrl = $shopLink;
            }
        }

        return $shopUrl;
    }

    public static function isActive(): bool
    {
        return class_exists('WooCommerce') && function_exists('wc_get_orders') && \taxonomy_exists('product_cat');
    }

    public static function resolveCategoryTerm(string $category): ?\WP_Term
    {
        if (!self::isActive()) {
            return null;
        }

        $term = \get_term_by('slug', $category, 'product_cat');
        if (!$term) {
            $term = \get_term_by('name', $category, 'product_cat');
        }
        return $term ?: null;
    }

    public static function getCustomerOrders(int $userId, array $statuses = ['completed', 'processing']): array
    {
        if (!self::isActive() || $userId <= 0) {
            return [];
        }

        $args = [
            'customer_id' => $userId,
            'status' => $statuses,
            'limit' => -1,
            'return' => 'objects',
        ];

        $orders = \wc_get_orders($args);
        return is_array($orders) ? $orders : [];
    }

    public static function productInCategory(\WC_Product $product, \WP_Term $categoryTerm): bool
    {
        if (!$product || !$categoryTerm) {
            return false;
        }

        $productId = $product->get_id();
        $terms = \wp_get_post_terms($productId, 'product_cat', ['fields' => 'ids']);
        if (\is_wp_error($terms) || empty($terms)) {
            return false;
        }

        $catId = (int)$categoryTerm->term_id;
        foreach ($terms as $tid) {
            $tid = (int)$tid;
            if ($tid === $catId) {
                return true;
            }
            $ancestors = \get_ancestors($tid, 'product_cat');
            if (in_array($catId, $ancestors, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return matching order line items for a user by category.
     * Each element contains: order, item, product, categoryTerm
     */
    public static function userPurchasedLineItemsByCategory(int $userId, string $category): array
    {
        $out = [];
        if (!self::isActive()) {
            return $out;
        }

        $term = self::resolveCategoryTerm($category);
        if (!$term) {
            return $out;
        }

        $orders = self::getCustomerOrders($userId);
        if (!$orders) {
            return $out;
        }

        foreach ($orders as $order) {
            if (!$order instanceof \WC_Order) {
                continue;
            }

            foreach ($order->get_items('line_item') as $item) {
                if (!$item instanceof \WC_Order_Item_Product) {
                    continue;
                }
                $product = $item->get_product();
                if (!$product instanceof \WC_Product) {
                    continue;
                }
                if (!self::productInCategory($product, $term)) {
                    continue;
                }

                $out[] = [
                    'order' => $order,
                    'item' => $item,
                    'product' => $product,
                    'categoryTerm' => $term,
                ];
            }
        }

        return $out;
    }
}
