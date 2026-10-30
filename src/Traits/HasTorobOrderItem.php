<?php

namespace Torob\Laravel\Traits;

/**
 * Default implementations of TorobOrderItemContract for Eloquent models.
 */
trait HasTorobOrderItem
{
    public function getTorobProductUrl(): string
    {
        return (string) ($this->product_url ?? $this->product?->getTorobPageUrl() ?? url("/products/{$this->product_id}"));
    }

    public function getTorobProductPrice(): int
    {
        return (int) ($this->product_price ?? $this->price ?? $this->unit_price ?? 0);
    }

    public function getTorobQuantity(): int
    {
        return (int) ($this->quantity ?? $this->qty ?? 1);
    }
}
