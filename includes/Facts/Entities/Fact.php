<?php

namespace OpenProfile\WordpressFactPod\Facts\Entities;

class Fact
{
    public function __construct(
        private int     $orderId,
        private int     $itemId,
        private int     $productId,
        private string  $productSku,
        private string  $productName,
        private string  $categoryName,
        private string  $categoryUrl,
        private ?string $orderDate,
        private string  $priceCurrency,
        private string  $price,
        private string  $productViewUrl
    )
    {
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductSku(): string
    {
        return $this->productSku;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getCategoryName(): string
    {
        return $this->categoryName;
    }

    public function getCategoryUrl(): string
    {
        return $this->categoryUrl;
    }

    public function getOrderDate(): ?string
    {
        return $this->orderDate;
    }

    public function getPriceCurrency(): string
    {
        return $this->priceCurrency;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getOrderViewUrl(): string
    {
        return $this->productViewUrl;
    }

    // schema.org-friendly HTTP identifiers
    public function getOrderItemUrl(): string
    {
        // Use the actual order view URL and add a stable fragment to point to the line item
        $orderUrl = $this->getOrderViewUrl();
        if ($orderUrl === '') {
            // Fallback if the My Account page URL cannot be resolved
            $orderUrl = \home_url('/my-account/view-order/' . $this->orderId);
        }
        return rtrim($orderUrl, '/') . '#item-' . $this->itemId;
    }

    public function getProductIdUrl(): string
    {
        // Prefer the real product permalink
        $permalink = \get_permalink($this->productId);
        if (!empty($permalink) && !\is_wp_error($permalink)) {
            return (string)$permalink;
        }
        // Fallback if permalinks are disabled
        return \home_url('/?p=' . $this->productId);
    }
}
