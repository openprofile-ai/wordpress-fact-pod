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

    public function getProductViewUrl(): string
    {
        return $this->productViewUrl;
    }

    public function getFactId(): string
    {
        return 'urn:wp:order:' . $this->orderId . ':item:' . $this->itemId;
    }

    public function getProductUrn(): string
    {
        return $this->productSku !== ''
            ? 'urn:sku:' . $this->productSku
            : 'urn:wp:product:' . $this->productId;
    }
}
