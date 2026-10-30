<?php

namespace Torob\Laravel\Contracts;

interface TorobOrderItemContract
{
    /**
     * Absolute URL to product page.
     */
    public function getTorobProductUrl(): string;

    /**
     * Price of a single unit in Toman.
     */
    public function getTorobProductPrice(): int;

    /**
     * Quantity of this item purchased (integer >= 1).
     */
    public function getTorobQuantity(): int;
}
