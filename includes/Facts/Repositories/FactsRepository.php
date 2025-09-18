<?php

namespace OpenProfile\WordpressFactPod\Facts\Repositories;

use OpenProfile\WordpressFactPod\Facts\Entities\Fact;
use OpenProfile\WordpressFactPod\Utils\WooCommerce;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;
use WP_Term;

class FactsRepository
{
    /**
     * @return Fact[]
     */
    public function getByCategory(int $userId, string $category): array
    {
        if ($userId <= 0 || $category === '') {
            return [];
        }

        if (!WooCommerce::isActive()) {
            return [];
        }

        $matches = WooCommerce::userPurchasedLineItemsByCategory($userId, $category);
        if (empty($matches)) {
            return [];
        }

        $facts = [];
        foreach ($matches as $row) {
            /** @var WC_Order $order */
            $order = $row['order'];
            /** @var WC_Order_Item_Product $item */
            $item = $row['item'];
            /** @var WC_Product $product */
            $product = $row['product'];
            /** @var WP_Term $categoryTerm */
            $categoryTerm = $row['categoryTerm'];

            $orderId = $order->get_id();
            $itemId = $item->get_id();
            $productId = $product->get_id();
            $sku = $product->get_sku();
            $name = $product->get_name();

            $price = (string)\wc_format_decimal($item->get_total(), 2);
            $currency = $order->get_currency();
            $date = $order->get_date_created() ? $order->get_date_created()->date('Y-m-d') : null;

            $categoryUrl = \get_term_link($categoryTerm);
            if (\is_wp_error($categoryUrl)) {
                $categoryUrl = '';
            }

            $myaccount = function_exists('wc_get_page_permalink') ? \wc_get_page_permalink('myaccount') : '';
            $viewUrl = $myaccount ? \wc_get_endpoint_url('view-order', (string)$orderId, $myaccount) : '';

            $facts[] = new Fact(
                $orderId,
                $itemId,
                $productId,
                $sku,
                $name,
                $categoryTerm->name,
                (string)$categoryUrl,
                $date,
                $currency,
                $price,
                $viewUrl
            );
        }

        return $facts;
    }
}
